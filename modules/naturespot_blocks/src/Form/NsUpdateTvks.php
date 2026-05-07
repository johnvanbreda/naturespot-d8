<?php

namespace Drupal\naturespot_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Form for updating TVKs after a UKSI update.
 */
class NsUpdateTvks extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_tvks_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#markup' => '<p>Use this form to upload a CSV file provided after an update to the UKSI species on the warehouse</p>',
    ];

    $form['import_csv'] = [
      '#type' => 'managed_file',
      '#title' => t('Upload file'),
      '#upload_location' => 'public://importcsv/',
      '#default_value' => '',
      "#upload_validators"  => ["file_validate_extensions" => ["csv"]],
      '#states' => [
        'visible' => [
          ':input[name="File_type"]' => ['value' => t('Upload the file')],
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload CSV'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /* Fetch the array of the file stored temporarily in database */
    $csv_file = $form_state->getValue('import_csv');

    /* Load the object of the file by it's fid */
    $file = File::load($csv_file[0]);

    /* Save the file in database */
    $file->save();

    // You can use any sort of function to process your data. The goal is to
    // get each 'row' of data into an array.
    // If you need to work on how data is extracted, process it here.
    $data = $this->csvToArray($file->getFileUri());
    foreach ($data as $row) {
      $operations[] = [
        '\Drupal\naturespot_blocks\updateTvks::updateTvkItem',
        [$row],
      ];
    }

    $batch = [
      'title' => t('Importing Data...'),
      'operations' => $operations,
      'init_message' => t('Import is starting.'),
      'finished' => '\Drupal\naturespot_blocks\updateTvks::updateTvkItemCallback',
    ];
    batch_set($batch);
  }

  /**
   * Convert a CSV file to an array of row associative arrays.
   *
   * @param string $filename
   *   Name of the CSV file to load.
   */
  private function csvToArray($filename) {
    if (!file_exists($filename) || !is_readable($filename)) {
      return FALSE;
    }
    $header = NULL;
    $data = [];

    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
        if (!$header) {
          $header = $row;
        }
        else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

}

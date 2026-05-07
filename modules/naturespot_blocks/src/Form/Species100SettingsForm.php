<?php

namespace Drupal\naturespot_blocks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Species 100 settings form.
 */
class Species100SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'species_100_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'naturespot_blocks.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('naturespot_blocks.settings');
    $form['email'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Welcome email details'),
    ];
    $form['email']['species_100_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Welcome email subject'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('species_100_email_subject'),
      '#description' => $this->t('Welcome email subject. Use {{ first_name }} and {{ last_name }} as tokens that will be replaced with details from the user account.'),
    ];
    $form['email']['species_100_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Welcome email body'),
      '#required' => TRUE,
      '#default_value' => $config->get('species_100_email_body'),
      '#format' => 'basic_html',
      '#description' => $this->t('Welcome email body. Use {{ first_name }} and {{ last_name }} as tokens that will be replaced with details from the user account.'),
    ];
    $form['achievement_email'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Achievement email details'),
    ];
    $form['achievement_email']['species_100_achievement_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Achievement email subject'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('species_100_achievement_email_subject'),
      '#description' => $this->t('Achievement email subject. Use {{ first_name }} and {{ last_name }} as tokens that will be replaced with details from the user account.'),
    ];
    $form['achievement_email']['species_100_achievement_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Achievement email body'),
      '#required' => TRUE,
      '#default_value' => $config->get('species_100_achievement_email_body'),
      '#format' => 'basic_html',
      '#description' => $this->t('Achievement email body. Use {{ first_name }} and {{ last_name }} as tokens that will be replaced with details from the user account.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 50,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('naturespot_blocks.settings');
    $values = $form_state->getValues();
    $config->set('species_100_email_subject', $values['species_100_email_subject']);
    $config->set('species_100_email_body', $values['species_100_email_body']['value']);
    $config->set('species_100_achievement_email_subject', $values['species_100_achievement_email_subject']);
    $config->set('species_100_achievement_email_body', $values['species_100_achievement_email_body']['value']);
    $config->save();
    $this->messenger()->addMessage($this->t('The configuration settings have been saved.'));
  }

}

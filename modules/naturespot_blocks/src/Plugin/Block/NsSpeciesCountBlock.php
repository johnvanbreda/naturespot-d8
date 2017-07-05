<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a species count block for the right column.
 *
 * @Block(
 *   id = "ns_species_count_block",
 *   admin_label = @Translation("NatureSpot species count block"),
 * )
 */
class NsSpeciesCountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $output = \report_helper::get_report_data(array(
      'dataSource' => 'naturespot/species_count',
      'readAuth' => $readAuth,
      'extraParams' => array(
        'website_id' => $config->get('website_id')
      ),
      'mode'=>'report',
      'caching' => TRUE,
      'cachePerUser' => FALSE
    ));

    $r = "<p id=\"site-species-count\" class=\"in-box\">Total count of species: " . $output[0]['species_count'] . '</p>';
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => $r,
      '#cache' => [
        'max-age' => 3600
      ]
    );

  }

}
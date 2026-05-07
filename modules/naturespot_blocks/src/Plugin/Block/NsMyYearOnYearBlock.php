<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a user league block by count of records.
 *
 * @Block(
 *   id = "ns_my_year_on_year_block",
 *   admin_label = @Translation("NatureSpot my year on year block"),
 * )
 */
class NsMyYearOnYearBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = '<h2>Year on year</h2>';
    $r .= \report_helper::report_grid([
      'dataSource' => 'projects/naturespot/year_species_and_occurrence_counts_for_user',
      'readAuth' => $readAuth,
      'downloadLink' => TRUE,
      'extraParams' => [
        'taxon_list_id' => 15,
        'user_id' => hostsite_get_user_field('indicia_user_id', 0),
      ],
      'paramDefaults' => [
        'taxon_group_id' => '',
        'site_name' => '',
        'date_from' => '',
        'date_to' => '',
      ],
      'autoParamsForm' => FALSE,
      'mode' => 'report',
    ]);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => $r,
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'iform/reportgrid',
        ],
      ],
      '#cache' => [
        'contexts' => [
          // Output is different per user.
          'user',
        ],
        'tags' => [
          // Output updates when the user posts a record.
          'user_records:$userId',
        ],
        // Max age 0.5 hrs to ensure readAuth stays valid.
        'max-age' => 1800,
      ],
    ];
  }

}

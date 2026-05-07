<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a species list for the my records page.
 *
 * @Block(
 *   id = "ns_my_species_block",
 *   admin_label = @Translation("NatureSpot my species block"),
 * )
 */
class NsMySpeciesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = \report_helper::report_grid([
      'id' => 'my-species',
      'reportGroup' => 'my-records',
      'readAuth' => $readAuth,
      'dataSource' => 'projects/naturespot/species_for_user',
      'itemsPerPage' => 50,
      'rowId' => 'occurrence_id',
      'autoParamsForm' => FALSE,
      'downloadLink' => TRUE,
      'ajax' => TRUE,
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

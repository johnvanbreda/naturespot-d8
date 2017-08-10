<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

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
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = '<h2>Year on year</h2>';
    $r .= \report_helper::report_grid(array(
      'dataSource' => 'naturespot/year_species_and_occurrence_counts_for_cms_user',
      'readAuth' => $readAuth,
      'downloadLink' => true,
      'extraParams' => array(
        'taxon_list_id' => 8,
        'user_id'=>hostsite_get_user_field('indicia_user_id', 0),
      ),
      'paramDefaults' => array(
        'taxon_group_id'=>'',
        'site_name'=>'',
        'date_from'=>'',
        'date_to'=>''
      ),
      'autoParamsForm' => FALSE,
      'mode'=>'report'
    ));
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => $r,
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/reportgrid'
        )
      ),
      '#cache' => [
        'contexts' => [
          // output is different per user
          'user'
        ],
        'tags' => [
          // output updates when the user posts a record
          'user_records:$userId'
        ],
        'max-age' =>
          // max age 0.5 hrs to ensure readAuth stays valid
          1800
      ]
    );
  }

}
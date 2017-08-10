<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a params block for the my records page.
 *
 * @Block(
 *   id = "ns_my_records_params_block",
 *   admin_label = @Translation("NatureSpot my records params block"),
 * )
 */
class NsMyRecordsParamsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = '<h2>My records</h2>';
    $r .= \report_helper::report_grid(array(
      'id'=>'my-records',
      'reportGroup'=>'my-records',
      'readAuth' => $readAuth,
      'dataSource'=>'naturespot/occurrences_for_user',
      'itemsPerPage' => 20,
      'rowId'=>'occurrence_id',
      'paramsOnly'=>true,
      'downloadLink' => true,
      'extraParams' => array(
        'taxon_list_id' =>8,
        'user_id'=>hostsite_get_user_field('indicia_user_id', 0)
      ),
      'paramDefaults'=>array(
        'taxon_group_id'=>'',
        'site_name'=>'',
        'date_from'=>'',
        'date_to'=>''
      )
    ));
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#type' => 'inline_template',
      '#template' => $r,
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/reportgrid'
        )
      )
    );
  }

}
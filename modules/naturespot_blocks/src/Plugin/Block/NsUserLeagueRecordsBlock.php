<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a user league block by count of records.
 *
 * @Block(
 *   id = "ns_user_league_records_block",
 *   admin_label = @Translation("NatureSpot user league records block"),
 * )
 */
class NsUserLeagueRecordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = \report_helper::report_grid(array(
      'dataSource' => 'library/recorder_name/species_and_occurrence_counts',
      'readAuth' => $readAuth,
      'extraParams' => array(
        'website_id' => $config->get('website_id'),
        'date_from' => '',
        'date_to' => '',
        'survey_id' => '',
        'include_total' => 'yes',
        'limit' => 1000,
        'orderby' => 'occurrences_count',
        'sortdir' => 'desc'
      ),
      'columns'=>array(
        array('fieldname'=>'recorder_name', 'display'=>'Recorder'),
        array('fieldname'=>'species_count', 'display'=>'Total no. of species'),
        array('fieldname'=>'occurrences_count', 'display'=>'Total no. of records'),
      ),
      'includeAllColumns' => false,
      'mode'=>'report',
      'caching'=>true,
      'cachePerUser'=>false
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
<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

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
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    if (!$loggedIn) {
      return [
        '#markup' => '<p>' . $this->t('You must be logged in to view this report.') . '</p>',
        '#cache' => [
          'contexts' => ['user.roles:anonymous'],
        ],
      ];
    }
    $r = \report_helper::report_grid([
      'dataSource' => 'library/recorder_name/species_and_occurrence_counts',
      'readAuth' => $readAuth,
      'extraParams' => [
        'website_id' => $config->get('website_id'),
        'date_from' => '',
        'date_to' => '',
        'survey_id' => '',
        'include_total' => 'yes',
        'limit' => 1000,
        'orderby' => 'occurrences_count',
        'sortdir' => 'desc',
      ],
      'columns' => [
        [
          'fieldname' => 'recorder_name',
          'display' => 'Recorder',
        ],
        [
          'fieldname' => 'species_count',
          'display' => 'Total no. of species',
        ],
        [
          'fieldname' => 'occurrences_count',
          'display' => 'Total no. of records',
        ],
      ],
      'includeAllColumns' => FALSE,
      'mode' => 'report',
      'caching' => TRUE,
      'cachePerUser' => FALSE,
    ]);

    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'iform/reportgrid',
        ],
      ],
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

}

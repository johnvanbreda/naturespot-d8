<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a user league block by count of records.
 *
 * @Block(
 *   id = "ns_my_records_block",
 *   admin_label = @Translation("NatureSpot my records block"),
 * )
 */
class NsMyRecordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $r = '<h2>My records</h2>';
    $r .= \report_helper::report_grid([
      'id' => 'my-records',
      'reportGroup' => 'my-records',
      'readAuth' => $readAuth,
      'dataSource' => 'projects/naturespot/occurrences_for_user',
      'itemsPerPage' => 20,
      'rowId' => 'occurrence_id',
      'autoParamsForm' => FALSE,
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
      'columns' => [
        // Hide these columns here rather than in the report XML file so they
        // appear in the download.
        [
          'fieldname' => 'occurrence_comment',
          'visible' => FALSE,
        ],
        [
          'fieldname' => 'sample_comment',
          'visible' => FALSE,
        ],
        [
          'fieldname' => 'status_hint',
          'visible' => FALSE,
        ],
        [
          'fieldname' => 'verifier',
          'visible' => FALSE,
        ],
        [
          'fieldname' => 'verifier_comment',
          'visible' => FALSE,
        ],
        [
          'display' => 'Actions',
          'actions' => [
            [
              'url' => 'content/submit-records',
              'urlParams' => [
                'occurrence_id' => '{occurrence_id}',
              ],
              'img' => '/modules/iform/media/images/nuvola/package_editors-22px.png',
              'caption' => 'Edit this record',
            ],
            [
              'url' => 'details/record',
              'urlParams' => [
                'occurrence_id' => '{occurrence_id}',
              ],
              'img' => '/modules/iform/media/images/nuvola/find-22px.png',
              'caption' => 'View this record',
            ],
          ],
        ],
      ],
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

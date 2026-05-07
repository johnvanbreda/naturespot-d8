<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Provides a latest records block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_latest_records_block",
 *   admin_label = @Translation("NatureSpot wild place latest records block"),
 * )
 */
class NsWildPlaceLatestRecordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    // If not logged in, no pager, so a more generous first page.
    $limit = $loggedIn ? 15 : 50;
    $params = [
      'month' => 0,
      'taxon_group' => 'all',
      'site_name' => $siteName,
      'limit' => $limit,
    ];

    $options = [
      'id' => 'latest-records',
      'dataSource' => 'projects/naturespot/occurrences_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns' => FALSE,
      'columns' => [
        [
          'fieldname' => 'geom',
          'visible' => FALSE,
        ],
        [
          'fieldname' => 'common',
          'display' => 'Common Name',
          'template' => '<a href="{rootFolder}species_by_key?key={external_key}">{common}</a>',
        ],
        [
          'fieldname' => 'taxon',
          'display' => 'Latin Name',
          'template' => '<a href="{rootFolder}species_by_key?key={external_key}"><em>{taxon}</em></a>',
        ],
        [
          'fieldname' => 'date',
          'display' => 'Date Recorded',
        ],
        [
          'fieldname' => 'recorder',
          'display' => 'Recorded By',
        ],
      ],
      'itemsPerPage' => $limit,
      'autoParamsForm' => FALSE,
      'extraParams' => $params,
      'pager' => $loggedIn,
      'sortable' => $loggedIn,
      'forceNoFilterRow' => !$loggedIn,
    ];
    $r = '';
    if (!$loggedIn) {
      $r .= '<div class="alert alert-info">The images above and record list below are limited. To view more, please <a href="/user/login?destination=' .
        Url::fromRoute('<current>')->toString() . '">log in</a>.</div>';
    }
    $r .= \report_helper::report_grid($options);
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
    ];

  }

}

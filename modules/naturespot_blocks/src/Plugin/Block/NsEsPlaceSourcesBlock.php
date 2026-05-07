<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a data sources block for wild places.
 *
 * Adds Elasticsearch sources and functionality to the page, ready for other
 * ES blocks which act as placeholders pointing to these sources.
 *
 * @Block(
 *   id = "ns_es_place_sources_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch wild place data sources block"),
 * )
 */
class NsEsPlaceSourcesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteIdData = \report_helper::get_report_data([
      'dataSource' => 'projects/naturespot/site_id_by_name',
      'readAuth' => $readAuth,
      'extraParams' => [
        'website_id' => $config->get('website_id'),
        'site_name' => $siteName,
      ],
      'mode' => 'report',
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'cachetimeout' => 14400,
    ]);
    if (count($siteIdData) !== 1) {
      // Nullify ES searches.
      \report_helper::$indiciaData['filter'] = [
        'def' => [
          'location_id' => 0,
        ],
      ];
      return [
        '#markup' => Markup::create('<div class="alert alert-warning">No unique site found for this site name.</div>'),
        '#cache' => [
          // No cache please.
          'max-age' => 0,
        ],
      ];
    }
    \report_helper::$indiciaData['filter'] = [
      'def' => [
        'location_id' => $siteIdData[0]['id'],
        'website_list' => 8,
      ],
    ];

    return [
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

}

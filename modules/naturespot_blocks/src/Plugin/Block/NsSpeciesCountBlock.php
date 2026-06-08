<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

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
    iform_load_helpers(['report_helper', 'ElasticsearchReportHelper']);
    $config = \Drupal::config('iform.settings');
    try {
      $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
      $countData = \report_helper::get_report_data([
        'dataSource' => 'projects/naturespot/species_in_list_count',
        'readAuth' => $readAuth,
        'mode' => 'report',
        'caching' => TRUE,
        'cachePerUser' => FALSE,
        'cachetimeout' => 14400,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('naturespot_blocks')->alert("Fetching species count failed: " . $e->getMessage());
      return [
        '#markup' => Markup::create('<div class="alert alert-info">Server unavailable.</div>'),
      ];
    }
    $count = $countData[0]['count'];
    $year = Date('Y');
    $r = <<<HTML
      <div id="site-species-count" class="in-box">
        <p>All species/taxa on NatureSpot: $count</p>
        <div id="es-year-counts" style="display: none">
          <h3>$year running total</h3>
          <p>No. of records: <span id="year-records"></span></p>
          <p>No. of species: <span id="year-species"></span></p>
          <p>No. of new species added: <span id="new-species"></span>
          <a class="help-tip badge" href="https://www.naturespot.org.uk/species_totals">?</a></p>
        </div>
      </div>
      HTML;
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'naturespot_blocks/es-blocks',
        ],
      ],
      '#create_placeholder' => FALSE,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

  }

}

<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a records block for wild places.
 *
 * @Block(
 *   id = "ns_es_place_records_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch wild place records block"),
 * )
 */
class NsEsPlaceRecordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['ElasticsearchReportHelper']);
    \ElasticsearchReportHelper::enableElasticsearchProxy();
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $limit = $loggedIn ? 15 : 50;
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-records',
      'proxyCacheTimeout' => 1800,
      'filterBoolClauses' => [
        'must' => [
          [
            'query_type' => 'query_string',
            'value' => 'location.coordinate_uncertainty_in_meters:[0 TO 100]',
          ],
        ],
        'must_not' => [
          [
            'query_type' => 'term',
            'field' => 'identification.verification_status',
            'value' => 'R',
          ],
          [
            'query_type' => 'term',
            'field' => 'identification.verification_substatus',
            'value' => 3,
          ],
        ],
      ],
      'size' => $limit,
      'sort' => ['event.date_start' => 'desc'],
      // Filterpath needed to reduce size of response.
      'filterPath' => implode(',', [
        'hits.hits._source.event.date_start',
        'hits.hits._source.event.date_end',
        'hits.hits._source.event.recorded_by',
        'hits.hits._source.location.verbatim_locality',
        'hits.hits._source.taxon.vernacular_name',
        'hits.hits._source.taxon.accepted_name',
        'hits.hits._source.identification.verification_status',
        'hits.hits._source.identification.verification_substatus',
        'hits.hits._source.identification.query',
        'hits.hits._source.metadata.sensitive',
        'hits.hits._source.metadata.confidential',
        'hits.hits._source.occurrence.zero_abundance,',
        'hits.hits._source.metadata.created_by_id',
        'hits.hits.length',
        'hits.total',
      ]),
    ]);
    $r .= \ElasticsearchReportHelper::dataGrid([
      'id' => 'records-grid',
      'source' => 'es-records',
      'columns' => [
        ['caption' => '', 'field' => '#status_icons#'],
        ['caption' => 'Common name', 'field' => 'taxon.vernacular_name'],
        ['caption' => 'Scientific name', 'field' => 'taxon.accepted_name'],
        ['caption' => 'Date recorded', 'field' => '#event_date#'],
        ['caption' => 'Recorded by', 'field' => 'event.recorded_by'],
        ['caption' => 'Site name', 'field' => 'location.verbatim_locality'],
      ],
      'includeColumnSettingsTool' => FALSE,
      'includeFullScreenTool' => FALSE,
      'includePager' => $loggedIn,
      'includeFilterRow' => $loggedIn,
      'sortable' => $loggedIn,
      'cookies' => FALSE,
    ]);
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'naturespot_blocks/es-blocks',
        ],
      ],
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

}

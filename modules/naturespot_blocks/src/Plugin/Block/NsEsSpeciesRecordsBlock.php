<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a records block for wild places.
 *
 * @Block(
 *   id = "ns_es_species_records_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch species records block"),
 * )
 */
class NsEsSpeciesRecordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $nbnKey = $node->field_nbn_number->value;
    iform_load_helpers(['ElasticsearchReportHelper']);
    $enabled = \ElasticsearchReportHelper::enableElasticsearchProxy();
    if (!$enabled) {
      global $indicia_templates;
      return [
        '#markup' => str_replace('{message}', $this->t('Service unavailable.'), $indicia_templates['warningBox']),
      ];
    }
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $limit = $loggedIn ? 15 : 50;
    $cacheTimeout = $loggedIn ? 3600 : 3600 * 24;
    /*\helper_base::$indiciaData['filter'] = [
      'def' => [
        'taxa_taxon_list_external_key_list' => $nbnKey,
      ],
    ];*/
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-records',
      'proxyCacheTimeout' => $cacheTimeout,
      'filterBoolClauses' => [
        'must_not' => [
          [
            'query_type' => 'term',
            'field' => 'identification.verification_status',
            'value' => 'R',
          ],
        ],
        'must' => [
          [
            'query_type' => 'term',
            'field' => 'taxon.species_taxon_id',
            'value' => $nbnKey,
          ],
        ]
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

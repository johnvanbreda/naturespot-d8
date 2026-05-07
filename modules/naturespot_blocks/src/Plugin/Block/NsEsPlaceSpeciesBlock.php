<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a species block for wild places.
 *
 * @Block(
 *   id = "ns_es_place_species_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch wild place species block"),
 * )
 */
class NsEsPlaceSpeciesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['ElasticsearchReportHelper']);
    $enabled = \ElasticsearchReportHelper::enableElasticsearchProxy();
    if (!$enabled) {
      global $indicia_templates;
      return [
        '#markup' => str_replace('{message}', $this->t('Service unavailable.'), $indicia_templates['warningBox']),
      ];
    }
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-species',
      'mode' => 'compositeAggregation',
      'proxyCacheTimeout' => 1900,
      'size' => 5000,
      'includeFullScreenTool' => FALSE,
      'includePager' => FALSE,
      'includeFilterRow' => $loggedIn,
      'sort' => ['taxon.input_group' => 'asc', 'taxon.species' => 'asc'],
      'uniqueField' => 'taxon.accepted_taxon_id',
      'fields' => [
        'taxon.input_group',
        'taxon.vernacular_name',
        'taxon.accepted_name',
        'taxon.accepted_taxon_id',
      ],
      'aggregation' => [
        'last_date' => [
          'max' => [
            'field' => 'event.date_end',
            'format' => 'dd/MM/yyyy',
          ],
        ],
      ],
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
    ]);
    $statusFilter = <<<HTML
      <div class="form-inline pull-right">
        <div class="checkbox">
          <label><input type="checkbox" class="form-control" id="species-verified-only"> Verified</label>
        </div>
      </div>
HTML;

    if ($loggedIn) {
      $downloadControl = \ElasticsearchReportHelper::download([
        'id' => 'species-download',
        'source' => 'es-species',
        'caption' => $this->t('Download this list'),
      ]);
      $r .= <<<HTML
        <p>The species list below is based on all records for this site, some of which may be
          awaiting a verification check. To view just the species from verified records, tick the
          'Verified' box.
        </p>
        <div class="row">
          <div class="col-md-9">
            $downloadControl
          </div>
          <div class="col-md-3">
            $statusFilter
          </div>
        </div>
HTML;
    }
    else {
      $r = $statusFilter;
    }
    $r .= \ElasticsearchReportHelper::dataGrid([
      'id' => 'species-grid',
      'source' => 'es-species',
      'columns' => [
        ['caption' => 'Group', 'field' => 'taxon.input_group'],
        ['caption' => 'Common name', 'field' => 'taxon.vernacular_name'],
        ['caption' => 'Scientific name', 'field' => 'taxon.accepted_name'],
        [
          'caption' => 'Link',
          'field' => '#template:<a href="/species_by_key?key=[taxon-accepted_taxon_id]"><i class="fas fa-external-link-alt"></i></a>#',
        ],
        ['caption' => 'Last record', 'field' => 'last_date'],
      ],
      'cookies' => FALSE,
    ]);
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'naturespot_blocks/es-blocks',
          'naturespot_blocks/placeSpeciesTab',
        ],
      ],
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

}

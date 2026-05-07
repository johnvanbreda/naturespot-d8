<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a species count block for wild places.
 *
 * @Block(
 *   id = "ns_es_place_species_count_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch wild place species count block"),
 * )
 */
class NsEsPlaceSpeciesCountBlock extends BlockBase {

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
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $msg = $node->getType() === 'parish' ?
      'Parish/ward species count' : 'Site species count';
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-species-count',
      'proxyCacheTimeout' => 1800,
      'size' => 0,
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
      'aggregation' => [
        'species_count' => [
          'cardinality' => [
            'field' => 'taxon.accepted_taxon_id',
          ],
        ],
      ],
    ]);
    $r .= \ElasticsearchReportHelper::customScript([
      'id' => 'species-count-script',
      'source' => 'es-species-count',
      'functionName' => 'setSpeciesCount',
      'template' => "<p class=\"in-box\">$msg: <span id=\"site-spp-count\"></span></p>",
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


    /*
    // Store info for Elasticsearch.

    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
    ];*/

  }

}

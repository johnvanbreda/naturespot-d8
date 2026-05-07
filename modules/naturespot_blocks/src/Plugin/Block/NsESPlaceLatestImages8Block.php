<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides an images block for wild places.
 *
 * @Block(
 *   id = "ns_es_place_latest_images8_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch wild place latest_images8 block"),
 * )
 */
class NsESPlaceLatestImages8Block extends BlockBase {

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
    if (!$enabled) {
      global $indicia_templates;
      return [
        '#markup' => str_replace('{message}', $this->t('Service unavailable.'), $indicia_templates['warningBox']),
      ];
    }
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $limit = $loggedIn ? 8 : 16;
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-photos',
      'proxyCacheTimeout' => 1800,
      'filterBoolClauses' => [
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
        'must' => [
          [
            'query_type' => 'query_string',
            'value' => 'location.coordinate_uncertainty_in_meters:[0 TO 100]',
          ],
          [
            'nested' => 'occurrence.media',
            'query_type' => 'exists',
            'field' => 'occurrence.media.path',
          ],
        ],
      ],
      'size' => $limit,
      'sort' => ['metadata.created_on' => 'desc'],
    ]);
    $r .= \ElasticsearchReportHelper::cardGallery([
      'id' => 'photo-cards',
      'source' => 'es-photos',
      'columns' => [
        [
          'field' => '#taxon_label#',
        ],
      ],
      'includeFullScreenTool' => FALSE,
      'includePager' => $loggedIn,
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

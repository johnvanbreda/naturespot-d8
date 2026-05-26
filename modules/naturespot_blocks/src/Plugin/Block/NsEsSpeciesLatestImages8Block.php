<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;

/**
 * Provides an images block for species.
 *
 * @Block(
 *   id = "ns_es_species_latest_images8_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch species latest_images8 block"),
 * )
 */
class NsEsSpeciesLatestImages8Block extends BlockBase {

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
    /*\helper_base::$indiciaData['filter'] = [
      'def' => [
        'taxa_taxon_list_external_key_list' => $nbnKey,
      ],
    ];*/
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $cacheTimeout = $loggedIn ? 3600 : 3600 * 24;
    $r = \ElasticsearchReportHelper::source([
      'id' => 'es-photos',
      'proxyCacheTimeout' => $cacheTimeout,
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
            'nested' => 'occurrence.media',
            'query_type' => 'exists',
            'field' => 'occurrence.media.path',
          ],
          [
            'query_type' => 'term',
            'field' => 'taxon.species_taxon_id',
            'value' => $nbnKey,
          ],
        ],
      ],
      'size' => 8,
      'sort' => ['metadata.created_on' => 'desc'],
    ]);
    $columns = [
      [
        'caption' => '',
        'field' => '#status_icons#',
      ],
      [
        'field' => '#taxon_label#',
      ],
    ];
    $user = User::load(\Drupal::currentUser()->id());
    if ($user->hasPermission('create species content')) {
      $columns[] = [
        'field' => '#template:<a href="/image_edit_by_file?file=[path]">edit</a> :occurrence.media#',
      ];
    }
    $r .= \ElasticsearchReportHelper::cardGallery([
      'id' => 'photo-cards',
      'source' => 'es-photos',
      'columns' => $columns,
      'includeFullScreenTool' => FALSE,
      'includePager' => FALSE,
      'includeSortTool' => FALSE,
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

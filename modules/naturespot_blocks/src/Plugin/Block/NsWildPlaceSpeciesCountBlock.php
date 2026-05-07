<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a species count block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_species_count_block",
 *   admin_label = @Translation("NatureSpot wild place species count block"),
 * )
 */
class NsWildPlaceSpeciesCountBlock extends BlockBase {

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
    $output = \report_helper::get_report_data([
      'dataSource' => 'projects/naturespot/species_and_occurrence_counts_total_filtered_by_named_site',
      'readAuth' => $readAuth,
      'extraParams' => [
        'website_id' => $config->get('website_id'),
        'site_name' => $siteName,
        'date_from' => '',
        'date_to' => '',
        'survey_id' => '',
      ],
      'mode' => 'report',
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'cachetimeout' => 7200,
    ]);
    $msg = $node->getType() === 'parish' ?
      'Parish/ward species count' : 'Site species count';
    $r = "<p id=\"site-species-count\" class=\"in-box\">$msg: " . $output[0]['species_count'] . '</p>';
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
    ];

  }

}

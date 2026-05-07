<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a statistics block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_statistics_block",
 *   admin_label = @Translation("NatureSpot wild place latest_image block"),
 * )
 */
class NsWildPlaceStatisticsBlock extends BlockBase {

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
    $website_id = $config->get('website_id');
    $readAuth = \report_helper::get_read_auth($website_id, $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $loggedIn = hostsite_get_user_field('id') ? TRUE : FALSE;
    $options = [
      'id' => 'parish-stats',
      'dataSource' => 'projects/naturespot/site_species_group_stats',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 1000,
      'autoParamsForm' => TRUE,
      'extraParams' => [
        'site_name' => $siteName,
        'website_id' => 8,
        'orderby' => 'species_count',
        'sortdir' => 'desc',
        'include_total' => 'yes',
      ],
      'columns' => [
        [
          'fieldname' => 'taxongroup',
          'display' => 'Species group',
        ],
        [
          'fieldname' => 'species_count',
          'display' => 'Total no. of species',
        ],
        [
          'fieldname' => 'occurrences_count',
          'display' => 'Total no. of records',
        ],
      ],
      'includeAllColumns' => FALSE,
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'cacheTimeout' => $loggedIn ? 60 * 5 : 60 * 60 * 24,
    ];
    if (!$loggedIn) {
      // Ensure public bots can't trawl report.
      $options['pager'] = FALSE;
      $options['sortable'] = FALSE;
      $options['ajax'] = FALSE;
    }
    $r = '';
    if ($loggedIn) {
      $r .= \report_helper::report_download_link($options) . '<br/>';
    }
    $r .= \report_helper::report_grid($options);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#cache' => [
        'contexts' => ['user.roles:anonymous'],
        'max-age' => $loggedIn ? 0 : 60 * 60 * 24,
      ],
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'iform/fancybox',
          'iform/reportgrid',
        ],
      ],
    ];
  }

}

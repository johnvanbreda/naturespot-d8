<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a species list block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_species_block",
 *   admin_label = @Translation("NatureSpot wild place species block"),
 * )
 */
class NsWildPlaceSpeciesBlock extends BlockBase {

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
    $params = [
      'site_name' => $siteName,
      'limit' => 5000,
    ];
    $loggedIn = \Drupal::currentUser()->id() > 0;
    $options = [
      'id' => 'species-list',
      'dataSource' => 'projects/naturespot/species_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns' => FALSE,
      'columns' => [
        ['fieldname' => 'taxon_group'],
        [
          'fieldname' => 'common',
          'template' => '<a href="{rootFolder}species_by_key?key={external_key}">{common}</a>',
        ],
        [
          'fieldname' => 'taxon',
          'template' => '<a href="{rootFolder}species_by_key?key={external_key}"><em>{taxon}</em></a>',
        ],
        ['fieldname' => 'date'],
      ],
      'autoParamsForm' => TRUE,
      'extraParams' => $params,
      'paramDefaults' => ['taxon_group_id' => '', 'date_from' => '', 'date_to' => ''],
      'pager' => FALSE,
      'forceNoFilterRow' => !$loggedIn,
      'itemsPerPage' => 5000,
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'cacheTimeout' => 7200,
    ];
    $r = '';
    if ($loggedIn) {
      $r = '<br/>' . \report_helper::report_download_link($options);
    }
    $r .= '<br/>' . \report_helper::report_grid($options);

    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#cache' => [
        // No cache please.
        'max-age' => 0,
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

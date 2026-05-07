<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_map_block",
 *   admin_label = @Translation("NatureSpot wild place map block"),
 * )
 */
class NsWildPlaceMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    iform_load_helpers(['map_helper', 'report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \map_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $options = [
      'presetLayers' => ['google_satellite'],
      'indiciaWMSLayers' => [
        'Parishes' => 'naturespot:vc55-parishes',
        //'naturespot:RutlandParishes',
        'Leicestershire & Rutland boundary' => 'naturespot:LeicestershireHybrid',
        'Paths' => 'naturespot:NaturespotPaths'
      ],
      'editLayer' => FALSE,
      'jsPath' => base_path() . 'modules/iform/media/js/',
      'initial_lat' => 52.67721,
      'initial_long' => -1.08765,
      'initial_zoom' => 9,
      'width' => '100%',
      'height' => 350,
    ];
    $olOptions = [
      'theme' => base_path() . 'modules/iform/media/js/theme/default/style.css',
    ];
    $r = \map_helper::map_panel($options, $olOptions);
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $r .= \report_helper::report_map([
      'readAuth' => $readAuth,
      'dataSource' => 'projects/naturespot/site_boundary',
      'extraParams' => ['site_name' => $siteName],
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'clickable' => FALSE,
    ]);
    \map_helper::$javascript .= <<<JS
mapInitialisationHooks.push(function(div) {
  var locations= new OpenLayers.Layer.WMS('Locations', 'https://warehouse1.indicia.org.uk/geoserver/wms', {
      layers: 'naturespot:vw_locations_without_containers',
      transparent: true,
      styles:'site_boundary_ns'
  }, {
      singleTile: true,
      isBaseLayer: false,
      sphericalMercator: true,
      opacity: 0.5
  });
  div.map.addLayer(locations);
  // Want the main site boundary on top.
  window.setTimeout(() => {
    indiciaData.reportlayer.setZIndex(1000);
  }, 200);
});
JS;
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
          'iform/openlayers',
          'iform/indiciaMapPanel',
          'iform/fancybox',
          'iform/reportgrid',
        ],
      ],
    ];
  }

}

<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

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
      return array();
    }
    iform_load_helpers(array('map_helper', 'report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \map_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $options = array(
      'presetLayers' => array('osm'),
      'editLayer' => false,
      'jsPath'=> base_path() . 'modules/iform/media/js/',
      'initial_lat'=>52.67721,
      'initial_long'=>-1.08765,
      'initial_zoom'=>9,
      'width'=>'100%',
      'height'=>350
    );
    $olOptions=array('theme' => base_path() . 'modules/iform/media/js/theme/default/style.css');
    $r = \map_helper::map_panel($options, $olOptions);
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $r .= \report_helper::report_map(array(
      'readAuth' => $readAuth,
      'dataSource' => 'naturespot/site_boundary',
      'extraParams' => array('site_name' => $siteName),
      'caching' => true,
      'cachePerUser' => false,
      'clickable'=>false
    ));
    \map_helper::$javascript .= <<<JS
mapInitialisationHooks.push(function(div) {
  var locations= new OpenLayers.Layer.WMS('Locations', 'http://warehouse1.indicia.org.uk:8080/geoserver/wms', { 
      layers: 'naturespot:vw_locations', 
      transparent: true,
      styles:'site_boundary_red'
  }, { 
      singleTile: true, 
      isBaseLayer: false, 
      sphericalMercator: true, 
      opacity: 0.5
  });
  div.map.addLayer(locations);
});
JS;
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => SafeMarkup::format($r, array()),
      '#cache' => [
        'max-age' => 0, // no cache please
      ],
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/openlayers',
          'iform/indiciaMapPanel',
          'iform/fancybox',
          'iform/reportgrid',
          //'iform/googlemaps'
        )
      ),
    );
  }

}
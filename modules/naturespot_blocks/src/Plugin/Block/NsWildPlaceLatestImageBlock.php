<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;

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
      drupal_set_message('NsWildPlaceMapBlock must be placed on a parish or wild place node page');
      return array();
    }
    iform_load_helpers(array('map_helper', 'report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \map_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $options = array(
      'presetLayers' => array('google_satellite'),
      'editLayer' => false,
      'jsPath'=>'/sites/all/modules/iform/media/js/',
      'initial_lat'=>52.67721,
      'initial_long'=>-1.08765,
      'initial_zoom'=>9,
      'width'=>415,
      'height'=>350
    );
    $olOptions=array('theme'=>'/sites/all/modules/iform/media/js/theme/default/style.css');
    $r = \map_helper::map_panel($options, $olOptions);
    $r .= \report_helper::report_map(array(
      'readAuth' => $readAuth,
      'dataSource' => 'naturespot/site_boundary',
      'extraParams' => array('site_name' => $node->field_parish->value),
      'caching' => true,
      'cachePerUser' => false,
      'clickable'=>false
    ));

    $script = \helper_base::get_scripts(\helper_base::$javascript, \helper_base::$late_javascript,
      \helper_base::$onload_javascript, FALSE, TRUE);

    \map_helper::$required_resources=array();
    \map_helper::dump_javascript();
    return array(
      '#markup' => '<div id="map" style="width: 415px; height: 350px;"></div>',
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/openlayers',
          'iform/indiciaMapPanel',
          'iform/fancybox',
          'iform/reportgrid',
          'iform/googlemaps',
          'naturespot_blocks/nswildplacemapblock'
        )
      ),
    );
  }

}
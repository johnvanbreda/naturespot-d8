<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a VC map block for species.
 *
 * @Block(
 *   id = "ns_species_vc_map_block",
 *   admin_label = @Translation("NatureSpot species VC map block"),
 * )
 */
class NsSpeciesVcMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      drupal_set_message('NsWildPlaceLatestImageBlock must be placed on a parish or wild place node page');
      return array();
    }
    iform_load_helpers(array('map_helper', 'data_entry_helper'));
    $config = \Drupal::config('iform.settings');
    $website_id = $config->get('website_id');
    $readAuth = \map_helper::get_read_auth($website_id, $config->get('password'));
    $nbnKey = $node->field_nbn_number->value;
    $fetchOpts = array(
      'table' => 'cache_taxa_taxon_list',
      'extraParams' => $readAuth + array(
          'external_key' => $nbnKey,
          'taxon_list_id' => 8,
          'preferred' => TRUE
        )
    );
    $prefRecords = \data_entry_helper::get_population_data($fetchOpts);
    $datasets = '';
    $taxon_meaning_id = $prefRecords[0]['taxon_meaning_id'];
    \map_helper::$javascript .= <<<JS
mapSettingsHooks.push(function(opts) {
  var nbn = new OpenLayers.Layer.WMS(
    "NBN Atlas data", 
    "https://records-ws.nbnatlas.org/ogc/wms/reflect" + 
      "?q=lsid:$nbnKey" +
      "&fq=$datasets", 
    {
      LAYERS: "ALA:occurrences", 
      CRS:"EPSG:3857", 
      FORMAT:"image/png", 
      TRANSPARENT: true,
      ENV: "colourmode:osgrid;gridlabels:true;gridres:singlegrid;opacity:1;color:ebef33"
    },
    {
      isBaseLayer: false,
      opacity: 0.5,
    }
  );
  var filter="website_id=$website_id";
  filter += " AND taxon_meaning_id=$taxon_meaning_id";
  filter += " AND(record_status<>'R')";  
  filter += " AND(strLength(entered_sref)>6)";
  var distLayer = new OpenLayers.Layer.WMS(
          "NatureSpot data",
          "http://warehouse1.indicia.org.uk:8080/geoserver/wms",
          {layers: "indicia:occurrences_for_map", transparent: true, CQL_FILTER: filter , styles: "dist_point_red"},
          {isBaseLayer: false, sphericalMercator: true, singleTile: true}
    );
  opts.layers.splice(0, 0, nbn, distLayer);
});
JS;
    $opts = array(
      'readAuth' => $readAuth,
      'presetLayers' => array('osm'),
      'editLayer' => FALSE,
      'searchLayer' => TRUE,
      'layers' => array(),
      'initial_lat' => 52.67721,
      'initial_long' => -1.02265,
      'initial_zoom' => 9,
      'width' => '100%',
      'height' => 370,
      'standardControls' => array('layerSwitcher', 'panZoom'),
      'rememberPos' => FALSE,
      'indiciaWMSLayers' => array('naturespot:Leicestershire')
    );
    $r = \data_entry_helper::georeference_lookup(array(
      'georefPreferredArea' => 'Leicestershire',
      'autoCollapseResults' => TRUE,
      'helpText' => 'Enter a town or village to see local records',
      'driver' => 'google_places'
    ));
    $r .= \map_helper::map_panel($opts);
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
          //'iform/googlemaps'
        )
      ),
    );

  }

}
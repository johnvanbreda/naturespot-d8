<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

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
      return [];
    }
    $nid = $node->id();
    iform_load_helpers(['map_helper', 'data_entry_helper']);
    $config = \Drupal::config('iform.settings');
    $website_id = $config->get('website_id');
    $readAuth = \map_helper::get_read_auth($website_id, $config->get('password'));
    $nbnKey = $node->field_nbn_number->value;
    $datasets = '';
    \map_helper::$javascript .= <<<JS
mapSettingsHooks.push(function(opts) {
  var nbn = new OpenLayers.Layer.WMS(
    "NBN Atlas data",
    "https://records-ws.nbnatlas.org/ogc/wms/reflect" +
      "?q=lsid:$nbnKey" +
      "&fq=$datasets" +
      "&fq=occurrence_status:present" +
      "&fq=identification_verification_status:(%22Accepted%22%20OR%20%22Accepted%20-%20considered%20correct%22%20OR%20%22Accepted%20-%20correct%22%20OR%20%22verified%22)",
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
  var filter="taxa_taxon_list_external_key='$nbnKey'";
  // Layer has built in filtering for size of grid square < 1km, website ID and record status.
  var distLayer = new OpenLayers.Layer.WMS(
    "NatureSpot data",
    "https://warehouse1.indicia.org.uk/geoserver/wms",
    {
      layers: "indicia:naturespot_occurrences_for_map",
      transparent: true,
      CQL_FILTER: filter ,
      styles: "naturespot:dist_point_ns"
    },
    {
      isBaseLayer: false,
      sphericalMercator: true,
      singleTile: true,
      minScale: 80000
    }
  );
  var distLayerGridSquares = new OpenLayers.Layer.Vector(
    "NatureSpot data 1km grid squares",
    {
      maxScale: 80001
    }
  );
  opts.layers.splice(0, 0, nbn, distLayer, distLayerGridSquares);
  // Example: Fetch data from Elasticsearch and process the response
  $.ajax({
    url: '/iform/esproxy/rawsearch/$nid',
    type: 'post',
    data: {
      proxyCacheTimeout: 3600,
      size: 0,
      query: {
        bool: {
          must: [
            {
              term: {'taxon.species_taxon_id': '$nbnKey'}
            },
            {
              term: {'metadata.website.id': '8'}
            },
            {
              term: {'identification.verification_status': 'V'}
            }
          ]
        }
      },
      aggs: {
        by_grid_square: {
          terms: {
            field: 'location.grid_square.1km.centre',
            size: 10000
          },
          aggs: {
            by_year: {
              terms: {
                field: 'event.year',
                size: 1,
                order: { '_key': 'desc' }
              }
            }
          }
        }
      }
    },
    success: function(response) {
      var features = [];
      const epsg4326 = new OpenLayers.Projection("EPSG:4326");
      const epsg27700 = new OpenLayers.Projection("EPSG:27700");
      const epsg900913 = new OpenLayers.Projection("EPSG:900913");
      $.each(response.aggregations.by_grid_square.buckets, function(index, bucket) {
        var lonlat = bucket.key.split(' ');
        var sqCentre = OpenLayers.Projection.transform(
          {x: lonlat[0], y: lonlat[1]},
          epsg4326,
          epsg27700
        );
        var left = Math.floor(sqCentre.x / 1000) * 1000;
        var bottom = Math.floor(sqCentre.y / 1000) * 1000;
        var right = left + 1000;
        var top = bottom + 1000;
        var geom = OpenLayers.Geometry.fromWKT(`POLYGON((\${left} \${bottom},\${right} \${bottom},\${right} \${top},\${left} \${top},\${left} \${bottom}))`);
        geom.transform(epsg27700, epsg900913);
        let colour = bucket.by_year.buckets.length > 0 ? '#C70039' : '#AAAAAA';
        if (bucket.by_year.buckets.length > 0) {
          const year = bucket.by_year.buckets[0].key;
          if (year < 2020) {
            colour = '#2E86C1';
          } else if (year < 2025) {
            colour = '#0614B9';
          }
        }
        var feature = new OpenLayers.Feature.Vector(
          geom,
          {
            year: bucket.by_year.buckets.length ? bucket.by_year.buckets[0].key : 'unknown'
          },
          {
            fillColor: colour,
            fillOpacity: 0.7,
            strokeColor: colour,
            strokeWidth: 1
          }
        );
        features.push(feature);
      });
      distLayerGridSquares.addFeatures(features);
    },
    error: function(xhr, status, error) {
      console.error('Elasticsearch request failed:', error);
    }
  });
});

JS;
    $opts = [
      'readAuth' => $readAuth,
      'presetLayers' => ['osm'],
      'editLayer' => FALSE,
      'searchLayer' => TRUE,
      'layers' => [],
      'initial_lat' => 52.67721,
      'initial_long' => -1.02265,
      'initial_zoom' => 9,
      'width' => '100%',
      'height' => 420,
      'standardControls' => ['layerSwitcher', 'panZoom'],
      'rememberPos' => FALSE,
      'indiciaWMSLayers' => ['naturespot:LeicestershireHybrid'],
    ];
    $r = \data_entry_helper::georeference_lookup([
      'georefPreferredArea' => 'Leicestershire',
      'autoCollapseResults' => TRUE,
      'helpText' => 'Enter a town or village to see local records',
      'driver' => 'nominatim',
    ]);
    $r .= \map_helper::map_panel($opts);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'iform/openlayers',
          'iform/indiciaMapPanel',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

  }

}

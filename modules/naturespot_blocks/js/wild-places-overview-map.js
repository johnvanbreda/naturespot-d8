(function ($) {

  indiciaData.imagesPath='/modules/iform/media/images/';
  indiciaData.warehouseUrl='https://warehouse1.indicia.org.uk/';
  indiciaData.proxyUrl='/modules/iform/client_helpers/proxy.php';
  indiciaData.protocol='https';
  indiciaData.jQuery = jQuery; //saving the current version of jQuery
  indiciaData.website_id = 8;
  indiciaData.documentReady = 'done';
  indiciaData.onloadFns.push(function() {
    var srefId = $.fn.indiciaMapPanel.defaults.srefId;
    window.wildPlacesLayer = new OpenLayers.Layer.WMS('Wild Places', 'https://warehouse1.indicia.org.uk/geoserver/wms', {
        layers: 'naturespot:vw_locations_without_containers',
        transparent: true,
        styles:'site_boundary_ns'
    }, {
        singleTile: true,
        isBaseLayer: false,
        sphericalMercator: true,
        opacity: 0.5
    });

    indiciaFns.on('change', '.views-exposed-form :input:visible', {}, function() {
      $('input[name="title_exact"]').val('');
    });

    var selectSite = function(features) {
      var site = features.length === 0 ? '' : features[0].data.name;
      $('.views-exposed-form select').val('All');
      $('input[name="title"]').val('');
      $('input[name="title_exact"]').val(site);
      $('.views-exposed-form .form-submit').click();
      return '';
    }

    $('#map').indiciaMapPanel({
      indiciaSvc: 'https://warehouse1.indicia.org.uk/',
      indiciaGeoSvc: 'https://warehouse1.indicia.org.uk/geoserver/',
      divId: 'map',
      class: '',
      width: '100%',
      height: '450px',
      jsPath: '/modules/iform/media/js/',
      clickForSpatialRef: true,
      gridRefHintInFooter: true,
      gridRefHint: false,
      presetLayers: ['google_hybrid'],
      editLayer: false,
      initial_lat: 52.67721000000000231011654250323772430419921875,
      initial_long: -1.087650000000000005684341886080801486968994140625,
      initial_zoom: 9,
      clickableLayersOutputMode: 'customFunction',
      clickableLayersOutputFn: selectSite,
      clickableLayersOutputDiv: 'dummy',
      bing_api_key: '',
      indiciaWMSLayers: {
        'Parishes': 'naturespot:vc55-parishes',
        //'naturespot:RutlandParishes',
        'Leicestershire & Rutland boundary': 'naturespot:LeicestershireHybrid',
        'Paths': 'naturespot:NaturespotPaths'
      },
      clickableLayers: [window.wildPlacesLayer]
    }, {
      theme: '/modules/iform/media/js/theme/default/style.css'
    });
    indiciaData.mapdiv.map.addLayer(window.wildPlacesLayer);
    if (srefId && $('#' + srefId).length && $('#' + srefId).val()!==''
        && indiciaData.mapdiv.settings.initialBoundaryWkt===null && indiciaData.mapdiv.settings.initialFeatureWkt===null) {
      $('#'+srefId).change();
    }
  });
  window.onload = function() {
    indiciaData.windowLoad = 'started';
    // ensure this is only run after document.ready
    if (indiciaData.documentReady === 'done') {
      $.each(indiciaData.onloadFns, function(idx, fn) {
        fn();
      });
    }
    indiciaData.windowLoaded = 'done';
  }
})(jQuery);
jQuery(document).ready(function docReady($) {
  var helpItems = [];
  /*mapInitialisationHooks.push(function (div) {
    var vector = new OpenLayers.Layer.Vector("Paths", {
      projection: "EPSG:4326",
      strategies: [new OpenLayers.Strategy.Fixed()],
      protocol: new OpenLayers.Protocol.HTTP({
        url: "/sites/default/files/layers/prow-paths4326.geojson",
        format: new OpenLayers.Format.GeoJSON()
      }),
      styleMap: new OpenLayers.StyleMap({'default':{
        strokeColor: "#CCCC00",
        strokeWidth: 3
      }})
    });

    indiciaData.mapdiv.map.addLayers([vector]);
  });*/

  // Add the help items to each label.
  if ($('#help-data').length > 0) {
    helpItems = $('#help-data').html().split(';');
    $.each(helpItems, function addHelp() {
      var tokens = this.split('=');
      var tip;
      if (this.trim() === '') {
        // Skip if blank.
        return true;
      }
      if (tokens.length !== 2) {
        alert('Incorrect help data format - each item must have a fieldname, equals sign and text');
      }
      tip = '<span class="help-tip badge" data-toggle="tooltip" title="' + tokens[1] + '">?</span>';
      $.each($('label:contains("' + tokens[0] + '"),th:contains("' + tokens[0] + '")'), function() {
        // Double check (in case a short term matches a larger label).
        if ($(this).text().match(new RegExp('^' + tokens[0] + ':?$'))) {
          $(this).append(tip);
        }
      });
      return true;
    });
    $('[data-toggle="tooltip"]').tooltip({html:true});
  }
});
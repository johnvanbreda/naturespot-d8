jQuery(document).ready(function docReady($) {
  var helpItems = [];
  var currentRow = null;
  $('table.species-grid').after(
    '<div id="recording-advice"></div>'
  );

  function setMessage(msg) {
    if (msg) {
      $('#recording-advice').html('<div class="alert alert-warning row">' +
        '<span class="glyphicon glyphicon-info-sign col-md-1" style="font-size: 3em"></span>' +
        '<div class="col-md-11">' + msg + '</div></div>');
    }
    else {
      $('#recording-advice').html('');
    }
  }
  hook_species_checklist_new_row.push(function hookNewRow(data, row) {
    $.ajax({
      url: '/species_recording_advice?key=' + data.external_key,
      dataType: 'json',
      success: function(response) {
        setMessage(response.msg)
        $(row).attr('data-msg', response.msg);
        currentRow = row;
        $(row).click(function() {
          if (currentRow !== this) {
            setMessage($(this).attr('data-msg'));
            currentRow = this;
          }
        });
      }
    });
  });
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

  // Comment length warnings.
  $('#sample\\:comment').change(function() {
    if ($('#sample\\:comment').val().length > 50) {
      $('#sample-comment-length-warning').slideDown();
    } else {
      $('#sample-comment-length-warning').slideUp();
    }
  });
  indiciaFns.on('change', '.scCommentCell input', {}, function() {
    let exceeded=false;
    $.each($('.scCommentCell input'), function() {
      if ($(this).val().length >250) {
        exceeded = true;
        $(this).addClass('warning');
      } else {
        $(this).removeClass('warning');
      }
    });
    if (exceeded) {
      $('#occurrence-comment-length-warning').slideDown();
    } else {
      $('#occurrence-comment-length-warning').slideUp();
    }
  });
});
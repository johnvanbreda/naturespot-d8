jQuery(document).ready(function($) {

  /**
   * A callback for the verification grid which loads the photo ID RAG icons.
   */
  window.loadRag = function (div) {
    var keys = [];
    // Find the keys we need to load.
    $.each($('#records-grid tbody tr'), function() {
      var doc = JSON.parse($(this).attr('data-doc-source'));
      if (doc.taxon && doc.taxon.accepted_taxon_id && doc.taxon.accepted_taxon_id.indexOf('?') === -1) {
        // Store key for AJAX request.
        keys.push(doc.taxon.accepted_taxon_id);
        // Tag cell where icon should go.
        $(this).find('td.col-1')
          .addClass('tvk-' + doc.taxon.accepted_taxon_id)
          .data('tvk', doc.taxon.accepted_taxon_id);
      }
    });
    // Grab the RAG icons for each key.
    $.ajax({
      dataType: 'json',
      url: '/species_rag',
      data: 'keys=' + keys.join(','),
      success: function (data) {
        $.each(data, function(key) {
          $('.tvk-' + key).append(this);
        });
      }
    });
  };
  // Set up the callback.
  indiciaData.onloadFns.push(function() {
    $('#records-grid')[0].callbacks.populate.push(loadRag);
  });

  $('.idc-recordDetails').before('<div id="recording-advice" class="alert alert-info"></div>');

  indiciaFns.on('click', '#records-grid tbody', {}, function (evt) {
    var tvk = $(evt.target).closest('tr').find('.col-1').data('tvk');
    if (tvk.match(/^[A-Z0-9]{16}$/)) {
      $.ajax({
        dataType: 'json',
        url: '/species_recording_advice',
        data: 'key=' +tvk,
        success: function (data) {
          // For each row, if we have any advice display it.
          if (data.msg === '') {
            $('#recording-advice').hide();
          }
          else {
            $('#recording-advice').show();
            $('#recording-advice').html(data.msg.replace('<p>', '<p><strong>Recording advice:</strong> '));
          }
        }
      });
    }
  });

  // Default to not update determiner for NatureSpot.
  $('#no-update-determiner').attr('checked', true);
});
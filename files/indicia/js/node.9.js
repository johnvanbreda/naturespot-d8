jQuery(document).ready(function docReady($) {
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
});

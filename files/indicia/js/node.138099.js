jQuery(document).ready(function($) {

  /**
   * A callback for the verification grid which loads the photo ID RAG icons.
   */
  window.loadRag = function (div) {
    var keys = [];
    // Ensure original callback called.
    callback_verification_grid();
    // Find the keys we need to load.
    $.each($('#verification-grid .record-tvk'), function() {
      if (this.textContent.match(/^[A-Z0-9]{16}$/)) {
        keys.push(this.textContent);
      }
    });
    // Grab the RAG icons for each key.
    $.ajax({
      dataType: 'json',
      url: '/species_rag',
      data: 'keys=' + keys.join(','),
      success: function (data) {
        // For each row, if we have a RAG icon, display it.
        $.each($('#verification-grid .record-tvk'), function() {
          if (this.textContent.match(/^[A-Z0-9]{16}$/)) {
            if (typeof data[this.textContent] !== 'undefined') {
              $(this).closest('td').append(data[this.textContent]);
            }
          }
        });
      }
    });
  };
  // Set up the callback.
  indiciaData.onloadFns.push(function() {
    indiciaData.reports.verification.grid_verification_grid[0].settings.callback = 'loadRag';
  });

  $('#record-details-tabs').before('<div id="recording-advice" class="alert alert-info"></div>');

  $('table.report-grid tbody').click(function (evt) {
    indiciaData.mainTaxonListId = 8;
    var tvk = $(evt.target).closest('tr').find('.record-tvk')[0].textContent;
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
});
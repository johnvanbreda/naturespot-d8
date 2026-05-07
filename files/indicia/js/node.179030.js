jQuery(document).ready(function docReady($) {
  'use strict';
  indiciaFns.formatTable = function(el, sourceSettings, response) {
    $.getJSON(indiciaData.warehouseUrl + 'index.php/services/report/requestReport?' +
          'report=projects/naturespot/sample_thanks.xml' +
          '&reportSource=local&sample_id=' + drupalSettings.path.currentQuery.id +
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
          '&mode=json&callback=?',
      function getSample(data) {
        var done = [];
        $('table#output tbody').hide();
        $.each(data, function eachRow() {
          if ($.inArray(this.external_key, done) === -1) {
            $('table#output tbody').append('<tr id="tvk-' + this.external_key + '">' +
              '<td>' + (this.default_common_name || '') + '</td>' +
              '<td><em>' + this.preferred_taxon + '</em></td>' +
              '<td class="data-records">0</td>' +
              '<td class="data-yourlast"><mark>New record!</mark></td>' +
              '<td class="data-alllast"><mark class="new-for-year">New this year!</mark></td>' +
              '</tr>');
            done.push(this.external_key);
          }
        });
        $.each(response.aggregations.samples.buckets, function() {
          $('#tvk-' + this.key.accepted_taxon_id).find('.data-records').html(this.doc_count);
          $('#tvk-' + this.key.accepted_taxon_id).find('.data-yourlast').html(
            new Date(this.not_sample.you.latest.value_as_string).toLocaleDateString()
          );
          $('#tvk-' + this.key.accepted_taxon_id).find('.data-yourlast').html(
            new Date(this.not_sample.latest.value_as_string).toLocaleDateString()
          );
        });
        $('table#output tbody').show();
      }
    );
  };
});
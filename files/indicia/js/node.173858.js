jQuery(document).ready(function docReady($) {
  'use strict';
  indiciaFns.formatTable = function(el, sourceSettings, response) {
    $.getJSON(indiciaData.warehouseUrl + 'index.php/services/report/requestReport?' +
          'report=projects/naturespot/sample_thanks.xml' +
          '&reportSource=local&sample_id=' + drupalSettings.path.currentQuery.id +
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
          '&mode=json&callback=?',
      function sampleReturned(data) {
        var sampleThisYear;
        var done = [];
        var link;
        $('table#output tbody').hide();
        $.each(data, function eachRow() {
          sampleThisYear = new Date(this.date_start).getFullYear() === new Date().getFullYear();
          if ($.inArray(this.external_key, done) === -1) {
            link = this.external_key
              ? '<a title="View this species\' page" href="/species_by_key?key=' + this.external_key + '"><em>' + this.preferred_taxon + '</em></a>'
              : '<em>' + this.preferred_taxon + '</em>';
            $('table#output tbody').append('<tr id="tvk-' + this.external_key + '">' +
              '<td>' + (this.default_common_name || '') + '</td>' +
              '<td>' + link + '</td>' +
              '<td class="data-records">0</td>' +
              '<td class="data-yourlast"><mark>New record!</mark></td>' +
              '<td class="data-alllast"><mark>New record!</mark></td>' +
              '</tr>');
            done.push(this.external_key);
          }
        });
        $.each(response.aggregations.samples.buckets, function() {
          var tdYourData = $('#tvk-' + this.key.accepted_taxon_id).find('.data-yourlast');
          var tdAllData = $('#tvk-' + this.key.accepted_taxon_id).find('.data-alllast');
          var lastDate;
          var cell;
          $('#tvk-' + this.key.accepted_taxon_id).find('.data-records').html(this.doc_count);
          if (this.not_sample.you.latest.value_as_string) {
            lastDate = new Date(this.not_sample.you.latest.value_as_string);
            cell = lastDate.toLocaleDateString();
            if (sampleThisYear && lastDate.getFullYear() < new Date().getFullYear()) {
              cell += ' <mark class="new-for-year">New this year!</mark>';
            }
            tdYourData.html(cell);
          }
          if (this.not_sample.latest.value_as_string) {
            lastDate = new Date(this.not_sample.latest.value_as_string);
            cell = lastDate.toLocaleDateString();
            if (sampleThisYear && lastDate.getFullYear() < new Date().getFullYear()) {
              cell += ' <mark class="new-for-year">New this year!</mark>';
            }
            tdAllData.html(cell);
          }
        });
        $('table#output tbody').show();
      }
    );
  };
});
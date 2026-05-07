var speciesNeedle;
var groupsNeedle;

jQuery(document).ready(function ($) {
  "use strict";

  var startYear = new Date().getFullYear() - 9;
  var thisYear = new Date().getFullYear();
  var i;
  for (i = startYear; i <= thisYear; i++) {
    $('<th>').appendTo($('#years-by-groups-table thead tr')).text(i);
  }
  $('<th>').appendTo($('#years-by-groups-table thead tr')).text('Total');

  $.each(indiciaData.esSources, function() {
    // Splice in a 10 year date range filter.
    if (this.id === 'years-by-groups') {
      this.filterBoolClauses.must[0].value = 'event.year:[' + startYear + ' TO ' + thisYear + ']';
    }
  });

  indiciaFns.myTotalsResponse = function(el, sourceSettings, response) {
    $('#total-records').html(response.hits.total.value);
    // Set defaults in case not in response.
    $('#total-records-V').html('0');
    $('#total-records-C0').html('0');
    $('#total-records-R').html('0');
    $('.plausible-data-row').hide();
    $.each(response.aggregations.status.buckets, function() {
      var status = this.key;
      if (status === 'V' || status === 'R') {
        $('#total-records-' + status).html(this.doc_count);
      } else if (status === 'C') {
        // Might need to split down to pending and plausible.
        $.each(this.substatus.buckets, function() {
          $('#total-records-' + status + this.key).html(this.doc_count);
          if (this.key === 3) {
            $('.plausible-data-row').show();
          }
        });
      }
    });
    $('#total-species').html(response.aggregations.exclude_if_not_accepted.species_count.value);
  }

  indiciaFns.yearsByGroupsResponse = function(el, sourceSettings, response) {
    var startYear = new Date().getFullYear() - 9;
    $('#years-by-groups-table tbody tr').remove();
    var recordsTotalsTr = $('<tr class="records-row totals">').appendTo($('#years-by-groups-table tbody'));
    var speciesTotalsTr = $('<tr class="species-row totals">').appendTo($('#years-by-groups-table tbody'));
    var recordsTotals = Array(10).fill(0);
    var speciesTotals = Array(10).fill(0);
    $.each(response.aggregations.by_group.buckets, function() {
      // For each group, set up a row of zeros for records and species.
      var recordsTr = $('<tr class="records-row">').appendTo($('#years-by-groups-table tbody'));
      var recordsDataCells = [];
      var speciesTr = $('<tr class="species-row">').appendTo($('#years-by-groups-table tbody'));
      var speciesDataCells = [];
      var i;
      for (i = 0; i < 10; i++) {
        recordsDataCells.push($('<td>').text('0'));
        speciesDataCells.push($('<td>').text('0'));
      }
      $('<th scope="row" rowspan="2">').appendTo(recordsTr).text(this.key);
      // Copy in the data values.
      $.each(this.by_year.buckets, function() {
        $(recordsDataCells[this.key - startYear]).text(this.doc_count);
        $(speciesDataCells[this.key - startYear]).text(this.species_count.value);
        recordsTotals[this.key - startYear] += this.doc_count;
        speciesTotals[this.key - startYear] += this.species_count.value;
      });
      // Add the records data cells to the row.
      $.each(recordsDataCells, function() {
        $(this).appendTo(recordsTr);
      });
      // Plus the row total.
      $('<td class="totals">').appendTo(recordsTr).text(this.doc_count);
      // Add the species data cells to the row.
      $.each(speciesDataCells, function() {
        $(this).appendTo(speciesTr);
      });
      // Plus the row total.
      $('<td class="totals">').appendTo(speciesTr).text(this.species_count.value);
    });
    // Add the totals rows to the top.
    $('<th scope="row" rowspan="2">').text('Total').appendTo(recordsTotalsTr);
    $.each(recordsTotals, function() {
      $('<td>').text(this).appendTo(recordsTotalsTr);
    });
    $.each(speciesTotals, function() {
      $('<td>').text(this).appendTo(speciesTotalsTr);
    });
    // Plus the total records total.
    $('<td>').appendTo(recordsTotalsTr).text(response.hits.total.value);
    // And the total species total
    $('<td>').appendTo(speciesTotalsTr).text(response.aggregations.species_count.value);
  }

  function dateParse(inputDate) {
    var parts = inputDate.split(/\D+/);
    var order = indiciaData.dateFormat.split(/[^A-Za-z]+/);;
    if (parts.length === 3) {
      return parts[order.indexOf('Y')] + '-' + parts[order.indexOf('m')] + '-' + parts[order.indexOf('d')];
    }
    return 'invalid';
  }

  $('#apply-filter').click(function applyFilter() {
    // Copy date values across, with some formatting tolerance.
    var dateFrom = $('#filter-date-from').val();
    var dateTo = $('#filter-date-to').val();
    // If input on a browser which doesn't support date input type, may need to reformat the date.
    if (dateFrom) {
      if (!dateFrom.match(/\d{4}-\d{2}-\d{2}/)) {
        dateFrom = dateParse(dateFrom);
      }
    }
    if (dateTo) {
      if (!dateTo.match(/\d{4}-\d{2}-\d{2}/)) {
        dateTo = dateParse(dateTo);
      }
    }
    if (dateFrom === 'invalid' || dateTo === 'invalid') {
      alert('The date format you have input is invalid.');
    }
    else if ($('#filter-date-from-query').val() !== dateFrom || $('#filter-date-to-query').val() !== dateTo) {
      $('#filter-date-from-query').val(dateFrom);
      $('#filter-date-to-query').val(dateTo);
      $('#filter-date-from-query').change();
    }
  });

  indiciaFns.outputSpecies100Summary = function(el, sourceSettings, data) {
    var countedSpecies = 0;
    var countedGroups = 0;
    var tbody = $(el).find('tbody');
    var spTotal = 0;
    data.aggregations.group.buckets.sort(function(a, b) {
      var vA = 0;
      var vB = 0;
      $.each(a.by_status.buckets, function() {
        if (this.key === 'V') {
          vA = this.species_count.value;
        }
      });
      $.each(b.by_status.buckets, function() {
        if (this.key === 'V') {
          vB = this.species_count.value;
        }
      });
      return vB - vA;
    });
    $.each(data.aggregations.group.buckets, function(i) {
      var groupBucket = this;
      var v = 0;
      var c = 0;
      var r = 0;
      var spClass;
      var grpClass;

      $.each(groupBucket.by_status.buckets, function() {
        if (this.key === 'V') {
          v = this.species_count.value;
        }
        else if (this.key === 'C') {
          c = this.species_count.value;
        }
        else if (this.key === 'R') {
          r = this.species_count.value;
        }
      });
      spClass = v > 0 && spTotal < 100 ? ' scores' : '';
      spTotal += Math.min(20, v);
      grpClass = (i < 10 && v > 0) ? ' scores' : '';
      $(tbody).append('<tr><th scope="row">' + this.key + '</th>' +
        '<td class="species-value numeric' + spClass + '"><span>' + (v > 0 ? Math.min(20, v) : '') + '</span></td>' +
        '<td class="group-value' + grpClass + '">' + (i < 10 && v > 0 ? '&#10004;' : '') + '</td>' +
        '<td class="status status-v numeric"><span>' + v + '</span></td>' +
        '<td class="status status-c numeric"><span>' + c + '</span></td>' +
        '<td class="status status-r numeric"><span>' + r + '</span></td>' +
        '</tr>');
      countedSpecies += Math.min(20, v);
      if (v > 0) {
        countedGroups++;
      }
    });
    countedSpecies = Math.min(100, countedSpecies);
    countedGroups = Math.min(10, countedGroups);
    $('#species-achieved').html(countedSpecies + ' / 100');
    $('#groups-achieved').html(countedGroups  + ' / 10');
    if (countedSpecies >= 100 && countedGroups >= 10) {
      $('.success-message').append('<p class="alert alert-info"><strong>Congratulations on passing the 100 species challenge!</strong><br/>You can download your certificate using the link below.<br/>' +
        '<a href="/print/pdf/node/242897" title="Download certificate"><i class="fas fa-file-pdf fa-4x"></i></a><p>');
    }

    jQuery.getScript( "https://d3js.org/d3.v3.min.js" )
    .done(function() {
      function init(selector) {
        var barWidth, chart, chartInset, degToRad, repaintGauge,
          height, margin, numSections, padRad, percToDeg, percToRad,
          radius, svg, totalPercent, width, sectionPerc, el;

        numSections = 1;
        sectionPerc = 1 / numSections / 2;
        padRad = 0.025;
        chartInset = 10;

        // Orientation of gauge:
        totalPercent = .75;

        el = d3.select(selector);

        margin = {
          top: 20,
          right: 20,
          bottom: 30,
          left: 20
        };

        width = el[0][0].offsetWidth - margin.left - margin.right;
        height = width / 2;
        radius = width / 2;
        barWidth = 40 * width / 300;

        /*
          Utility methods
        */
        percToDeg = function(perc) {
          return perc * 360;
        };

        percToRad = function(perc) {
          return degToRad(percToDeg(perc));
        }

        degToRad = function(deg) {
          return deg * Math.PI / 180;
        }

        // Create SVG element
        svg = el.append('svg').attr('width', width + margin.left + margin.right).attr('height', height + margin.top + margin.bottom);

        // Add layer for the panel
        chart = svg.append('g').attr('transform', "translate(" + ((width + margin.left) / 2) + ", " + ((height*2 + margin.top) / 2) + ")");
        chart.append('path').attr('class', "arc chart-filled");
        chart.append('path').attr('class', "arc chart-empty");

        var arc2 = d3.svg.arc().outerRadius(radius - chartInset).innerRadius(radius - chartInset - barWidth);
        var arc1 = d3.svg.arc().outerRadius(radius - chartInset).innerRadius(radius - chartInset - barWidth);

        repaintGauge = function (perc)
        {
          var next_start = totalPercent;
          var arcStartRad = percToRad(next_start);
          var arcEndRad = arcStartRad + percToRad(perc / 2);
          next_start += perc / 2;


          arc1.startAngle(arcStartRad).endAngle(arcEndRad);

          arcStartRad = percToRad(next_start);
          arcEndRad = arcStartRad + percToRad((1 - perc) / 2);

          arc2.startAngle(arcStartRad + padRad).endAngle(arcEndRad);


          chart.select(".chart-filled").attr('d', arc1);
          chart.select(".chart-empty").attr('d', arc2);

        }


        var Needle = (function() {

          /**
            * Helper function that returns the `d` value
            * for moving the needle
          **/
          var recalcPointerPos = function(perc) {
            var centerX, centerY, leftX, leftY, rightX, rightY, thetaRad, topX, topY;
            thetaRad = percToRad(perc / 2);
            centerX = 0;
            centerY = 0;
            topX = centerX - this.len * Math.cos(thetaRad);
            topY = centerY - this.len * Math.sin(thetaRad);
            leftX = centerX - this.radius * Math.cos(thetaRad - Math.PI / 2);
            leftY = centerY - this.radius * Math.sin(thetaRad - Math.PI / 2);
            rightX = centerX - this.radius * Math.cos(thetaRad + Math.PI / 2);
            rightY = centerY - this.radius * Math.sin(thetaRad + Math.PI / 2);
            return "M " + leftX + " " + leftY + " L " + topX + " " + topY + " L " + rightX + " " + rightY;
          };

          function Needle(el) {
            this.el = el;
            this.len = width / 3;
            this.radius = this.len / 6;
          }

          Needle.prototype.render = function() {
            this.el.append('circle').attr('class', 'needle-center').attr('cx', 0).attr('cy', 0).attr('r', this.radius);
            return this.el.append('path').attr('class', 'needle').attr('d', recalcPointerPos.call(this, 0));
          };

          Needle.prototype.moveTo = function(perc) {
            var self,
                oldValue = this.perc || 0;

            this.perc = perc;
            self = this;

            // Reset pointer position
            this.el.transition().delay(100).ease('quad').duration(200).select('.needle').tween('reset-progress', function() {
              return function(percentOfPercent) {
                var progress = (1 - percentOfPercent) * oldValue;

                repaintGauge(progress);
                return d3.select(this).attr('d', recalcPointerPos.call(self, progress));
              };
            });

            this.el.transition().delay(300).ease('bounce').duration(1500).select('.needle').tween('progress', function() {
              return function(percentOfPercent) {
                var progress = percentOfPercent * perc;

                repaintGauge(progress);
                return d3.select(this).attr('d', recalcPointerPos.call(self, progress));
              };
            });

          };

          return Needle;

        })();

        var needle = new Needle(chart);
        return needle;
      }
      speciesNeedle = init('.chart-gauge-species');
      groupsNeedle = init('.chart-gauge-groups');
      speciesNeedle.render();
      speciesNeedle.moveTo(countedSpecies / 100);
      groupsNeedle.render();
      groupsNeedle.moveTo(countedGroups / 10);

    })
    .fail(function( jqxhr, settings, exception ) {
      alert('Failed to load D3 script');
    });


  }

});
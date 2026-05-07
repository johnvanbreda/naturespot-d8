jQuery(document).ready(function($) {
  var prevTotal;
  var allTotal;

  indiciaFns.setSpeciesCount = function(el, sourceSettings, response) {
    $('#site-spp-count').text(response.aggregations.species_count.value);
  }

  if ($('#es-year-counts').length > 0) {
    const thisYear = new Date().getFullYear();
    // Showing the counts stats block, so fill in the stuff required from ES.
    $.ajax({
      url: '/iform/esproxy/searchbyparams/0',
      type: 'post',
      data: {
        bool_queries: [
          {
            bool_clause: 'must',
            query_type: 'query_string',
            value: `metadata.created_on:[${thisYear}-01-01 TO ${thisYear}-12-31]`
          },
          {
            bool_clause: 'must_not',
            query_type: 'term',
            field: 'identification.verification_status',
            value: 'R',
          }
        ],
        size: 0,
        aggs: {
          species_count: {
            cardinality: {
              field: 'taxon.accepted_taxon_id'
            }
          }
        },
        proxyCacheTimeout: 1800
      },
      dataType: 'json'
    })
    .done(function(response) {
      $('#year-species').text(response.aggregations.species_count.value);
      $('#year-records').text(response.hits.total.value);
    });

    $.ajax({
      url: '/iform/esproxy/searchbyparams/0',
      type: 'post',
      data: {
        bool_queries: [
          {
            bool_clause: 'must_not',
            query_type: 'query_string',
            value: `metadata.created_on:[${thisYear}-01-01 TO ${thisYear}-12-31]`
          },
          {
            bool_clause: 'must_not',
            query_type: 'term',
            field: 'identification.verification_status',
            value: 'R',
          }
        ],
        size: 0,
        aggs: {
          species_count: {
            cardinality: {
              field: 'taxon.accepted_taxon_id',
              precision_threshold: 20000
            }
          }
        },
        proxyCacheTimeout: 3600 * 24 * 7,
      },
      dataType: 'json'
    })
    .done(function(response) {
      prevTotal = response.aggregations.species_count.value;
      showTotalNewSp();
    });

    $.ajax({
      url: '/iform/esproxy/searchbyparams/0',
      type: 'post',
      data: {
        bool_queries: [
          {
            bool_clause: 'must_not',
            query_type: 'term',
            field: 'identification.verification_status',
            value: 'R',
          }
        ],
        size: 0,
        aggs: {
          species_count: {
            cardinality: {
              field: 'taxon.accepted_taxon_id',
              precision_threshold: 20000
            }
          }
        },
        proxyCacheTimeout: 3600
      },
      dataType: 'json'
    })
    .done(function(response) {
      allTotal = response.aggregations.species_count.value;
      showTotalNewSp();
    });

  }

  function showTotalNewSp() {
    if (prevTotal && allTotal) {
      $('#new-species').text(Math.max(0, allTotal - prevTotal));
      $('#es-year-counts').fadeIn();
    }
  }

});
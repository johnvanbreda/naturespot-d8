jQuery(document).ready(function($) {
  $('#species-verified-only').change(function() {
    if ($('#species-verified-only').is(':checked')) {
      indiciaData.esSourceObjects['es-species'].settings.filterBoolClauses.must.push({
        query_type: 'term',
        field: 'identification.verification_status',
        value: 'V'
      });
    } else if (indiciaData.esSourceObjects['es-species'].settings.filterBoolClauses.must.length > 1) {
      indiciaData.esSourceObjects['es-species'].settings.filterBoolClauses.must.pop();
    }
    indiciaData.esSourceObjects['es-species'].populate();
  });
});
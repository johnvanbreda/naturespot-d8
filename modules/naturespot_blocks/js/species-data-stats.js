jQuery(document).ready(function($) {
  let monthlyRecordsData = [];
  let yearlySqData = [];
  let yearlyRecordsData = [];
  let yearlyGroupRecordsData = {};
  let allMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
  indiciaData.speciesStats.by_month.forEach(function (w) {
    if (w.key) {
      let index = allMonths.indexOf(w.key);
      if (index !== -1) {
        allMonths.splice(index, 1);
      }
      monthlyRecordsData.push({
        taxon: 'taxon',
        month: w.key,
        n: w.doc_count
      });
    }
  });
  // Fill in the zeros.
  allMonths.forEach(function (m) {
    monthlyRecordsData.push({
      taxon: 'taxon',
      month: m,
      n: 0
    });
  });
  indiciaData.speciesStats.group_records_by_year.forEach(function (w) {
    yearlyGroupRecordsData[w.key] = w.doc_count;
  });
  indiciaData.speciesStats.species_records_and_squares_by_year.forEach(function (w) {
    yearlyRecordsData.push({
      taxon: 'taxon',
      year: w.key,
      n: w.doc_count / yearlyGroupRecordsData[w.key] * 100
    });
    yearlySqData.push({
      taxon: 'taxon',
      year: w.key,
      n: w.sq_count.value
    });
  });
  brccharts.phen1({
    selector: '#records_by_month',
    axisLabelFontSize: 22,
    data: monthlyRecordsData,
    metrics: [{ prop: 'n', label: 'Records per month', opacity: 1, colour: '#337ab7' }],
    taxa: ['taxon'],
    width: 500,
    height: 200,
    perRow: 1,
    expand: true,
    showTaxonLabel: false,
    showLegend: false,
    margin: {left: 60, right: 0, top: 10, bottom: 20},
    axisLeftLabel: 'Records per month'
  });
  brccharts.yearly({
    selector: "#percentage_of_group_by_year",
    axisLabelFontSize: 22,
    data: yearlyRecordsData,
    metrics: [{ prop: 'n', label: '% of records within species group per year', opacity: 1, colour: '#fd730a'}],
    taxa: ['taxon'],
    minYear: new Date().getFullYear() - 9,
    maxYear: new Date().getFullYear(),
    width: 500,
    height: 200,
    perRow: 1,
    expand: true,
    showTaxonLabel: false,
    showLegend: false,
    margin: {left: 60, right: 0, top: 10, bottom: 20},
    axisLeftLabel: '% of group records'
  });
  brccharts.yearly({
    selector: "#squares_by_year",
    axisLabelFontSize: 22,
    data: yearlySqData,
    metrics: [{ prop: 'n', label: '10km Grid squares per year', opacity: 1, colour: '#8fccb2'}],
    taxa: ['taxon'],
    minYear: new Date().getFullYear() - 9,
    maxYear: new Date().getFullYear(),
    width: 500,
    height: 200,
    perRow: 1,
    expand: true,
    showTaxonLabel: false,
    showLegend: false,
    margin: {left: 60, right: 0, top: 10, bottom: 20},
    axisLeftLabel: '10km sq. per year'
  });
});
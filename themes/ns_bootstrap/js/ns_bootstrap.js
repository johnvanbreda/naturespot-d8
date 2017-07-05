jQuery(document).ready(function($) {
  'option strict';
  var segments = window.location.href.split('/');

  $('#jump-menu--wild-places--go').click(function () {
    if ($('#jump-menu--wild-places option:selected').length) {
      window.location = $('#jump-menu--wild-places option:selected').val();
    }
  });

  $('#jump-menu--parishes--go').click(function () {
    if ($('#jump-menu--parishes option:selected').length) {
      window.location = $('#jump-menu--parishes option:selected').val();
    }
  });

  $('.fancybox-popup').fancybox();


  $('#taxon-create-form #add-taxon-parent_id').val(segments[segments.length - 1]);
  $('#taxon-create-form #add-taxon-redirect').val(window.location);
});
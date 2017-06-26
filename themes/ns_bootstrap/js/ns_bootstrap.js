jQuery(document).ready(function($) {
  'option strict';
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
});
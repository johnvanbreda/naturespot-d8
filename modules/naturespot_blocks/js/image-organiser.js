jQuery(document).ready(function($) {

  // Remove the toolbar as it gets in the way of drag-scrolling.
  $('#toolbar-administration').hide();

  $.each($('#image-organiser ul'), function() {
    new Sortable(this, {
      group: 'nested',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      handle: '.draggable',
      bubbleScroll: false,
      forceAutoscrollFallback: true,
      scrollSensitivity: 30,
      onSort: function (evt) {
        $(evt.item).addClass('edited');
      },
    });
  });

  $('#save-positions').click(function() {
    var s = {
      priority1: [],
      main: [],
      additional: [],
      unused: []
    };
    var changesFound = 0;
    $('#save-positions').attr('disabled', 'disabled');

    $.each(s, function(key, array) {
      var selector = '#images-' + key + ' li.draggable';
      // Don't bother submitting a section with no edits. Unused handled differently.
      if ($(selector + '.edited').length > 0 || key === 'unused') {
        // Since order doesn't matter on the warehouse, only need to submit the freshly unused images
        // for this group, or permanently deleted ones.
        if (key === 'unused') {
          selector = selector + '.edited,' + selector + '.deleted';
        }
        $.each($(selector), function() {
          var obj = { path: $(this).attr('data-path') };
          // store the image node ID, or warehouse ID if not loaded into Drupal.
          if ($(this).is('[data-nid]')) {
            obj.nid = $(this).attr('data-nid');
          } else if ($(this).is('[data-wid]')) {
            obj.wid = $(this).attr('data-wid');
          }
          if (key === 'unused' && $(this).hasClass('deleted')) {
            obj.deleted = true;
          }
          array.push(obj);
          changesFound++;
        });
      }
    });
    if (changesFound > 0) {
      $.post(
          drupalSettings.path.baseUrl + 'ns/image-organiser-save', {
            data: s,
            speciesTid: $('input[name="species-tid"]').val(),
            tvk: $('input[name="tvk"]').val()
          },
          function(data) {
            if (typeof data.msg === "undefined" || data.msg !== "OK") {
              alert('An error occurred whilst saving the changes.');
            } else {
              alert('The changes have been saved');
              window.location.href = $('#species-page-link').attr('href');
            }
          }
      );
    } else {
      alert('There were no changes to save');
      $('#save-positions').removeAttr('disabled');
    }
  });

  $('.fa-trash-alt').click(function() {
    var li = $(this).closest('li');
    if ($(li).hasClass('deleted')) {
      $(li).removeClass('deleted');
    } else {
      $(li).addClass('deleted');
    }
  })
});

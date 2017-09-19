jQuery(document).ready(function($) {
  
  function moveBlock(source, target) {
    if (source.prev()[0]!==target[0] && source.next()[0]!==target[0]) {
      if (source.hasClass('draggable')) {
        var controlDrop = source.next();
        // move the drop target as well
        controlDrop.insertAfter(target);
        source.insertAfter(target);
      }
      source.addClass('edited');
    }
  }
  
  $("li.draggable").draggable({
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid'
  });
  
  $("li.droppable").droppable({
    drop: function(event, ui) {
      moveBlock(ui.draggable, $(this));
    },
    accept: 'li.draggable',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
  
  $('#save-positions').click(function() {
    var s = {
      priority1: [],
      main: [],
      additional: [],
      unused: []
    };
    $('#save-positions').attr('disabled', 'disabled');
    $.each(s, function(key, array) {
      $.each($('#images-' + key + ' li.draggable'), function() {
        // store the image node ID, or warehouse ID if not loaded into Drupal.
        if ($(this).is('[data-nid]')) {
          array.push({ nid: $(this).attr('data-nid'), path: $(this).attr('data-path')});
        } else if ($(this).is('[data-wid]')) {
          array.push({ wid: $(this).attr('data-wid'), path: $(this).attr('data-path')});
        }
      });
    });
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
            location.reload();
          }
        }
    );
  });
});

(function ($) {
  Drupal.behaviors.pullquote = {
    attach: function(context) {
      var path = drupalSettings.pullquote.modulePath;
      var newCss = document.createElement('link');
      newCss.rel = 'stylesheet';
      newCss.id = 'pullquote-sheet';
      newCss.type = 'text/css';
      newCss.href = drupalSettings.pullquote.current;

      document.head.appendChild(newCss);
      $('#edit-css-selection').change(function() {
        $('link#pullquote-sheet').attr('href', path + $(this).val() + '.css');
      });
      $('#edit-css-source-selection').click(function() {
        var selection = $('#edit-css-selection').val();
        $('link#pullquote-sheet').attr('href', path + selection + '.css');
      });
    }
  };
})(jQuery);


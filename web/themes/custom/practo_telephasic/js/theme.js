(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.stickyHeader = {
    attach: function (context, settings) {
      const windowScrolls = once('sticky-header', window, context);
      windowScrolls.forEach(function() {
        $(window).on('scroll', function() {
          if ($(window).scrollTop() > 50) {
            $('#header').addClass('header-scrolled');
          } else {
            $('#header').removeClass('header-scrolled');
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);
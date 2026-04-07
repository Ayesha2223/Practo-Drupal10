/**
 * @file
 * Homepage interactions
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.homepageSearch = {
    attach: function (context, settings) {
      // Autocomplete suggestions (placeholder for now)
      $(once('autocomplete', '.search-input', context)).on('input', function() {
        var value = $(this).val();
        if (value.length > 2) {
          // Add autocomplete logic here
          console.log('Searching for: ' + value);
        }
      });

      $(once('specialities-carousel', '[data-carousel="specialities"]', context)).each(function () {
        var $carousel = $(this);
        var $track = $carousel.find('.specialities-track');
        if (!$track.length) {
          return;
        }

        var $items = $track.children();
        if ($items.length < 2) {
          return;
        }

        // Duplicate items once to allow seamless marquee.
        $items.clone(true).appendTo($track);
        $carousel.addClass('is-animating');
      });
    }
  };

})(jQuery, Drupal, once);
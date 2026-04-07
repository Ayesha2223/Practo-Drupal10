(function (Drupal) {
  Drupal.behaviors.practoAppointmentsTabs = {
    attach: function (context) {
      var buttons = context.querySelectorAll ? context.querySelectorAll('.tab-btn') : [];
      if (!buttons || !buttons.length) {
        return;
      }

      buttons.forEach(function (btn) {
        if (btn.dataset && btn.dataset.bound) {
          return;
        }
        if (btn.dataset) {
          btn.dataset.bound = '1';
        }
        btn.addEventListener('click', function () {
          var tab = btn.getAttribute('data-tab');
          if (!tab) {
            return;
          }

          context.querySelectorAll('.tab-btn').forEach(function (b) {
            b.classList.remove('active');
          });
          context.querySelectorAll('.tab-content').forEach(function (c) {
            c.classList.remove('active');
          });

          btn.classList.add('active');
          var target = context.querySelector('#' + tab);
          if (target) {
            target.classList.add('active');
          }
        });
      });
    }
  };
})(Drupal);

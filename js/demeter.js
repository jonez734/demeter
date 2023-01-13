// @see http://smoothstate.com/typical-implementation.html
// Contents of functions.js
/*
;(function($) {
  'use strict';
  var $body = $('html, body'),
      content = $('#smoothstatecontainer').smoothState({
        // Runs when a link has been activated
        onStart: {
          duration: 250, // Duration of our animation
          render: function (url, $container) {
            // toggleAnimationClass() is a public method
            // for restarting css animations with a class
            content.toggleAnimationClass('is-exiting');
            // Scroll user to the top
            $body.animate({
              scrollTop: 0
            });
          }
        }
      }).data('smoothState');
      //.data('smoothState') makes public methods available
})(jQuery);
*/
$(function(){
  'use strict';
  var $page = $('html, body'),
      options = {
        debug: true,
        development: true,
        prefetch: true,
        allowFormCaching: false,
        repeatDelay: 500,
        cacheLength: 2,
        blacklist:  'form',
        forms: 'form',
        onStart: {
          duration: 250, // Duration of our animation
          render: function ($container) {
            // Add your CSS animation reversing class
            $container.addClass('is-exiting');
            // Restart your animation
            smoothState.restartCSSAnimations();
          }
        },
        onReady: {
          duration: 0,
          render: function ($container, $newContent) {
            // Remove your CSS animation reversing class
            $container.removeClass('is-exiting');
            // Inject the new content
            $container.html($newContent);
          }
        }
      },
      smoothState = $page.smoothState(options).data('smoothState');
});

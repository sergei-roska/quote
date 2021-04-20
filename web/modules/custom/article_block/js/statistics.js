// (function ($, Drupal, drupalSettings) {
//
//   'use strict';
//
//   Drupal.behaviors.article_block = {
//     attach: function (context, settings) {
//       $(document, context).once('article_block').ready(function () {
//         let baseUrl = drupalSettings.path.baseUrl ? drupalSettings.path.baseUrl : '/';
//         console.log(settings.article_block.nid);
//         $.ajax({
//           url: baseUrl + 'article_block/' + settings.article_block.nid + '/ajax-call',
//         });
//       });
//     }
//   };
//
// })(jQuery, Drupal, drupalSettings);

(function ($, Drupal, drupalSettings) {
  $(document).ready(function(){
    let baseUrl = drupalSettings.path.baseUrl ? drupalSettings.path.baseUrl : '/';

    $.ajax({
      type: 'POST',
      cache: false,
      url: baseUrl + 'article_block/' + drupalSettings.article_block.nid + '/ajax-call',
    });
  });
})(jQuery, Drupal, drupalSettings);

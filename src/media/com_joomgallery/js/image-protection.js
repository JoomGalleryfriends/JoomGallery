/**
 * Casual-copy deterrents only: the browser still receives the image URL and bytes.
 * Do not block page-wide shortcuts, navigation, text selection, or touch scrolling.
 */
(() => {
  'use strict';

  const preventImageAction = (event) => {
    if (event.target instanceof Element && event.target.closest('[data-jg-image-protection]')) {
      event.preventDefault();
    }
  };

  document.addEventListener('contextmenu', preventImageAction);
  document.addEventListener('dragstart', preventImageAction);
})();

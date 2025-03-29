// Initialisation
const defaults = {
        itemid : 1,
        pagination: 1,
        layout: 'masonry',
        num_columns: 3,
        lightbox: false,
        thumbnails: false,
        lightboxes: {},
        imgboxclass: 'jg-image',
        imgclass: 'jg-image-thumb',
        gridclass: 'jg-category',
        infscrollclass: 'infinite-scroll',
        loadmoreid: 'loadMore',
        loaderclass: 'jg-loader',
        justifieds: {},
        justified: {height: 320, gap: 5}
};

// Ensure window.joomGrid exists
window.joomGrid = window.joomGrid || {};

// Loop through defaults and check against window.joomGrid
for(const [key, value] of Object.entries(defaults)) {
  if(!window.joomGrid.hasOwnProperty(key) || window.joomGrid[key] === undefined || window.joomGrid[key] === null) {
    window.joomGrid[key] = value;
  }
}

var callback = function() {
  // Get the grid container
  const grid = document.querySelector('.' + window.joomGrid.gridclass);

  // Initialize lightGallery
  if(window.joomGrid.lightbox) {
    const lightbox = document.getElementById('lightgallery-' + window.joomGrid.itemid);

    window.joomGrid.lightboxes[window.joomGrid.itemid] = lightGallery(lightbox, {
      selector: '.lightgallery-item',
      exThumbImage: 'data-thumb',
      // allowMediaOverlap: true,
      thumbHeight: '50px',
      thumbMargin: 5,
      thumbWidth: 75,
      thumbnail: window.joomGrid.thumbnails,
      toggleThumb: true,
      speed: 500,
      plugins: [lgThumbnail],
      preload: 1,
      loop: false,
      slideEndAnimation: false,
      hideControlOnEnd: true,
      counter: true,
      download: false,
      mobileSettings: {
        controls: false,
        showCloseIcon: true,
        download: false,
      },
      licenseKey: '1111-1111-111-1111',
    });
    
    if(lightbox) {
      window.joomGrid.lightboxes[window.joomGrid.itemid].outer.on('click', (e) => {
        const $item = window.joomGrid.lightboxes[window.joomGrid.itemid].outer.find('.lg-current .lg-image');
        if (
          e.target.classList.contains('lg-image') ||
          $item.get().contains(e.target)
        ) {
          window.joomGrid.lightboxes[window.joomGrid.itemid].goToNextSlide();
        }
      });
    }
  }

  // Load justified for grid selected by gridclass (category images)
  if(window.joomGrid.layout == 'justified' && document.querySelectorAll('.justified').length > 0) {
    addEventListener('load', _ => {
    const imgs = document.querySelectorAll('.' + window.joomGrid.gridclass + ' img');
    const options = {
      idealHeight: window.joomGrid.justified.height,
      rowGap: window.joomGrid.justified.gap,
      columnGap: window.joomGrid.justified.gap,
    };
    window.joomGrid.justifieds[window.joomGrid.itemid] = new ImgJust(grid, imgs, options);
    });
  }

  // Infinity scroll or load more
  if(window.joomGrid.pagination == 1 && grid || window.joomGrid.pagination == 2 && grid)
  {
    const items        = Array.from(grid.getElementsByClassName(window.joomGrid.imgboxclass));
    const maxImages    = window.joomGrid.num_columns * 2;
    const loadImages   = window.joomGrid.num_columns * 3;
    const hiddenClass  = 'hidden-' + window.joomGrid.imgboxclass;
    const hiddenImages = Array.from(document.getElementsByClassName(hiddenClass));

    items.forEach(function (item, index) {
      if (index > maxImages - 1) {
        item.classList.add(hiddenClass);
      }
    });

    if(window.joomGrid.pagination == 1) {
      // Infinity scroll
      const observerOptions = {
        root: null,
        rootMargin: '200px',
        threshold: 0
      };
      
      function observerCallback(entries, observer) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            [].forEach.call(document.querySelectorAll('.' + hiddenClass), function (
              item,
              index
            ) {
              if (index < loadImages) {
                item.classList.remove(hiddenClass);
              }
              if (document.querySelectorAll('.' + hiddenClass).length === 0) {
                noMore.classList.remove('hidden');
              }
            });
          }
        });
      }
      
      const fadeElms = document.querySelectorAll('.' + window.joomGrid.infscrollclass);
      const observer = new IntersectionObserver(observerCallback, observerOptions);
      fadeElms.forEach(el => observer.observe(el));
    } else if(window.joomGrid.pagination == 2) {
      // Load more button
      const loadMore = document.getElementById(window.joomGrid.loadmoreid);
  
      loadMore.addEventListener('click', function () {
        [].forEach.call(document.querySelectorAll('.' + hiddenClass), function (
          item,
          index
        ) {
          if (index < loadImages) {
            item.classList.remove(hiddenClass);
          }
          if (document.querySelectorAll('.' + hiddenClass).length === 0) {
            loadMore.style.display = 'none';
            noMore.classList.remove('hidden');
          }
        });
      });
    }
  }

  // Hide loader
  if(document.getElementsByClassName(window.joomGrid.loaderclass)) {
    const loaders = document.getElementsByClassName(window.joomGrid.loaderclass);

    Array.from(loaders).forEach(loader => {
      loader.classList.add('hidden');
    });
  }
}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}

// Initialisation
const defaults = {
        itemid : '0-0',
        pagination: 1,
        layout: 'masonry',
        num_columns: 3,
        numb_images: 12,
        reloaded_images: 3,
        lightbox: false,
        thumbnails: false,
        lightbox_obj: {},
        lightbox_params: {container: 'lightgallery-0-0', selector: '.lightgallery-item'},
        gridclass: 'jg-category',
        infscrollclass: 'infinite-scroll',
        loadmoreid: 'loadMore',
        loaderclass: 'jg-loader',
        justified_obj: {},
        justified: {height: 320, gap: 5}
};

// Ensure window.joomGrid exists
if(!window.joomGrid) {
  window.joomGrid = {};
  window.joomGrid['0-0'] = defaults;
}

var callback = function() {
  // Loop through all available joomGrids
  for(const [itemid, settings] of Object.entries(window.joomGrid)) {
    if(itemid === '0-0') {
      continue;
    }

    // Loop through defaults and check against provided settings
    for(const [key, value] of Object.entries(window.joomGrid['0-0'])) {
      if(!settings.hasOwnProperty(key) || settings[key] === undefined || settings[key] === null) {
        settings[key] = value;
      }
    }

    // Get the grid container
    const grid = document.querySelector('.' + settings.gridclass);

    // Initialize lightGallery
    if(settings.lightbox) {
      const lightbox = document.getElementById(settings.lightbox_params.container);

      window.joomGrid[itemid].lightbox_obj = lightGallery(lightbox, {
        selector: settings.lightbox_params.selector,
        exThumbImage: 'data-thumb',
        // allowMediaOverlap: true,
        thumbHeight: '50px',
        thumbMargin: 5,
        thumbWidth: 75,
        thumbnail: settings.thumbnails,
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
        window.joomGrid[itemid].lightbox_obj.outer.on('click', (e) => {
          const $item = window.joomGrid[itemid].lightbox_obj.outer.find('.lg-current .lg-image');
          if (
            e.target.classList.contains('lg-image') ||
            $item.get().contains(e.target)
          ) {
            window.joomGrid[itemid].lightbox_obj.goToNextSlide();
          }
        });
      }
    }

    // Load justified for grid selected by gridclass (category images)
    if(settings.layout == 'justified' && document.querySelectorAll('.justified').length > 0) {
      addEventListener('load', _ => {
      const imgs = document.querySelectorAll('.' + settings.gridclass + ' img');
      const options = {
        idealHeight: settings.justified.height,
        rowGap: settings.justified.gap,
        columnGap: settings.justified.gap,
      };
      window.joomGrid[itemid].justified_obj = new ImgJust(grid, imgs, options);
      });
    }

    // Infinity scroll or load more
    if(settings.pagination == 1 && grid || settings.pagination == 2 && grid)
    {
      let maxImages;
      let loadImages;
      if(settings.pagination == 1) {
          maxImages  = settings.num_columns * 2;
          loadImages = settings.num_columns * 3;
      }
      if(settings.pagination == 2) {
          maxImages  = settings.numb_images;
          loadImages = settings.reloaded_images;
      }

      const items        = Array.from(grid.getElementsByClassName('jg-image'));
      const hiddenClass  = 'hidden-jg-image';
      const hiddenImages = Array.from(document.getElementsByClassName(hiddenClass));

      items.forEach(function (item, index) {
        if (index > maxImages - 1) {
          item.classList.add(hiddenClass);
        }
      });

      if(settings.pagination == 1) {
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
        
        const fadeElms = document.querySelectorAll('.' + settings.infscrollclass);
        const observer = new IntersectionObserver(observerCallback, observerOptions);
        fadeElms.forEach(el => observer.observe(el));
      } else if(settings.pagination == 2) {
        // Load more button
        if(document.getElementById(settings.loadmoreid)) {
          const loadMore = document.getElementById(settings.loadmoreid);

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
    }

    // Hide loader
    if(document.getElementsByClassName(settings.loaderclass)) {
      const loaders = document.getElementsByClassName(settings.loaderclass);

      Array.from(loaders).forEach(loader => {
        loader.classList.add('hidden');
      });
    }
  }
}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}

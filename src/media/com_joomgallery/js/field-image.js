((customElements, Joomla) => {
  class JoomGalleryFieldImage extends HTMLElement {
    constructor() {
      super();
      this.onImageSelect = '';
      this.onchangeStr = ''; // Bind events

      this.buttonClick = this.buttonClick.bind(this);
      this.iframeLoad = this.iframeLoad.bind(this);
      this.variantModal = null;
      this.modalClose = this.modalClose.bind(this);
      this.setValue = this.setValue.bind(this);
      this.createButtonClick = this.createButtonClick.bind(this);
      this.handleMessage = this.handleMessage.bind(this);
    }

    static get observedAttributes() {
      return ['url', 'modal', 'modal-width', 'modal-height', 'input', 'input-name', 'input-img', 'button-select'];
    }

    get url() {
      return this.getAttribute('url');
    }

    set url(value) {
      this.setAttribute('url', value);
    }

    get modalClass() {
      return this.getAttribute('modal');
    }

    set modalClass(value) {
      this.setAttribute('modal', value);
    }

    get modalWidth() {
      return this.getAttribute('modal-width');
    }

    set modalWidth(value) {
      this.setAttribute('modal-width', value);
    }

    get modalHeight() {
      return this.getAttribute('modal-height');
    }

    set modalHeight(value) {
      this.setAttribute('modal-height', value);
    }

    get inputId() {
      return this.getAttribute('input');
    }

    set inputId(value) {
      this.setAttribute('input', value);
    }

    get inputImgId() {
      return this.getAttribute('input-img');
    }

    set inputImgId(value) {
      this.setAttribute('input-img', value);
    }

    get inputNameClass() {
      return this.getAttribute('input-name');
    }

    set inputNameClass(value) {
      this.setAttribute('input-name', value);
    }

    get buttonSelectClass() {
      return this.getAttribute('button-select');
    }

    set buttonSelectClass(value) {
      this.setAttribute('button-select', value);
    }

    connectedCallback() {
      // Set up elements
      window.addEventListener('message', this.handleMessage);
      this.modal = this.querySelector(this.modalClass);
      this.modalBody = this.querySelector('.modal-body');
      this.input = this.querySelector(this.inputId);
      this.inputName = this.querySelector(this.inputNameClass);
      this.inputImg = this.querySelector(this.inputImgId);
      this.buttonSelect = this.querySelector(this.buttonSelectClass); // Bootstrap modal init
      this.buttonCreate = this.querySelector('.button-create');

      if (this.buttonCreate) {
        this.buttonCreate.addEventListener('click', this.createButtonClick);
      }

      if (this.modal && window.bootstrap && window.bootstrap.Modal && !window.bootstrap.Modal.getInstance(this.modal)) {
        Joomla.initialiseModal(this.modal, {
          isJoomla: true
        });
      }

      if (this.buttonSelect) {
        this.buttonSelect.addEventListener('click', () => this.modalOpen());
        this.modal.addEventListener('hide', this.removeIframe.bind(this)); // Check for onchange callback,

        this.onchangeStr = this.input.getAttribute('data-onchange');

        if (this.onchangeStr) {
          /* eslint-disable */
          this.onImageSelect = new Function(this.onchangeStr);
          this.input.addEventListener('change', this.onImageSelect);
          /* eslint-enable */
        }
      }
    }

    disconnectedCallback() {
      window.removeEventListener('message', this.handleMessage);
      if (this.onchangeStr && this.input) {
        this.input.removeEventListener('change', this.onImageSelect);
      }

      if (this.buttonSelect) {
        this.buttonSelect.removeEventListener('click', this);
      }

      if (this.buttonCreate) {
        this.buttonCreate.removeEventListener('click', this.createButtonClick);
      }

      if (this.modal) {
        this.modal.removeEventListener('hide', this);
      }
    }

    buttonClick({
      target
    }) {
      this.setValue(target.getAttribute('data-image-value'), target.getAttribute('data-image-title'));
      this.modalClose();
    }

    iframeLoad() {
      const iframeDoc = this.iframeEl.contentWindow.document;
      const buttons = [].slice.call(iframeDoc.querySelectorAll('.button-select'));
      buttons.forEach(button => {
        button.addEventListener('click', this.buttonClick);
      });
    } // Opens the modal

    handleMessage(event) {
      if (event.origin !== window.location.origin) {
        return;
      }

      const data = event.data;

      if (data && data.messageType === 'joomgallery:variant-saved') {
        if (data.id && data.language) {
          const associationField = [
            ...document.querySelectorAll('joomla-field-image')
          ].find(field => {
            const input = field.querySelector('.field-image-input');
            return input?.name === `jform[associations][${data.language}]`;
          });

          if (associationField) {
            associationField.setValue(data.id, data.title);
          }
        }

        const iframe = [...document.querySelectorAll('iframe')]
          .find(frame => frame.contentWindow === event.source);

        const modalElement = iframe?.closest('.modal');

        if (modalElement && window.bootstrap?.Modal) {
          const modal = window.bootstrap.Modal.getInstance(modalElement);

          if (modal) {
            modal.hide();
          }
        }

        return;
      }

      if (!this.iframeEl || event.source !== this.iframeEl.contentWindow) {
        return;
      }

      if (!data || data.messageType !== 'joomla:content-select' || !data.id) {
        return;
      }

      this.setValue(data.id, data.title);
      this.modalClose();
    }

    createButtonClick(event) {
      const url = event.currentTarget.getAttribute('data-url');

      if (!url) {
        return;
      }

      const sourceId = new URL(window.parent.location.href).searchParams.get('id');

      if (!sourceId || Number(sourceId) <= 0) {
        Joomla.renderMessages({
          warning: ['Please select a source image first.']
        });
        return;
      }

      const createUrl = new URL(url, window.location.href);
      const forcedLanguage = createUrl.searchParams.get('forcedLanguage');

      createUrl.searchParams.set('option', 'com_joomgallery');
      createUrl.searchParams.set('view', 'image');
      createUrl.searchParams.set('layout', 'edit');
      createUrl.searchParams.set('id', '0');
      createUrl.searchParams.set('origin_id', sourceId);

      if (forcedLanguage) {
        createUrl.searchParams.set('forcedLanguage', forcedLanguage);
      }

      createUrl.searchParams.delete('task');

      this.modalOpen(createUrl.toString());
    }

modalOpen(url = this.url) {
  this.removeIframe();

  const iframe = document.createElement('iframe');
  iframe.setAttribute('name', 'field-image-modal');
  iframe.src = url.replace(
    '{field-image-id}',
    this.input.getAttribute('id')
  );
  iframe.setAttribute('width', this.modalWidth);
  iframe.setAttribute('height', this.modalHeight);

  if (url.includes('layout=edit')) {
    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.tabIndex = -1;
    modalElement.setAttribute('aria-hidden', 'true');

    modalElement.innerHTML = `
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Create image translation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"
              aria-label="Close"></button>
          </div>
          <div class="modal-body p-0"></div>
        </div>
      </div>
    `;

    document.body.appendChild(modalElement);
    modalElement.querySelector('.modal-body').appendChild(iframe);

    this.variantModal = new bootstrap.Modal(modalElement);
    const modal = this.variantModal;
    modalElement.addEventListener('hidden.bs.modal', () => {
      modalElement.remove();
    }, {
      once: true
    });

    modal.show();
    return;
  }

  this.modalBody.appendChild(iframe);
  this.modal.open();
  this.iframeEl = this.modalBody.querySelector('iframe');
  this.iframeEl.addEventListener('load', this.iframeLoad);
}

modalClose() {
  if (this.variantModal) {
    this.variantModal.hide();
    this.variantModal = null;
    return;
  }

  this.modal.close();
}


    removeIframe() {
      this.modalBody.innerHTML = '';
    } // Sets the value

    changeImgPath(img_path, newid) {
      let split_path = img_path.split('&');

      // Exchange image id
      split_path.forEach(function (value, i) {
        if(value.includes('id=')) {
          split_path[i] = 'id='+newid.toString();
        }
      });

      return split_path.join('&');
    } // Change image id in src path of image

setValue(value, name) {
  this.input.value = value;
  this.inputName.value = name || value;

  const imgPath = this.inputImg.getAttribute('src');
  this.inputImg.setAttribute(
    'src',
    this.changeImgPath(imgPath, value)
  );

  this.input.dispatchEvent(new Event('change', { bubbles: true }));

  this.dispatchEvent(new CustomEvent('change', {
    detail: {
      value,
      name
    },
    bubbles: true
  }));
}



  }

  customElements.define('joomla-field-image', JoomGalleryFieldImage);
})(customElements, Joomla);

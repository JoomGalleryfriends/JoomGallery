((customElements, Joomla) => {
  class JoomGalleryFieldCategory extends HTMLElement {
    constructor() {
      super();
      this.onCategorySelect = '';
      this.onchangeStr = '';
      this.buttonClick = this.buttonClick.bind(this);
      this.iframeLoad = this.iframeLoad.bind(this);
      this.modalClose = this.modalClose.bind(this);
      this.setValue = this.setValue.bind(this);
      this.handleMessage = this.handleMessage.bind(this);
      this.createButtonClick = this.createButtonClick.bind(this);
      this.deleteVariantButtonClick = this.deleteVariantButtonClick.bind(this);
    }

    static get observedAttributes() {
      return ['url', 'modal', 'modal-width', 'modal-height', 'input', 'input-name', 'button-select'];
    }

    get url() { return this.getAttribute('url'); }
    set url(value) { this.setAttribute('url', value); }
    get modalClass() { return this.getAttribute('modal'); }
    set modalClass(value) { this.setAttribute('modal', value); }
    get modalWidth() { return this.getAttribute('modal-width'); }
    set modalWidth(value) { this.setAttribute('modal-width', value); }
    get modalHeight() { return this.getAttribute('modal-height'); }
    set modalHeight(value) { this.setAttribute('modal-height', value); }
    get inputId() { return this.getAttribute('input'); }
    set inputId(value) { this.setAttribute('input', value); }
    get inputNameClass() { return this.getAttribute('input-name'); }
    set inputNameClass(value) { this.setAttribute('input-name', value); }
    get buttonSelectClass() { return this.getAttribute('button-select'); }
    set buttonSelectClass(value) { this.setAttribute('button-select', value); }

    connectedCallback() {
      this.modal = this.querySelector(this.modalClass);
      this.modalBody = this.querySelector('.modal-body');
      this.input = this.querySelector(this.inputId);
      this.inputName = this.querySelector(this.inputNameClass);
      this.buttonSelect = this.querySelector(this.buttonSelectClass);
      this.buttonCreate = this.querySelector('.button-create');
      this.buttonDeleteVariant = this.querySelector('.button-delete-variant');
      if (this.buttonCreate) this.buttonCreate.disabled = !!this.inputName?.value.trim();

      if (this.modal && window.bootstrap?.Modal && !window.bootstrap.Modal.getInstance(this.modal)) {
        Joomla.initialiseModal(this.modal, { isJoomla: true });
      }

      if (this.buttonSelect && this.modal) {
        this.buttonSelect.addEventListener('click', () => this.modalOpen());
        this.modal.addEventListener('hide', this.removeIframe.bind(this));
        this.onchangeStr = this.input?.getAttribute('data-onchange') || '';
        if (this.onchangeStr) {
          /* eslint-disable */
          this.onCategorySelect = new Function(this.onchangeStr);
          /* eslint-enable */
          this.input.addEventListener('change', this.onCategorySelect);
        }
      }

      this.buttonCreate?.addEventListener('click', this.createButtonClick);
      this.buttonDeleteVariant?.addEventListener('click', this.deleteVariantButtonClick);
      window.addEventListener('message', this.handleMessage);
    }

    disconnectedCallback() {
      if (this.onchangeStr && this.input) this.input.removeEventListener('change', this.onCategorySelect);
      this.buttonCreate?.removeEventListener('click', this.createButtonClick);
      this.buttonDeleteVariant?.removeEventListener('click', this.deleteVariantButtonClick);
      window.removeEventListener('message', this.handleMessage);
      this.modal?.removeEventListener('hide', this.removeIframe);
    }

    buttonClick({ target }) {
      this.setValue(target.getAttribute('data-category-value'), target.getAttribute('data-category-title'));
      this.modalClose();
    }

    iframeLoad() {
      const iframeDoc = this.iframeEl.contentWindow.document;
      iframeDoc.querySelectorAll('.button-select').forEach(button => button.addEventListener('click', this.buttonClick));
    }

    handleMessage(event) {
      if (event.origin !== window.location.origin) return;
      const data = event.data;
      if (!data || data.messageType !== 'joomgallery:category-variant-saved') return;

      if (data.id && data.language) {
        const associationField = [...document.querySelectorAll('joomla-field-category')].find(field => {
          const input = field.querySelector('.field-category-input');
          return input?.name === `jform[associations][${data.language}]`;
        });
        associationField?.setValue(data.id, data.title);
      }

      const iframe = [...document.querySelectorAll('iframe')].find(frame => frame.contentWindow === event.source);
      const modalElement = iframe?.closest('.modal');
      const modal = modalElement && window.bootstrap?.Modal?.getInstance(modalElement);
      modal?.hide();
    }

    createButtonClick(event) {
      const button = event.currentTarget;
      if (button.getAttribute('data-parent-missing') === '1') {
        Joomla.renderMessages({ error: [button.getAttribute('data-parent-error')] });
        return;
      }
      const url = button.getAttribute('data-url');
      const sourceId = new URL(window.parent.location.href).searchParams.get('id');
      if (!url || !sourceId || Number(sourceId) <= 0) {
        Joomla.renderMessages({ error: [event.currentTarget.getAttribute('data-error') || 'Save the source category before creating a language variant.'] });
        return;
      }

      const createUrl = new URL(url, window.location.href);
      createUrl.searchParams.set('id', '0');
      createUrl.searchParams.set('origin_id', sourceId);
      createUrl.searchParams.set('forcedLanguage', createUrl.searchParams.get('forcedLanguage') || '');
      this.modalOpen(createUrl.toString());
    }

    async deleteVariantButtonClick(event) {
      const button = event.currentTarget;
      const variantId = Number(this.input.value);
      const language = button.getAttribute('data-language');
      const sourceId = new URL(window.parent.location.href).searchParams.get('id');
      const url = button.getAttribute('data-url');

      if (!variantId || !sourceId || Number(sourceId) <= 0 || variantId === Number(sourceId) || !language || !url) {
        Joomla.renderMessages({ error: [button.getAttribute('data-error')] });
        return;
      }
      if (!window.confirm(button.getAttribute('data-confirm'))) return;

      button.disabled = true;
      try {
        const token = Joomla.getOptions('csrf.token');
        const body = new URLSearchParams({ id: String(variantId), origin_id: String(sourceId), forcedLanguage: language });
        if (token) body.append(token, '1');
        const response = await fetch(new URL(url, window.location.href).toString(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: body.toString()
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || button.getAttribute('data-error'));
        this.setValue('', '');
        Joomla.renderMessages({ success: [result.message || button.getAttribute('data-success')] });
      } catch (error) {
        Joomla.renderMessages({ error: [error.message || button.getAttribute('data-error')] });
      } finally {
        button.disabled = !this.input.value;
      }
    }

    modalOpen(url = this.url) {
      const iframe = document.createElement('iframe');
      iframe.setAttribute('name', 'field-category-modal');
      iframe.src = url.replace('{field-category-id}', this.input.getAttribute('id'));
      iframe.setAttribute('width', this.modalWidth);
      iframe.setAttribute('height', this.modalHeight);

      // The create form must not reuse the category-selection modal.
      if (url.includes('layout=edit')) {
        const modalElement = document.createElement('div');
        modalElement.className = 'modal fade';
        modalElement.tabIndex = -1;
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.innerHTML = `
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Create category translation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
          if (this.variantModal === modal) this.variantModal = null;
          modalElement.remove();
        }, { once: true });
        modal.show();
        return;
      }

      // Existing-category selection continues to use the original picker modal.
      this.removeIframe();
      this.modalBody.appendChild(iframe);
      this.modal.open();
      this.iframeEl = this.modalBody.querySelector('iframe');
      this.iframeEl.addEventListener('load', this.iframeLoad);
    }

    modalClose() {
      if (this.variantModal) {
        this.variantModal.hide();
        return;
      }
      this.modal.close();
    }

    removeIframe() { if (this.modalBody) this.modalBody.innerHTML = ''; }

    setValue(value, name) {
      this.input.value = value || '';
      this.inputName.value = name || value || '';

      if (this.buttonCreate) {
        this.buttonCreate.disabled = !!this.inputName.value.trim();
      }

      if (this.buttonDeleteVariant) {
        this.buttonDeleteVariant.disabled = !this.input.value;
      }

      this.input.dispatchEvent(new Event('change', { bubbles: true }));
      this.dispatchEvent(new CustomEvent('change', { detail: { value, name }, bubbles: true }));
    }
  }

  customElements.define('joomla-field-category', JoomGalleryFieldCategory);
})(customElements, Joomla);

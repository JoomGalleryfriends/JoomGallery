// JoomGallery AIinterface class //
import { Sema } from 'async-sema';
import JoomlaDialog from 'joomla.dialog'

class AIinterface {
  // Settings
  name = 'JoomGallery AI Interface';
  prefix = 'jgai';
  host = 'localhost';
  systemLang = 'en';
  configs = {};
  lang = {};
  forceTrailingSlash = false;
  apiKeys = {};

  // Data from API
  token = '';
  info = {};
  balance = 0;
  models = [];
  modes = [];
  providers = {'localhost': 'Local', 'ollama' : 'Ollama/Local'};

  // Request info
  selected_model = 'gemma3:4b';
  selected_mode = 'performance';
  selected_lang = this.systemLang;
  client_name = 'JG-General';
  client_version = '1.0.0';

  // Image panel
  current_image = 0;

  constructor(prefix, host, token, client_name, configs, lang) {
    if (prefix) this.prefix = prefix;
    if (host) this.host = host;
    if (token) this.token = token;
    if (client_name) this.client_name = client_name;
    if (configs) this.configs = configs;
    if (lang) this.lang = lang;

    this.name = this.lang.COM_JOOMGALLERY_JS_AIINT_TITLE ?? 'JoomGallery AI Interface';

    // Detect language
    if(this.configs.def_lang) {
      if (this.configs.def_lang.includes('de')) this.systemLang = 'de';
    }

    // Detect client version
    if(this.configs.version) {
      this.client_version = this.configs.version;
    }

    // Detect trailing slash Settings
    if(this.configs.forceTrailingSlash) {
      this.forceTrailingSlash = this.configs.forceTrailingSlash;
    }

    // Detect api keys Settings
    if(this.configs.api_keys) {
      this.apiKeys = this.configs.api_keys;
    }
  }

  sanitizeUrl(url) {
    const hasQuery = url.includes('?');
    const hadTrailingSlash = /\/$/.test(url);

    // Collapse multiple slashes except after "http(s):"
    url = url.replace(/([^:])\/{2,}/g, "$1/");

    // Remove trailing slashes
    url = url.replace(/\/+$/g, "");

    if (!hasQuery && (hadTrailingSlash || this.forceTrailingSlash)) url += "/";

    return url;
  }

  capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  urlEncode(s) {
    return encodeURIComponent(String(s));
  }

  addHeader(headers = {}, addContentType = true) {
    const h = { ...headers };

    if (addContentType && !Object.prototype.hasOwnProperty.call(h, 'Content-Type')) {
      h['Content-Type'] = 'application/json';
    }
    if (!Object.prototype.hasOwnProperty.call(h, 'X-Client-Name')) {
      h['X-Client-Name'] = this.client_name;
    }
    if (!Object.prototype.hasOwnProperty.call(h, 'X-Client-Version')) {
      h['X-Client-Version'] = this.client_version;
    }

    return h;
  }

  logRequestErrors(url, headers, msg) {
    // Keep it similar to Lua logger intent
    console.debug("[JGAIInterface] Request failed");
    console.debug("URL:", url);
    console.debug("Message:", msg);
    console.debug("Headers:", headers);
  }

  parseJsonResponse(txt, status, statusText) {
    if (!txt) {
      return { success: false, status: status, message: statusText || "", messages: [], data: null };
    }

    // Clean JSON response (your old check was startsWith('{"success"'))
    const trimmed = txt.trim();

    // Catch Errors, warnings from API script
    if (txt.includes("Fatal error") || txt.includes("Warning") || txt.includes("Notice")) {
      return { success: false, status: status, message: statusText, messages: [ "Errors or warnings detected in response." ], data: txt };
    }
    else
    {
      try {
        const obj = JSON.parse(trimmed);

        // If API wraps JSON inside obj.data as string (your old code did JSON.parse(res.data))
        if (obj && typeof obj.data === "string") {
          try {
            obj.data = JSON.parse(obj.data);
          } catch {
            // leave as string if it isn't JSON
          }
        }

        return {success: true, status: status, message: statusText, messages: [], data: obj};
      } catch {
        // fall through to other heuristics
      }
    }

    // Unknown non-JSON response
    return { success: false, status: status, message: statusText, messages: ["Unknown non-JSON response."], data: txt };
  }

  addProviders(services) {
    for (const service of services || []) {
      if(service.value) {
        this.providers[service.value] = service.name;
      }
    }
  }

  buildTables(array, mapping) {
    const items = [];
    for (const rec of array || []) {
      const opts = {};
      for (const key of mapping.options || []) {
        opts[key] = rec[key];
      }

      items.push({
        value: String(rec[mapping.value] ?? "").toLowerCase(),
        title: rec[mapping.title],
        options: opts,
      });
    }
    return items;
  }

  buildModelTitles(models) {
    for (const m of models || []) {
      const provKey = (m.options && m.options.service) ? String(m.options.service) : "";
      const providerName = this.providers[provKey.toLowerCase()] || provKey;
      const baseName = m.value || m.title;
      m.title = `${baseName} (${providerName})`;
    }
  }

  summarizeModels(models) {
    const services = new Map(); // friendly -> [values]
    const order = [];

    for (const m of models || []) {
      let provider = (m.options && m.options.service) ? String(m.options.service) : "unknown";
      provider = provider.toLowerCase();

      const friendly = this.providers[provider] || provider;

      if (!services.has(friendly)) {
        services.set(friendly, []);
        order.push(friendly);
      }
      services.get(friendly).push(m.value);
    }

    const lines = order.map((prov) => `<strong>${prov}:</strong> ${services.get(prov).join(", ")}`);
    return { summary: lines.join("<br>"), providerCount: order.length };
  }

  getProvider(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    return m?.options?.service;
  }

  getModelTitle(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    return m?.title ?? model;
  }

  findAPIkey(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    if (!m) return undefined;

    const provKey = (m.options && m.options.service) ? String(m.options.service) : "";
    const provLower = provKey.toLowerCase();

    if (provLower === "localhost" || provLower === "ollama") return "";

    const key = this.apiKeys?.[provLower];

    if (!key) {
      return false;
    }

    return key;
  }

  // --- DOM Helpers ----
  addEventListenerDropdown(el) {
    const dropdown = el.closest('ul');

    el.addEventListener('click', function (e) {
      e.preventDefault();

      // remove active + aria-selected from all
      dropdown.querySelectorAll('.dropdown-item').forEach(item => {
        item.classList.remove('active');
        item.setAttribute('aria-selected', 'false');
      });

      // add to clicked
      this.classList.add('active');
      this.setAttribute('aria-selected', 'true');
    });
  }

  toggleHideElement(selector, hide = null) {
    const element = document.querySelector(selector);

    if (!element) {
      return;
    }

    if (hide === null) {
      element.classList.toggle('hidden');
    } else {
      element.classList.toggle('hidden', hide);
    }
  }

  addListElements(selector, items) {
    let dropdown = document.getElementById(this.prefix + selector);
    dropdown.innerHTML = '';

    // in case items contains a property called data, unpack it
    if(items?.data) {
      items = items.data;
    }

    items.forEach((item, index) => {
      let value, title, link;

      if (item && typeof item === 'object' && !Array.isArray(item)) {
        value = item.value ?? String(item).toLowerCase();
        title = item.title ?? this.capitalize(String(item.value ?? ''));
        link = item.link ?? '#';

        if (!value && item.title) {
          value = item.title.toLowerCase();
        }
        if (!title && item.value) {
          title = this.capitalize(String(item.value));
        }
      } else {
        const str = String(item);
        value = str.toLowerCase();
        title = this.capitalize(str);
        link = '#';
      }

      const li = document.createElement('li');
      const a = document.createElement('a');
      a.className = 'dropdown-item';
      a.href = link;
      a.dataset.value = value;
      a.textContent = title;

      // mark first item selected
      if (index === 0) {
        a.classList.add('active');
        a.setAttribute('aria-selected', 'true');
      } else {
        a.setAttribute('aria-selected', 'false');
      }

      li.appendChild(a);
      dropdown.appendChild(li);

      // install event listeners on a element
      this.addEventListenerDropdown(a);
    });
  }

  getSelectedListElement(selector) {
    const selected = document.querySelector(`#${selector} [aria-selected="true"]`);
    return selected ? selected.dataset.value : null;
  }

  getManualKeywords(container) {
    const keywords = [];
    const grid = container?.querySelector('.manual-keywords .grid');

    if (!grid) {
      return keywords;
    }

    grid.querySelectorAll('button').forEach((btn) => {
      const keyword = btn.innerText.trim();
      if (keyword) {
        keywords.push(keyword);
      }
    });

    return keywords;
  }

  getPanelPositionByImageId(imageId) {
  let pos = 0;

  document.querySelectorAll('.images-panel .image-panel').forEach((panel) => {
    const imageEl = panel.querySelector('img.image');

    if (imageEl && imageEl.getAttribute('data-imgid') == imageId) {
      const match = panel.getAttribute('id')?.match(/-image-panel-(\d+)$/);
      pos = match ? parseInt(match[1], 10) : 0;
    }
  });

  return pos;
  }

  manualKeywords(el, event) {
    event.preventDefault();

    const txtField = document.getElementById(`${this.prefix}-manual-keywords`);
    const keywords = txtField.value
      .split(',')
      .map(item => item.trim())
      .filter(item => item !== '');

    const grid = el.parentElement.parentElement.querySelector('.grid');

    const existingKeywords = Array.from(grid.querySelectorAll('button')).map(
      btn => btn.textContent.trim().toLowerCase()
    );

    keywords.forEach(keyword => {
      if (existingKeywords.includes(keyword.toLowerCase())) {
        return;
      }

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-outline-primary';
      button.textContent = keyword;

      button.addEventListener('click', function () {
        this.remove();
      });

      grid.appendChild(button);
    });

    txtField.value = '';
  }

  updateImageNavigation() {
    const panels = document.querySelectorAll('.image-panel');
    const prevBtn = document.querySelector(`#${this.prefix}-prev-image-btn`);
    const nextBtn = document.querySelector(`#${this.prefix}-next-image-btn`);

    panels.forEach((panel, index) => {
      panel.style.display = index === this.current_image ? '' : 'none';
    });

    if (prevBtn) prevBtn.disabled = this.current_image === 0;
    if (nextBtn) nextBtn.disabled = this.current_image === panels.length - 1;
  }

  prevImage(el, event) {
    event.preventDefault();

    if (this.current_image > 0) {
      this.current_image--;
      this.updateImageNavigation();
    }
  }

  nextImage(el, event) {
    event.preventDefault();

    const panels = document.querySelectorAll('.image-panel');
    if (this.current_image < panels.length - 1) {
      this.current_image++;
      this.updateImageNavigation();
    }
  }

  async addKeywordsToImg(imgPos, keywords, color = 'black') {
    if (!Array.isArray(keywords) || !keywords.length) {
      console.log('Try to add keywords, but no keywords provided.');
      return;
    }

    // Get the panel
    const panel = document.querySelector(`#${this.prefix}-image-panel-${imgPos}`);
    if (!panel) {
      console.log(`Try to add keywords, but image panel at position ${imgPos} not found.`)
      return;
    }

    // Get the grid
    const grid = panel.querySelector('.grid');
    if (!grid) return;

    // Get image id from data attribute
    const imgID = panel.querySelector('.image').getAttribute('data-imgid');

    // Store keywords to image (ajax call)
    if(!(await this.storeKeywords(imgID, keywords, 'add')))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages({ error: [`${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_STORE_ERROR} ${this.lang.COM_JOOMGALLERY_JS_AIINT_KEYWORDS}: ${keywords_str}`] }, '#image-message-container');
      return;
    }

    // Collect existing keywords (case-insensitive)
    const existingKeywords = Array.from(
      grid.querySelectorAll('input[type="text"]')
    ).map(input => input.value.trim().toLowerCase());

    keywords.forEach((keyword, index) => {
      const value = keyword.trim();
      if (!value) return;

      // Skip duplicates
      if (existingKeywords.includes(value.toLowerCase())) return;

      // Generate a simple tag_id (you can replace this logic if needed)
      const tagId = Date.now() + '-' + index;

      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'input-group grid-item';

      // Create input
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control color-' + color;
      input.value = value;
      input.disabled = true;

      const inputId = `${this.prefix}-keyword-${imgID}-${tagId}`;
      input.id = inputId;

      // Create button
      const button = document.createElement('button');
      button.className = 'btn btn-outline-secondary';
      button.type = 'button';
      button.id = `${inputId}-btn`;
      button.textContent = 'X';

      // Add eventlistener to button
      button.addEventListener('click', (e) => this.removeKeyword(button, e));

      // Accessibility link
      input.setAttribute('aria-describedby', button.id);

      // Append elements
      wrapper.appendChild(input);
      wrapper.appendChild(button);
      grid.appendChild(wrapper);

      // Track to prevent duplicates within same call
      existingKeywords.push(value.toLowerCase());
    });
  }

  async remKeywordsFromImg(imgPos, keywords) {
    if (!Array.isArray(keywords) || !keywords.length) {
      console.log('Try to remove keywords, but no keywords provided.');
      return;
    }

    // Get the panel
    const panel = document.querySelector(`#${this.prefix}-image-panel-${imgPos}`);
    if (!panel) {
      console.log(`Try to remove keywords, but image panel at position ${imgPos} not found.`)
      return;
    }

    // Get the grid
    const grid = panel.querySelector('.grid');
    if (!grid) return;

    // Get image id from data attribute
    const imgID = panel.querySelector('.image').getAttribute('data-imgid');

    // Store keywords to image (ajax call)
    if(!(await this.storeKeywords(imgID, keywords, 'remove')))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages({ error: [`${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_REMOVE_ERROR} ${this.lang.COM_JOOMGALLERY_JS_AIINT_KEYWORDS}: ${keywords_str}`] }, '#image-message-container');
      return;
    }

    // Get all existing keywords
    const existingKeywords = Array.from(grid.querySelectorAll('input[type="text"]'));

    keywords.forEach((keyword, index) => {
      const value = keyword.trim();
      if (!value) return;

      // Remove existing ones
      const el = existingKeywords.find(input => input.value.trim() === value);
      if (el) {

        el.parentElement.remove();
      }
    });
  }

  addKeyword(el, event) {
    event.preventDefault();

    const keywords = [el.innerText.trim()];
    const pos      = this.current_image;
    this.addKeywordsToImg(pos, keywords, 'orange');
  }

  removeKeyword(el, event) {
    event.preventDefault();

    const inputID  = el.id.replace(/-btn$/, "");
    const input    = document.getElementById(inputID);
    const keywords = [input.value.trim()];
    const pos      = this.current_image;

    this.remKeywordsFromImg(pos, keywords);
  }

  async showAccount(el, event) {
    event.preventDefault();

    // Create models list
    const models = this.summarizeModels(this.models);

    // Fetch user info
    await this.getInfo();

    // Add content to modal
    document.getElementById(`${this.prefix}-modal-account-host`).textContent = this.sanitizeUrl(this.host);
    document.getElementById(`${this.prefix}-modal-account-models`).innerHTML = models.summary;
    document.getElementById(`${this.prefix}-modal-account-mail`).textContent = this.info.email;
    document.getElementById(`${this.prefix}-modal-account-balance`).textContent = this.balance;
    document.getElementById(`${this.prefix}-modal-account-infractions`).textContent = this.info.infractions;

    this.showModal('account');
  }

  async testConnection(el, event) {
    if (event) event.preventDefault();

    const url = this.sanitizeUrl(this.host);

    // Test ping
    const ping = await this.sendGet(url);

    if(ping.data !== "PING") {

      const title = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TITLE ?? 'Connection failed';
      const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TEXT + url;

      JoomlaDialog.alert(msg, title)
      .then(() => {
        console.log('Connection to "' + url + '" failed. Check your AI Interface host URL.');
      });
    } else {
      const info = await this.getInfo();

      if(info.status == 200) {
        const title = this.lang.COM_JOOMGALLERY_JS_AIINT_SUCCESS_TITLE ?? 'Success';
        const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_SUCCESS_TEXT + '  ' + url;

        JoomlaDialog.alert(msg, title)
      } else {
        const title = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TITLE ?? 'Failed';
        const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TEXT + '  ' + url;

        JoomlaDialog.alert(msg, title)
        .then(() => {
          console.log('Authentication to "' + url + '" failed. Check your AI Interface API-Key. Make sure your account in the AI Interface is up and running.');
        });
      }
    }
  }

  async keywordsGenerate(el, event) {
    event.preventDefault();

    // Get required data from UI
    let model  = this.getSelectedListElement(`${this.prefix}-models-dropdown`);
    let panels = document.querySelectorAll('.images-panel .image-panel');
    const manualKeywords = this.getManualKeywords(el.parentElement?.parentElement);

    if (!panels.length) {
      Joomla.renderMessages({ error: [this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_PANELS_ERROR] }, '#image-message-container');
      return;
    }

    // Check that privacy terms are agreed
    const isChecked = document.getElementById(`${this.prefix}-privacy-box`).checked;
    if (!isChecked) {
      Joomla.renderMessages({ error: [this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_PRIVACY_ERROR] }, '#image-message-container');
      return;
    }

    // Create statistics object
    const stats = {
      total: panels.length,

      // Fetching base64 image
      fetchDone: 0,
      fetchSuccess: 0,
      fetchFailed: 0,

      // Fetching base64 image
      generateDone: 0,
      generateSuccess: 0,
      generateFailed: 0,

      createdKeywords: new Set(),
      modelTokens: 0,
      serviceTokens: 0,
      infractions: 0
    };

    // Create the options object
    const options = {
      'confidence_values': false,
      'model': model,
      'predefined_tags': manualKeywords,
      'prompt_mode': this.getSelectedListElement(`${this.prefix}-modes-dropdown`),
      'service': this.getProvider(model),
      'language': this.getSelectedListElement(`${this.prefix}-langs-dropdown`),
      'suggested_topic': document.getElementById(`${this.prefix}-prompt-description`)?.value ?? '',
      'tag_count': document.getElementById(`${this.prefix}-nmb-keywords`)?.value ?? '',
      'token_count': true
    }

    this.showModal('generate');
    this.showProgressSection();

    this.updateImageFetchProgress(stats.total, 0, 0, 0, stats.total);
    this.updateKeywordGenerationProgress(1, 0, 0, 0, 1);

    const workerCount = Math.max(1, Number(this.configs.max_parallel || 1));

    // Step 1: fetch all images in parallel with sema
    const {
      results: fetchedResults,
      successCount: imageFetchSuccess,
      failedCount: imageFetchFailed
    } = await this.processImagesWithSema(panels, workerCount, stats);

    const images = fetchedResults
      .filter((item) => item?.success && item?.data)
      .map((item) => ({
        id: item.id,
        base64_data: item.data
      }));

    if (!images.length) {
      this.stopKeywordGenerationProgressTimer();

      this.renderGenerateSummary({
        successImages: 0,
        failedImages: stats.total,
        modelTitle: model,
        keywords: [],
        modelTokens: 0,
        serviceTokens: 0,
        infractions: 0,
        newBalance: this.balance,
        failedItems: fetchedResults
          .filter((r) => !r?.success)
          .map((r, index) => ({
            id: r?.id ?? index,
            error: r?.message ?? 'Image fetch failed'
          }))
      });

      return {
        success: false,
        errors: {
          image_fetch: {
            status: 0,
            error: 'All image fetches failed.'
          }
        },
        nmbErrors: imageFetchFailed,
        nmbSuccess: 0
      };
    }

    // Step 2: estimate and animate generation progress
    const estimatedSeconds = this.getGenerationTime(images.length, model);
    this.startKeywordGenerationProgressTimer(estimatedSeconds);

    // Step 3: call generation only once
    const response = await this.genKeywords(null, images, options);

    // Stop timer first
    this.stopKeywordGenerationProgressTimer();

    const modelTitle = this.getModelTitle(model);

    // Hard failure before any usable API payload exists
    if (!response?.success || !response?.data) {
      stats.generateFailed = images.length;

      // Show generation bar as failed/complete
      this.updateKeywordGenerationProgress(1, 1, 0, 1, 0);

      const failedItems = images.map((img) => ({
        id: img.id,
        error: response?.message || this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_GENERATE_ERROR || 'Keyword generation failed.'
      }));

      this.renderGenerateSummary({
        successImages: 0,
        failedImages: stats.fetchFailed + images.length,
        modelTitle,
        keywords: [],
        modelTokens: 0,
        serviceTokens: 0,
        infractions: 0,
        newBalance: this.balance,
        failedItems
      });

      return {
        success: false,
        errors: {
          generate: {
            status: response?.status ?? 0,
            error: response?.message || 'Keyword generation failed.'
          }
        },
        nmbErrors: stats.fetchFailed + images.length,
        nmbSuccess: 0
      };
    }

    // Valid generation response received
    // Step 4: stop timer and complete generation bar
    this.finishKeywordGenerationProgressTimer();

    // Step 5: process returned results
    const processed = await this.processKeywordResults(response, stats);

    const failedItems = [];
    Object.entries(processed.errors || {}).forEach(([id, item]) => {
      failedItems.push({
        id,
        error: item.error
      });
    });

    // include fetch errors too
    fetchedResults
      .filter((r) => !r?.success)
      .forEach((r, index) => {
        failedItems.push({
          id: r?.id ?? `fetch-${index}`,
          error: r?.message || 'Image fetch failed'
        });
      });

    this.renderGenerateSummary({
      successImages: stats.generateSuccess,
      failedImages: stats.generateFailed + stats.fetchFailed,
      modelTitle: modelTitle,
      keywords: Array.from(stats.createdKeywords),
      modelTokens: stats.modelTokens,
      serviceTokens: stats.serviceTokens,
      infractions: stats.infractions,
      newBalance: this.balance,
      failedItems
    });

    // Update user info
    const balance = await this.getTokens();
    if (balance?.data?.balance !== undefined) {
      document.getElementById(`${this.prefix}-balance-value`).textContent = String(balance.data.balance);
    }
  }

  async processImagesWithSema(panels, workerCount = 1, stats) {
    const sema = new Sema(workerCount);
    const panelList = Array.from(panels);
    const results = new Array(panelList.length);

    let nextIndex = 0;
    let successCount = 0;
    let failedCount = 0;

    const runWorkerLoop = async () => {
      while (true) {
        const currentIndex = nextIndex++;
        if (currentIndex >= panelList.length) {
          break;
        }

        await sema.acquire();
        const panel = panelList[currentIndex];

        try {
          const fetched = await this.fetchImageAsBase64(panel);
          results[currentIndex] = fetched;

          stats.fetchDone++;

          if (fetched.success && fetched.data) {
            stats.fetchSuccess++;
            successCount++;
          } else {
            stats.fetchFailed++;
            failedCount++;
          }

          this.updateImageFetchProgress(
            stats.total,
            stats.fetchDone,
            stats.fetchSuccess,
            stats.fetchFailed,
            stats.total - stats.fetchDone
          );
        } catch (error) {
          results[currentIndex] = {
            success: false,
            id: null,
            status: 0,
            message: error instanceof Error ? error.message : String(error),
            data: null
          };

          stats.fetchDone++;
          stats.fetchFailed++;
          failedCount++;

          this.updateImageFetchProgress(
            stats.total,
            stats.fetchDone,
            stats.fetchSuccess,
            stats.fetchFailed,
            stats.total - stats.fetchDone
          );
        } finally {
          sema.release();
        }
      }
    };

    const workerTotal = Math.min(workerCount, panelList.length);
    const promises = [];

    for (let i = 0; i < workerTotal; i++) {
      promises.push(runWorkerLoop());
    }

    await Promise.allSettled(promises);

    return {
      results,
      successCount,
      failedCount
    };
  }

  async processKeywordResults(response, stats) {
    const payload = response?.data ?? {};
    const results = payload?.results ?? [];

    if (!response?.success) {
      return {
        success: false,
        errors: {
          '0': {
            status: response?.status ?? 0,
            error: response?.message || 'Keyword generation request to API failed.'
          }
        }
      };
    }

    if (response.status !== 200 || !Array.isArray(results) || results.length < 1) {
      return {
        success: false,
        errors: {
          '0': {
            status: payload?.status ?? response?.status ?? 0,
            error: response?.message || 'API returned no valid result.'
          }
        }
      };
    }

    const errors = {};

    for (const result of results) {
      if (payload?.status !== 1 || result.error) {
        stats.generateFailed++;

        errors[String(result.id ?? stats.generateFailed)] = {
          status: payload?.status ?? response?.status ?? 0,
          error: result.error || 'Unknown generation error'
        };

        continue;
      }

      const keywords = (result.tags || [])
        .map((tag) => tag?.name?.trim())
        .filter(Boolean);

      keywords.forEach((kw) => stats.createdKeywords.add(kw));
      stats.modelTokens += Number(result.model_tokens ?? payload.model_tokens ?? 0);
      stats.serviceTokens += Number(result.service_tokens ?? payload.service_tokens ?? 0);
      stats.infractions = Number(payload.total_infractions ?? stats.infractions ?? 0);

      const imageId = result.id ?? null;
      const pos = imageId !== null ? this.getPanelPositionByImageId(imageId) : 0;
      await this.addKeywordsToImg(pos, keywords, 'red');

      stats.generateSuccess++;
    }

    return {
      success: Object.keys(errors).length === 0,
      errors
    };
  }

  // --- Generation Modal Helpers ----
  showModal(type) {
    const modalEl = document.getElementById(`${this.prefix}-modal-${type}`);
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      console.error('Bootstrap Modal is not available.');
      return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
      backdrop: 'static',
      keyboard: false
    });

    modal.show();
  }

  showProgressSection() {
    document.getElementById(`${this.prefix}-progress-section`)?.classList.remove('d-none');
    document.getElementById(`${this.prefix}-summary-section`)?.classList.add('d-none');
  }

  showSummarySection() {
    document.getElementById(`${this.prefix}-progress-section`)?.classList.add('d-none');
    document.getElementById(`${this.prefix}-summary-section`)?.classList.remove('d-none');
  }

  updateImageFetchProgress(total, done, success, failed, pending) {
    const bar = document.getElementById(`${this.prefix}-progress-fetch-bar`);
    const text = document.getElementById(`${this.prefix}-progress-fetch-text`);

    if (!bar) return;

    const percent = Math.round((done / total) * 100);

    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', percent);
    bar.textContent = `${done} / ${total}`;

    text.textContent = `${this.lang.COM_JOOMGALLERY_JS_AIINT_PREPARED}: ${success}, ${this.lang.COM_JOOMGALLERY_JS_AIINT_FAILED}: ${failed}`;

    console.log(`[AIinterface] Keyword image fetch progress: total=${total}, success=${success}, failed=${failed}, pending=${pending}`);
  }

  updateKeywordGenerationProgress(total, done, success, failed, pending) {
    const bar = document.getElementById(`${this.prefix}-progress-generate-bar`);
    const text = document.getElementById(`${this.prefix}-progress-generate-text`);

    if (!bar) return;

    const safeTotal = total > 0 ? total : 1;
    const safeDone = Math.max(0, Math.min(done, safeTotal));
    const percent = Math.round((safeDone / safeTotal) * 100);

    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', String(percent));
    bar.textContent = `${percent}%`;

    text.textContent = `Estimated progress: ${percent}%`;

    console.log(`[AIinterface] Keyword generation progress: total=${total}, done=${done}, success=${success}, failed=${failed}, pending=${pending}`);
  }

  startKeywordGenerationProgressTimer(totalSeconds) {
    this.stopKeywordGenerationProgressTimer();

    const duration = Math.max(1, Number(totalSeconds) || 1);
    const startedAt = Date.now();

    this._generationProgressTimer = window.setInterval(() => {
      const elapsedSeconds = (Date.now() - startedAt) / 1000;
      const ratio = Math.min(elapsedSeconds / duration, 0.95); // stop at 95% until real response arrives
      const done = ratio;
      const success = ratio;
      const failed = 0;

      this.updateKeywordGenerationProgress(1, done, success, failed, 1 - done);
    }, 200);
  }

  stopKeywordGenerationProgressTimer() {
    if (this._generationProgressTimer) {
      window.clearInterval(this._generationProgressTimer);
      this._generationProgressTimer = null;
    }
  }

  finishKeywordGenerationProgressTimer() {
    this.stopKeywordGenerationProgressTimer();
    this.updateKeywordGenerationProgress(1, 1, 1, 0, 0);
  }

  renderGenerateSummary(summary) {
    this.showSummarySection();

    const box  = document.getElementById(`${this.prefix}-summary-status`);
    const icon = document.getElementById(`${this.prefix}-summary-status-icon`);

    // Get success and failed numbers
    const success = summary.successImages ?? 0;
    const failed  = summary.failedImages ?? 0;

    // Change icon
    box.classList.remove('jgai-status-success', 'jgai-status-failed');
    if (failed === 0) {
      box.classList.add('jgai-status-success');
      icon.textContent = '✓';
    } else if (success === 0) {
      box.classList.add('jgai-status-failed');
      icon.textContent = '✕';
    } else {
      box.classList.add('jgai-status-warning');
      icon.textContent = '?';
    }

    // Generate list of failed images
    this.renderFailedImages(summary.failedItems);

    // Add summary content to table
    document.getElementById(`${this.prefix}-summary-success`).textContent = success;
    document.getElementById(`${this.prefix}-summary-failed`).textContent = failed;
    document.getElementById(`${this.prefix}-summary-model`).textContent = summary.modelTitle ?? summary.model ?? '-';
    document.getElementById(`${this.prefix}-summary-keywords`).textContent = (summary.keywords || []).join(', ') || '-';
    document.getElementById(`${this.prefix}-summary-model-tokens`).textContent = summary.modelTokens ?? 0;
    document.getElementById(`${this.prefix}-summary-service-tokens`).textContent = summary.serviceTokens ?? 0;
    document.getElementById(`${this.prefix}-summary-infractions`).textContent = summary.infractions ?? 0;
    document.getElementById(`${this.prefix}-summary-balance`).textContent = summary.newBalance ?? '-';
  }

  renderFailedImages(items = []) {
    const section = document.getElementById(`${this.prefix}-summary-failed-section`);
    const list    = document.getElementById(`${this.prefix}-summary-failed-list`);

    list.innerHTML = '';

    if (!items.length) {
      section.classList.add('d-none');
      return;
    }

    section.classList.remove('d-none');

    items.forEach(item => {
      const li = document.createElement('li');
      li.className = 'list-group-item text-danger';

      li.textContent = `${this.lang.COM_JOOMGALLERY_JS_AIINT_IMAGE}-ID ${item.id}: ${item.error}`;

      list.appendChild(li);
    });
  }

  // --- HTTP -------
  async sendGet(url, headers) {
    // Add default headers
    headers = this.addHeader(headers, false);

    // Set request parameters
    let params = {
      method: 'GET',
      cache: 'default',
      redirect: 'follow',
      referrerPolicy: 'no-referrer-when-downgrade',
      headers: headers
    };

    // Perform the fetch request
    let response;
    try {
      response = await fetch(url, params);
    } catch (e) {
      const msg = `Network error: ${String(e)}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: 0, message: msg, messages: [], data: null };
    }

    // Resolve promise as text string
    let txt = await response.text();

    if (!response.ok) {
      // Catch network error
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return {success: false, status: response.status, message: msg, messages: [txt], data: null};
    }

    // Detect response content type (NOT request header)
    const responseContentType = response.headers.get('content-type') || '';

    // JSON response
    if (responseContentType.includes('application/json')) {
      return this.parseJsonResponse(txt, response.status, response.statusText);
    }

    // Plain text (base64 etc.)
    return {
      success: true,
      status: response.status,
      message: response.statusText,
      messages: [],
      data: txt
    };
  }

  async sendPost(url, bodyObjOrString, headers = {}) {
    headers = this.addHeader(headers);

    const contentType = headers["Content-Type"] || headers["content-type"] || "";
    let body = '';

    if(bodyObjOrString instanceof FormData)
    {
      body = bodyObjOrString;

      // Let the browser set multipart/form-data with boundary
      delete headers["Content-Type"];
      delete headers["content-type"];
    }
    else if(contentType.includes("application/json"))
    {
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : JSON.stringify(bodyObjOrString ?? {});
    }
    else if(contentType.includes("application/x-www-form-urlencoded"))
    {
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : new URLSearchParams(bodyObjOrString ?? {}).toString();
    }
    else
    {
      // Fallback: keep strings as-is, stringify plain objects
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : JSON.stringify(bodyObjOrString ?? {});
    }

    const params = {
      method: "POST",
      cache: "default",
      redirect: "follow",
      referrerPolicy: "no-referrer-when-downgrade",
      headers,
      body,
    };

    let response;
    try {
      response = await fetch(url, params);
    } catch (e) {
      const msg = `Network error: ${String(e)}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: 0, message: msg, messages: [], data: null };
    }

    const txt = await response.text();

    if (!response.ok) {
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: response.status, message: msg, messages: [txt], data: null };
    }

    // Detect response content type (NOT request header)
    const responseContentType = response.headers.get('content-type') || '';

    // JSON response
    if (responseContentType.includes('application/json')) {
      return this.parseJsonResponse(txt, response.status, response.statusText);
    }

    // Plain text (base64 etc.)
    return {
      success: true,
      status: response.status,
      message: response.statusText,
      messages: [],
      data: txt
    };
  }

  async storeKeywords(imgId, keywords, action) {
    const url = this.sanitizeUrl(`${this.configs.base_url}/index.php?option=com_joomgallery&task=image.ajaxsavetags`);

    const headers = {
      'Authorization': '',
      'Content-Type': 'application/json',
      'X-CSRF-Token': this.configs.session
    };

    const formData = new FormData();
    formData.append('id', imgId);
    formData.append('action', action);

    keywords.forEach(keyword => {
      formData.append('keywords[]', keyword);
    });

    const res = await this.sendPost(url, formData, headers);

    if(!res.success) {
      console.log(res.error);
      console.log(res.data);

      if(action == 'add') {
        Joomla.renderMessages({'error':['Tags could not be added to image.']}, '#image-message-container');
      } else if(action == 'remove') {
        Joomla.renderMessages({'error':['Tags could not be removed from image.']}, '#image-message-container');
      }
    }
    else {
      if(action == 'add') {
        Joomla.renderMessages({'success':['Tags successfully stored to image.']}, '#image-message-container');
      } else if(action == 'remove') {
        Joomla.renderMessages({'success':['Tags successfully deleted from image.']}, '#image-message-container');
      }
    }

    return res.success
  }

  async fetchImageAsBase64(panel) {
    const imgEl = panel.querySelector('img.image');

    if (!imgEl) {
      return {
        success: false,
        status: null,
        message: 'No image element found in panel.',
        messages: [],
        data: null
      };
    }

    const imgID = imgEl.getAttribute('data-imgid');

    const variables = new URLSearchParams({
      id: imgID,
      type: this.configs.imagetype ?? '',
      base64: 1,
      resize: this.configs.resize ?? 0,
      resize_type: 3
    });

    const urlBase64 = this.sanitizeUrl(
      `${this.configs.base_url}/index.php?option=com_joomgallery&view=image&format=raw&${variables.toString()}`
    );

    const headersBase64 = {
      Authorization: '',
      'X-CSRF-Token': this.configs.session
    };

    const res = await this.sendGet(urlBase64, headersBase64);

    if (!res.success) {
      return {
        success: false,
        id: imgID,
        message: res.message || 'Image fetch failed.',
        data: null,
      };
    }

    return {
      success: true,
      id: imgID,
      message: 'OK',
      data: res.data,
    };
  }

  // --- API endpoints ----
  async getInfo(e) {
    if (e) e.preventDefault();

    const route = "users/info";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const data = await this.sendGet(url, headers);

    if (data?.data?.email) {
      this.info = data.data;
    } else if (data?.email) {
      this.info = data;
    }

    return data;
  }

  async getTokens(e) {
    if (e) e.preventDefault();

    const route = "/users/balance";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type" : "application/json"
    }

    const data = await this.sendGet(url, headers);

    if (data?.data?.balance) {
      this.balance = data.data.balance;
    } else if (data?.balance) {
      this.balance = data.balance;
    }

    return data;
  }

  async getLanguages(e) {
    if (e) e.preventDefault();

    const route = "/tags/languages";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type" : "application/json"
    }

    const data = await this.sendGet(url, headers);

    if (data?.data?.languages) {
      this.languages = data.data.languages;
    } else if (data?.languages) {
      this.languages = data.languages;
    }

    return data;
  }

  async getModels(e) {
    if (e) e.preventDefault();

    const route = "tags/models";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const data = await this.sendGet(url, headers);

    // Post-processing
    // If API returns {models:[{name,service,privacy}]} directly:
    if (data?.success !== false && data?.models) {
      // (Your Lua expects data.models; your older JS parsed res.data maybe.)
      const apiModels = data.models;

      if (apiModels.length > 0 && apiModels[0].value) {
        this.def_model = apiModels[0].value;
      }

      // Add services to providers table
      this.addProviders(data.services)

      const mapping = { value: "name", title: "service", options: ["service", "privacy"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      this.models = mapped;
      return { ...data, mappedModels: mapped, def_model: this.def_model };
    }
    // If API returns wrapped data like {data:{models:[...]}}:
    if (data?.data?.models) {
      const apiModels = data.data.models;

      if (apiModels.length > 0 && apiModels[0].value) {
        this.def_model = apiModels[0].value;
      }

      // Add services to providers table
      this.addProviders(data.data.services)

      const mapping = { value: "value", title: "title", options: ["service", "privacy", "modes"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      // Extranct available modes
      mapped.forEach(model => {
        model.options?.modes?.forEach(mode => {
          if (!this.modes.includes(mode)) this.modes.push(mode);
        });
      });

      this.models = mapped;
      return mapped;
    }

    return data;
  }

  getGenerationTime(count = 0, model = '') {
    if (!count) {
      return 0;
    }

    // Base cost per image in seconds
    const secPerImage = 2.5;

    // Rough model factor
    let modelFactor = 1.0;
    const m = String(model || '').toLowerCase();

    if (m.includes('nano')) {
      modelFactor = 0.6;
    } else if (m.includes('mini')) {
      modelFactor = 0.8;
    } else if (m.includes('4o')) {
      modelFactor = 1.0;
    } else if (m.includes('gemma')) {
      modelFactor = 1.1;
    } else {
      modelFactor = 1.0;
    }

    // Rough image-size factor
    const px = Number(this.configs.resize || this.configs.img_size || 500) || 500;
    const sizeFactor = px / 500;

    // Small fixed overhead
    const overhead = 3;

    return Math.ceil(overhead + (count * secPerImage * modelFactor * sizeFactor));
  }

  async genKeywords(e, images, options = {}) {
    if (e) e.preventDefault();

    const route = "tags/generate";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const imgs = images || [];
    const usedModel = options.model || this.def_model;

    if (!Array.isArray(imgs) || imgs.length < 1) {
      return { success: false, status: 0, message: this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_IMGS_ERROR, data: null };
    }

    const apiKey = this.findAPIkey(usedModel, this.models);
    if (apiKey === false) {
      return { success: false, status: 0, message: `${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_API_KEY_ERROR} '${usedModel}'`, data: null };
    }

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const body = {
      api_key: apiKey,
      options,
      images: imgs
    };

    return await this.sendPost(url, body, headers);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  if (!window.Joomla) {
    throw new Error('Joomla API was not properly initialised');
  }

  // Example: pull config from Joomla options (adjust key to your code)
  const opts = Joomla.getOptions('com_joomgallery.aiinterface', {});
  const lang  = Joomla.getOptions('com_joomgallery.aiinterface.lang', {});
  const prefix = opts.prefix ?? 'jgai';
  const host = opts.host ?? 'localhost';
  const token = opts.token ?? '';
  const clientName = opts.client_name ?? 'JG-General';
  const configs = opts.configs ?? {};
  const session = opts.session ?? '';
  const baseURL = opts.base_url ?? '';
  const autoload = opts.autoload ?? false;
  let connected = false;
  let balance = 0;
  let models = {};
  let langs = {};

  window.Joomla.aiinterface = new AIinterface(prefix, host, token, clientName, configs, lang);
  let ai = window.Joomla.aiinterface;

  if(autoload) {
    // Check connction and get balance
    balance = await ai.getTokens();
    if(balance?.data?.balance) {
      connected = true;
      models = await ai.getModels();
      langs = await ai.getLanguages();

      // Update balance
      document.getElementById(prefix + '-balance-value').textContent = String(balance.data.balance);

      // Update models dropdown
      ai.addListElements('-models-dropdown', ai.models)

      // Update modes dropdown
      ai.addListElements('-modes-dropdown', ai.modes)

      // Update languages dropdown
      ai.addListElements('-langs-dropdown', ai.languages)
    } else {
      // Connection failed
      Joomla.renderMessages({warning: [ai.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_CONNECTION_ERROR]}, );

      if(balance?.message !== 'OK') {
        Joomla.renderMessages({error: [ai.lang.COM_JOOMGALLERY_JS_AIINT_MSG_RESPOND_STATS + ': ' + balance.message]});
      }

      if(balance?.data?.messages.length > 0) {
        Joomla.renderMessages({warning: [ai.lang.COM_JOOMGALLERY_JS_AIINT_MSG_API_ANSWER + ': ' + balance.data.messages[0]]});
      }
    }
  }

  // Install event listener on geo location switch
  document.querySelectorAll('input[name="jgai_exif_location"]').forEach(el => {
    el.addEventListener('change', () => {
      const useExif = document.querySelector('input[name="jgai_exif_location"]:checked').value === '1';
      window.Joomla.aiinterface.toggleHideElement('#jgai-gr-geo-location', useExif);
    });
  });

  // Install event listeners on dropdowns
  document.querySelectorAll('[id^="'+prefix+'-"][id$="-dropdown"]').forEach(el => {
    el.querySelectorAll('.dropdown-item').forEach(item => {
      ai.addEventListenerDropdown(item);
    });
  });

  // Install event listeners on buttons
  document.querySelectorAll('[id^="'+prefix+'-"][id$="-btn"]').forEach(el => {
    const name = el.id.split('-').slice(1, -1).join('-');
    const fn = name.replace(/-([a-z])/g, (_, c) => c.toUpperCase());

    // Image Keyword Button
    let match = name.match(new RegExp(`^keyword-(\\d+)-(\\d+)$`));
    if (match) {
      const [, imgId, tagId] = match;
      el.addEventListener('click', (e) => ai.removeKeyword(el, e));
      return;
    }

    // Manual Keyword Button
    match = name.match(new RegExp(`^manual-keyword-(\\d+)$`));
    if (match) {
      el.addEventListener('click', (e) => ai.removeManualKeyword(el, e));
      return;
    }

    // Most used Keywords Button
    match = name.match(new RegExp(`^keywords-list-(\\d+)$`));
    if (match) {
      el.addEventListener('click', (e) => ai.addKeyword(el, e));
      return;
    }

    // Any other Button
    if (typeof ai[fn] === 'function') {
      el.addEventListener('click', (e) => ai[fn](el, e));
    } else {
      console.warn(`AIinterface: function ${fn}() does not exist. The corresponding button will not work.`);
    }
  });
})

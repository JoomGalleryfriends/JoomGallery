// JoomGallery AIinterface class //
import JoomlaDialog from 'joomla.dialog'

class AIinterface {
  // Settings
  name = 'JoomGallery AI Interface';
  prefix = 'jgai';
  host = 'localhost';
  systemLang = 'en';
  configs = {};
  lang = {};

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

    // Detect language
    if(this.configs.def_lang) {
      if (this.configs.def_lang.includes('de')) this.systemLang = 'de';
    }

    // Detect client version
    if(this.configs.version) {
      this.client_version = this.configs.version;
    }
  }

  sanitizeUrl(url) {
    const hadTrailingSlash = /\/$/.test(url);

    // Collapse multiple slashes except after "http(s):"
    url = url.replace(/([^:])\/{2,}/g, "$1/");

    // Remove trailing slashes
    url = url.replace(/\/+$/g, "");

    if (hadTrailingSlash) url += "/";
    return url;
  }

  capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  urlEncode(s) {
    return encodeURIComponent(String(s));
  }

  addHeader(headers = {}) {
    const h = { ...headers };

    if (!Object.prototype.hasOwnProperty.call(h, 'Content-Type')) {
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

    const lines = order.map((prov) => `${prov}: ${services.get(prov).join(", ")}`);
    return { summary: lines.join("\n"), providerCount: order.length };
  }

  getProvider(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    return m?.options?.service;
  }

  findAPIkey(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    if (!m) return undefined;

    const provKey = (m.options && m.options.service) ? String(m.options.service) : "";
    const provLower = provKey.toLowerCase();

    if (provLower === "localhost" || provLower === "ollama") return "";

    const optKey = `${provLower}_key`;
    const key = this.providers?.[optKey];

    if (!key) {
      // Match Lua behavior: return false to signal missing key
      return false;
    }

    return key;
  }

  async testConnection(el, event) {
    if (event) event.preventDefault();

    // Test ping
    const ping = await this.sendGet(this.host);

    if(ping.data !== "PING") {

      const title = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TITLE ?? 'Connection failed';
      const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TEXT + this.host;

      JoomlaDialog.alert(msg, title)
      .then(() => {
        console.log('Connection to "' + this.host + '" failed. Check your AI Interface host URL.');
      });
    } else {
      const info = await this.getInfo();

      const title = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TITLE ?? 'Authentication failed';
      const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TEXT + ' ' + this.host;

      if(info.status !== 200) {
        JoomlaDialog.alert(msg, title)
        .then(() => {
          console.log('Authentication to "' + this.host + '" failed. Check your AI Interface API-Key. Make sure your account in the AI Interface is up and running.');
        });
      }
    }
  }

  // --- DOM Helpers ----
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
        a.setAttribute('aria-selected', 'true');
      }

      li.appendChild(a);
      dropdown.appendChild(li);
    });
  }

  getSelectedListElement(selector) {
    const selected = document.querySelector(`#${selector} [aria-selected="true"]`);
    return selected ? selected.dataset.value : null;
  }

  manualKeywords(el, event) {
    event.preventDefault();

    const txtField = document.getElementById('jgai-manual-keywords');
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

  async addKeywordsToImg(imgPos, keywords) {
    if (!Array.isArray(keywords) || !keywords.length) {
      console.log('Try to add keywords, but no keywords provided.');
      return;
    }

    // Get the panel
    const panel = document.querySelector(`#jgai-image-panel-${imgPos}`);
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
    if(!this.storeKeywords(imgID, keywords, 'add'))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages(`Keywords could not be added/stored to database.<br>Keywords: ${keywords_str}`);
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
      input.className = 'form-control';
      input.value = value;
      input.disabled = true;

      const inputId = `jgai-keyword-${imgID}-${tagId}`;
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
    const panel = document.querySelector(`#jgai-image-panel-${imgPos}`);
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
    if(!this.storeKeywords(imgID, keywords, 'remove'))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages(`Keywords could not be removed/stored to database.<br>Keywords: ${keywords_str}`);
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
    this.addKeywordsToImg(pos, keywords);
  }

  removeKeyword(el, event) {
    event.preventDefault();

    const inputID  = el.id.replace(/-btn$/, "");
    const input    = document.getElementById(inputID);
    const keywords = [input.value.trim()];
    const pos      = this.current_image;

    this.remKeywordsFromImg(pos, keywords);
  }

  async keywordsGenerate(el, event) {
    event.preventDefault();

    model = this.getSelectedListElement(`${this.prefix}-models-dropdown`);

    result = await this.genKeywords(event, images, model, options);
  }

  // --- HTTP -------
  async sendGet(url, headers) {
    // Add default headers
    headers = this.addHeader(headers);

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
      return {success: false, status: response.status, message: response.message, messages: [txt], data: null};
    }

    const res = this.parseJsonResponse(txt, response.status, response.statusText);

    return res;
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

    const res = this.parseJsonResponse(txt, response.status, response.statusText);
    return res;
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
      this.laguages = data.languages;
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

  async genKeywords(e, images, model, options = {}) {
    if (e) e.preventDefault();

    const route = "tags/generate";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const imgs = images || [];
    const usedModel = model || this.def_model;

    if (!Array.isArray(imgs) || imgs.length < 1) {
      return { success: false, status: 0, message: "No images provided. You need to send at least one image.", data: null };
    }

    const apiKey = this.findAPIkey(usedModel, this.models);
    if (apiKey === false) {
      return { success: false, status: 0, message: `No API key found for model '${usedModel}'`, data: null };
    }

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const body = {
      api_key: apiKey,
      options,
      images: imgs,
      model: usedModel, // optional; include if your API wants it
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
      Joomla.renderMessages({warning: ['No connection to the AI Interface. Check your connection credentials in the JoomGallery configuration.']}, );

      if(balance?.message !== 'OK') {
        Joomla.renderMessages({error: ['Respond status: ' + balance.message]});
      }

      if(balance?.data?.messages.length > 0) {
        Joomla.renderMessages({warning: ['Answer from API: ' + balance.data.messages[0]]});
      }
    }
  }

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

  const btn = document.getElementById("ai-keywords-generate");
  if (btn) {
    btn.addEventListener("click", (e) => window.Joomla.aiinterface.getModels(e));
  }
})

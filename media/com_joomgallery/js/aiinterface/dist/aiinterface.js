var AIinterface;
/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it declares 'AIinterface' on top-level, which conflicts with the current library output.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
// JoomGallery AIinterface class //

class AIinterface {

  name = 'JoomGallery AI Interface';
  host = 'localhost';
  token = '';
  models = [];
  selected_model = 'gemma3'
  client_name = 'JG-General'

  constructor(host, token, client_name, client_version, providerKeys) {
    if (host) this.host = host;
    if (token) this.token = token;
    if (client_name) this.client_name = client_name;
    if (client_version) this.client_version = client_version;
    if (providerKeys && typeof providerKeys === "object") this.providerKeys = providerKeys;
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
      h['X-Client-Version'] = 'v1.0.0';
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
      return { success: false, status, message: statusText || "Empty response", messages: {}, data: { error: "", data: null } };
    }

    // Clean JSON response (your old check was startsWith('{"success"'))
    const trimmed = txt.trim();
    if (trimmed.startsWith("{")) {
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

        return { ...obj, status };
      } catch {
        // fall through to other heuristics
      }
    }

    // PHP fatal error text
    if (txt.includes("Fatal error")) {
      return { success: false, status, message: statusText, messages: {}, data: { error: txt, data: null } };
    }

    // Warnings/notices before JSON
    // Example: "Warning...\n{...json...}"
    const idx = txt.indexOf("\n{");
    if (idx !== -1) {
      const warningPart = txt.slice(0, idx);
      const jsonPart = txt.slice(idx + 1); // keep '{' at start

      try {
        const temp = JSON.parse(jsonPart);

        // decode temp.data if it is JSON string
        let data = temp.data;
        if (typeof data === "string") {
          try {
            data = JSON.parse(data);
          } catch {
            // keep as-is
          }
        }

        return {
          success: true,
          status,
          message: warningPart,
          messages: temp.messages ?? {},
          data,
        };
      } catch {
        // last resort
      }
    }

    // Unknown non-JSON response
    return { success: false, status, message: statusText, messages: {}, data: { error: txt, data: null } };
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
      const providerName = this.providerNames[provKey.toLowerCase()] || provKey;
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

      const friendly = this.providerNames[provider] || provider;

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
    const key = this.providerKeys?.[optKey];

    if (!key) {
      // Match Lua behavior: return false to signal missing key
      return false;
    }

    return key;
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
      return { success: false, status: 0, message: msg, messages: {}, data: { error: msg, data: null } };
    }

    // Resolve promise as text string
    let txt = await response.text();

    if (!response.ok) {
      // Catch network error
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return {success: false, status: response.status, message: response.message, messages: {}, data: {error: txt, data:null}};
    }

    const res = this.parseJsonResponse(txt, response.status, response.statusText);

    return res;
  }

  async sendPost(url, bodyObjOrString, headers = {}) {
    headers = this.addHeader(headers);

    const body =
      typeof bodyObjOrString === "string"
        ? bodyObjOrString
        : JSON.stringify(bodyObjOrString ?? {});

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
      return { success: false, status: 0, message: msg, messages: {}, data: { error: msg, data: null } };
    }

    const txt = await response.text();

    if (!response.ok) {
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: response.status, message: msg, messages: {}, data: { error: txt, data: null } };
    }

    const res = this.parseJsonResponse(txt, response.status, response.statusText);
    return res;
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

    return await this.sendGet(url, headers);
  }

  async getTokens(e) {
    if (e) e.preventDefault();

    const route = "/users/balance";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type" : "application/json"
    }

    return this.sendGet(url, headers);
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

      if (apiModels.length > 0 && apiModels[0].name) {
        this.def_model = apiModels[0].name;
      }

      const mapping = { value: "name", title: "service", options: ["service", "privacy"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      this.models = mapped;
      return { ...data, mappedModels: mapped, def_model: this.def_model };
    }
    // If API returns wrapped data like {data:{models:[...]}}:
    if (data?.data?.models) {
      const apiModels = data.data.models;

      if (apiModels.length > 0 && apiModels[0].name) {
        this.def_model = apiModels[0].name;
      }

      const mapping = { value: "name", title: "service", options: ["service", "privacy"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      this.models = mapped;
      return { ...data, mappedModels: mapped, def_model: this.def_model };
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

document.addEventListener('DOMContentLoaded', () => {
  if (!window.Joomla) {
    throw new Error('Joomla API was not properly initialised');
  }

  // Example: pull config from Joomla options (adjust key to your code)
  const opts = Joomla.getOptions('com_joomgallery.aiinterface', {});
  const host = opts.host ?? 'localhost';
  const token = opts.token ?? '';
  const clientName = opts.client_name ?? 'JG-General';

  window.Joomla.aiinterface = new AIinterface(host, token, clientName);

  const btn = document.getElementById("jg-ai-tokens");
  if (btn) {
    btn.addEventListener("click", (e) => window.Joomla.aiinterface.getModels(e));
  }
})

})();

AIinterface = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=aiinterface.js.map
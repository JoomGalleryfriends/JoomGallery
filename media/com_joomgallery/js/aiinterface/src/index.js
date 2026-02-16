/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

class AIinterface {

  name = 'JoomGallery AI Interface';
  host = 'localhost';
  token = '';
  models = [];
  selected_model = 'gemma3'
  client_name = 'JG-General'

  constructor(host, token, client_name) {
    if (host) this.host = host;
    if (token) this.token = token;
    if (client_name) this.client_name = client_name;
  }

  /**
   * Adds default headers if not existent
   * @returns {Object}   Headers
   */
  addHeader(headers) {
    if (!Object.prototype.hasOwnProperty.call(headers, 'Content-Type')) {
      headers['Content-Type'] = 'application/json';
    }
    if (!Object.prototype.hasOwnProperty.call(headers, 'X-Client-Name')) {
      headers['X-Client-Name'] = this.client_name;
    }
    if (!Object.prototype.hasOwnProperty.call(headers, 'X-Client-Version')) {
      headers['X-Client-Version'] = 'v1.0.0';
    }

    return headers;
  }

  /**
   * Perform an ajax GET request
   *
   * @returns {Object}   Result object
   *          {success: true, status: 200, message: '', messages: {}, data: { { {success, data, continue, error, debug, warning} }}
   */
  async sendGet(url, headers) {
    // Add default headers
    headers = this.addHeader(headers);

    // Set request parameters
    let parameters = {
      method: 'GET',
      cache: 'default',
      redirect: 'follow',
      referrerPolicy: 'no-referrer-when-downgrade',
      headers: headers
    };

    // Perform the fetch request
    let response = await fetch(url, parameters);

    // Resolve promise as text string
    let txt = await response.text();
    let res = null;

    if (!response.ok) {
      // Catch network error
      return {success: false, status: response.status, message: response.message, messages: {}, data: {error: txt, data:null}};
    }

    if(txt.startsWith('{"success"')) {
      // Response is of type json --> everything fine
      res = JSON.parse(txt);
      res.status = response.status;
      res.data   = JSON.parse(res.data);
    } else if (txt.includes('Fatal error')) {
      // PHP fatal error occurred
      res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
    } else {
      // Response is not of type json --> probably some php warnings/notices
      let split = txt.split('\n{"');
      let temp  = JSON.parse('{"'+split[1]);
      let data  = JSON.parse(temp.data);
      res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
    }

    // Make sure res.data.data.queue is of type array
    if(typeof res.data.data != "undefined" && res.data.data != null && 'queue' in res.data.data) {
      if(res.data.data.queue.constructor !== Array) {
        res.data.data.queue = Object.values(res.data.data.queue);
      }
    }

    return res;
  }

}

document.addEventListener('DOMContentLoaded', () => {
  if (!window.Joomla) {
    throw new Error('Joomla API was not properly initialised');
  }

  // Example: pull config from Joomla options (adjust key to your code)
  const opts = Joomla.getOptions('joomgallery-ai', {});
  const host = opts.host ?? 'localhost';
  const token = opts.token ?? '';
  const clientName = opts.client_name ?? 'JG-General';

  window.Joomla.aiinterface = new AIinterface(host, token, clientName);
})

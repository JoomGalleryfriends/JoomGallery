import { BasePlugin } from '@uppy/core';
import Translator from '@uppy/utils/lib/Translator';
import { Observable } from '@gullerya/object-observer';

export default class jgProcessor extends BasePlugin {
  /**
   * Constructor
   * Doc: https://uppy.io/docs/guides/building-plugins/#creating-a-plugin
   *
   * @param   {Object}   uppy      Uppy instance
   * @param   {Object}   opts      Options object passed in to the uppy.use()
   *
   * @returns {Viod}
   * @throws Error
   */
  constructor(uppy, opts) {
    super(uppy, opts);
    this.type     = 'saver';
    this.id       = this.opts.id || 'jgProcessor';
    this.title    = 'jgProcessor';
    this.formID   = this.opts.formID || 'adminForm';
    this.uploadID = this.opts.uploadID || '';

    // Bind this to plugin functions
    this.awaitSaveRequest = this.awaitSaveRequest.bind(this);
    this.startSaveRequest = this.startSaveRequest.bind(this);
    this.sendFetchRequest = this.sendFetchRequest.bind(this);
    this.prepareProcess   = this.prepareProcess.bind(this);
    this.handleError      = this.handleError.bind(this);
    this.setFileError     = this.setFileError.bind(this);
    this.setFileSuccess   = this.setFileSuccess.bind(this);
    this.addDebugBtn      = this.addDebugBtn.bind(this);
    this.addStateTxt      = this.addStateTxt.bind(this);
    this.addTitle         = this.addTitle.bind(this);
    this.idFromFilename   = this.idFromFilename.bind(this);

    // Define language strings
    this.defaultLocale = {
      strings: {
          savingImages: 'Saving image to database...',
          savingFailed: 'Saving failed',
          savingSuccessful: 'Saving successful',
      },
    };

    // Initialize other properties
    this.formData     = {};
    this.filecounters = {};

    // Initialize sema
    const { Sema } = require('async-sema');
    this.sema = new Sema(
      (this.opts.semaCalls || 1),
      {
        capacity: (this.opts.semaTokens  || 150)
      }
    );

    // Initialize an object collecting the finished files
    // This object is observed during postprocessing (awaitSaveRequest)
    this.finishedFiles = Observable.from({}, { async: true });

    this.i18nInit();
  }

  /**
   * Setter for options
   *
   * @param   {Object}   newOpts      Options object
   *
   * @returns {Viod}
   */
  setOptions (newOpts) {
    super.setOptions(newOpts);
  }

  /**
   * Read out the uuid from the upload URL
   *
   * @param   {String}   uploadURL     The URL
   *
   * @returns {String}   The uuid
   */
  resolveUuid (uploadURL) {
    let query = uploadURL.split('?')[1];
    let queryArray = query.split('&');

    for (let i = 0; i < queryArray.length; ++i) {
      if(queryArray[i].includes('uuid')) {
        return queryArray[i].replace('uuid=','');
      }
    }

    return '';
  }


  /**
   * Create a valid html id string from filename and uuid
   *
   * @param    {String}   file_id     The uploaded file id.
   * @param    {String}   uuid        The uppy upload id.
   *
   * @returns  {String}   The html id string
   */
  idFromFilename (file_id, uuid) {
    // Extract the part after the last slash
    const fileID = uuid + '_' + file_id.split('/').pop();

    // Replace invalid characters with underscores
    const sanitized = fileID.replace(/[^a-zA-Z0-9_-]/g, "_");

    return sanitized;
  }

  /**
   * Set an error for a specific file
   *
   * @param  {String}           error      Error message
   * @param  {object|Integer}   file       Object or ID of the uppy file
   * @param  {object}           response   Response object
   */
  setFileError (error, file, response) {
    let errorMsg = error || 'Unknown error';
    let fileID = (typeof file == 'number') ? file : file.id;

    // Add error to global uppy object
    this.uppy.setState({ error: errorMsg });

    // Add error to specific uppy file
    if (fileID in this.uppy.getState().files) {
      this.uppy.setFileState(fileID, {
        error: errorMsg,
        response: response,
      });
    }
  }

  /**
   * Set a success for a specific file
   *
   * @param  {object|Integer}   file       Object or ID of the uppy file
   * @param  {object}           response   Response object
   */
  setFileSuccess (file, response) {
    let fileID = (typeof file == 'number') ? file : file.id;

    // Add success to specific uppy file
    if (fileID in this.uppy.getState().files) {
      this.uppy.setFileState(fileID, {
        response: response,
      });
    }
  }

  /**
   * Add debug button to preview in dashboard
   *
   * @param  {Object}   file        The Uppy file that was uploaded.
   * @param  {String}   type        Button type. (success or danger)
   * @param  {String}   uuid        The uppy upload id.
   * @param  {String}   style       Class to add to the button. (optional)
   */
  addDebugBtn (file, type, uuid, style='') {
    // Create file_id
    const file_id = this.idFromFilename(file.id, uuid);

    // Create button element
    let btn = {
      'type' : type,
      'style' : style,
      'file_id' : file_id,
      'txt' : Joomla.Text._("COM_JOOMGALLERY_DEBUG_INFORMATION")
    }
    //let btn = '<button type="button" class="btn btn-'+type+' btn-sm '+style+'" data-bs-toggle="modal" data-bs-target="#modal'+file.uuid+'">'+Joomla.Text._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';

    // Add element FileInfo
    this.uppy.setFileState(file.id, {debugBtn: btn});
  }

  /**
   * Add state text to preview in dashboard
   *
   * @param  {Object}   file       The Uppy file that was uploaded.
   * @param  {String}   text       The text content to be added.
   */
  addStateTxt (file, text) {
    // Add element FileInfo
    this.uppy.setFileState(file.id, {statetxt: text});
  }

  /**
   * Add a new title to an uploaded file
   *
   * @param  {Object}   file       The Uppy file that was uploaded.
   * @param  {String}   title      The title to be added.
   */
  addTitle (file, title) {
    let newMeta = file.meta;

    // Adjust meta
    if(file.meta && file.meta.name) {
      file.meta.name = title;
    }

    // Add element FileInfo
    this.uppy.setFileState(file.id, {
      name: title,
      meta: file.meta,
    });
  }

  /**
   * Create the HTML string for a bootstrap modal
   *
   * @param    {Object}   file        The Uppy file that was uploaded.
   * @param    {String}   uuid        The uppy upload id.
   * @param    {Object}   response    The response of the ajax request to save the file
   *
   * @returns  {String}   The html string of the popup
   */
  createPopup (file, uuid, response) {
    // Create popup body
    let popupBody = '';

    if(Boolean(response.success) && Boolean(response.data) && Boolean(response.data.success)) {
      popupBody = Joomla.Text._('COM_JOOMGALLERY_SUCCESS_UPPY_UPLOAD').replace('{filename}', file.name);
    } else {
      popupBody = Joomla.Text._('COM_JOOMGALLERY_ERROR_UPPY_UPLOAD').replace('{filename}', file.name);
    }
    if(Boolean(response.message)) {
      popupBody = popupBody + '<br /><br />' + response.message;
    }
    if(Boolean(response.messages)) {
      if(Boolean(response.messages.notice)) {
        popupBody = popupBody + '<br /><br />' + response.messages.notice;
      }
      if(Boolean(response.messages.warning)) {
        popupBody = popupBody + '<br /><br />' + response.messages.warning;
      }
      if(Boolean(response.messages.error)) {
        popupBody = popupBody + '<br /><br />' + response.messages.error;
      }
    }
    if(Boolean(response.data) && Boolean(response.data.error)) {
      popupBody = popupBody + '<br /><br />' + response.data.error;
    }

    // Create file_id
    const file_id = this.idFromFilename(file.id, uuid);

    // Create popup
    let html =    '<div class="joomla-modal modal fade" id="modal'+file_id+'" tabindex="-1" aria-labelledby="modal'+file_id+'Label" aria-hidden="true">';
    html = html +   '<div class="modal-dialog modal-lg">';
    html = html +      '<div class="modal-content">';
    html = html +           '<div class="modal-header">';
    html = html +               '<h3 class="modal-title" id="modal'+file_id+'Label">'+Joomla.Text._('COM_JOOMGALLERY_DEBUG_INFORMATION')+'</h3>';
    html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="'+Joomla.Text._('JCLOSE')+'"></button>';
    html = html +           '</div>';
    html = html +           '<div class="modal-body">';
    html = html +               '<div id="'+file_id+'-ModalBody">'+popupBody+'</div>';
    html = html +           '</div>';
    html = html +           '<div class="modal-footer">';
    html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'+Joomla.Text._('JCLOSE')+'">'+Joomla.Text._('JCLOSE')+'</button>';
    html = html +           '</div>';
    html = html +      '</div>';
    html = html +   '</div>';
    html = html + '</div>';

    return html;
  }

  /**
   * Sends an asynchronous fetch request with form data from formID
   * for a corresponding file
   *
   * @param   {String}   tusID       ID of the tus connection (upload)
   * @param   {String}   fileID      ID of the corresponding file
   *
   * @returns {Object}   Result object
   *                     {success: bool, status: int, message: string, messages: array, data: object}
   *                      data: response data created by JSON.parse
   */
  async sendFetchRequest (tusID, fileID) {
    await this.sema.acquire();

    // initialize variable
    let res = {error: 'Nothing sent...'};

    try {
      // Catch form and data
      let formData = this.formData;
      formData.append('jform[uuid]', tusID);
      formData.append('jform[filecounter]', this.filecounters[fileID]);
      if(formData.get('jform[description]').trim().length === 0) {
        // Receive text content from editor
        // J4
        let txt = Joomla.editors.instances['jform_description'].getValue();
        // J5
        // let txt = Joomla.editors.get('jform_description').getValue();
        formData.set('jform[description]', txt);
      }
      let url = document.getElementById(this.formID).getAttribute('action');

      // Set request parameters
      let parameters = {
        method: 'POST',
        mode: 'same-origin',
        cache: 'default',
        redirect: 'follow',
        referrerPolicy: 'no-referrer-when-downgrade',
        body: formData,
      };

      // Perform the fetch request
      let response = await fetch(url, parameters);

      // Resolve promise as text string
      res = await response.text();

      if (!response.ok) {
        // Catch network error
        console.log(res);
        return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: res}};
      }

      if(res.startsWith('{"success"')) {
        // Response is of type json --> everything fine
        res = JSON.parse(res);
        res.status = response.status;
        res.data   = JSON.parse(res.data);
      } else if (res.includes('Fatal error')) {
        // PHP fatal error occurred
        res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: res}};
      } else {
        // error by php detected: json part with ...{"success":.... expected
        if (res.startsWith('<br />')) {
          // Response is not of type json --> probably some php warnings/notices
          let split = res.split('\n{"');
          let temp  = JSON.parse('{"'+split[1]);
          let data  = JSON.parse(temp.data);
          res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
        } else {
              res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: res}};
        }
      }

    } finally {
      this.sema.release();
    }

    return res;
  }

  /**
   * Uppy-Event: on('upload')
   * Fired when the upload starts.
   * Docs: https://uppy.io/docs/uppy/#upload-1
   *
   * @param   {Object}   data    Data object consists of `id` with upload ID and `fileIDs` array
   */
  prepareProcess (data) {
    // with file IDs in current upload
    console.log('Starting upload '+data.id+' for files '+data.fileIDs);

    // Check and validate the form
    let form = document.getElementById(this.formID);

    // When upload starts, save the data of the form
    this.formData = new FormData(form);

    // Get numbering start value
    let nmb_start = 0;
    if(document.getElementById('jform_nmb_start')) {
      nmb_start = parseInt(document.getElementById('jform_nmb_start').value);
    }

    // Add class to file to apply styles during saving process
    for (let i = 0; i < data.fileIDs.length; i++) {
      // // Add text uploading to file element
      let file = this.uppy.getFile(data.fileIDs[i]);
      this.addStateTxt(file, Joomla.Text._('COM_JOOMGALLERY_UPLOADING')+'...');

      // Store the class property filecounter
      this.filecounters[data.fileIDs[i]] = nmb_start+i;
    }
  }

  /**
   * Uppy-Event: on('upload-success')
   * Fired each time a single upload is completed.
   * Docs: https://uppy.io/docs/uppy/#upload-success
   *
   * @param   {UppyFile} file        The Uppy file that was uploaded.
   * @param   {Object}   response    An object with response data from the remote endpoint.
   */
  startSaveRequest (file, response) {
    if(this.uploadID == '' || this.uploadID != this.resolveUuid(response.uploadURL)) {
      this.uploadID = this.resolveUuid(response.uploadURL);
    }

    // File upload was successful
    console.log('Upload of '+file.name+' successful.');

    // Update progress
    this.uppy.emit('postprocess-progress', file, {
      mode: 'indeterminate',
      message: this.i18n('savingImages'),
    });

    // Variable to store the save state
    let successful = false;

    // Save the uploaded file to the database
    this.sendFetchRequest(this.uploadID, file.id).then(response => {
      if(response.success == false)  {
        // Ajax request failed
        this.uppy.log('[PostProcessor] Ajax request for file '+file.name+' failed.', 'error');
        console.log('[PostProcessor] Ajax request for file '+file.name+' failed.');
        console.log(response.message);
        console.log(response.messages);
        this.setFileError(Joomla.Text._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), file, response);

        // Add text saving to file element
        this.addStateTxt(file, 'Saving failed');

        // Add Button to upload form
        this.addDebugBtn(file, 'danger', this.uploadID);
      }
      else  {
        // Ajax request successful
        if(!response.data.success)
        {
          // Save record failed
          this.uppy.log('[PostProcessor] Save record to database of file '+file.name+' failed.', 'error');
          console.log('[PostProcessor] Save record to database of file '+file.name+' failed.');
          console.log(response.data.error);
          this.setFileError(Joomla.Text._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), file, response);

          // Add text saving to file element
          this.addStateTxt(file, 'Saving failed');

          // Add Button to upload form
          this.addDebugBtn(file, 'danger', this.uploadID);
        }
        else
        {
          // Save record successful
          this.uppy.log('[PostProcessor] Save record to database of file '+file.name+' successful.');
          console.log('[PostProcessor] Save record to database of file '+file.name+' successful.');
          this.setFileSuccess(file, response);

          // Change save state
          successful = true;

          // Add text saving to file element
          this.addStateTxt(file, 'Saving successful');

          // Exchange title of the upload file
          this.addTitle(file, response.data.record.title);

          // Add Button to upload form
          if(this.formData.get('jform[debug]') == 1) {
            this.addDebugBtn(file, 'success', this.uploadID);
            console.log(response.data.record);
          }
        }
      }

      // Add debug popup to file preview
      if(!successful || (successful && this.formData.get('jform[debug]') == 1)) {
        let div       = document.createElement('div');
        div.innerHTML = this.createPopup(file, this.uploadID, response);
        document.getElementById('popup-area').appendChild(div);

        // Create file_id
        const file_id = this.idFromFilename(file.id, this.uploadID);

        new bootstrap.Modal(document.getElementById('modal'+file_id));
      }

      // Add file ID to the observed object of finished files
      this.finishedFiles[file.id] = {success: successful, file: file};
    });
  }

  /**
   * Uppy-Event: on('upload-error')
   * Fired each time a single upload failed.
   * Docs: https://uppy.io/docs/uppy/#upload-error
   *
   * @param   {UppyFile} file        The Uppy file which didn’t upload.
   * @param   {Object}   error       The error object.
   * @param   {Object}   response    An object with response data from the remote endpoint.
   */
  handleError (file, error, response) {
    // file upload failed
    console.log('Upload of '+file.name+' failed.');

    // Add text saving to file element
    this.addStateTxt(file, 'Upload failed');

    // Add Button to upload form
    this.addDebugBtn(file, 'danger', this.uploadID);

    // Add Popup
    let temp_resp = {success: false};
    let div       = document.createElement('div');
    div.innerHTML = this.createPopup(file, this.uploadID, temp_resp);
    document.getElementById('popup-area').appendChild(div);

    // Create file_id
    const file_id = this.idFromFilename(file.id, this.uploadID);

    new bootstrap.Modal(document.getElementById('modal'+file_id));
  }

  /**
   * Uppy-PostProcessor
   * Postprocessing function which is hooked into the uppy pipeline
   * Doc: https://uppy.io/docs/guides/building-plugins/#upload-hooks
   *
   * @param   {Array}    fileIDs     List of file IDs that are being uploaded
   * @param   {String}   uploadID    ID of the current upload
   *
   * @returns {Promise}  Promise to signal completion
   */
  async awaitSaveRequest(fileIDs, uploadID) {
    // console.log('start observing...');

    const observeChanges = () => {
      return new Promise((resolve) => {
        let nmbFinished = 0;

        Observable.observe(this.finishedFiles, changes => {
          // Executed every time something changes in this.finishedFiles
          nmbFinished++;

          let c = 0;
          changes.forEach(change => {
            if(c === 0) {
              let file = this.uppy.getFile(change.value.file.id);
              this.uppy.emit('postprocess-complete', file);

              // console.log('new observed finished file:');
              // console.log(change.value.file.id);
            }
            c++;
          });

          if(nmbFinished >= fileIDs.length) {
            // Resolve the Promise when all observed changes are processed
            resolve();
          }
        });

        // Completion of files which are completed before observation starts
        for (let key in this.finishedFiles) {
          nmbFinished++;

          let file = this.uppy.getFile(this.finishedFiles[key].file.id);
          this.uppy.emit('postprocess-complete', file);

          // console.log('already finished files:');
          // console.log(this.finishedFiles[key].file.id);
        }

        if(nmbFinished >= fileIDs.length) {
          // Resolve the Promise when all observed changes are processed
          resolve();
        }
      });
    };
    return observeChanges().then(() => {console.log('[PostProcessor] All records have been saved. End of PostProcessing.');});
  }

  /**
   * Installation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#install
   *
   * @returns {Void}
   */
  install () {
    this.uppy.addPostProcessor(this.awaitSaveRequest);

    this.uppy.on('upload', this.prepareProcess);
    this.uppy.on('upload-success', this.startSaveRequest);
    this.uppy.on('upload-error', this.handleError);
  }

  /**
   * Deinstallation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#uninstall
   *
   * @returns {Void}
   */
  uninstall () {
    this.uppy.removePostProcessor(this.awaitSaveRequest);
  }
}

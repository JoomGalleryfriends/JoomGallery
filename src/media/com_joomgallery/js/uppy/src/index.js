// Script to handle tu uppy upload form
import Uppy from '@uppy/core';
import jgDashboard from './jgDashboard/index.js';
//import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';
import jgProcessor from './jgprocessor.js';

/**
 * Apply validity class to catid choices select field
 * 
 * @param   {Boolean}   ini      True to remove all validity classes
 * 
 * @returns {Boolean}   True if input field is not empty and valid
 */
function catidFieldValidity (ini = false) {
  let catid = document.getElementById('jform_catid');

  if(ini) {
    catid.parentElement.classList.remove('is-invalid');
    catid.parentElement.classList.remove('is-valid');
    catid.classList.remove('is-invalid');
    catid.classList.remove('is-valid');

    return false;
  }

  if(catid.checkValidity() && catid.value != '') {
    // is-valid
    catid.classList.remove('is-invalid');
    catid.classList.add('is-valid');

    return true;
  }
  else {
    // is-invalid
    catid.classList.remove('is-valid');
    catid.classList.add('is-invalid');

    return false;
  }
}

var callback = function() {
  // document ready function

  // Initialize the form
  document.getElementById('adminForm').classList.remove('was-validated');
  catidFieldValidity(true);
  
  let uppy = new Uppy({
    autoProceed: false,
    onBeforeUpload: (files) => {return onBeforeUpload(files);},
    restrictions: {
      maxFileSize: window.uppyVars.maxFileSize,
      allowedFileTypes: window.uppyVars.allowedTypes,
    }
  });

  if(uppy != null)
  {
    document.getElementById('drag-drop-area').innerHTML = '';
  }

  uppy.use(jgDashboard, {
    inline: true,
    target: window.uppyVars.uppyTarget,
    showProgressDetails: true,
    metaFields: [
      { id: 'jtitle', name: Joomla.JText._('JGLOBAL_TITLE'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_TITLE_HINT')},
      { id: 'jdescription', name: Joomla.JText._('JGLOBAL_DESCRIPTION'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT')},
      { id: 'jauthor', name: Joomla.JText._('JAUTHOR'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_AUTHOR_HINT')}
    ],
  });

  uppy.use(Tus, {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: window.uppyVars.uppyDelays,
    allowedMetaFields: null,
    limit: window.uppyVars.uppyLimit
  });

  uppy.use(jgProcessor, {
    formID: 'adminForm',
    semaCalls: window.uppyVars.semaCalls,
    semaTokens: window.uppyVars.semaTokens
  });

  /**
   * Function called before upload is initiated
   * Doc: https://uppy.io/docs/uppy/#onbeforeuploadfiles
   *
   * @param   {Array}    files      List of files that will be uploaded
   *
   * @returns {Boolean}  True to continue the upload, false to cancel it
   */
  function onBeforeUpload(files) {
    // Initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
    document.getElementById('system-message-container').innerHTML = '';
    catidFieldValidity(true);

    // Check and validate the form
    let form = document.getElementById('adminForm');
    
    if(!form.checkValidity() || !catidFieldValidity()) {
      // Form falidation failed
      // Cancel upload, render message
      Joomla.renderMessages({'error':[Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS')]});
      console.log(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS'));
      form.classList.add('was-validated');
      window.scrollTo(0, 0);

      return false;
    }
    else
    {
      // Form falidation successful
      // Start upload
      form.classList.add('was-validated');
      catidFieldValidity();
      window.scrollTo(0, 0);

      return true;
    }
  }

  uppy.on('complete', (result) => {
    // Re-initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
    document.getElementById('system-message-container').innerHTML = '';
    catidFieldValidity(true);
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}

// Selectors and settings used by this script
let typeSelector = 'data-type';
let formIdTmpl   = 'migrationForm';
let buttonTmpl   = 'migrationBtn';
let condTmpl     = 'startCond';
let step4Btn     = 'step4Btn';
let tryLimit     = 3;
let refresh      = false;

/**
 * Storage for migrateables
 * @var {Object}  migrateablesList
 */
var migrateablesList = {};

/**
 * Counter of how many times the same migration was tried to perform
 * @var {Integer}  tryCounter
 */
var tryCounter = 0;

/**
 * State. As long as this state is set to true, the migration will be
 * continued automatically regarding the pending queue in the migrateablesList.
 * @var {Boolean}  continueState
 */
var continueState = true;

/**
 * State. Set this state to true to stop automatic execution as soon as the next ajax respond comes back.
 * @var {Boolean}  forceStop
 */
var forceStop = false;

/**
 * State. Set this state to true if one migration is active.
 * @var {Boolean}  migrationActive
 */
var migrationActive = false;

/**
 * Log error messages from the session storage
 */
document.addEventListener('DOMContentLoaded', () => {
  let errorMessage = sessionStorage.getItem('migrationError');
  if (errorMessage) {
    console.error(errorMessage);
    sessionStorage.removeItem('migrationError');
  }
});

/**
 * Adds all completed migrateables to list
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let updateMigrateablesList = function() {
  let types = getTypes();

  // Loop through all migrateables
  Object.keys(types).forEach(type => {
    let formId = formIdTmpl + '-' + type;
    let form   = document.getElementById(formId);

    if(!form) {
      sessionStorage.setItem('migrationError', `(updateMigrateablesList) Migration form not found for type: ${type}`);
      location.reload();
      return;
    }

    let migrateable = atob(form.querySelector('[name="migrateable"]').value);
    migrateable = JSON.parse(migrateable);

    if(migrateable['completed'])
    {
      // Add migrateable in list
      migrateablesList[type] = migrateable;
    }
  });
}

/**
 * Submit the migration task by pressing the button
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let submitTask = function(event, element) {
  event.preventDefault();

  let type   = element.getAttribute(typeSelector);
  let formId = formIdTmpl + '-' + type;
  let task   = element.parentNode.querySelector('[name="task"]').value;

  if(tryCounter == 0 && refresh && migrationActive) {
    // There is already a migration running
    addLog(Joomla.JText._('COM_JOOMGALLERY_MIGRATION_ALREADY_RUNNING'), type, 'info');
    return;
  }

  // Set the migration state to true
  migrationActive = true;

  if(tryCounter == 0) {
    startTask(type, element);
  }

  tryCounter = tryCounter + 1;

  ajax(formId, task)
    .then(res => {
      // Handle the successful result here
      responseHandler(type, res);

      if(tryCounter >= tryLimit) {
        // We reached the limit of tries --> looks like we have a network problem
        updateMigrateables(type, {'success': false, 'message': Joomla.JText._('COM_JOOMGALLERY_ERROR_NETWORK_PROBLEM'), 'data': false});
        // Stop automatic execution and update GUI
        forceStop = true;
      }

      if(continueState && !forceStop) {
        // Kick off the next task
        submitTask(event, element);
      } else {
        // Stop automatic task execution and update GUI
        finishTask(type, element, formId);
      }
    })
    .catch(error => {
      // Handle any errors here
      addLog(error, type, 'error');

      // Set the migration state to true
      migrationActive = false;
    });
};

/**
 * Stop the migration task by pressing the button
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let stopTask = function(event, element) {
  event.preventDefault();

  let type     = element.getAttribute(typeSelector);
  let bar      = document.getElementById('progress-'+type);
  let startBtn = document.getElementById('migrationBtn-'+type);
  let stopBtn  = element;

  if(!bar) {
    sessionStorage.setItem('migrationError', `(stopTask) Progress bar not found for type: ${type}`);
    location.reload();
    return;
  }

  if(!startBtn) {
    sessionStorage.setItem('migrationError', `(stopTask) Start button not found for type: ${type}`);
    location.reload();
    return;
  }

  // Force automatic execution to stop
  forceStop = true;

  // Update progress bar
  bar.classList.remove('progress-bar-striped');
  bar.classList.remove('progress-bar-animated');

  // Enable start button
  startBtn.classList.remove('disabled');
  startBtn.removeAttribute('disabled');

  // Disable stop button
  stopBtn.classList.add('disabled');
  stopBtn.setAttribute('disabled', 'true');
}

/**
 * Manually set one record migration to true
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let repairTask = function(event, element) {
  event.preventDefault();

  // Get relevant elements
  let type      = element.getAttribute(typeSelector);
  let mig       = document.getElementById('migrationForm-'+type).querySelector('[name="migrateable"]');
  let inputType = document.getElementById('migrepairForm').querySelector('[name="type"]');
  let inputMig  = document.getElementById('migrepairForm').querySelector('[name="migrateable"]');

  // Fill input values
  inputType.value = type;
  inputMig.value  = mig.value;

  // Show modal
  let bsmodal = new bootstrap.Modal(document.getElementById('repair-modal-box'), {keyboard: false});
  bsmodal.show();
}

/**
 * Get an object with all available types
 *
 * @returns {Object}   Types object
 */
let getTypes = function() {
  let types_inputs = document.getElementsByName('type');
  let types = {};

  if(!types_inputs) {
    sessionStorage.setItem('migrationError', `(getTypes) Type elements not found.`);
    location.reload();
    return;
  }

  // Add all available migrateables to types object
  types_inputs.forEach((type) => {
    if(Boolean(type.value)) {
      types[type.value] = false;
    }
  });

  return types;
}

/**
 * Perform an ajax request in json format
 *
 * @param   {String}   formId   Id of the form element
 * @param   {String}   task     Name of the task
 *
 * @returns {Object}   Result object
 *          {success: true, status: 200, message: '', messages: {}, data: { { {success, data, continue, error, debug, warning} }}
 */
let ajax = async function(formId, task) {

  // Catch form and data
  let formData = new FormData(document.getElementById(formId));
  formData.append('format', 'json');

  if(task == 'migration.start') {
    formData.append('id', getNextMigrationID(formId));
  }

  // Set request parameters
  let parameters = {
    method: 'POST',
    mode: 'same-origin',
    cache: 'default',
    redirect: 'follow',
    referrerPolicy: 'no-referrer-when-downgrade',
    body: formData,
  };

  // Set the url
  let url = document.getElementById(formId).getAttribute('action');

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

/**
 * Search for the next migrateable id in the queue
 *
 * @param   {String}   formId   Id of the form element
 *
 * @returns {String}   Id of the database record to be migrated next
 */
let getNextMigrationID = function(formId) {
  let type = formId.replace(formIdTmpl + '-', '');
  let form = document.getElementById(formId);

  if(!form) {
    sessionStorage.setItem('migrationError', `(getNextMigrationID) Form not found with id: ${formId}`);
    location.reload();
    return;
  }

  // Get migrateables from form
  let migrateable = atob(form.querySelector('[name="migrateable"]').value);
  migrateable     = JSON.parse(migrateable);

  // Update/overwrite migrateables in list
  migrateablesList[type] = migrateable;

  // Loop through queue to get next migrateable to be performed
  for (let id of migrateable.queue) {
    if (!(id in migrateable.successful) && !(id in migrateable.failed)) {
      migrateablesList[type]['currentID'] = id;
      break;
    }
  }

  return migrateablesList[type]['currentID'];
}

/**
 * Handle migration response
 *
 * @param   {Object}   response   The response object in the form of
 *          {success: true, status: 200, message: '', messages: {}, data: { {success, data, continue, error, debug, warning} }}
 *
 * @returns void
 */
let responseHandler = function(type, response) {
  if(response.success == false)  {
    // Ajax request failed or server responded with error code
    addLog('Error in server response. We will try again. ('+tryCounter+'/'+tryLimit+')', type, 'info');
    addLog(response.message, type, 'error');
    addLog(response.messages, type, 'error');
    addLog(response.data.error, type, 'error');

    // Try again...
  }
  else  {
    // Ajax request successful
    if(!response.data.success)
    {
      // Migration failed
      addLog('[Migrator.js] Migration of '+type+' with id = '+migrateablesList[type]['currentID']+' failed.', type, 'error');
      logMessages(type, response.data);

      // Stop automatic continuation if requested from backend
      if(!response.data.continue || response.data.continue == null || response.data.continue == false) {
        console.log('Stop automatic continuation requested from backend');
        continueState = false;
      }

      // Update migrateables
      updateMigrateables(type, response.data);

      // Reset tryCounter if we are handling a different migrateable
      if(migrateablesList[type]['currentID'] != migrateablesList[type]['last']) {
        tryCounter = 0;
      }
    }
    else
    {
      // Save record successful
      logMessages(type, response.data);
      addLog('[Migrator.js] Migration of '+type+' with id = '+migrateablesList[type]['currentID']+'  successful.', type, 'success');

      // Stop automatic continuation if requested from backend
      if(!response.data.continue || response.data.continue == null || response.data.continue == false) {
        console.log('Stop automatic continuation requested from backend');
        continueState = false;
      }

      // Update migrateables
      updateMigrateables(type, response.data);

      // Reset tryCounter
      tryCounter = 0;
    }
  }
}

/**
 * Add a message to the logging output and the console
 *
 * @param   {Mixed}    msg        One or multiple messages to be added to the log
 * @param   {String}   type       The type defining the logging output to use
 * @param   {String}   msgType    The type of message (available: error, warning, success, info)
 * @param   {Boolean}  console    True to add the message also to the console
 * @param   {Boolean}  newLine    True to add the message on a new line
 * @param   {Integer}  marginTop  Number of how much margin you want on the top of the message
 *
 * @returns void
 */
let addLog = function(msg, type, msgType, console=false, newLine=true, marginTop=0) {
  if(!Boolean(msg) || msg == null || msg == '') {
    // Message is empty. Do nothing
    return;
  } else if(typeof msg === 'string') {
    // Your message is a simple string
    let tmp_msg = '';

    // Test if your string a json string
    try {
      tmp_msg = JSON.parse(msg);
    } catch (e) {
    }

    // Convert string to array
    if(tmp_msg !== '') {
      // remove object properties 'error' and 'code' if existent
      if('error' in tmp_msg) {
        delete tmp_msg.error;
      }
      if('code' in tmp_msg) {
        delete tmp_msg.code;
      }
      msg = Object.values(tmp_msg);
    } else {
      msg = [msg];
    }
  } else if(typeof msg === 'object') {
    // Your message is an object. Convert to array
    msg = Object.values(msg);
  }

  // Get logging output element
  let logOutput = document.getElementById('logOutput-'+type);

  // Loop through all messages
  msg.forEach((message, i) => {
    // Print in console
    if(console) {
      console.log(message);
    }

    // Create element
    let line = null;
    if(newLine) {
      line = document.createElement('p');
    } else {
      line = document.createElement('span');
    }

    // Top margin to element
    marginTop = parseInt(marginTop);
    if(marginTop > 0) {
      line.classList.add('mt-'+String(marginTop));
    }

    // Add text color
    line.classList.add('color-'+msgType);

    // Add message to element
    let msgType_txt = msgType.toLocaleUpperCase();
    line.textContent = '['+Joomla.JText._(msgType_txt)+']  '+String(message);

    // Print into logging output
    logOutput.appendChild(line);
  });
}

/**
 * Clear the logging output
 *
 * @param  {String}   type    The type defining the logging output to clear
 *
 * @returns void
 */
let clearLog = function(type) {
  // Get logging output element
  let logOutput = document.getElementById('logOutput-'+type);

  // clear
  logOutput.innerHTML = '';
}

/**
 * Output all available messages from the result object
 *
 * @param  {String}   type   The type defining the content type to be updated
 * @param  {Object}   res    The result object in the form of
 *           {success: bool, data: mixed, continue: bool, error: string|array, debug: string|array, warning: string|array}
 *
 * @returns void
 */
let logMessages = function(type, res) {
  // Available message types: error, debug, warning
  let available = ['error', 'debug', 'warning'];
  let msgTypes = {'error': 'error', 'debug': 'info', 'warning': 'warning'};

  available.forEach((value, index) => {
    if(!res[value] || !Boolean(res.data) || res.data == null) {
      return;
    }

    addLog(res[value], type, msgTypes[value]);
  });
}

/**
 * Update migrateable input field, progress bar and badges
 *
 * @param  {String}   type   The type defining the content type to be updated
 * @param  {Object}   res    The result object in the form of
 *           {success: bool, data: mixed, continue: bool, error: string|array, debug: string|array, warning: string|array}
 *
 * @returns void
 */
let updateMigrateables = function(type, res) {
  let formId = formIdTmpl + '-' + type;
  let form   = document.getElementById(formId);

  if(!res.success && (!Boolean(res.data) || res.data == null || res.data == '')) {
    // Migration failed, but no data available in result

    // Create result data based on input field
    let migrateable = atob(form.querySelector('[name="migrateable"]').value);
    res.data = JSON.parse(migrateable);

    // See: Joomgallery\Component\Joomgallery\Administrator\Model\MigrationModel::migrate
    // Remove migrated primary key from queue
    res.data.queue = res.data.queue.filter(function(e) { return e !== migrateablesList[type]['currentID'] })

    // Add migrated primary key to failed object
    res.data.failed[migrateablesList[type]['currentID']] = res.message;
  }

  if(!Boolean(res.data.progress) || res.data.progress == null || res.data.progress == '') {
    // Update progress if not delivered with result object
    let total    = res.data.queue.lenght + Object.keys(res.data.successful).length + Object.keys(res.data.failed).length;
    let finished = Object.keys(res.data.successful).length + Object.keys(res.data.failed).length;
    res.data.progress = Math.round((100 / total) * (finished));
  }

  // Get badges
  let queueBadge = document.getElementById('badgeQueue-'+type);
  let resBadge = document.getElementById('badgeSuccessful-'+type);
  if(!res.success) {
    resBadge = document.getElementById('badgeFailed-'+type);
  }

  // Update migrateable input field
  let field = form.querySelector('[name="migrateable"]');
  field.value = utf8_to_b64(JSON.stringify(res.data));

  // Update badges
  queueBadge.innerHTML = parseInt(queueBadge.innerHTML) - 1;
  resBadge.innerHTML   = parseInt(resBadge.innerHTML) + 1;

  // Update progress bar
  let bar = document.getElementById('progress-'+type);
  bar.setAttribute('aria-valuenow', res.data.progress);
  bar.style.width = res.data.progress + '%';
  bar.innerText = res.data.progress + '%';
}

/**
 * Update GUI to start migration
 *
 * @param  {String}      type    The type defining the content type to be updated
 * @param  {DOM Element} button  The button being pressed to start the task
 *
 * @returns void
 */
let startTask = function(type, button) {
  let startBtn = button;
  let bar      = document.getElementById('progress-'+type);
  let stopBtn  = document.getElementById('stopBtn-'+type);

  if(!bar) {
    sessionStorage.setItem('migrationError', `(startTask) Progress bar not found for type: ${type}`);
    location.reload();
    return;
  }

  if(!stopBtn) {
    sessionStorage.setItem('migrationError', `(startTask) Stop button not found for type: ${type}`);
    location.reload();
    return;
  }

  // Update progress bar
  bar.classList.add('progress-bar-striped');
  bar.classList.add('progress-bar-animated');

  // Disable start button
  startBtn.classList.add('disabled');
  startBtn.setAttribute('disabled', 'true');

  // Enable stop button
  stopBtn.classList.remove('disabled');
  stopBtn.removeAttribute('disabled');

  // Reinitialize variables
  tryCounter    = 0;
  continueState = true;
  forceStop     = false;
}

/**
 * Update GUI to end migration
 *
 * @param  {String}      type    The type defining the content type to be updated
 * @param  {DOM Element} button  The button being pressed to start the task
 * @param  {String}      formId  Id of the form element
 *
 * @returns void
 */
let finishTask = function(type, button, formId) {
  // Set the migration state to true
  migrationActive = false;

  // Get elements
  let startBtn   = button;
  let bar        = document.getElementById('progress-'+type);
  let stopBtn    = document.getElementById('stopBtn-'+type);

  if(!bar) {
    sessionStorage.setItem('migrationError', `(finishTask) Progress bar not found for type: ${type}`);
    location.reload();
    return;
  }

  if(!stopBtn) {
    sessionStorage.setItem('migrationError', `(finishTask) Stop button not found for type: ${type}`);
    location.reload();
    return;
  }

  // Update migrateablesList
  getNextMigrationID(formId);

  // Update progress bar
  bar.classList.remove('progress-bar-striped');
  bar.classList.remove('progress-bar-animated');

  // If migration is completed
  if(migrateablesList[type] && migrateablesList[type]['completed']) {
    if(refresh) {
      location.reload();
    } else {
      // Update start buttons
      updateStartBtns();
      // Update conditions texts
      updateConditionTxt();
      // Update step 4 button
      updateStep4Btn();
    }
  }

  // Enable start button
  if(!migrateablesList[type] || !migrateablesList[type]['completed']) {
    // Only enable start button if migration is not finished
    startBtn.classList.remove('disabled');
    startBtn.removeAttribute('disabled');
  }

  // Disable stop button
  stopBtn.classList.add('disabled');
  stopBtn.setAttribute('disabled', 'true');
}

/**
 * Update state of start conditions text color
 *
 * @returns void
 */
let updateConditionTxt = function() {
  let types  = getTypes();
  let errors = [];

  // Loop through all migrateables
  Object.keys(types).forEach(type => {
    let dependenciesElement = document.getElementById('is_dependent-' + type);
    if (!dependenciesElement) {
      errors.push(`(updateConditionTxt) Dependencies element not found for type: ${type}`);
    }

    let dependencies;
    try {
      dependencies = JSON.parse(dependenciesElement.innerHTML);
    } catch (e) {
      errors.push(`(updateConditionTxt) Invalid JSON in dependencies for type: ${type}`);
    }

    // Check if all dependencies are migrated
    Object.values(dependencies).forEach(dependency => {
      // Get condition html element
      let condition  = document.getElementById(condTmpl + '-' + type).querySelectorAll('[data-type="' + dependency + '"]')[0];
      if (!condition) {
        errors.push(`(updateConditionTxt) Condition element not found for type '${type}' and dependency '${dependency}'`);
      }

      if(migrateablesList[dependency] && migrateablesList[dependency]['completed']) {
        // fulfilled
        condition.classList.remove('pending');
        condition.classList.add('fulfilled');
      } else {
        // not fulfilled
        condition.classList.remove('fulfilled');
        condition.classList.add('pending');
      }
    });
  });

  // Handle errors after loop
  if (errors.length > 0) {
    sessionStorage.setItem('migrationErrors', JSON.stringify(errors));
    location.reload();
  }
}

/**
 * Update state of start buttons
 *
 * @returns void
 */
let updateStartBtns = function() {
  let types  = getTypes();
  let errors = [];

  // Loop through all migrateables
  Object.keys(types).forEach(type => {
    let dependenciesElement = document.getElementById('is_dependent-' + type);
    if (!dependenciesElement) {
      errors.push(`(updateStartBtns) Dependencies element not found for type: ${type}`);
    }

    let dependencies;
    try {
      dependencies = JSON.parse(dependenciesElement.innerHTML);
    } catch (e) {
      errors.push(`(updateStartBtns) Invalid JSON in dependencies for type: ${type}`);
    }

    // Check if all dependencies are migrated
    let dependenciesMigrated = true;
    Object.values(dependencies).forEach(dependency => {
      if (!migrateablesList[dependency] || !migrateablesList[dependency]['completed']) {
        dependenciesMigrated = false;
      }
    });

    // Get button
    let btn = document.getElementById(buttonTmpl + '-' + type);
    if (!btn) {
      errors.push(`(updateStartBtns) Start button not found for type: ${type}`);
    }

    if(dependenciesMigrated && (!migrateablesList[type] || !migrateablesList[type]['completed'])) {
      // Enable button
      btn.classList.remove('disabled');
      btn.removeAttribute('disabled');
    } else {
      // Disable button
      btn.classList.add('disabled');
      btn.setAttribute('disabled', 'true');
    }
  });

  // Handle errors after loop
  if (errors.length > 0) {
    sessionStorage.setItem('migrationErrors', JSON.stringify(errors));
    location.reload();
  }
}

/**
 * Update button to go to step 4
 *
 * @returns void
 */
let updateStep4Btn = function() {
  let types = getTypes();

  // Check if all migrateables are available and completed
  let totComplete = true;
  Object.keys(types).forEach(type => {
    if(Boolean(migrateablesList[type])) {
      if(!migrateablesList[type]['completed'])
      {
        // Migrateable not yet completed
        totComplete = false;
      }
    }
    else
    {
      // Migrateable does not yet exist. Thus not completed
      totComplete = false;
    }
  });

  if(totComplete) {
    // Get button
    let btn = document.getElementById(step4Btn);
    if (!btn) {
      sessionStorage.setItem('migrationError', `(updateStep4Btn) End Button not found`);
      location.reload();
      return;
    }

    // Enable step 4 button
    btn.classList.remove('disabled');
    btn.removeAttribute('disabled');
  }
}

let utf8_to_b64 = function(str) {
  return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
      return String.fromCharCode('0x' + p1);
  }));
}

/**
 * @package    com_joomgallery
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright  2008 - 2025 JoomGallery::ProjectTeam
 * @license    GNU General Public License version 3 or later
 */

import { Sema } from 'async-sema';

/**
 * How many tasks (workers) should be executed in parallel.
 */
let parallelLimit = 10;

/**
 * Set to true to stop automatic execution.
 * @var {Boolean}
 */
var forceStop = false;

/**
 * Stores whether the stop was explicitly triggered by the user (pause click).
 * @var {Boolean}
 */
var userPaused = false;

/**
 * Stores whether a task is already being actively executed.
 * @var {Boolean}
 */
var taskActive = false;

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.jg-run-instant-task').forEach(button => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();

      if (!taskActive) {
        const isNewRun = !forceStop;
        setPlayButtonState(button, 'pause');
        await runTask(event, button, isNewRun);
      } else {
        console.log('Task pause requested.');
        userPaused = true;
        forceStop = true;
        setPlayButtonState(button, 'play');
      }
    });
  });

  document.querySelectorAll('.jg-show-failed-items').forEach(link => {
    link.addEventListener('click', async (event) => {
      event.preventDefault();
      const taskId = link.dataset.taskId;
      const modalBody = document.getElementById('jg-failed-items-list');

      modalBody.innerHTML = '<p>Loading error list...</p>';

      try {
        const response = await getFailedItems(taskId);

        if (response.success && response.data) {
          if (response.data.items && response.data.items.length > 0) {
            let html = '<ul class="list-group">';
            response.data.items.forEach(item => {
              html += `<li class="list-group-item" data-item-id="${item.item_id || ''}"><strong>Item ${item.item_id || 'Unknown'}:</strong><br>${item.error_message || 'No error message'}</li>`;
            });
            html += '</ul>';
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<p>No failed items found.</p>';
          }
        } else {
          const errorMsg = response.data.error || response.message || 'Error loading data.';
          throw new Error(errorMsg);
        }
      } catch (error) {
        modalBody.innerHTML = `<p class="text-danger">An error occurred: ${error.message}</p>`;
      }
    });
  });

  document.querySelectorAll('[data-copy-failed-button]').forEach(button => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      const listContainer = document.getElementById('jg-failed-items-list');
      if (!listContainer) {
        return;
      }

      const items = listContainer.querySelectorAll('li[data-item-id]');
      const itemIds = Array.from(items)
        .map(item => item.dataset.itemId)
        .filter(id => id)
        .join(',');

      if (itemIds) {
        try {
          await navigator.clipboard.writeText(itemIds);
          const originalTitle = button.getAttribute('title');
          const icon = button.querySelector('span.fa');
          button.setAttribute('title', 'Copied!');
          if (icon) {
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check');
          }
          setTimeout(() => {
            button.setAttribute('title', originalTitle);
            if (icon) {
              icon.classList.remove('fa-check');
              icon.classList.add('fa-copy');
            }
          }, 2000);
        } catch (err) {
          console.error('Failed to copy item IDs: ', err);
          alert('Failed to copy IDs to clipboard.');
        }
      }
    });
  });
});

/**
 * Sets the play/pause button to a specific state.
 *
 * @param {Element} button The button element
 * @param {String} state    'play' (shows play icon) or 'pause' (shows pause icon)
 */
let setPlayButtonState = function(button, state) {
  if (!button) return;
  const playIcon = button.querySelector('span.fa');
  if (!playIcon) return;

  if (state === 'pause') {
    playIcon.classList.remove('fa-play');
    playIcon.classList.add('fa-pause');
  } else {
    playIcon.classList.remove('fa-pause');
    playIcon.classList.add('fa-play');
  }
}

/**
 * A "worker loop" that continuously requests work.
 *
 * @param {int} workerId     Just for info
 * @param {String} taskId    The ID of the main task
 * @param {Sema} sema        The semaphore instance
 * @param {Element} logContainer The log element
 * @param {function} updateCounters Callback to update counters in runTask
 */
async function runWorkerLoop(workerId, taskId, sema, logContainer, updateCounters) {
  while (!forceStop) {
    const result = await processItem(taskId, sema, logContainer);

    if (result.status === 'success') {
      updateCounters('success', result.itemId);
    } else if (result.status === 'failed') {
      updateCounters('failed', result.itemId);
    } else if (result.status === 'no_work') {
      forceStop = true; // Stops all other workers
      break;
    } else if (result.status === 'network_error') {
      break;
    }
  }
}

/**
 * Processes ONE item. Fetches the job atomically from the backend.
 *
 * @param   {String}   taskId         The ID of the main task
 * @param   {Sema}     sema           The semaphore instance
 * @param   {Element}  logContainer   The log element
 * @returns {Object}   A status object
 */
async function processItem(taskId, sema, logContainer) {
  await sema.acquire();
  let itemId = null;

  try {
    const response = await ajax(taskId);

    if (response.success && response.data.success) {
      itemId = response.data.item_id;

      if (itemId === null) {
        addLog('No further items found. Worker finished.', logContainer, 'info');
        return { status: 'no_work' };
      }

      return { status: 'success', itemId: itemId };
    } else {
      itemId = response.data.item_id || 'Unknown';
      const errorMsg = response.data.error || response.message || 'Server reported an error';
      addLog(`Processing of item ${itemId} failed. Error: ${errorMsg}`, logContainer, 'error');
      return { status: 'failed', itemId: itemId };
    }
  } catch (error) {
    addLog(`Network/AJAX Error: ${error.message}`, logContainer, 'error');
    return { status: 'network_error' };
  } finally {
    sema.release();
  }
}


/**
 * Starts processing the queue for a specific task.
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   The clicked button
 * @param {Boolean} isNewRun  True if the log should be cleared
 */
async function runTask(event, element, isNewRun = false) {
  if (taskActive) {
    alert('Another task is already running.');
    return;
  }

  taskActive = true;
  forceStop = false;
  userPaused = false;

  const taskId = element.dataset.id;
  const logContainer = document.getElementById('jg-modal-log-output');
  const workerCount = element.dataset.limit || parallelLimit;

  if (isNewRun) {
    clearLog(logContainer);
  }
  addLog('Task starting...', logContainer, 'info');
  startTaskUI(taskId);

  let successCount = parseInt(document.getElementById(`count-success-${taskId}`).textContent, 10) || 0;
  let failedCount = parseInt(document.getElementById(`count-failed-${taskId}`).textContent, 10) || 0;
  let pendingCount = parseInt(document.getElementById(`count-pending-${taskId}`).textContent, 10) || 0;
  const totalItems = successCount + failedCount + pendingCount;

  if (totalItems === 0 || (pendingCount === 0 && isNewRun)) {
    if (totalItems === 0) {
      addLog('Queue is empty. (No items found)', logContainer, 'info');
    } else {
      addLog('Queue is already finished. (No "Pending" items)', logContainer, 'info');
    }
    taskActive = false;
    finishTaskUI(taskId);
    setPlayButtonState(element, 'play');
    return;
  }

  addLog(`Starting ${workerCount} workers for ${pendingCount} remaining items...`, logContainer, 'info');

  const sema = new Sema(workerCount);

  const updateCounters = (status, itemId) => {
    if (status === 'success') {
      successCount++;
    } else if (status === 'failed') {
      failedCount++;
    }
    if (pendingCount > 0) {
      pendingCount--;
    }
    updateTaskProgress(taskId, totalItems, successCount, failedCount, pendingCount);
  };

  updateTaskProgress(taskId, totalItems, successCount, failedCount, pendingCount);

  const promises = [];
  for (let i = 0; i < workerCount; i++) {
    promises.push(runWorkerLoop(i, taskId, sema, logContainer, updateCounters));
  }

  await Promise.allSettled(promises);

  // If not stopped by user (pause), clean up and reload
  if (!userPaused) {
    addLog('Cleaning up and reloading...', logContainer, 'info');
    const url = new URL(window.location.href);

    try {
      const res = await cleanupTask(taskId);
      const json = await res.json();

      if (json.success) {
        if (json.data && json.data.deleted === false) {
          console.warn('Task was not deleted:', json.data.reason);
        } else {
          url.searchParams.delete('newTaskId');
          window.location.replace(url.toString());
        }
      } else {
        alert('System error during cleanup: ' + json.message);
      }
    } catch (error) {
      console.error(error);
    } finally {
      finishTaskUI(taskId);
    }

    return;
  }

  addLog('Task processing paused.', logContainer, 'info');
  taskActive = false;
  finishTaskUI(taskId);
}

/**
 * Executes an Ajax request to process a single queue item.
 *
 * @param   {String}   taskId   The ID of the main task
 * @returns {Object}   The response object
 */
let ajax = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.runTask'); // Calls TaskController::runTask()
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  let parameters = { method: 'POST', body: formData };
  let url = document.getElementById('adminForm').getAttribute('action');

  let response = await fetch(url, parameters);
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  }

  if(txt.startsWith('{"success"')) {
    res = JSON.parse(txt);
    res.status = response.status;
    if (res.data) {
      try {
        res.data = JSON.parse(res.data);
      } catch (e) { /* is ok */ }
    }
  } else if (txt.includes('Fatal error')) {
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    let split = txt.split('\n{"');
    if (split.length > 1) {
      let temp  = JSON.parse('{"'+split[1]);
      let data  = JSON.parse(temp.data);
      res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
    } else {
      res = {success: false, status: response.status, message: 'Unknown response from server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Executes an Ajax request to get the list of failed items.
 *
 * @param   {String}   taskId   The ID of the main task
 * @returns {Object}   The response object
 */
let getFailedItems = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.getFailedItems'); // Calls TaskController::getFailedItems()
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  let parameters = { method: 'POST', body: formData };
  let url = document.getElementById('adminForm').getAttribute('action');

  let response = await fetch(url, parameters);
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  }

  if(txt.startsWith('{"success"')) {
    res = JSON.parse(txt);
    res.status = response.status;
    if (res.data) {
      try {
        res.data = JSON.parse(res.data);
      } catch (e) { }
    }
  } else if (txt.includes('Fatal error')) {
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    let split = txt.split('\n{"');
    if (split.length > 1) {
      let temp  = JSON.parse('{"'+split[1]);
      let data  = JSON.parse(temp.data);
      res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
    } else {
      res = {success: false, status: response.status, message: 'Unknown response from server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Adds a message to the log window.
 *
 * @param   {String}   msg             The message
 * @param   {Element}  logContainer    The DOM element for the log (in the modal)
 * @param   {String}   msgType         Type: 'error', 'warning', 'success', 'info'
 */
let addLog = function(msg, logContainer, msgType) {
  if (!msg || !logContainer) {
    return;
  }
  let line = document.createElement('p');
  const colorMap = {
    'error': 'text-danger',
    'warning': 'text-warning',
    'success': 'text-success',
    'info': 'text-muted'
  };
  line.className = colorMap[msgType] || 'text-dark';
  let msgTypeText = msgType.toLocaleUpperCase();
  line.textContent = `[${msgTypeText}] ${String(msg)}`;
  logContainer.appendChild(line);
  logContainer.scrollTop = logContainer.scrollHeight;
}

/**
 * Clears the log window.
 *
 * @param {Element} logContainer The DOM element for the log (in the modal)
 */
let clearLog = function(logContainer) {
  if (logContainer) {
    logContainer.innerHTML = '';
  }
}

/**
 * Updates the counters and the progress bar for a task.
 *
 * @param {String} taskId        The ID of the task
 * @param {int}    totalItems    Total number of items
 * @param {int}    successCount  Number of successful items
 * @param {int}    failedCount   Number of failed items
 * @param {int}    pendingCount  The number of remaining items
 */
let updateTaskProgress = function(taskId, totalItems, successCount, failedCount, pendingCount) {
  totalItems = totalItems || 0;
  successCount = successCount || 0;
  failedCount = failedCount || 0;
  pendingCount = (pendingCount < 0) ? 0 : pendingCount;

  let processedCount = successCount + failedCount;
  let progress = (totalItems > 0) ? Math.round((processedCount / totalItems) * 100) : 0;

  document.getElementById(`count-pending-${taskId}`).textContent = pendingCount;
  document.getElementById(`count-success-${taskId}`).textContent = successCount;
  document.getElementById(`count-failed-${taskId}`).textContent = failedCount;

  let bar = document.getElementById(`progress-${taskId}`);
  bar.style.width = progress + '%';
  bar.setAttribute('aria-valuenow', progress);
}

/**
 * Updates the UI when a task starts (only progress bar).
 *
 * @param {String} taskId The ID of the task
 */
let startTaskUI = function(taskId) {
  let bar = document.getElementById(`progress-${taskId}`);
  if (bar) {
    bar.classList.add('progress-bar-striped', 'progress-bar-animated');
  }
}

/**
 * Updates the UI when a task ends (progress bar and button).
 *
 * @param {String} taskId The ID of the task
 */
let finishTaskUI = function(taskId) {
  let startBtn = document.querySelector(`.jg-run-instant-task[data-id="${taskId}"]`);
  let bar = document.getElementById(`progress-${taskId}`);

  if (bar) {
    bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
  }
  if (startBtn) {
    setPlayButtonState(startBtn, 'play');
  }
}

/**
 * Calls the cleanup endpoint.
 *
 * @param {String} taskId The ID of the task
 */
let cleanupTask = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));
  formData.append('format', 'json');
  formData.append('task', 'task.cleanupTask');
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  return fetch(document.getElementById('adminForm').getAttribute('action'), {
    method: 'POST',
    body: formData
  });
}

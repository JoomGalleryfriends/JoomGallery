/**
 * @package    com_joomgallery
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright  2008 - 2025 JoomGallery::ProjectTeam
 * @license    GNU General Public License version 3 or later
 */

import { Sema } from 'async-sema';

/**
 * Wie viele Tasks (Worker) parallel ausgeführt werden sollen.
 */
let parallelLimit = 10;

/**
 * Auf true setzen, um die automatische Ausführung zu stoppen.
 * @var {Boolean}
 */
var forceStop = false;

/**
 * Speichert, ob bereits ein Task aktiv ausgeführt wird.
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

      modalBody.innerHTML = '<p>Lade Fehlerliste...</p>';

      try {
        const response = await getFailedItems(taskId);

        if (response.success && response.data) {
          if (response.data.items && response.data.items.length > 0) {
            let html = '<ul class="list-group">';
            response.data.items.forEach(item => {
              html += `<li class="list-group-item"><strong>Item ${item.item_id || 'Unbekannt'}:</strong><br>${item.error_message || 'Keine Fehlermeldung'}</li>`;
            });
            html += '</ul>';
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<p>Keine fehlgeschlagenen Items gefunden.</p>';
          }
        } else {
          const errorMsg = response.data.error || response.message || 'Fehler beim Laden der Daten.';
          throw new Error(errorMsg);
        }
      } catch (error) {
        modalBody.innerHTML = `<p class="text-danger">Ein Fehler ist aufgetreten: ${error.message}</p>`;
      }
    });
  });
});

/**
 * Setzt den Play/Pause-Button auf einen bestimmten Status.
 *
 * @param {Element} button Das Button-Element
 * @param {String} state    'play' (zeigt Play-Icon) oder 'pause' (zeigt Pause-Icon)
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
 * Eine "Worker-Schleife", die kontinuierlich Arbeit anfordert.
 *
 * @param {int} workerId     Nur zur Info
 * @param {String} taskId    Die ID des Haupt-Tasks
 * @param {Sema} sema        Die Semaphore-Instanz
 * @param {Element} logContainer Das Log-Element
 * @param {function} updateCounters Callback, um die Zähler in runTask zu aktualisieren
 */
async function runWorkerLoop(workerId, taskId, sema, logContainer, updateCounters) {
  while (!forceStop) {
    const result = await processItem(taskId, sema, logContainer);

    if (result.status === 'success') {
      updateCounters('success', result.itemId);
    } else if (result.status === 'failed') {
      updateCounters('failed', result.itemId);
    } else if (result.status === 'no_work') {
      forceStop = true; // Stoppt alle anderen Worker
      break;
    } else if (result.status === 'network_error') {
      break;
    }
  }
}

/**
 * Verarbeitet EIN Item. Holt sich den Job atomar vom Backend.
 *
 * @param   {String}   taskId         Die ID des Haupt-Tasks
 * @param   {Sema}     sema           Die Semaphore-Instanz
 * @param   {Element}  logContainer   Das Log-Element
 * @returns {Object}   Ein Status-Objekt
 */
async function processItem(taskId, sema, logContainer) {
  await sema.acquire();
  let itemId = null;

  try {
    const response = await ajax(taskId);

    if (response.success && response.data.success) {
      itemId = response.data.item_id;

      if (itemId === null) {
        addLog('Keine weiteren Items gefunden. Worker beendet.', logContainer, 'info');
        return { status: 'no_work' };
      }

      return { status: 'success', itemId: itemId };
    } else {
      itemId = response.data.item_id || 'Unbekannt';
      const errorMsg = response.data.error || response.message || 'Server meldete einen Fehler';
      addLog(`Verarbeitung von Item ${itemId} fehlgeschlagen. Fehler: ${errorMsg}`, logContainer, 'error');
      return { status: 'failed', itemId: itemId };
    }
  } catch (error) {
    addLog(`Netzwerk/AJAX Fehler: ${error.message}`, logContainer, 'error');
    return { status: 'network_error' };
  } finally {
    sema.release();
  }
}


/**
 * Startet die Abarbeitung der Queue für einen bestimmten Task.
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   Der geklickte Button
 * @param {Boolean} isNewRun  True, wenn das Log geleert werden soll
 */
async function runTask(event, element, isNewRun = false) {
  if (taskActive) {
    alert('Ein anderer Task wird bereits ausgeführt.');
    return;
  }

  taskActive = true;
  forceStop = false;

  const taskId = element.dataset.id;
  const logContainer = document.getElementById('jg-modal-log-output');
  const workerCount = element.dataset.limit || parallelLimit;

  if (isNewRun) {
    clearLog(logContainer);
  }
  addLog('Task wird gestartet...', logContainer, 'info');
  startTaskUI(taskId);

  let successCount = parseInt(document.getElementById(`count-success-${taskId}`).textContent, 10) || 0;
  let failedCount = parseInt(document.getElementById(`count-failed-${taskId}`).textContent, 10) || 0;
  let pendingCount = parseInt(document.getElementById(`count-pending-${taskId}`).textContent, 10) || 0;
  const totalItems = successCount + failedCount + pendingCount;

  if (totalItems === 0 || (pendingCount === 0 && isNewRun)) {
    if (totalItems === 0) {
      addLog('Queue ist leer. (Keine Items gefunden)', logContainer, 'info');
    } else {
      addLog('Queue ist bereits abgeschlossen. (Keine "Pending" Items)', logContainer, 'info');
    }
    taskActive = false;
    finishTaskUI(taskId);
    setPlayButtonState(element, 'play');
    return;
  }

  addLog(`Starte ${workerCount} Worker für ${pendingCount} verbleibende Items...`, logContainer, 'info');

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

  addLog('Task-Abarbeitung abgeschlossen.', logContainer, 'info');
  taskActive = false;
  finishTaskUI(taskId);
}

/**
 * Führt einen Ajax-Request aus, um ein einzelnes Queue-Item zu verarbeiten.
 *
 * @param   {String}   taskId   Die ID des Haupt-Tasks
 * @returns {Object}   Das Antwort-Objekt
 */
let ajax = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.runTask'); // Ruft TaskController::runTask()
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
      } catch (e) { /* ist ok */ }
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
      res = {success: false, status: response.status, message: 'Unbekannte Antwort vom Server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Führt einen Ajax-Request aus, um die Liste der fehlgeschlagenen Items zu holen.
 *
 * @param   {String}   taskId   Die ID des Haupt-Tasks
 * @returns {Object}   Das Antwort-Objekt
 */
let getFailedItems = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.getFailedItems'); // Ruft TaskController::getFailedItems()
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
      res = {success: false, status: response.status, message: 'Unbekannte Antwort vom Server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Fügt eine Nachricht zum Log-Fenster hinzu.
 *
 * @param   {String}   msg             Die Nachricht
 * @param   {Element}  logContainer    Das DOM-Element für das Log (im Modal)
 * @param   {String}   msgType         Typ: 'error', 'warning', 'success', 'info'
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
 * Leert das Log-Fenster.
 *
 * @param {Element} logContainer Das DOM-Element für das Log (im Modal)
 */
let clearLog = function(logContainer) {
  if (logContainer) {
    logContainer.innerHTML = '';
  }
}

/**
 * Aktualisiert die Zähler und den Fortschrittsbalken für einen Task.
 *
 * @param {String} taskId        Die ID des Tasks
 * @param {int}    totalItems    Gesamtzahl der Items
 * @param {int}    successCount  Anzahl erfolgreicher Items
 * @param {int}    failedCount   Anzahl fehlgeschlagener Items
 * @param {int}    pendingCount  Die Anzahl der verbleibenden Items
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
 * Aktualisiert die UI, wenn ein Task startet (nur Ladebalken).
 *
 * @param {String} taskId Die ID des Tasks
 */
let startTaskUI = function(taskId) {
  let bar = document.getElementById(`progress-${taskId}`);
  if (bar) {
    bar.classList.add('progress-bar-striped', 'progress-bar-animated');
  }
}

/**
 * Aktualisiert die UI, wenn ein Task endet (Ladebalken und Button).
 *
 * @param {String} taskId Die ID des Tasks
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
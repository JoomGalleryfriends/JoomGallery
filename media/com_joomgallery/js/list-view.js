/*
 * Apply the predefined action for the current element
 *
 * @param {Event} event
 */
function gridItemAction(event) {
  let item = event.target;
  if (item.nodeName === 'SPAN' && ['A', 'BUTTON'].includes(item.parentNode.nodeName)) {
    item = item.parentNode;
  }
  if (item.nodeName === 'A') {
    event.preventDefault();
  }
  if (item.hasAttribute('disabled') || !item.hasAttribute('data-item-task')) {
    return;
  }
  const {
    itemId
  } = item.dataset;
  const {
    itemTask
  } = item.dataset;
  const {
    itemFormId
  } = item.dataset;

  let parts = [];
  if(itemTask && itemTask.includes('.')) {
    parts = itemTask.split('.', 2);
  }
  if (itemFormId) {
    //
    let form = document.getElementById(itemFormId);
    form.action = exchangeGETvar(form.action, 'view', 'controller='+parts[0]);
    form.baseURI = form.action;

    Joomla.listItemTask(itemId, itemTask, itemFormId);
  } else {
    //
    let form = document.getElementById('adminForm');
    form.action = exchangeGETvar(form.action, 'view', 'controller='+parts[0]);
    form.baseURI = form.action;

    Joomla.listItemTask(itemId, itemTask);
  }
}

/*
 * Apply the delete action for the current element
 *
 * @param {Event} event
 */
function gridItemActionDelete(event) {
  let item = event.target;
  if (item.nodeName === 'SPAN' && ['A', 'BUTTON'].includes(item.parentNode.nodeName)) {
    item = item.parentNode;
  }
  if (item.nodeName === 'A') {
    event.preventDefault();
  }
  if (item.hasAttribute('disabled') || !item.hasAttribute('data-item-task')) {
    return;
  }
  const {
    itemId
  } = item.dataset;
  const {
    itemTask
  } = item.dataset;
  const {
    itemFormId
  } = item.dataset;

  if(!confirm(item.getAttribute('data-item-confirm'))) {
    event.preventDefault();
    return false;
  }

  let parts = [];
  if(itemTask && itemTask.includes('.')) {
    parts = itemTask.split('.', 2);
  }
  if (itemFormId) {
    let form = document.getElementById(itemFormId);
    form.action = exchangeGETvar(form.action, 'view', 'controller='+parts[0]);
    form.baseURI = form.action;

    Joomla.listItemTask(itemId, itemTask, itemFormId);
  } else {
    let form = document.getElementById('adminForm');
    form.action = exchangeGETvar(form.action, 'view', 'controller='+parts[0]);
    form.baseURI = form.action;

    Joomla.listItemTask(itemId, itemTask);
  }  
}

/*
 * Apply the transition state for the current element
 *
 * @param {Event} event
 */
function gridTransitionItemAction(event) {
  const item = event.target;
  if (item.nodeName !== 'SELECT' || item.hasAttribute('disabled')) {
    return;
  }
  const {
    itemId
  } = item.dataset;
  const {
    itemTask
  } = item.dataset;
  const {
    itemFormId
  } = item.dataset;
  item.form.transition_id.value = item.value;
  if (itemFormId) {
    Joomla.listItemTask(itemId, itemTask, itemFormId);
  } else {
    Joomla.listItemTask(itemId, itemTask);
  }
}

/*
 * Apply the transition state for the current element
 *
 * @param {Event} event
 */
function gridTransitionButtonAction(event) {
  let item = event.target;
  if (item.nodeName === 'SPAN' && item.parentNode.nodeName === 'BUTTON') {
    item = item.parentNode;
  }
  if (item.hasAttribute('disabled')) {
    return;
  }
  Joomla.toggleAllNextElements(item, 'd-none');
}

/*
 * Switch the check state for the current element
 *
 * @param {Event} event
 */
function applyIsChecked(event) {
  const item = event.target;
  const itemFormId = item.dataset.itemFormId || '';
  if (itemFormId) {
    Joomla.isChecked(item.checked, itemFormId);
  } else {
    Joomla.isChecked(item.checked);
  }
}

/*
 * 
 * Exchange a specific GET variable in a provided URL
 *
 * @param String url    The url
 * @param String key    The key of the variable to exchange
 * @param String value  The new value in the form 'key=value'
 */
function exchangeGETvar(url, key, value) {
  let parts = url.split('&');

  parts.forEach(function callback(element, i) {
    if(element.includes(key)) {
      parts[i] = value;
    }
  });

  url = parts.join('&');
  return url;
}

/*
 * Set up an interactive list elements
 *
 * @param {Event} event
 */
const setup = ({
  target
}) => {
  target.querySelectorAll('.js-grid-item-check-all').forEach(element => element.addEventListener('click', event => Joomla.checkAll(event.target)));
  target.querySelectorAll('.js-grid-item-is-checked').forEach(element => element.addEventListener('click', applyIsChecked));
  target.querySelectorAll('.js-grid-item-action').forEach(element => element.addEventListener('click', gridItemAction));
  target.querySelectorAll('.js-grid-item-delete').forEach(element => element.addEventListener('click', gridItemActionDelete));
  target.querySelectorAll('.js-grid-item-transition-action').forEach(element => element.addEventListener('change', gridTransitionItemAction));
  target.querySelectorAll('.js-grid-button-transition-action').forEach(element => element.addEventListener('click', gridTransitionButtonAction));
};
setup({
  target: document
});
document.addEventListener('joomla:updated', setup);

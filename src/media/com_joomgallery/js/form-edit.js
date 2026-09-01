/**
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
((document, submitForm) => {

  // Selectors used by this script
  const buttonDataSelector = 'data-submit-task';
  const formId = 'adminForm';
  const typeId = 'itemType';

  /**
   * Submit the task
   * @param task
   */
  const submitTask = task => {
    const form = document.getElementById(formId);
    const type = document.getElementById(typeId).value;
    const btn = document.querySelector(`[${buttonDataSelector}="${task}"]`);
    if (task === type+'.cancel') {
      submitForm(task, form);
    }
    if (btn.parentElement.getAttribute('confirm-message')) {
      if(confirm(btn.parentElement.getAttribute('confirm-message')) && document.formvalidator.isValid(form)) {
        submitForm(task, form);
      }
    } else if (document.formvalidator.isValid(form)) {
      submitForm(task, form);
    }
  };

  // Register events
  document.addEventListener('DOMContentLoaded', () => {
    const buttons = [].slice.call(document.querySelectorAll(`[${buttonDataSelector}]`));
    buttons.forEach(button => {
      button.addEventListener('click', e => {
        e.preventDefault();
        const task = e.target.getAttribute(buttonDataSelector);
        submitTask(task);
      });
    });
  });
})(document, Joomla.submitform);

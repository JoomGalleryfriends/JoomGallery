/**
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
((document, submitForm) => {

  // Selectors used by this script
  const buttonDataSelector = 'data-submit-task';
  const formId = 'adminForm';
  const containerId = 'formInputContainer';

  /**
   * Submit the task
   * @param task
   */
  const submitTask = task => {
    const form = document.getElementById(formId);
    const container = document.getElementById(containerId);

    // add task input element
    let input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'task';
    input.value = task;
    container.appendChild(input);

    // submit form
    form.submit();
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

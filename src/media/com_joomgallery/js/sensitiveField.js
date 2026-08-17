// Scripts for activating sensitive buttons in forms

var enableEditing = function(event, element) {
  event.preventDefault();

  let input = element.nextSibling;

  if(input.classList[0] != "sensitive-input") {
    input = input.nextSibling;
  }

  if(input.classList[0] == "sensitive-input") {
    if(input.disabled) {
      if (confirm(Joomla.JText._('COM_JOOMGALLERY_CONFIG_ALERT_ENABLE_SENSITIVE_FIELD'))) {
        input.disabled = false;
      }
    }
  }
}

// taggingtoolbar.js
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modal-tagging');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    if (!trigger) return;

    // Only for your specific toolbar button
    if (!trigger.classList.contains('button-tagging')) return;

    // Take the current URL (in your case from button.value)
    const raw = trigger.value;
    const url = new URL(raw, window.location.origin);

    // Get selected IDs from the list
    const selected = [...document.querySelectorAll('input[name="cid[]"]:checked')].map(x => x.value);
    url.searchParams.set("cid", selected.join(","));

    // Write the url back to the button
    const newUrl = url.toString();
    trigger.value = newUrl;

    // Make the iframe to load the new URL
    const iframe = modal.querySelector('iframe');
    if (iframe) {
      iframe.src = newUrl;
    }
  });
});

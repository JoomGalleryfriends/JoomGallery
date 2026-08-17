// Scripts for buttons in the categories view

var callback = function(){
  // document ready function;

  // Detect if delete button was clicked
  var delBtn_clicked = false;
  document.querySelector("#toolbar-delete").addEventListener("click", function()
  {
    delBtn_clicked = true;
  });

  // Detect if form is submitted
  document.querySelector("#adminForm").addEventListener("submit", function(event)
  {
    if(delBtn_clicked)
    {
      event.preventDefault();

      document.querySelector("#del_force").value = "1";
      this.removeEventListener("submit", arguments.callee, false);
      this.submit();
    }
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}

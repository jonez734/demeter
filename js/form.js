var be = getbbsengine();

function handleform()
{
  var form = $("form");

  if (typeof form === "object")
  {
    $("form input[name='pageprotocol']").val("enhanced");
    $("body").on("submit", "form", function (event) {
      console.log("form.js: submit button clicked");
      form = $("form");
      action = form.attr("action");
      
      $("form input[type='submit']").attr("disabled", true);

      $.post(action, form.serialize()).done(function (response) {
        if (typeof response === "object")
        {
          console.log("response from post (object): "+JSON.stringify(response)+"\n\n");
          data = response;
        }
        else
        {
          console.log("response from post (json): "+response+"\n\n");
          data = JSON.parse(response);
        }
        $("form input[type='submit']").attr("disabled", false);
        processpage(data, action);
//        processpage(data.page, action);
        return; // false;
      });
      event.preventDefault();
      return false; // no further processing
    });
  }
}

$(document).ready(function () { handleform(); } );

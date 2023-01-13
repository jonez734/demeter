$(document).ready(function () {
  var topbar = $("#topbar");
  
  be = getbbsengine();
  
//  be.logentry(topbar);
  
  if (typeof topbar === "object" && typeof topbar.offset === "function")
  {
    var offset = topbar.offset();
    if (typeof offset === "object")
    {
      var navPos = offset.top;
      
      $(window).scroll(function() {
        var fixIT = $(this).scrollTop() >= navPos;
           
        if (fixIT === true)
        {
          topbar.addClass("fixed");

        }
        else
        {
          topbar.removeClass("fixed");
        }
      });
    }
  }
});

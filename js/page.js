var be = getbbsengine();

function processpage(page, url)
{
  be.logentry("processpage() called. page="+page+"\nurl="+url);
  $("body").fadeOut({ 
    duration: 300,
    complete: function() {
      be.logentry("page.head="+page.head);
      if (typeof page.head === "object")
      {
        title = page.head.title;
      }
      else
      {
        title = document.title;
      }
      be.logentry("changing document title to '"+title+"'");
      window.history.pushState(null, title, url);
      document.title = title;

      $("body").html(page.body);
      $("body").fadeIn(750);
      refresh = page.refresh;
      if (typeof refresh === "object")
      {
        setTimeout(function () {
          window.location.href = refresh.url; // replace(refresh.url);
        }, 1000*refresh.delay);
      }
    }
  });
}

function zoidwebpages()
{
//  $("a.zoidweb").each(function () {
//    transitionzoidwebpage(this);
//  });

  be.logentry("zoidwebpages.100: called");
  $("a.zoidweb").click(function (event) {
    event.preventDefault();
    be.logentry("zoidwebpages.110: clicked");
    transitionzoidwebpage($(this));
    return;
  });  
}

function transitionzoidwebpage(self)
{
    var url = self.attr("href");

    srclocation = be.getlocation(window.location);
    dstlocation = be.getlocation(url);
    
    sameorigin = srclocation.hostname === dstlocation.hostname;

    be.logentry("sameorigin="+sameorigin);

    if (sameorigin)
    {
      url = be.appendurlparameter(url, "pageprotocol=enhanced");
      be.logentry("sending jsonp request for url "+url);

      $.jsonp({
        url: url,
        callbackParameter: "callback",
        success: function (json) {
          be.logentry("jsonp request done");
          processpage(json, url);
          return;
        },
        error: function () {
          be.logentry("jsonp error");
          return;
        },
        complete: function(xOptions, textStatus) {
          be.logentry("jsonp() complete. status="+textStatus);
          return;
        }
      });
      return;
          
/*
      $.ajax({
        type: "GET",
        url: url,
        dataType: "jsonp",
        fail: function (jqxhr, status, errorthrown)
        {
          be.logentry("ajax request failed. status="+status+" errorthrown="+errorthrown);
        },
        always: function (page)
        {
          be.logentry("ajax request for "+url+" complete");
        },
        done: function (data)
        {
          be.logentry("ajax request done");
          processpage(data, url);
        }
*/
    }
    else
    {
      be.logentry("using standard redirect to url="+url);
      window.location.href = url;
    }
}

$(document).ready(function () {
//  zoidwebpages();
});


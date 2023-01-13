// inspired by http://caolanmcmahon.com/files/jquery.notify.js/examples/index.html

$(document).ready(function() {
  var displaylist = [];
  var notifycount = -1;
  var notifydiv = $("div#topbar .messages");
  var notifystatusfragment = null;
  var oldnotifystatusfragment = notifydiv.html();

  var payload = null;

/*
  var notificationtimeout = setInterval(function () { 
*/
  be.addinterval(5000, "poll for notify count", function() {
    logentry = be.logentry;
    be.logentry("poll for notify count");
    $.ajax(
      {
        type: "GET",
        dataType: "jsonp",
        url: "//trailersdemo.zoidtechnologies.com/get-notify-count?callback=?"
      })
        .fail(function( jqxhr, textStatus, error ) {
          var err = textStatus + ', ' + error;
            notifydiv.html(err);
        })
        .done(function(payload) {
          notifystatusfragment = $.trim(payload.fragment);
          oldnotifystatusfragment = $.trim(oldnotifystatusfragment);
          
          if (notifystatusfragment !== oldnotifystatusfragment)
          {
            be.logentry("fade out notifystatusfragment");
            notifydiv.fadeOut({
              duration: 300,
              complete: function(data) {
                notifydiv.html(notifystatusfragment);
                oldnotifystatusfragment = notifystatusfragment;
                notifycount = parseInt(payload.unread, 10);
                be.logentry("notifycount="+notifycount);
                notifydiv.fadeIn(800);
              }
            });
          }
        });
  });
  $.notify = function(sticky, id, fragment) {
//    console.log("sticky="+sticky+" id="+id+" fragment="+fragment);
    
    var container = $("ul#notifications");
    if (container.length === 0)
    {
      container = $('<ul id="notifications"></ul>').appendTo(document.body);
      be.logentry("created notification container");
    }

    displaylist.push(id);

    var li = $("<li/>");
    li.data("notifyid", id);
    li.css("opacity", 0.00);
    li.html(fragment);
    li.appendTo(container);
    li.animate({ opacity: 1.0 }, 500);
//    console.log("li="+JSON.stringify(li));

    closebutton = li.find(".closebutton");
    closebutton.click(function(event) {
      var foo = $(this).parent().parent();
      var notifyid = foo.data("notifyid");
      index = displaylist.indexOf(notifyid);
      if (index > -1)
      {
        displaylist.splice(index, 1);
      }
      
      foo.animate({ height: 0 }, 1000, function() {
        foo.remove();
        $("#notifications:empty").remove();
//        foo.slideUp("slow", function() {
//          foo.remove();
//          $("#notifications:empty").remove();
//        });
      });
    });

/*
    if (sticky === false)
    {
//      console.log("sticky set to false");
      setTimeout(function() {
        li.fadeUp('slow', 0, function() {
          $(this).slideUp('fast', function() {
            $(this).remove();
            $("#notifications:empty").remove();
          });
        });
      }, 5000);
    }
*/                                                                                                                        
    return li;
  };
    
  be.addinterval(5000, "poll for undisplayed notifies", function() {
    if (notifycount > 0)
    {
      l = $.ajax(
        {
          type: "GET",
          dataType: "jsonp",
          url: "//trailersdemo.zoidtechnologies.com/get-notify-list?callback=?",
          data: { "displaylist[]": displaylist }
        });
          
      l.done(function (data) {
        var notify = data;
        if (typeof(notify.some) != "undefined") {
          notify.some(function (value, index, array) {
            if ($.inArray(array[index].id, displaylist) === -1)
            {
              $.notify(array[index].sticky, array[index].id, array[index].html);
            }
          });
        }
      });
    }
  });
});

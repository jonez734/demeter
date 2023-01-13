//
// https://forum.jquery.com/topic/using-jquery-ui-tooltip-with-dynamic-ajax-content-on-click-instead-of-hover
//

// thanks to some nice people in ##javascript on freenode 2015-sep-15 for
// helping me get the fetchtooltip() function working.

fetchtooltip.ajaxBuffer = {};
function fetchtooltip(url, tip)
{
  var cache = fetchtooltip.tooltipcache;

  if (fetchtooltip.ajaxBuffer[url]) 
  {
    fetchtooltip.ajaxBuffer[url].push(tip);
    return;
  }

  if (cache == undefined)
  {
//    be.logentry("initializing tooltipcache");
    cache = fetchtooltip.tooltipcache = {};
  }
//  be.logentry("fetchtooltip.110: cache="+JSON.stringify(cache));

//  be.logentry("fetchtooltip.100: url="+url);
  
  if (cache.hasOwnProperty(url))
  {
//    be.logentry("found cached fragment for tooltip with url "+url);
    tip.html(cache[url]);
    return;
  }

  fetchtooltip.ajaxBuffer[url] = [];
  $.ajax({
    url: url,
    dataType: "jsonp",
    success: function (data) {
//      be.logentry("adding "+url+" to tooltipcache");
      cache[url] = data;
      fetchtooltip.tooltipcache = cache;
      fetchtooltip.ajaxBuffer[url].forEach(function (_tip) 
      {
        _tip.html(data);
      });
      delete fetchtooltip.ajaxBuffer[url];
//      be.logentry(fetchtooltip.tooltipcache);
      tip.html(data);
      return;
    }
  });
  return;
}

function buildtooltips() {

  var be = getbbsengine();

  var i = 1;

  $("a.tooltip").each(function () {

    var elem = $(this);
    
    var url = elem.data("contenturl");

//    console.log("url="+url);

    var tip = $('<div id="tooltip_'+i+'" style="display: none;"></div>').appendTo("body");
//    console.log("tooltip: url="+url+" tip.attr('id')="+tip.attr("id"));

    fetchtooltip(url, tip);
//    tip.html(data);

//    $('#'+tip.attr('id')).load(url);
    elem.tooltip({
      position: { my: "left top", at: "right+20% bottom", collision: "fit flip" },
      //Make the items attach to the element (elem is specific, $(this) is not since it is attached to the class)
      items: elem,
      track: false,
      show: { effect: "fadeIn", duration: 500, delay: 1000 },
      hide: { effect: "explode", duration: 1000, delay: 500 },
      content: function(callback) {
        var data = tip.html();
        callback(data);
      },
      close: function(event, ui) {
/*
        ui.tooltip.hover(
          function () {
            $(this).stop(true).fadeTo(400, 1); 
          },
          function () {
            $(this).fadeOut(400, function() {
              $(this).remove();
            });
          }
        ); // tooltip.hover
*/
//        ui.tooltip.on("remove", function() {
//          $(me).tooltip("destroy");
//        });
      }
    }); // tooltip
    i++;
  }); // each
} // buildtooltips

$(document).ready(function () { buildtooltips(); });


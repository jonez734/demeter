// @since 20160221
// singleton inspiration: http://www.dofactory.com/javascript/singleton-design-pattern
var bbsengine = (function () {
    var instance;
 
    function createInstance() 
    {
      return {
        // @since 20160219
        intervals: [],
        visibilityhandler: false,
        // @since 20140210
        logentry: function(message) 
        {
          if (typeof console == "object")
          {
            console.log(message);
          }
          return;
        },
        // @since 20140822
        endswith: function (str, suffix)
        {
          return str.indexOf(suffix, str.length - suffix.length) !== -1;
        },
        appendurlparameter: function (url, param)
        {
          // http://stackoverflow.com/questions/8737615/append-a-param-onto-the-current-url
          var seperator = (url.indexOf("?")===-1)?"?":"&",
              newParam = seperator + param;
          newUrl = url.replace(newParam,"");
          newUrl += newParam;
          return newUrl;
        },
        // http://stackoverflow.com/questions/736513/how-do-i-parse-a-url-into-hostname-and-path-in-javascript
        // @since 20150915
        getlocation: function(href)
        {
          var l = document.createElement("a");
          l.href = href;
          return l;
        },

        initvisibilityeventhandler: function()
        {
          // @see https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API
          var hidden, visibilityChange; 
          if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support 
            hidden = "hidden";
            visibilityChange = "visibilitychange";
          } else if (typeof document.mozHidden !== "undefined") {
            hidden = "mozHidden";
            visibilityChange = "mozvisibilitychange";
          } else if (typeof document.msHidden !== "undefined") {
            hidden = "msHidden";
            visibilityChange = "msvisibilitychange";
          } else if (typeof document.webkitHidden !== "undefined") {
            hidden = "webkitHidden";
            visibilityChange = "webkitvisibilitychange";
          }
          // If the page is hidden, stop the intervals
          // if the page is shown, restart the intervals
          function handleVisibilityChange() 
          {
//            instance.logentry("handlevisibilitychange.100: called");
            if (document[hidden])
            {
              be.cancelintervals();
            } 
            else 
            {
              be.restartintervals();
            }
          }

          if (typeof document.addEventListener === "undefined" || typeof hidden === "undefined") 
          {
            instance.logentry("This site requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
          } 
          else 
          {
            // Handle page visibility change   
            document.addEventListener(visibilityChange, handleVisibilityChange, false);
          }
        },
        
        addinterval: function(interval, note, func)
        {
          id = setInterval(func, interval);
          this.intervals.push([id, interval, func, note]);
          instance.logentry("addinterval.110: id="+id+" interval="+interval+" note="+note);
          if (this.visibilityhandler === false)
          {
//            instance.logentry("init visibility event handler");
            this.initvisibilityeventhandler();
            this.visibilityhandler = true;
          }
          return;
        },
        cancelintervals: function() {
          logentry = instance.logentry;
          logentry("canceling intervals");
          this.intervals.forEach(function (item, index, arr) {
            id = item[0];
            clearInterval(id);
//            logentry("cancelintervals.100: id="+id);
          });
          return;
        },
        restartintervals: function() {
          logentry = instance.logentry;
          logentry("restarting intervals");
          this.intervals.forEach(function (item, index, arr) {
            oldid = item[0];
            interval = item[1];
            func = item[2];
            note = item[3];
            id = setInterval(func, interval);
//            logentry("restartintervals.100: id="+id+" note="+note);
            arr[index][0] = id;
          });
          return;
        }
      };
    }
    return {
        getInstance: function () {
            if (!instance) {
//                console.log("calling createinstance()");
                instance = createInstance();
            }
//            instance.logentry("returning instance");
            return instance;
        }
    };
})();

function getbbsengine() {
//  console.log("getbbsengine called");
  return bbsengine.getInstance();
}

<?php

define("SITETITLE", "demeter");
define("SITEADMINEMAIL", "Zoid Technologies <demeter@projects.zoidtechnologies.com>");

define("STATICSKINURL", "/skin/");
/**
 * define the base url for the site. THIS VALUE MUST BE TERMINATED WITH A "/"
 */
define("SITEURL", "http://demeter.zoidtechnologies.com/");
define("SKINURL", SITEURL . "skin/");
define("SYSTEMDSN", "pgsql://apache@127.0.0.1/zoidweb2");

// VHOSTDIR *must* be an absolute path, else there can be problems with path traversal attacks
define("VHOSTDIR", "/srv/www/vhosts/demeter.zoidtechnologies.com/");
define("DOCUMENTROOT", VHOSTDIR . "80/html/");

define("SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c");
define("SMARTYPLUGINSDIR", VHOSTDIR . "smarty/");
define("SMARTYTEMPLATESDIR", VHOSTDIR."skin/tmpl/");

// @see http://php.net/strftime
define("DATEFORMAT", "%Y-%b-%d %I:%M %p %Z (%A)");

define("LOGENTRYPREFIX", "demeter");

date_default_timezone_set("America/New_York");

define("SESSIONCOOKIEDOMAIN", "demeter.zoidtechnologies.com");
define("SESSIONCOOKIEEXPIRE", 12*60*60);
define("SESSIONCOOKIEPATH", "/");

define("STATICJAVASCRIPTURL", "/js/");

define("ENGINEURL", "/");

define("USEMEMBERCREDITS", False);

define("FONTAWESOMECSSURL", "//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css");

define("SITENAME", "demeter");

define("USESHOPPINGCART", False);
?>

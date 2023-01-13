<?php

/**
 * This file is part of bbsengine3
 * @copyright (C) 2002-2016 Zoid Technologies. All Rights Reserved.
 *
 * Thank you for using Software That Sucks Less.
 *
 * @package bbsengine3
 */

/**
 * pull in {@link http://pear.php.net/html_quickform pear::html_quickform}
 *
 */
require_once("HTML/QuickForm2.php");
require_once("HTML/QuickForm2/Renderer.php");

/**
 * @since 20160419
 */
require_once("Log.php");

/**
 * pull in smarty class
 */
require_once("Smarty.class.php");

/**
 * pull in {@link http://pear.php.net/mdb2 PEAR::MDB2} database abstraction module
 */
require_once("MDB2.php");

/**
 * pull in {@link http://pear.php.net/html_page2 PEAR::HTML_Page2}
 */
require_once("HTML/Page2.php");

require_once("Pager.php");
/**
 * returns a lightly configured PEAR::HTML_QuickForm2 instance
 * 
 * @param string $name name of the form
 * @param string $method "post" or "get" defaults to "post"
 * @param string $action
 * @param string $target where the form should be submitted
 * @param string $attributes
 * @param boolean $tracksubmit
 *
 * @since 20081006
 */
/*
function &getquickform($name, $method="post", $action="", $target="", $attributes="", $tracksubmit=True)
{
  if (defined("USEHTMLQUICKFORM2"))
  {
    return PEAR::raiseError("qf2 support is not yet complete");
  }
  else
  {
    $form = new HTML_QuickForm($name, $method, $action, $target, $attributes, $tracksubmit);  
    $form->removeAttribute("name");
    
    $form->addElement("hidden", "mode", "NEEDINFO", array("id" => "mode"));
    $form->addElement("hidden", "id", "NEEDINFO");
    $form->addElement("hidden", "memberid", "NEEDINFO");
    $form->addElement("hidden", "protocol", "http", array("id" => "protocol"));
  }

  return $form;
}
*/

/**
 * returns a configured PEAR::HTML_QuickForm2 instance. 
 *
 * 'pageprotocol' is set to 'standard'. if javascript is running the field value will be changed to 'enhanced'.
 *
 * the 'tracksubmit' param defaults to * True because I cannot think of a reason not to use it that way but it should be a knob.
 *
 * there is a recursive filter automagically added that calls 'trim'.
 *
 * @param string $name name of the form which should be unique to the app
 * @param string $method "post" or "get" defaults to "post"
 * @param string $attributes attributes for the <form> tag
 * @param boolean $tracksubmit defines whether or not to add a hidden field that tracks if the form has been submitted.
 * @return a configured html_quickform2 instance
 *
 * @since 20130209
 */
function getquickform($id, $method="post", $attributes="", $tracksubmit=True)
{
  $form = new HTML_QuickForm2($id, $method, $attributes, $tracksubmit);
  $form->setAttribute("enctype", "multipart/form-data");
  $form->addHidden("mode")->setValue("NEEDINFO");
  $form->addHidden("id")->setValue("NEEDINFO");
  $form->addHidden("memberid")->setValue("NEEDINFO");
  $form->addHidden("pageprotocol")->setValue("standard");
  $form->addRecursiveFilter("trim");
//  $form->addRecursiveFilter("strip_tags");

  return $form;
}

/**
 * @since 20140902
 *
 * function which returns a configured Array renderer for use by quickform2
 * @param $options array optional dictionary containing renderer options
 * @return QF2 Array renderer
 */
function getquickformrenderer($options=null)
{
 $_options = array(
  "group_errors" => True, 
  "group_hiddens" => True, 
  "required_note" => "<span class='requiredstar'>*</span> denotes required fields."
 );
 
 if (is_array($options))
 {
  $_options = array_merge($_options, $options);
 }

 $renderer = HTML_QuickForm2_Renderer::factory("array")->setOption($_options);
 return $renderer;
}

/**
 * return a smarty3 template object, configured for the website
 * @param dictionary options an array of options: templatedir, pluginsdir, compiledir, compileid
 * @since 20140710
 */
function _getsmarty($options=null)
{
//    logentry("_getsmarty.100: options=".var_export($options, True));
    
    $s = new Smarty();
    if (is_array($options))
    {
      if (array_key_exists("templatedir", $options) === True)
      {
        $s->setTemplateDir($options["templatedir"]);
      }
      if (array_key_exists("pluginsdir", $options) === True)
      {
        $s->addPluginsDir($options["pluginsdir"]);
      }
      if (array_key_exists("compiledir", $options) === True)
      {
        $s->compile_dir = $options["compiledir"];
      }
      if (array_key_exists("compileid", $options) === True)
      {
        $s->compile_id = $options["compileid"];
      }
      if (array_key_exists("vars", $options) === True)
      {
        foreach ($options["vars"] as $k => $v)
        {
          $s->assign($k, $v);
        }
      }
    }
    
    $currentmemberid = getcurrentmemberid();
    
    if ($currentmemberid > 0)
    {
      $member = getcurrentmember();
      if (PEAR::isError($member))
      {
        logentry("getsmarty.10: " . $member->toString());
        return PEAR::raiseError($member);
      }
    }
    else
    {
      $member = [];
      $member["id"] = null;
    }
    $flags = getflags($currentmemberid);
    if (PEAR::isError($flags))
    {
      logentry("getsmarty.42: " . $flags->toString());
      return PEAR::raiseError($flags);
    }

    $member["flags"] = $flags;

//    logentry("getsmarty.43: currentsite=".var_export(getcurrentsite(), True));

    $s->assign("currentpage", getcurrentpage());
    $s->assignByRef("currentmemberid", $currentmemberid);
    $s->assignByRef("currentmember", $member);
    $s->assign("currentaction", getcurrentaction());
    $s->assign("currentsite", getcurrentsite());
    $s->assign("currentpageprotocol", getpageprotocol());
    $s->assign("currenturi", getcurrenturi());
    $s->assign("currentpath", getcurrentpath());
    $s->assign("currentsig", getcurrentsig());

    if (defined("USESHOPPINGCART") && USESHOPPINGCART === True)
    {
      $s->assign("currentcart", getcurrentcart());
    }
    
//    logentry("getsmarty.44: gettemplatedir=".var_export($s->getTemplateDir(), True));
    return $s;
}

/**
 * define getsmarty() in case an upper layer did not define it
 * @since 20140710
 */
if (function_exists("getsmarty") === False)
{
  function getsmarty($options=null)
  {
    $options = array();
    $options["pluginsdir"] = array(SMARTYPLUGINSDIR);
    $options["templatedir"] = array(SMARTYTEMPLATESDIR);
    $options["compiledir"] = SMARTYCOMPILEDTEMPLATESDIR;
    $options["compileid"] = LOGENTRYPREFIX;
    return _getsmarty($options);
  }
}

/** 
 * return a configured html_page2 instance using the 'message' and 'url'
 * parameters.  this function should be used in cases where content or css
 * files need to be added.
 * 
 * @since 20120823
 * @deprecated
 */
function fetchredirectpage($message, $url = null, $delay = 3)
{
  logentry("fetchredirectpage.10: function disabled");  
}

/**
 * display a redirect page template with message and optional url
 * 
 * @param string $message
 * @param string $url
 * @access public
 */
function displayredirectpage($message, $url = null, $delay = 5)
{
  // if we were not given a url to redirect to, get the last one that was
  // set and use that.
  $url = ($url === null) ? getreturntourl() : $url;
  $title = getreturntotitle();

  $tmpl = getsmarty();
  $tmpl->assign("message", $message);
  $tmpl->assign("url", $url);
  $tmpl->assign("title", $title);
  $tmpl->assign("delay", $delay);

  $options = [];
  $options["pagedata"]["body"] = $tmpl->fetch("redirectpage.tmpl");

  $page = getpage("redirecting to {$url}...");
  $page->addStyleSheet(STATICSKINURL."css/redirectpage.css");
  $page->addScript(STATICJAVASCRIPTURL."redirectpage.js");
  
  $page->setMetaRefresh($delay, $url);

  $options["redirect"]["delay"] = $delay;
  $options["redirect"]["url"] = $url;
  
  return displaypage($page, $options);
/*  
  $pageprotocol = getpageprotocol();
  if ($pageprotocol === "standard")
  {
    if ($delay > 0)
    {
      $page->setMetaRefresh($delay, $url);
    }

    $page->display();
  }
  else if ($pageprotocol === "enhanced")
  {
    $page = array();
    $page["body"] = implode($bodycontent);
    
    if ($delay > 0)
    {
      $page["refresh"]["delay"] = $delay;
      $page["refresh"]["url"] = $url;
    }
    print encodejson(array("page" => $page));
  }
  return;
*/
}

/**
 * return a html_page2 instance populated with the errormessage.tmpl smarty template
 * 
 * @since 20120823
 */
function fetcherrorpage($message)
{
  $page = getpage("Error");
  $page->addStyleSheet(SKINURL . "css/errormessage.css");
  $page->addBodyContent(fetchpageheader("Error"));

  $s = getsmarty();
  $s->assign("message", $message);
  $page->addBodyContent($s->fetch("errormessage.tmpl"));

  $page->addBodyContent(fetchpagefooter());
  return $page;
  
}

/**
 * display an error page template with message
 * 
 * @param string $message
 * @param integer $errorcode http error code (i.e. 500, 404)
 * @access public
 * @since 20120608
 */
function displayerrorpage($message, $statuscode=418, $template="errormessage.tmpl")
{
  logentry("displayerrorpage.100: message=".var_export($message, True)." statuscode=".var_export($statuscode, True));
  
  $tmpl = getsmarty();
  $tmpl->assign("message", $message);
  $tmpl->assign("statuscode", $statuscode);

  $page = getpage("error");

  header("HTTP/1.0 {$statuscode} error", True, $statuscode);
  $options = [];
  $options["statuscode"] = $statuscode;
  $options["pagedata"]["body"] = $tmpl->fetch($template);
  displaypage($page, $options);
  return;
}

/**
 * renamed to displayerrorpage
 * @removed 20160604
 */
/*
function displayerrormessage($message, $errorcode=418)
{
  logentry(">>> displayerrormessage.10: called renamed function displayerrormessage.");
  return displayerrorpage($message, $errorcode);
}
*/
/**
 * returns footer.tmpl as a string with optional mantra
 * 
 * @param array $options dictionary of options
 * @since 20110105
 * @access private
 */
function _fetchpagefooter($options=null)
{
  $tmpl = getsmarty();
  return $tmpl->fetch("pagefooter.tmpl");
}

if (!function_exists("fetchpagefooter"))
{
  function fetchpagefooter()
  {
    return _fetchpagefooter();
  }
}

/**
 * return page footer template as a string. previously known as fetchfooter().
 * 
 * @since 20130826
*/
if (!function_exists("fetchpagefooter"))
{
  function fetchpagefooter($options)
  {
    return _fetchpagefooter($options);
  }
}

/**
 * display a page footer template
 *
 * @access public
 * @deprecated
 */
function displayfooter()
{
  logentry("displayfooter.100: this function is deprecated.");
  displaypagefooter();
  return;
}

/**
 * print result of fetchpagefooter() to browser
 *
 * @since 20130826
 */
function displaypagefooter()
{
  print fetchpagefooter();
}

/** 
 * @since 20110105
 */
function fetchheader($title=null, $css=null, $script=null)
{
//  print "trace fetchheader 1";
  $s = getsmarty();
//  print "trace fetchheader 2";
  $s->assign("title", $title);
  $s->assign("css", $css);
  $s->assign("script", $script);
  return $s->fetch("pageheader.tmpl");
}

/** 
 * @since 20110105
 */
function fetchpageheader($title=null, $css=null, $script=null)
{
  $tmpl = getsmarty();
  $tmpl->assign("title", $title);
  $tmpl->assign("css", $css);
  $tmpl->assign("script", $script);
  return $tmpl->fetch("pageheader.tmpl");
}

/**
 * display a page header template with optional title string
 *
 * @param string $title title of page
 * @param array $css
 * @param array $script
 * @access public
 * @deprecated
 */
function displayheader($title=null, $css=null, $script=null)
{
  print fetchpageheader($title, $css, $script);
  return;
}

function displaypageheader($title=null, $css=null, $script=null)
{
  print fetchpageheader($title, $css, $script);
}

/**
 * return the navbar template as a string.
 *
 * @since 20110413
 * @param dictionary options keys 'menu' and 'template' accepted
 * @access private
 * @return string
 */
function _fetchnavbar($options=null)
{
//  logentry("_fetchnavbar.100: options=".var_export($options, True));
  
  $menu = isset($options["menu"]) ? $options["menu"] : null;
  $template = isset($options["template"]) ? $options["template"] : "navbar.tmpl";

  $s = getsmarty();
  $s->assign("data", $menu);
  return $s->fetch($template);
}

if (!function_exists("fetchnavbar")) {
  function fetchnavbar($menu=null)
  {
    return _fetchnavbar(["menu" => $menu, "template" => "navbar.tmpl"]);
  }
}

function _fetchsidebar($menu=null)
{
  return _fetchnavbar(["menu" => $menu, "template" => "sidebar.tmpl"]);
}

if (!function_exists("fetchsidebar"))
{
  function fetchsidebar($menu=null)
  {
    return _fetchnavbar(["menu" => $menu, "template" => "sidebar.tmpl"]);
  }
}

/*
function displaysidebar($menu=null)
{
  print fetchnavbar($menu);
  return;
}
*/

/**
 * put $message into a log at the given priority
 *
 * @since 20080105
 * @param string
 * @param enum
 */
function logentry($message, $priority=LOG_INFO)
{
  
  if (defined("LOGENTRYPREFIX") === False)
  {
    define("LOGENTRYPREFIX", "Br0k3d-C0nfig!");
  }
  
  $ip = $_SERVER["REMOTE_ADDR"];
  
  $logger = Log::singleton("syslog", "", LOGENTRYPREFIX, [], PEAR_LOG_DEBUG);
  $logger->log("--> ".$priority." ".$message, $priority);
  /*
  if (defined("LOGENTRYPREFIX"))
  {
    openlog(LOGENTRYPREFIX, LOG_ODELAY|LOG_PID, LOG_LOCAL0);
  }
  else
  {
    openlog("BBSENGINE3", LOG_ODELAY|LOG_PID, LOG_LOCAL0);
  }
  syslog($priority, $message);
  closelog();
  */
  return;
}

/**
 * set the 'returnto' session variable
 *
 * @param string $url
 * @param string $title
 * @return old value
 *
 */
function setreturnto($url=null, $title=null)
{
  $old = getreturntourl();
  $url = ($url === null) ? $old : $url;
//  $parsedurl = parse_url($url);
//  $normalizedurl = http_build_url($parsedurl, $parsedurl);
  $returnto = ["url" => $url, "title" => $title];

  $_SESSION["returnto"] = $returnto;
    
  logentry("setreturnto: url='{$url}'  title='{$title}'");

  return $old;
}
               
/**
 * get the 'returnto' session var which contains 'url' and 'title', falling back to SITEURL and SITETITLE from config
 *
 * @since 20150722
 * @return array with two keys 'url' and 'title'
*/
function getreturnto()
{
 return isset($_SESSION["returnto"]) ? $_SESSION["returnto"] : array("url" => SITEURL, "title" => SITETITLE);
}
                 
/**
 * returns the returntourl as a string if it has been set.
 *
 */
function getreturntourl()
{
  $url = isset($_SESSION["returnto"]["url"]) ? $_SESSION["returnto"]["url"] : SITEURL;
  
  if (isset($url) && !empty($url) && !is_null($url))
  {
    return $url;
  }
  else
  {
    return SITEURL;
  }
}
                   
function getreturntotitle()
{
  return isset($_SESSION["returnto"]["title"]) ? $_SESSION["returnto"]["title"] : SITETITLE;
}
                     
/**
 * only strip slashes from a string if get_magic_quotes_gpc() returns True
 *
 * this function was ripped from {@link http://pear.php.net/services_amazon/ PEAR::Services_Amazon}.
 *
 * @param string
 * @return string with slashes stripped
 * 
 */
function safestripslashes($value)
{
  return get_magic_quotes_gpc() ? stripslashes($value) : $value;
}

/**
 * display the permission denied template
 *
 * @access public
 * @since 20121008
 */
function displaypermissiondenied($message=null)
{
  logentry("displaypermissiondenied.100: message=".var_export($message, True));
  
  $message = empty($message) ? "permission has been denied. sorry it didn't work out" : $message;
  $res = displayerrorpage($message, 403);
  return $res;
}

/**
 * @since 20110722
 * @deprecated
 */
function permission($name, $memberid=0)
{
  logentry("used deprecated function permission(). use flag() instead.");
  return flag($name, $memberid);
}

/**
 * permission checking function
 * 
 * permissions "PUBLIC" and "AUTHENTICATED" are built-in and checked for
 * specially before any database connection is made. other permissions are
 * in uppercase and must be listed in the flag table. if the member being
 * checked does not have a value set for a particular flag, the default
 * value will be returned.
 *
 * @param string $name 
 * @param integer $memberid
 * @return boolean
 * @since 20080324
 */ 
function flag($name, $memberid=0)
{
  if ($memberid == 0)
  {
    $memberid = getcurrentmemberid();
  }
	
  $name = strtoupper($name);
    
  if ($name == "PUBLIC")
  {
    return True;
  }

  if ($memberid == 0 || is_null($memberid))
  {
    return False;
  }
	
  if ($name == "AUTHENTICATED")
  {
    return True;
  }
    
  $res = getflag($name, $memberid);
  if (PEAR::isError($res))
  {
    logentry("permission: ERROR: " . $res->toString());
    return PEAR::raiseError($res);
  }
  
  if (is_null($res))
  {
    return $res;
  }
  
  if ($res == True)
  {
    return True;
  }
  
  return False;
}

/**
 *
 * connect to a DSN using the MDB2 "singleton" static method, configures the
 * object (quote_identifier set to true, fetchmode set to
 * MDB2_FETCHMODE_ASSOC, and loading the "Extended" module), and returns a
 * reference to it.
 *
 * @param string dsn
 * @return object|error
 */
function dbconnect($dsn)
{
  $dbh = MDB2::singleton($dsn);
  if (PEAR::isError($dbh))
  {
    logentry("dbconnect.10: " . $dbh->toString(), emerg());
    return $dbh;
  }
  
  $res = $dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);
  if (PEAR::isError($res))
  {
    logentry("dbconnect.12: " . $res->toString(), notice());
    return $res;
  }
  
  $res = $dbh->loadModule("Extended");
  if (PEAR::isError($res))
  {
    logentry("dbconnect.14: " . $res->toString(), notice());
    return $res;
  }

/*
  $res = $dbh->setOption("quote_identifier", True);
  if (PEAR::isError($res))
  {
    logentry("dbconnect.16: " . $res->toString());
    return $res;
  }
*/  
  return $dbh;
}

/**
 * return the "current member id" for the session
 *
 * @param string key optional key to use when accessing $_SESSION, defaults to "currentmemberid"
 *
 * @return int
 */
function getcurrentmemberid($key=null)
{
  if ($key === null)
  {
    if (defined("CURRENTMEMBERIDSESSIONKEY"))
    {
      $key = CURRENTMEMBERIDSESSIONKEY;
    }
    else
    {
      $key = "currentmemberid";
    }
  }

  $res = isset($_SESSION[$key]) ? intval($_SESSION[$key]) : null;
  return $res;
}

/**
 * set the "current member id" for the session
 * 
 * @param integer
 * @return int previous value
 */
function setcurrentmemberid($id)
{
  logentry("setcurrentmemberid.10: id=".var_export($id, True));

  $old = getcurrentmemberid();
  $_SESSION["currentmemberid"] = intval($id);
  return $old;
}

/**
 * returns data for a given member id.
 *
 * @param integer
 * @return array|PEAR::Error
 * @access public
 * @since 19990102
 */
function _getmember($id)
{
//  logentry("_getmember.110: id=" . $id);

  $id = intval($id);
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("_getmember.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select member.* from engine.member where id=? limit 1";
  $dat = array($id);
  $res = $dbh->getRow($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("_getmember.120: " . $res->toString());
    return $res;
  }
//  logentry("_getmember.130: returning res");
  return $res;
}

if (!function_exists("getmember"))
{
  function getmember($id)
  {
    return _getmember($id);
  }
}

function getcurrentmember()
{
  $currentmemberid = getcurrentmemberid();
  return getmember($currentmemberid);
}

/**
 * an html_quickfom rule which returns True if the given email address is in the database.
 *
 * @param string
 * @param integer
 * @todo is this a duplicate?
 * @see emailaddresscallback()
 *
 */
function emailaddressformrule($value, $id=0)
{
  if (empty($value))
  {
    return False;
  }

  if (emailaddresscallback($value) === False)
  {
    return True;
  }
  else
  {
    return False;
  }
  $dbh = &dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("bbsengine2.emailaddressformrule: " . $dbh->toString());
    return False;
  }

  $sql = "select id from member where emailaddress=?";
  $dat = array($value);
  $res = $dbh->getOne($sql, "integer", $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("bbsengine2.emailaddressformrule: " . $res->toString());
    return False;
  }

  $id = intval($res);
  if ($id > 0)
  {
    return False;
  }

  return True;
}

function datestamp($stamp, $format=null)
{
  if (is_string($format) && !is_null($format) && !empty($format))
  {
    $res = strftime($format, $stamp);
  }
  else
  {
    $res = strftime(DATEFORMAT, $stamp);
  }
//  logentry("datestamp: {$stamp} = {$res}");
  return $res;
}

/**
 * add a flag to the system
 *
 * @since 20121029
 */
function addflag($name, $description, $defaultvalue)
{
  $flag = array();
  $flag["name"] = $name;
  $flag["description"] = $description;
  $flag["defaultvalue"] = $defaultvalue;
  
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("addflag.8: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->autoExecute("flag", $flag, MDB2_AUTOQUERY_INSERT);
  if (PEAR::isError($res))
  {
    logentry("addflag.10: " . $res->toString());
    return $res;
  }
  return;
}

/**
 * function to clear a flag, resetting it to the default value for the given user.
 *
 * @since 20121029
 */
function clearflag($name, $memberid=null)
{
  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }
  logentry("clearflag.14: clearing {$name} for memberid {$memberid}");
  
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("clearflag.10: " . $dbh->toString());
    return $dbh;
  }

  $res = $dbh->autoExecute("map_member_flag", null, MDB2_AUTOQUERY_DELETE, "name=".$dbh->quote($name, "text")." and memberid=".$dbh->quote($memberid, "integer"));
  if (PEAR::isError($res))
  {
    logentry("clearflag.12: " . $res->toString());
    return $res;
  }
  return;
}
/**
 * set a flag on the given memberid to the given value
 * 
 * @since 20120409
 * @param string
 * @param boolean
 * @param integer
 */
function setflag($flag, $value, $id=null)
{
  logentry("setflag: id=".var_export($id, True)." flag=".var_export($flag, True)." value=".var_export($value, True));

  if ($id === null)
  {
    $id = getcurrentmemberid();
  }

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    return $dbh;
  }

//  $sql = "delete from map_member_flag where memberid=" . $dbh->quote($id, "integer") . " and flagname=" . $dbh->quote($flag, "text");
//  $res = $dbh->execute($sql);
  $where = array("memberid=" . $dbh->quote($id, "integer"), "name=" . $dbh->quote($flag, "text"));
  $res = $dbh->autoExecute("engine.map_member_flag", null, MDB2_AUTOQUERY_DELETE, $where);
  if (PEAR::isError($res))
  {
    logentry("setflag.10: " . $res->toString());
    return $res;
  }
  
  $mmf = array();
  $mmf["name"] = $flag;
  $mmf["memberid"] = $id;
  $mmf["value"] = $value;
  $res = $dbh->autoExecute("engine.map_member_flag", $mmf, MDB2_AUTOQUERY_INSERT, array("text", "integer", "boolean"));
  if (PEAR::isError($res))
  {
    logentry("setflag.12: " . $res->toString());
    return $res;
  }

  return;
}

/**
 * returns flag value given the flag name and member id.
 *
 * @param string $flag flag name
 * @param integer $id member id
 * @return boolean
 */
function getflag($flag, $memberid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    return $dbh;
  }
  
  // @since 20130617
  // thanks to pingwin and teh1ghool on #php (oftc)

//  logentry("getflag.100: flag=".var_export($flag, True)." id=".var_export($id, True));

    $sql = <<<SQL
select 
  f.name, 
  coalesce(mmf.value, f.defaultvalue) as value 
from engine.flag as f
left outer join engine.map_member_flag as mmf on (f.name=mmf.name and mmf.memberid=?) 
where f.name=?;
SQL;

  $dat = array($memberid, $flag);
  $res = $dbh->getRow($sql, null, $dat, array("integer", "text"));
  if (PEAR::isError($res))
  {
    logentry("bbsengine3.getflag.0: " . $res->toString());
    return PEAR::raiseError($res);
  }

  $res = (isset($res["value"]) && $res["value"] == "t") ? True : False;
//  logentry("getflag.100: flag=".var_export($flag, True). " memberid=".var_export($memberid, True)." res=".var_export($res, True));
  return $res;
}

/**
 * return the set of flags and their values for a given memberid.
 * rewritten 2011-jun-23 so it actually works without smarty3 throwing notices about undefined vars
 *
 * @since 20081002
 * @param integer $memberid
 * @return array or PEAR_Error
 */
function getflags($memberid)
{
//  logentry("getflags.10: memberid=".var_export($memberid, True));
  $dbh = dbconnect(SYSTEMDSN);
//  var_export(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getflags.1: " . $dbh->toString());
    return $dbh;
  }
  $sql = <<<SQL
select 
  flag.name, 
  coalesce(map_member_flag.value, flag.defaultvalue) as value
from engine.flag 
left outer join engine.map_member_flag on flag.name = engine.map_member_flag.name and engine.map_member_flag.memberid=?
SQL;
  $dat = array($memberid);
  $res = $dbh->getAll($sql, null, $dat);
  if (PEAR::isError($res))
  {
    logentry("getflags.10: " . $res->toString());
    return $res;
  }
  $flags = array();
  if ($memberid > 0)
  {
    $flags["AUTHENTICATED"] = True;
  }
  else
  {
    $flags["AUTHENTICATED"] = False;
  }

  foreach ($res as $rec)
  {
    $k = $rec["name"];
    $v = $rec["value"];

    if ($v == "t" || $v == "1")
    {
      $flags[$k] = True;
    }
    elseif ($v == "f" || $v == "0")
    {
      $flags[$k] = False;
    }
  }
//  var_export($flags);
  return $flags;
}

/**
 * returns a lightly configured PEAR::HTML_Page2 instance
 *
 * @since 20110725
 */
function _getpage($title=null, $options=array())
{
  $page = new HTML_Page2($options);
/*
  if (strpos($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) 
  {
       $page->setDoctype("XHTML 1.0 Strict");
       $page->setMimeEncoding("application/xhtml+xml");
  } 
  else 
  {
       // HTML that qualifies for XHTML 1.0 Strict automatically
       // also complies with XHTML 1.0 Transitional, so if the
       // requesting browser doesn't take the necessary mime type
       // for XHTML 1.0 Strict, let's give it what it can take.
       $page->setDoctype("XHTML 1.0 Transitional");
  }
*/
  $page->setDoctype("XHTML 1.0 Transitional");

  $page->setTitle($title);

  if (is_array($options) && array_key_exists("staticskinurl", $options))
  {
    $staticskinurl = $options["staticskinurl"];
  }
  else
  {
    $staticskinurl = "/skin/";
  }
    
//  logentry("_getpage.100: options=".var_export($options, True));

  $page->addStyleSheet($staticskinurl . "css/pageheader.css");
  $page->addStyleSheet($staticskinurl . "css/blurb.css");
  $page->addStyleSheet($staticskinurl . "css/pagefooter.css");

  $stylesheets = isset($options["stylesheets"]) ? $options["stylesheets"] : [];
  foreach ($stylesheets as $stylesheet)
  {
    $page->addStyleSheet($stylesheet);
  }

  if (defined("SITECSS"))
  {
    $page->addStyleSheet(SITECSS);
  }

  if ($title !== null)
  {
    if (defined("TITLEPOSTFIX"))
    {
      $page->setTitle($title.TITLEPOSTFIX);
    }
    else
    {
      $page->setTitle($title);
    }
  }
  
  if (defined("METAKEYWORDS"))
  {
    $page->setMetaData("keywords", METAKEYWORDS);
  }
  else
  {
    $page->setMetaData("keywords", "zoid technologies, intranet, extranet, website, custom application, bbsengine, bbsengine3, custom information systems");
  }

  return $page;
}

if (!function_exists("getpage"))
{
  function getpage($title=null, $options=null)
  {
    return _getpage($title, $options);
  }
}

/**
 * returns the maturecontentwarning smarty template as a string.
 *
 * @since 20110601
 */
function fetchmaturecontentwarning()
{
  $tmpl = getsmarty();
  return $tmpl->fetch("maturecontentwarning.tmpl");
}

/**
 * set current page
 *
 * @param string $page
 * @since 20100510
 */
function setcurrentpage($page)
{
  logentry("setcurrentpage.10: page=".var_export($page, True));
  $_SESSION["currentpage"] = $page;
  return;
}

/**
 * get current page
 *
 * @author Zoid Technologies
 * @since 20100510
 */
function getcurrentpage()
{
  $page = isset($_SESSION["currentpage"]) ? $_SESSION["currentpage"] : null;
  return $page;
}

/**
 * adds a set of html_quickform elements and rules for a captcha
 *
 * @since 20110614
 */
/*
function buildcaptchafieldset($form, $sessionVar=null, $options=null)
{
//  $form->addElement("header", "captchafieldset", "Verification");

  logentry("buildcaptchafieldset.10: disabled");
  return;

  if ($sessionVar === null)
  {
    $sessionVar = basename(__FILE__, ".php");
  }

  logentry("buildcaptchafieldset.5: sessionVar=".var_export($sessionVar, True));
  
  $_options = array(
    "width"        => 250,
    "height"       => 90,
    "callback"     => "/gencaptchaimage.php?var=".$sessionVar,
    "sessionVar"   => $sessionVar,
    "alt" => "testing",
    "imageOptions" => array(
      "font_size" => 20,
      "font_path" => "/usr/share/fonts/truetype/",
      "font_file" => "cour.ttf",
      "min_font_size" => 10,
      "max_font_size" => 30,
      "lines_color" => "#FF0000",
      "background_color" => "#F0F0F0")
    );

  if ($options !== null)
  {
    $options = array_merge($_options, $options);
  }
  else
  {
    $options = $_options;
  }

  logentry("options=".var_export($options, True));
  
  $captcha_question = &$form->addElement("CAPTCHA_Image", "captcha_question",
                                         "Type the letters you see", $options);
  
  if (PEAR::isError($captcha_question))
  {
    logentry("buildcaptchafieldset.10: " . $captcha_question->toString());
    return PEAR::raiseError("Form Error (code: buildcaptchafieldset.10)");
  }

  $captcha_answer = &$form->addElement("text", "captcha", "Enter the answer");
  if (PEAR::isError($captcha_answer))
  {
    logentry("buildcaptchafieldset.12: " . $captcha_answer->toString());
    return PEAR::raiseError("Form Error (code: buildcaptchafieldset.12)");
  }

  $form->addRule("captcha", "Enter the answer to the verification",
                 "required");
                 
  $form->addRule("captcha", "You did not answer the verification correctly",
                  "CAPTCHA", $captcha_question);
//  var_export($options);
//  exit;
}
*/

/**
 * function which displays the "delete confirmation" template.
 *
 * @since 20110801
 */
function displaydeleteconfirmation($message, $yesuri, $yestxt, $nouri, $notxt, $title=null)
{
  if ($title === null)
  {
    $title = SITETITLE . " - Delete Confirmation";
  }
  $page = getpage($title);
  $page->addStyleSheet(SKINURL . "css/deleteconfirmation.css");
  $page->addBodyContent(fetchpageheader($title));
  $tmpl = getsmarty();
  $tmpl->assign("message", $message);
  $tmpl->assign("yesuri", $yesuri);
  $tmpl->assign("yestxt", $yestxt);
  $tmpl->assign("nouri", $nouri);
  $tmpl->assign("notxt", $notxt);
  $page->addBodyContent($tmpl->fetch("deleteconfirmation.tmpl"));
  $page->addBodyContent(fetchpagefooter());
  $page->display();
  return;
}

/**
 * function to set the current "action" so that "view" can be hidden when in view mode, etc
 *
 * @since 20110803
 */
function setcurrentaction($op)
{
  if (defined("PEARHTTPSESSION"))
  {
    logentry("setcurrentaction.10: op=".var_export($op, True));
    HTTP_Session2::set("currentaction", $op);
  }
  else
  {
    $_SESSION["currentaction"] = $op;
  }
}

/**
 * function to get the current "action" so that "view" can be hidden when in view mode, etc
 *
 * @since 20110803
 */
function getcurrentaction()
{
  $op = isset($_SESSION["currentaction"]) ? $_SESSION["currentaction"] : null;
  return $op;
}

/**
 * function to clear the current action
 *
 * @since 20150309
 */
function clearcurrentaction()
{
  setcurrentaction(NULL);
  return;
}

function get_request_url()
{
  return get_request_scheme() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
    
function get_request_scheme()
{
  return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

/**
 * function that returns the current url (protocol, hostname, etc)
 * 
 * @since 20110804
 */
function getcurrenturi()
{
  $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
  $host = $_SERVER["HTTP_HOST"];
  $uri = $_SERVER["REQUEST_URI"];
  $buf = "{$protocol}://{$host}{$uri}";
//  logentry("getcurrenturi.100: " . var_export($buf, True));
  return $buf;
}

/**
 * html_quickform callback function to check if an email address is already in use in the db
 * 
 * @todo is this a duplicate?
 * @see emailaddressformrule
 *
 * @return True if address is _not_ in the database
 * @since 20101011
 */
function uniqueemailaddresscallback($value)
{
  logentry("uniqueemailaddresscallback.0");

  $sql = "select id from member where emailaddress=?";
  $dat = array($value);

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("uniqueemailaddresscallback.1: " . $res->toString());
    return PEAR::raiseError($dbh);
  }

  $res = $dbh->getOne($sql, null, $dat);
  if (PEAR::isError($res))
  {
    logentry("uniqueemailaddresscallback.2: " . $res->toString());
    return PEAR::raiseError($res);
  }
  if ($res === null)
  {
    return True;
  }
  return False;
}

/**
 * update user record in database
 *
 * @since 20111128
 */
function updatemember($memberid, $member)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("updatemember.0: " . $dbh->toString());
    return PEAR::raiseError($dbh);
  }
  $res = $dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_UPDATE, "id={$memberid}");
  if (PEAR::isError($res))
  {
    logentry("updatemember.1: " . $res->toString());
    return PEAR::raiseError($res);
  }
  return $res;
}

/**
 * @since 20111215
 */
function startsession()
{
//  logentry("startsession.50: expire=".var_export(SESSIONCOOKIEEXPIRE, True)." domain=".var_export(SESSIONCOOKIEDOMAIN, True));
  
  session_set_cookie_params(SESSIONCOOKIEEXPIRE, "/", SESSIONCOOKIEDOMAIN, False, True);
  session_set_save_handler("_opensession", "_closesession", "_readsession", "_writesession", "_destroysession", "_gcsession");
  ini_set("session.gc_probability", 100);
  ini_set("session.gc_divisor", 100);
  session_start();

/*
  $currentmember = getcurrentmember();
  if (PEAR::isError($currentmember))
  {
    logentry("startsession.100: " . $currentmember->toString());
    return;
  }

  if ($currentmember === null)
  {
    $lifetime = 0;
  }
  else
  {
    $lifetime = $currentmember["sessiontimeout"];
  }
*/
  $lifetime = 0;
//  logentry("startsession.110: lifetime=".var_export($lifetime, True));

  setcookie(session_name(),session_id(),time()+$lifetime);
  return;
}

function checksession()
{
  return;
}

function endsession()
{
  return;
}

/** 
 * custom session handler open function
 *
 * @since 20111228
 * @access private
 */
function _opensession($path, $name)
{
//  logentry("_opensession.10: path=".var_export($path, True)." name=".var_export($name, True));
  return;
}

/** 
 * custom session handler close function.
 *
 * @since 20111228
 * @access private
 */
function _closesession()
{
//  logentry("_closesession.10: called");
}

/** 
 * custom session handler read function.
 *
 * @since 20111228
 * @access private
 */
function _readsession($id)
{
  if (defined("DEBUGSESSION"))
  {
    logentry("_readsession.10: id=".var_export($id, True));
  }
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("_readsession.12: " . var_export($dbh->toString(), True));
    return False;
  }
  $sql = "select data from engine.session where id=? and expiry >= now()";
  $dat = array($id);
  $res = $dbh->getOne($sql, array("text"), $dat, array("text"));

//  logentry("_readsession.14: res=".var_export($res, True));

  if (PEAR::isError($res))
  {
    if (defined("DEBUGSESSION"))
    {
      logentry("_readsession.16: " . var_export($res->toString(), True));
    }
    return "";
  }
  if ($res === null)
  {
//    logentry("_readsession.18: result is null, returning empty string.");
    return "";
  }
  
//  logentry("_readsession.20: sess=".var_export($res, True));
  return $res;
}

/** 
 * custom session handler write function
 *
 * @since 20111228
 * @access private
 */
function _writesession($id, $data)
{
//  logentry("_writesession.10: id=".var_export($id, True)." data=".var_export($data, True));
//  logentry("_writesession.11: session=".var_export($_SESSION, True));
  
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("_writesession.14: " . $dbh->toString());
    return False;
  }
  $sql = "select 1 from engine.__session where id=?";
  $dat = array($id);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("_writesession.16: " . $res->toString());
    return False;
  }

  $memberid = getcurrentmemberid();

  if ($res === null)
  {
    $expiry = time() + SESSIONCOOKIEEXPIRE;

    $session = array();
    $session["id"] = $id;
    $session["data"] = session_encode();
    $session["expiry"] = date(DATE_RFC822, $expiry);
    $session["ipaddress"] = $_SERVER["REMOTE_ADDR"];
    $session["useragent"] = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
    $session["memberid"] = $memberid;
    $session["datecreated"] = "now()";
    
//    logentry("_writesession.18: new session=".var_export($session, True));

    $res = $dbh->autoExecute("engine.__session", $session, MDB2_AUTOQUERY_INSERT);
    if (PEAR::isError($res))
    {
      logentry("_writesession.20: " . $res->toString());
      return False;
    }

  }
  else
  {
    $session = array();
    $session["data"] = session_encode();
    $session["memberid"] = $memberid;

//    logentry("_writesession.22: update session=".var_export($session, True)." id=".var_export($id, True));
    $res = $dbh->autoExecute("engine.__session", $session, MDB2_AUTOQUERY_UPDATE, "id=".$dbh->quote($id, "text"));
    if (PEAR::isError($res))
    {
      logentry("_writesession.24: ".$res->toString());
      return False;
    }
  
  }
  
  return True;
}

/** 
 * custom session handler destroy function
 *
 * @since 20111228
 * @access private
 */
function _destroysession($id)
{
  logentry("_destroy.10: id=".var_export($id, True));
  return True;
}

/**
 * custom session handler garbage collection function
 *
 * @since 20111228
 * @access private
 */
function _gcsession($maxlifetime)
{
  if (defined("DEBUGSESSION"))
  {
    logentry("_gcsession.10: maxlifetime=".var_export($maxlifetime, True));
  }
  $dbh = dbconnect(SYSTEMDSN);
  $res = $dbh->autoExecute("engine.__session", null, MDB2_AUTOQUERY_DELETE, "expiry < now()");
  if (PEAR::isError($res))
  {
    logentry("_gcsession.10: " . $res->toString());
    return False;
  }
  return True;
}

/**
 * @since 20120507
 */
function buildlabel($buf)
{
  $buf = strtolower($buf);
  // replace anything that is not a-z0-9 with -
  $buf = preg_replace("@[^a-z0-9_]@","_", $buf);

  // replace 2 or more - with single -
  $buf = preg_replace("@[_-]{2,}@", "_", $buf);

  // trim '_' and '.' from both ends
  $buf = trim($buf, "_");
  $buf = trim($buf, ".");
  
  return $buf;
}

/**
 * @deprecated
 */
function buildname($buf)
{
  logentry("buildname.100: renamed to buildlabel");
  return buildlabel($buf);
}

/**
 * @since 20121107
 *
 * transforms the given uri into something that can be used with the 'ltree' type.
 *
 * @param string $name
 * @return string
 */
function buildpath($buf)
{
  if ($buf == "/" || $buf == "")
  {
    return "top";
  }
  $buf = buildlabel($buf);
  $buf = "top.".$buf;

  return $buf;
}

/**
 * builds a uri from a given ltree labelpath
 * 
 * @since 20120507
 * @param labelpath ltree
 * @see buildlabelpath
 *
 */
function builduri($labelpath)
{
  $buf = str_replace("top.", "", $labelpath);
  $buf = str_replace(".", "/", $buf);
  $buf = str_replace("_", "-", $buf);
  $buf = trim($buf);
  $buf = trim($buf, ".");
  $buf .= "/";
  return $buf;
}

/**
 * process an uploaded file
 *
 * rewritten based on code in register.php 2010-10-20
 *
 * changed on 2015-09-01 so it accepts a dictionary (values) as a
 * parameter, because most times this function is called from an update() or
 * insert() function. this change is a COMPAT BUSTER.
 *
 * @since 20100730
 */
function processupload($value, $destinationdir)
{
  if (PEAR::isError($value))
  {
    logentry("processupload.14: " . $value->toString());
    return $value;
  }

  $multiple = is_array($value["tmp_name"]) ? True : False;

  logentry("processupload.16: multiple=".var_export($multiple, True)." value=".var_export($value, True));
  if ($value["error"] === 0)
  {
    $filename = buildlabel(rand(1,999).trim($value["name"]));
    logentry("processupload.18: filename=".var_export($filename, true)." destinationdir=".var_export($destinationdir, true));
    rename($value["tmp_name"], $destinationdir.$filename);
    return $filename;
  }
  logentry("processupload.20: error processing upload. error=".var_export($value["error"], True));
  return null;
}

/**
 * function to determine access to the "member" table
 *
 * @param string
 * @param array
 * @param integer
 * @since 20130225
 */
function accessmember($op, $data=null, $memberid=null)
{
  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }

  $member = isset($data["member"]) ? $data["member"] : null;

  switch ($op)
  {
    case "editcredits":
    {
      if (USEMEMBERCREDITS === False)
      {
        $res = False;
        break;
      }
      if (flag("ADMIN"))
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "detail":
    {
      $res = True;
      break;
    }
    case "changepassword":
    {
      if (flag("AUTHENTICATED") === False)
      {
        $res = False;
        break;
      }
      if (flag("ADMIN", $memberid) === True || ($memberid !== null && $data["id"] == $memberid))
      {
        $res = True;
        break;
      }
      $res = False; 
      break;
    }
    case "add":
    {
     $res = True;
     break;
    }
    case "edit":
    {
      if (flag("ADMIN", $memberid) === True || $data["id"] == $memberid)
      {
        $res = True; 
        break;
      }
      $res = False; 
      break;
    }
    case "editflags":
    {
      if (flag("ADMIN", $memberid) === True)
      {
        $res = True; 
        break;
      }
      $res = False; 
      break;
    }
    case "sendverifyemail":
    {
      if (flag("ADMIN", $memberid) === True)
      {
        $res = True; 
        break;
      }
      $res = False; 
      break;
    }
    default:
    {
      $res = null;
      break;
    }
  }
//  logentry("accessmember.50: op=".var_export($op, True)." member.id=".var_export($data["id"], True)." memberid=".var_export($memberid, True)." res=".var_export($res, True));
  return $res;  
}

/**
 * @since 20131031
 */
function setcurrentsite($site)
{
  $_SESSION["currentsite"] = $site;
//  logentry("setcurrentsite.10: site=".var_export($site, True));
  return;
}

/**
 * @since 20131031
 */
function getcurrentsite()
{
  $site = isset($_SESSION["currentsite"]) ? $_SESSION["currentsite"] : null;
//  logentry("getcurrentsite.10: site=".var_export($site, True));
  return $site;
}

/**
 * function to add a trailing slash if needed. also removes duplicate slashes.
 * @since 20140511
 *
 */
function normalizepath($path)
{
  $path = preg_replace('@[/]{2,}@', '/', $path);
  if (substr($path, -1) != "/")
  {
    $path .="/";
  }
  return $path;
}

if (function_exists("buildnotifytemplatename") === False)
{
  function buildnotifytemplatename($type)
  {
    logentry("engine.buildnotifytemplatename.999: type=".var_export($type, True));
    return null;
  }
}

/**
 * @since 20140513
 * @param type string
 * @param memberid integer
 * @param data dictionary automagically converted to json
 */
function sendnotify($type, $memberid, $data=null)
{
  $notify = array();
  $notify["type"] = $type;
  $notify["memberid"] = $memberid;
  $notify["data"] = encodejson($data);
  $notify["displayed"] = False;
  $notify["datecreated"] = "now()";
  
  $notify["template"] = buildnotifytemplatename($type);

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("sendnotify.100: " . $dbh->toString());
    return $dbh;
  }
  logentry("sendnotify.200: notify=".var_export($notify, True));
  
  $res = $dbh->autoExecute("engine.__notify", $notify, MDB2_AUTOQUERY_INSERT);
  if (PEAR::isError($res))
  {
    logentry("sendnotify.110: " . $res->toString());
    return $res;
  }
  logentry("sendnotify.120: type=".var_export($type, True)." memberid=".var_export($memberid, True));
  return;
}

/**
 * unset the "current member id" for the session aka "logout"
 * 
 * @since 20121018
 */
function clearcurrentmemberid()
{
  logentry("clearcurrentmemberid.10: unsetting session var");
  unset($_SESSION["currentmemberid"]);
  $name = session_name();
  setcookie($name, "", 1);
  setcookie($name, false);
  unset($_COOKIE[$name]);
  return;
}

/** 
 * invalidate session cookie. from {@link https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet#Cookies OWASP Cookies}
 * 
 * @since 20140724
 */
function removesessioncookie()
{
  $name = session_name();
  setcookie($name, "", 1);
  setcookie($name, false);
  unset($_COOKIE[$name]);
  return;
}

/**
 * @since 20140727
 *
 */
function getsig($labelpath)
{
  $sql = "select * from engine.sig where path=?";
  $dat = array($labelpath);
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getsig.110: " . $res->toString());
    return $dbh;
  }

  $res = $dbh->getRow($sql, null, $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("getsig.100: " . $res->toString());
    return $res;
  }
  return $res;
}

// https://github.com/timostamm/NonceUtil-PHP
function generatenonce($secret, $timeoutSeconds=180) 
{
  if (is_string($secret) == false || strlen($secret) < NONCESALTLENGTH) 
  {
    // missing valid secret
    return PEAR::raiseError("generatenonce.100: secret is not a string or it is not of proper length (".var_export(NONCESALTLENGTH, True).")");
  }
  $salt = generatesalt();
  $time = time();
  $maxTime = $time + $timeoutSeconds;
  $nonce = $salt . "," . $maxTime . "," . sha1( $salt . $secret . $maxTime );
  return $nonce;
}

function checknonce($secret, $nonce) 
{
  if (is_string($nonce) == false) 
  {
    return false;
  }
  $a = explode(',', $nonce);
  if (count($a) != 3) 
  {
    return false;
  }
  $salt = $a[0];
  $maxTime = intval($a[1]);
  $hash = $a[2];
  $back = sha1( $salt . $secret . $maxTime );
  if ($back != $hash) 
  {
    return false;
  }
  if (time() > $maxTime) 
  {
    return false;
  }
  return true;
}

function generatesalt() 
{
  $length = NONCESALTLENGTH;
  $chars='1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
  $ll = strlen($chars)-1;
  $o = '';
  while (strlen($o) < $length) 
  {
    $o .= $chars[ rand(0, $ll) ];
  }
  return $o;
}

/**
 * @since 20140805
 */
if (function_exists("buildmemberfieldset") === False)
{
  function buildmemberfieldset($form)
  {
    $fieldset = $form->addFieldset("member");
    $fieldset->setLabel("account");
    
    $name = $fieldset->addText("name");
    $name->setLabel("name");
    $name->addRule("required", "'Name' is a required field");

    $email = $fieldset->addText("email");
    $email->setLabel("e-mail address");
    $email->addRule("required", "'E-Mail address' is a required field.");
    
    $realname = $fieldset->addText("realname");
    $realname->setLabel("real name");
    
    if (accessmember("editcredits"))
    {
      $credits = $fieldset->addText("credits");
      $credits->setLabel("credits");
      $credits->addRule("regex", "'Credits' must be an integer", '/^[0-9]+$/');
    }
    return;
  }
}
/**
 * @since 20140825
 */
function clearpageprotocol()
{
  $_SESSION["pageprotocol"] = "standard";
  logentry("clearpageprotocol.100: cleared");
  return $_SESSION["pageprotocol"];
}

/**
 * @since 20140822
 *
 * @param string $pageproto
 */
function setpageprotocol($pageprotocol)
{
  $_SESSION["pageprotocol"] = $pageprotocol;
  logentry("setpageprotocol.100: pageprotocol=".var_export($pageprotocol, True));
  return;
}

/**
 * @since 20140822
 *
 * @return string $pageproto
 */
function getpageprotocol()
{
  if (isset($_REQUEST["pageprotocol"]))
  {
    $pageprotocol = $_REQUEST["pageprotocol"];
    logentry("getpageprotocol.102: _REQUEST is set to ".var_export($pageprotocol, True));
  }
  elseif (isset($_SESSION["pageprotocol"]))
  {
    $pageprotocol = $_SESSION["pageprotocol"];
//    logentry("getpageprotocol.103: using session ".var_export($pageprotocol, True));
  }
  else
  {
    $pageprotocol = "standard";
//    logentry("getpageprotocol.101: setting protocol to standard");
  }
//  $pageprotocol = isset($_SESSION["pageprotocol"]) ? $_SESSION["pageprotocol"] : "standard";
//  logentry("getpageprotocol.100: pageprotocol=".var_export($pageprotocol, True));
  return $pageprotocol;
}

/**
 * @since 20140829
 * @param ltree $labelpath ltree label path
 * @return ltree labelpath except for last element
 */
function buildparentlabelpath($labelpath)
{
  $res = implode(".",explode(".", $labelpath, -1));
  if (empty($res))
  {
    return "top";
  }
  return $res;
}

if (function_exists("buildmemberrecord") === False)
{
  function buildmemberrecord($values)
  {
    $member = array();
    $member["email"] = $values["email"];
    $member["realname"] = $values["realname"];
    $member["name"] = $values["name"];
    
    if (isset($values["credits"]))
    {
      $member["credits"] = intval($values["credits"]);
    }

    return $member;
  }
}
/**
 * @since 20140901
 * @param $name text handle
 * @return mixed memberid for $name, NULL if name doesn't exist, PEAR::Error for errors
*/
function getmemberid($name)
{
  $sql = "select id from engine.member where name ilike ?";
  $dat = array($name);
  $dbh = dbconnect(SYSTEMDSN);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("getmemberid.100: " . $res->toString());
  }
  if ($res === NULL)
  {
    logentry("getmemberid.102: getmemberid(".var_export($name, True).") returned null");
  }
  return $res;
}

/**
 * @since 20140831
 * @param text $password plain text password i.e. from a quickform
 * @param integer memberid memberid to check against
 * 
 * @see hashpassword()
 */
function checkpassword($password, $memberid)
{
 logentry("checkpassword.100: password=".var_export($password, True)." memberid=".var_export($memberid, True));
 $sql = "select 1 from engine.member where id=? and passwd=?";
 $dat = array(intval($memberid), hashpassword($password));
 $dbh = dbconnect(SYSTEMDSN);
 if (PEAR::isError($dbh))
 {
  logentry("checkpassword.100: " . $dbh->toString());
  return False;
 }
 $res = $dbh->getOne($sql, array("integer"), $dat, array("integer", "text"));
 if (PEAR::isError($res))
 {
  logentry("checkpassword.102: " . $res->toString());
  return False;
 }
 if ($res === 1)
 {
  logentry("checkpassword.106: password correct");
  return True;
 }
 
 $dat = array(intval($memberid), hashpassword($password));
 $res = $dbh->getOne($sql, array("integer"), $dat, array("integer", "text"));
 if (PEAR::isError($res))
 {
  logentry("checkpassword.102: " . $res->toString());
  return False;
 }
 if ($res === 1)
 {
  logentry("checkpassword.106: password correct using crypt check");
  return True;
 }
 return False;
}

/**
 * @since 20141016
 */
function buildnewpasswordfieldset($form)
{
 $fieldset = $form->addElement("fieldset");
 $fieldset->setLabel("Password");
 $newpassword = $fieldset->addElement("password", "password", array("style" => "width: 200px;"))->setLabel("Password:");
 $newpassword->addRule("required", "'Password' is a required field.");
 $repeatpassword = $fieldset->addElement("password", "repeatpassword", array("style" => "width: 200px;"))->setLabel("Repeat password:");
 $repeatpassword->addRule("required", "'PasswordRepeat' is a required field.");
// $newPassword->addRule("nonempty")->and_($repPassword->createRule("nonempty"))->or_($repPassword->createRule("eq", "The passwords do not match", $newPassword));
 $repeatpassword->addRule("eq", "The passwords do not match.", $newpassword);
 return;
}
/**
 * @since 20140831
 */
function buildchangepasswordfieldset($form, $data=array())
{
/*
  $group = $form->addGroup()->setLabel("group");
  $group->addPassword("password")->setLabel("Password");
  $group->addPassword("repeatpassword")->setLabel("Repeat Password");
*/
 $memberid = isset($data["memberid"]) ? intval($data["memberid"]) : null;
 logentry("buildpasswordfieldset.100: memberid=".var_export($memberid, True));
 
 $fieldset = $form->addElement("fieldset")->setLabel("Password");
 $oldPassword = $fieldset->addElement("password", "oldPassword", array("class" => "form-control"))->setLabel("Type your old password");

 $oldPassword->addRule("empty")->or_($oldPassword->createRule("callback", "wrong password", array("callback" => "checkpassword", "arguments" => array($memberid))));
 $newPassword = $fieldset->addElement("password", "newPassword", array("class" => "form-control"))->setLabel("Type your new password");
 $repPassword = $fieldset->addElement("password", "newPasswordRepeat", array("class" => "form-control"))->setLabel("Confirm your new password");

 // this behaves exactly as it reads: either "password" and "password
 // repeat" are both empty or they should be equal

 $newPassword->addRule("empty")->and_($repPassword->createRule("empty"))->or_($repPassword->createRule("eq", "The passwords do not match", $newPassword));

 // Either new password is not given, or old password is required
 $newPassword->addRule("empty")->or_($oldPassword->createRule("nonempty", "Supply old password if you want to change it"));

 //  $newPassword->addRule("minlength", 'The password is too short', 6, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

 // No sense changing the password to the same value
 $newPassword->addRule("nonempty")->and_($newPassword->createRule("neq", "New password is the same as the old one", $oldPassword));

  return;
}

/**
 * @since 20140908
 */
function fetchtopbar($actions=null)
{
  $tmpl = getsmarty();
  $tmpl->assign("actions", $actions);
  $res = $tmpl->fetch("topbar.tmpl");
  return $res;
}

/**
 * @since 20140910
 */
function normalizeuri($uri)
{
 $uri = preg_replace("@(/){2,}@", '$1', $uri);
 return $uri;
}

if (function_exists("checklogin") === False)
{
  /**
   * this function is intended to be used as part of an html_quickform2 callback rule
   *
   * @return boolean
   * @param args dictionary with 'username' and 'password' as keys
   */
  function checklogin($args)
  {
          logentry("checklogin.50: args=".var_export($args, True));

          $name = $args["name"];
          $password = $args["passwd"];
          $id = getmemberidfromusername($name);
          if (PEAR::isError($id))
          {
                  logentry("checklogin.60: ".$id->toString());
                  return False;
          }
          if (checkpassword($password, $id) === True)
          {
                  logentry("checklogin.65: checkpassword passed");
                  return True;
          }
          logentry("checklogin.66: checkpassword failed");
          return False;
  }

}

function buildloginfieldset($form)
{
  $fieldset = $form->addElement("fieldset");
  $name = $fieldset->addElement("text", "name");
  $name->setLabel("Handle");
  $name->addRule("required", "'Handle' is a required field");

  $password = $fieldset->addElement("password", "passwd");
  $password->setLabel("Password");
  $password->addRule("required", "'Password' is a required field");
  
  $fieldset->addRule("callback", "'Handle' or 'Password' incorrect.", "checklogin"); // array("callback" => "checklogin"));
  
  return;
}


/**
 * calls json_encode() with default parameters
 *
 * @since 20140730
 */
function encodejson($data)
{
 // @see http://us3.php.net/manual/en/json.constants.php
 return json_encode($data); // , JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_NUMERIC_CHECK); // JSON_UNESCAPED_UNICODE in 5.4+
}

/**
 * decodes given json data into a dictionary (associative array)
 * 
 * @since 20140730
 */
function decodejson($data)
{
 if (is_string($data) === False)
 {
  logentry("decodejson.100: data=".var_export($data, True));
  return;
 }
 return json_decode($data, True);
}

/**
 * @since 20141223
 */
function getlastlogin()
{
 $lastlogin = isset($_SESSION["lastlogin"]) ? $_SESSION["lastlogin"] : null;
 return $lastlogin;
}

/**
 * @since 20141223
 */
function setlastlogin($lastlogin)
{
 logentry("setlastlogin.100: lastlogin=".var_export($lastlogin, True));

 $_SESSION["lastlogin"] = $lastlogin;
 return;
}

/**
 * @since 20141223
 */
function getlastloginfrom()
{
 $lastloginfrom = isset($_SESSION["lastloginfrom"]) ? $_SESSION["lastloginfrom"] : null;
 return $lastloginfrom;
}

/**
 * @since 20141223
 */
function setlastloginfrom($lastloginfrom)
{
 $_SESSION["lastloginfrom"] = $lastloginfrom;
 return;
}

/**
 * @since 20121017
 *
 * a quickform2 callback to see if the given $value exists in the name field of the member table
 */
function uniqueusernamecallback($value)
{
  logentry("uniqueusernamecallback.0");

  $value = trim($value);
  $value = strip_tags($value);
  $sql = "select 1 from engine.member where name ilike ?";
  $dat = array($value);

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("uniqueusernamecallback.1: " . $res->toString());
    return PEAR::raiseError($dbh);
  }

  $res = $dbh->getOne($sql, null, $dat);
  if (PEAR::isError($res))
  {
    logentry("uniqueusernamecallback.2: " . $res->toString());
    return PEAR::raiseError($res);
  }
  if ($res === null)
  {
    return True;
  }
  return False;
}

/**
 * @since 20150109
 */
function hashpassword($plaintext)
{
  if (defined("CRYPTSALT"))
  {
    logentry("hashpassword.100: using crypt()");
    return crypt($plaintext, CRYPTSALT);
  }
  logentry("hashpassword.110: using md5()");
  return md5($plaintext);
}

function setpassword($memberid, $hashedpassword)
{
 $member = array();
 $member["passwd"] = $hashedpassword;
 
 $dbh = dbconnect(SYSTEMDSN);
 if (PEAR::isError($dbh))
 {
  logentry("setpassword.100: " . $dbh->toString());
  return $dbh;
 }
 $res = $dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_UPDATE, "id=".$dbh->quote($memberid, "integer"));
 if (PEAR::isError($res))
 {
  logentry("setpassword.100: " . $res->toString());
  return $res;
 }
 return;
}

if (function_exists("buildnotifyrecord") === False)
{
 /**
  * @since 20160202
  */
 function buildnotifyrecord($key, $notify)
 {
  switch ($key)
  {
   default:
   {
    logentry("buildnotifyrecord.100: unhandled key '".var_export($key, True)."'");
    break;
   }
  }
 }
}

if (function_exists("buildnotifyactions") === False)
{
 // @since 20130520
 function buildnotifyactions($notify)
 {
   logentry("buildnotifyactions.100: getcurrentaction() == ".var_export(getcurrentaction(), True));
 //    logentry("buildnotifyactions.150: notify=".var_export($notify, True));

   $type = isset($notify["type"]) ? $notify["type"] : null;
   logentry("buildnotifyactions.120: type=".var_export($type, True));

   $data = $notify["data"];
   
   $id = intval($notify["id"]);

   $actions = [];
   if (accessnotify("delete", $notify) === True)
   {
     $actions[] = [ "class" => "fa fa-fw fa-remove", "href" => ENGINEURL."notify-delete-{$id}", "title" => "delete" ];
   }

   if (accessnotify("detail", $notify) === True)
   {
     $actions[] = [ "class" => "fa fa-fw fa-angle-double-down", "href" => ENGINEURL."notify-detail-{$id}", "title" => "detail" ];
   }

   if (accessnotify("edit", $notify) === True)
   {
     $actions[] = [ "class" => "fa fa-fw fa-edit", "href" => ENGINEURL."notify-edit-{$id}", "title" => "edit" ];
   }
   return $actions;
 }
}

function _buildnotifydata($notify)
{
    $type = $notify["type"];

    $data = $notify["data"];

    $dat = array();

    switch($type)
    {
      default:
      {
        logentry("bbsengine3._buildnotifydata: unhandled type ".var_export($type, True));
        break;
      }
    }

    return $dat;
}

if (function_exists("buildnotifydata") === False)
{  
  function buildnotifydata($notify)
  {
    logentry("buildnotifydata.999: using _buildnotifydata");
    return _buildnotifydata($notify);
  }
}

/**
 * @since 20130411
 */
function getnotify($notifyid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getnotify.10: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select * from engine.notify where id=?";
  $dat = array($notifyid);
  $res = $dbh->getRow($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("getnotify.12: " . $res->toString());
    return $res;
  }
  $res["data"] = decodejson($res["data"]);
  return $res;
}

/**
 * @since 20130401
 */
function gettotalnotifications($id)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("gettotalnotifications.10: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from engine.notify where memberid=?";
  $dat = array($id);
  $res = $dbh->getOne($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("gettotalnotifications.12: " . $res->toString());
    return $res;
  }
  if ($res === null)
  {
    return 0;
  }
  return intval($res);
}

/**
 * @since 20130401
 */
function gettotalunreadnotifications($id)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("gettotalunreadnotifications.10: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from engine.notify where memberid=? and datereadepoch is null and detailtemplate <> ''";
  $dat = array($id);
  $res = $dbh->getOne($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("gettotalunreadnotifications.12: " . $res->toString());
    return $res;
  }
  if ($res === null)
  {
    return 0;
  }
  return intval($res);
}

/**
 * @since 20130411
 */
function accessnotify($op, $notify=null, $memberid=null)
{
  $currentmemberid = getcurrentmemberid();
  $memberid = intval($notify["memberid"]);

  logentry("accessnotify.42: op=".var_export($op, True)." currentmemberid=".var_export($currentmemberid, True)." memberid=".var_export($memberid, True));

  switch ($op)
  {
    case "edit":
    {
      if (flag("ADMIN") === True)
      {
        return True;
      }
      return False;
    }
    case "detail":
    {
      if ($notify["template"] == "")
      {
        return False;
      }
      if (flag("AUTHENTICATED") === True && ($memberid === $currentmemberid || flag("ADMIN") === True))
      {
        return True;
      }
      return False;
      
    }
    case "view":
    case "delete":
    {
      if (flag("AUTHENTICATED") === True && ($memberid === $currentmemberid || flag("ADMIN") === True))
      {
        return True;
      }
      return False;
    }
    break;
  }
  return False;
}

/**
 * @since 20150410
 */
function getmemberidfromemail($email)
{
  $sql = "select id from engine.member where email=?";
  $dat =  array($email);
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getmemberidfromemail.100: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->getOne($sql, array("integer"), $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("getmemberidfromemail.110: " . $res->toString());
    return $res;
  }
  if ($res === null)
  {
    logentry("getmemberidfromemail.120: query returned null");
    return PEAR::raiseError("Unable to get memberid from email address (code: getmemberidfromemail.120)");
  }
  return intval($res);
}

/**
 * @since 20150420
 */
function getmemberidfrom($name, $value, $type)
{
  logentry("getmemberidfrom.105: name=".var_export($name, True)." value=".var_export($value, True)." type=".var_export($type, True));

  $sql = "select id from engine.member where {$name}=?";
  $dat =  array($value);
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getmemberidfrom.100: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->getOne($sql, array("integer"), $dat, array($type));
  if (PEAR::isError($res))
  {
    logentry("getmemberidfrom.110: " . $res->toString());
    return $res;
  }
  
  return $res;
  
}

function getmemberidfromusername($username)
{
  $res = getmemberidfrom("name", $username, "text");
  if (PEAR::isError($res))
  {
    logentry("getmemberidfromusername.100: " . $res->toString());
  }
  return $res;
  
}

if (function_exists("buildbuttonfieldset") === False)
{
  /**
   * @since 20150418
   */
  function buildbuttonfieldset($form, $submitlabel="NEEDINFO", $attributes=null)
  {
    if (is_array($attributes))
    {
      $attributes["value"] = $submitlabel;
    }
    else
    {
      $attributes = array("value" => $submitlabel);
    }
    $fs = $form->addElement("fieldset");
    $el = $fs->addElement("submit", "submit", $attributes);
    return;
    
/*
    $buttons = array();
    
    $buttons[] = &HTML_QuickForm2_Factory::createElement("submit", null, $submitlabel, array("id" => "submitform"));
    $form->addGroup($buttons);
  //    $form->addElement("image", "clicktocontinue", "/images/clicktocontinue.png");

    return;
*/
  }
}

function toboolean($field)
{
//  logentry("toboolean.100: field=".var_export($field, True));
  
  if (is_bool($field) === True)
  {
//    logentry("toboolean.100: field is already a boolean value");
    return $field;
  }

  if ($field === "t" || (isset($field) && empty($field)))
  {
//    logentry("toboolean.110: returning true");
    return True;
  }
//  logentry("toboolean.112: returning false");
  return False;
}

/**
 * handle a form given an html_quickform2 instance, a callback function and a page title. if the form validates, the callback function is called. 
 * 
 * @param form html_quickform2 instance
 * @param callback function/method to call if form validates
 * @param pagetitle string page title
 * @return whatever value the callback returns. 'True' should be used for 'success'.
 * @since 20150408
 */
function handleform($form, $callback)
{
  $issubmitted = $form->isSubmitted();
  $validate = $form->validate();

  logentry("handleform.100: issubmitted=".var_export($issubmitted, True)." validate=".var_export($validate, True));
  
  if ($issubmitted === True)
  {
    $value = $form->getValue();
    $pageprotocol = isset($value["pageprotocol"]) ? $value["pageprotocol"] : "standard";
    setpageprotocol($pageprotocol);
  }
  else
  {
    $pageprotocol = clearpageprotocol();
  }

  if ($issubmitted === True && $validate === True)
  {
    foreach ($form->getElements() as $element)
    {
      // @FIX: handle nested fieldsets
      logentry("handleform.200: inside foreach. class=".var_export(get_class($element), True));
      if ($element instanceof HTML_QuickForm2_Element_Captcha)
      {
        logentry("handleform.210: clearing captcha session");
        $element->clearCaptchaSession();
      }
    }

    logentry("handleform.110: form validated");
    
    $form->toggleFrozen(True);
// now done in getquickform()
//    $form->addRecursiveFilter("trim");
    $values = $form->getValue();
    logentry("handleform.120: values=".var_export($values, True));
    if (is_callable($callback) === True)
    {
      logentry("handleform.150: calling form callback with form values");
      $res = call_user_func($callback, $values);
    }
    else
    {
      logentry("handleform.140: callback is not callable!");
      $res = null;
    }
    if (PEAR::isError($res))
    {
      logentry("handleform.130: " . $res->toString());
    }
    return $res;
  }

/*
  $renderer = getquickformrenderer(); 
  $form->render($renderer);
  $rendered = $renderer->toArray();

  $tmpl = getsmarty();
  $tmpl->assign("form", $rendered);

  $bodycontent = array();
  $bodycontent[] = fetchpageheader($pagetitle);
  $bodycontent[] = fetchtopbar();
  $bodycontent[] = fetchsidebar();
  $bodycontent[] = $tmpl->fetch($formtemplate);
  $bodycontent[] = fetchpagefooter();

  $page = getpage($pagetitle);
  $page->addScript(STATICJAVASCRIPTURL."form.js");
  $page->addStyleSheet(STATICSKINURL . "css/form.css");
  $page->addBodyContent($bodycontent);
  $res = displaypage($page);
*/
  return False;
}

/**
 * @since 20150903
 */
function getmembercredits($id)
{
  $sql = "select credits from engine.member where id=?";
  $dat = array($id);
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getmembercredits.100: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->getOne($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("getmembercredits.100: " . $res->toString());
    return $res;
  }
  return $res;
}

/**
 * @since 20150903
 */
function setmembercredits($id, $credits)
{
  $member = array();
  $member["credits"] = intval($credits);
  $res = updatemember($id, $member);
  if (PEAR::isError($res))
  {
    logentry("setmembercredits.100: " . $res->toString());
    return $res;
  }
  return;
}

/**
 * @since 20150903
 */
function getcurrentmembercredits()
{
  $id = getcurrentmemberid();
  return getmembercredits($id);
}

/**
 * @since 20150903
 */
function setcurrentmembercredits($credits)
{
  $id = getcurrentmemberid();
  return setmembercredits($id, $credits);
}

/**
 * @since 20150929
 */
function _displaypage($page, $options=null)
{
  $pageprotocol = isset($options["pageprotocol"]) ? $options["pageprotocol"] : getpageprotocol();

  $format = isset($options["format"]) ? $options["format"] : "json";
  $delay = isset($options["redirect"]["delay"]) ? $options["redirect"]["delay"] : 0;
  $url = isset($options["redirect"]["url"]) ? $options["redirect"]["url"] : "";

  logentry("_displaypage.100: pageprotocol=".var_export($pageprotocol, True)." format=".var_export($format, True));

  if ($pageprotocol === "standard")
  {
    $statuscode = isset($options["statuscode"]) ? $options["statuscode"] : 200;
    if ($delay > 0)
    {
      $page->setMetaRefresh($delay, $url);
    }
    header("error", True, $statuscode);

/*
    // Check if browse can take the proper mime type
    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
    {
      $page->setDoctype('XHTML 1.0 Strict');
      $page->setMimeEncoding('application/xhtml+xml');
    } 
    else 
    {
     $page->setDoctype('XHTML 1.0 Transitional');
    }
*/
    $page->disableXmlProlog();
    $page->display();
    return;
  }

  $body = $page->getBodyContent();
  $page = [];
  $page["body"] = $body;
  if ($delay > 0)
  {
    $page["refresh"]["delay"] = $delay;
    $page["refresh"]["url"] = $url;
  }
  
  $encode = encodejson($page);

  if ($pageprotocol === "enhanced")
  {
    header("Content-Type: application/json; charset=utf-8");

    if ($format === "json")
    {
      print $encode;
      return;
    }
    elseif ($format === "jsonp")
    {
      $callback = isset($_REQUEST["callback"]) ? $_REQUEST["callback"] : null;
      print "{$callback}({$encode});";
      return;
    }
    else
    {
      print "WTF?";
    }
  }
  print "pageprotocol unknown";
  return;
}

if (function_exists("displaypage") === False)
{
  /**
    * @since 20151203
    *
    */
  function displaypage($page, $options=null)
  {
    $pagedata = isset($options["pagedata"]) ? $options["pagedata"] : null;
    if ($pagedata === null)
    {
      // for backwards compat
      logentry("displaypage.100: pagedata is null");
    }
    else
    {
      $template = isset($pagedata["template"]) ? $pagedata["template"] : "page.tmpl";
      logentry("displaypage.110: using pagedata");
      $tmpl = getsmarty();
      foreach ($pagedata as $key => $value)
      {
        $tmpl->assign($key, $value);
      }
      $page->setBody($tmpl->fetch($template));
    }
    return _displaypage($page, $options);
  }
}

/**
 * function which accepts a renderer instance converted to an array, a page
 * title, and an optional template name.  it composes an html_page2 instance
 * and calls displaypage() which is part of bbsengine3
 *
 * @param html_quickform2 $form
 * @param string $title
 * @param string $template
 * @since 20150629
 */
function displayform($renderer, $title, $options=array())
{
//  logentry("displayform.100: enter");
  
//  logentry("displayform.150: bodycontent=".var_export($bodycontent, True));
  
  $page = getpage($title);
  if (isset($options["stylesheets"]))
  {
    foreach ($options["stylesheets"] as $stylesheet)
    {
      $page->addStyleSheet($stylesheet);
    }
  }
  
  $template = isset($options["template"]) ? $options["template"] : "form.tmpl";

  $tmpl = getsmarty();
  $tmpl->assign("form", $renderer->toArray());
  $options = [];
  $options["pagedata"]["topbar"] = []; // @FIX do not hard-code layout by assuming a topbar
  $options["pagedata"]["body"] = $tmpl->fetch($template);
//  $page->addScript(STATICJAVASCRIPTURL."form.js", ["execute" => "defer"]);
  $page->addStyleSheet(STATICSKINURL . "css/form.css");
  
  displaypage($page, $options);
}

/**
 *
 * given one or more URIs (the function has a variable number of arguments),
 * compose a proper labelpath, calling normalizelabelpath() at the end.
 *
 * @since 20120312
 * re-written 20160115
 */
function buildlabelpath()
{
  $argv = func_get_args();
  $argc = func_num_args();
  
  $teospath = parse_url(TEOSURL, PHP_URL_PATH);
  if ($teospath === null)
  {
      return PEAR::raiseError("unable to parse url (code: buildlabelpath.100)");
  }
  $teospath = ltrim($teospath, "/");
  logentry("buildlabelpath.120: teospath=".var_export($teospath, True));

  $foo = [];
  
  foreach ($argv as $arg)
  {
   $explode = explode("/", $arg);

   $uripath = parse_url($arg, PHP_URL_PATH);

   $count = 1;
   $res = str_replace($teospath, "", $uripath, $count);

     $fragments = explode("/", $res);
     foreach ($fragments as $fragment)
     {
      $foo[] = buildlabel($fragment);
     }
   
  }
  $foo = array_filter($foo);
  
  $path = implode($foo, ".");
   
  $path = normalizelabelpath($path);
  return $path;
}


/**
 * @since 20140903
 * @param string $labelpath
 * @return string 
*/
function normalizelabelpath()
{
 $argc = func_num_args();
 $argv = func_get_args();

 $foo = array();
 
 foreach ($argv as $arg)
 {
  $labels = explode(".", $arg);
  foreach ($labels as $label)
  {
   $foo[] = buildlabel($label);
  }
 }
 $foo = array_filter($foo);
// logentry("normalizelabelpath.102: foo=".var_export($foo, True));
 if (count($foo) > 0 && $foo[0] !== "top")
 {
  array_unshift($foo, "top");
 }
 $res = implode(".", $foo);
 if ($res === "")
 {
  $res = "top";
 }
// logentry("normalizepath.100: res=".var_export($res, True));
 return $res;
}

/**
 * build a fieldset for handling the 'sig' table
 *
 * @since 20140730
 */
function buildsigfieldset($form)
{
  $form->addHidden("uri");
  $form->addHidden("id");
  $fieldset = $form->addElement("fieldset")->setLabel("Folder");
  $fieldset->addText("title", "size=60")->setLabel("Title")->addRule("required", "'Title' is a required field");
  $fieldset->addText("parentlabelpath", "size=60")->setLabel("Parent Path");
  
  $fieldset->addText("name", "size=60")->setLabel("Name")->addRule("required", "'Parent Path' is a required field");
  $fieldset->addElement("textarea", "intro", array("cols" => 50, "rows" => 7))->setLabel("Introduction");

  return;
} 



if (function_exists("accesssig") === False)
{
  /**
   * @since 20160202
   * @return boolean
   * @param string op delete, edit, add, view
   * @param dictionary sig dictionary containing a sig record
   * @param integer memberid memberid to check or null to use currentmemberid
   */
  function accesssig($op, $sig=null, $memberid=null)
  {
    if ($memberid === null)
    {
      $memberid = getcurrentmemberid();
    }

    switch ($op)
    {
      case "delete":
      case "edit":
      case "add":
      {
        if (flag("ADMIN", $memberid))
        {
          $res = True;
          break;
        }
        $res = False;
        break;
      }
      case "view":
      {
        $res = True;
        break;
      }
      default:
      {
        logentry("accesssig.100: unknown op ".var_export($op, True));
        $res = PEAR::raiseError("unknown mode (code: accesssig.100)");
        break;
      }
    }
    logentry("accesssig.120: op=".var_export($op, True)." res=".var_export($res, True));
    return $res;
  }

}

if (function_exists("buildsigactions") === False)
{
  /**
   * @since 20150203
   */
  function buildsigactions($sig)
  {
    $uri = isset($sig["uri"]) ? $sig["uri"] : null;
    $labelpath = isset($sig["path"]) ? $sig["path"] : null;
    
    $currentmemberid = getcurrentmemberid();

    $actions = [];
    if (accesssig("edit") === True)
    {
      $actions[] = ["href" => TEOSURL . $uri . "edit-sig", "title" => "edit sig", "class" => "fa fa-edit fa-fw"];
    }
    if (accesssig("addlink") === True)
    {
      $actions[] = ["href" => TEOSURL . $uri . "add-link", "title" => "add link", "class" => "fa fa-plus fa-fw"];
    }

    return $actions;
  }
}

/**
 * @since 20151118
 */
function buildpagerinfo($sql, $params)
{
//  logentry("buildpagerinfo.100: params=".var_export($params, True));

  $pager = Pager::factory($params);
        
  $pageinfo = array();
  $pageinfo["totalItems"] = $params["totalItems"];
  $pageinfo["range"] = $pager->range;
  $pageinfo["numPages"] = $pager->numPages();
  list($pageinfo["from"], $pageinfo["to"]) = $pager->getOffsetByPageId();

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("buildpagerinfo.110: " . $dbh->toString());
    return $dbh;
  }

  logentry("buildpagerinfo.200: sql=".var_export($sql, True)." pageinfo=".var_export($pageinfo, True));
  
  $qry = $dbh->limitQuery($sql, null, $params["perPage"], $pageinfo["from"]-1);
  if (PEAR::isError($qry))
  {
    logentry("buildpagerinfo.112: " . $qry->toString());
    return $qry;
  }

  $filterfunction = isset($params["filterfunction"]) ? $params["filterfunction"] : null;

  $pageinfo["data"] = [];
  while ($row = $qry->fetchRow())
  {
    if (is_callable($filterfunction) === True)
    {
      $foo = call_user_func($filterfunction, $row);
      if (PEAR::isError($foo))
      {
        logentry("buildpagerinfo.114: " . $foo->toString());
        continue;
      }
      if ($foo === False)
      {
        continue;
      }
    }
    $pageinfo["data"][] = $row;
  }
  $qry->free();
  $pageinfo["links"] = $pager->getLinks();
  
  return $pageinfo;
}

/**
 * return a list of dictionaries with keys 'title' and 'uri' for each part of $sigpath (ltree)
 *
 * @since 20151118
 */
function buildbreadcrumbs($sigpath)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("buildbreadcrumbs.10: " . $dbh->toString());
    return PEAR::raiseError($dbh);
  }
  $sql = "select title, path as labelpath, url from engine.sig where path @> ? order by path asc";
  $dat = [$sigpath];
  $res = $dbh->getAll($sql, null, $dat);
  if (PEAR::isError($res))
  {
    logentry("buildbreadcrumbs.12: " . $res->toString());
    return PEAR::raiseError($res);
  }
  
  $crumbs = [];
  foreach ($res as $rec)
  {
//    $labelpath = $rec["labelpath"];
//    $uri = $rec["uri"]; // builduri($labelpath);
/*    
    $uri = $labelpath;
    $uri = str_replace("top.", "", $uri);
    $uri = str_replace(".", "/", $uri);
    $uri = str_replace("_", "-", $uri);
    $uri .= "/";
*/
/*
    $crumb = array();
    $crumb["title"] = $rec["title"];
    $crumb["uri"] = $uri;
    $crumb["labelpath"] = $labelpath;
*/    
    $crumbs[] = $rec;
  }

//  logentry("buildbreadcrumbs.200: crumbs=".var_export($crumbs, True));
  return $crumbs;
}

/**
 * get sigid for a given labelpath
 *
 * @since 20140713
 * moved from zoidweb2 2015nov22
 */
function getsigidfromlabelpath($labelpath)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getsigidfromlabelpath.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select id from engine.sig where path=?";
  $dat = array($labelpath);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("getsigidfromlabelpath.110: " . $res->toString());
    return $res;
  }
  return $res;
}

/**
 * @since 20151204
 */
function getcurrentpath($uri=null)
{
  if ($uri === null)
  {
    $uri = getcurrenturi();
  }
  $path = parse_url($uri, PHP_URL_PATH);
  if (substr($path, -1) !== "/")
  {
    $path .= "/";
  }
  
  return $path;
}

/**
 * @since 20160430
 */
function cartidexists($cartid)
{
  $sql = "select 1 from cart where id=?";
  $dat = [$cartid];
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("cartidexists.100: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->getOne($sql, ["integer"], $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("cartidexists.100: " . $res->toString());
    return $dbh;
  }
  if ($res == True)
  {
    return True;
  }
  return False;
}

/**
 * @since 20151214
 */
function getcurrentcart()
{
  $cartid = getcurrentcartid();
  if (PEAR::isError($cartid))
  {
    logentry("getcurrentcart.120: " . $cartid->toString());
    return $cartid;
  }
//  logentry("getcurrentcart.200: cartid=".var_export($cartid, True));
  $cart = getcart($cartid);
  if (PEAR::isError($cart))
  {
    logentry("getcurrentcart.140: " . $cart->toString());
    return $cart;
  }
  if ($cart === null)
  {
//    logentry("getcurrentcart.145: getcart(".var_export($cartid, True).") returned null");
    return null;
  }
//  logentry("getcurrentcart.150: cart=".var_export($cart, True));
/*
  $cartitems = getcartitems($cartid);
  if (PEAR::isError($cartitems))
  {
    logentry("getcurrentcart.160: " . $cartitems->toString());
    return $cartitems;
  }
  $cart["items"] = $cartitems;
*/
  return $cart;
}

function getcurrentcartid()
{
  $id = isset($_SESSION["cartid"]) ? intval($_SESSION["cartid"]) : null;
  if (cartidexists($id) === True)
  {
    return $id;
  }
  return null;
}

function setcurrentcartid($cartid)
{
//  logentry("setcurrentcartid.100: cartid=".var_export($cartid, True));
  $_SESSION["cartid"] = $cartid;
  return;
}

/**
 * @since 20151214
 */
/*
function addcartitem($item)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
      logentry("addcartitem.102: " . $dbh->toString());
      return PEAR::raiseError("database connect error (code: addcartitem.102)");
  }
  if ($dbh === null)
  {
      logentry("addcartitem.103: dbh is null");
      return PEAR::raiseError("database connect error (code: addcartitem.103)");
  }
  logentry("addcartitem.152: about to insert cart record");
  $res = $dbh->autoExecute("__cart", $item, MDB2_AUTOQUERY_INSERT);
  if (PEAR::isError($res))
  {
      logentry("addcartitem.100: " . $res->toString());
      return PEAR::isError("database insert error (code: addcartitem.100)");
  }

  $cart = getcurrentcart();
  $cart[] = $item;
  setcurrentcart($cart);
  return;
}
*/

/** 
 * @since 20160503
 */
function clearcart($cartid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("clearcart.100: " . $dbh->toString());
    return $dbh;
  }
  $res = $dbh->autoExecute("__cartitem", null, MDB2_AUTOQUERY_DELETE, "cartid=".$dbh->quote($cartid, "integer"));
  if (PEAR::isError($res))
  {
    logentry("clearcart.110: " . $res->toString());
    return $res;
  }
  return;
}

/**
 * @since 20151214
 */
function clearcurrentcart()
{
  $cartid = getcurrentcartid();
  $res = clearcart($cartid);
  if (PEAR::isError($res))
  {
    logentry("clearcurrentcart.100: " . $res->toString());
    return $res;
  }
  $currentcart = getcurrentcart();
  if (PEAR::isError($currentcart))
  {
    logentry("clearcurrentcart.120: " . $currentcart->toString());
    return $currentcart;
  }
  $currentcart["items"] = [];
  setcurrentcart($currentcart);
  
  return;
}

/**
 * @since 20151214
 */
function normalizecart()
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("normalizecart.100: " . $dbh->toString());
    return $dbh;
  }
  $currentcart = getcurrentcart();
  if ($currentcart === null)
  {
    logentry("normalizecart.110: current cart is null");
    return null;
  }
  $cart = [];
  foreach ($currentcart as $cartitem)
  {
    $title = $cartitem["title"];
    $quantity = $cartitem["quantity"];
    $price = $cartitem["price"];
    logentry("normalizecart.118: title=".var_export($title, True)." price=".var_export($price, True));
    if ((in_array($title, $cartitem) && $cartitem["title"] === $title) &&
      (in_array($price, $cartitem) && $cartitem["price"] === $price))
    {
      logentry("normalizecart.120: title and price match");
      continue;
    }
    logentry("normalizecart.122: no match.");
    $cart[] = $cartitem;
  }
  return $cart;
}

/**
 * @since 20151214
 */
function setcurrentcart($cart)
{
  $_SESSION["cart"] = $cart;
  return;
}

/**
 * @since 20151220
 */
function getcart($cartid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getcart.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select * from cart where id=?";
  $dat = [$cartid];
  $res = $dbh->getRow($sql, null, $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("getcart.110: ". $res->toString());
    return $res;
  }
  $items = getcartitems($cartid);
  if (PEAR::isError($items))
  {
    logentry("cart.120: " . $items->toString());
    return $items;
  }
  $res["items"] = $items;
  
  $actions = buildcartactions(["cartid" => $cartid, "cart" => $res]);
  $res["actions"] = $actions;
  return $res;
}

/**
 * @since 20151222
 */
function buildcartrecord()
{
  $currentmemberid = getcurrentmemberid();
  
  $cart = [];
  $cart["memberid"] = $currentmemberid;
  $cart["datecreated"] = "now()";
  $cart["datemodified"] = "now()";
  $cart["createdbyid"] = $currentmemberid;
  $cart["modifiedbyid"] = $currentmemberid;
  $cart["sessionid"] = session_id();
  
  return $cart;
}

/**
 * @since 20160411
 */
if (function_exists("getcartitems") === False)
{
  function getcartitems($cartid)
  {
    logentry("getcartitems.140: call getcartitems(".var_export($cartid, True).")");
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
      logentry("getcartitems.100: " . $dbh->toString());
      return $dbh;
    }
    $sql = "select * from cartitem where cartid=?";
    $dat = [$cartid];
    $res = $dbh->getAll($sql, null, $dat, ["integer"]);
    logentry("getcartitems.120: res=".var_export($res, True)." count=".count($res), True);
    if (PEAR::isError($res))
    {
      logentry("getcartitems.110: " . $res->toString());
    }
    return $res;
  }
}

/**
 * @since 20160429
 */
function accesscartitem($op, $cartitem=null)
{
  switch ($op)
  {
    case "delete":
    {
	$res = True;
	break;
    }
    case "edit":
    {
      if (flag("ADMIN") === True)
      {
        $res = True;
        break;
      }
      $res = False;
    }
  }
  return $res;
}
/**
 * @since 20160429
 */
function buildcartitemactions($data=[])
{
  $cartitemid = isset($data["cartitemid"]) ? intval($data["cartitemid"]) : null;
  $cartitem = isset($data["cartitem"]) ? $data["cartitem"] : null;

  $actions = [];
/*
  if (accesscartitem("edit", $cartitem) === True)
  {
    $actions[] = ["href" => "/edit-cartitem-{$cartitemid}", "title" => "edit item", "class" => "fa fa-edit fa-fw"];
  }
*/
/*
  if (accesscartitem("delete", $cartitem) === True)
  {
    $actions[] = ["href" => "/delete-cartitem-{$cartitemid}", "title" => "delete item", "class" => "fa fa-remove fa-fw"];
  }
*/
  return $actions;
}

/** 
 * @since 20160502
 */
function accesscart($op, $data=null)
{
  $currentmemberid = getcurrentmemberid();
  $currentsessionid = session_id();
  
  $cart = $data["cart"];
  
  switch ($op)
  {
    case "clear":
    {
      if (flag("ADMIN") === True)
      {
        $res = True;
        break;
      }
      if (iscartowner($cart) === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "view":
    {
      if (flag("ADMIN") === True)
      {
        $res = True;
        break;
      }
      if (iscartowner($cart) === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    default:
    {
      $res = null;
      break;
    }
  }
  return $res;
}

if (function_exists("buildcartactions") === False)
{
  /**
   * @since 20160429
   */
  function buildcartactions($data)
  {
    $cartid = $data["cartid"];
    $cart = $data["cart"];
    $actions = [];
    if (accesscart("clear", ["cart" => $cart]) === True)
    {
      $actions[] = ["href" => "/cart-clear-{$cartid}", "title" => "clear cart", "class" => "fa fa-remove fa-fw"];
    }
    return $actions;
  }
}

/**
 * moved from zoidweb2 to bbsengine
 * 
 * this function will update a sig mapping given the table name, the name of the id field, and the value of the idfield.
 *
 * @since 20151114
 * @param sigs string|array if string, call explodesiglabelpaths()
 * @param tablename name of mapping table
 * @param idfield name of id field marked unique in the db (example: "linkid")
 * @param id the actual id of the item (example: 42)
 * @return null if all is well else PEAR::Error
 */
function updatesigmap($sigs, $tablename, $idfield, $id, $ltreefield="siglabelpath")
{
  logentry("updatesigmap.100: sigs=".var_export($sigs, True)." tablename=".var_export($tablename, True)." idfield=".var_export($idfield, True)." id=".var_export($id, True));
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("updatesigmap.110: ".$dbh->toString());
    return $dbh;
  }

//  $res = $dbh->beginTransaction();
  if (is_string($sigs) === True)
  {
    $sigmap = explodesiglabelpaths($sigs);
  }
  else if (is_array($sigs))
  {
    $sigmap = $sigs;
  }

  $res = $dbh->autoExecute($tablename, null, MDB2_AUTOQUERY_DELETE, "{$idfield}=".$dbh->quote($id, "integer"));
  if (PEAR::isError($res))
  {
    logentry("updatesigmap.120: ". $res->toString());
    $dbh->rollback();
    return $res;
  }

  foreach ($sigmap as $sig)
  {
    $map = [];
    $map[$idfield] = $id;
    $map[$ltreefield] = normalizelabelpath($sig);
    logentry("updatesigmap.130: map=".var_export($map, True)); 
    $res = $dbh->autoExecute($tablename, $map, MDB2_AUTOQUERY_INSERT);
    if (PEAR::isError($res))
    {
      logentry("updatesigmap.140: " . $res->toString());
//      $res = $dbh->rollback();
      continue;
    }
  }
//  $dbh->commit();
  logentry("updatesigmap.150: sigs updated");
  return;
}

/**
 * @since 20160206
 */
function getsigmap($maptablename, $idfield, $id, $ltreefield="siglabelpath")
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getsigmap.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select {$ltreefield} from {$maptablename} where {$idfield}=?";
  $dat = [$id];
  $res = $dbh->getAll($sql, ["text"], $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("getsigmap.110: " . $res->toString());
    return $res;
  }
  $sigmap = [];
  foreach($res as $rec)
  {
    $sigmap[] = $rec[$ltreefield];
  }
  return $sigmap;
}

function implodesigmap($sigmap)
{
//  logentry("implodesigmap.100: sigmap=".var_export($sigmap, True));

  $sigmap = array_filter($sigmap);
  $sigmap = array_map("trim", $sigmap);
  $sigmap = array_unique($sigmap, SORT_STRING);

  return implode(", ", $sigmap);
}

/**
  * accepts a string of comma separated label paths and returns an array.
  * @return array of siglabelpaths
  * @since 20150703
  * @deprecated
  */
function explodesigmap($sigmap)
{
  logentry("explodesigmap.100: deprecated. forwarded to explodesiglabelpaths()");
  return explodesiglabelpaths($sigmap);
  
    $sigmap = explode(",", $sigmap);
    $sigmap = array_filter($sigmap);
    $sigmap = array_map("trim", $sigmap);
    $sigmap = array_unique($sigmap, SORT_STRING);
    return $sigmap;
}

/**
 * @since 20160207
 */
function explodesiglabelpaths($siglabelpaths)
{
//  logentry("explodesiglabelpath.100: renamed to explodesigmap");
//  return explodesigmap($siglabelpath);
  
    $sigmap = explode(",", $siglabelpaths);
    $sigmap = array_filter($sigmap);
    $sigmap = array_map("trim", $sigmap);
    $sigmap = array_unique($sigmap, SORT_STRING);
    return $sigmap;
}

if (function_exists("buildaccountfieldset") === False)
{
  function buildaccountfieldset($form, $options)
  {
    return;
  }
}

/**
 * @since 20160319
 */
function explodeuri($uri)
{
  $foo = explode("/", $uri);
  $foo = array_filter($foo);
  $foo = array_map("trim", $foo);
  return $foo;
}

/**
 * @since 20160427
 */
function getcurrentsig()
{
  $currentsig = isset($_SESSION["currentsig"]) ? $_SESSION["currentsig"] : null;
  return $currentsig;
}

/**
 * @since 20160427
 */
function setcurrentsig($sig=null)
{
  $_SESSION["currentsig"] = $sig;
  return;
}

/**
 * @since 20160502
 */
function cartitemexists($trailerid)
{
  $currentcart = getcurrentcart();
  
  logentry("cartitemexists.100: trailerid=".var_export($trailerid, True));
  $items = $cart["items"];
  foreach ($items as $item)
  {
    if ($item["items"]["trailerid"] === $trailerid)
    {
      logentry("cartitemexists.110: found trailerid");
      return True;
    }
  }
  logentry("cartitemexists.120: trailerid not found");
  return False;
}

function iscartowner($cart)
{
  $currentmemberid = getcurrentmemberid();
  
  if ($cart["memberid"] === $currentmemberid && flag("AUTH") === True)
  {
    return True;
  }
  if ($cart["sessionid"] === session_id())
  {
    return True;
  }
  return False;
}

function buildbutton($form, $name, $data)
{
  $value = isset($data["value"]) ? $data["value"] : null;
  $form->addElement("button", $name, $value);
  return;
}
?>

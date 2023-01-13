<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

/**
 * this module accepts a username and password and validates it against the postgresql database.
 *
 * @copyright (C) 2007-2016 {@link http://zoidtechnologies.com/ Zoid Technologies} All Rights Reserved.
 * @package bbsengine3
 */
class login
{
  /**
   * @since 20150420
   */
  function checklogin($args)
  {
          logentry("checklogin.50: args=".var_export($args, True));

          $email = $args["email"];
          $password = $args["password"];
          $id = getmemberidfromemail($email);
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

  /**
   * wrapper which validates credentials (username/password) passed via quickform.
   *
   * @return none
   */
  function validate($values)
  {
    $name = isset($values["name"]) ? $values["name"] : null;
    $password = isset($values["passwd"]) ? $values["passwd"] : null;

    $res = $this->_validate($name, $password);
    if (PEAR::isError($res))
    {
      displayerrorpage($res->getMessage());
      return False;
    }
    return $res;
  }

  function _validate($username, $password)
  {
    logentry("login.100: username=".var_export($username, true)." passwd=".var_export($password, true));
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
      displayerrorpage("database error (code: login.102)");
      logentry("login.102: " . $dbh->toString());
      return False;
    }

    $memberid = getmemberid($username);
    if (PEAR::isError($memberid))
    {
      logentry("login.104: " . $memberid->toString());
      return PEAR::raiseError("Invalid Login (code: login.104)");
    }

    if (checkpassword($password, $memberid) === False)
    {
      displayerrorpage("invalid login or password");
      logentry("login.106: wrong password. username=".var_export($username, True).", password=".var_export($password, True));
      return False;
    }

    // @since 20141223
    $member = getmember($memberid);
    if (PEAR::isError($member))
    {
      logentry("login.200: " . $member->toString());
      return $member;
    }

    setlastlogin($member["lastlogin"]);
    setlastloginfrom($member["lastloginfrom"]);
    
    $member = array();
    $member["lastlogin"] = "now()";
    $member["lastloginfrom"] = $_SERVER["REMOTE_ADDR"];
    $res = $dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_UPDATE, "id=" . $dbh->quote($memberid, "integer"));
    if (PEAR::isError($res))
    {
      displayerrorpage("database error (login.108)");
      logentry("login.108: " . $res->toString());
      $dbh->rollback();
      return False;
    }

    setcurrentmemberid($memberid);

    setcookie(session_name(),session_id(),time()+SESSIONCOOKIEEXPIRE, SESSIONCOOKIEPATH, SESSIONCOOKIEDOMAIN);
                
//    session_set_cookie_params($lifetime); <-- called in startsession() in bbsengine3.php
//    session_regenerate_id(true); 

    displayredirectpage("OK -- logged in");

    $member = getmember($memberid);
    
    $name = isset($member["name"]) ? $member["name"] : null;
    
    logentry("login.20: success for ".var_export($name, True)." (#{$memberid})");
    
    sendnotify("login-success", $memberid);

    $dbh->disconnect();
    return True;
  }
  
  function main()
  {
    startsession();

    clearpageprotocol();

    setcurrentsite(SITENAME);
    setcurrentpage("login");
    setcurrentaction("form");
    $form = getquickform("trailersdemo-login", "post", array("action" => "/login"));
    buildloginfieldset($form);
    buildcaptchafieldset($form);

    $group = $form->addGroup("buttons");
    $group->setSeparator("&nbsp;");
    
    $group->addElement("submit", "submit", array("value" => "red pill"));
    $group->addElement("submit", "cancel", array("value" => "blue pill"));

    $const = array();
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

    // recursive filters "trim" and "strip_tags" added to getquickform() in bbsengine3.php

    $res = handleform($form, [$this, "validate"], "follow the white rabbit");
    if (PEAR::isError($res))
    {
      logentry("login.300: " . $res->toString());
      return PEAR::raiseError("login form handling error (code: login.300)");
    }
    if ($res === True)
    {
      logentry("login.301: handleform(...) returned True");
      return $res;
    }
    $renderer = getquickformrenderer();
    $form->render($renderer);
    $options = array();
    $options["stylesheets"] = array(STATICSKINURL."css/login.css");
    $res = displayform($renderer, "knock, knock neo...", $options);
    if (PEAR::isError($res))
    {
      logentry("login.302: " . $res->toString());
      return PEAR::raiseError("error displaying form (code: login.302)");
    }
    return $res;
  }
}

$l = new login();
$l->main();
if (PEAR::isError($l))
{
  logentry("login.999: " . $l->toString());
  exit;
}

?>

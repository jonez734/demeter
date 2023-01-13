<?php

/**
 * @package bbsengine3
 *
 * this is a module to handle member registrations
 *
 */

require_once("config.php");
require_once("demeter.php");
require_once("bbsengine3.php");

class register
{
    var $dbh = null;
    
    function insert($values)
    {
      $member = [];
      $member["email"] = $values["email"];
      $member["username"] = $values["username"];
      $member["realname"] = $values["realname"];
      $member["dateregistered"] = "now()";
      
/*
      $page = getpage("debug register");
      $page->addBodyContent(var_export($member, True));
      displaypage($page);
      return True; 
*/      
      $res = $this->dbh->beginTransaction();
      $res = $this->dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_INSERT);
      if (PEAR::isError($res))
      {
          logentry("register.20: " . $res->toString());
          $this->dbh->rollback();
          return PEAR::raiseError("Database Error (code: register.20)");
      }

      $memberid = $this->dbh->lastInsertID();
      $hashedpassword = hashpassword($values["newPassword"]);
      $res = setpassword($memberid, $hashedpassword);
      if (PEAR::isError($res))
      {
          logentry("register.22: " . $res->toString());
          return $res;
      }

      $res = $this->dbh->commit();
      $tmpl = getsmarty();

      $options["pagedata"]["body"] = $tmpl->fetch("thankyouforregistering.tmpl");
/*
      $bodycontent = array();
      $bodycontent[] = fetchpageheader();
      $bodycontent[] = fetchtopbar();
      $bodycontent[] = $tmpl->fetch("thankyouforregistering.tmpl");
      $bodycontent[] = fetchpagefooter();
*/
      $page = getpage("welcome to the matrix");
//      $page->addBodyContent($bodycontent);
      displaypage($page, $options);
      return True;
    }
    
    function main()
    {
        startsession();
        
/*
        if (flag("AUTHENTICATED") === False)
        {
            displaypermissiondenied();
            return;
        }
*/
        $this->dbh = dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            logentry("register.10: " . $this->dbh->toString());
            return PEAR::raiseError("Database Error (code: register.10)");
        }
        
        setcurrentaction("join");

//        logentry("register.100: site=".var_export(getcurrentsite(), True)." action=".var_export(getcurrentaction(), True));

        $form = getquickform(LOGENTRYPREFIX."-register");
        buildmemberfieldset($form);
        buildaccountfieldset($form, array("uniqueusername" => True));
        // buldprofilefieldset($form);
        buildnewpasswordfieldset($form);
        buildcaptchafieldset($form);

        $form->addElement("submit", "submit", ["value" => "register"]);
        
        $const = array();
        $const["memberid"] = isset($_REQUEST["memberid"]) ? intval($_REQUEST["memberid"]) : getcurrentmemberid();
        
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));
  
        $defaults = array();
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));
        
        $res = handleform($form, array($this, "insert"), "new user");
        if (PEAR::isError($res))
        {
            logentry("register.100: " . $res->toString());
            return PEAR::raiseError("displayform error (code: register.100)");
        }
        if ($res === True)
        {
            logentry("register.130: handleform(...) returned True");
            return True;
        }
        $renderer = getquickformrenderer();
        $form->render($renderer);
        $res = displayform($renderer, "knock, knock neo...");
        if (PEAR::isError($res))
        {
          logentry("register.302: " . $res->toString());
          return PEAR::raiseError("error displaying form (code: register.302)");
        }
        $this->dbh->disconnect();
        return $res;
    }
};

$j = new register();
$r = $j->main();
if (PEAR::isError($r))
{
    logentry("register.100: " . $r->toString());
    displayerrormessage($r->getMessage());
    exit;
}
?>

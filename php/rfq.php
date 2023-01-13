<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

class requestforquote 
{
    function contactinfosubmit($values)
    {
        logentry("contactinfosubmit.100: " . var_export($values));
        if (getcurrentmemberid() > 0)
        {
            $dbh = dbconnect(SYSTEMDSN);
            if (PEAR::isError($dbh))
            {
                logentry("contactinfosubmit.110: " . $dbh->toString());
                return PEAR::raiseError("database connect error (code: contactinfosubmit.110)");
            }

            $profile = getcurrentprofile();
            if (PEAR::isError($profile))
            {
                logentry("contactinfosubmit.150: " . $profile->toString());
                return PEAR::raiseError("database profile error (code: contactinfosubmit.150)");
            }
            
            if ($profile === null)
            {
                $profile = buildprofilerecord($values);
                $profile["modifiedbyid"] = $currentmemberid;
                $profile["datemodified"] = "now()";
                $profile["createdbyid"] = $currentmemberid;
                $profile["datecreated"] = "now()";
                
                $res = $dbh->autoExecute("__profile", $profile, MDB2_AUTOQUERY_INSERT);
                if (PEAR::isError($res))
                {
                    logentry("contactinfosubmit.120: " . $res->toString());
                    return PEAR::raiseError("database insert error (code: contactinfosubmit.120)");
                }
                displayredirectpage("Ok -- contact info added");
                return;
            }
            logentry("contactinfosubmit.130: updating profile");
            // save contactinfo for later orders            
            $profile = buildprofilerecord($values);
            $profile["modifiedbyid"] = $currentmemberid;
            $profile["datemodified"] = "now()";
            $res = $dbh->autoExecute("__profile", $profile, MDB2_AUTOQUERY_UPDATE, null, "id=".$dbh->quote($id, "integer"));
            if (PEAR::isError($res))
            {
                logentry("contactinfosubmit.140: " . $res->toString());
                return PEAR::raiseError("database update error (code: contactinfosubmit.140)");
            }
        }
        
        return;
    }

    function gathercontactinfo()
    {
        $currentmemberid = getcurrentmemberid();
        $profile = getcurrentmemberprofile();
        if (PEAR::isError($profile))
        {
            logentry("rfq.100: " . $profile->toString());
            $profile = [];
//            return PEAR::raiseError("error getting member profile (code: rfq.100)");
        }
        if ($profile === null)
        {
            logentry("rfq.110: getcurrentmemberprofile() returned null");
            $profile = [];
        }
        $form = getquickform("rfq-contactinfo-{$currentmemberid}");
        
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($profile));

        $const = array();
        $const["mode"] = "gathercontactinfo";
        $const["memberid"] = getcurrentmemberid();
      
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

        buildprofilefieldset($form);

        $form->addElement("submit", "formsubmit", array("value" => "submit rfq"));
      
        $res = handleform($form, [$this, "contactinfosubmit"], "contact information");
        if (PEAR::isError($res))
        {
            logentry("rfq.308: " . $res->toString());
            return PEAR::raiseError("error handling form (code: rfq.308)");
        }

        if ($res === True)
        {
            logentry("rfq.310: handleform(...) returned True");
            return True;
        }

        $renderer = getquickformrenderer();
        $form->render($renderer);
        $res = displayform($renderer, "gather contact information");
        if (PEAR::isError($res))
        {
            logentry("rfq.320: " . $res->toString());
            return PEAR::raiseError("error displaying form (code: rfq.320)");
        }
        return $res;
    }
    
    function main()
    {
        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        switch ($mode)
        {
            case "gathercontactinfo":
            {
                $res = $this->gathercontactinfo();
                break;
            }
            case "cancel":
            {
                $res = $this->cancel();
                break;
            }
            default:
            {
                $res = PEAR::raiseError("invalid mode (code: rfq.100)");
                break;
            }
        }
        return $res;
    }
};

$a = new requestforquote();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("rfq.900: " . $b->toString());
    displayerrorpage($b->getMessage());
    exit;
}
?>

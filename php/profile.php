<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

class profile 
{
    function confirm($values)
    {
        var_export($values);
        return;
    }

    function form()
    {
        logentry("bar!");
        $form = getquickform("profile-form");
        buildprofilefieldset($form);
        buildcaptchafieldset($form);
        buildbutton($form, "blah", ["value" => "confirm contact info"]);
//        $form->addElement("submit", "blah", array("value" => "confirm contact info"));
      
        $res = handleform($form, [$this, "confirm"], "confirm contact information");
        if (PEAR::isError($res))
        {
            logentry("profile.200: " . $res->toString());
            return PEAR::raiseError("error handling form (code: profile.200)");
        }

        if ($res === True)
        {
            logentry("profile.210: handleform(...) returned True");
            return True;
        }

        $renderer = getquickformrenderer();
        $form->render($renderer);
        $res = displayform($renderer, "edit contact info");
        if (PEAR::isError($res))
        {
            logentry("profile.220: " . $res->toString());
            return PEAR::raiseError("error displaying form (code: profile.220)");
        }
        logentry("foo!");
        return $res;
        
    }
    
    function main()
    {
        startsession();
        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        logentry("profile.990: mode=".var_exporT($mode, True));
        switch ($mode)
        {
            case "form":
            {
                $res = $this->form();
                break;
            }
            default:
            {
                logentry("profile.991: mode=".var_export($mode, True));
                $res = PEAR::raiseError("invalid mode (code: profile.991)");
                break;
            }
        }
        endsession();
        return $res;
    }
};

$a = new profile();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("profile.999: " . $b->toString());
    displayerrorpage($b->getMessage());
    exit;
}

?>

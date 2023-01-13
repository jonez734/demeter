<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

class trailer
{
    function detail()
    {
        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
        $trailer = gettrailer($id);
        if (PEAR::isError($trailer))
        {
            logentry("trailer.600: " . $trailer->toString());
            displayerrorpage("database error (code: trailer.600)");
            return;
        }
        if ($trailer === null)
        {
            logentry("trailer.610: gettrailer(".var_export($id, True).") returned null");
            displayerrorpage("database select error (code: trailer.610)");
            return;
        }
        
        $page = getpage(SITETITLE." - trailer - detail - ".$trailer["title"]);
        $tmpl = getsmarty();
        $tmpl->assign("trailer", $trailer);
        $options["pagedata"]["body"] = $tmpl->fetch("trailer-detail.tmpl");
        displaypage($page, $options);
    }

    function update($values)
    {
      $currentmemberid = getcurrentmemberid();

      $id = intval($values["id"]);

      $sigs = isset($values["sigs"]) ? $values["sigs"] : null;
      logentry("trailer.400: sigs=".var_export($sigs, True));

      logentry("trailer.401: values['image1']=".var_export($values["image1"], True));
      logentry("trailer.403: values['image2']=".var_export($values["image2"], True));
      logentry("trailer.402: values=".var_export($values, True));

      $this->dbh->beginTransaction();
      
      $res = updatesigmap($sigs, "map_trailer_sig", "trailerid", $id);
      if (PEAR::isError($res))
      {
        logentry("trailer.426: " . $res->toString());
        $this->rollback();
        return PEAR::raiseError("database update error (code: trailer.426)");
      }

      $trailer = buildtrailerrecord($values);
      
      $trailer["datemodified"] = "now()";
      $trailer["modifiedbyid"] = $currentmemberid;

      $destinationdir = TRAILERIMAGEDIR;

      logentry("update.16: values.image1=".var_export($values["image1"], True));
      
      $image1filename = processtrailerimage($values["image1"]);
      if (empty($image1filename) === False)
      {
          $trailer["image1"] = $image1filename;
      }

      $image2filename = processtrailerimage($values["image2"]);
      if (empty($image2filename) === False)
      {
          $trailer["image2"] = $image2filename;
      }


      $res = $this->dbh->autoExecute("__trailer", $trailer, MDB2_AUTOQUERY_UPDATE, "id=".$this->dbh->quote($id, "integer"));
      if (PEAR::isError($res))
      {
        logentry("update.14: ". $res->toString());
        $this->dbh->rollback();
        return PEAR::raiseError("Database Error (code: update.14)");
      }
      $this->dbh->commit();

      displayredirectpage("OK -- trailer updated");

      return True;
    }

    function edit()
    {
        if (flag("ADMIN") === False)
        {
            logentry("trailer.300: permission denied editing trailer");
            displaypermissiondenied();
            return;
        }
        
        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
        $trailer = gettrailer($id);
        if (PEAR::isError($trailer))
        {
            logentry("trailer.302: " . $trailer->toString());
            return PEAR::raiseError("database select error (code: trailer.302)");
        }

        if ($trailer === null)
        {
            logentry("trailer.304: gettrailer(".var_export($id, True).") returned null");
            return PEAR::raiseError("database select error (code: trailer.304)");
        }
        
        setcurrentaction("edit");
      
        $form = getquickform("trailer-edit");
        $form->setAttribute("enctype", "multipart/form-data");

        logentry("trailer.306: trailer.sigs=".var_export($trailer["sigs"], True));

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($trailer));

        $const = array();
        $const["mode"] = "edit";
        $const["memberid"] = getcurrentmemberid();
      
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

        buildtrailerfieldset($form);

        $form->addElement("submit", "blah", array("value" => "edit"));
      
        $res = handleform($form, array($this, "update"), "edit trailer");
        if (PEAR::isError($res))
        {
            logentry("trailer.308: " . $res->toString());
            return PEAR::raiseError("error handling form (code: trailer.308)");
        }

        if ($res === True)
        {
            logentry("trailer.310: handleform(...) returned True");
            return True;
        }

        $renderer = getquickformrenderer();
        $form->render($renderer);
        $res = displayform($renderer, "edit trailer");
        if (PEAR::isError($res))
        {
            logentry("trailer.320: " . $res->toString());
            return PEAR::raiseError("error displaying form (code: trailer.320)");
        }
        return $res;
    }

    function insert($values)
    {
        $currentmemberid = getcurrentmemberid();
        
        $trailer = buildtrailerrecord($values);
        if (PEAR::isError($trailer))
        {
            logentry("trailer.200: " . $trailer->getMessage());
            return $trailer;
        }

        $trailer["createdbyid"] = $currentmemberid;
        $trailer["datecreated"] = "now()";
        $trailer["modifiedbyid"] = $currentmemberid;
        $trailer["datemodified"] = "now()";
        
        $sigs = $values["sigs"];
        
        $res = $this->dbh->beginTransaction();
        if (PEAR::isError($res))
        {
            logentry("trailer.212: " . $res->toString());
            return PEAR::raiseError("transaction error (code: trailer.212)");
        }

        $res = $this->dbh->autoExecute("__trailer", $trailer, MDB2_AUTOQUERY_INSERT);
        if (PEAR::isError($res))
        {
            logentry("trailer.220: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database error (code: trailer.220)");
        }
        
        $trailerid = $this->dbh->lastInsertId();
        if (PEAR::isError($trailerid))
        {
            logentry("trailer.222: " . $trailerid->toString());
            return PEAR::raiseError("database transaction error (code: trailer.222)");
        }

        $res = updatesigmap($sigs, "map_trailer_sig", "trailerid", $trailerid);
        if (PEAR::isError($res))
        {
            logentry("trailer.230: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database error (code: trailer.230)");
        }

        
        $res = $this->dbh->commit();
        if (PEAR::isError($res))
        {
            logentry("trailer.240: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database commit error (code: trailer.240)");
        }
        displayredirectpage("OK -- Trailer added to catalog");
        return True;
    }

    function add()
    {
      setcurrentaction("add");

      if (flag("ADMIN") === False)
      {
          logentry("trailer.120: permission denied to add trailer");
          displaypermissiondenied();
          return;
      }

      $form = getquickform("trailersdemo-add");
      buildtrailerfieldset($form);

      $form->addElement("submit", "blah", array("value" => "add trailer to catalog"));

      $siguri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
      $sigpath = buildlabelpath($siguri);
      
      logentry("trailer.100: sigpath=".var_export($sigpath, True));

      $defaults = [];
      
      $defaults["sigs"] = $sigpath;

      $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

      $const = array();
      $const["mode"] = "add";
      $const["memberid"] = getcurrentmemberid();

      $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));
      
      $res = handleform($form, [$this, "insert"], "add trailer");
      if (PEAR::isError($res))
      {
          logentry("trailer.104: " . $res->toString());
          return PEAR::raiseError("form handling error (code: trailer.104)");
      }
      if ($res === True)
      {
        logentry("trailer.102: handleform(...) returned True");
        return True;
      }

      $renderer = getquickformrenderer();
      $form->render($renderer);

      $res = displayform($renderer, "add trailer");
      if (PEAR::isError($res))
      {
        logentry("trailer.101: " . $res->toString());
      }
      return $res;
    }

    function main()
    {
        startsession();
        $this->dbh = dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            logentry("trailer.900: " . $this->dbh->toString());
            return PEAR::raiseError("database connect error (code: trailer.900)");
        }

        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        $res = null;
        switch ($mode)
        {
            case "add":
            {
                $res = $this->add();
                break;
            }
            case "edit":
            {
                $res = $this->edit();
                break;
            }
            case "detail":
            {
                $res = $this->detail();
                break;
            }
            default:
            {
              $res = PEAR::raiseError("invalid mode (code: trailer.998)");
              logentry("trailer.998: invalid mode ".var_export($mode, True));
              break;
            }
            
        }
        endsession();
        return $res;
    }
};

$a = new trailer();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("trailer.999: ". $b->toString());
    displayerrorpage($b->getMessage());
    exit;
}
?>

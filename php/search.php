<?php

require_once("config.php");
require_once("demeter.php");
require_once("bbsengine3.php");

class search
{
    function detail()
    {
        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
        $search = getsearch($id);
        if (PEAR::isError($search))
        {
            logentry("search.600: " . $search->toString());
            displayerrorpage("database error (code: search.600)");
            return;
        }
        if ($search === null)
        {
            logentry("search.610: getsearch(".var_export($id, True).") returned null");
            displayerrorpage("database select error (code: search.610)");
            return;
        }
        
        $page = getpage(SITETITLE." - search - detail - ".$search["title"]);
        $tmpl = getsmarty();
        $tmpl->assign("search", $search);
        $options["pagedata"]["body"] = $tmpl->fetch("search-detail.tmpl");
        displaypage($page, $options);
    }

    function update($values)
    {
      $currentmemberid = getcurrentmemberid();

      $id = intval($values["id"]);

      $sigs = isset($values["sigs"]) ? $values["sigs"] : null;
      logentry("search.400: sigs=".var_export($sigs, True));

      logentry("search.401: values['image1']=".var_export($values["image1"], True));
      logentry("search.403: values['image2']=".var_export($values["image2"], True));
      logentry("search.402: values=".var_export($values, True));

      $this->dbh->beginTransaction();
      
      $res = updatesigmap($sigs, "map_search_sig", "searchid", $id);
      if (PEAR::isError($res))
      {
        logentry("search.426: " . $res->toString());
        $this->rollback();
        return PEAR::raiseError("database update error (code: search.426)");
      }

      $search = buildsearchrecord($values);
      
      $search["datemodified"] = "now()";
      $search["modifiedbyid"] = $currentmemberid;

      $destinationdir = TRAILERIMAGEDIR;

      logentry("update.16: values.image1=".var_export($values["image1"], True));
      
      $image1filename = processsearchimage($values["image1"]);
      if (empty($image1filename) === False)
      {
          $search["image1"] = $image1filename;
      }

      $image2filename = processsearchimage($values["image2"]);
      if (empty($image2filename) === False)
      {
          $search["image2"] = $image2filename;
      }


      $res = $this->dbh->autoExecute("__search", $search, MDB2_AUTOQUERY_UPDATE, "id=".$this->dbh->quote($id, "integer"));
      if (PEAR::isError($res))
      {
        logentry("update.14: ". $res->toString());
        $this->dbh->rollback();
        return PEAR::raiseError("Database Error (code: update.14)");
      }
      $this->dbh->commit();

      displayredirectpage("OK -- search updated");

      return True;
    }

    function edit()
    {
        if (flag("ADMIN") === False)
        {
            logentry("search.300: permission denied editing search");
            displaypermissiondenied();
            return;
        }
        
        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
        $search = getsearch($id);
        if (PEAR::isError($search))
        {
            logentry("search.302: " . $search->toString());
            return PEAR::raiseError("database select error (code: search.302)");
        }

        if ($search === null)
        {
            logentry("search.304: getsearch(".var_export($id, True).") returned null");
            return PEAR::raiseError("database select error (code: search.304)");
        }
        
        setcurrentaction("edit");
      
        $form = getquickform("search-edit");
        $form->setAttribute("enctype", "multipart/form-data");

        logentry("search.306: search.sigs=".var_export($search["sigs"], True));

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($search));

        $const = array();
        $const["mode"] = "edit";
        $const["memberid"] = getcurrentmemberid();
      
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

        buildsearchfieldset($form);

        $form->addElement("submit", "blah", array("value" => "edit"));
      
        $res = handleform($form, array($this, "update"), "edit search");
        if (PEAR::isError($res))
        {
            logentry("search.308: " . $res->toString());
            return PEAR::raiseError("error handling form (code: search.308)");
        }

        if ($res === True)
        {
            logentry("search.310: handleform(...) returned True");
            return True;
        }

        $renderer = getquickformrenderer();
        $form->render($renderer);
        $res = displayform($renderer, "edit search");
        if (PEAR::isError($res))
        {
            logentry("search.320: " . $res->toString());
            return PEAR::raiseError("error displaying form (code: search.320)");
        }
        return $res;
    }

    function insert($values)
    {
        $currentmemberid = getcurrentmemberid();
        
        $search = buildsearchrecord($values);
        if (PEAR::isError($search))
        {
            logentry("search.200: " . $search->getMessage());
            return $search;
        }

        $search["createdbyid"] = $currentmemberid;
        $search["datecreated"] = "now()";
        $search["modifiedbyid"] = $currentmemberid;
        $search["datemodified"] = "now()";
        
        $sigs = $values["sigs"];
        
        $res = $this->dbh->beginTransaction();
        if (PEAR::isError($res))
        {
            logentry("search.212: " . $res->toString());
            return PEAR::raiseError("transaction error (code: search.212)");
        }

        $res = $this->dbh->autoExecute("__search", $search, MDB2_AUTOQUERY_INSERT);
        if (PEAR::isError($res))
        {
            logentry("search.220: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database error (code: search.220)");
        }
        
        $searchid = $this->dbh->lastInsertId();
        if (PEAR::isError($searchid))
        {
            logentry("search.222: " . $searchid->toString());
            return PEAR::raiseError("database transaction error (code: search.222)");
        }

        $res = updatesigmap($sigs, "map_search_sig", "searchid", $searchid);
        if (PEAR::isError($res))
        {
            logentry("search.230: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database error (code: search.230)");
        }

        
        $res = $this->dbh->commit();
        if (PEAR::isError($res))
        {
            logentry("search.240: " . $res->toString());
            $this->dbh->rollback();
            return PEAR::raiseError("database commit error (code: search.240)");
        }
        displayredirectpage("OK -- Trailer added to catalog");
        return True;
    }

    function add()
    {
      setcurrentaction("add");

/*
      if (flag("ADMIN") === False)
      {
          logentry("search.120: permission denied to add search");
          displaypermissiondenied();
          return;
      }
*/
      $form = getquickform("demeter-add-search");
      buildsearchfieldset($form);

      $form->addElement("submit", "blah", array("value" => "add search to catalog"));

      $siguri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
      $sigpath = buildlabelpath($siguri);
      
      logentry("search.100: sigpath=".var_export($sigpath, True));

      $defaults = [];
      
      $defaults["sigs"] = $sigpath;

      $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

      $const = array();
      $const["mode"] = "add";
      $const["memberid"] = getcurrentmemberid();

      $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));
      
      $res = handleform($form, [$this, "insert"], "add search");
      if (PEAR::isError($res))
      {
          logentry("search.104: " . $res->toString());
          return PEAR::raiseError("form handling error (code: search.104)");
      }
      if ($res === True)
      {
        logentry("search.102: handleform(...) returned True");
        return True;
      }

      $renderer = getquickformrenderer();
      $form->render($renderer);

      $res = displayform($renderer, "add search");
      if (PEAR::isError($res))
      {
        logentry("search.101: " . $res->toString());
      }
      return $res;
    }

    function main()
    {
        startsession();
        $this->dbh = dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            logentry("search.900: " . $this->dbh->toString());
            return PEAR::raiseError("database connect error (code: search.900)");
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
              $res = PEAR::raiseError("invalid mode (code: search.998)");
              logentry("search.998: invalid mode ".var_export($mode, True));
              break;
            }
            
        }
        endsession();
        return $res;
    }
};


$a = new search();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("search.999: " . $b->toString());
    exit;
}
?>

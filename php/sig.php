<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

// require_once("Pager.php");

class sig
{
  var $dbh = null;

  /**
   * @since 20151017
   */
  function getsigcount($labelpath)
  {
    $sql = "select count(id) from engine.sig where path ~ ?";
    $dat = array("{$labelpath}.*{1}");
    $dbh = dbconnect(SYSTEMDSN);
    $res = $dbh->getOne($sql, ["integer"], $dat, ["text"]);
    return intval($res);
  }

  function detail()
  {
    $uri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
    logentry("sig.301: uri=".var_export($uri, True));
    $labelpath = buildlabelpath($uri);
    logentry("sig.303: labelpath=".var_export($labelpath, True));
    $sig = getsig($labelpath);
    if (PEAR::isError($sig))
    {
      logentry("sig.300: ".var_export($sig->toString()));
      displayerrorpage("invalid uri (code: sig.300)");
      return;
    }
    if ($sig === null)
    {
      logentry("sig.302: getsigfrompath(".var_export($labelpath, True).") returned null");
      displayerrorpage("invalid uri (code: sig.302)");
      return;
    }
//    $sig["totallinks"] = $this->getlinkcount($labelpath);
//    $sig["totalposts"] = $this->getpostcount($labelpath);
    $sig["totalsigs"] = $this->getsigcount($labelpath);
    $bare = isset($_REQUEST["bare"]) ? True : False;
    if ($bare === True)
    {
      header('content-type: application/json; charset=utf-8');
      $tmpl = getsmarty();
      $tmpl->assign("sig", $sig);
      $data = $tmpl->fetch("sig-detail.tmpl");
      $encode = encodejson($data);
      $callback = isset($_REQUEST["callback"]) ? $_REQUEST["callback"] : null;
      print "{$callback} ({$encode});";
      return;
    }

    print "not working yet";
    return;
  }

  function edit()
  {
    $currentmemberid = getcurrentmemberid();
    
    $uri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
    
    setreturnto(TEOSURL.$uri);

    $labelpath = buildlabelpath($uri);
    $parentlabelpath = buildparentlabelpath($labelpath); // buildlabelpath(dirname($uri));
    $name = basename($uri);

    logentry("sig.209: uri=".var_export($uri, True). " labelpath=".var_export($labelpath, True));

    $sig = getsig($labelpath);
    if (PEAR::isError($sig))
    {
      logentry("sig.210: " . $sig->toString());
      return PEAR::raiseError("Database Error (code: sig.210)");
    }
    
    if ($sig === null)
    {
      logentry("sig.220: labelpath ".var_export($labelpath, True)." not found");
      return PEAR::raiseError("Input Error (code: sig.220)");
    }

    $sig["uri"] = $uri;
    
    if (accesssig("edit", $sig) === False)
    {
      displaypermissiondenied("You do not have permission to edit this sig.");
      return;
    }
    
    $sigid = getsigidfromlabelpath($labelpath);
    if (PEAR::isError($sigid))
    {
      logentry("sig.222: " . $sigid->getMessage());
      return PEAR::raiseError("Error processing labelpath (code: sig.222)");
    }

    $form = getquickform("sig-edit-{$sigid}");
    
    $parentpath = buildparentlabelpath($labelpath);

    $defaults = [];
    $defaults["parentlabelpath"] = $parentlabelpath; // $sig["path"];
    $defaults["title"] = $sig["title"];
    $defaults["name"] = $name; // sig["name"];
    $defaults["intro"] = $sig["intro"];

    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

    $constants = [];
    $constants["mode"] = "edit";
    $constants["uri"] = $uri;
    $constants["id"] = $sigid;

    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($constants));

    buildsigfieldset($form);
    $form->addElement("submit", "blah", array("value" => "update"));

    $res = handleform($form, array($this, "update"), "edit sig");
    if (PEAR::isError($res))
    {
      logentry("sig.230: " . $res->toString());
      return PEAR::raiseError("error handling form (code: sig.230");
    }
    if ($res === True)
    {
      return;
    }
    
    $renderer = getquickformrenderer();
    $form->render($renderer);
  
    $res = displayform($renderer, "edit sig");
    if (PEAR::isError($res))
    {
      logentry("sig.232: " . $res->toString());
      return PEAR::raiseError("error displaying form (code: sig.232)");
    }
    return;
  }
  
  function update($values)
  {
    $path = $values["parentlabelpath"];
    $name = $values["name"];
    $id = intval($values["id"]);

    $uri = $values["uri"];
    $pageprotocol = isset($values["pageprotocol"]) ? $values["pageprotocol"] : "standard";

    $labelpath = normalizelabelpath($path, $name);

    $sig = [];
    $sig["title"] = $values["title"];
    $sig["intro"] = $values["intro"];
    $sig["name"] = $name;
    $sig["path"] =  $labelpath; // path + name
    $sig["lastmodified"] = "now()";
    $sig["lastmodifiedbyid"] = getcurrentmemberid();

    $res = $this->dbh->beginTransaction();
    $res = $this->dbh->autoExecute("engine.__sig", $sig, MDB2_AUTOQUERY_UPDATE, "id=".$this->dbh->quote($id, "integer"));
    if (PEAR::isError($res))
    {
      logentry("sig.300: " . $res->toString());
      $this->dbh->rollback();
      return PEAR::raiseError("Database Error (code: sig.300)");
    }
    $this->dbh->commit();
    displayredirectpage("Folder Updated", TEOSURL.$uri);
    return True;
  }

  function delete()
  {
    $currentmemberid = getcurrentmemberid();
    
    $uri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
    if ($uri === null)
    {
      logentry("sig.400: delete() passed null for uri");
      return PEAR::raiseError("Input Error (code: sig.400)");
    }
    
    setreturnto(TEOSURL.$uri."delete");

    $path = buildpath($uri);
    $sig = getsig($path);
    if (PEAR::isError($sig))
    {
      logentry("sig.410: " . $sig->toString());
      return PEAR::raiseError("Database Error (code: sig.410)");
    }
    if ($sig === null)
    {
      logentry("sig.420: getsig(".var_export($path, True).") returned null");
      return PEAR::raiseError("Input Error (code: sig.420)");
    }

    $sig["uri"] = $uri;
    
    if (accesssig("delete", $sig, $currentmemberid) === False)
    {
      logentry("sig.430: permission denied trying to delete ".var_export($path, True));
      displaypermissiondenied("You do not have permission to delete this sig");
      return;
    }
    $confirm = isset($_REQUEST["confirm"]) ? True : False;
    if ($confirm === False)
    {
      $title = $sig["title"];
      displaydeleteconfirmation("Are you sure you want to delete <i>{$title}</i>?", TEOSURL.$uri."delete?confirm", "Yes", TEOSURL.$uri, "No");
      return;
    }

    $res = $this->dbh->autoExecute("sig", null, MDB2_AUTOQUERY_DELETE, "path=".$this->dbh->quote($path, "text"));
    if (PEAR::isError($res))
    {
      logentry("sig.440: ".$res->toString());
      return PEAR::raiseError("Database Error (code: sig.440)");
    }
    displayredirectpage("Sig Deleted", "/");
    return;
  }
  
  function browse()
  {
    $uri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null;
    $path = buildlabelpath($uri);
    logentry("browse.100: path=".var_export($path, True));
    $sql = "select sig.* from engine.sig where path ~ ?";
    $dat = [$path];
    $res = $this->dbh->getRow($sql, null, $dat, array("text"));
    if (PEAR::isError($res))
    {
      logentry("sig.510: " . $res->toString());
      return PEAR::raiseError("Database Error (code: sig.510)");
    }
    if ($res === null)
    {
      displayerrorpage("Folder not found (code: sig.520)");
      return;
    }

    setcurrentaction("browse");
    setcurrentpage("catalog");

    $currentsig = $res;
    setcurrentsig($currentsig);

    $currentmemberid = getcurrentmemberid();
    
    $actions = buildsigactions($currentsig);
    $currentsig["actions"] = $actions;

    $sql = "select s.id, s.path, s.title, s.url from engine.sig as s where s.path ~ ? order by s.title asc";
    $dat = "{$path}.*{1}";
    $res = $this->dbh->getAll($sql, null, $dat, array("text"));
    if (PEAR::isError($res))
    {
      logentry("sig.520: " . $res->toString());
      return PEAR::raiseError("Database Error (code: sig.520)");
    }

    $sigs = [];
    foreach ($res as $rec)
    {
      logentry("sig.200: rec=".var_export($rec, True));
      if (accesssig("view", $rec) === False)
      {
        continue;
      }
      $rec["actions"] = buildsigactions($rec);
      $sigs[] = $rec;
    }

    $perPage = 10;
        
    setreturnto(getcurrenturi());

    $breadcrumbs = buildbreadcrumbs($path);
    logentry("sig.200: path=".var_export($path, True)." breadcrumbs=".var_export($breadcrumbs, True));
    
    $sidebaractions = [];
    if (accesssig("add", $currentsig) === True)
    {
      $sidebaractions[] = array("name" => "addsig", "url" => TEOSURL."{$uri}/add-sig", "desc" => "add a subsig to this sig", "title" => "add sig");
    }
    if (accesssig("edit", $currentsig) === True)
    {
      $sidebaractions[] = array("name" => "editsig", "url" => TEOSURL."{$uri}/edit-sig", "desc" => "edit sig", "title" => "edit sig");
    }

    logentry("sig.210: " .var_export($sidebaractions, True));
    
    $trailers = [];
    $sql = "select trailerid from map_trailer_sig where siglabelpath = ?";
    $dat = [$path];
    $res = $this->dbh->getAll($sql, null, $dat, "text");
    if (PEAR::isError($res))
    {
      logentry("sig.212: " . $res->toString());
      return PEAR::raiseError("database error (code: sig.212)");
    }

    logentry("sig.213: res=".var_export($res, True));
    
    foreach ($res as $rec)
    {
      $trailerid = $rec["trailerid"];
      $trailer = gettrailer($trailerid);
      if (PEAR::isError($trailer))
      {
        logentry("sig.214: " . $trailer->toString());
        continue;
      }
      if ($trailer === null)
      {
        logentry("sig.215: gettrailer(".var_export($trailerid, True).") returned null");
        continue;
      }

      $trailers[] = $trailer;
    }
    $page = getpage(SITETITLE." - catalog - browse - ".$currentsig["title"]);

//    $page->addStyleSheet(STATICSKINURL."css/pagelinks.css");
    $page->addStylesheet(STATICSKINURL."css/breadcrumbs.css");
    $page->addStyleSheet(STATICSKINURL."css/youarehere.css");
    $page->addStyleSheet(STATICSKINURL."css/trailer.css");
    $tmpl = getsmarty();

    $tmpl->assign("sigs", $sigs);
    $tmpl->assign("trailers", $trailers);

//    $tmpl->assign("linkpageinfo", $linkpageinfo);
    $tmpl->assign("currentsig", $currentsig);
    $tmpl->assign("breadcrumbs", $breadcrumbs);

    $options = [];
        
    $topbardata = buildtopbardata($sidebaractions);
    $options["pagedata"]["topbardata"] = $topbardata; // fetchsidebar(buildsidebarmenu($sidebaractions));
    $options["pagedata"]["sidebardata"] = buildsidebarmenu($sidebaractions);
    $options["pagedata"]["body"] = $tmpl->fetch("sig.tmpl");
    $res = displaypage($page, $options);
    return;
  }

  function add()
  {
    if (accesssig("add") === False)
    {
      logentry("sig.25: permission denied. op=add memberid=".var_export($currentmemberid, True)." labelpath=".var_export($labelpath, True));
      displaypermissiondenied("You do not have permission to add sigs here. (code: sig.25)");
      return;
    }

    setcurrentpage("sig");
    setcurrentaction("add");

    $parent = isset($_REQUEST["parent"]) ? $_REQUEST["parent"] : null;
    $parentlabelpath = buildlabelpath($parent);
    logentry("sig.48: parent=".var_export($parent, True)." labelpath=".var_export($labelpath, True));
    $currentmemberid = getcurrentmemberid();
    
    setreturnto(TEOSURL.$parent);
    
    $form = getquickform("trailersdemo-sig-add");
    buildsigfieldset($form);
    $form->addElement("submit", "addsig", array("value" => "add"));

    $defaults = [];
    $defaults["parentlabelpath"] = $parentlabelpath;

    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

    $const = [];
    $const["mode"] = "add";
    $const["memberid"] = getcurrentmemberid();
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

    $renderer = getquickformrenderer();
    $form->render($renderer);
    $res = handleform($form, [$this, "insert"], "add sig");
    if (PEAR::isError($res))
    {
      logentry("sig.300: ". $res->toString());
      return PEAR::raiseError("unable to handle form. (code: sig.300)");
    }
    if ($res === True)
    {
      logentry("sig.302: handleform(...) returned true");
      return;
    }
    $res = displayform($renderer, "add sig");
    return;
  }

  function insert($values)
  {
    $title = $values["title"];
    $labelpath = $values["parentlabelpath"];    
    $name = !empty($values["name"]) ? $values["name"] : buildlabel($title);

    logentry("sig.22: name=".var_export($name, True)." title=".var_export($title, True)." labelpath=".var_export($labelpath, True));

    $currentmemberid = getcurrentmemberid();
    
    $sig = [];
    $sig["path"] = normalizelabelpath($labelpath, $name);
    $sig["title"] = $title;
    $sig["intro"] = $values["intro"];
    $sig["name"] = $name;
    $sig["postedbyid"] = $currentmemberid;
    $sig["dateposted"] = "now()";
    $sig["lastmodified"] = "now()";
    $sig["lastmodifiedbyid"] = $currentmemberid;
    
    $res = $this->dbh->autoExecute("engine.__sig", $sig, MDB2_AUTOQUERY_INSERT);
    if (PEAR::isError($res))
    {
      logentry("sig.28: " . $res->toString());
      return PEAR::raiseError("Error Inserting Folder (code: sig.28)");
    }
    
    displayredirectpage("SIG added", TEOSURL.$siguri);
    return True;
  }
  
  function main()
  {
    startsession();

//    setcurrentsite("teos");
    setcurrentpage("index");
        
    $this->dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($this->dbh))
    {
      logentry("sig.90: ".$this->dbh->toString());
      return PEAR::raiseError("Database Connect Error (code: sig.90)");
    }
    
    clearpageprotocol();
    
    $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    logentry("sig.500: mode=".var_export($mode, True));
    switch($mode)
    {
      case "browse":
      {
        $r = $this->browse();
        break;
      }
      case "add":
      {
        $r = $this->add();
        break;
      }
      case "edit":
      {
        $r = $this->edit();
        break;
      }
      case "delete":
      {
        $r = $this->delete();
        break;
      }
      case "detail":
      {
        $r = $this->detail();
        break;
      }
      default:
      {
        $r = $this->browse();
        break;
      }
    }
    return $r;
  }
};

$a = new sig();
$b = $a->main();
if (PEAR::isError($b))
{
  logentry("sig.100: " . $b->toString());
  displayerrorpage($b->getMessage());
}
?>

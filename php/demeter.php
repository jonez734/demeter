<?php

require_once("HTML/QuickForm2/Element/Captcha/Image.php");

function getsmarty($options=null)
{
  $options = array();
  $options["pluginsdir"] = array(SMARTYPLUGINSDIR);
  $options["templatedir"] = array(SMARTYTEMPLATESDIR);
  $options["compiledir"] = SMARTYCOMPILEDTEMPLATESDIR;
  $options["compileid"] = LOGENTRYPREFIX;
  return _getsmarty($options);
}

function getpage($title, $options=null)
{
  $_options = array();
  $_options["staticskinurl"] = STATICSKINURL;
//  $_options["sitecss"] = SITECSS;

  if (is_array($options))
  {
    $options = array_merge($_options, $options);
  }
  else
  {
    $options = $_options;
  }

  $disablepageheader = False;
  if (is_array($options) && array_key_exists("disablepageheader", $options))
  {
   $disablepageheader = $options["disablepageheader"];
  }
  $tmpl = getsmarty();

  $page = _getpage($title, $options);
  if ($disablepageheader === False)
  {
   $page->addScript("//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js", ["execute" => "immediate"]);
   

//   $page->addScript("//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js", ["execute" => "immediate"]);
//   $page->addStyleSheet("//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/start/jquery-ui.css");

   $page->addStyleSheet(STATICSKINURL."css/actions.css");
   
   $page->addStyleSheet(STATICSKINURL."css/panel.css");

//   $page->setMetaData("google-site-verification", "-crBDeAJOq6aN0255NpBZKq9KiCFhIa7GVozMsjdb0Y");
   $page->setMetaData("viewport", "width=device-width, initial-scale=1");

//   $page->addScriptDeclaration($tmpl->fetch("googleanalytics.tmpl"));

   $page->addScript(STATICJAVASCRIPTURL."bbsengine3.js", ["execute" => "defer"]);

//   $page->addScript(STATICJAVASCRIPTURL."tooltip.js", ["execute" => "defer"]);
  
   $page->addScript(STATICJAVASCRIPTURL."topbar.js", ["execute" => "defer"]);
   $page->addStyleSheet(STATICSKINURL."css/topbar.css");
  
   $page->addScript(STATICJAVASCRIPTURL."clock.js", ["execute" => "defer"]);
   $page->addStylesheet(STATICSKINURL."css/clock.css");
  
   $page->addStyleSheet(STATICSKINURL."css/actions.css");
   
//   $page->addScript(ENGINEURL."js/jquery.jsonp.js", ["execute" => "defer"]);

//   $page->addScript(STATICJAVASCRIPTURL."page.js", ["execute" => "defer"]);
   
   if (defined("FONTAWESOMECSSURL") === True)
   {
     $page->addStyleSheet(FONTAWESOMECSSURL);
   }
   $page->addStyleSheet(STATICSKINURL."css/noscript.css");
   $page->addBodyContent($tmpl->fetch("noscript.tmpl"));
  
   if (getcurrentmemberid() > 0)
   {
    $page->addScript(STATICJAVASCRIPTURL."notify.js", ["execute" => "defer"]);
     $page->addStyleSheet(STATICSKINURL."css/notify.css");
   }
   
   $page->addScript(STATICJAVASCRIPTURL."jquery.smoothState.js", ["execute" => "defer"]);
//   $page->addScript(STATICJAVASCRIPTURL."ssi.js", ["execute" => "defer"]);
   $page->addStyleSheet(STATICSKINURL."css/demeter.css");
   
//   $page->setAttribute("id", "htmlpagefoobar");
}

  return $page;
}

function displaypage($page, $options=null)
{
  $metadata = isset($options["metadata"]) ? $options["metadata"] : [];
  $tags = [
    "SITEURL" => "og:url", 
    "SITEKEYWORDS" => "keywords", 
    "SITETITLE" => "og:title", 
    "SITEDESCRIPTION" => "og:description", 
    "FACEBOOKAPPID" => "fb:app_id"
  ];
  $defaultmeta = [];
  foreach ($tags as $k => $v)
  {
    if (defined($k) === True)
    {
      $defaultmeta[$v] = constant($k);
    }
  }
  $mergedmeta = array_merge($defaultmeta, $metadata);
  logentry("displaypage.100: mergedmeta=".var_export($mergedmeta, True));

  foreach ($mergedmeta as $key => $content)
  {
    if (strpos($key, "og:") === 0 || strpos($key, "fb:") === 0)
    {
      $data = "<meta property=\"{$key}\" content=\"{$content}\">";
    }
    else
    {
      $data = "<meta name=\"{$key}\" content=\"{$content}\">";
    }
    $page->addRawHeaderData($data);
  }

  $pagedata = isset($options["pagedata"]) ? $options["pagedata"] : null;
  if ($pagedata === null)
  {
    // for backwards compat
    logentry("displaypage.100: pagedata is null");
  }
  else
  {
    $template = isset($pagedata["template"]) ? $pagedata["template"] : "page.tmpl";
    $sidebardata = []; // isset($pagedata["sidebardata"]) ? $pagedata["sidebardata"] : buildsidebarmenu();
    logentry("displaypage.110: using pagedata");
    $pagedata["sidebardata"] = $sidebardata;

    if (isset($pagedata["topbardata"]) === False)
    {
     $pagedata["topbardata"] = buildtopbardata();
    }
    $tmpl = getsmarty();
    foreach ($pagedata as $key => $value)
    {
      $tmpl->assign($key, $value);
    }
    $page->setBody($tmpl->fetch($template));
  }
  return _displaypage($page, $options);
}

function buildcaptchafieldset($form, $options=null)
{
  $_options = array(
//    "label" => "Are You Human?",

    // Captcha options
    "output" => "png",
    "width"  => 300,
    "height" => 100,

    // Path where to store images
          "imageDir" => DOCUMENTROOT. "captchas/",
          "imageDirUrl" => "/captchas/",
          "imageOptions" => array(
              "font_path"        => "/usr/share/fonts/truetype/",
              "font_file"        => "cour.ttf",
              "text_color"       => "#000000",
              "background_color" => "#ffffff",
              "lines_color"      => "#000000",
          )
  );
  if (is_array($options))
  {
   $_options = array_merge($_options, $options);
  }
  $fs = $form->addFieldset("captcha");
  $el = $fs->addElement(
    new HTML_QuickForm2_Element_Captcha_Image(
      "captcha[image]",
      array("id" => "captcha_image"),
      $_options)
  );
  if (PEAR::isError($el))
  {
   logentry("buildcaptchafieldset.100: " . $el->toString());
  }
  $fs->setLabel("Human Verification");
  return;
}

function buildnotifytemplatename($type)
{
 switch ($type)
 {
  case "login-success":
  {
   $res = "notify-login-success.tmpl";
   break;
  }
 }
 return $res;
}

function buildtopbaractions()
{
 $actions = [];
 $actions[] = ["name" => "about", "href" => "/about", "title" => "About"];
 $actions[] = ["name" => "index", "href" => "/", "title" => "Home"];
 return $actions;
}

function limitimageuploads($args)
{
  $images = isset($_FILES["images"]) ? $_FILES["images"] : null;
  if (count($images["tmp_name"]) > 2)
  {
    logentry("limitimageuploads.100: > 2");
    return False;
  }
  return True;
}
/**
 * add a fieldset for handling a trailer
 * 
 * @since 20160328
 */
function buildsearchfieldset($form, $options=null)
{
  $fieldset = $form->addElement("fieldset");
  $fieldset->setLabel("search");
  $field = $fieldset->addElement("text", "keywords");
  $field->setLabel("keywords");
  $field->addRule("required", "must specify at least one keyword");
  
  $searchtypegroup = $fieldset->addElement("group", null, [], ["separator" => "&nbsp;"]);
  $searchtypegroup->setLabel("Search Type");
  $field = $searchtypegroup->addElement("radio", "searchtype", ["value" => "cl"]);
  $field->setLabel("craigslist");
  $field = $searchtypegroup->addElement("radio", "searchtype", ["value" => "govdeals"]);
  $field->setLabel("govdeals");
  return;
}

function buildclsearchfieldset($form, $options=null)
{
  $fieldset = $form->addElement("fieldset");
  $fieldset->setLabel("cl specific search parameters");
  return;
}
/**
 * @since 20160411
 */
function buildtopbardata()
{
 $topbardata = [];
 $topbardata["left"] = buildtopbaractions();
 return $topbardata;
}

/**
 * @since 20160421
 */
function buildsigactions($sig)
{
  $actions = [];
  $siguri = TEOSURL.$sig["url"];
  
  if (accesssig("edit", $sig) === True)
  {
   $actions[] = ["name" => "edit", "href" => $siguri."edit-sig", "title" => "edit", "class" => "fa fa-fw fa-edit"];
  }
  if (accesssig("add", $sig) === True)
  {
   $actions[] = ["name" => "add subfolder", "href" => $siguri."add-sig", "title" => "add subfolder", "class" => "fa fa-fw fa-plus"];
  }
  if (accesssig("delete", $sig) === True)
  {
   $actions[] = ["name" => "delete", "href" => $siguri."delete-sig", "title" => "delete folder", "class" => "fa fa-fw fa-remove"];
  }
  return $actions;
}

/**
 * copied from zoidweb2
 * 
 * @since 20160426
 */
function sortsidebarmenu($a, $b)
{
  $foo = isset($a["title"]) ? $a["title"] : null;
  $bar = isset($b["title"]) ? $b["title"] : null;

  if ($foo < $bar) return -1;
  if ($foo == $bar) return 0;
  if ($foo > $bar) return 1;
}

/**
 * copied from zoidweb2
 * 
 * @since 20160426
 */
function buildsidebarmenu($menu=[])
{
//  $menu[] = array("name" => "addtrailer", "title" => "add trailer to this category", "url" => "add-trailer", "desc" => "add trailer");
  uasort($menu, "sortsidebarmenu");
  logentry("buildsidebarmenu.100: menu=".var_export($menu, True));
  return $menu;
}

function buildprofilefieldset($form)
{
  $fieldset = $form->addFieldset("Contact Information");
  $field = $fieldset->addText("name");
  $field->addRule("required", "'Name' is a required field");
  $field->setLabel("Name");
  
  $field = $fieldset->addText("phonenumber");
  $field->addRule("required", "'Phone Number' is a required field");
  $field->setLabel("Phone Number");
  
  $field = $fieldset->addText("email");
  $field->addRule("required", "'E-Mail Address' is a required field");
  $field->setLabel("E-Mail Address");
  
  $field = $fieldset->addTextArea("specialinstructions");
  $field->setLabel("Special Instructions");
  
  return;
  
}

function buildprofilerecord($values)
{
  $profile = [];
  $profile["name"] = $values["name"];
  $profile["email"] = $values["email"];
  $profile["phonenumber"] = $values["phonenumber"];
  
  return $profile;
}

function getprofile($profileid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getprofile.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select * from profile where id=?";
  $dat = [$profileid];
  $res = $dbh->getRow($sql, null, $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("getprofile.100: " . $res->toString());
    return $res;
  }

  return $res;
}

function getmemberprofile($memberid)
{
  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getprofile.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select * from profile where memberid=?";
  $dat = [$memberid];
  $res = $dbh->getRow($sql, null, $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("getprofile.100: " . $res->toString());
    return $res;
  }

  return $res;

}

/** 
 * @since 20160514
 */
function getcurrentmemberprofile()
{
  $currentmemberid = getcurrentmemberid();
  return getmemberprofile($currentmemberid);
}
?>

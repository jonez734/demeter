<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

function buildpluginfilepath($smarty, $name)
{
  foreach ($smarty->getPluginsDir() as $plugindir)
  {
    $p = $plugindir.DIRECTORY_SEPARATOR.basename($name);
    if (file_exists($p))
    {
      return $p;
    }
  }
  return null;
}

function smarty_function_teos($options, Smarty_Internal_Template $template)
{
//  logentry("options=".var_export($options, True));
//  $template->smarty->loadPlugin("modifier.escape.php");
//  $template->smarty->loadPlugin("modifier.wpprop.php");
  require_once(buildpluginfilepath($template->smarty, "modifier.escape.php"));
  require_once(buildpluginfilepath($template->smarty, "modifier.wpprop.php"));

  $labelpath = isset($options["path"]) ? $options["path"] : null;
  $labelpath = normalizelabelpath($labelpath);
  
  $itemprop = isset($options["itemprop"]) ? $options["itemprop"] : False;
  
  $link = isset($options["link"]) ? $options["link"] : True;
  
  
//  logentry("function.teos.200: path=".var_export($options["path"], True)." labelpath=".var_export($labelpath, True));

  $dbh = dbconnect(SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("function.teos.100: ". $dbh->toString());
    return "TEOS.100";
  }

//  logentry("function.teos.101: labelpath=".var_export($labelpath, True));

  $sql = "select coalesce(engine.sig.title, engine.sig.name) as name, url from engine.sig where path=?";
  $dat = array($labelpath);
  $res = $dbh->getRow($sql, array("text", "text"), $dat, array("text"));
  if (PEAR::isError($res))
  {
    logentry("function.teos.102: " . $res->toString());
    return "TEOS.102";
  }

  if ($res === null)
  {
    logentry("function.teos.104: path ".var_export($labelpath, True)." not found");
    return "TEOS.104";
  }
  
  $title = isset($options["title"]) ? $options["title"] : $res["name"];

  $href = TEOSURL;

  // http://stackoverflow.com/a/5448671
  $href .= (substr($href, -1) == '/' ? '' : '/');
  $href .= $res["url"];

  $title = smarty_modifier_escape($title);
  $title = smarty_modifier_wpprop($title);

//  logentry("title=".var_export($title, True));
  
  $buf = "<span itemprop=\"title\">{$title}</span>";
  if ($link === True)
  {
    $buf = "<a class=\"tooltip teosfolder\" data-contenturl=\"{$href}detail?bare\" itemprop=\"url\" href=\"{$href}\">{$buf}</a>";
  }
/*
  if ($itemprop === True)
  {
    if ($link === True)
    {
      $buf = "<a class=\"tooltip teosfolder\" data-contenturl=\"{$href}detail?bare\" itemprop=\"url\" href=\"{$href}\">{$buf}</a>";
    }
  }
  else
  {
    $buf = "<a class=\"tooltip teosfolder\" data-contenturl=\"{$href}detail?bare\" href=\"{$href}\"><span>{$title}</span></a>";
  }
*/
  return $buf;
}
?>

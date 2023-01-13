<?php

require_once("config.php");
require_once("demeter.php");
require_once("bbsengine3.php");

class page
{
  function main()
  {
    startsession();

    $name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : null;
    if ($name === null)
    {
      displayerrorpage("page not found", 404);
      return;
    }

    logentry("page.102: name=".var_export($name, True));

    setcurrentsite(SITENAME);
    setreturnto(getcurrenturi());
    setcurrentpage($name);
    setcurrentaction("view");
    
    logentry("page.108: name=".var_export($name, True));
    
    $tmpl = getsmarty();

    $pagedata = [];
    if ($tmpl->templateExists("{$name}.tmpl") === False)
    {
      displayerrorpage("template not found (code: page.110)", 404);
      logentry("page.110: template ".var_export($name, True)." does not exist");
      return;
    }
    
    $options = [];
    $options["pagedata"]["body"] = $tmpl->fetch("{$name}.tmpl");
    $options["pagedata"]["topbardata"] = buildtopbardata();
    
    $page = getpage($name, $options);
    if (is_readable(SKINURL."css/{$name}"))
    {
      $page->addStyleSheet(SKINURL."css/{$name}");
    }
    displaypage($page, $options);
    return;
  }
};

$a = new page();
$b = $a->main();
if (PEAR::isError($b))
{
  displayerrorpage($b->getMessage());
  exit;
}

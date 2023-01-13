<?php

require_once("config.php");
require_once("demeter.php");
require_once("bbsengine3.php");

class index 
{
    function main()
    {
        startsession();
        clearpageprotocol();

        $page = getpage("demeter");
        $tmpl = getsmarty();
        $options = [];
        $options["pagedata"]["body"] = $tmpl->fetch("index.tmpl");
        $options["pagedata"]["topbardata"] = buildtopbardata();
        
        setcurrentsite(SITENAME);
        setcurrentaction("view");
        setcurrentpage("index");
        displaypage($page, $options);
        return;
    }
};

$a = new index();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("index.900: " . $b->toString());
    displayerrorpage($b->toString());
    exit;
}
?>

<?php

// this module is based on teos.zoidtechnologies.com

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

class catalog
{
    function main()
    {
        startsession();

        setcurrentsite(SITENAME);
        setcurrentpage("catalog");
        
        $sig = isset($_REQUEST["sig"]) ? $_REQUEST["sig"] : null;
        logentry("catalog.100: sig=".var_export($sig, True));
        // top
        if ($sig === null || $sig === "")
        {
            $foo = scandir(CATALOGDIR);
            $dirs = [];
            foreach ($foo as $bar)
            {
                if ($bar === "." || $bar === "..")
                {
                    continue;
                }
                $name = CATALOGDIR.$bar;
                if (is_dir($name) === True && is_readable($name) === True)
                {
                    if (is_readable($name."/.htsettings"))
                    {
                        $settings = parse_ini_file($name."/.htsettings");
                        if ($settings === False)
                        {
                            $title = "NEEDINFO (error parsing settings)";
                        }
                        $title = isset($settings["title"]) ? $settings["title"] : "NEEDINFO";
                    }
                    else
                    {
                        $title = "NEEDINFO (no htsettings)";
                    }
                    $dir = ["title" => $title, "uri" => "/catalog/".basename($name)];
                    $dirs[] = $dir;
                }
            }
            $page = getpage("Catalog");
            $page->setTitle($title);

            $tmpl = getsmarty();
            $tmpl->assign("dirs", $dirs);

            $options["pagedata"]["body"] = $tmpl->fetch("catalog/index.tmpl");
            $options["pagedata"]["topbardata"] = buildtopbardata();

            displaypage($page, $options);
            return;
        }
        else
        {
            $basedir = CATALOGDIR.$sig;
            $baseuri = "/catalog/{$sig}";
            $exploded = explodeuri($sig);
            setcurrentpage($sig); // exploded[0]);
            logentry("catalog.200: basedir=".var_export($basedir, True). " baseuri=".var_export($baseuri, True));
            if (is_readable($basedir."/.htsettings"))
            {
                $settings = parse_ini_file($basedir."/.htsettings");
                $title = isset($settings["title"]) ? $settings["title"] : "NEEDINFO";
            }
            else
            {
                $title = "NEEDINFO (". var_export($basedir."/.htsettings", True). " not readable)";
            }
            $tmpl = getsmarty();
            $tmpl->assign("baseuri", $baseuri);
            $template = $basedir."/index.tmpl";
            
            logentry("catalog.202: template=".var_export($template, True));
            
            $options = [];
            if (is_readable($template) === False)
            {
                logentry("catalog.204: is_readable(".var_export($template, True).") returned false");
                return PEAR::raiseError("template not found (code: catalog.204)");
            }
            setcurrentpage($sig);
            $options["pagedata"]["body"] = $tmpl->fetch($template);
            $options["pagedata"]["topbardata"] = buildtopbardata();
            $page = getpage(SITETITLE." ".$title);
            $res = displaypage($page, $options);
            if (PEAR::isError($res))
            {
                logentry("catalog.206: " . $res->toString());
                return PEAR::raiseError("page display error (code: catalog.206)");
            }
        }
        return $res;
    }
};

$a = new catalog();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("catalog.999: " . $b->toString());
    displayerrorpage($b->getMessage(), 200);
    exit;
}
?>

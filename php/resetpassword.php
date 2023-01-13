<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

$memberid = 2;
$hashedpassword = hashpassword("Hurons");
setpassword($memberid, $hashedpassword);

print "OK -- changed password for memberid={$memberid}";
exit;

?>

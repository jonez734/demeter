<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

function smarty_function_trailer($params, Smarty_Internal_Template $template)
{
    $productcode = $params["productcode"];
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
        logentry("function.trailer.100: " . $dbh->toString());
        return "ERROR code: function.trailer.100";
    }
    $sql = "select 1 from trailer where code=?";
    $dat = [$productcode];
    $res = $dbh->getOne($sql, null, $dat);
    if (PEAR::isError($res))
    {
        logentry("function.trailer.102: " . $res->toString());
        return "ERROR code: function.trailer.102";
    }
    if ($res === null)
    {
        logentry("function.tailer.104: search for ".var_export($productcode, True)." returned null");
        return "ERROR code: function.tailer.106";
    }

    $output = "<a href=\"/cart-add/{$productcode}\">Add to RFQ</a>";
    return $output;
}

?>

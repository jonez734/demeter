<?php

require_once("config.php");
require_once("ssi.php");
require_once("bbsengine3.php");

class cart
{
    var $dbh = false;

    function clear()
    {
        $cartid = isset($_REQUEST["cartid"]) ? intval($_REQUEST["cartid"]) : null;
        $cart = getcart($cartid);
        if (PEAR::isError($cart))
        {
            logentry("cart.600: " . $cart->toString());
            return PEAR::raiseError("cart error (code: cart.600)");
        }
        if ($cart === null)
        {
            logentry("cart.610: getcart(".var_export($cartid, True).") returned null");
            return PEAR::raiseError("cart error (code: cart.610)");
        }
        if (accesscart("clear", ["cart" => $cart]) === False)
        {
            logentry("cart.620: permission denied clearing cart with id ".var_export($cartid, True));
            displaypermissiondenied();
            return;
        }
        $res = clearcart($cartid);
        if (PEAR::isError($res))
        {
            logentry("cart.630: " . $res->toString());
            return PEAR::raiseError("error clearing cart (code: cart.630)");
        }
        displayredirectpage("OK -- cart cleared of items");
        return;
    }

    function additem()
    {
        $currentcartid = getcurrentcartid();
        if ($currentcartid === null)
        {
            $cart = [];
            $cart["memberid"] = getcurrentmemberid();
            $cart["sessionid"] = session_id();
            $cart["datecreated"] = "now()";
            $cart["dateupdated"] = "now()";
            $res = $this->dbh->autoExecute("__cart", $cart, MDB2_AUTOQUERY_INSERT);
            if (PEAR::isError($res))
            {
                logentry("cart.150: " . $res->toString());
                return PEAR::raiseError("cart create error (code: cart.150)");
            }
            $currentcartid = $this->dbh->lastInsertId();
            if (PEAR::isError($currentcartid))
            {
                logentry("cart.152: " . $currentcartid->toString());
                return PEAR::raiseError("cart create error (code: cart.152)");
            }
            setcurrentcartid($currentcartid);
        }
        $productcode = isset($_REQUEST["productcode"]) ? $_REQUEST["productcode"] : null;
        $product = getproductbycode($productcode);
        if (PEAR::isError($product))
        {
            logentry("cart.100: " . $product->toString());
            return PEAR::raiseError("database error (code: cart.100)");
        }
        if ($product === null)
        {
            logentry("cart.102: getproductcode(".var_export($productcode, True).") returned null");
            return PEAR::isError("database error (code: cart.102)");
        }

        $trailerid = isset($product["id"]) ? intval($product["id"]) : null;
        
        $cartitem = [];
        $cartitem["trailerid"] = $trailerid;
        $cartitem["cartid"] = $currentcartid;
        
        $res = $this->dbh->autoExecute("__cartitem", $cartitem, MDB2_AUTOQUERY_INSERT);
        if (PEAR::isError($res))
        {
            logentry("cart.104: " . $res->toString());
            return PEAR::isError("database insert error (code: cart.104)");
        }
        displayredirectpage("OK -- item added to cart");
        return True;
    }

    function summary()
    {
        $cartid = isset($_REQUEST["cartid"]) ? intval($_REQUEST["cartid"]) : getcurrentcartid();
        logentry("cart.260: cartid=".var_export($cartid, True));
        $cart = getcart($cartid);
        if (PEAR::isError($cart))
        {
            logentry("cart.200: " . $cart->toString());
            return PEAR::raiseError("cart error (code: cart.200)");
        }
        if ($cart === null)
        {
            logentry("cart.210: getcart(".var_export($cartid, True).") returned null");
            return PEAR::raiseError("cart error (code: cart.210)");
        }
        if (accesscart("view", ["cart" => $cart]) === False)
        {
            displaypermissiondenied();
            return;
        }
        $page = getpage("Cart Summary");
        $page->addStyleSheet(STATICSKINURL."css/trailer.css");
        $tmpl = getsmarty();
        $tmpl->assign("cart", $cart);
        $options["pagedata"]["body"] = $tmpl->fetch("cart-summary.tmpl");
        
        setcurrentaction("summary");

        displaypage($page, $options);
        return;
    }
    
    function main()
    {
        startsession();
        clearpageprotocol();
        
        $this->dbh = dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            logentry("cart.995: " .$this->dbh->toString());
            return PEAR::raiseError("database connect error (code: cart.995)");
        }
        
        setcurrentpage("cart");

        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        switch ($mode)
        {
            case "additem":
            {
                $res = $this->additem();
                break;
            }
            case "summary":
            {
                $res = $this->summary();
                break;
            }
            case "clear":
            {
                $res = $this->clear();
                break;
            }
            default:
            {
                logentry("cart.997: unknown mode ".var_export($mode, True));
                $res = PEAR::raiseError("unknown mode (code: cart.997)");
                break;
            }
        }
        endsession();
        return $res;
    }
};

$a = new cart();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("cart.999: " . $b->toString());
    displayerrorpage($b->getMessage());
    exit;
}
?>

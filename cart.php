<?
# Product management extension for dev/con cms.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# This file contains tag and document functions for directories of type
# PRODUCT and CART.


# Describe directory hierarchy for dbobj.class.
if (!isset ($_MERGED_DIRS)) {
    $dep->set_ref ('categories', 'categories', 'id_parent');
    $dep->set_ref ('categories', 'pages',      'id_category');
    $dep->set_ref ('pages',      'products',   'id_page');
    $dep->set_ref ('products',   'cart',       'id_product');
    $dep->set_primary ('categories', 'id');
    $dep->set_primary ('pages', 'id');
    $dep->set_primary ('products', 'id');
    $dep->set_primary ('cart', 'id');

    # Create directory types.
    $scanner->assoc ('CATEGORY', 'categories');
    $scanner->assoc ('PAGE',     'pages');
    $scanner->assoc ('PRODUCT',  'products');
    $scanner->assoc ('SESSION',  true);
    $scanner->assoc ('CART',     true);
}

# Register cart tags.
$scanner->dirtag ('PRODUCT', 'QUANTITY PRICE TOTAL FORMQUANTITY QUANTITYNAME LIST-ATTRS NUM-ATTRS ATTR');
$scanner->dirtag ('CART', 'LIST LINK QUANTITY TOTAL IS-EMPTY');

$product_attr = '0'; # LIST-ATTRS can't be nested.


####################
### PRODUCT tags ###
####################

# Return quantity of current product in user's cart.
function dirtag_product_quantity ($dummy)
{
    global $session, $scanner, $db;

    if (!$id = $session->id ())
        return 0;
    $q = isset ($scanner->context['attrib']) ? '"' . $scanner->context['attrib'] . '"' : '0';
    $res = $db->select ('quantity', 'cart', "id_session=$id AND id_product=" . $scanner->context['id'] . " AND attrib=$q");
    return $res ? $res->get ('quantity') : 0;
}

function dirtag_product_quantityname ($dummy)
{
    global $scanner, $product_attr;

    $attr = isset ($scanner->context['attrib']) ? $scanner->context['attrib'] : $product_attr;
    if (!trim ($attr))
        $attr = '0';

    return 'quant[' . $scanner->context['id'] . ',' . urlencode ($attr) . ']';
}

function dirtag_product_formquantity ($dummy)
{
    $quant = dirtag_product_quantity ('');
    if (!$quant)
        return '1';
    return $quant;
}

# TODO: Fetch price from object.
function dirtag_product_price ($attr)
{
    global $scanner;

    $currency = strtolower ($attr['currency']);
    # TODO: Global config: default currency.
    if (!$currency)
        $currency = 'dm';
    if (!isset ($scanner->context["price_$currency"]))
        return '0,00';
    return number_format ($scanner->context["price_$currency"], 2);
}

function dirtag_product_total ($attr)
{
    $currency = strtolower ($attr['currency']);
    return number_format (dirtag_product_price ($attr) * dirtag_product_quantity (''), 2);
}

function dirtag_product_list_attrs ($attr)
{
    global $product_attr, $scanner;

    $obj = cms_fetch_object ('u_attrib_mask');
    if (!$obj)
        return '<b>PRODUCT:LIST-ATTRS: No object of class u_attribs.</b>';

    $arr = unserialize ($obj);
    if (is_array ($arr))
        foreach ($arr as $record)
	    if (isset ($record['name']))
                $attr[$record['name']] = $record['is_used'];

    # Scan template for each set attribute.
    $out = '';
    foreach ($attr as $name => $flag) {
        if (!$flag)
            continue;
        $product_attr = $name;
        $branch = $scanner->scan ($attr['_']);
        $out .= $scanner->exec ($branch);
        $product_attr = '0';
    }

    return $out;
}

function dirtag_product_num_attrs ($attr)
{
    global $product_attr, $scanner;

    $obj = cms_fetch_object ('u_attrib_mask');
    if (!$obj)
        return '<b>PRODUCT:NUM-ATTRS: No object of class u_attribs.</b>';

    $arr = unserialize ($obj);
    if (is_array ($arr))
        foreach ($arr as $record)
	    if (isset ($record['name']))
                $attr[$record['name']] = $record['is_used'];

    # Scan template for each set attribute.
    $num = '0';
    foreach ($attr as $name => $flag)
        if ($flag)
            $num++;

    return $num;
}

function dirtag_product_attr ($attr)
{
    global $product_attr, $scanner, $db, $session;

    if ($scanner->parent_dirtype != 'CART' && $scanner->parent_dirtype != 'ORDER') {
        if (!$product_attr)
            return;
        return $product_attr;
    }

    $res = $db->select ('attrib', 'cart', 'id_product=' . $scanner->context['id'] . ' AND id_session=' . $session->id());
    return $res ? $res->get ('attrib');
}


#################
### CART tags ###
#################

function update_product_quantity ($id, $quantity, $attribute)
{
    global $db, $session;

    if (!$attribute)
        $attribute = "0";

    $res = $db->select ('id,quantity', 'cart', 'id_session=' . $session->id () . " AND id_product=$id AND attrib=\"$attribute\"");
    if ($res) {
        $r = $res->get ();
        $s = substr ($quantity, 0, 1);
        if ($s == '+' || $s == '-')
            $quantity = ((int) $r['quantity'] + (int) $quantity);
        if ($quantity < 0)
            $quantity = 0;
        if (!$quantity)
            $db->delete ('cart', 'id=' . $r['id']);
        else
 	    $db->update ('cart', 'quantity=' . $quantity, 'id=' . $r['id'] . " AND attrib=\"$attribute\"");
    } else
        $db->insert ('cart', 'id_session=' . $session->id () . ", id_product=$id, quantity=" . (int) $quantity . ", attrib=\"$attribute\"");
}

function cart_is_empty ()
{
    global $db;

    return !$session->id () || !$db->select ('id', 'cart', 'id_session=' . $session->id ());
}

# Update cart.
# See also: dirtag_product_form.*() and dirtag_product_quantity.*()
function document_cart ()
{
    global $path, $quant, $PHP_SELF, $session, $dep, $scanner, $db, $path_tail, $use_cookies, $SCRIPT_NAME, $SERVER_NAME, $url_vars;

    $attr = isset ($path_tail[1]) ? urldecode ($path_tail[1]) : '0';
    $attr = addslashes ($attr);

    # Determine if there's already a valid session key.
    !$session->key () ? $NEWKEY = true : $NEWKEY = false;

    # Update quantities in cart if there's a $quant array.
    if (is_array ($quant)) {
        # Force a session key if we need one.
        if ($NEWKEY)
            $session->force_key ();

        # Update quantity of each item.
        foreach ($quant as $name => $q) {
            $p = strpos ($name, ',');
            if ($p === false)
                die ('document_cart(): No attribute in quantityname.');
            $id = substr ($name, 0, $p);
            $at = urldecode (substr ($name, $p + 1));

	    # Set alarm if ID is not numeric. This means that someone fiddles
	    # around with the software.
	    if (!is_numeric ($id))
	        panic ($GLOBALS['REMOTE_ADDR'] . ' tries SQL hacking?', 'Form field: Product id is not an integer.');

	    # Empty quantity fields are considered to be 0.
	    if (trim ($q) == '')
	        $q = 0;
	    else
                if (!is_numeric ($q))
                    continue;	# Just skip this item if quantity is not numeric.

	    update_product_quantity ($id, $q, $at);
        }
    } else {
        # Process cart URL with quantities in it.
        if (isset ($path_tail[0]) && $path_tail[0]) {
	    # Force a session if there is none.
            if ($NEWKEY) {
                $session->force_key ();
	        if ($use_cookies)
	            $ret = setcookie ('SESSION_KEY', $session->key (), time () + 3600, $SCRIPT_NAME, $SERVER_NAME);

                # Force session key in url variables in case cookie wasn't set.
                $url_vars['SESSION_KEY'] = $session->key ();
            }

	    # Since cart URLs are generated by the shop, there should be
	    # no invalid data. Set alarm if there is.
	    if (!is_numeric ($path_tail[0]))
	        panic ($GLOBALS['REMOTE_ADDR'] . ' tries SQL hacking?', 'Quantity update: Product id is not an integer.');
	    if (isset ($path_tail[2]) && !is_numeric ($path_tail[2]))
	        panic ($GLOBALS['REMOTE_ADDR'] . ' tries SQL hacking?', 'Quantity update: Quantity is not an integer.');

            update_product_quantity ($path_tail[0], isset ($path_tail[2]) ? $path_tail[2] : 0, $attr);
        }
    }

    # Redirect to l_empty_cart if there still is no session or if the
    # cart is empty.
    if (cart_is_empty ()) {
        $templ_empty = cms_fetch_object ('l_empty_cart');
        if (!$templ_empty)
            return '<b>CART: No template for empty cart l_empty_cart.</b>';
        document_set_template ($templ_empty);
        return;
    }

    # Redirect to new url with session key to make it hard to click back
    # (and loose the cart's contents).
    if ($NEWKEY) {
        echo '<HTML><HEAD><META HTTP-EQUIV="REFRESH" CONTENT="0;URL=' . dirtag_cart_link ('') . '"></HEAD></HTML>';
        exit;
    }
}

# Create link to cart document.
function dirtag_cart_link ($attr)
{
    global $scanner, $vdir_alias, $product_attr;

    @$quantity = $attr['quantity'];

    # If called without arguments no quantitiy is changed.
    if ($quantity == '')
        return tag_link (array ('template' => $vdir_alias['CART']));

    return tag_link (array ('template' => $vdir_alias['CART'] . '/' . $scanner->context['id'] . '/' . urlencode ($product_attr) . "/$quantity"));
}
 
function dirtag_cart_list ($attr)
{
    global $session, $scanner, $dep, $db;

    $table = $scanner->context_table;

    # Query all products in the cart.
    $res = $db->select ('id_product,attrib', 'cart', 'id_session=' . $session->id ());

    # Create indexed record set.
    $i = 0;
    while ($res && $cartitem = $res->get ()) {
        $set[$i] = cms_fetch_directory ('products', $cartitem['id_product']);
        $set[$i++]['attrib'] = $cartitem['attrib'];
    }

    # Let the CMS do the rest.
    $scanner->context_table = 'products';
    $list = cms_process_list ($set, $attr['_']);
    $scanner->context_table = $table;

    return $list;
}

# Return total price of products in cart.
function dirtag_cart_total ($attr)
{
    global $session, $scanner, $db;

    $currency = !isset ($attr['currency']) ? $currency = 'dm' : strtolower ($attr['currency']);

    $res = $db->select ('id_product,quantity', 'cart', 'id_session=' . $session->id ());
    for ($total = 0; $res && list ($id_prod, $quant) = $res->get (); $total += $price * $quant) {
        $res2 = $db->select ("price_$currency", 'products', "id=$id_prod");
        list ($price) = $res2->get ();
    }
    return number_format ($total, 2);
}

# Return total number of products in user's cart.
function dirtag_cart_quantity ($dummy)
{
    global $session, $scanner, $db;

    $quantity = 0;
    if ($sid = $session->id ())
        if ($res = $db->select ('SUM(num)', 'cart', 'id_session=' . $session->id ()))
            list ($quantity) = $res->get ();
    return $quantity;
}

function dirtag_cart_is_empty ($args)
{
    return cart_is_empty ();
}
?>

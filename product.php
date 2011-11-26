<?
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Register tags.                                                                                                                                                                 
$scanner->dirtag ('PRODUCT', 'QUANTITY PRICE TOTAL FORMQUANTITY QUANTITYNAME LIST-ATTRS NUM-ATTRS ATTR');

$product_attr = '0'; # LIST-ATTRS can't be nested.

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
    return dirtag_product_quantity ('') || '1';
}

# TODO: Fetch price from object.
function dirtag_product_price ($attr)
{
    global $scanner;

    $currency = strtolower ($attr['currency']);
    # TODO: Global config: default currency.
    if (!$currency)
        $currency = 'dm';
    return (!isset ($scanner->context["price_$currency"])) ?
           number_format ($scanner->context["price_$currency"], 2) :
           '0,00';
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

    if ($scanner->parent_dirtype != 'CART' && $scanner->parent_dirtype != 'ORDER')
        return $product_attr;

    $res = $db->select ('attrib', 'cart', 'id_product=' . $scanner->context['id'] . ' AND id_session=' . $session->id());
    return $res ? $res->get ('attrib');
}

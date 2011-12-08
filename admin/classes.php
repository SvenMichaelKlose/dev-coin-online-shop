<?php

# Object classes.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose (sven@devcon.net)
#
# Licensed under the MIT, BSD and GPL licenses.
  
function class_init (&$app)
{
    $app->add_function ('view_classes');
    $app->add_function ('view_class');
}

function tag ($content)
{
    return "<B>@@$content@@</B>";
}

# Return tag that uses a particular object class.
function _class2tag ($name)
{
    global $lang;
    $arg = '';
    if (substr ($name, 0, 2) == 'd_')
        return 'Konfiguration';
    if (substr ($name, 0, 2) == 'u_')
        return tag (":OBJECTLINK&nbsp;$name") . ' (' . $lang['user defined'] . ')';
    if ($name == 'l_cart')
        return tag ('CART');
    if ($name == 'l_ecml')
        return tag ('ORDER');
    if ($name == 'l_order')
        return tag ('ORDER');
    if ($name == 'l_order_email' || $name == 'l_order_confirm' || substr ($name, 0, 2) == 'd_' || $name == 'l_index')
        return '-';
    if (substr ($name, 0, 2) == 'l_') {
        if ($name == 'l_pages') {
            $arg = " $name";
	    $name = 'l_page';
        } else
            if ($name == 'l_empty_cart')
	        $name = 'l_cart';
        return tag (strtoupper (substr ($name, 2)) . ":LINK$arg");
    } else if (substr ($name, 0, 3) == 'll_') {
        if ($name == 'll_pages')
            $arg = " $name";
        else
            if ($name == 'll_page_indices') {
                $arg = " $name";
	        $name = 'll_page';
            } else
                if ($name == 'll_category_group')
                    return tag ('CATEGORY:LIST-GROUP');
                else
                    if ($name == 'll_order_email' || $name == 'll_order_confirm')
                        $name = 'll_order';
        return tag (strtoupper (substr ($name, 3)) . ":LIST$arg");
    }
    return $name . ' <FONT COLOR="RED">(' . $lang['illegal name'] . ')</FONT>';
}

function view_class (&$app)
{
    global $lang;
    $p =& $app->ui;
    $c = $app->arg ("_cursor");
    $p->set_cursor ($c);

    $p->headline ($lang['title view_class']);
    tk_autoform_create_form ($app, $app->arg ("_cursor"));
}

function view_classes (&$app)
{
    $c = new generic_list_conf;
    $c->child_table = 'obj_classes';
    $c->child_view = 'view_class';
    $c->have_submit_button = true;
    generic_list ($app, $c);
}

?>

<?
# Object classes.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose (sven@devcon.net)
#
# Licensed under the MIT, BSD and GPL licenses.
  

function class_init (&$app)
{
    $app->add_function ('view_classes');
    $app->add_function ('edit_class');
}

# View classes.
# No arguments.
function view_classes (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->headline ($lang['title view_classes']);
    $p->link ($lang['title defaultview'], 'defaultview', 0);
    $p->no_update = true;
    $p->open_source ('obj_classes');
    $p->table_headers (Array ('Syntax', $lang['class'], $lang['description']));
    $p->query ('', 'ORDER BY name ASC');
    while ($p->get ()) {
        $p->open_row ();
        $p->label (_class2tag ($p->value ('name')));

        $e = new event ('edit_class');
        $p->link ($p->value ('name'), $e);

        $v = $p->value ('descr');
        if (!$v)
            $v = '[' . $lang['unnamed'] . ']';
        $p->link ($v, $e);
        $p->close_row ();
    }
    $p->paragraph ();  
    $p->cmd_create ($lang['cmd create class'], 'view_classes');  
    $p->close_source ();
}

# Edit class name.
# id = Key of class in table obj_classes.
function edit_class (&$app)
{
    global $lang;
    $p =& $app->ui;
    $p->headline ($lang['title edit_class']);
    $p->open_source ('obj_classes');
    $p->query ('id=' . $app->arg ('_cursor')->key ());
    $p->get ();
    $p->inputline ('name', 64, $lang['class name']);
    $p->label ('<B>' . _class2tag ($p->value ('name')) . '</B>');
    $p->paragraph ();
    $p->inputline ('descr', 64, $lang['description']);
    $p->paragraph ();
    $p->open_row ();
    $p->cmd_update (null, 'view_classes');
    $p->cmd_delete (null, 'view_classes');
    $p->close_row ();
    $p->close_source ();
}

# Return tag that uses a particular object class.
# $name = Class name.
function _class2tag ($name)
{
    global $lang;
    $arg = '';
    if (substr ($name, 0, 2) == 'd_')
        return 'Konfiguration';
    if (substr ($name, 0, 2) == 'u_')
        return "<B>&lt;!:OBJECTLINK&nbsp;$name!&gt;</B> (" . $lang['user defined'] . ')';
    if ($name == 'l_cart')
        return '<B>&lt;!CART!&gt;</B>';
    if ($name == 'l_ecml')
        return '<B>&lt;!ORDER!&gt;</B>';
    if ($name == 'l_order')
        return '<B>&lt;!ORDER!&gt;</B>';
    if ($name == 'l_order_email' || $name == 'l_order_confirm' || substr ($name, 0, 2) == 'd_' || $name == 'l_index')
        return '-';
    if (substr ($name, 0, 2) == 'l_') {
        if ($name == 'l_pages') {
            $arg = " $name";
	    $name = 'l_page';
        } else
            if ($name == 'l_empty_cart')
	        $name = 'l_cart';
        return '<b>&lt;!' . strtoupper (substr ($name, 2)) . ":LINK$arg!&gt;</b>";
    } else if (substr ($name, 0, 3) == 'll_') {
        if ($name == 'll_pages')
            $arg = " $name";
        else
            if ($name == 'll_page_indices') {
                $arg = " $name";
	        $name = 'll_page';
            } else
                if ($name == 'll_category_group')
                    return '<B>&lt;!CATEGORY:LIST-GROUP!&gt;</B>';
                else
                    if ($name == 'll_order_email' || $name == 'll_order_confirm')
                        $name = 'll_order';
        return '<b>&lt;!' . strtoupper (substr ($name, 3)) . ":LIST$arg!&gt;</b>";
    }
    return $name . ' <FONT COLOR="RED">(' . $lang['illegal name'] . ')</FONT>';
}

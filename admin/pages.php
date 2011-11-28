<?
# Product group editors.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 dev/consulting GmbH
#
# Licensed under the MIT, BSD and GPL licenses.


function page_init (&$app)
{
    $app->add_function ('view_pages');
}

function view_pages (&$app)
{
    global $lang;

    $c = new generic_list_conf;
    $c->table = 'categories';
    $c->parent_table = 'categories';
    $c->child_table = 'pages';
    $c->ref_table = 'id_category';
    $c->ref_parent = 'id_parent';
    $c->recordfunc = 'record_page';
    $c->txt_no_records = $lang['msg no product group'];
    $c->txt_create = $lang['cmd create_page'];
    $c->txt_input = $lang['category'];
    $c->parent_view = 'defaultview';
    $c->child_view = 'view_products';
    $c->have_submit_button = true;
    generic_list ($app, $c);
}

function record_page (&$app, $idx)
{
    global $lang;

    $p =& $app->ui;

    $nam = trim ($p->value ('name'));
    if ($nam == '')
        $nam = $lang['unnamed'];

    $p->open_row ();
    $p->checkbox ('marker');
    $p->link ("$idx $nam", new event ('view_products', array ('id' => $p->value ('id'))));
    $p->link ($p->value ('bestnr'), new event ('view_products', array ('id' => $p->value ('id'))));
    $p->link ($p->value ('price_eur'), new event ('view_products', array ('id' => $p->value ('id'))));
    #$p->close_row ();

    #$p->paragraph ();

    #$p->open_row ();
    #_object_box ($app, 'pages', $p->value ('id'), $app->args, true);
    $p->close_row ();

    $p->paragraph ();
}
?>

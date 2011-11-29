<?php

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

function record_page (&$app, $idx)
{
    global $lang;

    $p =& $app->ui;

    $name = trim ($p->value ('name'));
    if ($name == '')
        $name = $lang['unnamed'];

    $p->open_row ();
    $p->checkbox ('marker');
    $p->link ("$idx $name", new event ('view_pages', array ('id' => $p->value ('id'))));
    $p->link ($p->value ('bestnr'), new event ('view_pages', array ('id' => $p->value ('id'))));
    $p->link ($p->value ('price'), new event ('view_pages', array ('id' => $p->value ('id'))));
    $p->close_row ();
}

function view_pages (&$app)
{
    global $lang;

    $c = new generic_list_conf;

    $c->parent_table = 'directories';
    $c->parent_view = 'view_pages';

    $c->table = 'directories';
    $c->ref_parent = 'id_parent';

    $c->child_table = 'directories';
    $c->child_ref_parent = 'id_parent';
    $c->child_view_list = 'record_page';
    $c->child_view = 'view_pages';

    $c->txt_no_records = $lang['msg no product group'];
    $c->txt_create = $lang['cmd create_page'];
    $c->txt_input = $lang['category'];
    $c->have_submit_button = true;

    generic_list ($app, $c);
}

?>

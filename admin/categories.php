<?php

# Category editor
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function category_init (&$app)
{
    $app->add_function ('create_category');
    $app->add_function ('category_overview');
}

# Create a subcategory for a category.
function create_category (&$app)
{
    global $lang;
    $ui = & $app->ui;

    # XXX reference to parent should be set via hash of preset values.
    $nid = $app->db->append_new ('directories', $app->args ('pre'));
    $ui->mark_id = "directories$nid";
    $ui->color_highlight = '#00FF00';
    $ui->msgbox ($lang['msg category created']);
    $app->call (new event ('defaultview'));
}

function category_overview (&$app)
{
    global $lang;

    $p =& $app->ui;

    $conf = new tree_edit_conf;
    $conf->source = 'directories';
    $conf->id = '1';
    $conf->treeview = $app->event ();
    $conf->nodeview = 'view_pages';
    $conf->nodecreator = 'create_category';
    $conf->rootname = 'shop';
    $conf->table = 'directories';
    $conf->name = 'name';
    $conf->id = 'id';
    $conf->preset_values = array ('id_directory_type' => get_directory_type_id ($app->db, 'category'));
    $conf->txt_select_node = $lang['msg choose category to move'];
    $conf->txt_select_dest = $lang['msg choose dest category'];
    $conf->txt_moved = $lang['msg category moved'];
    $conf->txt_not_moved = $lang['err category not moved'];
    $conf->txt_move_again = $lang['cmd move further'];
    $conf->txt_back = $lang['cmd back/quit'];
    $conf->txt_unnamed = $lang['unnamed'];

    $e = new event ('tree_edit_move', array ('conf' => $conf));
    $e->set_caller ($app->event ());
    $p->link ($lang['cmd move_category'], $e);
    tree_edit ($app, $conf);
}

?>

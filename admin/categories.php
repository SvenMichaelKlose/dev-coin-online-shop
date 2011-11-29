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
    $nid = $app->db->append_new ('directories', $app->arg ('id'));
    $ui->mark_id = "directories$nid";
    $ui->color_highlight = '#00FF00';
    $ui->msgbox ($lang['msg category created']);
    $app->call (new event ('defaultview'));
}

function category_overview (&$app)
{
    global $lang;

    $p =& $app->ui;

    $treeargs = array (
        'source' => 'directories',
        'id' => '1',
        'treeview' => $app->event (),
        'nodeview' => 'view_pages',
        'nodecreator' => 'create_category',
        'rootname' => 'shop',
        'table' => 'directories',
        'name' => 'name',
        'id' => 'id',
        'txt_select_node' => $lang['msg choose category to move'],
        'txt_select_dest' => $lang['msg choose dest category'],
        'txt_moved' => $lang['msg category moved'],
        'txt_not_moved' => $lang['err category not moved'],
        'txt_move_again' => $lang['cmd move further'],
        'txt_back' => $lang['cmd back/quit'],
        'txt_unnamed' => $lang['unnamed']
    );

    tree_edit ($app, $treeargs);
}

?>

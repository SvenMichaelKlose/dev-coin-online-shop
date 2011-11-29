<?php

# Generic directory lister.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function _get_index (&$app, $parent, $ref_parent, $table, $id)
{
    $id_parent = $app->db->column ($table, $ref_parent, $id);
    $res = $app->db->select ('id, id_last, id_next', $table, "$ref_parent=$id_parent");
    while ($res && $row = $res->get ())
        $tmp[$row['id_last']] = $row;
    for ($row = reset ($tmp), $i = 1; $row; $row = next ($tmp), $i++)
        if ($row['id'] == $id)
            $thisindex = $i;
    return array ($thisindex, $i - 1);
}

function generic_create (&$app, $c)
{
    $def =& $app->db->def;

    if ($def->is_list ($c->child_table))
        $pre[$def->ref_id ($c->child_table)] = $app->arg ('id');
    $e = new event ('record_create', array ('preset_values' => $pre));
    $e->set_next ($app->event ());
    $app->ui->submit_button ($c->txt_create, $e);
}

function _range_panel (&$app, $c)
{
    global $lang;

    $p =& $app->ui;
    $def =& $app->db->def;
    $m = array ('marker_field' => 'marker');

    $p->paragraph ();

    # Link to creator of new record.
    $p->v->cursor->set_key ('');
    $p->open_row ();

    $e = new event ('tk_range_edit_select', $m);
    $e->set_next ($app->event ());
    $p->submit_button ('select range', $e);

    $sel = tk_range_edit_all_selected ($app, 'marker');

    if ($sel == 0 || $sel == 2) {
        $e = new event ('tk_range_edit_select', $m);
        $e->set_next ($app->event ());
        $p->submit_button ('select all', $e);
    }

    if ($sel == 1 || $sel == 2) {
        $e = new event ('tk_range_edit_unselect', $m);
        $e->set_next ($app->event ());
        $p->submit_button ('select all', $e);
    }

    $e_delete = new event ('record_delete');
    $e_delete->set_next ($app->event ());
    $e = new event ('tk_range_edit_call', array ('view' => $e_delete, 'argname' => 'id', 'marker_fiel' => 'marker'));

    generic_create ($app, $c);

    if ($c->have_submit_button)
        $p->cmd_update ();

    $p->close_row ();

    $p->paragraph ();
}

class generic_list_conf {
    public $parent_table;
    public $parent_view;

    public $table;
    public $ref_parent;

    public $child_table;
    public $child_ref_parent;
    public $child_view_list;
    public $child_view;
    public $headers;
    public $txt_no_func;
    public $txt_create;
    public $txt_input;
    public $have_submit_button = false;
};

function generic_list (&$app, $c)
{
    global $lang;

    $db =& $app->db;
    $def =& $db->def;
    $p =& $app->ui;
    $id = $app->arg ('id');
    $app->event ()->set_arg ('table', $c->table); # Required by _object_box().

    # Navigator
    $p->headline ($lang["title " . $app->event ()->name]);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($app, $c->table, $id);

    # Link to next/last product group.
    $id_last = $db->column ($c->table, 'id_last', $id);
    $id_next = $db->column ($c->table, 'id_next', $id);
    list ($thisindex, $last) = _get_index ($app, $c->parent_table, $c->ref_parent, $c->table, $id);
    if ($id_last) {
        $e = $app->event ();
        $e->set_arg ('id', $id_last);
        $p->link ($lang['previous'], $e);
    }
    echo ' ' . sprintf ($lang['x of y'], $thisindex, $last) . ' ';
    if ($id_next) {
        $e = $app->event ();
        $e->set_arg ('id', $id_next);
        $p->link ($lang['next'], $e);
    }

    # Show all objects for this group.
    _object_box ($app, $c->table, $id, $app->args ());

    # Input field for group name.
    $parent_id = $db->column ($c->table, $c->ref_parent, $id);
    $p->open_source ($c->table);
    $p->query (sql_assignment ('id', $id));

    if ($p->get ()) {
        $p->open_row ();
        $p->cmd_delete ('', $c->parent_view, array ('id' => $parent_id));
        $p->inputline ('name', 255, $c->txt_input);
        $p->cmd_update ();
        $p->close_row ();
    }
    $p->close_source ();

    $p->open_source ($c->child_table);
    $p->use_filter ('form_safe');
    $res = $p->query (sql_assignment ($c->child_ref_parent, $id));

    if ($res) {
        if ($c->headers)
            $p->table_headers ($c->headers);

        $idx = 1;
        while ($p->get ()) {
	    $fun = $c->child_view_list;
	    $fun ($app, $idx);
	    $idx++;
        }

        _range_panel ($app, $c);
    } else {
        $p->label ($c->txt_no_records);
        generic_create ($app, $c);
    }

    $p->close_source ();
}

?>

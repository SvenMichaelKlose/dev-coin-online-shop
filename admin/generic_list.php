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
    $c = new cursor_sql ();
    $c->set_source ($table);
    $c->query ("$ref_parent=$id_parent");
    $i = 1;
    while ($row = $c->get ()) {
        if ($row['id'] == $id)
            return array ($i, $c->size ());
        $i++;
    }
}

function generic_create (&$app, $c)
{
    global $lang;

    $def =& $app->db->def;

    if ($def->is_list ($c->child_table))
        $pre[$def->id_parent ($c->child_table)] = $app->arg ('id');
    else
        $pre = array ();
    $e = new event ('record_create', array ('preset_values' => array_merge ($c->child_values, $pre)));
    $e->set_next ($app->event ());
    $app->ui->submit_button ($lang['cmd create'], $e);
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
    $e = new event ('tk_range_edit_call', array ('view' => $e_delete, 'argname' => 'id', 'marker_field' => 'marker'));

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
    public $values = array ();

    public $child_table;
    public $child_view_list;
    public $child_view;
    public $child_values = array ();
    public $headers;
    public $have_submit_button = false;
};

function generic_list_siblings (&$app, $c)
{
    global $lang;

    $db =& $app->db;
    $def =& $db->def;
    $p =& $app->ui;
    $id = $app->arg ('id');

    $id_last = $db->column ($c->table, 'id_last', $id);
    $id_next = $db->column ($c->table, 'id_next', $id);
    list ($thisindex, $last) = _get_index ($app, $c->table, $def->id_parent ($c->table), $c->table, $id);
    if ($id_last) {
        $e = $app->event ()->copy ();
        $e->set_arg ('id', $id_last);
        $p->link ($lang['previous'], $e);
    }
    echo ' ' . sprintf ($lang['x of y'], $thisindex, $last) . ' ';
    if ($id_next) {
        $e = $app->event ()->copy ();
        $e->set_arg ('id', $id_next);
        $p->link ($lang['next'], $e);
    }
}

function generic_list_editor (&$app, $c)
{
    global $lang;

    $db =& $app->db;
    $def =& $db->def;
    $p =& $app->ui;
    $id = $app->arg ('id');

    $parent_id = $db->column ($c->table, $def->id_parent ($c->table), $id);
    $p->open_source ($c->table);
    $p->query (sql_selection_assignments (array_merge ($c->child_values, array ('id' => $id))));
    if ($p->get ()) {
        $p->open_row ();
        $p->cmd_delete ('', $c->parent_view, array ('id' => $parent_id));
        $p->inputline ('name', 255);
        $p->cmd_update ();
        $p->close_row ();
    }
    $p->close_source ();
}

function generic_list_children (&$app, $c)
{
    global $lang;

    $db =& $app->db;
    $def =& $db->def;
    $p =& $app->ui;
    $id = $app->arg ('id', ARG_OPTIONAL);

    $p->open_source ($c->child_table);
    $p->use_filter ('form_safe');
    $p->cursor ()->set_preset_values ($id ?
                                      array_merge ($c->child_values, array ($def->id_parent ($c->child_table) => $id)) :
                                      $c->child_values);
    $res = $p->query ();
    if ($res) {
        if ($c->headers)
            $p->table_headers ($c->headers);
        $conf = new tk_auto_list_conf;
        $conf->record_view = $c->child_view;
        $conf->record_view_arg = 'id';
        $conf->txt_empty = $lang['empty'];
        $cur = $p->cursor (); # Workaround strict rules.
        tk_autoform_list_cursor ($app, $cur, $conf);
        _range_panel ($app, $c);
    }
    generic_create ($app, $c);

    $p->close_source ();
}

function generic_list (&$app, $conf)
{
    global $lang;

    $db =& $app->db;
    $def =& $db->def;
    $p =& $app->ui;
    $id = $app->arg ('id', ARG_OPTIONAL);
    $app->event ()->set_arg ('table', $conf->table); # Required by _object_box().

    $p->headline ($lang["title " . $app->event ()->name]);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);

    if ($conf->table) {
        show_directory_index ($app, $conf->table, $id, false);
        generic_list_siblings ($app, $conf);
        generic_list_editor ($app, $conf);
        show_directory_objects ($app, $conf->table, $id, $app->args ());
    }
    generic_list_children ($app, $conf);
}

?>

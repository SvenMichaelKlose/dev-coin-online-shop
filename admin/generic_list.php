<?
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

function _range_panel (&$app, $id, $child_view, $txt_create, $table, $have_submit_button = false)
{
    global $lang;

    $p =& $app->ui;

    # Link to creator of new record.
    $p->use_key ('');
    $p->open_row ();
    $p->submit_button ('select range', 'tk_range_edit_select', $app->arg_set_next (0, $app->view, $app->args));
    $sel = tk_range_edit_all_selected ($app);
    if ($sel == 0 || $sel == 2)
        $p->submit_button ('select all', 'tk_range_edit_select_all', $app->arg_set_next (0, $app->view, $app->args));
    if ($sel == 1 || $sel == 2)
        $p->submit_button ('unselect all', 'tk_range_edit_unselect_all', $app->arg_set_next (0, $app->view, $app->args));
    $p->submit_button ('delete', 'tk_range_edit_call', array ('view' => '_delete', 'argname' => 'id',
                                                              'arg' => $app->arg_set_next (array ('table' => $table, '_ssi_obj' => 'dbi'), $app->view, $app->args)));
    $p->cmd_create ($txt_create, $child_view, 'id', $id, $lang['msg record created']);
    if ($have_submit_button)
        $p->submit_button ('Ok', '_update', $app->arg_set_next ());
    $p->close_row ();
}

class generic_list_conf {
    public $table;
    public $parent_table;
    public $child_table;
    public $ref_table;
    public $ref_parent;
    public $headers;
    public $recordfunc;
    public $txt_no_func;
    public $txt_create;
    public $txt_input;
    public $parent_view;
    public $child_view;
    public $have_submit_button = false;
};

# View products within a product group.
function generic_list (&$app, $c)
{
    global $lang;

    $db =& $app->db;
    $app->args['table'] = $c->table; # Needed for _object_box.
    $id = $app->args['id'];
    $p =& $app->ui;

    # Navigator
    $p->headline ($lang["title $app->view"]);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($app, $c->table, $id);

    # Link to next/last product group.
    $id_last = $db->column ($c->table, 'id_last', $id);
    $id_next = $db->column ($c->table, 'id_next', $id);
    list ($thisindex, $last) = _get_index ($app, $c->parent_table, $c->ref_parent, $c->table, $id);
    if ($id_last)
        $p->link ($lang['previous'], $app->view, array ('id' => $id_last));
    echo ' ' . sprintf ($lang['x of y'], $thisindex, $last) . ' ';
    if ($id_next)
        $p->link ($lang['next'], $app->view, array ('id' => $id_next));
    echo '<BR>';

    # Show all objects for this group.
    _object_box ($app, $c->table, $id, $app->args);

    # Input field for group name.
    $parent_id = $db->column ($c->table, $c->ref_parent, $id);
    $p->open_source ($c->table, '_update', $app->arg_set_next (0, $app->view, array ('id' => $id)));

    if ($p->get ("where id=$id")) {
        $p->open_row ();
        $p->cmd_delete ($lang['remove'], $c->parent_view, array ('id' => $parent_id));
        $p->inputline ('name', 255, $c->txt_input);
        $p->submit_button ('Ok', '_update', $app->arg_set_next (0, $app->view, array ('id' => $id)));
        $p->close_row ();
    }
    $p->close_source ();

    $p->open_source ($c->child_table);
    $p->use_filter ('form_safe');

    if ($p->get ("WHERE $c->ref_table=$id", true)) {
        if ($c->headers)
            $p->table_headers ($c->headers);
        $idx = 1;
        do {
	    $recordfunc ($app, $idx);
	    $idx++;
        } while ($p->get_next ());

        $p->paragraph ();
        _range_panel ($app, $id, $c->child_view, $c->txt_create, $c->child_table, $c->have_submit_button);
    } else {
        $p->label ($c->txt_no_records);
        $p->cmd_create ($c->txt_create, $c->child_view, 'id', $id, $lang['msg record created']);
    }

    $p->close_source ();

    return ''; # XXX ???
}
?>

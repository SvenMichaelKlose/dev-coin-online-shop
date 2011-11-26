<?
# Generic directory lister.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function _get_index (&$this, $parent, $ref_parent, $table, $id)
{
    $id_parent = $this->db->column ($table, $ref_parent, $id);
    $res = $this->db->select ('id, id_last, id_next', $table, "$ref_parent=$id_parent");
    while ($res && $row = $res->get ())
        $tmp[$row['id_last']] = $row;
    for ($row = reset ($tmp), $i = 1; $row; $row = next ($tmp), $i++)
        if ($row['id'] == $id)
            $thisindex = $i;
    return array ($thisindex, $i - 1);
}

function _range_panel (&$this, $id, $child_view, $txt_create, $table, $have_submit_button = false)
{
    global $lang;

    $p =& $this->ui;

    # Link to creator of new record.
    $p->use_key ('');
    $p->open_row ();
    $p->submit_button ('select range', 'tk_range_edit_select', $this->arg_set_next (0, $this->view, $this->args));
    $sel = tk_range_edit_all_selected ($this);
    if ($sel == 0 || $sel == 2)
        $p->submit_button ('select all', 'tk_range_edit_select_all', $this->arg_set_next (0, $this->view, $this->args));
    if ($sel == 1 || $sel == 2)
        $p->submit_button ('unselect all', 'tk_range_edit_unselect_all', $this->arg_set_next (0, $this->view, $this->args));
    $p->submit_button ('delete', 'tk_range_edit_call', array ('view' => '_delete', 'argname' => 'id',
                                                              'arg' => $this->arg_set_next (array ('table' => $table, '_ssi_obj' => 'dbi'), $this->view, $this->args)));
    $p->cmd_create ($txt_create, $child_view, 'id', $id, $lang['msg record created']);
    if ($have_submit_button)
        $p->submit_button ('Ok', '_update', $this->arg_set_next ());
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
function generic_list (&$this, $c)
{
    global $lang;

    $this->args['table'] = $c->table; # Needed for _object_box.
    $id = $this->args['id'];
    $p =& $this->ui;

    # Navigator
    $p->headline ($lang["title $this->view"]);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($this, $c->table, $id);

    # Link to next/last product group.
    $id_last = $this->db->column ($c->table, 'id_last', $id);
    $id_next = $this->db->column ($c->table, 'id_next', $id);
    list ($thisindex, $last) = _get_index ($this, $c->parent_table, $c->ref_parent, $c->table, $id);
    if ($id_last)
        $p->link ($lang['previous'], $this->view, array ('id' => $id_last));
    echo ' ' . sprintf ($lang['x of y'], $thisindex, $last) . ' ';
    if ($id_next)
        $p->link ($lang['next'], $this->view, array ('id' => $id_next));
    echo '<BR>';

    # Show all objects for this group.
    _object_box ($this, $c->table, $id, $this->args);

    # Input field for group name.
    $parent_id = $this->db->column ($c->table, $c->ref_parent, $id);
    $p->open_source ($c->table, '_update', $this->arg_set_next (0, $this->view, array ('id' => $id)));

    if ($p->get ("where id=$id")) {
        $p->open_row ();
        $p->cmd_delete ($lang['remove'], $c->parent_view, array ('id' => $parent_id));
        $p->inputline ('name', 255, $c->txt_input);
        $p->submit_button ('Ok', '_update', $this->arg_set_next (0, $this->view, array ('id' => $id)));
        $p->close_row ();
    }
    $p->close_source ();

    $p->open_source ($c->child_table);
    $p->use_filter ('form_safe');

    if ($p->get ("WHERE $c->ref_table=$id", true)) {
        $p->table_headers ($c->headers);
        $idx = 1;
        do {
	    $recordfunc ($this, $idx);
	    $idx++;
        } while ($p->get_next ());

        $p->paragraph ();
        _range_panel ($this, $id, $c->child_view, $c->txt_create, $c->child_table, $c->have_submit_button);
    } else {
        $p->label ($c->txt_no_records);
        $p->cmd_create ($c->txt_create, $c->child_view, 'id', $id, $lang['msg record created']);
    }

    $p->close_source ();

    return ''; # XXX ???
}
?>

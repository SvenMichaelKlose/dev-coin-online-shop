<?
# Product attribute object.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function product_attrib_init (&$this)
{
    $this->add_viewfunc ('product_attrib_edit');
    $ssi =& new ssi_php_array;
    $this->ui->add_ssi ('php_array', $ssi);
}

function product_attrib_mask_view (&$this, &$obj)
{
    global $lang;

    $p =& $this->ui;

    # Fetch attribute object.
    $aobj = new dbobj ($this->db, 'u_attribs', $this->db->def, $obj->_table, $obj->_id);
    foreach (unserialize ($aobj->active['data']) as $record)
        $attr[$record['name']] = true;

    # Mask out attributes.
    $arr = unserialize ($obj->active['data']);
    if (is_array ($arr))
        foreach ($arr as $record)
	    if (isset ($attr[$record['name']]))
                $attr[$record['name']] = $record['is_used'];

    # Write back mask info.
    foreach ($attr as $name => $flag)
        $masks[] = array ('name' => $name, 'is_used' => $flag);
    $obj->active['data'] = serialize ($masks);
    $obj->assoc ();

    # Open source in mask object.
    $p->open_source ('obj_data');
    $p->get ('WHERE id=' . $obj->active['id']);
    if ($obj->active['found_local'])
        $p->cmd_delete ('delete');
    $p->paragraph ();
    $p->use_field ('data');
    $p->open_source ('data');
    $p->stack_ssi ('php_array');
    #$p->use_filter ('form_safe');
    $p->open_row ();

    if ($p->get ()) {
        do {
            $p->checkbox ('is_used');
            $p->show ('name');
        } while ($p->get_next ());
    }
    $p->close_row ();
    $p->close_source ();
    $p->close_source ();
}

function product_attrib_mask_edit (&$this, &$obj)
{
    $this->call_view ('return2caller');
}

function product_attrib_edit (&$this, &$obj)
{
    global $lang;

    $p =& $this->ui;

    $p->open_source ('obj_data');
    $p->get ('WHERE id=' . $obj->active['id']);
    $p->cmd_delete ('delete');
    $p->paragraph ();
    $p->use_field ('data');
    $p->open_source ('data');
    $p->stack_ssi ('php_array');
    $p->use_filter ('form_safe');

    if ($p->get ()) {
        do {
            $p->open_row ();
            $p->cmd_delete ('delete');
            $p->inputline ('name', 60);
            $p->close_row ();
        } while ($p->get_next ());
    }
    $p->paragraph ();
    $p->open_row ();
    $p->link ( 'Add new', '_create', $this->arg_set_next (array ('preset_values' => array ('name' => ''),
                                                                 'msg' => 'Record created.')));
    $p->submit_button ('Ok', '_update', $this->arg_set_next ());
    $p->close_row ();
    $p->close_source ();
    $p->close_source ();
}
?>

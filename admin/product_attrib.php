<?
# Product attribute object.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function product_attrib_init (&$app)
{
    $app->add_function ('product_attrib_edit');
    $ssi = new ssi_php_array;
    $app->ui->add_ssi ('php_array', $ssi);
}

function product_attrib_mask_view (&$app, &$obj)
{
    global $lang;

    $p =& $app->ui;

    # Fetch attribute object.
    $aobj = new dbobj ($app->db, 'u_attribs', $app->db->def, $obj->_table, $obj->_id);
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
    $p->query (sql_assignment ('id', $obj->active['id']));
    $p->get ();
    if ($obj->active['found_local'])
        $p->cmd_delete ();

    $p->paragraph ();

    $p->use_field ('data');

    $p->open_source ('data');
    $p->stack_ssi ('php_array');
    #$p->use_filter ('form_safe');
    $p->open_row ();

    while ($p->get ()) {
        $p->checkbox ('is_used');
        $p->show ('name');
    }
    $p->close_row ();
    $p->close_source ();
    $p->close_source ();
}

function product_attrib_mask_edit (&$app, &$obj)
{
    $app->call ('return2caller');
}

function product_attrib_edit (&$app, &$obj)
{
    global $lang;

    $p =& $app->ui;

    $p->open_source ('obj_data');
    $p->query ('id=' . $obj->active['id']);
    $p->get ();
    $p->cmd_delete ();
    $p->paragraph ();
    $p->use_field ('data');
    $p->open_source ('data');
    $p->stack_ssi ('php_array');
    $p->use_filter ('form_safe');

    if ($p->get ()) {
        do {
            $p->open_row ();
            $p->cmd_delete ();
            $p->inputline ('name', 60);
            $p->close_row ();
        } while ($p->get_next ());
    }

    $p->paragraph ();

    $p->open_row ();

    $e = new event ( 'record_create', array ('preset_values' => array ('name' => ''),
                                             'msg' => 'Record created.'));
    $e->set_next ($app->event ());
    $p->link ( 'Add new', $e);

    $p->cmd:update ();
    $p->close_row ();

    $p->close_source ();
    $p->close_source ();
}
?>

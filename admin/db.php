<?php

# Miscellaneous database stuff.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function db_init (&$app)
{
    $app->add_function ('create_tables');
    $app->add_function ('database_menu');
    $app->add_function ('db_consistency_check');
    $app->add_function ('db_sort_directories');
}

function create_object_classes (&$app)
{
    global $lang;

    $db =& $app->db;

    $classes = array (
        'l_index', 'l_category', 'l_page', 'l_product',
        'l_cart', 'l_empty_cart',
        'l_order', 'l_order_email', 'l_order_confirm',
        'l_search',
        'l_empty_cart', 'l_ecml',
        'd_order_address', 'd_order_duty', 'd_order_extra',
        'd_order_email_subject',
        'u_attribs', 'u_attrib_mask'
    );
    foreach ($classes as $class) {
        $descr = $lang["class $class"];

        if ($res = $db->select ('*', 'obj_classes', "name='$class'")) {
            $db->update ('obj_classes', "descr='$descr'", 'id=' . $res->get ('id'));
	    continue;
        }

        $db->insert ('obj_classes', "name='$class', descr='$descr'");
    }
}

function create_tables (&$app)
{
    global $lang;

    $p =& $app->ui;
    $db =& $app->db;

    echo "<HR>\n";

    $db->create_tables ();
    $p->msgbox ($lang['msg tables created']);

    create_directory_types ($app);
    create_object_classes ($app);

    if ($db->select ('id', 'directories', 'id=1')) {
        $p->msgbox ($lang['msg root category exists']);
    } else {
        $db->insert ('directories', 'id=1, name=\'root\', id_directory_type=' . get_directory_type_id ($db, 'category'));
        $p->msgbox ($lang['msg root category created']);
    }

    $app->call (new event ('database_menu'));
}

# Remove invalid object reference from directory type.
function dbchkdir (&$app, &$objs, $dirname)
{
    $cnt = '0';

    $res = $app->db->select ('id,id_obj', $dirname, 'id_obj!=0');
    while ($res && $row = $res->get ())
        if (isset ($objs[$row['id_obj']]) == false) {
	    $app->db->update ($dirname, 'id_obj=0', 'id="' . $row['id'] . '"');
	    $cnt++;
        }

    $app->ui->msgbox ("$cnt invalid object pointers removed from $dirname.");
}

function db_consistency_check (&$app)
{
    global $lang;

    $db =& $app->db;
    $p =& $app->ui;
    $p->msgbox ('Please wait...', 'yellow');
 
    $p->msgbox ('Removing free objects...', 'yellow');
    flush ();

    # Get all object id in directories.
    $res = $db->select ('id_obj', 'directories');
    while ($res && list ($id_obj) = $res->get ())
        $refs[$id_obj] = true;

    # Remove objects and data that is not referenced
    $res = $db->select ('id', 'objects');
    while ($res && list ($id) = $res->get ())
        if (!isset ($refs[$id])) {
            $db->delete ('obj_data', "id_obj=$id");
            $db->delete ('objects', "id=$id");
        }

    $p->msgbox ('Removing empty objects...', 'yellow');
    flush ();
    $res = $db->select ('id, id_obj', 'obj_data', 'data=\'\'');
    while ($res && $row = $res->get ())
	$db->delete ('obj_data', 'id=' . $row['id']);
    $num_rows = ($res ? $res->num_rows () : 0);
    $p->msgbox ("$num_rows empty objects removed.");

    $p->msgbox ('Removing dangling references...', 'yellow');
    flush ();
    $res = $db->select ('id_obj', 'obj_data');
    while ($res && list ($id_obj) = $res->get ())
        $bref[$id_obj] = true;
    $n = 0;
    $res = $db->select ('id', 'objects');
    while ($res && list ($id) = $res->get ())
        if (!$refs[$id]) {
            $db->delete ('objects', "id=$id");
	    $n++;
	    $changes++;
        }
    $p->msgbox ("$n references removed.");

    # Remove dangling object ids in directories.
    $p->msgbox ('Removing dangling object IDs in directories...', 'yellow');
    flush ();
    $res = $db->select ('id', 'objects');
    while ($res && $row = $res->get ())
        $obj[$row['id']] = true;
    dbchkdir ($app, $obj, 'directories');

    $p->msgbox ('Removing old tokens...', 'yellow');
    flush ();
    $db->delete ('tokens');

    $p->msgbox ('Optimizing tables...', 'yellow');
    flush ();
    foreach ($db->def->table_names () as $table)
        $db->query ("OPTIMIZE TABLE $table");

    $p->msgbox ('O.K. Done.');
    $app->call (new event ('database_menu'));
}

function db_sort_directories (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->msgbox ('Sorting directories - please wait...', 'yellow');
    flush ();
    sort_linked_list ($p->db, 'directories', '1', 'ORDER BY name ASC' , -1);
    $p->msgbox ('Directories sorted.');
    $app->call (new event ('database_menu'));
}

# Menu of database operations.
function database_menu (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->headline ($lang['title database_menu']);

    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    $p->link ($lang['cmd create_tables'], 'create_tables', 0);
    $p->link ($lang['cmd db_consistency_check'], 'db_consistency_check', 0);
    $p->link ($lang['cmd db_sort'], 'db_sort_directories', 0);
}

?>

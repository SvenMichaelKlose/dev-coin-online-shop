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

# Create a directory type $name and return its id.
function create_directory_type (&$db, $name)
{
    # Create a directory type of $name.
    $res =& $db->insert ('directory_types', "name='$name'");
    return $db->insert_id ();
}

function create_object_classes (&$app)
{
    global $lang, $debug;

    $p =& $app->ui;
    $db =& $app->db;

    $object_classes = array (
        'l_index', 'l_category', 'l_page', 'l_product',
        'l_cart', 'l_empty_cart',
        'l_order', 'l_order_email', 'l_order_confirm',
        'l_search',
        'l_empty_cart', 'l_ecml',
        'd_order_address', 'd_order_duty', 'd_order_extra',
        'd_order_email_subject',
        'u_attribs', 'u_attrib_mask'
    );
    foreach ($object_classes as $v)
        $class[] = array ($v, $lang["class $v"]);

    for ($i = 0; $i < sizeof ($class); $i++) {
        echo 'Klasse ' . $class[$i][0] . ' (' . $class[$i][1] . ') ';

        # Rename already existing classes.
        if ($tmp = $db->select ('*', 'obj_classes', 'name=\'' . $class[$i][0] . '\'')) {
            $res = $tmp->get ();
            $db->update ('obj_classes', 'descr="' . $class[$i][1] . '"', 'id=' . $res['id']);
            echo "<FONT COLOR=GREEN>updated.</FONT><BR>\n";
	    continue;
        }

        # Create new class.
        $db->insert ('obj_classes', 'name=\'' . $class[$i][0] . '\', descr=\'' . $class[$i][1] . '\'');
        echo "<FONT COLOR=GREEN>erstellt.</FONT><BR>\n";
    }
}

function create_tables (&$app)
{
    global $lang, $debug;

    $p =& $app->ui;
    $db =& $app->db;

    echo "<HR>\n";

    $db->create_tables ();
    echo '<FONT COLOR=GREEN>' . $lang['msg tables created'] . '</FONT><BR>';

    create_object_classes ($app);

    if ($db->select ('id', 'directories', 'id=1')) {
        echo '<FONT COLOR=RED>' . $lang['msg root category exists'] . '</FONT><BR>';
    } else {
        $db->insert ('directories', 'id=1, name=\'root\'');
        echo '<FONT COLOR=GREEN>' . $lang['msg root category created'] . '</FONT><BR>';
    }
    #merge_directories ($app);
    $p->link ($lang['cmd back'], 'defaultview');
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
    echo "$cnt invalid object pointers removed from $dirname.<br>";
}

function db_consistency_check (&$app)
{
    global $lang;

    $db =& $app->db;
    $p =& $app->ui;
    $p->msgbox ('Please wait...', 'yellow');
    $changes = 0;
 
    echo 'Removing free objects...<br>';
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

    echo 'Removing empty objects...<br>';
    flush ();
    $res = $db->select ('id, id_obj', 'obj_data', 'data=\'\'');
    while ($res && $row = $res->get ())
	$db->delete ('obj_data', 'id=' . $row['id']);
    $num_rows = ($res ? $res->num_rows () : 0);
    echo "$num_rows empty objects removed.<br>";
    $changes += $num_rows;

    echo 'Removing hanging xrefs...<br>';
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
    echo "$n xrefs removed.<br>";

    # Remove dangling object ids in directories.
    echo 'Removing dangling object ids in directories...<br>';
    flush ();
    $res = $db->select ('id', 'objects');
    while ($res && $row = $res->get ())
        $obj[$row['id']] = true;
    $changes += dbchkdir ($app, $obj, 'directories');

    $p->msgbox ("$changes changes.");

    echo 'Removing old tokens...<br>';
    flush ();
    $db->delete ('tokens');

    echo 'Optimizing tables...<br>';
    flush ();
    foreach ($db->def->table_names () as $table)
        $db->query ("OPTIMIZE TABLE $table");

    $p->link ('back', 'defaultview');
}

function db_sort_directories (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->msgbox ('Sorting directories - please wait...', 'yellow');
    flush ();
    sort_linked_list ($p->db, 'directories', '1', 'ORDER BY name ASC' , -1);
    $p->msgbox ('Directories sorted.');
    $p->link ('back', 'defaultview');
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

<?
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
function create_dirtype (&$db, $name)
{
    # Create a directory type of $name.
    $res =& $db->insert ('dirtypes', "name='$name'");
    return $db->insert_id ();
}

function copy_o2ndir (&$app, $name, $id_type, &$dir_array)
{
    $db =& $app->db;
    $c = 0;
    $dep = $db->def;
    $res = $db->select ('*', $name);
    while ($res && $row = $res->get ()) {
        if ($c++ % 100 == 0) 
 	    echo "$c directories copied...<br>", flush ();
        $tmp = $db->insert ( 'directories', 'id_obj=' . $row['id_obj'] . ', name="' . addslashes ($row['name']) . '"');
        $row['id_type'] = $id_type;
        $row['id_new'] = $id_new = $db->insert_id ();
        $dir_array ['new'][$id_new] = $row;
        $dir_array ['old'][$id_type][$row['id']] = $id_new;

        if ($name == 'products') {
            $obj = new DBOBJ ($db, 'u_price', $dep, 0, 0);
            @$obj->active['data'] = array ('val' => $row['price_dm'], 'name' => 'dm');
            $obj->active['mime'] = 'text/plain';
	    $obj->assoc ($name, $row['id']);
        }
    }
}

# Merge all directories into a single table 'directories'.
# Table 'xref' contains Links between directories and allows
# n:n relationships.
function merge_directories ($app)
{
    $db =& $app->db;

    # Create directory types.
    $tcategories = create_dirtype ($db, 'categories');
    $tpages = create_dirtype ($db, 'pages');
    $tproducts = create_dirtype ($db, 'products');

    # Copy directories into the directory table and remind the new
    # primary keys and directory type ids.
    $app->ui->msgbox ('Copying directories...');
    flush ();

    $dirs ='';
    copy_o2ndir ($app, 'categories', $tcategories, $dirs);
    copy_o2ndir ($app, 'pages', $tpages, $dirs);
    copy_o2ndir ($app, 'products', $tproducts, $dirs);

    $rc[$tcategories] = 'id_parent';
    $rc[$tpages] = 'id_category';
    $rc[$tproducts] = 'id_page';
    $dt[$tcategories] = $tcategories;
    $dt[$tpages] = $tcategories;
    $dt[$tproducts] = $tpages;

    # Create xrefs from id arrays.
    $app->ui->msgbox ('Creating directory links...');
    flush ();

    foreach ($dirs['new'] as $id_child => $row) {
        $id_type = $row['id_type'];
        if (isset ($dirs['old'][$dt[$id_type]][$row[$rc[$id_type]]])) {
            $id_parent = $dirs['old'][$dt[$id_type]][$row[$rc[$id_type]]];
            $type_parent = $dirs['new'][$id_parent]['id_type'];
        } else
	    $id_parent = $type_parent = 0;
        $type_child = $row['id_type'];
        #echo "$id_type, $id_parent, $id_child, $type_parent, $type_child, " . $row[$rc[$id_type]] . "<br>";
        $db->insert ('xrefs', "id_parent=$id_parent, id_child=$id_child, type_parent=$type_parent, type_child=$type_child");
    }
}

function create_tables (&$app)
{
    global $lang, $debug;

    $p =& $app->ui;
    $db =& $app->db;
    $TABLE_PREFIX = isset ($app->args['__TABLE_PREFIX']) ? $app->args['__TABLE_PREFIX'] : '';

    echo "<HR>\n";

    $tmp = $debug;
    $debug = true;
    $db->create_tables ($TABLE_PREFIX);
    $debug = $tmp;

    echo '<FONT COLOR=GREEN>' . $lang['msg tables created'] . '</FONT><BR>';

    if ($db->select ('id', 'categories', 'id=1')) {
        echo '<FONT COLOR=RED>' . $lang['msg root category exists'] . '</FONT><BR>';
    } else {
        $db->insert ('categories', 'id=1, name=\'root\'');
        echo '<FONT COLOR=GREEN>' . $lang['msg root category created'] . '</FONT><BR>';
    }

    # Create default classes.
    $tmp = array (
        'l_index', 'l_category', 'l_page', 'l_product',
        'l_cart', 'l_empty_cart',
        'l_order', 'l_order_email', 'l_order_confirm',
        'l_search',
        'l_empty_cart', 'l_ecml',
        'd_order_address', 'd_order_duty', 'd_order_extra',
        'd_order_email_subject',
        'u_attribs', 'u_attrib_mask'
    );

    foreach ($tmp as $v)
        $class[] = Array ($v, $lang["class $v"]);

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

    #merge_directories ($app);
    return 'defaultview';
}

# Menu of database operations.
function database_menu (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->headline ($lang['title database_menu']);

    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    echo '<UL><LI>';
    $p->link ($lang['cmd create_tables'], 'create_tables', 0);
    echo '</LI><LI>';
    $p->link ($lang['cmd db_consistency_check'], 'db_consistency_check', 0);
    echo '</LI><LI>';
    $p->link ($lang['cmd db_sort'], 'db_sort_directories', 0);
    echo '</LI></UL>';
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

# TODO: Make database description fit for a general consistency check.
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
    $res = $db->select ('id_obj', 'categories');
    while ($res && list ($id_obj) = $res->get ())
        $refs[$id_obj] = true;
    $res = $db->select ('id_obj', 'pages');
    while ($es && list ($id_obj) = $res->get ())
        $refs[$id_obj] = true;
    $res = $db->select ('id_obj', 'products');
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
    $changes += dbchkdir ($app, $obj, 'categories');
    $changes += dbchkdir ($app, $obj, 'pages');
    $changes += dbchkdir ($app, $obj, 'products');

    echo 'Removing old tokens...<br>';
    flush ();
    $db->delete ('tokens');

    echo 'Optimizing tables...<br>';
    flush ();
    $db->query ('OPTIMIZE TABLE categories');
    $db->query ('OPTIMIZE TABLE pages');
    $db->query ('OPTIMIZE TABLE products');
    $db->query ('OPTIMIZE TABLE objects');
    $db->query ('OPTIMIZE TABLE obj_data');
    $db->query ('OPTIMIZE TABLE cart');
    $db->query ('OPTIMIZE TABLE sessions');
    $db->query ('OPTIMIZE TABLE tokens');

    $p->msgbox ("$changes changes.");
    $p->link ('back', 'defaultview');
}

# TODO: Make database description fit for a general consistency check.
function db_sort_directories (&$app)
{
    global $lang;

    $p =& $app->ui;
    $p->msgbox ('Sorting directories - please wait...', 'yellow');
    flush ();
    sort_linked_list ($p->db, 'categories', '1', 'ORDER BY name ASC' , -1);
    $p->msgbox ('Directories sorted.');
    $p->link ('back', 'defaultview');
}
?>

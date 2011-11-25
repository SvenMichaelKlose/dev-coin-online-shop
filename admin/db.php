<?
  # $Id: db.php,v 1.17 2001/12/01 16:31:46 sven Exp $
  #
  # Miscellaneous database stuff.
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #	    	            Sven Klose (sven@devcon.net)
  #
  # This program is free software; you can redistribute it and/or modify
  # it under the terms of the GNU General Public License as published by
  # the Free Software Foundation; either version 2 of the License, or
  # (at your option) any later version.
  #
  # This program is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  # GNU General Public License for more details.
  #
  # You should have received a copy of the GNU General Public License
  # along with this program; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  function db_init (&$this)
  {
    $this->add_viewfunc ('create_tables');
    $this->add_viewfunc ('database_menu');
    $this->add_viewfunc ('db_consistency_check');
    $this->add_viewfunc ('db_sort_directories');
  }

  # Create a directory type $name and return its id.
  function create_dirtype (&$db, $name)
  {
    # Create a directory type of $name.
    $res =& $db->insert ('dirtypes', 'name="' . $name . '"');
    return $db->insert_id ();
  }

  function copy_o2ndir (&$this, $name, $id_type, &$dir_array)
  {
global $debug;
#$debug = true;
    $db =& $this->db;
    $res = $db->select ('*', $name);
    $c = 0;
    $dep = $this->db->def;
    while ($row = $res->fetch_array ()) {
      if ($c++ % 100 == 0) 
 	echo "$c directories copied...<br>", flush ();
      $tmp = $db->insert (
	'directories',
	'id_obj=' . $row['id_obj'] . ', name="' . addslashes ($row['name']) . '"'
      );
      $row['id_type'] = $id_type;
      $row['id_new'] = $id_new = $db->insert_id ();
      $dir_array ['new'][$id_new] = $row;
      $dir_array ['old'][$id_type][$row['id']] = $id_new;
#echo $row['id'] . " => " . $id_new . "<br>";

      if ($name == 'products') {
        $obj = new DBOBJ (&$db, 'u_price', $dep, 0, 0);
        @$obj->active['data'] = array ('val' => $row['price_dm'], 'name' => 'dm');
        $obj->active['mime'] = 'text/plain';
	$obj->assoc ($name, $row['id']);
      }
    }
  }

  # Merge all directories into a single table 'directories'.
  # Table 'xref' contains Links between directories and allows
  # n:n relationships.
  function merge_directories ($this)
  {
    $db =& $this->db;

    # Create directory types.
    $tcategories = create_dirtype ($db, 'categories');
    $tpages = create_dirtype ($db, 'pages');
    $tproducts = create_dirtype ($db, 'products');

    # Copy directories into the directory table and remind the new
    # primary keys and directory type ids.
    $this->ui->msgbox ('Copying directories...');
    flush ();

    $dirs ='';
    copy_o2ndir ($this, 'categories', $tcategories, $dirs);
    copy_o2ndir ($this, 'pages', $tpages, $dirs);
    copy_o2ndir ($this, 'products', $tproducts, $dirs);

    $rc[$tcategories] = 'id_parent';
    $rc[$tpages] = 'id_category';
    $rc[$tproducts] = 'id_page';
    $dt[$tcategories] = $tcategories;
    $dt[$tpages] = $tcategories;
    $dt[$tproducts] = $tpages;

    # Create xrefs from id arrays.
    $this->ui->msgbox ('Creating directory links...');
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
      $db->insert (
	'xrefs',
	'id_parent=' . $id_parent . ',' .
	'id_child=' . $id_child . ',' .
	'type_parent=' . $type_parent . ',' .
	'type_child=' . $type_child
      );
    }
    # Done.
  }

  function create_tables (&$this)
  {
    global $lang, $debug;

    $p =& $this->ui;
    if (isset ($this->args['__TABLE_PREFIX']))
      $TABLE_PREFIX = $this->args['__TABLE_PREFIX'];
    else
      $TABLE_PREFIX = '';

    echo "<HR>\n";

    $tmp = $debug;
    $debug = true;
    $this->db->create_tables ($TABLE_PREFIX);
    $debug = $tmp;

    echo '<FONT COLOR=GREEN>' . $lang['msg tables created'] . '</FONT><BR>';

    $res = $this->db->select ('id', 'categories', 'id=1');
    if ($res->num_rows () < 1) {
      $this->db->insert ('categories', 'id=1, name=\'root\'');
      echo '<FONT COLOR=GREEN>' . $lang['msg root category created'] .
      	   '</FONT><BR>';
    } else
      echo '<FONT COLOR=RED>' . $lang['msg root category exists'] .
      	   '</FONT><BR>';

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
      $class[] = Array ($v, $lang['class ' . $v]);

    for ($i = 0; $i < sizeof ($class); $i++) {
      echo 'Klasse ' . $class[$i][0] . ' (' . $class[$i][1] . ') ';

      # Rename already existing classes.
      $tmp = $this->db->select (
        '*', 'obj_classes', 'name=\'' . $class[$i][0] . '\''
      );
      if ($tmp->num_rows () > 0) {
        $res = $tmp->fetch_array ();
        $this->db->update (
	  'obj_classes', 'descr="' . $class[$i][1] . '"', 'id=' . $res['id']
	);
        echo "<FONT COLOR=GREEN>updated.</FONT><BR>\n";
	continue;
      }

      # Create new class.
      $this->db->insert (
	'obj_classes',
	'name=\'' . $class[$i][0] . '\', descr=\'' . $class[$i][1] . '\''
      );
      echo "<FONT COLOR=GREEN>erstellt.</FONT><BR>\n";
    }

    #merge_directories ($this);
    return 'defaultview';
  }

  # Menu of database operations.
  function database_menu (&$this)
  {
    global $lang;
    $p =& $this->ui;
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
  function dbchkdir (&$this, &$objs, $dirname)
  {
    $cnt = '0';
    $res = $this->db->select ('id,id_obj', $dirname, 'id_obj!=0');
    while ($row = $res->fetch_array ())
      if (isset ($objs[$row['id_obj']]) == false) {
	$this->db->update ($dirname, 'id_obj=0', 'id="' . $row['id'] . '"');
	$cnt++;
      }
    echo $cnt . ' invalid object pointers removed from ' . $dirname . '.<br>';
  }

  # TODO: Make database description fit for a general consistency check.
  function db_consistency_check (&$this)
  {
    global $lang;
    $p =& $this->ui;
    $p->msgbox ('Please wait...', 'yellow');
    $changes = 0;
 
    echo 'Removing free objects...<br>';
    flush ();
    # Get all object id in directories.
    $res = $p->db->select ('id_obj', 'categories');
    while (list ($id_obj) = $res->fetch_array ())
      $refs[$id_obj] = true;
    $res = $p->db->select ('id_obj', 'pages');
    while (list ($id_obj) = $res->fetch_array ())
      $refs[$id_obj] = true;
    $res = $p->db->select ('id_obj', 'products');
    while (list ($id_obj) = $res->fetch_array ())
      $refs[$id_obj] = true;

    # Remove objects and data that is not referenced
    $res = $p->db->select ('id', 'objects');
    while (list ($id) = $res->fetch_array ())
      if (!isset ($refs[$id])) {
        $p->db->delete ('obj_data', 'id_obj=' . $id);
        $p->db->delete ('objects', 'id=' . $id);
      }

    echo 'Removing empty objects...<br>';
    flush ();
    $res = $p->db->select ('id, id_obj', 'obj_data', 'data=\'\'');
    while ($row = $res->fetch_array ())
	$p->db->delete ('obj_data', 'id=' . $row['id']);
    echo $res->num_rows () . ' empty objects removed.<br>';
    $changes += $res->num_rows ();

    echo 'Removing hanging xrefs...<br>';
    flush ();
    $res = $p->db->select ('id_obj', 'obj_data');
    while (list ($id_obj) = $res->fetch_array ())
      $bref[$id_obj] = true;
    $res = $p->db->select ('id', 'objects');
    $n = 0;
    while (list ($id) = $res->fetch_array ())
      if (!$refs[$id]) {
        $p->db->delete ('objects', 'id=' . $id);
	$n++;
	$changes++;
      }
    echo $n . ' xrefs removed.<br>';

    # Remove dangling object ids in directories.
    echo 'Removing dangling object ids in directories...<br>';
    flush ();
    $res = $p->db->select ('id', 'objects');
    while ($row = $res->fetch_array ())
      $obj[$row['id']] = true;
    $changes += dbchkdir ($this, $obj, 'categories');
    $changes += dbchkdir ($this, $obj, 'pages');
    $changes += dbchkdir ($this, $obj, 'products');

    echo 'Removing old tokens...<br>';
    flush ();
    $p->db->delete ('tokens');

    echo 'Optimizing tables...<br>';
    flush ();
    $p->db->query ('OPTIMIZE TABLE categories');
    $p->db->query ('OPTIMIZE TABLE pages');
    $p->db->query ('OPTIMIZE TABLE products');
    $p->db->query ('OPTIMIZE TABLE objects');
    $p->db->query ('OPTIMIZE TABLE obj_data');
    $p->db->query ('OPTIMIZE TABLE cart');
    $p->db->query ('OPTIMIZE TABLE sessions');
    $p->db->query ('OPTIMIZE TABLE tokens');
    if ($changes == '0')
      $changes = 'No';
    $p->msgbox ($changes . ' changes made.');
    $p->link ('back', 'defaultview');
  }

  # TODO: Make database description fit for a general consistency check.
  function db_sort_directories (&$this)
  {
    global $lang;
    $p =& $this->ui;
    $p->msgbox ('Sorting directories - please wait...', 'yellow');
    flush ();
    sort_linked_list ($p->db, 'categories', '1', 'ORDER BY name ASC' , -1);
    $p->msgbox ('Directories sorted.');
    $p->link ('back', 'defaultview');
  }

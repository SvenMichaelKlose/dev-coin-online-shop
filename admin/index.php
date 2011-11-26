<?
# dev/con modular shop administration interface
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# This file contains the main entry point at its end.


# The code must be clean.
error_reporting (-1);
  
$debug = 0;
$PATH_TO_CAROSHI = '.';

if (!file_exists ('./.dbi.conf.php'))
    die ('Can\'t find database confiuration file .dbi.conf.php - stop.');
require '.dbi.conf.php';

if (!isset ($PATH_TO_PUBLIC))
    die ('$PATH_TO_PUBLIC is not set.');

# Include libraries.
require "$PATH_TO_CAROSHI/lib/application.class";
require "$PATH_TO_CAROSHI/admin_panel/admin_panel.class";
require "$PATH_TO_CAROSHI/admin_panel/ssi/php_array.class";
require "$PATH_TO_CAROSHI/admin_panel/tk/range_edit.php";
require "$PATH_TO_CAROSHI/admin_panel/tk/tree_edit.php";
require "$PATH_TO_CAROSHI/admin_panel/tk/treeview.class";
require "$PATH_TO_CAROSHI/dbi/dbsession.class";
require "$PATH_TO_CAROSHI/dbi/dbobj.class";
require "$PATH_TO_CAROSHI/dbi/dbsort.php";
require "$PATH_TO_CAROSHI/dbi/dbtree.php";

# Load language descriptions.
require "lang_$language.inc";
require "$PATH_TO_PUBLIC/lang_$language.inc";

# Include other views.
require 'categories.php';
require 'classes.php';
require 'config.php';
require 'db.php';
require 'generic_list.php';
require 'navigator.php';
require 'obj_order_fields.php';
require 'objects.php';
require 'orders.php';
require 'pages.php';
require 'products.php';
require 'product_attrib.php';
require 'tables.php';
  
class shop_admin extends application {
    var $ui;	# user interface
  
    ##################
    ### Index page ###
    ##################

    # Standard view, displayed on first time entering the admin area.
    function defaultview ()
    {
        global $lang;
        $p =& $this->ui;
 
        $treeargs = array (
            'treeview' => $this->view, 'nodeview' => 'view_pages',
	    'nodecreator' => 'create_category', 'rootname' => 'shop',
	    'table' => 'categories', 'name' => 'name', 'id' => 'id',
            'txt_select_node' => $lang['msg choose category to move'],
            'txt_select_dest' => $lang['msg choose dest category'],
            'txt_moved' => $lang['msg category moved'],
            'txt_not_moved' => $lang['err category not moved'],
            'txt_move_again' => $lang['cmd move further'],
            'txt_back' => $lang['cmd back/quit'],
	    'txt_unnamed' => $lang['unnamed']
        );

        $p->headline ($lang['title defaultview']);
  
        # Main menu
        $p->link ($lang['cmd move_category'], 'tree_edit_move', $treeargs);
        $p->link ($lang['cmd view_classes'], 'view_classes', 0);
        $p->link ($lang['cmd database_menu'], 'database_menu', 0);
        # TODO: Statistics, last orders.
        #$p->link ('Bestellungen', 'view_orders', 0);

        # Display category tree.
        tree_edit ($this, $treeargs);
    }

    ###################
    ## Configuration ##
    ###################

    function init ()
    {
        global $lang, $SERVER_NAME;

        $def =& $this->db->def;

        tables_define ($this);

        # Init user interface.
        $this->ui =& new admin_panel ($this, $lang['administration'] . " $SERVER_NAME dev/coin 0.9.11 ");

        # Initialise toolkits
        tk_range_edit_init ($this);
        tree_edit_register ($this);

        # Initialise other modules.
        category_init ($this);
        page_init ($this);
        product_init ($this);
        product_attrib_init ($this);
        object_init ($this);
        class_init ($this);
        db_init ($this);
        order_init ($this);
    }

    function close ()
    {
        $this->ui->close ();
    }
}

$app =& new shop_admin;
$app->debug = $debug;
$app->run ();
?>

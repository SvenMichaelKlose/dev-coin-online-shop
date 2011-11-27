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
$language = 'de';

if (!file_exists ('config.php'))
    die ('Can\'t find database confiuration file config.php - stop.');
require_once 'config.php';

# Include libraries.
require_once PATH_TO_CAROSHI . '/proc/application.class.php';
require_once PATH_TO_CAROSHI . '/admin_panel/admin_panel.class.php';
#require PATH_TO_CAROSHI . '/admin_panel/ssi/php_array.class.php';
require_once PATH_TO_CAROSHI . '/admin_panel/tk/range_edit/range_edit.php';
require_once PATH_TO_CAROSHI . '/admin_panel/tk/tree_edit.php';
require_once PATH_TO_CAROSHI . '/admin_panel/tk/treeview.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbsession.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbobj.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbsort.php';
require_once PATH_TO_CAROSHI . '/dbi/dbtree.php';

# Load language descriptions.
require "../lang_$language.php";

# Include other views.
require 'admin_panel.php';
require 'categories.php';
require 'classes.php';
require 'cms-config.php';
require 'db.php';
require 'generic_list.php';
require 'navigator.php';
require 'obj_order_fields.php';
require 'objects.php';
require 'orders.php';
require 'pages.php';
require 'products.php';
#require 'product_attrib.php';
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
            'source' => 'categories',
            'id' => '1',
            'treeview' => $this->event (), 'nodeview' => 'view_pages',
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
        $p->link ($lang['cmd move_category'], 'tree_edit_move', 0);
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
        $this->ui = new devcoin_admin_panel ($this, new widget_set);

        # Initialise toolkits
        tk_range_edit_init ($this);
        tree_edit_register ($this);

        # Initialise other modules.
        category_init ($this);
        page_init ($this);
        product_init ($this);
#        product_attrib_init ($this);
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

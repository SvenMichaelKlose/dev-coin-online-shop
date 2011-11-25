<?
  # $Id: index.php,v 1.39 2001/12/03 10:34:55 sven Exp $
  #
  # dev/con modular shop administration interface
  #
  # (c)2000-2001 dev/consulting GmbH
  #	    	 Sven Klose (sven@devcon.net)
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

  # About this file:
  #
  # This file contains the main entry point at its end.

  # The code must be clean.
  error_reporting (-1);
  
  $debug = 0;

  # Include libraries.
  require 'lib/application.class';
  require 'admin_panel/admin_panel.class';
  require 'admin_panel/ssi/php_array.class';
  require 'admin_panel/tk/range_edit.php';
  require 'admin_panel/tk/tree_edit.php';
  require 'admin_panel/tk/treeview.class';
  require 'dbi/dbsession.class';
  require 'dbi/dbobj.class';
  require 'dbi/dbsort.php';
  require 'dbi/dbtree.php';
  require '.dbi.conf.php';

  # Load language description.
  if (file_exists ('lang_' . $language . '.inc'))
    @require 'lang_' . $language . '.inc';
  else
    @require 'mod_shop/lang_' . $language . '.inc';

  # Include other views.
  require 'mod_shop/admin/categories.php';
  require 'mod_shop/admin/classes.php';
  require 'mod_shop/admin/config.php';
  require 'mod_shop/admin/db.php';
  require 'mod_shop/admin/generic_list.php';
  require 'mod_shop/admin/navigator.php';
  require 'mod_shop/admin/obj_order_fields.php';
  require 'mod_shop/admin/objects.php';
  require 'mod_shop/admin/orders.php';
  require 'mod_shop/admin/pages.php';
  require 'mod_shop/admin/products.php';
  require 'mod_shop/admin/product_attrib.php';
  require 'mod_shop/admin/tables.php';
  
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

      echo '<FONT SIZE="-1">' .
      	 '$Id: index.php,v 1.39 2001/12/03 10:34:55 sven Exp $' .
  	 '</FONT>';
    }

    ###################
    ## Configuration ##
    ###################
    function init ()
    {
      global $lang;

      $def =& $this->db->def;

      tables_define ($this);

      # Init user interface.
      $this->ui =& new admin_panel (
        $this,
        $lang['administration'] . '&nbsp;' . $GLOBALS['SERVER_NAME'],
        '&nbsp;dev/coin&nbsp;0.9.11&nbsp;'
      );

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

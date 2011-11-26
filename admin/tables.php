<?
# Database table definitions
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function tables_define (&$this)
{
    $def =& $this->db->def;

    dbobj::define_tables ($def);

    # Database table definitions.
/*$this->db->query ('drop table directories');
$this->db->query ('drop table dirtypes');
$this->db->query ('drop table xrefs');
      $def->define_table (
  	  'directories',
  	  'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,' .
  	  'id_obj INT NOT NULL,' .
  	  'id_parent INT NOT NULL,' .
  	  'id_last INT NOT NULL,' .
  	  'id_next INT NOT NULL,' .
  	  'name VARCHAR (255) NOT NULL,' .
  	  'key (id_obj),' .
  	  'key (id_last),' .
  	  'key (id_next),' .
  	  'key (id_parent),' .
  	  'key (name)'
      );
      $def->set_primary ('directories', 'id');
      $def->set_listref ('directories', 'id_last', 'id_next');

      $def->define_table (
	'xrefs',
  	'id_parent INT NOT NULL,' .
  	'id_child INT NOT NULL,' .
  	'type_parent INT NOT NULL,' .
  	'type_child INT NOT NULL,' .
  	'key (id_parent),' .
  	'key (id_child),' .
  	'key (type_parent),' .
  	'key (type_child)'
      );

      $def->define_table (
  	  'dirtypes',
  	  'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,' .
  	  'name VARCHAR (255) NOT NULL,' .
  	  'key (name)'
      );*/

####################################
### This is going to be removed! ###
####################################

    # Category table.
    $def->define_table (
  	'categories',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_obj',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Object reference'),
          array ('n' => 'id_parent',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to parent'),
          array ('n' => 'id_last',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to previous sibling'),
          array ('n' => 'id_next',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to next sibling'),
          array ('n' => 'name',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Category name')
	)
    );
    $def->set_primary ('categories', 'id');
    $def->set_ref ('categories', 'categories', 'id_parent');
    $def->set_ref ('categories', 'pages', 'id_category');
    $def->set_listref ('categories', 'id_last', 'id_next');

    # Product group table.
    $def->define_table (
  	'pages',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_obj',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Object reference'),
          array ('n' => 'id_category',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to category'),
          array ('n' => 'id_last',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to previous sibling'),
          array ('n' => 'id_next',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to next sibling'),
          array ('n' => 'name',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Product group name')
	)
    );
    $def->set_primary ('pages', 'id');
    $def->set_ref ('pages', 'products', 'id_page');
    $def->set_listref ('pages', 'id_last', 'id_next');
 
    # Product table.
    $def->define_table (
  	'products',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_obj',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Object reference'),
          array ('n' => 'id_page',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to product group'),
          array ('n' => 'id_last',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to previous sibling'),
          array ('n' => 'id_next',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to next sibling'),
          array ('n' => 'name',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Product name'),
          array ('n' => 'bestnr',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Product code'),
          array ('n' => 'price_dm',
                 't' => 'DECIMAL(14,2) NOT NULL',
		 'i' => true,
                 'd' => 'Price (DM)'),
          array ('n' => 'price_eur',
                 't' => 'DECIMAL(14,2) NOT NULL',
		 'i' => true,
                 'd' => 'Price (Euro)')
	)
    );
    $def->set_primary ('products', 'id');
    $def->set_ref ('products', 'cart', 'id_product');
    $def->set_listref ('products', 'id_last', 'id_next');
  
############################################################################

    # Cart item table.
    # TODO: Copy products into own cart set.
    $def->define_table (
  	'cart',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_session',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to session'),
          array ('n' => 'id_product',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to product'),
          array ('n' => 'quantity',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Quantity of product'),
          array ('n' => 'attrib',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Product attributes')
	)
    );
    $def->set_primary ('cart', 'id');

    # ECML adresses
    $def->define_table (
  	'address',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'name_prefix',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'name_first',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'name_middle',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'name_last',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'name_suffix',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'company',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'street1',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'street2',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'street3',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'city',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'state',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'postal_code',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'country_code',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'phone',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => ''),
          array ('n' => 'email',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => '')
	)
    );
    $def->set_primary ('address', 'id');
  
    # Order table.
    $def->define_table (
  	'ecml_order',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_session',
                 't' => 'INT NOT NULL',
                 'd' => 'Reference to session'),
          array ('n' => 'id_address_shipto',
                 't' => 'INT NOT NULL',
                 'd' => 'Reference to ShipTo address'),
          array ('n' => 'id_address_billto',
                 't' => 'INT NOT NULL',
                 'd' => 'Reference to BillTo address'),
          array ('n' => 'id_address_receiptto',
                 't' => 'INT NOT NULL',
                 'd' => 'Reference to ReceiptTo address'),
          array ('n' => 'extrafields',
                 't' => 'MEDIUMTEXT NOT NULL',
                 'd' => 'Serialized php array of user defined fields'),
          array ('n' => 'wallet_id',
                 't' => 'VARCHAR(255) NOT NULL',
                 'd' => 'ECML wallet id (unused)')
	)
    );
    $def->set_primary ('ecml_order', 'id');
}
?>

<?php

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function tables_define (&$app)
{
    $def =& $app->db->def;

    dbobj::define_tables ($def);

    $def->define_table (
  	'directories',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key',
                 'tk_autoform' => 'hide'),
          array ('n' => 'id_directory_type',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Directory type',
                 'tk_autoform' => 'hide'),
          array ('n' => 'id_obj',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Object reference',
                 'tk_autoform' => 'hide'),
          array ('n' => 'id_parent',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to parent',
                 'tk_autoform' => 'hide'),
          array ('n' => 'id_last',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to previous sibling',
                 'tk_autoform' => 'hide'),
          array ('n' => 'id_next',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Reference to next sibling',
                 'tk_autoform' => 'hide'),
          array ('n' => 'name',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Category name')));
    $def->set_primary ('directories', 'id');
    $def->set_ref ('directories', 'directories', 'id_parent');
    $def->set_listref ('directories', 'id_last', 'id_next');

    $def->define_table (
  	'directory_types',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'name',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Name')
	)
    );
 
    $def->define_table (
  	'product_variants',
        array (
          array ('n' => 'id',
                 't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                 'd' => 'primary key'),
          array ('n' => 'id_directory',
                 't' => 'INT NOT NULL',
		 'i' => true,
                 'd' => 'Directory reference'),
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
                 'd' => 'Description'),
          array ('n' => 'code',
                 't' => 'VARCHAR(255) NOT NULL',
		 'i' => true,
                 'd' => 'Product code'),
          array ('n' => 'price',
                 't' => 'DECIMAL(14,2) NOT NULL',
		 'i' => true,
                 'd' => 'Price')
	)
    );
    $def->set_primary ('product_variants', 'id');
    $def->set_ref ('directories', 'product_variants', 'id_directory');
    $def->set_ref ('product_variants', 'cart', 'id_product_variant');
    $def->set_listref ('product_variants', 'id_last', 'id_next');
  
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
          array ('n' => 'id_product_variant',
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

    $def->define_table (
  	'addresses',
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
    $def->set_primary ('addresses', 'id');
  
    $def->define_table (
  	'orders',
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
    $def->set_primary ('orders', 'id');
}

?>

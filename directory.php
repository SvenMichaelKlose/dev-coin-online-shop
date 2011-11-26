<?
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Describe directory hierarchy for dbobj.class.
$dep->set_ref ('categories', 'categories', 'id_parent');
$dep->set_ref ('categories', 'pages',      'id_category');
$dep->set_ref ('pages',      'products',   'id_page');
$dep->set_ref ('products',   'cart',       'id_product');
$dep->set_primary ('categories', 'id');
$dep->set_primary ('pages', 'id');
$dep->set_primary ('products', 'id');
$dep->set_primary ('cart', 'id');

# Create directory types.
$scanner->assoc ('CATEGORY', 'categories');
$scanner->assoc ('PAGE',     'pages');
$scanner->assoc ('PRODUCT',  'products');
$scanner->assoc ('SESSION',  true);
$scanner->assoc ('CART',     true);
?>

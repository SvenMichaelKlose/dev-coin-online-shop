<?php

# Global CMS configuration.
#
# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

$cms_root_table = 'directories';
$cms_root_id = 1;

# Default document templates.
$default_document = array ('INDEX' => 'l_index',
                           'CATEGORY' => 'l_category',
                           'PAGE' => 'l_page',
                           'PRODUCT' => 'l_product',
                           'CART' => 'l_cart',
                           'ORDER' => 'l_order',
                           'SEARCH' => 'l_search');

# Module specific objects.
$cms_module_templates = array (
    'CART' => array ('l_empty_cart'),
    'ORDER' => array ('l_order_email', 'll_order_email',
                      'l_order_confirm', 'll_order_confirm',
                      'l_ecml',
                      'd_order_address', 'd_order_duty', 'd_order_extra',
                      'd_order_email_subject')
);

# Class specific editors.
$cms_object_editors = array ('d_order_duty' => 'edit_duty_fields',
                             'd_order_extra' => 'edit_user_fields',
			     'u_attribs' => 'product_attrib_edit',
			     'u_attrib_mask' => 'product_attrib_mask_edit');

$cms_object_views = array ('u_attrib_mask' => 'product_attrib_mask_view');

# Virtual directory aliases for link creation.
$vdir_name = array ('Warenkorb' => 'CART', 'Bestellung' => 'ORDER');
$vdir_alias = array ('CART' => 'Warenkorb', 'ORDER' => 'Bestellung');
$vdir_name['Suche'] = 'SEARCH';
$vdir_alias['SEARCH'] = 'Suche';

?>

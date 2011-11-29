<?php

# Miscellaneous database stuff.
#
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function create_directory_type (&$db, $name)
{
    $db->insert ('directory_types', "name='$name'");
}

function get_directory_type_id (&$db, $name)
{
    return $db->select ('id', 'directory_types', "name='$name'")->get ('id');
}

function create_directory_types (&$app)
{
    global $lang;

    $db =& $app->db;

    $types = array ('category', 'product', 'product_variant');
    foreach ($types as $type) 
        if (!$res = $db->select ('*', 'directory_types', "name='$type'"))
            create_directory_type ($db, $type);
}

?>

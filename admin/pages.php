<?php

# Product group editors.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 dev/consulting GmbH
#
# Licensed under the MIT, BSD and GPL licenses.

function page_init (&$app)
{
    $app->add_function ('view_pages');
}

function view_pages (&$app)
{
    global $lang;

    $c = new generic_list_conf;

    $c->parent_table = 'directories';
    $c->parent_view = 'view_pages';

    $c->table = 'directories';
    $c->values = array ('id_directory_type' => get_directory_type_id ($app->db, 'category'));

    $c->child_table = 'directories';
    $c->child_view = 'view_pages';
    $c->child_values = array ('id_directory_type' => get_directory_type_id ($app->db, 'product'));

    $c->have_submit_button = true;

    generic_list ($app, $c);
}

?>

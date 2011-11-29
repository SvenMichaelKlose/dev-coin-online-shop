<?php

# Category editor
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function category_init (&$app)
{
    $app->add_function ('create_category');
}

# Create a subcategory for a category.
function create_category (&$app)
{
    global $lang;
    $ui = & $app->ui;

    # XXX reference to parent should be set via hash of preset values.
    $nid = $app->db->append_new ('directories', $app->arg ('id'));
    $ui->mark_id = "directories$nid";
    $ui->color_highlight = '#00FF00';
    $ui->msgbox ($lang['msg category created']);
    $app->call (new event ('defaultview'));
}

?>

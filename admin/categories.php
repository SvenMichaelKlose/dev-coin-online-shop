<?
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
# id = Key of parent category.
function create_category (&$app)
{
    global $lang;
    $ui = & $app->ui;

    $nid = $app->db->append_new ('categories', $app->arg ('id'));
    $ui->mark_id = "categories_$nid";
    $ui->color_highlight = '#00FF00';
    $ui->msgbox ($lang['msg category created']);
}
?>

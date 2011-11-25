<?
# Category editor
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function category_init (&$this)
{
    $this->add_viewfunc ('create_category');
}

# Create a subcategory for a category.
# id = Key of parent category.
function create_category (&$this)
{
    global $lang;

    $nid = $this->db->append_new ('categories', $this->arg ('id'));
    $this->ui->mark_id = 'categories_' . $nid;
    $this->ui->color_highlight = '#00FF00';
    $this->ui->msgbox ($lang['msg category created']);
}
?>

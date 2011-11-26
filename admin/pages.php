<?
# Product group editors.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 dev/consulting GmbH
#
# Licensed under the MIT, BSD and GPL licenses.


function page_init (&$this)
{
    $this->add_viewfunc ('view_pages');
}

function view_pages (&$this)
{
    global $lang;

    $c = new generic_list_conf;
    $c->table = 'categories';
    $c->parent_table = 'categories';
    $c->child_table = 'pages';
    $c->ref_table = 'id_category';
    $c->ref_parent = 'id_parent';
    $c->recordfunc = 'record_page';
    $c->txt_no_func = $lang['msg no product group'];
    $c->txt_create = $lang['cmd create_page'];
    $c->txt_input = $lang['category'];
    $c->parent_view = 'defaultview';
    $c->child_view = 'view_products';
    $c->have_submit_button = true;
    generic_list ($this, $c);
}

function record_page (&$this, $idx)
{
    global $lang;

    $p =& $this->ui;

    $nam = trim ($p->value ('name'));
    if ($nam == '')
        $nam = $lang['unnamed'];

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'LEFT', 'WIDTH' => '100&'));
    $p->link ("$idx $nam", 'view_products', array ('id' => $p->value ('id')));
    $p->close_cell ();
    $p->open_cell (array ('ALIGN' => 'RIGHT'));
    $p->link ($lang['cmd view_products'], 'view_products', array ('id' => $p->value ('id')));
    $p->close_cell ();
    $p->close_row ();

    $p->paragraph ();

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $p->checkbox ('marker');
    $p->close_cell ();
    $p->open_cell ();
    _object_box ($this, 'pages', $p->value ('id'), $this->args, true);
    $p->close_cell ();
    $p->close_row ();
    $p->paragraph ();
}
?>

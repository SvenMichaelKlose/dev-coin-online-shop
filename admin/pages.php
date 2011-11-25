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

    generic_list ($this,
                  'categories', 'categories', 'pages', 'id_category', 'id_parent',
                  Array (''),
                  'record_page',
                  $lang['msg no product group'], $lang['cmd create_page'],
                  $lang['category'], 'defaultview', 'view_products',
                  true); # Have submit button.
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
    $p->link ($idx . ' ' . $nam, 'view_products', array ('id' => $p->value ('id')));
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

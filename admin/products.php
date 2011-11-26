<?
# Product editors.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function product_init (&$this)
{
    $this->add_viewfunc ('view_products');
    $this->add_viewfunc ('edit_product');
    $this->add_viewfunc ('products_after_create');
}

# View products within a product group.
function view_products (&$this)
{
    global $lang;

    generic_list ($this,
                  'pages', 'categories', 'products', 'id_page', 'id_category',
                  Array ('&nbsp;', '&nbsp;', $lang['description'], $lang['product key'], $lang['price'] . ' Euro'),
                  'record_product',
                  $lang['msg no product'], $lang['cmd create_product'],
                  $lang['product group name'],
                  'view_pages', 'products_after_create',
                  true); # Have submit button.
}

function record_product (&$this, $idx)
{
    $p =& $this->ui;

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $p->checkbox ('marker');
    $p->close_cell ();
    $p->label ("$idx .", array ('ALIGN' => 'CENTER'));
    $p->inputline ('name', 40);
    $p->inputline ('bestnr', 16);
    $p->inputline ('price_eur', 8);
    $p->cmd ('edit', 'id', 'edit_product');   
    $p->close_row ();
}

function edit_product (&$this)
{
    global $lang;

    $this->args['table'] = 'products';
    $id = $this->args['id'];
    $p =& $this->ui;

    # Navigator
    $p->headline ($lang['title edit_product']);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($this, 'products', $id);

    # Show all objects for this product.
    _object_box (&$this, 'products', $id, $this->args, false);

    # Get group id.
    $res = $this->db->select ('id_page', 'products', "id=$id");
    list ($pid) = $res->fetch_array ();

    # Create form for product fields.
    $p->open_source ('products', '_update', $this->arg_set_next (0, $this->view, $this->args));
    if ($p->get ("where id=$id")) {
        $p->open_row ();
        $p->label ($lang['description']);
        $p->inputline ('name', 255);
        $p->close_row ();
        $p->open_row ();
        $p->label ($lang['product key']);
        $p->inputline ('bestnr', 255);
        $p->close_row ();
        $p->open_row ();
        $p->label ($lang['price'] . ' (DM)');
        $p->inputline ('price_dm', 255);
        $p->close_row ();
        $p->open_row ();
        $p->label ($lang['price'] . ' (&euro;)');
        $p->inputline ('price_eur', 255);
        $p->close_row ();
        $p->paragraph ();
        $p->open_row ();
        $p->cmd_delete ($lang['remove'], 'view_products', array ('id' => $pid));
        $p->submit_button ('Ok', '_update', $this->arg_set_next (0, $this->view, $this->args));
        $p->close_row ();
    }
    $p->close_source ();
}

function products_after_create (&$this)
{
    $id_page = $this->db->column ('products', 'id_page', $this->arg ('id'));
    $this->call_view ('view_products', array ('id' => $id_page));
}
?>

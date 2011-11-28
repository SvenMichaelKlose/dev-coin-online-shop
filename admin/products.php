<?
# Product editors.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function product_init (&$app)
{
    $app->add_function ('view_products');
    $app->add_function ('edit_product');
    $app->add_function ('products_after_create');
}

# View products within a product group.
function view_products (&$app)
{
    global $lang;

    $c = new generic_list_conf;
    $c->table = 'pages';
    $c->parent_table = 'categories';
    $c->child_table = 'products';
    $c->ref_table = 'id_page';
    $c->ref_parent = 'id_category';
    $c->headers = Array ('&nbsp;', '&nbsp;', $lang['description'], $lang['product key'], $lang['price'] . ' Euro');
    $c->recordfunc = 'record_product';
    $c->txt_no_records = $lang['msg no product'];
    $c->txt_create = $lang['cmd create_product'];
    $c->txt_input = $lang['product group name'];
    $c->parent_view = 'view_pages';
    $c->child_view = 'products_after_create';
    $c->have_submit_button = true;
    generic_list ($app, $c);
}

function record_product (&$app, $idx)
{
    $p =& $app->ui;

    $p->open_row ();
    $p->checkbox ('marker');
    $p->label ("$idx.");
    $p->inputline ('name', 40);
    $p->inputline ('bestnr', 16);
    $p->inputline ('price_eur', 8);
    $p->cmd ('edit', 'id', 'edit_product');   
    $p->close_row ();
}

function edit_product (&$app)
{
    global $lang;

    $app->event->set_arg ('table', 'products');
    $id = $app->arg ('id');
    $p =& $app->ui;

    # Navigator
    $p->headline ($lang['title edit_product']);
    $p->link ($lang['cmd defaultview'], 'defaultview');
    show_directory_index ($app, 'products', $id);

    # Show all objects for this product.
    _object_box ($app, 'products', $id, $app->args, false);

    # Get group id.
    $pid = $db->select ('id_page', 'products', "id=$id")->get ('id_page');

    # Create form for product fields.
    $p->open_source ('products', "id=$id");
    if ($p->get ()) {
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
        $p->cmd_update ();
        $p->close_row ();
    }
    $p->close_source ();
}

function products_after_create (&$app)
{
    $id_page = $app->db->column ('products', 'id_page', $app->arg ('id'));
    $app->call ('view_products', array ('id' => $id_page));
}
?>

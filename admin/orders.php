<?
# List of orders.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function order_init (&$this)
{
    $this->add_viewfunc ('view_orders');
}

function view_orders (&$this)
{
    global $lang;
    $p =& $this->ui;
    $p->headline ('&Uuml;bersicht der Bestellungen');
    $p->link ($lang['cmd defaultview'], 'defaultview');

    $p->open_source ('ecml_order');
    $p->no_update = true;
    if ($p->get ())
        do {
            $p->open_row ();
            $p->show_ref ('id_address_shipto', 'address', 'city');
            $p->show_ref ('id_address_shipto', 'address', 'name_prefix');
            $p->show_ref ('id_address_shipto', 'address', 'name_first');
            $p->show_ref ('id_address_shipto', 'address', 'name_middle');
            $p->show_ref ('id_address_shipto', 'address', 'name_last');
            $p->show_ref ('id_address_shipto', 'address', 'name_suffix');
            $p->show_ref ('id_address_shipto', 'address', 'company');
            $p->show_ref ('id_address_shipto', 'address', 'street1');
            $p->show_ref ('id_address_shipto', 'address', 'street2');
            $p->show_ref ('id_address_shipto', 'address', 'street3');
            $p->show_ref ('id_address_shipto', 'address', 'city');
            $p->show_ref ('id_address_shipto', 'address', 'state');
            $p->show_ref ('id_address_shipto', 'address', 'postal_code');
            $p->show_ref ('id_address_shipto', 'address', 'country_code');
            $p->show_ref ('id_address_shipto', 'address', 'phone');
            $p->show_ref ('id_address_shipto', 'address', 'email');
            $p->close_row ();
        } while ($p->get_next ());
    else
        $p->label ('Noch keine Bestellungen erfolgt.');
    $p->close_source ();
}
?>

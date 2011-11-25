<?
  # $Id: orders.php,v 1.6 2001/11/08 07:49:06 sven Exp $
  #
  # List of orders.
  #
  # (c)2000-2001 dev/consulting GmbH
  #	    	 Sven Klose (sven@devcon.net)
  #
  # This program is free software; you can redistribute it and/or modify
  # it under the terms of the GNU General Public License as published by
  # the Free Software Foundation; either version 2 of the License, or
  # (at your option) any later version.
  #
  # This program is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  # GNU General Public License for more details.
  #
  # You should have received a copy of the GNU General Public License
  # along with this program; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

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

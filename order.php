<?
  # $Id: order.php,v 1.26 2001/12/01 16:17:18 sven Exp $
  #
  # ORDER directory extension for dev/con cms.
  #
  # (c) 2000-2001 dev/consulting GmbH,
  #	    	  Sven Klose (sven@devcon.net)
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

  # About this file:
  #
  # The tag handlers in this file are wrappers to the address form functions
  # in ecml.php.

  $order_errors = '';		# Errors on missing ecml form fields.
				# See document_ecml().

  $scanner->assoc ('ORDER',    'ecml_order');
  $scanner->dirtag ('ORDER',
                    'LINK ECML-VERSION FINISH ' .
                    'SHIPTO BILLTO RECEIPTTO ' .
                    'SHIPTO-NAME BILLTO-NAME RECEIPTTO-NAME ' .
                    'IS-INCOMPLETE ERRORS');

  # Document handler for ORDER documents.
  function document_order ()
  {
    global $session, $scanner, $db, $dep, $use_cookies, $SERVER_NAME,
           $SCRIPT_NAME;

    $table = $scanner->context_table;
    $id = $scanner->context['id'];

    # Fetch current session key. If there's none, report odd things are
    # happening and exit.
    if (!$session->id ()) {
      panic ('document_order: No session key.',
	     'Maybe tries to pinch address information.');
      exit;
    }

    # Redirect to empty cart template if appropriate.
    $res = $db->select ('id' , 'cart', 'id_session=' . $session->id ());
    if ($res->num_rows () < 1) {
      document_set_template (cms_fetch_object ('l_empty_cart'));
      return;
    }

    # Read in order record.
    $res =& $db->select ('*', 'ecml_order', 'id_session=' . $session->id ());

    # Perform order if address info is complete.
    if (ecml_parse_form () == true) {
      do_order ();
      $session->lock ();
      if ($use_cookies)
        setcookie ('SESSION_KEY', $session->key (), time () - 3600,
                   $SCRIPT_NAME, $SERVER_NAME);

      # Redirect to thank-you-page.
      document_set_template (cms_fetch_object ('l_ecml'));
      return;
    }
  }

  function dirtag_order_link ($arg)
  {
    global $vdir_alias;

    return tag_link (array ('template' => $vdir_alias['ORDER']));
  } 

  # Be ECML compliant (http://www.ecml.org/ECMLv1.1fieldspec.txt)
  function dirtag_order_ecml_version ($arg)
  {
    return '<INPUT TYPE=HIDDEN NAME="' . ecml_version_name () . '" ' .
	   'VALUE="' . ecml_version () . '">';
  }

  function dirtag_order_finish ($arg)
  {
    return '<input type="hidden" name="' . ecml_finish () . '" value="yes">';
  }

  function dirtag_order_shipto ($attr)
  {
    return ecml_address_field ($attr['field'], 'shipto');
  }

  function dirtag_order_billto ($attr)
  {
    return ecml_address_field ($attr['field'], 'billto');
  }

  function dirtag_order_receiptto ($attr)
  {
    return ecml_address_field ($attr['field'], 'receiptto');
  }

  function dirtag_order_shipto_name ($attr)
  {
    return ecml_address_field_name ('ShipTo', $attr['field']);
  }

  function dirtag_order_billto_name ($attr)
  {
    return ecml_address_field_name ('BillTo', $attr['field']);
  }

  function dirtag_order_receiptto_name ($attr)
  {
    return ecml_address_field_name ('ReceiptTo', $attr);
  }

  function dirtag_order_is_incomplete ($arg)
  {
    return $GLOBALS['order_errors'] ? 1 : 0;
  }

  function dirtag_order_errors ($arg)
  {
    return $GLOBALS['order_errors'];
  }
?>

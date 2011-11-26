<?
# ORDER directory extension for dev/con cms.
#
# Copyright (c) 2000-2001 dev/consulting GmbH,
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# The tag handlers in this file are wrappers to the address form functions
# in ecml.php.


# Errors on missing ecml form fields. See document_ecml().
$order_errors = '';

$scanner->assoc ('ORDER', 'ecml_order');
$scanner->dirtag ('ORDER', 'LINK ECML-VERSION FINISH SHIPTO BILLTO RECEIPTTO SHIPTO-NAME BILLTO-NAME RECEIPTTO-NAME IS-INCOMPLETE ERRORS');

# Document handler for ORDER documents.
function document_order ()
{
    global $session, $scanner, $db, $dep, $use_cookies, $SERVER_NAME, $SCRIPT_NAME;

    $table = $scanner->context_table;
    $id = $scanner->context['id'];
    $sid = $session->id ();

    # Fetch current session key. If there's none, report odd things are
    # happening and exit.
    if (!$session->id ())
        panic ('document_order: No session key.', 'Maybe tries to pinch address information.');

    # Redirect to empty cart template if appropriate.
    if (cart_is_empty ()) {
        document_set_template (cms_fetch_object ('l_empty_cart'));
        return;
    }

    # Perform order if address info is complete.
    if (ecml_parse_form () == true) {
        do_order ();
        $session->lock ();
        if ($use_cookies)
            setcookie ('SESSION_KEY', $session->key (), time () - 3600, $SCRIPT_NAME, $SERVER_NAME);

        # Redirect to thank-you-page.
        document_set_template (cms_fetch_object ('l_ecml'));
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
    return '<input type="hidden" name="' . ecml_version_name () . '" value="' . ecml_version () . '">';
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

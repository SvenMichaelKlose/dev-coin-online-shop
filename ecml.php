<?
# ECML extension
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# This file contains cms-independent functions to process ECML v1.1
# address forms. (see http://www.ecml.org/version/1.1 for more details on
# Electronic Commerce Markup Language).


function ecml_version_name ()
{
    return 'Ecom_SchemaVersion';
}

function ecml_version ()
{
    return 'http://www.ecml.org/version/1.1';
}

function ecml_finish ()
{
    return 'Ecom_TransactionComplete';
}

function ecml_address_field_name ($type, $shortcut)
{
    $fields = ecml_typearray ();
    $name = $fields[strtolower ($shortcut)];
    if (!$name)
        die ("No such ECML $type-fieldname '$shortcut'.");
    return 'Ecom_' . $type . "_$name";
}

# Fetch particular address field.
# One may want to fetch the address set just once.
function ecml_address_field ($arg, $address)
{
    global $scanner, $db, $session;

    # Fetch key of address field in order record.
    $res = $db->select ("id_address_$address", 'ecml_order', 'id_session=' . $session->id ());
    if ($res->num_rows () < 1)
        return;
    list ($id) = $res->fetch_array ();
    if (!$id)
        return;

    # Fetch field from address record.
    $res = $db->select ($arg, 'address', "id=$id");
    if ($res->num_rows () < 1)
        return;
    list ($field) = $res->fetch_array ();
    return $field;
}

# Returns a associative array with shortcuts of ECML field names.
function ecml_typearray ()
{
    $ecml['name_prefix'] =	'Postal_Name_Prefix';
    $ecml['name_first'] =	'Postal_Name_First';
    $ecml['name_middle'] =	'Postal_Name_Middle';
    $ecml['name_last'] =	'Postal_Name_Last';
    $ecml['name_suffix'] =	'Postal_Name_Suffix';
    $ecml['company'] =		'Postal_Company';
    $ecml['street1'] =		'Postal_Street_Line1';
    $ecml['street2'] =		'Postal_Street_Line2';
    $ecml['street3'] =		'Postal_Street_Line3';
    $ecml['city'] =		'Postal_City';
    $ecml['state'] =		'Postal_StateProv';
    $ecml['postal_code'] =	'Postal_PostalCode';
    $ecml['country_code'] =	'Postal_CountryCode';
    $ecml['phone'] =		'Telecom_Phone_Number';
    $ecml['email'] =		'Online_Email';
    return $ecml;
}

# Insert or update a ECML address record.
# $type is one of BillTo, ShipTo or ReceiptTo.
function ecml_add_address ($db, $type, $idname)
{
    global $session;
    $SESSION_ID = $session->id ();

    $idname = "id_address_$idname";
    # Get address' id.
    $res = $db->select ($idname, 'ecml_order', "id_session=$SESSION_ID");
    if ($res->num_rows () < 1) {
        $db->insert ('ecml_order', "id_session=$SESSION_ID");
        $id = 0;
    } else
        list ($id) = $res->fetch_array ();

    # Collect each existing field into update set.
    $first = true;
    $set = '';
    $ecml = ecml_typearray ();
    foreach ($ecml as $k => $v) {
        $fname = 'Ecom_' . $type . '_' . $v;
        if (isset ($GLOBALS[$fname])) {
	    $field = $GLOBALS[$fname];
	    if (!$first)
	        $set .= ',';
	    else
	        $first = false;
	    $set .= "$k='$field'";
        }
    }

    if ($set) {
        # Create new address record if missing.
        if (!$id) {
            $db->insert ('address', 'name_last=\'\'');
            $id = $db->insert_id ();
            $db->update ('ecml_order', "$idname=$id", "id_session=$SESSION_ID");
        }
        $db->update ('address', $set, "id=$id");
    }
}

# Processes an ECML form.
# Returns: true if form is complete.
function ecml_parse_form ()
{
    global $session, $scanner, $dep, $db;

    $SESSION_ID = $session->id ();
    $SESSION_KEY = $session->key ();

    # Check for adress fields
    ecml_add_address ($db, 'ShipTo',	'shipto');
    ecml_add_address ($db, 'BillTo',	'billto');
    ecml_add_address ($db, 'ReceiptTo', 'receiptto');

    # TODO: Check for credit card info.

    # Read in ECML-Fields.
    if (isset ($GLOBALS['Ecom_SchemaVersion']) && $GLOBALS['Ecom_SchemaVersion'] == 'http://www.ecml.org/version/1.1') {
        global $order_errors;

        # Read in duty field description.
        $tmp =& cms_fetch_object ('d_order_duty');
        if (!$tmp)
            die ('No duty fields defined - stop.');
        $tmp = unserialize ($tmp);

        foreach ($tmp as $at => $f) {
            if (!is_array ($f['duty_fields']))
	        continue;
            $af = $f['duty_fields'];
	    $am = $f['duty_msgs'];
            for ($t = sizeof ($af), reset ($af); $t--; next ($af)) {
	        $k = key ($af);
	        $duty[$at][] = $k;
	        $msg[$at][$k] = stripslashes ($am[$k]);
            }
        }

        # Query fields for each address type.
        foreach ($duty as $addrtype => $address) {
	    $res = $db->select ('id_address_' . $addrtype, 'ecml_order', "id_session=$SESSION_ID");
	    list ($aid) = $res->fetch_array ();

	    # Read in duty fields to check them later.
	    for ($list = '', $v = reset ($address); $v;) {
	        $list .= $v;
	        if ($v = next ($address))
	            $list .= ',';
            }

	    $fields = Array ('');
	    if ($aid) {
	        $res = $db->select ($list, 'address', "id=$aid");
                if ($res->num_rows () > 0)
	            $fields = $res->fetch_array ();
	    }

	    # Check duty fields.
	    foreach ($address as $v)
	        if (!isset ($fields[$v]) || !$fields[$v])
	            $order_errrors .= $msg[$addrtype][$v] || "No warning phrase defined for missing field '$v'";
        }
        # Return true if address info is complete.
        if (!$order_errors && $GLOBALS['Ecom_TransactionComplete'])
	    return true;
    }
    return false;
}
?>

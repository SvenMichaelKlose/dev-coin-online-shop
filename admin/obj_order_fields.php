<?
# ECML form configuration
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Print an order field.
function print_field_form (&$app, $title, $p, $stdtype, $adrtype, $data)
{
    global $lang;

    if (isset ($GLOBALS[$adrtype . '_duty_fields']))
        $form_fields = $GLOBALS[$adrtype . '_duty_fields'];
    if (isset ($form_fields) && is_array ($form_fields))
        $duty_fields = $form_fields;
    else
        $duty_fields = $data[$adrtype]['duty_fields'];

    if (isset ($GLOBALS[$adrtype . '_duty_msgs']))
        $form_fields = $GLOBALS[$adrtype . '_duty_msgs'];
    if (isset ($form_fields) && is_array ($form_fields))
        $duty_msgs = $form_fields;
    else
        $duty_msgs = $data[$adrtype]['duty_msgs'];

    $p->headline ($lang["title $adrtype"] . ':');
    $p->open_table ();
    for (reset ($stdtype); $n = key ($stdtype); next ($stdtype)) {
        $p->open_row ();
        $p->open_cell ();
        isset ($duty_fields[$n]) ? $sel = ' CHECKED' : $sel = '';
        echo '<div align="left"><INPUT TYPE="CHECKBOX" NAME="' . $adrtype . '_duty_fields[' . $n . ']" VALUE="1"' . $sel . '> '. $n . '</div>';
        $p->close_cell ();
        $p->open_cell ();
        echo '<div align="left"><INPUT TYPE="TEXT" SIZE="60" NAME="' . $adrtype . '_duty_msgs[' . $n . ']" VALUE="' . htmlentities (stripslashes ($duty_msgs[$n])) . '"></div>';
        $p->close_cell ();
        $p->close_row ();
    }
    $p->paragraph ();
    $p->open_row ();
    $e = new event ('remove_object', $app->args ());
    $e->set_next ($app->args ('caller'));
    $p->link ($lang['remove'], $e);
    $p->cmd_update ();
    $p->close_row ();
    $p->close_table ();
    return array ('duty_fields' => $duty_fields, 'duty_msgs' => $duty_msgs);
}

  # Edit duty fields with checkboxes.
function edit_duty_fields (&$app, &$obj, $class)
{
    global $lang;

    $p =& $app->ui;

    $data = unserialize ($obj->active['data']);

    $p->headline ($lang['title edit_duty_fields']);
    $stdtype = ecml_typearray ();

    echo '<form method="post" action="' . $app->link ('edit_data', $app->args) . '">';
    $ret['shipto'] = print_field_form ($app, 'Lieferadresse:', $p, $stdtype, 'shipto', $data);
    $ret['receiptto'] = print_field_form ($app, 'Lieferscheinadresse:', $p, $stdtype, 'receiptto', $data);
    $ret['billto'] = print_field_form ($app, 'Rechnungsadresse:', $p, $stdtype, 'billto', $data);
    echo '</FORM>';

    # Write config back to object.
    $obj->active['data'] = serialize ($ret);
    $obj->assoc ();
}

  # Edit user defined field names and description.
function edit_user_fields (&$app, &$obj, $class)
{
    global $lang, $name, $desc; # Form fields.

    $p =& $app->ui;

    $data = unserialize ($obj->active['data']);
    if (!is_array ($data))
        unset ($data);

    # Read in form fields overriding database content.
    if (is_array ($name) && is_array ($desc)) {
        unset ($data);
        for ($i = sizeof ($name) - 1; $i >= 0; $i--)
            $data[] = array ('name' => $name[$i], 'desc' => $desc[$i]);
    }

    # Remove a field.
    if ($app->args ('removefield')) {
        $tmp = $app->args ('removefield');
        unset ($data[$tmp]);
        $app->event ()->remove_arg ('removefield');
    }

    # Create new field.
    if ($app->args ('newfield')) {
        $data[] = array ('name' => $lang['unnamed'], 'desc' => '');
        $app->event ()->remove_arg ('newfield');
    }

    $p->headline ($lang['title edit_user_fields']);

    echo '<form method="post" action="' . $app->link ('edit_data', $app->args) . '">';
    $p->open_table ();
    $p->table_headers (array ('Name', 'Description'));
    $args = $app->args;

    if (is_array ($data)) {
        foreach ($data as $k => $v) {
            $args['removefield'] = $k;
            $name = $v['name'];
            $desc = $v['desc'];
            $p->open_row ();
            $p->open_cell ();
            echo '<INPUT TYPE="TEXT" NAME="name[]" SIZE="16" VALUE="' . $name . '">';
            $p->close_cell ();
            $p->open_cell ();
            echo '<INPUT TYPE="TEXT" NAME="desc[]" SIZE="60" VALUE="' . $desc . '">';
            $p->close_cell ();
            $args = $app->args ();
            $args['removefield'] = $k;
            $e = new event ('remove_data');
            $e->set_next ($app->event ());
            $p->link ($lang['remove'], $e);
            $p->close_row ();
        }
    }

    $p->paragraph ();

    $p->open_row ();

    $tmp = $app->args ();
    $tmp['class'] = $class;
    $e = new event ( 'remove_object', $tmp);
    $e->set_next ($app->args ('caller'));
    $p->link ($lang['remove'], $e);

    $tmp = $app->args ();
    $tmp['newfield'] = true;
    $e = new event ('edit_data', $tmp);
    $e->set_next ($app->event ());
    $p->link ($lang['cmd user_field_new'], $e);

    $p->cmd_update ();

    $p->close_row ();

    $p->close_table ();
    echo '</FORM>';

    # Write config back to object.
    $obj->active['data'] = serialize ($data);
    $obj->assoc ();
}
?>

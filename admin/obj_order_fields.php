<?
# ECML form configuration
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Print an order field.
function print_field_form (&$this, $title, $p, $stdtype, $adrtype, $data)
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
    $tmp = $this->args;
    $tmp['__next'] = $this->args['caller'];
    $p->link ($lang['remove'], 'remove_object', $tmp);
    $p->submit_button ('Ok', '_update', $this->arg_set_next (0, $this->view));
    $p->close_row ();
    $p->close_table ();
    return array ('duty_fields' => $duty_fields, 'duty_msgs' => $duty_msgs);
}

  # Edit duty fields with checkboxes.
function edit_duty_fields (&$this, &$obj, $class)
{
    global $lang;

    $p =& $this->ui;

    $data = unserialize ($obj->active['data']);

    $p->headline ($lang['title edit_duty_fields']);
    $stdtype = ecml_typearray ();

    echo '<form method="post" action="' . $this->link ('edit_data', $this->args) . '">';
    $ret['shipto'] = print_field_form ($this, 'Lieferadresse:', $p, $stdtype, 'shipto', $data);
    $ret['receiptto'] = print_field_form ($this, 'Lieferscheinadresse:', $p, $stdtype, 'receiptto', $data);
    $ret['billto'] = print_field_form ($this, 'Rechnungsadresse:', $p, $stdtype, 'billto', $data);
    echo '</FORM>';

    # Write config back to object.
    $obj->active['data'] = serialize ($ret);
    $obj->assoc ();
}

  # Edit user defined field names and description.
function edit_user_fields (&$this, &$obj, $class)
{
    global $lang, $name, $desc; # Form fields.

    $p =& $this->ui;

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
    if (isset ($this->args['removefield'])) {
        $tmp = $this->args['removefield'];
        unset ($data[$tmp]);
        unset ($this->args['removefield']);
    }

    # Create new field.
    if ($this->args['newfield']) {
        $data[] = array ('name' => $lang['unnamed'], 'desc' => '');
        unset ($this->args['newfield']);
    }

    $p->headline ($lang['title edit_user_fields']);

    echo '<form method="post" action="' . $this->link ('edit_data', $this->args) . '">';
    $p->open_table ();
    $p->table_headers (array ('Name', 'Description'));
    $args = $this->args;

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
            $args = $this->args;
            $args['removefield'] = $k;
            $p->link ($lang['remove'], 'edit_data', $args);
            $p->close_row ();
        }
    }

    $p->paragraph ();
    $p->open_row ();
    $tmp = $this->args;
    $tmp['class'] = $class;
    $tmp['__next'] = $this->args['caller'];
    $p->link ($lang['remove'], 'remove_object', $tmp);
    $args = $this->args;
    $args['newfield'] = true;
    $p->link ($lang['cmd user_field_new'], 'edit_data', $args);
    $p->submit_button ('Ok', '_update', $this->arg_set_next (0, $this->view));
    $p->close_row ();

    $p->close_table ();
    echo '</FORM>';

    # Write config back to object.
    $obj->active['data'] = serialize ($data);
    $obj->assoc ();
}
?>

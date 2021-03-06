<?php

# User interface
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


class devcoin_admin_panel extends admin_panel {
    private $_is_open = false;

    function devcoin_admin_panel ($application)
    {
        $this->admin_panel ($application, new widget_set);
    }

    function make_event ($handler = '', $args = 0)
    {
        return $handler ? new event ($handler, $args) : $this->application->event ();
    }

    function open_source ($table, $handler = '', $args = 0)
    {
        $c = new cursor_sql ($table);
        $c->set_source ($table);

        $this->open_context ($c);
        $this->open_form ($this->make_event ($handler, $args));
        $this->open_table ();

        $_is_open = true;
    }

    function close_source ()
    {
        $this->close_table ();
        $this->close_form ();
        $this->close_context ();

        $_is_open = false;
    }

    function query ($where = '', $order = '')
    {
        return $this->cursor ()->query ($where, $order);
    }

    function get ()
    {
        return $this->cursor ()->get ();
    }

    function open_row_and_cell ()
    {
        $this->open_row ();
        $this->open_cell ();
    }

    function close_cell_and_row ()
    {
        $this->close_cell ();
        $this->close_row ();
    }

    function _make_cmd ($cmd, $label, $handler, $args)
    {
        global $lang;

        if (!$label)
            $label = $lang["cmd $cmd"];

        $this->open_row_and_cell ();
        $e = new event (($this->no_update || $cmd == 'delete' ? 'record' : 'form') . "_$cmd");
        $e->set_next ($this->make_event ($handler, $args));
        $this->submit_button ($label, $e);
        $this->close_cell_and_row ();
    }

    function cmd_create ($label = '', $handler ='', $args = 0)
    {
        $this->_make_cmd ('create', $label, $handler, $args);
    }

    function cmd_delete ($label, $handler ='', $args = 0)
    {
        $this->_make_cmd ('delete', $label, $handler, $args);
    }

    function cmd_update ($label = '', $handler ='', $args = 0)
    {
        $this->_make_cmd ('update', $label, $handler, $args);
    }
}

?>

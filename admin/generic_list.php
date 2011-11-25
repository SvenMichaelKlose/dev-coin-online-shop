<?
  # $Id: generic_list.php,v 1.16 2001/10/23 17:32:28 sven Exp $
  #
  # Generic directory lister.
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

  function _get_index (&$this, $parent, $ref_parent, $table, $id)
  {
    $id_parent = $this->db->column ($table, $ref_parent, $id);
    $res = $this->db->select (
      'id, id_last, id_next', $table, $ref_parent . '=' . $id_parent
    );
    while ($row = $res->fetch_array ())
      $tmp[$row['id_last']] = $row;
    for ($row = reset ($tmp), $i = 1; $row; $row = next ($tmp), $i++)
      if ($row['id'] == $id)
        $thisindex = $i;
    return array ($thisindex, $i - 1);
  }

  function _range_panel (&$this, $id, $child_view, $txt_create, $table,
                         $have_submit_button = false)
  {
    global $lang;

    $p =& $this->ui;

    # Link to creator of new record.
    $p->use_key ('');
    $p->open_row ();
    $p->submit_button ('select range',
                       'tk_range_edit_select',
                       $this->arg_set_next (0, $this->view, $this->args));
    $sel = tk_range_edit_all_selected ($this);
    if ($sel == 0 || $sel == 2)
      $p->submit_button ('select all',
                         'tk_range_edit_select_all',
                         $this->arg_set_next (0, $this->view, $this->args));
    if ($sel == 1 || $sel == 2)
      $p->submit_button ('unselect all',
                         'tk_range_edit_unselect_all',
                         $this->arg_set_next (0, $this->view, $this->args));
    $p->submit_button ('delete', 'tk_range_edit_call',
                       array ('view' => '_delete', 'argname' => 'id',
                              'arg' => $this->arg_set_next (array ('table' => $table, '_ssi_obj' => 'dbi'), $this->view, $this->args)));
    $p->cmd_create (
      $txt_create,
      $child_view, 'id',
      $id,
      $lang['msg record created']
    );
    if ($have_submit_button)
      $p->submit_button ('Ok', '_update', $this->arg_set_next ());
    $p->close_row ();
  }

  # View products within a product group.
  function generic_list (
    &$this,
    $table, $parent_table, $child_table,
    $ref_table, $ref_parent,
    $headers, $recordfunc,
    $txt_no_records, $txt_create, $txt_input, $parent_view, $child_view,
    $have_submit_button = false
  )
  {
    global $lang;

    $this->args['table'] = $table;	# Needed for _object_box.
    $id = $this->args['id'];
    $p =& $this->ui;

    # Navigator
    $p->headline ($lang['title ' . $this->view]);
    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($this, $table, $id);

    # Link to next/last product group.
    $id_last = $this->db->column ($table, 'id_last', $id);
    $id_next = $this->db->column ($table, 'id_next', $id);
    list ($thisindex, $last)
      = _get_index ($this, $parent_table, $ref_parent, $table, $id);
    if ($id_last)
      $p->link ($lang['previous'],
                $this->view, array ('id' => $id_last));
    echo ' ' . sprintf ($lang['x of y'], $thisindex, $last) . ' ';
    if ($id_next)
      $p->link ($lang['next'], $this->view, array ('id' => $id_next));
    echo '<BR>';

    # Show all objects for this group.
    _object_box ($this, $table, $id, $this->args);

    # Input field for group name.
    $parent_id = $this->db->column ($table, $ref_parent, $id);
    $p->open_source ($table,
                    '_update',
                    $this->arg_set_next (0, $this->view, array ('id' => $id)));

    if ($p->get ("where id=$id")) {
      $p->open_row ();
      $p->cmd_delete ($lang['remove'],
                      $parent_view, array ('id' => $parent_id));
      $p->inputline ('name', 255, $txt_input);
      $p->submit_button ('Ok',
	                 '_update',
                         $this->arg_set_next (0,
                                              $this->view,
                                              array ('id' => $id)));
      $p->close_row ();
    }
    $p->close_source ();

    $p->open_source ($child_table);
    $p->use_filter ('form_safe');

    if ($p->get ('WHERE ' . $ref_table . '=' . $id, true)) {
      $p->table_headers ($headers);
      $idx = 1;
      do {
	$recordfunc ($this, $idx);
	$idx++;
      } while ($p->get_next ());

      $p->paragraph ();
      _range_panel ($this, $id, $child_view, $txt_create, $child_table,
                    $have_submit_button);
    } else {
      $p->label ($txt_no_records);
      $p->cmd_create (
        $txt_create,
        $child_view, 'id',
        $id,
        $lang['msg record created']
      );
    }

    $p->close_source ();

    return ''; # XXX
  }
?>

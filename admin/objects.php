<?
# Object editor.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function object_init (&$this)
{
    $this->add_viewfunc ('assoc_object');
    $this->add_viewfunc ('copy_object');
    $this->add_viewfunc ('remove_object');
    $this->add_viewfunc ('remove_object4real');
    $this->add_viewfunc ('edit_data');
}

# Associate object with a table.
# Creates an object and stores the object-id in the referenced table.
function assoc_object (&$this)
{
    global $lang;

    $class = $this->arg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');

    $obj =& new DBOBJ ($this->db, $class, $this->db->def);
    $obj->active['mime'] = 'text/plain';
    $obj->assoc ($table, $id);

    $this->ui->msgbox (sprintf ($lang['msg obj assoced'], $class));
}
  
# Copy object to another directory.
function copy_object (&$this)
{
    global $lang;

    $hier =& $this->db->def;
    $class = $this->arg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');
    $srctable = $this->arg ('srctable');
    $srcid = $this->arg ('srcid');

    $obj =& new DBOBJ ($this->db, $class, $hier, $table, $id);
    if ($obj->active['_table'] == $table && $obj->active['_id'] == $id) {
        $this->ui->msgbox ($lang['msg obj already exists'], "red");
        return;
    }

    # Fetch data of source object.
    $obj =& new DBOBJ ($this->db, $class, $hier, $srctable, $srcid);
    $data['data'] = $obj->active['data'];
    $data['mime'] = $obj->active['mime'];
    $data['start'] = $obj->active['start'];
    $data['is_local'] = $obj->active['is_local'];
    $data['is_public'] = $obj->active['is_public'];

    # Create a new object and copy the source data to it.
    $obj =& new DBOBJ ($this->db, $class, $hier);
    $obj->active = $data;

    # Associate object with source directory.
    $obj->assoc ($table, $id);
    $this->ui->msgbox (sprintf ($lang['msg obj assoced'], $table, $id));
}

function remove_object (&$this)
{
    global $lang;

    $class = $this->arg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');
    $otable = $this->subarg ('otable');
    $oid = $this->subarg ('oid');

    if ($table != $otable || $id != $oid)
        $q = $lang['ask remove other object'];
    else
        $q = $lang['ask remove object'];

    $arg = array ('class' => $class, 'table' => $table, 'id' => $id);
    $this->ui->confirm ($q, $lang['yes'], 'remove_object4real', $arg, $lang['no'], 'edit_data', $arg);
}
 
# Remove association of object.
# table/id = directory of object.
# class = object's class name.
function remove_object4real (&$this)
{
    global $lang;

    $db =& $this->db;
    $hierarchy =& $this->db->def;
    $class = $this->arg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');

    $obj =& new DBOBJ ($db, $class, $hierarchy, $table, $id, true);
    $obj->remove ();

    $this->ui->msgbox ($lang['msg obj removed']);
    $this->call_view ('return2caller');
}  

# Navigator for edit_data and related views.
function edit_data_navigator (&$this)
{
    global $lang;

    $p =& $this->ui;
    $dep =& $this->db->def;
    $class = $this->arg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');
    $otable = $this->subarg ('otable');
    $oid = $this->subarg ('oid');

    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($this, $otable, $oid);
    # Link back to originating view.
    $p->link ($lang['cmd back/quit'], 'return2caller');

    # Print paths of all available objects in the current path.
    $p->headline ($lang['available objects']);

    # Start with the caller's directory.
    $xtable = $otable;
    $xid = $oid;

    echo '<table border="0">';
    while (1) {
        # Fetch the object.
        $obj =& new DBOBJ ($this->db, $class, $dep, $xtable, $xid);

        # If there's none, stop right here.
        if (!isset ($obj->active))
            break;

        # Get source directory of the found object.
        $t = $obj->active['_table'];
        $i = $obj->active['_id'];

        # Mark the path if the object is currently displayed.
        if ($t == $table && $i == $id)
	    echo '<td><b>' . $lang['current'] . ' --></b></td><td>-</td>';
        else {
            echo '<td>&nbsp;</td><td>';
	    $p->link ($lang['copy to current'], 'copy_object', $this->arg_set_next (array ('class' => $class,
	  	                                                                           'table' => $otable,
	  	                                                                           'id' => $oid,
	  	                                                                           'srctable' => $obj->active['_table'],
	  	                                                                           'srcid' => $obj->active['_id'])));
	    echo '</td>';
        }

        # Print the path to the directory.
        echo '<td>';
        $p->link ($this->db->traverse_refs_from ($this, $t, $i, 'nav_linkpath', 1, false), 'edit_data', array ('table' => $t, 'id' => $i, 'class' => $class));
        echo '</td></tr>';

        # Fetch parent directory's position and break if there is none.
        $xtable = $t;
        $xid = $i;
        dbitree_get_parent ($this->db, $xtable, $xid);
        if (!$xid)
            break;
        echo '<BR>';
    }
    echo '</table>';
}

# Edit data, mime type and other flags of an object.
# caller/table/id = directory of object.
# class = Class of object.
function edit_data (&$this)
{
    global $lang, $cms_object_editors;

    $p =& $this->ui;
    $dep = $this->db->def;
    $otable = $this->subarg ('otable');
    $oid = $this->subarg ('oid');
    $class = $this->subarg ('class');
    $table = $this->arg ('table');
    $id = $this->arg ('id');
    $remove_args = $this->arg_set_next (compact ('class', 'table', 'id'));

    $p->headline ($lang['title edit_data']);

    # Output standard navigator with copy options.
    edit_data_navigator ($this);

    # Fetch the object we want to edit.
    $obj =& new DBOBJ ($this->db, $class, $dep, $table, $id, true);

    # Use external object editor if specified.
    if (isset ($cms_object_editors[$class]))
        return $cms_object_editors[$class] ($this, $obj, $class);
 
    # Fetch class description.
    $res = $this->db->select ('descr', 'obj_classes', "name='$class'");
    list ($classdesc) = $res->fetch_array ();

    # Open view on obj_data.
    $p->open_source ('obj_data');

    # Fetch the row.
    $p->get ('WHERE id=' . $obj->active['id']);

    # Show class name and example tag.
    $p->table_headers (array ('<B><FONT SIZE="+1">' . $classdesc .
                              '</FONT></B>'));

    $p->paragraph ();

    # Print mime type, times and flags.
    $p->open_row ();
    $p->inputline ('mime', 16, $lang['mime type']);
    $p->inputline ('start', 10, $lang['start time']);
    $p->inputline ('end', 10, $lang['end time']);

    # Radiobox local/not local.
    $p->radiobox ('is_local', $lang['yes'], $lang['no'], $lang['local'] . '<BR>');
    # Radiobox public/private.
    $p->radiobox ('is_public', $lang['yes'], $lang['no'], $lang['public'] . '<BR>');
    $p->submit_button ('Ok', '_update', $this->arg_set_next ());
    $p->close_row ();
    $p->paragraph ();

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $p->link ($lang['remove'], 'remove_object', $remove_args);
    $p->close_cell ();
    # Mark it as inherited or local.
    if ($obj->active['_table'] == $otable && $obj->active['_id'] == $oid)
        $p->label ($lang['local']);
    else
        $p->label ($lang['inherited']);
    # Print size in bytes.
    if (isset ($obj->active['data']))
        $p->label (strlen ($obj->active['data']) . ' bytes');
    else
        $p->label ($lang['empty']);
    $p->label (_class2tag ($class));
    $p->close_row ();
    $p->paragraph ('<hr>');

    # Show download link, textarea or image depending on mime type.
    $p->open_row (array ('ALIGN' => 'CENTER'));
    $mime = $p->value ('mime');
    $data = $p->value ('data');
    if (!$mime)
        $mime = 'text/plain';
    $mime = substr ($mime, 0, strpos ($mime, '/'));
    if ($mime == 'text') {
        $lines = substr_count ($data, "\n");
        if ($lines < 5)
            $lines = 5;
        else {
            $lines += 3;
	    if ($lines > 25)
                $lines = 25;
        }
        $p->textarea ('data', 80, $lines);
    } else {
        if ($mime == 'image')
            $p->show_mime_image ('data', $p->value ('mime'));
        else
            $p->label('<a href="' . $p->filelink ('obj_data', 'data', $mime, $id) . '">' . $lang['download'] . '</a>');
    }
    $p->close_row ();

    $p->paragraph ('<hr>');

    $p->open_row (array ('ALIGN' => 'CENTER'));
    $p->fileform ('data', $lang['upload'], 'mime', 'filename');
    $p->submit_button ('Ok', '_update', $this->arg_set_next ());
    $p->close_row ();

    if ($mime == 'text') {
        $p->paragraph ('<hr>');
        $p->label ('php syntax:');
        $p->open_row ();
        $p->open_cell ();
        highlight_string ($p->value ('data'));
        $p->close_cell ();
        $p->close_row ();
    }

    $p->close_source ();
}

# Show inherited and/or local objects.
# $table/$id specify the current directory.
# If $only_local is true only local objects are shown.
function _object_box (&$this, $table, $id, $caller, $only_local = false)
{
    global $lang, $cms_object_views;

    $caller['otable'] = $table; # Save starting point so the paths can be
    $caller['oid'] = $id;	#   displayer correctly by edit_data().
    $p =& $this->ui;
    $db =& $this->db;
    $dep =& $this->db->def;

    $oargs = $this->args;
    isset ($oargs['objmode'])
      ? $objmode = $oargs['objmode']
      : $oargs['objmode'] = $objmode = 0;
    $oargs['objmode'] ^= 1;

    # Print color descriptions.
    if (!$only_local) {
        if ($objmode & 1)
            $tmp = '<B>' . $lang['cmd objectbox hide'] . ':</B>';
        else
            $tmp = '<B>' . $lang['cmd objectbox unhide'] . '</B>';
      $p->link ($tmp, $this->args['__view'], $oargs);
      if (!($objmode & 1))
          $only_local = true; #return;
      echo ' <FONT COLOR="#0000CC">' . $lang['local'] . '</FONT> ' .
           '<FONT COLOR="#008800">' . $lang['inherited'] . '</FONT> ' .
           '<FONT COLOR="#666666">' . $lang['undefined'] . '</FONT>';
    }

    # Read all objects along the path to root to $cache.
    $t = $table;
    $i = $id;
    while ($t && $i) {
        $res =& $db->select ('*', $t, 'id=' . $i);
        if ($res->num_rows () > 0) {
            $tmp = $res->fetch_array ();
	    $res =& $db->select ('*', 'obj_data', 'id_obj=' . $tmp['id_obj']);
	    if ($res->num_rows () > 0)
	        while ($row =& $res->fetch_array ()) {
	            $row['_table'] = $t;
	            $row['_id'] = $i;
	            $cache[$row['id_class']][] =& $row;
	        }
        }
        dbitree_get_parent ($db, $t, $i);
    }

    # For each class, search for an object.
    $res =& $db->select ('id,name,descr', 'obj_classes', '', ' ORDER BY descr ASC');

    $documents = $enumerations = $configuration = $user_defined = $images = '';

    while (list ($id_class, $class, $descr) = $res->fetch_array ()) {
        $descr = ereg_replace (' ', '&nbsp;', $descr);
        $tmp = '';

        ### Simulate DBOBJ by reading objects from the cache.
        # Check if there's any object of the current class.

        # Link to create object if none found.
        if (!isset ($cache[$id_class][0]) && ((!$only_local) || ($only_local && substr ($class, 0, 2) == 'u_'))) {
            $tmp = '[' .
                   $p->_looselink ('<FONT COLOR="BLACK">' . $descr . '</FONT>' , 'assoc_object',
                                   $this->arg_set_next (array ('table' => $table, 'id' => $id, 'class' => $class),
		                                        'edit_data',
		                                        $this->arg_set_caller (array ('table' => $table, 'id' => $id, 'class' => $class,
		                                                                      'otable' => $table, 'oid' => $id)))) .
                   "]\n";
        } else {
            if (!isset ($cache[$id_class][0]))
	        continue;
            $obj = $cache[$id_class][0];
            $obj['_table'] == $table && $obj['_id'] == $id
                ? $found_local = true
	        : $found_local = false;

            # Skip local object not found local.
	    if ($obj['is_local'] && $found_local == false)
	        continue;

            # Check object's mime type
            $type = $obj['mime'];
            $type = substr ($type, 0, strpos ($type, '/'));

            # Force generation of link to create an object locally.
            if ($only_local && !$found_local && substr ($class, 0, 2) != 'u_') {
	        $obj = '';
	        continue;
	    }

            # Call object-specific editor.
	    if (isset ($cms_object_views[$class]))
	        $objviews[$class] = array ($table, $id);

            # Use different colors for class description when finding it local or
            # not.
            if ($found_local)
                $color = '#0000CC';
            else
                $color = '#009000';

            # Warn if label is public.
            if ($obj['is_public'] || $obj['is_local']) {
                  $stat = ' (';
	          if ($obj['is_public'])
	              $stat .= '<FONT COLOR="RED">' . $lang['public'] . '</FONT>';
                  if ($obj['is_public'] && $obj['is_local'])
	              $stat .= ', ';
	          if ($obj['is_local'])
	              $stat .= $lang['local'];
	          $stat .= ')';
              } else
	          $stat = '';

      	    switch ($type) {
	        # Print image box
      	        case 'image':
	            $imagename = $obj['filename'];
	            if ($imagename == '')
	                $imagename = $obj['mime'];
                    $images .= '<td><table border="1" cellpadding="2" cellspacing="0">' .
	                       '<tr><td align="center">' .
	                       '<a href="' .
	                       $this->link ('edit_data',
		                            $this->arg_set_caller (array ('table' => $table, 'id' => $id, 'class' => $class,
		                                                   'otable' => $table, 'oid' => $id))) .
                               '"><img border="0" src="' .
	                       $p->filelink ('obj_data', 'data', $obj['mime'], $obj['id'], $obj['data']) .
                               '" alt="' . $imagename . '"></a><br>' .
	                       '<FONT COLOR="' . $color . '">' . $descr . ', ' . $imagename . '</FONT>' . $stat .
	                       '</td></tr>' .
	                       '</table></td>' . "\n";
	            continue;

                default:
                    $tmp = '[' .
                           $p->_looselink ('<FONT COLOR="' . $color . '">' . $descr . '</FONT>' . $stat,
	                                   'edit_data',
	                                   $this->arg_set_caller (array ('table' => $table, 'id' => $id, 'class' => $class,
	                                                                 'otable' => $table, 'oid' => $id))) .
                           "]\n";
            }
        }

        # Sort in generated HTML code.
        switch (substr ($class, 0, 2)) {
            case 'l_':
	        $documents .= $tmp;
	        break;
	    case 'd_':
	        $configuration .= $tmp;
	        break;
	    default:
	        $user_defined .= $tmp;
        }
    }

    ### Print everything ###

    echo '<table width="100%" bgcolor="#eeeeee" border="0">' . "\n";

    # Print objects in fixed order.
    if ($images) {
        if (!$only_local)
            echo '<tr><td bgcolor="#dddddd"><b>' . $lang['images'] . ':</b></td></tr>'. "\n";
        echo '<tr><td align="center"><table border="0"><tr>' . $images . '</tr></table></td></tr>'. "\n";
    }
    if ($documents)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['documents'] . ':</b></td></tr><tr><td>' . $documents . "</td></tr>\n";
    if ($user_defined)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['user defined classes'] . ':</b></td></tr><tr><td>' . $user_defined . "</td></tr>\n";
    if ($configuration)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['configuration'] . ':</b></td></tr><tr><td>' . $configuration . "</td></tr>\n";
    echo '</table>';

    if (!isset ($objviews))
        return;

    echo '<table>';
    foreach ($objviews as $class => $ti) {
        list ($table, $id) = $ti;

        # Fetch the object we want to edit.
        $obj =& new DBOBJ ($this->db, $class, $dep, $table, $id, true);

        $cms_object_views[$class] ($this, $obj);
    }
    echo '</table>';
}
?>

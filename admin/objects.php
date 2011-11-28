<?
# Object editor.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function object_init (&$app)
{
    $app->add_function ('assoc_object');
    $app->add_function ('copy_object');
    $app->add_function ('remove_object');
    $app->add_function ('remove_object4real');
    $app->add_function ('edit_data');
}

# Associate object with a table.
# Creates an object and stores the object-id in the referenced table.
function assoc_object (&$app)
{
    global $lang;

    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');

    $obj = new DBOBJ ($app->db, $class, $app->db->def);
    $obj->active['mime'] = 'text/plain';
    $obj->assoc ($table, $id);

    $app->ui->msgbox (sprintf ($lang['msg obj assoced'], $class));
}
  
# Copy object to another directory.
function copy_object (&$app)
{
    global $lang;

    $hier =& $app->db->def;
    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');
    $srctable = $app->arg ('srctable');
    $srcid = $app->arg ('srcid');

    $obj = new DBOBJ ($app->db, $class, $hier, $table, $id);
    if ($obj->active['_table'] == $table && $obj->active['_id'] == $id) {
        $app->ui->msgbox ($lang['msg obj already exists'], "red");
        return;
    }

    # Fetch data of source object.
    $obj = new DBOBJ ($app->db, $class, $hier, $srctable, $srcid);
    $data['data'] = $obj->active['data'];
    $data['mime'] = $obj->active['mime'];
    $data['start'] = $obj->active['start'];
    $data['is_local'] = $obj->active['is_local'];
    $data['is_public'] = $obj->active['is_public'];

    # Create a new object and copy the source data to it.
    $obj = new DBOBJ ($app->db, $class, $hier);
    $obj->active = $data;

    # Associate object with source directory.
    $obj->assoc ($table, $id);
    $app->ui->msgbox (sprintf ($lang['msg obj assoced'], $table, $id));
}

function remove_object (&$app)
{
    global $lang;

    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');
    $otable = $app->subarg ('otable');
    $oid = $app->subarg ('oid');

    $q = ($table != $otable || $id != $oid) ? $lang['ask remove other object'] : $lang['ask remove object'];
    $arg = array ('class' => $class, 'table' => $table, 'id' => $id);
    $app->ui->confirm ($q, $lang['yes'], 'remove_object4real', $arg, $lang['no'], 'edit_data', $arg);
}
 
# Remove association of object.
# table/id = directory of object.
# class = object's class name.
function remove_object4real (&$app)
{
    global $lang;

    $db =& $app->db;
    $hierarchy =& $app->db->def;
    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');

    $obj = new DBOBJ ($db, $class, $hierarchy, $table, $id, true);
    $obj->remove ();

    $app->ui->msgbox ($lang['msg obj removed']);
    $app->call ('return2caller');
}  

# Navigator for edit_data and related views.
function edit_data_navigator (&$app)
{
    global $lang;

    $p =& $app->ui;
    $dep =& $app->db->def;
    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');
    $otable = $app->subarg ('otable');
    $oid = $app->subarg ('oid');

    $p->link ($lang['cmd defaultview'], 'defaultview', 0);
    show_directory_index ($app, $otable, $oid);
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
        $obj = new DBOBJ ($app->db, $class, $dep, $xtable, $xid);

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
            $e = new event ('copy_object', array ('class' => $class,
	  	                                  'table' => $otable,
	  	                                  'id' => $oid,
	  	                                  'srctable' => $obj->active['_table'],
	  	                                  'srcid' => $obj->active['_id']));
            $e->set_next ($app-event ());
	    $p->link ($lang['copy to current'], $e);
	    echo '</td>';
        }

        # Print the path to the directory.
        echo '<td>';
        $p->link ($app->db->traverse_refs_from ($app, $t, $i, 'nav_linkpath', 1, false), 'edit_data', array ('table' => $t, 'id' => $i, 'class' => $class));
        echo '</td></tr>';

        # Fetch parent directory's position and break if there is none.
        $xtable = $t;
        $xid = $i;
        dbitree_get_parent ($app->db, $xtable, $xid);
        if (!$xid)
            break;
        echo '<BR>';
    }
    echo '</table>';
}

# Edit data, mime type and other flags of an object.
# caller/table/id = directory of object.
# class = Class of object.
function edit_data (&$app)
{
    global $lang, $cms_object_editors;

    $p =& $app->ui;
    $db = $app->db;
    $dep = $db->def;
    $otable = $app->subarg ('otable');
    $oid = $app->subarg ('oid');
    $class = $app->subarg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');
    $remove_args = compact ('class', 'table', 'id');

    $p->headline ($lang['title edit_data']);

    # Output standard navigator with copy options.
    edit_data_navigator ($app);

    # Fetch the object we want to edit.
    $obj = new DBOBJ ($db, $class, $dep, $table, $id, true);

    # Use external object editor if specified.
    if (isset ($cms_object_editors[$class]))
        return $cms_object_editors[$class] ($app, $obj, $class);
 
    # Fetch class description.
    $res = $db->select ('descr', 'obj_classes', "name='$class'");
    list ($classdesc) = $res->get ();

    # Open view on obj_data.
    $p->open_source ('obj_data');
    $p->query (sql_assignment ('id', $obj->active['id']));
    $p->get ();

    # Show class name and example tag.
    $p->table_headers (array ("<B><FONT SIZE=\"+1\">$classdesc</FONT></B>"));

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

    $p->cmd_update ();
    $p->close_row ();

    $p->paragraph ();

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $e = new event ('remove_object', $remove_args);
    $e->set_next ($app->event ());
    $p->link ($lang['remove'], $e);
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
    $p->cmd_update ();
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

function _object_box_toggler_for_inherited_objects (&$app, $only_local)
{
    global $lang;

    if ($only_local)
        return;

    $p =& $app->ui;

    $oargs = $app->args ();
    $label = $oargs['display_inherited_objects'] ?
             '<B>' . $lang['cmd objectbox hide'] . ':</B>' :
             '<B>' . $lang['cmd objectbox unhide'] . '</B>';
    $oargs['display_inherited_objects'] ^= true;
    $p->link ($label, $app->args['__view'], $oargs);

    # Describe label colors for inherited/local objects.
    echo ' <FONT COLOR="#0000CC">' . $lang['local'] . '</FONT> ' .
         '<FONT COLOR="#008800">' . $lang['inherited'] . '</FONT> ' .
         '<FONT COLOR="#666666">' . $lang['undefined'] . '</FONT>';
}

# Show inherited and/or local objects.
# $table/$id specify the current directory.
# If $only_local is true only local objects are shown.
function _object_box (&$app, $table, $id, $caller, $only_local = false)
{
    global $lang, $cms_object_views;

    $p =& $app->ui;
    $db =& $app->db;
    $dep =& $app->db->def;
    $common_args = array ('class' => $class,
                          'table' => $table, 'id' => $id,
                          'otable' => $table, 'oid' => $id);
    $e_edit_data = new event ('edit_data', $common_args);
    $e_edit_data->set_caller ($app->event);

    # Save starting point so the paths can be displayed correctly by edit_data().
    $caller['otable'] = $table;
    $caller['oid'] = $id;

    _object_box_toggler_for_inherited_objects ($app, $only_local);

    $cache = dbtree_get_objects_in_path ($db, $table, $id);
    $documents = $enumerations = $configuration = $user_defined = $images = '';

    # For each class, search for an object.
    $res = $db->select ('id,name,descr', 'obj_classes', '', ' ORDER BY descr ASC');
    while ($res && list ($id_class, $class, $descr) = $res->get ()) {
        $descr = ereg_replace (' ', '&nbsp;', $descr);
        $tmp = '';

        ### Simulate DBOBJ by reading objects from the cache.
        # Check if there's any object of the current class.

        # Link to create object if none found.
        if (!isset ($cache[$id_class][0]) && ((!$only_local) || ($only_local && substr ($class, 0, 2) == 'u_'))) {
            $e = new event ('assoc_object', $common_args);
            $e->set_next ($e_edit_data);
            $tmp = '[' . $p->_looselink ("<FONT COLOR=\"BLACK\">$descr</FONT>" , $e) . "]\n";
        } else {
            if (!isset ($cache[$id_class][0]))
	        continue;
            $obj = $cache[$id_class][0];
            $found_local = ($obj['_table'] == $table && $obj['_id'] == $id);

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
            $color = $found_local ? '#0000CC' : '#009000';

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
	                       $app->link ($e_edit_data);
                               '"><img border="0" src="' .
	                       $p->filelink ('obj_data', 'data', $obj['mime'], $obj['id'], $obj['data']) .
                               "\" alt=\"$imagename\"></a><br>" .
	                       '<FONT COLOR="' . $color . '">' . $descr . ", $imagename</FONT>$stat" .
	                       '</td></tr>' .
	                       '</table></td>' . "\n";
	            continue;

                default:
                    $tmp = '[' .  $p->_looselink ("<FONT COLOR=\"$color\">$descr</FONT>$stat", $e_edit_data) .  "]\n";
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
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['documents'] . ":</b></td></tr><tr><td>$documents</td></tr>\n";
    if ($user_defined)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['user defined classes'] . ":</b></td></tr><tr><td>$user_defined</td></tr>\n";
    if ($configuration)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang['configuration'] . ":</b></td></tr><tr><td>$configuration</td></tr>\n";
    echo '</table>';

    if (!isset ($objviews))
        return;

    echo '<table>';
    foreach ($objviews as $class => $ti) {
        list ($table, $id) = $ti;

        # Fetch the object we want to edit.
        $obj = new DBOBJ ($app->db, $class, $dep, $table, $id, true);

        $cms_object_views[$class] ($app, $obj);
    }
    echo '</table>';
}
?>

<?php

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
    $obj->data['mime'] = 'text/plain';
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
    if ($obj->data['_table'] == $table && $obj->data['_id'] == $id) {
        $app->ui->msgbox ($lang['msg obj already exists'], "red");
        return;
    }

    # Fetch data of source object.
    $obj = new DBOBJ ($app->db, $class, $hier, $srctable, $srcid);
    $data['data'] = $obj->data['data'];
    $data['mime'] = $obj->data['mime'];
    $data['start'] = $obj->data['start'];
    $data['is_local'] = $obj->data['is_local'];
    $data['is_public'] = $obj->data['is_public'];

    # Create a new object and copy the source data to it.
    $obj = new DBOBJ ($app->db, $class, $hier);
    $obj->data = $data;

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
    $otable = $app->arg ('otable');
    $oid = $app->arg ('oid');

    $p->link ($lang['cmd defaultview'], 'defaultview');
    show_directory_index ($app, $otable, $oid, true);
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
        if (!isset ($obj->data))
            break;

        # Get source directory of the found object.
        $t = $obj->data['_table'];
        $i = $obj->data['_id'];

        # Mark the path if the object is currently displayed.
        if ($t == $table && $i == $id)
	    echo '<td><b>' . $lang['current'] . ' --></b></td><td>-</td>';
        else {
            echo '<td>&nbsp;</td><td>';
            $e = new event ('copy_object', array ('class' => $class,
	  	                                  'table' => $otable,
	  	                                  'id' => $oid,
	  	                                  'srctable' => $obj->data['_table'],
	  	                                  'srcid' => $obj->data['_id']));
            $e->set_next ($app-event ());
	    $p->link ($lang['copy to current'], $e);
	    echo '</td>';
        }

        # Print the path to the directory.
        echo '<td>';
        $p->link ($app->db->traverse_refs_from ($app, $t, $i, 'nav_linkpath', false, false),
                  new event ('edit_data', array ('table' => $t, 'id' => $i, 'class' => $class,
                                                 'otable' => $otable, 'oid' => $oid)));
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
    $otable = $app->arg ('otable');
    $oid = $app->arg ('oid');
    $class = $app->arg ('class');
    $table = $app->arg ('table');
    $id = $app->arg ('id');
    $remove_args = compact ('class', 'table', 'id');

    $p->headline ($lang['title edit_data']);

    edit_data_navigator ($app);

    $obj = new DBOBJ ($db, $class, $dep, $table, $id, true);

    if (isset ($cms_object_editors[$class]))
        return $cms_object_editors[$class] ($app, $obj, $class);
 
    $res = $db->select ('descr', 'obj_classes', "name='$class'");
    list ($classdesc) = $res->get ();

    $p->open_source ('obj_data');
    $p->query (sql_assignment ('id', $obj->data['id']));
    $p->get ();

    $p->table_headers (array ("<B><FONT SIZE=\"+1\">$classdesc</FONT></B>"));

    $p->paragraph ();

    $p->open_row ();
    $p->inputline ('mime', 16, $lang['mime type']);
    $p->inputline ('start', 10, $lang['start time']);
    $p->inputline ('end', 10, $lang['end time']);

    $p->open_cell ();
    $p->label ($lang['local'] . ":");
    $p->radiobox ('is_local', $lang['yes'], $lang['no'], $lang['local'] . '<BR>');
    $p->close_cell ();

    $p->open_cell ();
    $p->label ($lang['public'] . ":");
    $p->radiobox ('is_public', $lang['yes'], $lang['no'], $lang['public'] . '<BR>');
    $p->close_cell ();

    $p->cmd_update ();
    $p->close_row ();

    $p->paragraph ();

    $p->open_row ();
    $e = new event ('remove_object', $remove_args);
    $e->set_next ($app->event ());
    $p->link ($lang['remove'], $e);

    if ($obj->data['_table'] == $otable && $obj->data['_id'] == $oid)
        $p->label ($lang['local']);
    else
        $p->label ($lang['inherited']);

    if (isset ($obj->data['data']))
        $p->label (strlen ($obj->data['data']) . ' bytes');
    else
        $p->label ($lang['empty']);
    $p->label (_class2tag ($class));
    $p->close_row ();

    $p->paragraph ('<hr>');

    $p->open_row ();
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
	    if ($lines > 50)
                $lines = 50;
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

    $p->open_row ();
    $p->fileform ('data', $lang['upload'], 'mime', 'filename');
    $p->cmd_update ();
    $p->close_row ();

    if ($mime == 'text') {
        $p->paragraph ('<hr>');
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

    $do_show =  $app->arg ('display_inherited_objects', ARG_OPTIONAL);
    $label = $do_show ?
             '<B>' . $lang['cmd objectbox hide'] . ':</B>' :
             '<B>' . $lang['cmd objectbox unhide'] . '</B>';
    $args = $app->args ();
    $args['display_inherited_objects'] = $do_show ^ true;
    $p->link ($label, new event ($app->event ()->name, $args));

    return $do_show;
}

function mime_type ($x)
{
    return substr ($x, 0, strpos ($x, '/'));
}

function _show_existing_object_class (&$images, &$cache, &$app, $table, $id, $only_local, $id_class, $class, $descr, $e_edit_data)
{
    $p =& $app->ui;

    $tmp = '';
    if (!isset ($cache[$id_class][0]))
        return '';
    $obj = $cache[$id_class][0];
    $found_local = ($obj['_table'] == $table && $obj['_id'] == $id);

    if ($obj['is_local'] && $found_local == false)
        return '';

    if ($only_local && !$found_local && substr ($class, 0, 2) != 'u_')
        return '';

    if (isset ($cms_object_views[$class]))
        $objviews[$class] = array ($table, $id);

    $color = $found_local ? '#0000CC' : '#009000';

    $stat = '';
    if ($obj['is_public'] || $obj['is_local']) {
        $stat = ' (';
        if ($obj['is_public'])
            $stat .= '<FONT COLOR="RED">' . $lang['public'] . '</FONT>';
        if ($obj['is_public'] && $obj['is_local'])
            $stat .= ', ';
        if ($obj['is_local'])
            $stat .= $lang['local'];
        $stat .= ')';
    }

    if (mime_type ($obj['mime']) == 'image') {
        $imagename = $obj['filename'];
        if ($imagename == '')
            $imagename = $obj['mime'];
        $images .= '<td><table border="1" cellpadding="2" cellspacing="0">' .
                   '<tr><td align="center">' .
                   '<a href="' .
                   $app->url ($e_edit_data);
                   '"><img border="0" src="' .
                   $p->filelink ('obj_data', 'data', $obj['mime'], $obj['id'], $obj['data']) .
                   "\" alt=\"$imagename\"></a><br>" .
                   '<FONT COLOR="' . $color . '">' . $descr . ", $imagename</FONT>$stat" .
                   '</td></tr>' .
                   '</table></td>' . "\n";
        return '';
    }

    return '[' . $p->_looselink ("<FONT COLOR=\"$color\">$descr</FONT>$stat", $e_edit_data) . "]\n";
}

function _show_object_class (&$documents, &$images, &$user_defined, &$configuration, &$cache, &$app, $table, $id, $only_local, $res)
{
    $p =& $app->ui;
    list ($id_class, $class, $descr) = $res->get ();
    $common_args = array ('class' => $class, 'table' => $table, 'id' => $id, 'otable' => $table, 'oid' => $id);
    $e_edit_data = new event ('edit_data', $common_args);
    $e_edit_data->set_caller ($app->event ());

    $descr = preg_replace ('/ /', '&nbsp;', $descr);

    if (!isset ($cache[$id_class][0]) && ((!$only_local) || ($only_local && substr ($class, 0, 2) == 'u_'))) {
        $e = new event ('assoc_object', $common_args);
        $e->set_next ($e_edit_data);
        $tmp = '[' . $p->_looselink ("<FONT COLOR=\"BLACK\">$descr</FONT>" , $e) . "]\n";
    } else
        $tmp = _show_existing_object_class ($images, $cache, $app, $table, $id, $only_local, $id_class, $class, $descr, $e_edit_data);

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

function show_directory_object_section ($section, $html)
{
    global $lang;

    if ($html)
        echo '<tr><td bgcolor="#dddddd"><b>' . $lang[$section] . ":</b></td></tr><tr><td>$html</td></tr>\n";
}

# Show inherited and/or local objects.
# $table/$id specify the current directory.
# If $only_local is true only local objects are shown.
function show_directory_objects (&$app, $table, $id, $caller, $only_local = false)
{
    global $lang, $cms_object_views;

    $p =& $app->ui;
    $db =& $app->db;
    $dep =& $app->db->def;

    $caller['otable'] = $table;
    $caller['oid'] = $id;

    if (!_object_box_toggler_for_inherited_objects ($app, $only_local))
        return;

    echo ' <FONT COLOR="#0000CC">' . $lang['local'] . '</FONT> ' .
         '<FONT COLOR="#008800">' . $lang['inherited'] . '</FONT> ' .
         '<FONT COLOR="#666666">' . $lang['undefined'] . '</FONT>';

    $cache = dbtree_get_objects_in_path ($db, $table, $id);
    $documents = $enumerations = $configuration = $user_defined = $images = '';

    $res = $db->select ('id,name,descr', 'obj_classes', '', ' ORDER BY descr ASC');
    while ($res && list ($id_class, $class, $descr) = $res->get ()) 
        _show_object_class ($documents, $images, $user_defined, $configuration, $cache, $app, $table, $id, $only_local, $res);

    echo '<table width="100%" bgcolor="#eeeeee" border="0">' . "\n";
    if ($images) {
        if (!$only_local)
            echo '<tr><td bgcolor="#dddddd"><b>' . $lang['images'] . ':</b></td></tr>'. "\n";
        echo '<tr><td align="center"><table border="0"><tr>' . $images . '</tr></table></td></tr>'. "\n";
    }
    show_directory_object_section ('documents', $documents);
    show_directory_object_section ('user defined classes', $user_defined);
    show_directory_object_section ('configuration', $configuration);
    echo '</table>';

    if (!isset ($objviews))
        return;

    echo '<table>';
    foreach ($objviews as $class => $ti) {
        list ($table, $id) = $ti;
        $obj = new DBOBJ ($app->db, $class, $dep, $table, $id, true);
        $cms_object_views[$class] ($app, $obj);
    }
    echo '</table>';
}

?>

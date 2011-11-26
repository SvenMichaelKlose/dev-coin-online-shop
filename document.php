<?
# Document lookup.
#
# Copyright(c) 2000-2001 dev/consulting GmbH
# Copyright(c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# document_proces() analyses the URL and invokes the scanner the first time.
# It is called from index.php.


# Contains tail of path that wasn't analysed. This is used to pass extra arguments.
unset ($path_tail);

# Hold intital document template.
unset ($document_template);

function document_set_template (&$template)
{
    $GLOBALS['document_template'] = $template;
}

# Converts special characters in a string to others that can be used
# in an URL.
function document_readable_url ($url)
{
    $url =& ereg_replace ('&auml;', 'ae', urldecode ($url));
    $url =& ereg_replace ('&ouml;', 'oe', $url);
    $url =& ereg_replace ('&uuml;', 'ue', $url);
    $url =& ereg_replace ('&Auml;', 'Ae', $url);
    $url =& ereg_replace ('&Ouml;', 'Oe', $url);
    $url =& ereg_replace ('&Uuml;', 'Ue', $url);
    $url =& ereg_replace ('&amp;', '', $url);
    $url =& ereg_replace ('&', '', $url);
    $url =& ereg_replace (',', '', $url);
    $url =& ereg_replace ('\(', '', $url);
    $url =& ereg_replace ('\)', '', $url);
    $url =& ereg_replace ('/', '_2f', $url);
    $url =& ereg_replace (' ', '_', $url);
    $url =& ereg_replace ('__', '_', $url);
    return urlencode ($url);
}

# Converts a path into a table/primary key pair that specifies the
# directory as well as the name of a virtual directory if specified, the
# path elements that couldn't be processed and the last path element
# that was taken into consideration for something.
# Paths are case insensitive.
#
# $table/$id: Directory where to start.
function document_path_to_directory ($path, $table, $id)
{
    global $scanner, $vdir_name, $dep, $db;

    # Walk through path.
    $dirtype = $scanner->dirtypes[$table];
    $vdir = false;
    reset ($path);
    for ($name = next ($path); $name; $name = next ($path)) {
        # Break for object link.
        if (strtoupper ($name) == 'OBJ')
	    break;

        # Break on virtual directory.
        if (isset ($vdir_name[$name])) {
	    $dirtype = $vdir_name[$name];
	    $vdir = true;
	    break;
        }

        # Check for name in referencing tables.
        $desc = $dep->table[$table]; # Array of all directory names.
        foreach ($desc as $ctab) {
	    # Read names into hash.
	    unset ($names);
            # Get all childs of $ctab/$id.
	    for ($res =& dbitree_get_childs ($db, $ctab, $id);
	         $row =& $res->fetch_array (), isset ($row['name']);
	         $names[strtolower (document_readable_url ($row['name']))] = $row);

	    # If subdirectory with $name exists, save the table name, id
	    # and directory type.
	    $rname = strtolower (document_readable_url ($name));
	    if (isset ($names[$rname]) && $row = $names[$rname]) {
	        $table = $ctab;
	        if ($row['id'])
	            $id = $row['id'];
	        $dirtype = $scanner->dirtypes[$table];
	        $name = '';
	        break;
	    }
        }
        if ($name)
	    $lastname = $name;
    }

    # Add last directory and followers to tail.
    $tail = array ();
    if (isset ($lastname))
        $tail[] = $lastname;
    while (($tmp = next ($path)) != '')
        $tail[] = $tmp;

    return array ($dirtype, $table, $id, $vdir, $tail, $name);
}

# Set up the document, parse and evaluate it.
function document_process ($root_table, $root_id, $root_template)
{
    global $scanner, $dep, $db, $path_tail, $current_index, $current_indices, $PATH_INFO, $default_document, $document_template, $debug, $list_offsets, $url_vars;

    list ($dirtype, $table, $id, $vdir, $path_tail, $name) = document_path_to_directory (explode ('/', $PATH_INFO), $root_table, $root_id);

    # Check if the first unprocessed directory of the tail is a known
    # object class and use it as the template. If it's not, a default template
    # is used.
    $template = '';
    if (sizeof ($path_tail) > 0) {
        $res =& $db->select ('id', 'obj_classes', 'name=\'' . $path_tail[0] . '\'');
        if ($res->num_rows () > 0)
            $template = $path_tail[0]; # Tail is a legal user template.
    }
    if (!$template)
        if ($table == $root_table && $id == $root_id && !$vdir)
	    $template = $root_template;	# Show index page.
        else
	    $template = $default_document[$dirtype]; # Use default template.

    # Fetch document template for this directory.
    # Don't use cms_fetch_object() here because there's no cursor.
    $dbobj = new DBOBJ ($db, $template, $dep, $table, $id, true);

    # Export public object as file.
    # This is done right here to skip all other activities and exit.
    if ($name == 'OBJ') {
        if (!is_array ($dbobj->active) || !$dbobj->active['id'])
	    die ("'$template' is not an object class.");
        if (!$dbobj->active['is_public']) {
	    exit; #panic ('Object is not marked public!');
	    exit;
        }
        Header ('Content-type: ' . $dbobj['mime']);
        echo $dbobj->active['data']; # No run through scanner.
        exit;
    }

    if (!isset ($dbobj->active['data']))
        die ('Kein Dokument f&uuml;r die Eingansseite definiert - stop.');
    $document_template = $dbobj->active['data'];

    # Get index info.
    $tmp = $table;
    $rid = $id;
    dbitree_get_parent ($db, $tmp, $rid);
    if (!$tmp)
        die ('No such path.');

    # Fetch result into array.
    for ($res =& dbitree_get_childs ($db, $table, $rid);
	 $tmp =& $res->fetch_array ();
	 $set[$tmp['id_last']] = $tmp['id']);

    # Sort indices into $current_indices array and find the current one.
    for ($i = 1, $last = 0; isset ($set[$last]) && ($rid = $current_indices[$i] = $set[$last]); $last = $rid, $i++)
        if ($rid == $id)
	    $current_index = $i;

    # Feed list offsets into URL vars.
    if (isset ($list_offsets))
        foreach ($list_offsets as $key => $val)
            $url_vars["list_offsets[$key]"] = $val;

    # Open a new context.
    $scanner->push_context (); # XXX is this really required?
    cms_create_context ($dirtype, $table, $id);

    # Call document handler if the current directory is virtual.
    if ($vdir) {
        # Explicitly create context for virtual directory.

        # Call document handler.
        # TODO: Document handlers for any directory type.
        $func = 'document_' . strtolower ($dirtype);
        $func ();
    }

    if ($debug)
        echo "dirtype: $dirtype - tab: $table - id: $id - default template: $template<br>";

    # Invoke scanner and evaluate the page.
    # TODO: This could depend on the document template's mime type.
    # see also the scanner in lib/scanner.class.
    $document_tree = $scanner->scan ($document_template);
    $out =& $scanner->exec ($document_tree, $table, $id);
    eval ("?>$out<?");
}
?>

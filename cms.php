<?
# Generic document generation
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# This file contains the global definitions for tags that can be applied
# to all types of directories. They define the tag handling functions that
# follow the global definitions.
#
# NOTE: The tag functions are invoked by the scanner. See document.php
# to see how the scanner is invoked.
#
# Two sorts of directories can be handled by the CMS: Real directories
# represented as rows in database tables and virtual directories that can
# be childs of any real directory but can contain no objects.
# There can also be functions that can only be used with particular
# directory types.


# Register default cms tags.
$scanner->tags ('FIELD HAS-OBJECT OBJECT TEMPLATE ' . # data
                'LIST ' . # enumerations
                'LINK OBJECTLINK FIRST PREV NEXT LAST ' . # links
                'INDEX THIS-INDEX PREV-INDEX NEXT-INDEX FIRST-INDEX LAST-INDEX ' . # indexes
                'TYPE NUM-SUBDIRS NUM-TYPE ' . # structural
                'SELECTED '); # conditional

$current_index = -1;
$current_indices = '';
$url_vars = array ();

# Helpers for grouping of lists.
$list_sizes = array ();	# Last list sizes.
#$list_offsets = array (); # Last starting offsets. DO NOT UNCOMMENT!


#######################
### Database access ###
#######################

# Fetch object starting at current context or position passed in
# $l_table/$l_id. $fields limits the fields fetched from an object.
# This is useful if you need just a mime type and not a whole file.
function &cms_fetch_object ($class, $l_table = '', $l_id = '', $fields = '*')
{
    global $dep, $db, $scanner;

    $l_table ? $t = $l_table : $t = $scanner->context_table;
    $l_id ? $i = $l_id : $i = $scanner->context['id'];
    if (!$t || !$i) {
        $t = $scanner->parent_context_table;
        $i = $scanner->parent_context['id'];
    }
    $obj =& new DBOBJ ($db, $class, $dep, $t, $i, true, $fields);

    # Return nothing if object wasn't found.
    if (isset ($obj->active) && is_array ($obj->active))
        return $obj->active['data'];
}

# Fetch array at position specified by $table/$id and return it as array
# of columns. Caching is used here.
function &cms_fetch_directory ($table, $id)
{ 
    global $db, $_CMS_CACHE_DIRS;

    # Return array from cache if it's there.
    if (isset ($_CMS_CACHE_DIRS[$table][$id]))
        return $_CMS_CACHE_DIRS[$table][$id];

    # Select whole directory record.
    $res =& $db->select ('*', $table, "id=$id");

    # Read record into cache and return it.
    if ($res->num_rows () > 0)
        return $_CMS_CACHE_DIRS[$table][$id] = $res->fetch_array ();
  }


#####################
### Link creation ###
#####################

# Create link to document at table/id pair. Virtual directories are
# appended in tag_link() if needed.
function cms_make_link ($t, $i)
{
    global $scanner, $dep, $db, $cms_root_table, $cms_root_id;

    # Traverse from current position to root directory, prepending directory
    # names to the path.
    $url = '';
    do {
        # Do nothing when in root directory.
        if ($t == $cms_root_table && $i == $cms_root_id)
            break;

        $row =& cms_fetch_directory ($t, $i);
        if (!$row)
	    break;
        if ($url)
            $url = document_readable_url ($row['name']) . "/$url";
        else
            $url = document_readable_url ($row['name']);
        dbitree_get_parent ($db, $t, $i);
    } while ($t && $i);

    # Prepend script name to path an return it.
    return $GLOBALS['SCRIPT_NAME'] . "/$url";
}

# Add current set of URL variables to URL.
function cms_variable_url ($url)
{
    $tail = cms_variable_tail ();
    return $tail ? "$url?$tail" : $url;
}

# Create URL tail for variable set in $url_vars.
function cms_variable_tail ()
{
    global $session, $url_vars, $use_cookies, $SESSION_KEY;

    # Always be sure to include the session key if cookies aren't used.
    if ($session->key () && $SESSION_KEY)
        $url_vars['SESSION_KEY'] = $session->key ();

    # Just return the url of there're no URL variables.
    if (sizeof ($url_vars) == 0)
        return;

    # Assemble tail.
    $url = '';
    foreach ($url_vars as $k => $v)
        $url .= ($url ? '&' : '') . "$k=" . urlencode ($v);

    return $url;
}


########################
### Context creation ###
########################

# Create context for a real node.
# A context is specified by:
#
#    - current real directory type
#    - real directory's primary key
#    - optional virtual directory type
#
# Virtual directories are subdirectories of the specified directory.
#
# If a table/id pair is specified the current scanner context is
# replaced by the specified directory.
#
# NOTE: This function is called by parse() in dev/con php base
# lib/scanner.class.
function cms_create_context ($dirtype, $table = '', $id = 0)
{
    global $db, $dep, $scanner, $cms_root_table, $cms_root_id;

    # If $table/$id is specified reload the context.
    if ($table && $id) {
        $scanner->context = cms_fetch_directory ($table, $id);
        $scanner->context_table = $table;
    }

    if (!$dirtype)
        return;

    $table = $scanner->parent_context_table;
    $id = $scanner->parent_context['id'];

    $scanner->dirtype = $dirtype;

    # If directory is virtual, we already have the parent context
    if (isset ($GLOBALS['vdir_alias'][$dirtype])) 
        return;

    if ($table && $id && isset ($scanner->tables[$dirtype]) && $scanner->tables[$dirtype] == $table)
        return;

    # Context for root directory.
    if (strtoupper ($dirtype) == 'ROOT') {
        $scanner->context = cms_fetch_directory ($cms_root_table, $cms_root_id);
        $scanner->context_table = $cms_root_table;
        $scanner->dirtype = $scanner->dirtypes[$cms_root_table];
        return;
    }

    # Get parent directory or real if virtual.
    if (strtoupper ($dirtype) == 'PARENT') {
        dbitree_get_parent ($db, $table, $id);
        if (!($row =& cms_fetch_directory ($table, $id)) || !$row['id']) {
	    # There's no parent. Clear context so tag won't get executed.
	    $scanner->context = $scanner->context_table = 0;
	    return;
        }
        $scanner->dirtype = $scanner->dirtypes[$table];
        $scanner->context_table = $table;
        $scanner->context = $row;
        return;
    }

    # Type is a real directory - get the table name.
    if (!isset ($scanner->tables[$dirtype])) {
        echo "Undefined source keyword '$dirtype'.";
        return;
    }

    # We're going to search for a parent directory of the specified type.
    # Convert $dirtype to the table name we're looking for.
    $scanner->dirtype = $dirtype;
    $dirtype = $scanner->tables[$dirtype];
    $table = $scanner->context_table;
    $id = $scanner->context['id'];

    # Search through the path until we find a directory of the given type.
    while ($id) {
        $row =& cms_fetch_directory ($table, $id);
        if ($table == $dirtype) {
            $scanner->dirtype = $scanner->dirtypes[$table];
            $scanner->context_table = $table;
            $scanner->context = $row;
            return;
        }
        dbitree_get_parent ($db, $table, $id);
    }
    $scanner->context_table = $dirtype;

    # Panic if there's no parent context.
    if (!isset ($scanner->context['id']))
        die ('No originating context specified. Use create_context () with ' .
	     'directory location first. Please contact the developers about ' .
	     'this bug.');
}


################
### Sessions ###
################

$scanner->dirtag ('SESSION', 	'KEY');

$session =& new DBSESSION ($db);
if (isset ($SESSION_KEY))
    $session->read_id ($SESSION_KEY);

function dirtag_session_key ($dirtype, $arg)
{
    return cms_variable_tail ();
}


###################
### LIST output ###
###################

# Returns index information key for current directory.
function cms_listsource ()
{
    global $scanner;

    return $scanner->dirtype . $scanner->context_table . $scanner->context['id'];
}

# Used by <!:LIST!> tags.
# Fetch subdirectories and create a array set for cms_process_list(),
# This function also creates index numbers for the records we need to split
# up lists..
# TODO: Index code doesn't belong here. Use linked list support instead.
function &parse_result_set (&$template, $size = 0)
{
    global $current_index, $current_results, $current_indices, $scanner, $dep, $db;

    $table = $scanner->parent_context_table;
    $id = $scanner->parent_context['id'];

    $res =& dbitree_get_childs ($db, $table, $id, $scanner->context_table);
    if (!$res || $res->num_rows () < 1)
        return '<!-- Nothing to list. -->';

    # Read IDs for index2link ()
    $old_indices = $current_indices;
    unset ($current_indices);
    while ($tmp =& $res->fetch_array ())
        $set[$tmp['id_last']] = $tmp;

    # Sort indexes into $current_indices and $list.
    for ($i = 1, $last = 0;
	 isset ($set[$last]) ? $tmp = $set[$last] : 0;
         $last = $current_indices[$i] = $tmp['id'], $list[$i - 1] = $tmp, $i++);

    $out = cms_process_list ($list, $template, '', $size);

    $current_indices = $old_indices;

    return $out;
}

# Parse full or partial record set.
# TODO: Support multiple directory types if directory tables are merged.
#
# $records	= Array of records to list.
# $template	= Document tree of template.
# $size	= Maximum list size.
function cms_process_list (&$records, &$template, $table = '', $size = 0)
{
    global $db, $dep, $scanner, $list_sizes, $list_offsets, $current_index;
    $old_index = $current_index;

    if (!$table)
        $table = $scanner->context_table;
    $out = '';

    # Save the list size.
    $listsource = cms_listsource ();
    $list_sizes[$scanner->dirtype] = $size; # XXX - Why do we save the size?

    # Get or create current_index of list.
    if (!isset ($list_offsets[$listsource]))
        $list_offsets[$listsource] = 1;
    $current_index = $list_offsets[$listsource];

    # Get ending record by start and size.
    if ($size != 0) {
        $end = (($current_index + $size) > sizeof ($records)) ?
	       sizeof ($records) :
	       $current_index + (int) $size - 1;
    } else
        $end = sizeof ($records);

    while (isset ($records[$current_index - 1]) && $current_index <= $end ? $row = $records[$current_index - 1] : 0) {
        # Execute child branch in product's context.
        $out .= $scanner->exec ($template, $table, $row['id'], $row);

        $current_index++;
    }  

    $current_index = $old_index;

    return $out;
}


#######################
### Global CMS tags ###
#######################

# NOTE: Tag functions are called by scan() in lib/scanner.class.

# Return URL to document of current enumeration item.
fuNction tag_link ($attr, $use_key = true)
{
    global $scanner, $list_offsets, $url_vars;

    @$relative_index = $attr['relative_index'];
    @$arg = $attr['template'];
    @$key = strtolower ($attr['key']);

    $table = $scanner->context_table;
    $id = $scanner->context['id'];

    $url = cms_make_link ($table, $id);

    # Cut off trailing slash if any.
    if (substr ($url, $tmp = strlen ($url) - 1) == '/')
        $url = substr ($url, 0, $tmp);

    # Add argument, maybe a virtual directory's name to the URL,
    # XXX if there's no argument add 'index.html' ...??!?
    $url .= '/' . ($arg ? $arg : 'index.html');

    # Return bare URL without variable tail.
    if (!$use_key || $key == 'no')
        return $url;

    $oldvars = $url_vars;

    # If we're in a list context, create offset variables in the URL.
    $listsource = cms_listsource ();
    if ($relative_index)
        $url_vars["list_offsets[$listsource]"] = isset ($list_offsets[$listsource]) ? $list_offsets[$listsource] + $relative_index : 1 + $relative_index;

    $url = cms_variable_url ($url);

    # Restore former URL variable set.
    $url_vars = $oldvars;

    return $url;
}

function tag_field ($attr)
{
    global $scanner;

    $arg = $attr['name'];
    if (!isset ($scanner->context[$arg]))
        return '';
    return $scanner->context[$arg];
}

function tag_object ($arg)
{
    return cms_fetch_object ($arg['class']);
}

function tag_has_object ($attr)
{
    return cms_fetch_object ($attr['class']) ? '1' : '0';
}

function tag_template ($attr)
{
    global $scanner;

    $class = $attr['class'];
    $template =& cms_fetch_object ($class);
    $tree =& $scanner->scan ($template);
    return $scanner->exec ($tree);
}

# Return URL to object.
function tag_objectlink ($attr)
{
    global $dep, $db, $scanner;

    $arg = $attr['class'];
    if (!$arg)
        return '<!-- No object class specified. -->';

    # Create file name from mime type.
    $table = $scanner->context_table;
    $id = $scanner->context['id'];
    $dbobj =& new DBOBJ ($db, $arg, $dep, $table, $id, true, 'mime');
    $filename = isset ($dbobj->active) ? ereg_replace ('/', '.', $dbobj->active['mime']) : '';
    $arg .= '/' . strtolower ($filename);

    # Prevent use of session key or other url variables for images.
    $use_key = !(isset ($dbobj->active) && strhead ($dbobj->active['mime'] , 'image/'));

    # OBJ is an internal virtual directory for object downloads.
    return tag_link (array ('template' => "OBJ/$arg"), $use_key);
}

# Create link from index.
function &index2link ($index, $vdir_template = '')
{
    global $SCRIPT_NAME, $current_indices, $scanner;

    if ($index != 0)
        $index = $current_indices[$index];
    return sid (cms_make_link ($scanner->context_table, $index));
}

# Return current result index.
function tag_index ($arg)
{
    global $scanner, $list_offsets;

    # Return index of list context.
    $listsource = cms_listsource ();
    return isset ($list_offsets[$listsource]) ? $list_offsets[$listsource] : 1;
}

# Return index for form result in set or 0.
function tag_prev_index ($arg)
{
    return $GLOBALS['current_index'] - 1;
}

function tag_this_index ($arg)
{
    return $GLOBALS['current_index'];
}

# Return size of last result set.
function tag_last_index ($arg)
{
    return sizeof ($GLOBALS['current_indices']);
}

# Return index of next result or 0.
function tag_next_index ($arg)
{
    global $current_index;

    return ($current_index == tag_last_index ($arg)) ? 0 : $current_index + 1;
}

function tag_first ($arg)
{
    return index2link (1, $arg);
}

function tag_last ($arg)
{
    return index2link (tag_last_index (''), $arg);
}

function tag_prev ($arg)
{
    return index2link (tag_prev_index (''), $arg);
}

function tag_next ($arg)
{
    return index2link (tag_next_index (''), $arg);
}

function &tag_list ($attr)
{
    global $scanner, $default_enumeration;

    @$size = $attr['size'];
    $template =& $attr['_'];
    return parse_result_set ($template, $size);
}

# Return current directory type.
function tag_type ($arg)
{
    # TODO: New numeric directory types.
    return $scanner->dirtypes[$scanner->context_table];
}

# Return number of subdirectory of type $arg.
# This does quite a lot queries. Maybe we should prewalk the tree.
function tag_num_type ($attr)
{
    global $scanner, $dep, $db;

    $table = $scanner->context_table;
    $id = $scanner->context['id'];
    $arg = strtoupper ($attr['type']);

    if (!($desttab = $scanner->tables[$arg]))
        return "<!-- cms: No directories of type '$arg'. -->";

    # TODO: Do a proper iteration.
    if ($table == 'categories' && $id == 1 ) {
        $res =& $db->select ('COUNT(id)', $desttab);
        list ($num) = $res->fetch_array ();
        return (string) $num;
    }

    $srctab = $scanner->context_table;

    # Find path of diretory type from $arg dir to current dir not containing
    # any doubles.
    $apath[] = $t = $desttab;
    while ($reftab = $dep->ref_table ($t)) {
        $apath[] = $t = $reftab;
        if ($t == $srctab)
            break;
    }

    # If srctab is not the first entry, we can't reach desttab.
    if ($apath[sizeof ($apath) - 1] != $srctab)
        return "<!-- cms: No subdirectories of type '$arg' in directories of type " . $scanner->dirtypes[$srctab] . '.';

    return (string) _c ($apath, sizeof ($apath) - 1, $id);
}

# Returns number of subcategories.
function tag_num_subdirs ($attr)
{
    global $scanner, $db, $dep;

    $arg = $attr['type'];

    if (!($table = $scanner->tables[$arg]))
        return "<!-- cms: No directory type '$arg' known. -->";
    $res =& dbitree_get_childs ($db, $table, $scanner->context['id']);
    if (($num = $res->num_rows ()) < 1)
        $num = 0;
    return (string) $num;
}

# TODO: unroll this
function _c ($apath, $i, $id, $subtable = '', $norec = 0)
{
    global $db, $dep, $test;

    $table = $apath[$i];
    $num = 0;

    # If directory type has subdirs of its own type, check those first.
    if ($dep->ref_table ($table) == $table && !$norec)
        $num += _c ($apath, $i, $id, $table, 1);
    if (!$subtable)
        $subtable = $apath[$i - 1];

    $res =& dbitree_get_childs ($db, $subtable, $id);
    if ($res && ($tmp = $res->num_rows ()) > 0) {
        if ($i == 1)
            return $num += $tmp;
        else {
            if (!$norec)
	        $i--;
            while ($tmp =& $res->fetch_array ())
                $num += _c ($apath, $i, $tmp['id']);
        }
    }
    return $num;
}

function tag_selected ($arg)
{
    global $scanner;

    # TODO: Better check the index.
    if ($scanner->context['id'] == $GLOBALS['id'])
        return $scanner->exec ($arg['_']);
}
?>

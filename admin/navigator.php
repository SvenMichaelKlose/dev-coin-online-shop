<?php

# Navigator used in directory listings.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Create a link if $table/$row[$app->db->primaries[$table]] is not the
# cursor position.
function nav_linkpath (&$app, $table, $row, $arg)
{
    global $lang;

    $p =& $app->ui;
    $name = '<I>' . preg_replace ('/ +/', '&nbsp;', $row['name']) . '</I>';
    $id = $row['id'];
    $out = '';

    $out .= '/';
    $link = $id == 1 ? $lang['root category'] : $name;
    $view = 'view_pages';

    $args['id'] = $id;

    # If app is the current position, only show where we are.
    $out .= ($arg || $GLOBALS['table'] == $table && $GLOBALS['id'] == $id) ?
            "<B>$link</B>" :
            $p->_looselink ($link, new event ($view, $args));
    return $out;
}

# Set our database cursor $table/$id and invoke the walk to the category root.
function show_directory_index (&$app, $table, $id)
{
    global $lang;

    $db =& $app->db;
    $p =& $app->ui;
    $GLOBALS['table'] = $table;
    $GLOBALS['id'] = $id;
    echo $db->traverse_refs_from ($app, $table, $id, 'nav_linkpath', 0, false);

    if ($table == 'directories') {
        # List subcategories
        if ($res = $db->select ('name, id', 'directories', "id_parent=$id ORDER BY name ASC")) {
            echo "<P>\n<font color=\"#888888\"><B>" . $lang['subdirectories'] . ':</B></FONT>';
	    while (list ($name, $id) = $res->get ())
	        $p->link ($name, new event ('view_pages', array ('id' => $id)));
        }
    }
    echo '<BR>';
}

?>

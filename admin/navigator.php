<?
# Navigator used in directory listings.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Create a link if $table/$row[$this->db->primaries[$table]] is not the
# cursor position.
function nav_linkpath ($this, $table, $row, $arg)
{
    global $lang;

    $p =& $this->ui;
    $name = '<I>' . ereg_replace (' +', '&nbsp;', $row['name']) . '</I>';
    $id = $row['id'];
    $out = '';

    # Every table needs another view.
    switch ($table) {
        case 'categories':
	    $out .= '/';
	    $link = $id == 1 ? $lang['root category'] : $name;
	    $view = 'view_pages';
	    break;
        case 'pages':
	    $out .= ' ' . $lang['product group'] . '&nbsp;';
	    $link = "&quot;$name&quot;";
	    $view = 'view_products';
	    break;
        case 'products':
	    $out .= ' ';
	    $link = $lang['product'] . "&nbsp;&quot;$name&quot;";
	    break;
    }

    $args['id'] = $id;

    # If this is the current position, only show where we are.
    $out .= ($arg || $GLOBALS['table'] == $table && $GLOBALS['id'] == $id) ?
            "<B>$link</B>" :
            $p->_looselink ($link, $view, $args);
    return $out;
}

# Set our database cursor $table/$id and invoke the walk to the category root.
function show_directory_index (&$this, $table, $id)
{
    global $lang;

    $db =& $this->db;
    $p =& $this->ui;
    $GLOBALS['table'] = $table;
    $GLOBALS['id'] = $id;
    echo $db->traverse_refs_from ($this, $table, $id, 'nav_linkpath', 0, false);

    if ($table == 'categories') {
        # List subcategories
        if ($res = $db->select ('name, id', 'categories', "id_parent=$id ORDER BY name ASC")) {
            echo "<P>\n<font color=\"#888888\"><B>" . $lang['subdirectories'] . ':</B></FONT>';
	    while (list ($name, $id) = $res->fetch_array ())
	        $p->link ($name, 'view_pages', array ('id' => $id));
        }
    }
    echo '<BR>';
}

<?
# Fulltext search extension for dev/con cms.
#
# Copyright (c) 2000 dev/consulting GmbH,
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Register a virtual directory named 'SEARCH'.
$scanner->assoc ('SEARCH', true);

# Register new tags for our directory.
$scanner->dirtag ('SEARCH', 'LIST LINK NUM-RESULTS');


function dirtag_search_link ($attr)
{
    global $vdir_alias;

    @$template = $attr['template'];
    # XXX Pfusch!
    $args = array ('template' => $vdir_alias['SEARCH'] . $template);
    return tag_link (array_merge ($attr, $args));
} 

# Document handler for search page.
# Create query from form data and pass the result to the cms lister.
function document_search ()
{
    global $SEARCH_TEXT, $SEARCH_NEW, $search_records, $db, $url_vars, $list_offsets;

    $search_records = array ();

    if (isset ($SEARCH_NEW)) {
        $SEARCH_TEXT = $SEARCH_NEW;
        unset ($list_offsets[cms_listsource ()]);
        unset ($url_vars['list_offsets[' . cms_listsource () . ']']);
    }

    if (!isset ($SEARCH_TEXT) || !$SEARCH_TEXT)
        return;

    $SEARCH_TEXT = mysql_real_escape ($SEARCH_TEXT);
    $url_vars['SEARCH_TEXT'] = $SEARCH_TEXT;

    $res = $db->select ('id', 'products', "name LIKE '%'$SEARCH_TEXT%' OR bestnr LIKE '%$SEARCH_TEXT%'");
    while ($res && $row = $res->get ())
        $search_records[] = $row;
}

# <!SEARCH:LIST!>
function dirtag_search_list ($attr)
{
    global $scanner, $default_enumeration, $search_records;

    @$size = $attr['size'];

    # Fetch default template for enumeration if there's none specified.
    return cms_process_list (&$search_records, $attr['_'], 'products', $size);
}

# <!SEARCH:NUM-RESULTS!>
function dirtag_search_num_results ($attr)
{
    return sizeof ($GLOBALS['search_records']);
}
?>

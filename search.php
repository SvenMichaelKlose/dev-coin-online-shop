<?
  # $Id: search.php,v 1.7 2001/12/01 16:17:18 sven Exp $
  #
  # Fulltext search extension for dev/con cms.
  #
  # (c)2000 dev/consulting GmbH,
  #	    Sven Klose (sven@devcon.net)
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
    global $SEARCH_TEXT, $SEARCH_NEW, $search_records, $db, $url_vars,
	   $list_offsets;

    $search_records = array ();

    if (isset ($SEARCH_NEW)) {
      $SEARCH_TEXT = $SEARCH_NEW;
      unset ($list_offsets[cms_listsource ()]);
      unset ($url_vars['list_offsets[' . cms_listsource () . ']']);
    }

    if (!isset ($SEARCH_TEXT) || !$SEARCH_TEXT)
      return;

    $url_vars['SEARCH_TEXT'] = $SEARCH_TEXT;

    $res =& $db->select ('id', 'products',
                         'name LIKE \'%' . $SEARCH_TEXT . '%\'' .
                         ' OR bestnr LIKE \'%' . $SEARCH_TEXT . '%\'');

    while ($row =& $res->fetch_array ())
      $search_records[] = $row;
  }

  # <!SEARCH:LIST!>
  function dirtag_search_list ($attr)
  {
    global $scanner, $default_enumeration, $search_records;

    @$size = $attr['size'];

    # Fetch default template for enumeration if there's none specified.

    return cms_process_list (&$search_records, $attr['_'], 'products',
                             $size);
  }

  # <!SEARCH:NUM-RESULTS!>
  function dirtag_search_num_results ($attr)
  {
    return sizeof ($GLOBALS['search_records']);
  }
?>

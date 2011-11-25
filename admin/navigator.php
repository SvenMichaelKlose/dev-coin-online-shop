<?
  # $Id: navigator.php,v 1.5 2001/10/23 17:32:28 sven Exp $
  #
  # Navigator used in directory listings.
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
	if ($id == 1)
	  $link = $lang['root category'];
	else
	  $link = $name;
	$view = 'view_pages';
	break;
      case 'pages':
	$out .= ' ' . $lang['product group'] . '&nbsp;';
	$link = '&quot;' . $name . '&quot;';
	$view = 'view_products';
	break;
      case 'products':
	$out .= ' ';
	$link = $lang['product'] . '&nbsp;&quot;' . $name . '&quot;';
	break;
    }
    $args['id'] = $id;
    # If this is the current position, only show where we are.
    if ($arg || $GLOBALS['table'] == $table && $GLOBALS['id'] == $id)
      $out .= '<B>' . $link . '</B>';
    else
      $out .= $p->_looselink ($link, $view, $args);
    return $out;
  }

  # Set our virtual database cursor $table/$id and invoke the walk to the
  # category root.
  function show_directory_index (&$this, $table, $id)
  {
    $p =& $this->ui;
    global $lang;
    $GLOBALS['table'] = $table;
    $GLOBALS['id'] = $id;
    echo $this->db->traverse_refs_from ($this, $table, $id, 'nav_linkpath', 0, false);

    if ($table == 'categories') {
      # List subcategories
      $res = $this->db->select (
	'name, id', 'categories', 'id_parent=' . $id, ' ORDER BY name ASC'
      );
      if ($res && $res->num_rows () > 0) {
        echo '<P>' . "\n" .
	     '<FONT COLOR="#888888"><B>' .
	     $lang['subdirectories'] .
	     ':</B></FONT>';
	while (list ($name, $id) = $res->fetch_array ())
	  $p->link ($name, 'view_pages', array ('id' => $id));
      }
    }
    echo '<BR>';
  }

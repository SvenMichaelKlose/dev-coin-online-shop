<?
  # $Id: pages.php,v 1.11 2001/11/08 19:27:11 sven Exp $
  #
  # Product group editors.
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

  function page_init (&$this)
  {
    $this->add_viewfunc ('view_pages');
  }

  function view_pages (&$this)
  {
    global $lang;

    generic_list (
      $this,
      'categories', 'categories', 'pages', 'id_category', 'id_parent',
      Array (''),
      'record_page',
      $lang['msg no product group'], $lang['cmd create_page'],
      $lang['category'], 'defaultview', 'view_products',
      true # Have submit button.
    );
  }

  function record_page (&$this, $idx)
  {
    global $lang;

    $p =& $this->ui;

    $nam = trim ($p->value ('name'));
    if ($nam == '')
      $nam = $lang['unnamed'];

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'LEFT', 'WIDTH' => '100&'));
    $p->link ($idx . ' ' . $nam, 'view_products',
              array ('id' => $p->value ('id')));
    $p->close_cell ();
    $p->open_cell (array ('ALIGN' => 'RIGHT'));
    $p->link ($lang['cmd view_products'], 'view_products',
              array ('id' => $p->value ('id')));
    $p->close_cell ();
    $p->close_row ();

    $p->paragraph ();

    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $p->checkbox ('marker');
    $p->close_cell ();
    $p->open_cell ();
    _object_box ($this, 'pages', $p->value ('id'), $this->args, true);
    $p->close_cell ();
    $p->close_row ();
    $p->paragraph ();
  }
?>

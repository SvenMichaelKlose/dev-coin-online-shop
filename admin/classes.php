<?
  # $Id: classes.php,v 1.8 2001/10/23 17:50:06 sven Exp $
  #
  # Object classes.
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
  
  function class_init (&$this)
  {
    $this->add_viewfunc ('view_classes');
    $this->add_viewfunc ('edit_class');
  }

  # View classes.
  # No arguments.
  function view_classes (&$this)
  {
    global $lang;
    $p =& $this->ui;
    $p->headline ($lang['title view_classes']);
    $p->link ($lang['title defaultview'], 'defaultview', 0);
    $p->no_update = true;
    $p->open_source ('obj_classes');
    $p->table_headers (Array ('Syntax', $lang['class'], $lang['description']));
    if ($p->get ('ORDER BY name ASC'))
      do {
        $p->open_row (array ('ALIGN' => 'LEFT'));
	$p->label (_class2tag ($p->value ('name')));
	$p->show ('name');
	$v = $p->value ('descr');
	if (!$v)
	  $v = '[' . $lang['unnamed'] . ']';
        $p->open_cell (array ('ALIGN' => 'LEFT'));
	$p->cmd ($v, 'id', 'edit_class');
	$p->close_cell ();
	$p->close_row ();
      } while ($p->get_next ());	
    $p->paragraph ();  
    $p->cmd_create ($lang['cmd create class'], 'view_classes');  
    $p->close_source ();
  }

  # Edit class name.
  # id = Key of class in table obj_classes.
  function edit_class (&$this)
  {
    global $lang;
    $p =& $this->ui;
    $p->headline ($lang['title edit_class']);
    $p->open_source ('obj_classes');
    $p->get ('WHERE id=' . $this->args['id']);
    $p->open_row (array ('ALIGN' => 'LEFT'));
    $p->inputline ('name', 64, $lang['class name']);
    $p->label ('<B>' . _class2tag ($p->value ('name')) . '</B>');
    $p->close_row ();
    $p->paragraph ();
    $p->inputline ('descr', 64, $lang['description']);
    $p->paragraph ();
    $p->open_row ();
    $p->cmd_delete ($lang['remove'], 'view_classes');
    $p->submit_button (
      'Ok', '_update', $this->arg_set_next (0, 'view_classes')
    );
    $p->close_row ();
    $p->close_source ();
  }

  # Return tag that uses a particular object class.
  # $name = Class name.
  function _class2tag ($name)
  {
    global $lang;
    $arg = '';
    if (substr ($name, 0, 2) == 'd_')
      return 'Konfiguration';
    if (substr ($name, 0, 2) == 'u_')
      return '<B>&lt;!:OBJECT&nbsp;' . $name . '!&gt;</B> ' .
             ' <B>&lt;!:OBJECTLINK&nbsp;' . $name . '!&gt;</B> (' . $lang['user defined'] . ')';
    if ($name == 'l_cart')
      return '<B>&lt;!CART!&gt;</B>';
    if ($name == 'l_ecml')
      return '<B>&lt;!ORDER!&gt;</B>';
    if ($name == 'l_order')
      return '<B>&lt;!ORDER!&gt;</B>';
    if ($name == 'l_order_email' || $name == 'l_order_confirm'
      || substr ($name, 0, 2) == 'd_' || $name == 'l_index')
      return '-';
    if (substr ($name, 0, 2) == 'l_') {
      if ($name == 'l_pages') {
        $arg = ' ' . $name;
	$name = 'l_page';
      } else if ($name == 'l_empty_cart')
	$name = 'l_cart';
      return '<b>&lt;!' . strtoupper (substr ($name, 2)) .
      	     ':LINK' . $arg . '!&gt;</b>';
    } else if (substr ($name, 0, 3) == 'll_') {
      if ($name == 'll_pages')
        $arg = ' ' . $name;
      else if ($name == 'll_page_indices') {
        $arg = ' ' . $name;
	$name = 'll_page';
      } else if ($name == 'll_category_group')
        return '<B>&lt;!CATEGORY:LIST-GROUP!&gt;</B>';
      else if ($name == 'll_order_email' || $name == 'll_order_confirm')
        $name = 'll_order';
      return '<b>&lt;!' . strtoupper (substr ($name, 3)) .
      	     ':LIST' . $arg . '!&gt;</b>';
    }
    return $name . ' <FONT COLOR="RED">(' . $lang['illegal name'] . ')</FONT>';
  }

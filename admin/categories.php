<?
  # $Id: categories.php,v 1.7 2001/10/23 17:50:06 sven Exp $
  #
  # Editing categories
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

  function category_init (&$this)
  {
    $this->add_viewfunc ('create_category');
  }

  # Create a subcategory for a category.
  # id = Key of parent category.
  function create_category (&$this)
  {
    global $lang;

    $nid = $this->db->append_new ('categories', $this->arg ('id'));
    $this->ui->mark_id = 'categories_' . $nid;
    $this->ui->color_highlight = '#00FF00';
    $this->ui->msgbox ($lang['msg category created']);
  }
?>

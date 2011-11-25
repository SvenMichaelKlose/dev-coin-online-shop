<?
  # $Id: updates.php,v 1.16 2001/10/23 17:32:28 sven Exp $
  #
  # Various update code for the public.
  #
  # Copyright (c) 2000 dev/consulting GmbH
  # Copyright (c) 2011 Sven Klose <pixel@copei.de>
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

  include 'dbi/dbi.class.php';

  function do_updates ()
  {
    if (isset ($GLOBALS['no_update']))
      return;

    include '.dbi.conf.php';
    $db =& new DBI ($dbidatabase, $dbiserver, $dbiuser, $dbipwd);
  }
?>

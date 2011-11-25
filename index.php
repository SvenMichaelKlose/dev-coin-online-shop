<?
  # $Id: index.php,v 1.90 2001/12/01 16:17:18 sven Exp $
  #
  # dev/coin shop
  #
  # This is the main file of the public script.
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH,
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

  # We want it strict.
  error_reporting (-1); # "strict-mode-surrogate-extract"

  # HEADSUP: Global variable declarations start here.
  # Use global variables rarely.
  $debug = 0;			# Set true for excessive verbosity/profiling.
  $page_profiler = false;	# Show overall time needed to create page.
  				# Note that this options spoils images so they
				# won't be displayed by any browser.

  # Get current time for profiling.
  if ($debug || $page_profiler) {
    $t = gettimeofday ();
    $__start_time = $t['usec'] + $t['sec'] * 1000000;
  }

  ##################################
  ### Inclusion of external file ###
  ##################################

  # dbictrl.class provides basic database access.
  include 'dbi/dbctrl.class.php';

  # dbdepend.class holds dependencies between database tables.
  include 'dbi/dbdepend.class.php';

  # dbobj.class manages inheritable objects in the directory tree of
  # categories, pages (aka product groups) and products.
  include 'dbi/dbobj.class.php';

  # Tree walking functions.
  include 'dbi/dbtree.php';

  # scanner.class is the template scanner.
  include 'lib/xml_scanner.class.php';

  # htmllig2latin.php convert HTML ligatures to latin characters.
  # e.g. &auml; => ae
  include 'lib/htmllig2latin.php';

  # Call panic() if you want to set alarm and send a mail to the
  # administrator. (Uses the email address in $SERVER_ADMIN which you can
  # override in .dbi.conf.php.
  include 'lib/panic.class.php';

  ##############################
  ### Database configuration ###
  ##############################
  
  if (!file_exists ('./.dbi.conf.php'))
    die ('Can\'t find database confiuration file .dbi.conf.php - stop.');
  include '.dbi.conf.php';

  $db =& new DBCtrl ($dbidatabase, $dbiserver, $dbiuser, $dbipwd);
  $tmp = $db->select ('COUNT(id)', 'obj_classes');
  if (!$db->is_connected () || $db->error ())
    die ('Can\'t connect to database. Please invoke the admin script ' .
         '- stop.<BR>' .
	 'Keine Verbindung zur Datenbank moeglich. Bitte versuchen Sie es ' .
	 'mit dem Administrationsskript - Stop.');

  # $dep contains a database description which is very important for
  # the directory management. See dbi/dbdepend.class for details.
  $dep =& new DBDEPEND;
  $db->def =& $dep;

  # Create an instance of the scanner to process templates.
  $scanner =& new XML_SCANNER;

  # HEADSUP: End of global variable declarations.

  ###################################
  ### Inclusion of internal files ###
  ###################################

  # Load CMS configuration.
  require 'mod_shop/admin/config.php';

  # attic.php contains outdated features scheduled for removal but left
  # in for temporary backwards compatibility.
  require 'mod_shop/attic.php';

  # cms.php contains the data management system, e.g. context creation
  # and general tag handlers.
  require 'mod_shop/cms.php';

  # cart.php contains the trolley functions and all tag handlers for
  # PRODUCT and CART directories.
  require 'mod_shop/cart.php';

  # order.php interfaces to ecml.php using tag handlers for ORDER
  # directories.
  require 'mod_shop/order.php';

  # ecml.php contains cms-independent functions for ECML v1.1 support.
  require 'mod_shop/ecml.php';

  # send_order.php sends orders via mail (invoked by document_order() in
  # in order.php).
  require 'mod_shop/send_order.php';

  # document.php analyses the URL and determines the initial directory as
  # well as the default document template which can be overridden by 
  # the document handlers it calls.
  require 'mod_shop/document.php';

  # Search engine.
  require 'mod_shop/search.php';

  ###########
  ### Go! ###
  ###########

  # Process document at $PATH_INFO starting with row of 'categories' where
  # id == 1. Use 'l_index' template for index page.
  # This function is in file 'document.php'.
  document_process ('categories', 1, 'l_index');

  ##########################
  ### Optional profiling ###
  ##########################

  # Page profiler: Get current time and print the difference.
  if ($debug || $page_profiler) {
    $t = gettimeofday ();
     echo 'Overall time spent: ' .
          (($t['usec'] + $t['sec'] * 1000000 - $__start_time) / 1000000) . 's' .
	  ' database queries: ' . $DB_QUERIES;
  }
?>

<?
# This is the main file of the public script.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# We want it strict.
error_reporting (-1); # "strict-mode-surrogate-extract"

# Set true for excessive verbosity/profiling.
$debug = 0;

# Show overall time needed to create page.
# Note that this options spoils images so they won't be displayed by any browser.
$page_profiler = false;

$PATH_TO_CAROSHI = '.';
$PATH_TO_ADMIN = 'admin/';
  
if (!file_exists ('./.dbi.conf.php'))
    die ('Can\'t find database confiuration file .dbi.conf.php - stop.');
include '.dbi.conf.php';

# Get current time for profiling.
if ($debug || $page_profiler) {
    $t = gettimeofday ();
    $__start_time = $t['usec'] + $t['sec'] * 1000000;
}


#####################
### External file ###
#####################

# Get head of strings.
include "$PATH_TO_CAROSHI/lib/strhead.php";

# scanner.class is the template scanner.
include "$PATH_TO_CAROSHI/lib/xml_scanner.class.php";

# Convert HTML ligatures to latin characters. E.g. &auml; => ae
include "$PATH_TO_CAROSHI/lib/htmllig2latin.php";

# Call panic() if you want to set alarm and send a mail to the
# administrator. (Uses the email address in $SERVER_ADMIN which you can
# override in .dbi.conf.php.
include "$PATH_TO_CAROSHI/lib/panic.class.php";

# Debug dumps.
include "$PATH_TO_CAROSHI/lib/debug_dump.php";

# Basic database access.
include "$PATH_TO_CAROSHI/dbi/dbctrl.class.php";

# Database table relations.
include "$PATH_TO_CAROSHI/dbi/dbdepend.class.php";

# Inheritable objects in the directory tree of
# categories, pages (aka product groups) and products.
include "$PATH_TO_CAROSHI/dbi/dbobj.class.php";

# Tree walking.
include "$PATH_TO_CAROSHI/dbi/dbtree.php";

# Sessions.
include "$PATH_TO_CAROSHI/dbi/dbsession.php";


##############################
### Global initializations ###
##############################

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

$session =& new DBSESSION ($db);
if (isset ($SESSION_KEY))
    $session->read_id ($SESSION_KEY);


###################################
### Inclusion of internal files ###
###################################

# Load CMS configuration.
require "$PATH_TO_ADMIN/admin/config.php";

# Outdated features scheduled for removal but left in for temporary backwards compatibility.
require 'attic.php';

# Data management system, e.g. context creation and general tag handlers.
require 'cms.php';

# Trolley functions and all tag handlers for PRODUCT and CART directories.
require 'cart.php';

require 'order.php';

# CMS-independent functions for ECML v1.1 support.
require 'ecml.php';

# Sending orders via mail,
require 'send_order.php';

# Analyses the URL, picks a template and processes it.
require 'document.php';

# Search engine.
require 'search.php';


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
    echo 'Overall time spent: ' . (($t['usec'] + $t['sec'] * 1000000 - $__start_time) / 1000000) . 's' .
	 ' database queries: ' . $DB_QUERIES;
}
?>

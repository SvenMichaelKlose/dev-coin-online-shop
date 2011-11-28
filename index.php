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

$PATH_TO_ADMIN = 'admin/';
define ('PATH_TO_ADMIN', 'admin');
  
if (!file_exists ('./config.php'))
    die ('Can\'t find database confiuration file config.php - stop.');
include 'config.php';

if (!file_exists ('caroshi-php/index.php'))
    die ('Can\'t find caroshi-php/index.php config.php - please download <a href="https://github.com/SvenMichaelKlose/Caroshi-PHP">Caroshi-PHP</a> and extract it here.');                                                                                                                                                    
require_once 'caroshi-php/index.php';

# Get current time for profiling.
if ($debug || $page_profiler) {
    $t = gettimeofday ();
    $__start_time = $t['usec'] + $t['sec'] * 1000000;
}


######################
### External files ###
######################


include PATH_TO_CAROSHI . '/string/strhead.php'; # Get head of strings.
include PATH_TO_CAROSHI . '/string/htmllig2latin.php'; # Convert HTML ligatures to latin characters. E.g. &auml; => ae
include PATH_TO_CAROSHI . '/text/xml/scanner.class.php'; # scanner.class is the template scanner.
include PATH_TO_CAROSHI . '/proc/panic.class.php'; # Panic and tell the administrator about incidents.
include PATH_TO_CAROSHI . '/proc/debug_dump.php'; # Debug dumps.
include PATH_TO_CAROSHI . '/dbi/dbctrl.class.php'; # Basic database access.
include PATH_TO_CAROSHI . '/dbi/dbdepend.class.php'; # Database table relations.
include PATH_TO_CAROSHI . '/dbi/dbobj.class.php'; # Inheritable objects in the directory.
include PATH_TO_CAROSHI . '/dbi/dbtree.php'; # Directory utilities.
include PATH_TO_CAROSHI . '/dbi/dbsession.class.php'; # Sessions management.


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

# $dep contains a database description required for the directory management.
# See also dbi/dbdepend.class in Caroshi.
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


require PATH_TO_ADMIN . '/cms-config.php';

# Shop-indepented part.
require 'attic.php'; # Outdated features scheduled for removal but left in for temporary backwards compatibility.
require 'cms.php'; # Data management system, e.g. context creation and general tag handlers.
require 'document.php'; # Analyses the URL, picks a template and processes it.

require 'directory.php'; # Describes how tables relate to form the directory.
require 'product.php';
require 'cart.php';
require 'ecml.php'; # CMS-independent functions for ECML v1.1 support.
require 'send_order.php';
require 'order.php';
require 'search.php'; # Product search.


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

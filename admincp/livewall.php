<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2007-2009 Fillip Hannisdal AKA Revan/NeoRevan/Belazor # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS ######################
define('THIS_SCRIPT', 'livewall');
define('CVS_REVISION', '$RCSfile: livewall.php,v $ - $Revision: $WCREV$ $');
define('IN_LIVEWALL', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('dbtech_livewall', 'cphome', 'logging', 'threadmanage',
'maintenance', 'banning', 'cpuser', 'cpoption', 'cppermission');

// get special data templates from the datastore
require_once('../dbtech/livewall/includes/specialtemplates.php');
$specialtemplates = $extracache;

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS ######################
if (!can_administer('canadminlivewall') AND $_REQUEST['do'] != 'finalise')
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ##############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (!empty($_POST['do']))
{
	// $_POST requests take priority
	$action = $_POST['do'];
}
else if (!empty($_GET['do']))
{
	// We had a GET request instead
	$action = $_GET['do'];
}
else
{
	// No request
	$action = 'main';
}

// Strip non-valid characters
$action = preg_replace('/[^\w-]/i', '', $action);

if (!file_exists(DIR . '/dbtech/livewall/actions/admin/' . $action . '.php'))
{
	if (!file_exists(DIR . '/dbtech/livewall_pro/actions/admin/' . $action . '.php'))
	{
		// Throw error from invalid action
		print_cp_message($vbphrase['dbtech_livewall_invalid_action']);
	}
	else
	{
		// Include the selected file
		include_once(DIR . '/dbtech/livewall_pro/actions/admin/' . $action . '.php');	
	}
}
else
{
	// Include the selected file
	include_once(DIR . '/dbtech/livewall/actions/admin/' . $action . '.php');	
}

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: livewall.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>
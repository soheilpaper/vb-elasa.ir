<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'vbactivity');
define('IN_VBACTIVITY', true);

if (isset($_REQUEST['do']) AND $_REQUEST['do'] == 'ajax')
{
	define('CSRF_PROTECTION', true);
	define('LOCATION_BYPASS', 1);
	define('NOPMPOPUP', 1);
	define('VB_ENTRY', 'ajax.php');
	define('SESSION_BYPASS', true);
	define('VB_ENTRY_TIME', microtime(true));
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('dbtech_vbactivity', 'user', 'posting', 'album', 'messaging');

// get templates used by all actions
$globaltemplates = array(
	'dbtech_vbactivity',
	'dbtech_vbactivity_member_css',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'main' => array(
		'dbtech_vbactivity_main',
	),
	'achievements' => array(
		'dbtech_vbactivity_changes',		
		'dbtech_vbactivity_daybit',
		'dbtech_vbactivity_entrybit',
	),
	'promotions' => array(
		'dbtech_vbactivity_changes',		
		'dbtech_vbactivity_daybit',
		'dbtech_vbactivity_entrybit',
	),
	'trophies' => array(
		'dbtech_vbactivity_changes',		
		'dbtech_vbactivity_daybit',
		'dbtech_vbactivity_entrybit',
	),
	'medals' => array(
		'dbtech_vbactivity_changes',
		'dbtech_vbactivity_daybit',
		'dbtech_vbactivity_entrybit',
	),
	'activity' => array(
		'dbtech_vbactivity_changes',
		'dbtech_vbactivity_daybit',
		'dbtech_vbactivity_entrybit',
	),
	'ranking' => array(
		'dbtech_vbactivity_ranking',
		'dbtech_vbactivity_ranking_userbit',
	),
	'leaderboards' => array(
		'dbtech_vbactivity_leaderboards',
		'dbtech_vbactivity_leaderboards_leaderboardbit',
		'dbtech_vbactivity_leaderboards_userbit',
	),
	'achievtargets' => array(
		'dbtech_vbactivity_categorybits',
		'dbtech_vbactivity_contentbits',
	),
	'allachievements' => array(
		'dbtech_vbactivity_categorybits',
		'dbtech_vbactivity_contentbits',
	),
	'allawards' => array(
		'dbtech_vbactivity_categorybits',
		'dbtech_vbactivity_contentbits',
	),
	'alltrophies' => array(
		'dbtech_vbactivity_categorybits',
		'dbtech_vbactivity_contentbits',
	),
	'contests' => array(
		'dbtech_vbactivity_contests',
		'dbtech_vbactivity_contests_bit',
		'dbtech_vbactivity_contests_info',
		'dbtech_vbactivity_contests_standing',
		'dbtech_vbactivity_contests_standing_bit',
		'dbtech_vbactivity_contests_winner',
		'dbtech_vbactivity_contests_prize',
		'dbtech_vbactivity_contests_manage',
		'dbtech_vbactivity_contests_manage_bit',
		'dbtech_vbactivity_contests_manage_modify',
		'dbtech_vbactivity_contests_manage_modify_target',
		'dbtech_vbactivity_contests_manage_modify_thread',
		'dbtech_vbactivity_contests_manage_prizebit',
		'dbtech_vbactivity_contests_manage_wrapper',
	),
	'activitystats' => array(
		'dbtech_vbactivity_activitystats',
		'dbtech_vbactivity_activitystats_bit',
	),
	'nominatemedal' => array(
		'dbtech_vbactivity_nominate',
	),
	'requestmedal' => array(
		'dbtech_vbactivity_request',
	),
	'profile' => array(
		'USERCP_SHELL',
		'usercp_nav_folderbit',
	),
);

// get special data templates from the datastore
require('./dbtech/vbactivity/includes/specialtemplates.php');
$specialtemplates = $extracache;

// ############################### default do value ######################
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = $_GET['do'] = 'main';
}

if (isset($_REQUEST['xml']) AND $_REQUEST['xml'])
{
	$_REQUEST['do'] = $_GET['do'] = 'leaderboards';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}

if (!class_exists('VBACTIVITY'))
{
	eval(standard_error($vbphrase['dbtech_vbactivity_deactivated']));
}


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

if (!$vbulletin->options['dbtech_vbactivity_active'] AND !VBACTIVITY::$permissions['ismanager'])
{
	// Sb is shut off
	eval(standard_error($vbulletin->options['dbtech_vbactivity_closedreason']));
}

if (!VBACTIVITY::$permissions['canview'])
{
	// Can't view Activity
	print_no_permission();
}

// begin navbits
$navbits = array('vbactivity.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['dbtech_vbactivity_vbactivity']);

// Core page template
$page_template = 'dbtech_vbactivity';

if (!file_exists(DIR . '/dbtech/vbactivity/actions/' . $action . '.php'))
{
	if (!file_exists(DIR . '/dbtech/vbactivity_pro/actions/' . $action . '.php'))
	{
		// Throw error from invalid action
		eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_invalid_action'])));
	}
	else
	{
		// Include the selected file
		include_once(DIR . '/dbtech/vbactivity_pro/actions/' . $action . '.php');	
	}
}
else
{
	// Include the selected file
	include_once(DIR . '/dbtech/vbactivity/actions/' . $action . '.php');	
}

if (intval($vbulletin->versionnumber) == 3)
{
	// Create navbits
	$navbits = construct_navbits($navbits);	
	eval('$navbar = "' . fetch_template('navbar') . '";');
}
else
{
	$navbar = render_navbar_template(construct_navbits($navbits));	
}

if (intval($vbulletin->versionnumber) == 3)
{
	// Begin the monster template
	//$headinclude .= vB_Template::create('dbtech_vbsupport.css')->render();
}

$show['vb4compat'] = version_compare($vbulletin->versionnumber, '4.0.8', '>=');
$headinclude .= vB_Template::create('dbtech_vbactivity_member_css')->render();

$clientscripts = "<script type=\"text/javascript\">vbphrase = vbphrase || []; vbphrase['n_a'] = '" . str_replace("'", "\'", $vbphrase['n_a']) . "';</script>";

// Finish the main template
$templater = vB_Template::create($page_template);
	$templater->register_page_templates();
	$templater->register('navclass', 		$navclass);
	$templater->register('HTML', 			$HTML);
	$templater->register('navbar', 			$navbar);
	$templater->register('pagetitle', 		$pagetitle);
	$templater->register('pagedescription', $pagedescription);
	$templater->register('template_hook', 	$template_hook);
	$templater->register('includecss', 		$includecss);
	$templater->register('year',			date('Y'));
	$templater->register('version',			VBACTIVITY::$version);
	$templater->register('versionnumber', 	VBACTIVITY::$versionnumber);
	$templater->register('jQueryVersion', 	VBACTIVITY::$jQueryVersion);
	$templater->register('jQueryPath',		VBACTIVITY::jQueryPath());
	$templater->register('headinclude', 	$headinclude);
	$templater->register('clientscripts', 	$clientscripts);
print_output($templater->render());
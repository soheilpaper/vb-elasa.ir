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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('IN_LIVEWALL', true);

if ($_REQUEST['do'] == 'ajax')
{
	define('THIS_SCRIPT', 'ajax');
	define('CSRF_PROTECTION', true);
	define('LOCATION_BYPASS', 1);
	define('NOPMPOPUP', 1);
	define('NONOTICES', 1);
	define('VB_ENTRY', 'ajax.php');
	define('SESSION_BYPASS', true);
	define('VB_ENTRY_TIME', microtime(true));
}
else
{
	define('THIS_SCRIPT', 'livewall');	
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('dbtech_livewall', 'user', 'album');

// get templates used by all actions
$globaltemplates = array(
	'dbtech_livewall',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'viewcomments' => array(
		'dbtech_livewall_comment',
		'dbtech_livewall_comments',
		'dbtech_livewall_entry_comments',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',		
	),
	'main' => array(
		'dbtech_livewall_main',
		'dbtech_livewall_entry',
		'dbtech_livewall_comment',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',		
	),
	'favourites' => array(
		'dbtech_livewall_main',
		'dbtech_livewall_entry',
		'dbtech_livewall_comment',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',		
	),
	'ajax' => array(
		'dbtech_livewall_entry',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',		
	),
	'profile' => array(
		'USERCP_SHELL',
		'usercp_nav_folderbit',
	),
);

// get special data templates from the datastore
require_once('./dbtech/livewall/includes/specialtemplates.php');
$specialtemplates = $extracache;

// ############################### default do value ######################
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = $_GET['do'] = 'main';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/livewall/includes/class_template.php');
}

if (!class_exists('LIVEWALL'))
{
	eval(standard_error($vbphrase['dbtech_livewall_deactivated']));
}

if ($_REQUEST['do'] == 'devinfo' AND $_REQUEST['devkey'] == 'dbtech')
{
	LIVEWALL::outputJSON(array(
		'version' 		=> LIVEWALL::$version,
		'versionnumber' => LIVEWALL::$versionnumber,
		'pro'			=> LIVEWALL::$isPro,
		'vbversion'		=> $vbulletin->versionnumber
	));
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

if (!$vbulletin->options['dbtech_livewall_active'])
{
	// Sb is shut off
	eval(standard_error($vbulletin->options['dbtech_livewall_closedreason']));
}

if (LIVEWALL::$permissions['isbanned'])
{
	// Can't view Activity
	print_no_permission();
}

// begin navbits
$navbits = array('livewall.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['dbtech_livewall_livewall']);

// Core page template
$page_template = 'dbtech_livewall';

// Show branding or not
$show['livewall_branding'] = $vbulletin->options['dbtech_livewall_branding_free'] != '<(-=LiveWall.Key|LiveWall.Branding.Free=-)>';

if (!file_exists(DIR . '/dbtech/livewall/actions/' . $action . '.php'))
{
	if (!file_exists(DIR . '/dbtech/livewall_pro/actions/' . $action . '.php'))
	{
		// Throw error from invalid action
		eval(standard_error(fetch_error('dbtech_livewall_error_x', $vbphrase['dbtech_livewall_invalid_action'])));
	}
	else
	{
		// Include the selected file
		include_once(DIR . '/dbtech/livewall_pro/actions/' . $action . '.php');	
	}
}
else
{
	// Include the selected file
	include_once(DIR . '/dbtech/livewall/actions/' . $action . '.php');	
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
	$headinclude .= vB_Template::create('dbtech_livewall.css')->render();
}

// Show branding or not
$show['dbtech_livewall_producttype'] = (LIVEWALL::$isPro ? ' (Pro)' : ' (Lite)');

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
	$templater->register('jQueryVersion',	LIVEWALL::$jQueryVersion);
	$templater->register('version',			LIVEWALL::$version);
	$templater->register('versionnumber', 	LIVEWALL::$versionnumber);
	$templater->register('headinclude', 	$headinclude);
print_output($templater->render());

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: livewall.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>
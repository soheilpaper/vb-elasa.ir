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

if (!$vbulletin->userinfo['userid'])
{
	// Ensure guests can't access
	print_no_permission();
}

// ######################### REQUIRE BACK-END ############################
require_once(DIR . '/includes/functions_user.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// ############################### start display options ###############################
if ($_REQUEST['action'] == 'options' OR empty($_REQUEST['action']))
{
	// Navigation bits
	$navbits[''] = $vbphrase['dbtech_livewall_settings'];
	
	/*
	// Grab all the bitfields we can
	require_once(DIR . '/includes/class_bitfield_builder.php');
	$bitfields = vB_Bitfield_Builder::return_data();
	*/
	
	// Begin the array of options
	$optionlist = $selectlist = array();
	
	/*
	foreach (array(
		'dbtech_livewall_notification_settings' 	=> $bitfields['nocache']['dbtech_livewall_notification_settings'],
	) as $settinggroup => $settings)
	{
		// Begin settings
		$optionlist[$settinggroup] = array();
		
		foreach ($settings as $settingname => $bit)
		{
			$optionlist[$settinggroup][] = array(
				'varname'		=> $settingname,
				'description' 	=> $vbphrase[$settingname . '_descr'],
				'checked'		=> ((intval($vbulletin->userinfo['dbtech_livewall_settings2']) & $bit) ? ' checked="checked"' : ''),
				'settingphrase'	=> $vbphrase[$settingname],
				'phrase'		=> $vbphrase[$settingname . '_short'],
			);
		}
	}
	*/	
	
	$updated = false;
	foreach (LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
	{
		if (!$contenttype['active'])
		{
			// Inactive contenttype
			continue;
		}
		
		if (!$contenttype['enabled'])
		{
			// Pro only and we're in Lite
			continue;
		}
		
		if (!isset($vbulletin->userinfo[$contenttypeid . '_display']) AND !$updated)
		{
			// Insert default settings
			LIVEWALL::$db->insert('dbtech_livewall_settings', array('userid' => $vbulletin->userinfo['userid']));
			$updated = true;
		}
		
		// Determine selected values
		$key1 = (isset($vbulletin->userinfo[$contenttypeid . '_display']) ? $vbulletin->userinfo[$contenttypeid . '_display'] : 0);
		$key2 = (isset($vbulletin->userinfo[$contenttypeid . '_privacy']) ? $vbulletin->userinfo[$contenttypeid . '_privacy'] : 0);
		
		$selectlist['dbtech_livewall_display_settings'][] = array(
			'varname'		=> $contenttypeid . '_display',
			'description' 	=> $vbphrase['dbtech_livewall_contenttype_display_descr'],
			'selected'		=> array($key1 => 'selected="selected"'),
			'settingphrase'	=> $vbphrase['dbtech_livewall_contenttype_display'],
			'phrase'		=> $contenttype['title'],
		);
		$selectlist['dbtech_livewall_privacy_settings'][] = array(
			'varname'		=> $contenttypeid . '_privacy',
			'description' 	=> $vbphrase['dbtech_livewall_contenttype_privacy_descr'],
			'selected'		=> array($key2 => 'selected="selected"'),
			'settingphrase'	=> $vbphrase['dbtech_livewall_contenttype_privacy'],
			'phrase'		=> $contenttype['title'],
		);
	}
	
	foreach ($optionlist as $headerphrase => $options)
	{
		$optionbits2 = '';
		foreach ($options as $option)
		{
			$templater = vB_Template::create('dbtech_livewall_options_bit_bit');
				$templater->register('option', $option);
			$optionbits2 .= $templater->render();	
		}
		
		$templater = vB_Template::create('dbtech_livewall_options_bit');
			$templater->register('headerphrase', $vbphrase[$headerphrase]);
			$templater->register('optionbits2', $optionbits2);
		$optionbits .= $templater->render();	
	}
	
	foreach ($selectlist as $headerphrase => $options)
	{
		$optionbits2 = '';
		foreach ($options as $option)
		{
			$templater = vB_Template::create('dbtech_livewall_options_bit_bit2');
				$templater->register('option', $option);
			$optionbits2 .= $templater->render();	
		}
		
		$templater = vB_Template::create('dbtech_livewall_options_bit');
			$templater->register('headerphrase', $vbphrase[$headerphrase]);
			$templater->register('optionbits2', $optionbits2);
		$optionbits .= $templater->render();	
	}
	
	// Include the page template
	$page_templater = vB_Template::create('dbtech_livewall_options');
		$page_templater->register('optionbits', $optionbits);
}

// ############################### start save options ##################################
if ($_POST['action'] == 'updateoptions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'options'        		=> TYPE_ARRAY_BOOL,
		'set_options'    		=> TYPE_ARRAY_BOOL,
		'settings'        		=> TYPE_ARRAY_INT,
	));
	
	$update = array();
	foreach (LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
	{
		if (!$contenttype['active'])
		{
			// Inactive contenttype
			continue;
		}
		
		if (!$contenttype['enabled'])
		{
			// Pro only and we're in Lite
			continue;
		}
		
		// Set the update params
		$update[$contenttypeid . '_display'] = intval($vbulletin->GPC['settings'][$contenttypeid . '_display']);
		$update[$contenttypeid . '_privacy'] = intval($vbulletin->GPC['settings'][$contenttypeid . '_privacy']);
	}
	
	// Update the DB
	LIVEWALL::$db->update('dbtech_livewall_settings', $update, 'WHERE userid = ' . intval($vbulletin->userinfo['userid']));
	
	/*
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	
	$bitfields = array();
	foreach (LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
	{
		$bitfields[$contenttype['varname']] = $contenttype['bitfield'];
	}
	
	// Add to userdata
	$userdata->bitfields['dbtech_livewall_settings'] 	= $bitfields;
	
	// options bitfield
	foreach ($userdata->bitfields['dbtech_livewall_settings'] AS $key => $val)
	{
		if (isset($vbulletin->GPC['options'][$key]) OR isset($vbulletin->GPC['set_options'][$key]))
		{
			$value = $vbulletin->GPC['options'][$key];
			$userdata->set_bitfield('dbtech_livewall_settings', $key, $value);
		}
	}
	
	// Grab all the bitfields we can
	require_once(DIR . '/includes/class_bitfield_builder.php');
	$bitfields = vB_Bitfield_Builder::return_data();	
	
	// Add to userdata
	$userdata->bitfields['dbtech_livewall_settings2'] = $bitfields['nocache']['dbtech_livewall_notification_settings'];

	// options bitfield
	foreach ($userdata->bitfields['dbtech_livewall_settings2'] AS $key => $val)
	{
		if (isset($vbulletin->GPC['options'][$key]) OR isset($vbulletin->GPC['set_options'][$key]))
		{
			$value = $vbulletin->GPC['options'][$key];
			$userdata->set_bitfield('dbtech_livewall_settings2', $key, $value);
		}
	}
	
	// Save the userdata
	$userdata->save();
	*/
	
	$vbulletin->url = 'livewall.php?' . $vbulletin->session->vars['sessionurl'] . 'do=profile&action=options';
	eval(print_standard_redirect(array('redirect_updatethanks', $vbulletin->userinfo['username'])));
}

// #######################################################################
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
construct_usercp_nav('dbtech_livewall_' . $_REQUEST['action']);

$templater = vB_Template::create('USERCP_SHELL');
	$templater->register_page_templates();
	$templater->register('cpnav', $cpnav);
	if (method_exists($page_templater, 'render'))
	{
		// Only run this if there's anything to render
		$templater->register('HTML', $page_templater->render());
	}
	$templater->register('clientscripts', $clientscripts);
	$templater->register('navbar', $navbar);
	$templater->register('navclass', $navclass);
	$templater->register('onload', $onload);
	$templater->register('pagetitle', $pagetitle);
	$templater->register('template_hook', $template_hook);
print_output($templater->render());

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: profile.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>
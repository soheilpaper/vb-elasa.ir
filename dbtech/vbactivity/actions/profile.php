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
if ($_REQUEST['action'] == 'options')
{
	// Navigation bits
	$navbits[''] = $vbphrase['dbtech_vbactivity_settings'];
	
	// Grab all the bitfields we can
	require_once(DIR . '/includes/class_bitfield_builder.php');
	$bitfields = vB_Bitfield_Builder::return_data();
	
	// Begin the array of options
	$optionlist = array();
	
	$settinggroups = array(
		'dbtech_vbactivity_display_settings' 		=> $bitfields['nocache']['dbtech_vbactivity_display_settings'],
	);

	/*DBTECH_PRO_START*/
	// Add notif settings
	$settinggroups['dbtech_vbactivity_notification_settings'] = $bitfields['nocache']['dbtech_vbactivity_notification_settings'];
	/*DBTECH_PRO_END*/

	foreach ($settinggroups as $settinggroup => $settings)
	{
		// Begin settings
		$optionlist["$settinggroup"] = array();
		
		foreach ($settings as $settingname => $bit)
		{
			$optionlist["$settinggroup"][] = array(
				'varname'		=> $settingname,
				'description' 	=> $vbphrase["{$settingname}_descr"],
				'checked'		=> ((intval($vbulletin->userinfo['dbtech_vbactivity_settings']) & $bit) ? ' checked="checked"' : ''),
				'settingphrase'	=> $vbphrase["{$settingname}_short"],
				'phrase'		=> $vbphrase["{$settingname}"],
			);
		}
	}
	
	foreach ($optionlist as $headerphrase => $options)
	{
		$optionbits2 = '';
		foreach ($options as $option)
		{
			$templater = vB_Template::create('dbtech_vbactivity_options_bit_bit');
				$templater->register('option', $option);
			$optionbits2 .= $templater->render();	
		}
		
		$templater = vB_Template::create('dbtech_vbactivity_options_bit');
			$templater->register('headerphrase', preg_replace('/<dfn>.*<\/dfn>/isU', '', $vbphrase["$headerphrase"]));
			$templater->register('optionbits2', $optionbits2);
		$optionbits .= $templater->render();	
	}
	
	$selected = array(
		'stats' => array(
			$vbulletin->userinfo['dbtech_vbactivity_autocollapse_stats'] => ' selected="selected"'
		),
		'bar' => array(
			$vbulletin->userinfo['dbtech_vbactivity_autocollapse_bar'] => ' selected="selected"'
		)
	);
	
	// Include the page template
	$page_templater = vB_Template::create('dbtech_vbactivity_options');
		$page_templater->register('optionbits', $optionbits);
		$page_templater->register('selected', $selected);		
}

// ############################### start save options ##################################
if ($_POST['action'] == 'updateoptions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'options'        		=> TYPE_ARRAY_BOOL,
		'set_options'    		=> TYPE_ARRAY_BOOL,
		'autocollapse_stats'	=> TYPE_INT,
		'autocollapse_bar' 		=> TYPE_INT,
	));
	
	// Grab all the bitfields we can
	require_once(DIR . '/includes/class_bitfield_builder.php');
	$bitfields = vB_Bitfield_Builder::return_data();
	
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	
	// Add to userdata
	//$userdata->bitfields['dbtech_vbactivity_settings'] = array_merge($bitfields['nocache']['dbtech_vbactivity_general_settings'], $bitfields['nocache']['dbtech_vbactivity_display_settings']);
	$userdata->bitfields['dbtech_vbactivity_settings'] = $bitfields['nocache']['dbtech_vbactivity_display_settings'];
	
	/*DBTECH_PRO_START*/
	// Add notif settings
	$userdata->bitfields['dbtech_vbactivity_settings'] = array_merge($userdata->bitfields['dbtech_vbactivity_settings'], $bitfields['nocache']['dbtech_vbactivity_notification_settings']);
	/*DBTECH_PRO_END*/

	// options bitfield
	foreach ($userdata->bitfields['dbtech_vbactivity_settings'] AS $key => $val)
	{
		if (isset($vbulletin->GPC['options']["$key"]) OR isset($vbulletin->GPC['set_options']["$key"]))
		{
			$value = $vbulletin->GPC['options']["$key"];
			$userdata->set_bitfield('dbtech_vbactivity_settings', $key, $value);
		}
	}
	
	$userdata->set('dbtech_vbactivity_autocollapse_stats', $vbulletin->GPC['autocollapse_stats']);
	$userdata->set('dbtech_vbactivity_autocollapse_bar', $vbulletin->GPC['autocollapse_bar']);

	// Save the userdata
	$userdata->save();	
	
	$vbulletin->url = 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=profile&action=options';
	if (version_compare($vbulletin->versionnumber, '4.1.7') >= 0)
	{
		eval(print_standard_redirect(array('redirect_updatethanks', $vbulletin->userinfo['username'])));
	}
	else
	{
		eval(print_standard_redirect('redirect_updatethanks'));
	}
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
construct_usercp_nav('dbtech_vbactivity_' . $_REQUEST['action']);

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
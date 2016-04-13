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

// #############################################################################
if ($_REQUEST['action'] == 'permissions' OR empty($_REQUEST['action']))
{
	// Array of groups to include
	$includegroups = array('dbtech_livewallpermissions');
	
	// Ensure we can fetch bitfields
	require_once(DIR . '/includes/class_bitfield_builder.php');
	if (vB_Bitfield_Builder::build(false) !== false)
	{
		$myobj =& vB_Bitfield_Builder::init();
		if (sizeof($myobj->datastore_total['ugp']) != sizeof($vbulletin->bf_ugp))
		{
			$myobj->save($db);
			build_forum_permissions();
			define('CP_REDIRECT', $vbulletin->scriptpath);
			print_stop_message('rebuilt_bitfields_successfully');
		}
	}
	else
	{
		echo "<strong>error</strong>\n";
		print_r(vB_Bitfield_Builder::fetch_errors());
	}
	
	foreach ($myobj->data['ugp'] AS $grouptitle => $perms)
	{
		foreach ($perms AS $permtitle => $permvalue)
		{
			if (empty($permvalue['group']) OR !in_array($grouptitle, $includegroups))
			{
				continue;
			}
			
			if ($permvalue['intperm'])
			{
				continue;
				//$groupinfo[$permvalue['group']][$permtitle]['intperm'] = true;
			}
			
			$groupinfo[$permvalue['group']][$permtitle] = array('phrase' => $permvalue['phrase'], 'value' => $permvalue['value'], 'parentgroup' => $grouptitle);
			
			if (!empty($myobj->data['layout'][$permvalue['group']]['ignoregroups']))
			{
				$groupinfo[$permvalue['group']]['ignoregroups'] = $myobj->data['layout'][$permvalue['group']]['ignoregroups'];
			}
			if (!empty($permvalue['ignoregroups']))
			{
				$groupinfo[$permvalue['group']][$permtitle]['ignoregroups'] = $permvalue['ignoregroups'];
			}
			if (!empty($permvalue['options']))
			{
				$groupinfo[$permvalue['group']][$permtitle]['options'] = $permvalue['options'];
			}
		}
	}
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['usergroup'];
	
	foreach ($groupinfo AS $grouptitle => $group)
	{
		foreach ($group AS $permtitle => $permvalue)
		{
			// Permission is shown only if a particular option is enabled.
			if (isset($permvalue['options']) AND !$vbulletin->options[$permvalue['options']])
			{
				continue;
			}
	
			// Permission is hidden from specific groups
			if (isset($permvalue['ignoregroups']))
			{
				$ignoreids = explode(',', $permvalue['ignoregroups']);
				if (in_array($vbulletin->GPC['usergroupid'], $ignoreids))
				{
					continue;
				}
			}
			
			$headings[] = $vbphrase[$permvalue['phrase']];
		}
	}
	
	print_cp_header($vbphrase['dbtech_livewall_permissions']);
	echo '<style type="text/css">dfn { display:none; }</style>';
	print_form_header('livewall', 'permissions');
	construct_hidden_code('action', 'update');
	print_table_header($vbphrase['dbtech_livewall_permissions'], count($headings));
	print_cells_row($headings, 0, 'thead');
	
	foreach ($vbulletin->usergroupcache as $usergroupid => $usergroup)
	{
		$ug_bitfield = array();
		foreach($vbulletin->bf_ugp AS $permissiongroup => $fields)
		{
			$ug_bitfield[$permissiongroup] = convert_bits_to_array($usergroup[$permissiongroup], $fields);
		}
		
		// Table data
		$cell = array();
		$cell[] = $usergroup['title'];
		foreach ($groupinfo AS $grouptitle => $group)
		{
			foreach ($group AS $permtitle => $permvalue)
			{
				// Permission is shown only if a particular option is enabled.
				if (isset($permvalue['options']) AND !$vbulletin->options[$permvalue['options']])
				{
					continue;
				}
				
				/*
				// Permission is hidden from specific groups
				if (isset($permvalue['ignoregroups']))
				{
					$ignoreids = explode(',', $permvalue['ignoregroups']);
					if (in_array($vbulletin->GPC['usergroupid'], $ignoreids))
					{
						continue;
					}
				}
				*/
	
				$getval = $ug_bitfield[$permvalue['parentgroup']][$permtitle];
				
				$cell[] = '<center>
					<input type="hidden" name="permissions[' . $usergroupid . '][' . $permvalue['parentgroup'] . '][' . $permtitle . ']" value="0" />
					<input type="checkbox" name="permissions[' . $usergroupid . '][' . $permvalue['parentgroup'] . '][' . $permtitle . ']" value="1"' . ($getval ? ' checked="checked"' : '') . '/>
				</center>';
			}
		}
		
		// Print the data
		print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
	}
	
	print_submit_row($vbphrase['save'], false, count($headings));
}

// #############################################################################
if ($_POST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'permissions' => TYPE_ARRAY,
	));
	
	// create bitfield values
	require_once(DIR . '/includes/functions_misc.php');
	foreach ($vbulletin->GPC['permissions'] as $usergroupid => $permissions)
	{
		foreach($vbulletin->bf_ugp AS $permissiongroup => $fields)
		{
			if (is_array($permissions[$permissiongroup]))
			{
				// Generate the bitfield
				$permissions[$permissiongroup] = convert_array_to_bits($permissions[$permissiongroup], $fields, 1);
			}
		}
		
		// Update the usergroup
		$db->query_write(fetch_query_sql($permissions, 'usergroup', "WHERE usergroupid=" . $usergroupid));	
	}
	
	require_once(DIR . '/includes/functions_databuild.php');
	build_forum_permissions();
	
	define('CP_REDIRECT', 'livewall.php?do=permissions');
	print_stop_message('dbtech_livewall_x_y', $vbphrase['permissions'], $vbphrase['dbtech_livewall_edited']);
}

print_cp_footer();

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: instance.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>
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

if (!VBACTIVITY::$permissions['impex'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

$tables = array(
	'achievement', 'medal', 'trophy'
);

// #############################################################################
if ($_REQUEST['action'] == 'impex' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_impex']);
	
	print_form_header('vbactivity', 'impex', 1, 1, 'uploadform');
	construct_hidden_code('action', 'import');
	print_table_header($vbphrase['import']);
	print_upload_row($vbphrase['dbtech_vbactivity_upload_xml'], 		'productfile', 999999999);
	//print_yes_no_row($vbphrase['dbtech_vbactivity_allow_overwrite'], 	'allowoverwrite', 0);
	print_submit_row($vbphrase['import'], false);
	
	// Table header
	$headings = array();
	$headings[] = '';
	$headings[] = '';
	$headings[] = "<input type=\"checkbox\" name=\"allbox\" id=\"allbox\" title=\"$vbphrase[check_all]\" onclick=\"js_check_all(this.form);\" /><label for=\"allbox\">$vbphrase[check_all]</label>";
	
	//print_form_header('vbactivity', 'doexport');	
	print_form_header('vbactivity', 'impex', 0, 1, 'cpform');
	construct_hidden_code('action', 'export');
	print_table_header($vbphrase['dbtech_vbactivity_export_management'], count($headings));
	print_description_row($vbphrase['dbtech_vbactivity_export_management_descr'], false, count($headings));	
	print_cells_row($headings, 1);
	$nullcount = 0;
	foreach ($tables as $table)
	{
		$cache = array();
		if ($table == 'trophy')
		{
			$cache = VBACTIVITY::$cache['type'];
		}
		else
		{
			$cache = VBACTIVITY::$cache["$table"];
		}
		
		if (count($cache))
		{
			// Table header
			$headings = array();
			$headings[] = $vbphrase['title'];
			$headings[] = $vbphrase['description'];
			$headings[] = '';
			
			print_table_header($vbphrase["dbtech_vbactivity_{$table}"], count($headings));
			print_cells_row($headings, 1);
			//print_cells_row($headings, 0, 'thead');
			
			foreach ($cache as $featureid => $feature)
			{
				if ($table == 'trophy')
				{
					if (!$feature['active'] OR !($feature['settings'] & 4))
					{
						// Only active types
						continue;
					}
					$feature['typename'] = ($feature['typename'] == 'totalpoints' ? $feature['typename'] : 'per' . $feature['typename']);
				}
				
				// Table data
				$cell = array();
				$cell[] = ($table == 'trophy' ? ($feature['trophyname'] ? $feature['trophyname'] : $vbphrase["dbtech_vbactivity_condition_{$feature[typename]}"]) : $feature['title']);
				$cell[] = ($table == 'trophy' ? $vbphrase["dbtech_vbactivity_condition_{$feature[typename]}"] : $feature['description']);
				$cell[] = "<input type=\"checkbox\" name=\"export[{$table}][{$featureid}]\" id=\"exportlist_{$table}_{$featureid}\" title=\"{$table}[{$feature[title]}]\" value=\"1\" />";
				
				print_cells_row($cell);
				
				// Print the data
				//print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
			}
			print_table_break('');
		}
		else
		{
			// There was no data to export
			continue;
		}
	}
	print_submit_row($vbphrase['export'], false);	
}

// #############################################################################
if ($_POST['action'] == 'import')
{
	$vbulletin->input->clean_array_gpc('f', array(
		'productfile' => TYPE_FILE
	));
	
	if (file_exists($vbulletin->GPC['productfile']['tmp_name']))
	{
		// got an uploaded file?
		$xml = file_read($vbulletin->GPC['productfile']['tmp_name']);
	}
	else
	{
		print_stop_message('no_file_uploaded_and_no_local_file_found');
	}
	
	require_once(DIR . '/includes/class_xml.php');
	
	$xmlobj = new vB_XML_Parser($xml);
	if ($xmlobj->error_no == 1)
	{
		print_stop_message('no_xml_and_no_path');
	}
	
	if (!$arr = $xmlobj->parse())
	{
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}
	
	$categories = array();
	foreach (VBACTIVITY::$cache['category'] as $categoryid => $category)
	{
		$categories["$category[title]"] = $categoryid;
	}
	
	$achievements = array();
	foreach (VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
	{
		$achievements["$achievement[title]"] = $achievementid;
	}
	
	$medals = array();
	foreach (VBACTIVITY::$cache['medal'] as $medalid => $medal)
	{
		$medals["$medal[title]"] = $medalid;
	}
	
	if (is_array($arr['conditions']))
	{
		// We had some conditions
		$conditions = array();
		
		foreach ($arr['conditions'] as $feature => $featureinfo)
		{
			// We have conditions for this feature
			$conditions["$feature"] = array();
			
			foreach ($featureinfo as $condition)
			{
				// All the conditions for this feature id
				$idstring = substr($feature, 0, -1) . 'id';
				$conditions["$feature"]["$condition[$idstring]"] = array();
				
				if (is_array($condition['condition']))
				{
					foreach ($condition['condition'] as $conditiondata)
					{
						// Store the data
						$conditions["$feature"]["$condition[$idstring]"][] = unserialize($conditiondata);
					}
				}
				else
				{
					// Store the data
					$conditions["$feature"]["$condition[$idstring]"][] = unserialize($condition['condition']);
				}
			}
		}
		unset($arr['conditions']);
	}
	
	if (is_array($arr['categories']))
	{
		// We had some conditions
		$categorys = array();
		
		foreach ($arr['categories'] as $feature => $featureinfo)
		{
			// We have categorys for this feature
			$categorys["$feature"] = array();
			
			foreach ($featureinfo['category'] as $category)
			{
				// All the categorys for this feature id
				$idstring = substr($feature, 0, -1) . 'id';
				
				// Store the data
				$categorys["$feature"]["$category[$idstring]"] = unserialize($category['value']);
			}
		}
		unset($arr['categories']);
	}
	
	if (is_array($arr['achievements']))
	{
		$map = array();
		foreach ($arr['achievements']['achievement'] as $key => $achievementinfo)
		{
			$achievement = unserialize($achievementinfo['value']);
			$map["$achievementinfo[achievementid]"] = $achievement['title'];
		}
		
		foreach ($arr['achievements']['achievement'] as $key => $achievementinfo)
		{
			$achievement = unserialize($achievementinfo['value']);
			
			if ($achievements["$achievement[title]"])
			{
				continue;
			}
			
			$achievement['conditions'] = $conditions['achievements']["$achievementinfo[achievementid]"];
			$achievement['category'] = $categorys['achievements']["$achievementinfo[achievementid]"];
			
			$category_title = $achievement['category']['title'];
			if (!$categories["$category_title"])
			{
				// init data manager
				$dm =& VBACTIVITY::initDataManager('Category', $vbulletin, ERRTYPE_CP);
					$dm->set('title', 			$achievement['category']['title']);
					$dm->set('description', 	$achievement['category']['description']);
					$dm->set('displayorder', 	$achievement['category']['displayorder']);
				$categoryid = $dm->save();
				unset($dm);				
				
				// Cache the category id
				$categories["$category_title"] = $categoryid;
			}
			
			// Set the category id
			$achievement['categoryid'] = $categories["$category_title"];
			
			$parent_title = $map["{$achievement[parentid]}"];
			if ($achievements["$parent_title"])
			{
				$achievement['parentid'] = $achievements["$parent_title"];
			}
			else
			{
				$achievement['parentid'] = 0;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Achievement', $vbulletin, ERRTYPE_CP);
				$dm->set('categoryid', 		$achievement['categoryid']);
				$dm->set('parentid', 		$achievement['parentid']);
				$dm->set('title', 			$achievement['title']);
				$dm->set('description', 	$achievement['description']);
				$dm->set('icon', 			$achievement['icon']);
				$dm->set('displayorder', 	$achievement['displayorder']);
				$dm->set('sticky', 			$achievement['sticky']);
			$achievementid = $dm->save();
			unset($dm);			
			$achievements["$achievement[title]"] = $achievementid;
			
			$conditionbridge = array();
			foreach ($achievement['conditions'] as $condition)
			{
				if ($existing = $db->query_first_slave("
					SELECT conditionid FROM " . TABLE_PREFIX . "dbtech_vbactivity_condition
					WHERE `typeid` = " . $db->sql_prepare($condition['typeid']) . "
						AND `comparison` = " . $db->sql_prepare($condition['comparison']) . "
						AND `value` = " . $db->sql_prepare($condition['value']) . "
						AND `type` = " . $db->sql_prepare($condition['type`'])
				))
				{
					// This condition exists
					$conditionbridge[] = "(" . intval($existing['conditionid']) . ", 'achievement', " . $achievementid . ")";
				}
				else
				{
					$dm =& VBACTIVITY::initDataManager('Condition', $vbulletin, ERRTYPE_CP);
						$dm->set('typeid', 		$condition['typeid']);
						$dm->set('comparison', 	$condition['comparison']);
						$dm->set('value', 		$condition['value']);
						$dm->set('type', 		$condition['type']);
					$conditionid = $dm->save();
					unset($dm);			
					
					// This condition exists
					$conditionbridge[] = "(" . intval($conditionid) . ", 'achievement', " . $achievementid . ")";				
				}
			}
			
			if (count($conditionbridge))
			{
				// Insert new condition bridges
				$db->query_write("
					INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
						(`conditionid`, `feature`, `featureid`)
					VALUES
						" . implode(',', $conditionbridge)
				);
				
				// Rebuild the cache
				VBACTIVITY_CACHE::build('conditionbridge');
			}
		}
	}
	
	if (is_array($arr['medals']))
	{
		// We had some achievements
		$medals = array();
		
		foreach ($arr['medals']['medal'] as $key => $medalinfo)
		{
			$medal = unserialize($medalinfo['value']);
			
			if ($medals["$medal[title]"])
			{
				continue;
			}
			
			// Grab the medal info
			$medal['category'] = $categorys['medals']["$medalinfo[medalid]"];
	
			$category_title = $medal['category']['title'];
			if (!$categories["$category_title"])
			{
				// init data manager
				$dm =& VBACTIVITY::initDataManager('Category', $vbulletin, ERRTYPE_CP);
					$dm->set('title', 			$medal['category']['title']);
					$dm->set('description', 	$medal['category']['description']);
					$dm->set('displayorder', 	$medal['category']['displayorder']);
				$categoryid = $dm->save();
				unset($dm);				
				
				// Cache the category id
				$categories["$category_title"] = $categoryid;
			}
			
			// Set the category id
			$medal['categoryid'] = $categories["$category_title"];
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Medal', $vbulletin, ERRTYPE_CP);
				$dm->set('categoryid', 		$medal['categoryid']);
				$dm->set('title', 			$medal['title']);
				$dm->set('description', 	$medal['description']);
				$dm->set('icon', 			$medal['icon']);
				$dm->set('displayorder', 	$medal['displayorder']);
				$dm->set('sticky', 			$medal['sticky']);
			$medalid = $dm->save();
			unset($dm);			
		}
	}
	
	if (is_array($arr['trophys']))
	{
		// We had some achievements
		$medals = array();
		
		foreach ($arr['trophys']['trophy'] as $key => $trophyinfo)
		{
			// Grab the trophy info
			$trophys["$trophyinfo[trophyid]"] = unserialize($trophyinfo['value']);
		}
		
		foreach ($trophys as $trophyid => $trophy)
		{
			if (!$existing = VBACTIVITY::$cache['type'][VBACTIVITY::fetch_type($trophy['typename'])])
			{
				// Porbs related to an addon that we dont have. fuck this
				continue;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Type', $vbulletin, ERRTYPE_CP);
				$dm->set_existing($existing);
				$dm->set('trophyname', 	$trophy['trophyname']);
				$dm->set('icon', 		$trophy['icon']);
			$dm->save();
			unset($dm);			
		}
	}
		
	define('CP_REDIRECT', 'vbactivity.php?do=impex');
	print_stop_message('dbtech_vbactivity_xml_imported');
}

// #############################################################################
if ($_POST['action'] == 'export')
{
	$vbulletin->input->clean_array_gpc('p', array('export' => TYPE_ARRAY));
	
	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	
	// Parent for features
	$xml->add_group('vbactivity');
	
	$dependency = array();
	foreach ($vbulletin->GPC['export'] as $table => $features)
	{
		$cache = array();
		if ($table == 'trophy')
		{
			$cache = VBACTIVITY::$cache['type'];
		}
		else
		{
			$cache = VBACTIVITY::$cache["$table"];
		}
		
		// Add the table group
		$xml->add_group($table . 's');
		
		foreach ($features as $featureid => $onoff)
		{
			if (!$onoff)
			{
				// For some reason
				continue;
			}
			
			// Shorthand
			$feature = $cache["$featureid"];
			
			if ($table == 'achievement')
			{
				// Fetch all conditions
				$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'achievement');
				$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $featureid);
				
				foreach ((array)$conditioninfo as $condition)
				{
					// Add dependency
					$dependency['condition']["$featureid"]["$condition[conditionid]"] = true;
				}
				
				// Add category dependaency
				$dependency['category']['achievement']["$featureid"]["$feature[categoryid]"] = true;
			}
			
			if ($table == 'medal')
			{
				// Add category dependaency
				$dependency['category']['medal']["$featureid"]["$feature[categoryid]"] = true;
			}
			
			switch ($table)
			{
				case 'achievement':
				case 'medal':
					$arr = array(
						'categoryid' 	=> $feature['categoryid'],
						'title' 		=> $feature['title'],
						'description' 	=> $feature['description'],
						'icon' 			=> $feature['icon'],
						'displayorder' 	=> $feature['displayorder'],
						'sticky' 		=> $feature['sticky']
					);
					if ($table == 'achievement')
					{
						$arr['parentid'] = $feature['parentid'];
					}
					$xml->add_tag($table, serialize($arr), array($table . 'id' => $featureid));
					break;
					
				case 'trophy':
					$xml->add_tag('trophy', serialize(array(
						'typename' 		=> $feature['typename'],
						'trophyname' 	=> ($feature['trophyname'] ? $feature['trophyname'] : $feature['typename']),
						'icon' 			=> $feature['icon']
					)), array($table . 'id' => $featureid));
					break;
			}
		}
		
		// Close off the table group
		$xml->close_group();
	}
	
	// Add the main condition group
	$xml->add_group('conditions');
	foreach ($dependency['condition'] as $achievementid => $conditions)
	{
		// Add the achievements group
		$xml->add_group('achievements', array('achievementid' => $achievementid));
		
		foreach ($conditions as $conditionid => $onoff)
		{
			if (!$onoff)
			{
				// For some reason
				continue;
			}
			
			// Add the condition
			$xml->add_tag('condition', serialize(array(
				'typeid' 		=> VBACTIVITY::$cache['condition']["$conditionid"]['typeid'],
				'comparison' 	=> VBACTIVITY::$cache['condition']["$conditionid"]['comparison'],
				'value' 		=> VBACTIVITY::$cache['condition']["$conditionid"]['value'],
				'type' 			=> VBACTIVITY::$cache['condition']["$conditionid"]['type'],
			)));
		}
		// Close achievement
		$xml->close_group();
	}
	
	// Close conditions
	$xml->close_group();
	
	// Add the main category group
	$xml->add_group('categories');
	foreach ($dependency['category'] as $feature => $featureids)
	{
		// Add the achievements group
		$xml->add_group($feature . 's');
		
		foreach ($featureids as $featureid => $categoryids)
		{
			foreach ($categoryids as $categoryid => $onoff)
			{
				if (!$onoff)
				{
					// For some reason
					continue;
				}
				
				// Add the condition
				$xml->add_tag('category', serialize(array(
					'title' 		=> VBACTIVITY::$cache['category']["$categoryid"]['title'],
					'description' 	=> VBACTIVITY::$cache['category']["$categoryid"]['description'],
					'displayorder' 	=> VBACTIVITY::$cache['category']["$categoryid"]['displayorder']
				)), array($feature . 'id' => $featureid));
			}
		}
		
		// Close achievement
		$xml->close_group();	
	}
	
	// Close conditions
	$xml->close_group();
	
	// Close the core group
	$xml->close_group();
	
	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n" . $xml->output();
	unset($xml);
	
	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'vbactivity-export.xml', 'text/xml');
}

print_cp_footer();
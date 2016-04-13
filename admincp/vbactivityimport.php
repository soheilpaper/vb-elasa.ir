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
define('THIS_SCRIPT', 'vbactivity');
define('CVS_REVISION', '$RCSfile: vbactivity.php,v $ - $Revision: $WCREV$ $');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('dbtech_vbactivity', 'cphome', 'logging', 'threadmanage', 'banning', 'cpuser', 'cpoption', 'cppermission');

// get special data templates from the datastore
$specialtemplates = array(
	'dbtech_vbactivity_achievement',
	'dbtech_vbactivity_activitylevel',
	'dbtech_vbactivity_category',
	'dbtech_vbactivity_condition',
	'dbtech_vbactivity_conditionbridge',
	'dbtech_vbactivity_medal',		
	'dbtech_vbactivity_type',
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS ######################
if (!can_administer('canadminvbactivity'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ##############################
log_admin_action();

// ############################# VBPHRASE   ##############################
$vbphrase['dbtech_vbactivity_importer'] = 'YAAS to DragonByte Tech: vBActivity v5.x';
$vbphrase['dbtech_vbactivity_entries_per_page'] = 'Awards per page';
$vbphrase['dbtech_vbactivity_importing_awards'] = 'Importing Awards';
$vbphrase['dbtech_vbactivity_awards_imported'] = 'Awards imported successfully!';

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

require_once(DIR . '/includes/class_dbalter.php');
$db_alter = new vB_Database_Alter_MySQL($db);

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

print_cp_header($vbphrase['dbtech_vbactivity_importer']);
switch ($action)
{
	case 'main':
		print_form_header('vbactivityimport', 'doimport');	
		print_table_header($vbphrase['dbtech_vbactivity_importer']);
		print_input_row($vbphrase['dbtech_vbactivity_entries_per_page'], 'perpage', 15);
		print_submit_row($vbphrase['submit'], 0);
		break;
		
	case 'doimport':
		$vbulletin->input->clean_array_gpc('r', array(
			'perpage' 		=> TYPE_UINT,
			'startat' 		=> TYPE_UINT
		));
	
		if (empty($vbulletin->GPC['perpage']))
		{
			$vbulletin->GPC['perpage'] = 15;
		}
		
		echo '<p>' . $vbphrase['dbtech_vbactivity_importing_awards'] . '...</p>';
		
		if (empty($vbulletin->GPC['startat']))
		{
			$awards = $db->query_read_slave("
				SELECT award.*, award_cat.*
				FROM " . TABLE_PREFIX . "award AS award
				LEFT JOIN " . TABLE_PREFIX . "award_cat AS award_cat USING(award_cat_id)
			");
			while ($info = $db->fetch_array($awards))
			{
				// Add the dbtech_vbactivity_rewards field
				if (!isset($info['__imported']))
				{
					if ($db_alter->fetch_table_info('award'))
					{
						$db_alter->add_field(array(
							'name'       => '__imported',
							'type'       => 'tinyint',
							'length'     => '1',
							'attributes' => 'unsigned',
							'null'       => false,	// True = NULL, false = NOT NULL
							'default'    => '0'
						));				
					}
					$info['__imported'] = 0;
				}
				
				if ($info['__imported'])
				{
					// Already imported
					continue;
				}
				
				if (!$existing = $db->query_first_slave("SELECT categoryid FROM " . TABLE_PREFIX . "dbtech_vbactivity_category WHERE title = " . $db->sql_prepare($info['award_cat_title'])))
				{
					// Update the database
					$db->query_write("
						INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_category`
							(`title`, `description`, `displayorder`)
						VALUES
							(
								" . $db->sql_prepare($info['award_cat_title']) . ",
								" . $db->sql_prepare($info['award_cat_desc']) . ",
								" . $db->sql_prepare($info['award_cat_displayorder']) . "
							)
					");
					$existing = array('categoryid' => $db->insert_id());
				}
				
				// Update the database
				$db->query_write("
					INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_medal`
						(`categoryid`, `title`, `description`, `displayorder`)
					VALUES
						(
							" . $db->sql_prepare($existing['categoryid']) . ",
							" . $db->sql_prepare($info['award_name']) . ",
							" . $db->sql_prepare($info['award_desc']) . ",
							" . $db->sql_prepare($info['award_displayorder']) . "
						)
				");
				
				$db->query_write("UPDATE " . TABLE_PREFIX . "award SET __imported = 1 WHERE award_id = " . intval($info['award_id']));
			}
		}
		
		$userawards = $db->query_read_slave("
			SELECT award_user.*, award.award_name
			FROM " . TABLE_PREFIX . "award_user AS award_user
			LEFT JOIN " . TABLE_PREFIX . "award AS award USING(award_id)
			WHERE issue_id >= " . $vbulletin->GPC['startat'] . "
			ORDER BY issue_id
			LIMIT " . $vbulletin->GPC['perpage']
		);
		$finishat = $vbulletin->GPC['startat'];
		
		while ($info = $db->fetch_array($userawards))
		{
			echo construct_phrase($vbphrase['processing_x'], $info['issue_id']) . "<br />\n";
			vbflush();
			
			// Add the dbtech_vbactivity_rewards field
			if (!isset($info['__imported']))
			{
				if ($db_alter->fetch_table_info('award_user'))
				{
					$db_alter->add_field(array(
						'name'       => '__imported',
						'type'       => 'tinyint',
						'length'     => '1',
						'attributes' => 'unsigned',
						'null'       => false,	// True = NULL, false = NOT NULL
						'default'    => '0'
					));				
				}
				$info['__imported'] = 0;
			}
			
			if ($info['__imported'])
			{
				// Already imported
				continue;
			}
			
			$medal = $db->query_first_slave("SELECT medalid FROM " . TABLE_PREFIX . "dbtech_vbactivity_medal WHERE title = " . $db->sql_prepare($info['award_name']));
			
			// For rewards cache building
			$userinfo['userid'] = $info['userid'];
			
			// Grant the reward to the user
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_rewards
					(userid, feature, featureid, dateline)
				VALUES (
					" . $info['userid'] . ",
					'medal',
					" . $medal['medalid'] . ",
					" . $info['issue_time'] . "
				)
			");
			
			/*
			if (class_exists('VBACTIVITY'))
			{
				// Add a notification
				VBACTIVITY::add_notification('medal', $info['userid']);
			}
			else
			{
				// Add a notification
				$vbactivity->add_notification('medal', $info['userid']);
			}
			*/
			
			if (class_exists('VBACTIVITY'))
			{
				// Add a notification
				VBACTIVITY::build_rewards_cache($userinfo);
			}
			else
			{
				// Build the rewards cache for this user
				$vbactivity->build_rewards_cache($userinfo);
			}
			
			
			$db->query_write("UPDATE " . TABLE_PREFIX . "award_user SET __imported = 1 WHERE issue_id = " . intval($info['issue_id']));
		
			$finishat = ($info['issue_id'] > $finishat ? $info['issue_id'] : $finishat);
		}
		
		$finishat++;
		
		if ($checkmore = $db->query_first_slave("SELECT issue_id FROM " . TABLE_PREFIX . "award_user WHERE issue_id >= $finishat LIMIT 1"))
		{
			print_cp_redirect("vbactivityimport.php?" . $vbulletin->session->vars['sessionurl'] . "do=doimport&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
			echo "<p><a href=\"vbactivityimport.php?" . $vbulletin->session->vars['sessionurl'] . "do=doimport&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
		}
		else
		{
			// Rebuild the cache
			if (class_exists('VBACTIVITY_CACHE'))
			{
				VBACTIVITY_CACHE::buildAll();
			}
			else
			{
				$vbactivity->build_cache('dbtech_vbactivity_category', 'ORDER BY `displayorder` ASC');					
				$vbactivity->build_cache('dbtech_vbactivity_medal', 'LEFT JOIN ' . TABLE_PREFIX . 'dbtech_vbactivity_category AS category ON (category.categoryid = dbtech_vbactivity_medal.categoryid)
					ORDER BY category.displayorder, dbtech_vbactivity_medal.displayorder ASC
				');
			}
			
			print_cp_message($vbphrase['dbtech_vbactivity_shouts_imported'], 'vbactivityimport.php?do=main');
		}

		break;
}
print_cp_footer();

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: vbactivity.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>
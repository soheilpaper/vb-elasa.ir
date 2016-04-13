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

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!$vbulletin->options['dbtech_vbactivity_enable_achievements'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $vbphrase['dbtech_vbactivity_achievement_directory'];

// draw cp nav bar
VBACTIVITY::setNavClass('allachievements');

$vbulletin->input->clean_array_gpc('r', array(
	'achievementid'  	=> TYPE_UINT
));

// Grab the info
$info = VBACTIVITY::$cache['achievement'][$vbulletin->GPC['achievementid']];

$cacheResult = VBACTIVITY_CACHE::read('allachievements', 'allachievements.others');
if (!is_array($cacheResult))
{
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();

	$numholders = VBACTIVITY::$db->fetchAll('
		SELECT COUNT(*) AS totalusers, featureid
		FROM $dbtech_vbactivity_rewards AS rewards
		LEFT JOIN $user AS user ON (user.userid = rewards.userid)
		WHERE user.dbtech_vbactivity_excluded_tmp = \'0\'
			AND feature = \'achievement\'
		GROUP BY featureid
	', array(
		':vBShop' => ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : '')
	));

	if ($cacheResult != -1)
	{
		// Write to the cache
		VBACTIVITY_CACHE::write($numholders, 'allachievements', 'allachievements.others');
	}
}
else
{
	// Set the entry cache
	$numholders = $cacheResult;
}

$others = array();
foreach ($numholders as $holder)
{
	$others[$holder['featureid']] = $holder['totalusers'];
}

if ($vbulletin->userinfo['userid'])
{
	$cacheResult = VBACTIVITY_CACHE::read('allachievements', 'allachievements.self.' . $vbulletin->userinfo['userid']);
	if (!is_array($cacheResult))
	{
		// Set the excluded parameters
		VBACTIVITY::set_excluded_param();

		$achievements_q = VBACTIVITY::$db->fetchAll('
			SELECT
				featureid AS achievementid,
				user.userid,
				username,
				user.usergroupid,
				infractiongroupid,
				displaygroupid
				:vBShop
			FROM $userlist AS userlist
			LEFT JOIN $dbtech_vbactivity_rewards AS rewards ON (rewards.userid = userlist.relationid)
			LEFT JOIN $user AS user ON (user.userid = rewards.userid)
			WHERE user.dbtech_vbactivity_excluded_tmp = \'0\'
				AND feature = \'achievement\'
				AND userlist.userid = ?
				AND type = \'buddy\'
				AND friend = \'yes\'
			ORDER BY username ASC
		', array(
			':vBShop' => ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : ''),
			$vbulletin->userinfo['userid']
		));

		if ($cacheResult != -1)
		{
			// Write to the cache
			VBACTIVITY_CACHE::write($achievements_q, 'allachievements', 'allachievements.self.' . $vbulletin->userinfo['userid']);
		}
	}
	else
	{
		// Set the entry cache
		$achievements_q = $cacheResult;
	}
			
	$friends = array();
	foreach ($achievements_q as $achievements_r)
	{
		// Grab the extended username
		fetch_musername($achievements_r);
		
		$link = 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $achievements_r['userid'] . '&amp;tab=vbactivity';
		
		// Create a link to profile
		$link = '<a href="' . $link . '" target="_blank">' . $achievements_r['musername'] . '</a>';
		
		// Store the array sorted by level
		$friends[$achievements_r['achievementid']][] = $link;
		
		// Decrement the "others" count
		$others[$achievements_r['achievementid']]--;
	}
}

$achievements_by_category = array();
foreach ((array)VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
{
	// Index by categoryid
	$achievements_by_category[$achievement['categoryid']][$achievementid] = $achievement;
}

// Ensure our rewards cache is up to specs
VBACTIVITY::verify_rewards_cache($vbulletin->userinfo);

// Store better rewards cache
$rewardscache = array(
	'medal' => array(),
	'achievement' => array(),
);

foreach ($vbulletin->userinfo['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
{
	if (!$rewardscache[$reward['feature']][$reward['featureid']] OR $rewardscache[$reward['feature']][$reward['featureid']] < $reward['dateline'])
	{
		// Store dateline
		$rewardscache[$reward['feature']][$reward['featureid']] = $reward['dateline'];
	}
}

if (count($achievements_by_category))
{
	if (intval($vbulletin->versionnumber) == 3)
	{
		$HTML = '<table class="tborder" cellpadding="' . $stylevar['cellpadding'] . '" cellspacing="' . $stylevar['cellspacing'] . '" width="100%" border="0">';
	}
	foreach ($achievements_by_category as $categoryid => $achievements)
	{
		$templater = vB_Template::create('dbtech_vbactivity_categorybits');
			$templater->register('expand', true);
			$templater->register('category', VBACTIVITY::$cache['category'][$categoryid]);
		
		$contents = '';
		foreach ($achievements as $achievementid => $achievement)
		{
			if ($vbulletin->userinfo['userid'])
			{
				// Check if we've earned this reward or not
				$info['earned'] = VBACTIVITY::check_feature('achievement', $achievementid, $vbulletin->userinfo);
			}
			
			// Condition data
			$conditions = array();
			
			if ($vbulletin->options['dbtech_vbactivity_show_criteria_achievement'])
			{
				// Fetch all conditions
				$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'achievement');
				$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $achievementid);
				foreach ($conditioninfo as $condition)
				{
					// Shorthand
					$condition 	= VBACTIVITY::$cache['condition'][$condition['conditionid']];
					$typename 	= VBACTIVITY::$cache['type'][$condition['typeid']]['typename'];

					// Inititalise this type
					VBACTIVITY::init_type(VBACTIVITY::$cache['type'][$condition['typeid']]);
					
					// Add this condition
					$conditions[] = ($vbulletin->userinfo['userid'] ? '<img src="images/icons/vbactivity/' . (VBACTIVITY::$types[$typename]->check_criteria($condition['conditionid'], $vbulletin->userinfo) ? 'met' : 'notmet') . '.png" alt="" /> ' : '') . 
						$vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '');
				}
			}
			
			
			// Check if we've earned this reward or not
			if ($rewardscache['achievement'][$achievementid])
			{
				// Store time earned
				$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_achievement_earned_x'], vbdate($vbulletin->options['timeformat'], $rewardscache['achievement'][$achievementid]) . ' ' . vbdate($vbulletin->options['dateformat'], $rewardscache['achievement'][$achievementid])) . '</span>';
			}
			
			if (!empty($friends[$achievementid]))
			{
				if ($others[$achievementid])
				{
					// We have franz with this achievement
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_friends_y_others_have_this_achievement'], implode(', ', $friends[$achievementid]), vb_number_format($others[$achievementid])) . '</span>';
				}
				else
				{
					// We have franz with this achievement
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_friends_have_this_achievement'], implode(', ', $friends[$achievementid])) . '</span>';
				}
			}
			else
			{
				if ($others[$achievementid])
				{
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_people_have_this_achievement'], vb_number_format($others[$achievementid])) . '</span>';
				}
			}
						
			// Ensure we got an untouched title
			$achievement['title_clean'] = $achievement['title_translated'];
			
			$feature = 'achievement';
			$featureinfo =& $$feature;
		
			/*DBTECH_PRO_START*/
			require(DIR . '/dbtech/vbactivity_pro/includes/actions/feature.php');
			/*DBTECH_PRO_END*/
			
			$templaterr = vB_Template::create('dbtech_vbactivity_contentbits');
				$templaterr->register('icon', ($achievement['icon'] ? '<img src="images/icons/vbactivity/' . $achievement['icon'] . '" alt="' . $achievement['title_clean'] . '" /> ' : '') . '<b>' . $achievement['title_translated'] . '</b>');
				$templaterr->register('description', $achievement['description_translated']);
				$templaterr->register('conditions', implode('<br />', $conditions));
				$templaterr->register('extracss', ($achievementid == $info['achievementid'] ? ' highlight' : ''));
			$contents .= $templaterr->render();
		}
			$templater->register('contents', $contents);
		$HTML .= $templater->render();
	}
	if (intval($vbulletin->versionnumber) == 3)
	{
		$HTML .= '</table>';
	}
}
else
{
	$HTML = $vbphrase['dbtech_vbactivity_no_achievements_frontend'];
}
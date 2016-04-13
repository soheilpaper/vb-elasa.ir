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

if (!$vbulletin->options['dbtech_vbactivity_enable_achievements'] OR !$vbulletin->options['dbtech_vbactivity_show_criteria_achievement'] OR !$vbulletin->userinfo['userid'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $vbphrase['dbtech_vbactivity_achievement_targets'];

// draw cp nav bar
VBACTIVITY::setNavClass('achievtargets');

// Ensure our rewards cache is up to specs
VBACTIVITY::verify_rewards_cache($vbulletin->userinfo);

// Store better rewards cache
$rewardscache = array(
	'medal' => array(),
	'achievement' => array(),
);

foreach ($vbulletin->userinfo['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
{
	if (!$rewardscache["$reward[feature]"]["$reward[featureid]"] OR $rewardscache["$reward[feature]"]["$reward[featureid]"] < $reward['dateline'])
	{
		// Store dateline
		$rewardscache["$reward[feature]"]["$reward[featureid]"] = $reward['dateline'];
	}
}

$achievements_by_category = array();
foreach (VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
{
	// Index by categoryid
	$achievements_by_category["$achievement[categoryid]"]["$achievementid"] = $achievement;
}

if (count($achievements_by_category))
{
	foreach ($achievements_by_category as $categoryid => $achievements)
	{
		$templater = vB_Template::create('dbtech_vbactivity_categorybits');
			$templater->register('expand', true);
			$templater->register('category', VBACTIVITY::$cache['category']["$categoryid"]);
		
		$contents = '';
		$haveachiev = false;
		foreach ($achievements as $achievementid => $achievement)
		{
			/*
			if ($rewardscache['achievement']["$achievement[parentid]"] OR !$achievement['parentid'])
			{
				// We've earned this achievement's parent
				continue;
			}
			*/
			
			if (VBACTIVITY::check_feature('achievement', $achievementid, $vbulletin->userinfo))
			{
				// We earned this achievement, go to the next one
				continue;
			}
			
			// Condition data
			$conditions = array();
			
			// Fetch all conditions
			$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'achievement');
			$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $achievementid);
			foreach ($conditioninfo as $condition)
			{
				// Shorthand
				$condition 	= VBACTIVITY::$cache['condition']["$condition[conditionid]"];
				$typename 	= VBACTIVITY::$cache['type']["$condition[typeid]"]['typename'];
				$typename	= ($condition['type'] == 'points' ? 'per' . $typename  : $typename) . ($condition['forumid'] ? '_forumid_' . $condition['forumid'] : '');
				
				// Add this condition
				$conditions[] = '<img src="images/icons/vbactivity/' . (VBACTIVITY::check_criteria($condition['conditionid'], $vbulletin->userinfo) ? 'met' : 'notmet') . '.png" alt="" /> ' . $vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '') . '<br />' . construct_phrase($vbphrase['dbtech_vbactivity_you_have_x'], $vbulletin->userinfo["{$typename}"]);
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
			$contents = $templaterr->render();
			
			break;
		}
		
		if (!$contents)
		{
			// Skip this category, we have it all
			continue;
		}
		
			$templater->register('contents', $contents);
		$HTML .= $templater->render();
		
		if (!VBACTIVITY::$isPro)
		{
			// Lite only shows 1 category
			break;
		}
	}
	
	if (!$HTML)
	{
		$HTML = $vbphrase['dbtech_vbactivity_no_achievement_targets_frontend'];
	}
	else
	{
		if (intval($vbulletin->versionnumber) == 3)
		{
			$HTML = '<table class="tborder" cellpadding="' . $stylevar['cellpadding'] . '" cellspacing="' . $stylevar['cellspacing'] . '" width="100%" border="0">' . $HTML . '</table>';
		}
	}
}
else
{
	$HTML = $vbphrase['dbtech_vbactivity_no_achievements_frontend'];
}
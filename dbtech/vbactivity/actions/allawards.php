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

if (!$vbulletin->options['dbtech_vbactivity_enable_medals'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $vbphrase['dbtech_vbactivity_medal_directory'];

// draw cp nav bar
VBACTIVITY::setNavClass('allawards');

$vbulletin->input->clean_array_gpc('r', array(
	'medalid'  	=> TYPE_UINT
));

// Grab the info
$info = VBACTIVITY::$cache['medal'][$vbulletin->GPC['medalid']];

$cacheResult = VBACTIVITY_CACHE::read('allawards', 'allawards.others');
if (!is_array($cacheResult))
{
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();

	$numholders = VBACTIVITY::$db->fetchAll('
		SELECT COUNT(*) AS totalusers, featureid
		FROM $dbtech_vbactivity_rewards AS rewards
		LEFT JOIN $user AS user ON (user.userid = rewards.userid)
		WHERE user.dbtech_vbactivity_excluded_tmp = \'0\'
			AND feature = \'medal\'
		GROUP BY featureid
	', array(
		':vBShop' => ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : '')
	));

	if ($cacheResult != -1)
	{
		// Write to the cache
		VBACTIVITY_CACHE::write($numholders, 'allawards', 'allawards.others');
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
	$cacheResult = VBACTIVITY_CACHE::read('allawards', 'allawards.self.' . $vbulletin->userinfo['userid']);
	if (!is_array($cacheResult))
	{
		// Set the excluded parameters
		VBACTIVITY::set_excluded_param();

		$awards_q = VBACTIVITY::$db->fetchAll('
			SELECT
				featureid AS awardid,
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
				AND feature = \'medal\'
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
			VBACTIVITY_CACHE::write($awards_q, 'allawards', 'allawards.self.' . $vbulletin->userinfo['userid']);
		}
	}
	else
	{
		// Set the entry cache
		$awards_q = $cacheResult;
	}
			
	$friends = array();
	foreach ($awards_q as $awards_r)
	{
		// Grab the extended username
		fetch_musername($awards_r);
		
		$link = 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $awards_r['userid'] . '&amp;tab=vbactivity';
		
		// Create a link to profile
		$link = '<a href="' . $link . '" target="_blank">' . $awards_r['musername'] . '</a>';
		
		// Store the array sorted by level
		$friends[$awards_r['awardid']][] = $link;
		
		// Decrement the "others" count
		$others[$awards_r['awardid']]--;
	}
}

$medals_by_category = array();
foreach (VBACTIVITY::$cache['medal'] as $medalid => $medal)
{
	// Index by categoryid
	$medals_by_category[$medal['categoryid']][$medalid] = $medal;
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

if (count($medals_by_category))
{
	if (intval($vbulletin->versionnumber) == 3)
	{
		$HTML = '<table class="tborder" cellpadding="' . $stylevar['cellpadding'] . '" cellspacing="' . $stylevar['cellspacing'] . '" width="100%" border="0">';
	}	
	foreach ($medals_by_category as $categoryid => $medals)
	{
		$templater = vB_Template::create('dbtech_vbactivity_categorybits');
			$templater->register('expand', true);
			$templater->register('category', VBACTIVITY::$cache['category'][$categoryid]);
		
		$contents = '';
		foreach ($medals as $medalid => $medal)
		{
			// Condition data
			$conditions = array();

			// Check if we've earned this reward or not
			if ($rewardscache['medal'][$medalid])
			{
				// Store time earned
				$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_medal_earned_x'], vbdate($vbulletin->options['timeformat'], $rewardscache['medal'][$medalid]) . ' ' . vbdate($vbulletin->options['dateformat'], $rewardscache['medal'][$medalid])) . '</span>';
			}
			
			if (!empty($friends[$medalid]))
			{
				if ($others[$medalid])
				{
					// We have franz with this medal
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_friends_y_others_have_this_medal'], implode(', ', $friends[$medalid]), vb_number_format($others[$medalid])) . '</span>';
				}
				else
				{
					// We have franz with this medal
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_friends_have_this_medal'], implode(', ', $friends[$medalid])) . '</span>';
				}
			}
			else
			{
				if ($others[$medalid])
				{
					$conditions[] = '<span class="smallfont">' . construct_phrase($vbphrase['dbtech_vbactivity_x_people_have_this_medal'], vb_number_format($others[$medalid])) . '</span>';
				}
			}
			
			$canrequest 	= (($vbulletin->userinfo['permissions']['dbtech_vbactivitypermissions'] & $vbulletin->bf_ugp_dbtech_vbactivitypermissions['canrequestmedal']) AND 
				!($vbulletin->userinfo['permissions']['dbtech_vbactivity_requestdelay'] AND $requests['request'] >= (TIMENOW - ($vbulletin->userinfo['permissions']['dbtech_vbactivity_requestdelay'] * 86400))) AND
				$vbulletin->userinfo['userid'] AND 
				($medal['availability'] & 1)
			);
			$cannominate 	= (($vbulletin->userinfo['permissions']['dbtech_vbactivitypermissions'] & $vbulletin->bf_ugp_dbtech_vbactivitypermissions['cannominatemedal']) AND
				!($vbulletin->userinfo['permissions']['dbtech_vbactivity_nominatedelay'] AND $requests['nominate'] >= (TIMENOW - ($vbulletin->userinfo['permissions']['dbtech_vbactivity_nominatedelay'] * 86400))) AND
				$vbulletin->userinfo['userid'] AND 
				($medal['availability'] & 2)
			);
			
			if ($canrequest OR $cannominate)
			{
				$conditions[] = '<span class="smallfont">
					' . ($canrequest ? '[<a href="vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=requestmedal&amp;medalid=' . $medalid . '">' . $vbphrase['dbtech_vbactivity_request_medal'] . '</a>] ' : '') . 
					($cannominate ? '[<a href="vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=nominatemedal&amp;medalid=' . $medalid . '">' . $vbphrase['dbtech_vbactivity_nominate_for_medal'] . '</a>]' : '') . '
				</span>';
			}

			// Clean title
			$medal['title_clean'] = $medal['title_translated'];
			
			$feature = 'medal';
			$featureinfo =& $$feature;
		
			/*DBTECH_PRO_START*/
			require(DIR . '/dbtech/vbactivity_pro/includes/actions/feature.php');
			/*DBTECH_PRO_END*/
			
			$templaterr = vB_Template::create('dbtech_vbactivity_contentbits');
				$templaterr->register('icon', ($medal['icon'] ? '<img src="images/icons/vbactivity/' . $medal['icon'] . '" alt="' . $medal['title_clean'] . '" /> ' : '') . '<b>' . $medal['title_translated'] . '</b>');
				$templaterr->register('description', $medal['description_translated']);
				$templaterr->register('conditions', implode('<br />', $conditions));
				$templaterr->register('extracss', ($medalid == $info['medalid'] ? ' highlight' : ''));
			$contents .= $templaterr->render();
			/*


			$medalusers = array();
			$count = array();
			foreach ((array)$users[$medalid] as $key => $user)
			{
				$link = 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $user['userid'] . '&amp;tab=vbactivity';
				
				// Create a link to profile
				$link = '<a href="' . $link . '" target="_blank">' . $user['musername'] . '</a>';
				
				if (!in_array($link, $medalusers))
				{
					$medalusers[$user['userid']] = $link;
					$count[$user['userid']]++;
				}
				else
				{
					$count[$user['userid']]++;
				}
			}
			
			foreach ($medalusers as $key => $username)
			{
				if ($count[$key] <= 1)
				{
					// We only want to change those with 2 or above
					continue;
				}
				
				// Update username
				$medalusers[$key] = $username . " ({$count[$key]})";
			}
			*/
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
	$HTML = $vbphrase['dbtech_vbactivity_no_medals_frontend'];
}
<?php
if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}

/*
$microtimes = array();
$microtimes['start'] = explode(' ', microtime());
$microtimes['start'] = $microtimes['start'][0] + $microtimes['start'][1];
*/

if (!VBACTIVITY::$postbitcache[$post['userid']] AND !$post['dbtech_vbactivity_excluded'])
{
	$vbactivity_postbit = array();
	if ($this->registry->options['dbtech_vbactivity_enable_stats'])
	{
		// Starter array
		$userinfo = array(
			'points' 			=> vb_number_format(round($post['dbtech_vbactivity_points'], 2)),
			'pointscache' 		=> round($post['dbtech_vbactivity_pointscache'], 2),
			'pointscache_day' 	=> $post['dbtech_vbactivity_pointscache_day'],
			'pointscache_week' 	=> $post['dbtech_vbactivity_pointscache_week'],
			'pointscache_month' => $post['dbtech_vbactivity_pointscache_month']
		);
		
		// Fetch activity level
		VBACTIVITY::fetch_activity_level($userinfo);
		VBACTIVITY::fetch_activity_rating($userinfo);
		
		$display = array(
			'stats' => 'block',
			'bar' 	=> 'block'
		);
		
		/*DBTECH_PRO_START*/
		$display = array(
			'stats' => ($this->registry->options['dbtech_vbactivity_autocollapse_stats'] ? 'none' : 'block'),
			'bar' => ($this->registry->options['dbtech_vbactivity_autocollapse_bar'] ? 'none' : 'block')
		);

		$userinfo['activity'] = '';

		if (!($this->registry->userinfo['dbtech_vbactivity_settings'] & 16))
		{
			// Create the activity info template
			$templater = vB_Template::create('dbtech_vbactivity_postbit_stats_activitybit');
				$templater->register('phrase', $vbphrase['dbtech_vbactivity_daily_activity']);
				$templater->register('pointscache', $userinfo['pointscache_day']);
				$templater->register('target', $userinfo['target']['daily_target']);
				$templater->register('percent', $userinfo['target']['daily']);
			$vbactivity_postbit['activity_daily'] = $templater->render();

			if ($this->registry->options['dbtech_vbactivity_activity_postbit'] & 1)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_daily'];
			}
			
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'red');
				$templater->register('percent', $userinfo['target']['daily_bar']);
			if (intval($vbulletin->versionnumber) == 3)
			{
				$vbactivity_postbit['activity_daily_bar'] = '<div>' . $templater->render() . '</div>';
			}
			else
			{
				$vbactivity_postbit['activity_daily_bar'] = '<dd>' . $templater->render() . '</dd>';
			}

			if ($this->registry->options['dbtech_vbactivity_bars_postbit'] & 1)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_daily_bar'];
			}
		}

		if (!($this->registry->userinfo['dbtech_vbactivity_settings'] & 32))
		{
			$templater = vB_Template::create('dbtech_vbactivity_postbit_stats_activitybit');
				$templater->register('phrase', $vbphrase['dbtech_vbactivity_weekly_activity']);
				$templater->register('pointscache', $userinfo['pointscache_week']);
				$templater->register('target', $userinfo['target']['weekly_target']);
				$templater->register('percent', $userinfo['target']['weekly']);
			$vbactivity_postbit['activity_weekly'] = $templater->render();

			if ($this->registry->options['dbtech_vbactivity_activity_postbit'] & 2)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_weekly'];
			}
			
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'grey');
				$templater->register('percent', $userinfo['target']['weekly_bar']);
			if (intval($vbulletin->versionnumber) == 3)
			{
				$vbactivity_postbit['activity_weekly_bar'] = '<div>' . $templater->render() . '</div>';
			}
			else
			{
				$vbactivity_postbit['activity_weekly_bar'] = '<dd>' . $templater->render() . '</dd>';
			}

			if ($this->registry->options['dbtech_vbactivity_bars_postbit'] & 2)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_weekly_bar'];
			}
		}

		if (!($this->registry->userinfo['dbtech_vbactivity_settings'] & 64))
		{
			$templater = vB_Template::create('dbtech_vbactivity_postbit_stats_activitybit');
				$templater->register('phrase', $vbphrase['dbtech_vbactivity_monthly_activity']);
				$templater->register('pointscache', $userinfo['pointscache_month']);
				$templater->register('target', $userinfo['target']['monthly_target']);
				$templater->register('percent', $userinfo['target']['monthly']);
			$vbactivity_postbit['activity_monthly'] = $templater->render();

			if ($this->registry->options['dbtech_vbactivity_activity_postbit'] & 4)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_monthly'];
			}

			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'green');
				$templater->register('percent', $userinfo['target']['monthly_bar']);
			if (intval($vbulletin->versionnumber) == 3)
			{
				$vbactivity_postbit['activity_monthly_bar'] = '<div>' . $templater->render() . '</div>';
			}
			else
			{
				$vbactivity_postbit['activity_monthly_bar'] = '<dd>' . $templater->render() . '</dd>';
			}

			if ($this->registry->options['dbtech_vbactivity_bars_postbit'] & 4)
			{
				$userinfo['activity'] .= $vbactivity_postbit['activity_monthly_bar'];
			}
		}
		/*DBTECH_PRO_END*/
		
		if ($this->registry->userinfo['userid'])
		{
			switch ($this->registry->userinfo['dbtech_vbactivity_autocollapse_stats'])
			{
				case 0:
					$display['stats'] = 'block';
					break;
					
				case 1:
					$display['stats'] = 'none';
					break;
			}
			
			switch ($this->registry->userinfo['dbtech_vbactivity_autocollapse_bar'])
			{
				case 0:
					$display['bar'] = 'block';
					break;
					
				case 1:
					$display['bar'] = 'none';
					break;
			}
		}
		
		// Create the Level bar
		$templater = vB_Template::create('dbtech_vbactivity_bar');
			$templater->register('post', $post);
			$templater->register('color', 'green');
			$templater->register('percent', $userinfo['levelpercent']);
		$activitylevel = $templater->render();
		
		if (intval($this->registry->versionnumber) == 3)
		{
			global $vbcollapse;
			
			$post['vbastatspostid'] = 'vbastatspostmenu_[postid]_table';
			$post['vbastatspostimgid'] = 'collapseimg_' . $post['vbastatspostid'];
			$post['vbastatscollapseobj'] = $vbcollapse["collapseobj_{$post[vbastatspostid]}"];
			$post['vbastatscollapseimg'] = $vbcollapse["collapseimg_{$post[vbastatspostid]}"];
			
			$post['vbabarspostid'] = 'vbabarspostmenu_[postid]_table';
			$post['vbabarspostimgid'] = 'collapseimg_' . $post['vbabarspostid'];
			$post['vbabarscollapseobj'] = $vbcollapse["collapseobj_{$post[vbabarspostid]}"];
			$post['vbabarscollapseimg'] = $vbcollapse["collapseimg_{$post[vbabarspostid]}"];		
		}
		
		// Create the main stats template
		$templater = vB_Template::create('dbtech_vbactivity_postbit_stats_points');
			$templater->register('userinfo', 		$userinfo);
		$vbactivity_postbit['activitystat_points'] = $templater->render();
		
		// Create the main stats template
		$templater = vB_Template::create('dbtech_vbactivity_postbit_stats_level');
			$templater->register('userinfo', 		$userinfo);
		$vbactivity_postbit['activitystat_level'] = $templater->render();
		
		// Create the main stats template
		$templater = vB_Template::create('dbtech_vbactivity_postbit_stats');
			$templater->register('post', 				$post);
			$templater->register('userinfo', 			$userinfo);
			$templater->register('activitylevel', 		$activitylevel);
			$templater->register('display', 			$display);
			$templater->register('post', 				$post);
			$templater->register('vbactivity_postbit', 	$vbactivity_postbit);
		$vbactivity_postbit['activitystat'] = $templater->render();
	}
	
	// Ensure we have everything in order
	VBACTIVITY::verify_rewards_cache($post);
	
	if (!is_array($post['dbtech_vbactivity_rewardscache']))
	{
		// Just to be 100% sure
		$post['dbtech_vbactivity_rewardscache'] = array();
	}
	krsort($post['dbtech_vbactivity_rewardscache'], SORT_NUMERIC);
	
	// Shorthand
	$rewardscache = array('achievement' => array(), 'medal' => array());
	
	foreach ($post['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
	{
		// Store this in a better format
		$rewardscache[$reward['feature']][$rewardid] = $reward;
	}

	switch ($this->registry->options['dbtech_vbactivity_awards_displayorder'])
	{
		case 'acp':
			// First we need to clear this cache
			unset($rewardscache['medal']);

			$tmpCache = array();
			foreach ($post['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
			{
				if ($reward['feature'] != 'medal')
				{
					// Skip this
					continue;
				}

				if (!isset($tmpCache[$reward['featureid']]))
				{
					// Begin indexing by medal ID
					$tmpCache[$reward['featureid']] = array();
				}

				// Index by medal ID
				$tmpCache[$reward['featureid']][] = $rewardid;
			}

			foreach (VBACTIVITY::$cache['medal'] as $medalid => $medal)
			{
				if (!isset($tmpCache[$medalid]))
				{
					// SKip this
					continue;
				}

				foreach ($tmpCache[$medalid] as $rewardid)
				{
					// Store this in a better format
					$rewardscache['medal'][$rewardid] = $post['dbtech_vbactivity_rewardscache'][$rewardid];
				}
			}
			break;

		case 'asc':
			ksort($rewardscache['medal'], SORT_NUMERIC);
			break;

		case 'desc':
		default:
			// Do nothing
			break;
	}
	
	/*DBTECH_PRO_START*/
	$achievCache = array();
	foreach ((array)$rewardscache['achievement'] as $rewardid => $reward)
	{
		$achievCache[$reward['featureid']] = true;
	}
	
	$achievesToDelete = array();
	foreach ((array)$rewardscache['achievement'] as $rewardid => $reward)
	{
		$featureid = $reward['featureid'];
		if (!$achievement = VBACTIVITY::$cache['achievement'][$featureid])
		{
			// Skip this
			continue;
		}
		
		do
		{
			$foundParent = false;
			foreach ((array)VBACTIVITY::$cache['achievement'] as $key => $arr)
			{
				if ($arr['parentid'] == $featureid AND $achievCache[$key])
				{
					// We've got a newer achievement
					$achievesToDelete[] = $rewardid;
					
					$featureid = $key;
					$foundParent = true;
					break;
				}
			}
		}
		while ($foundParent);
	}
	
	foreach ($achievesToDelete as $rewardid)
	{
		// Unset these
		unset($rewardscache['achievement'][$rewardid]);
	}
	/*DBTECH_PRO_END*/

	$rewardtypes = array();
	if ($this->registry->options['dbtech_vbactivity_enable_achievements'] AND !($this->registry->userinfo['dbtech_vbactivity_settings'] & 8192))
	{
		$rewardtypes[] = 'achievement';
	}
	
	if ($this->registry->options['dbtech_vbactivity_enable_medals'] AND !($this->registry->userinfo['dbtech_vbactivity_settings'] & 16384))
	{
		$rewardtypes[] = 'medal';
	}	

	foreach ($rewardtypes as $feature)
	{
		$rewards = array(
			'sticky' => '',
			'normal' => ''
		);
		$i = array(
			'sticky' => 0,
			'normal' => 0
		);
		
		foreach ((array)$rewardscache[$feature] as $featureid => $reward)
		{
			// This is the info we need
			$reward = array_merge($reward, (array)VBACTIVITY::$cache[$reward['feature']][$reward['featureid']]);
			
			$reward['reasonsafe'] = $reward['reason'];
			$reward['reason'] = nl2br($reward['reason']);
			
			// Is this normal or sticky?
			$word = ($reward['sticky'] ? 'sticky' : 'normal');
			
			if ($i[$word] >= $this->registry->options["dbtech_vbactivity_num{$feature}s"])
			{
				// That's too many!
				continue;
			}
			
			/*DBTECH_PRO_START*/
			$reward['pre'] = '<a href="vbactivity.php?' . $this->registry->session->vars['sessionurl'] . 'do=all' . ($reward['feature'] == 'medal' ? 'award' : $reward['feature']) . 's&amp;' . $reward['feature'] . 'id=' . $reward['featureid'] . '">';
			$reward['post'] = '</a>';

			$reward['icon'] = ($reward['icon_small'] ? $reward['icon_small'] : $reward['icon']);
			/*DBTECH_PRO_END*/

			// Create the achievement bit
			$templater = vB_Template::create('dbtech_vbactivity_postbit_rewardbit');
				$templater->register('post', $post);
				$templater->register('reward', $reward);
			$rewards[$word] .= $templater->render();
			
			$i[$word]++;
		}
		
		if (strlen($rewards['sticky']))
		{
			// We had at least 1 sticky achievement
			$rewards['normal'] = $rewards['sticky'] . '<br />' . $rewards['normal'];
		}
		
		$templater = vB_Template::create('dbtech_vbactivity_postbit_reward');
			$templater->register('post', $post);
			$templater->register('phrase', $vbphrase["dbtech_vbactivity_{$feature}s"]);
			$templater->register('rewards', $rewards['normal']);
		$vbactivity_postbit[$feature] = $templater->render();
		
		if (intval($this->registry->versionnumber) == 3)
		{
			$vbactivity_postbit[$feature] = '<div>' . $vbactivity_postbit[$feature] . '</div>';
		}
		else
		{
			$vbactivity_postbit[$feature] = '<dd>' . $vbactivity_postbit[$feature] . '</dd>';
		}
	}
	
	// Cache this
	VBACTIVITY::$postbitcache[$post['userid']] = $vbactivity_postbit;
}

VBACTIVITY::$postbitcache2 = (is_array(VBACTIVITY::$postbitcache[$post['userid']]) ? VBACTIVITY::$postbitcache[$post['userid']] : array());
foreach (VBACTIVITY::$postbitcache2 as $feature => &$template)
{
	// Ensure this is updated with the proper postid
	$template = str_replace('[postid]', $post['postid'], $template);
	
	if ($this->registry->options["dbtech_vbactivity_{$feature}s_postbit"] & 1)
	{
		// We're hooking into this location
		//$template_hook['postbit_userinfo_left'] .= '<dl class="userinfo_extra">' . $template . '</dl>';
	}
	
	if ($this->registry->options["dbtech_vbactivity_{$feature}s_postbit"] & 2 AND ($this->registry->userinfo['userid'] OR !$this->registry->options['dbtech_vbactivity_hideguests']))
	{
		// We're hooking into this location
		$template_hook['postbit_userinfo_right_after_posts'] .= $template;
	}
	
	if ($this->registry->options["dbtech_vbactivity_{$feature}s_postbit"] & 4)
	{
		// We're hooking into this location
		//$template_hook['postbit_userinfo_right'] .= '<div class="imlinks"><dl class="userinfo_extra">' . $template . '</dl></div>';
	}
	
	if ($this->registry->options["dbtech_vbactivity_{$feature}s_postbit"] & 8)
	{
		// We're hooking into this location
		//$template_hook['postbit_signature_start'] .= '<div class="userinfo"><dl class="userinfo_extra">' . $template . '</dl></div>';
	}
	
	if ($this->registry->options["dbtech_vbactivity_{$feature}s_postbit"] & 16)
	{
		// We're hooking into this location
		//$template_hook['postbit_signature_end'] .= '<div class="userinfo"><dl class="userinfo_extra">' . $template . '</dl></div>';
	}
}

$vbactivity_postbit = VBACTIVITY::$postbitcache2;
if (intval($this->registry->versionnumber) > 3)
{
	vB_Template::preRegister('postbit', array('vbactivity_postbit' => VBACTIVITY::$postbitcache2));
	vB_Template::preRegister('postbit_legacy', array('vbactivity_postbit' => VBACTIVITY::$postbitcache2));
}

if (class_exists('POSTBITTABS'))
{
	if (!POSTBITTABS::$created['dbtech_vbactivity'])
	{
		// DragonByte Tech: Postbit Tabs - registerView()
		POSTBITTABS::registerView('dbtech_vbactivity_activitystats', 	'DragonByte Tech: vBActivity - Activity Stats', (intval($this->registry->versionnumber) == 3 ? '{$vbactivity_postbit[activitystat]}' : '<dl>{vb:raw vbactivity_postbit.activitystat}</dl>'));
		POSTBITTABS::registerView('dbtech_vbactivity_achievements', 	'DragonByte Tech: vBActivity - Achievements', 	(intval($this->registry->versionnumber) == 3 ? '{$vbactivity_postbit[achievement]}' : '{vb:raw vbactivity_postbit.achievement}'));
		POSTBITTABS::registerView('dbtech_vbactivity_awards', 			'DragonByte Tech: vBActivity - Awards', 		(intval($this->registry->versionnumber) == 3 ? '{$vbactivity_postbit[medal]}' : '{vb:raw vbactivity_postbit.medal}'));
		
		// Set created
		POSTBITTABS::$created['dbtech_vbactivity'] = true;
	}
}

/*
$microtimes['end'] = explode(' ', microtime());
$microtimes['end'] = $microtimes['end'][0] + $microtimes['end'][1];

$microtimes['diff'] = $microtimes['end'] - $microtimes['start'];

echo "<pre>";
print_r($microtimes);
echo "</pre>";
*/
?>
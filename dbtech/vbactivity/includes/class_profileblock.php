<?php
/**
* Profile Block for Activity
*
* @package vBActivity
*/
class vB_ProfileBlock_vBActivity extends vB_ProfileBlock
{
	/**
	* The name of the template to be used for the block
	*
	* @var string
	*/
	var $template_name = 'dbtech_vbactivity_memberinfo_block_activity';

	var $nowrap = true;

	/**
	* Variables to automatically prepare
	*
	* @var array
	*/
	var $auto_prepare = array(
		'dbtech_vbactivity_points',
		'dbtech_vbactivity_pointscache',
		'dbtech_vbactivity_pointscache_day',
		'dbtech_vbactivity_pointscache_week',
		'dbtech_vbactivity_pointscache_month',
		'dbtech_vbactivity_rewardscache',
	);

	/**
	* Whether to return an empty wrapper if there is no content in the blocks
	*
	* @return bool
	*/
	function confirm_empty_wrap()
	{
		return false;
	}

	/**
	* Whether or not the block is enabled
	*
	* @return bool
	*/
	function block_is_enabled($id)
	{
		return (
			$this->registry->options['dbtech_vbactivity_active'] AND
			!$this->profile->userinfo['dbtech_vbactivity_excluded'] AND
			!($this->profile->userinfo['permissions']['dbtech_vbactivitypermissions'] & $this->registry->bf_ugp_dbtech_vbactivitypermissions['isexcluded'])
		? true : false);
	}

	/**
	* Prepare any data needed for the output
	*
	* @param	string	The id of the block
	* @param	array	Options specific to the block
	*/
	function prepare_output($id = '', $options = array())
	{
		global $show, $vbphrase, $template_hook, $vbulletin;
		
		if (intval($this->registry->versionnumber) == 3)
		{
			$this->nowrap = false;
		}
		
		// Raeg.
		$this->profile->prepared['dbtech_vbactivity_points'] = round($this->profile->prepared['dbtech_vbactivity_points'], 2);
		
		$userinfo = array(
			'points' 			=> round(doubleval($this->profile->prepared['dbtech_vbactivity_points']), 2),
			'pointscache'		=> round(doubleval($this->profile->prepared['dbtech_vbactivity_pointscache']), 2),
			'pointscache_day' 	=> round(doubleval($this->profile->prepared['dbtech_vbactivity_pointscache_day']), 2),
			'pointscache_week' 	=> round(doubleval($this->profile->prepared['dbtech_vbactivity_pointscache_week']), 2),
			'pointscache_month' => round(doubleval($this->profile->prepared['dbtech_vbactivity_pointscache_month']), 2)
		);
		
		// Fetch activity related stuff
		VBACTIVITY::fetch_activity_level($userinfo);
		VBACTIVITY::fetch_activity_rating($userinfo);
		
		if ($this->registry->options['dbtech_vbactivity_enable_stats']/* AND ($this->registry->options['dbtech_vbactivity_achievements_profile'] & 1)*/)
		{
			// We're showing stats
			$show['stats_block'] = true;
			
			$this->block_data['level'] = $userinfo['activitylevel'];
			$this->block_data['tonextlevel'] = $userinfo['tonextlevel'];
			$this->block_data['tnlphrase'] = construct_phrase($vbphrase['dbtech_vbactivity_tnl_data'], $userinfo['levelpercent']);
			$this->block_data['userinfo'] = $userinfo;
		}
		
		// Ensure our rewards cache is up to specs
		VBACTIVITY::verify_rewards_cache($this->profile->userinfo);
		
		// Build a better mouse trap
		$features 	= array();
		foreach ((array)$this->profile->userinfo['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
		{
			if (!is_array(VBACTIVITY::$cache["$reward[feature]"]["$reward[featureid]"]))
			{
				// Rebuild the cache automatically
				VBACTIVITY_CACHE::build("dbtech_vbactivity_{$reward[feature]}");
				continue;
			}
			
			// Cache the results
			$features["$reward[feature]"]["$rewardid"] = $reward;
		}
		
		foreach (array('achievement', 'medal') AS $feature)
		{
			if ($this->profile->userinfo['userid'] == $this->registry->userinfo['userid'] AND $this->registry->userinfo["dbtech_vbactivity_{$feature}count"] > 0 AND $_REQUEST['tab'] == 'vbactivity')
			{
				// Flag our achievements as "read"
				VBACTIVITY::remove_notification($feature);
			}

			if (!$this->registry->options["dbtech_vbactivity_enable_{$feature}s"] OR !($this->registry->options["dbtech_vbactivity_{$feature}s_profile"] & 1))
			{
				// We're not displaying
				continue;
			}
			
			// We're showing achievements
			$show["{$feature}_block"] = true;
			
			// Ensure this is an array
			$features["$feature"] = (!is_array($features["$feature"]) ? array() : $features["$feature"]);
			
			// Emulate order by dateline desc
			krsort($features["$feature"], SORT_NUMERIC);

			// Limiter
			$i = 0;
			
			// Init some important vars
			$bits = array(
				'sticky' => '',
				'normal' => ''
			);
			foreach ($features[$feature] as $rewardid => $reward)
			{
				if ($this->registry->options['dbtech_vbactivity_profile_' . $feature . '_limit'] > -1 AND $i++ >= $this->registry->options['dbtech_vbactivity_profile_' . $feature . '_limit'])
				{
					// HALT!
					break;
				}

				$reward['reasonsafe'] = $reward['reason'];
				$reward['reason'] = nl2br($reward['reason']);
				
				// Shorthand featureinfo
				$featureinfo = array_merge((array)VBACTIVITY::$cache[$feature][$reward['featureid']], (array)$reward);
				
				// Is this a sticky or normal achievement?
				$word = ($featureinfo['sticky'] ? 'sticky' : 'normal');
				
				/*DBTECH_PRO_START*/
				require(DIR . '/dbtech/vbactivity_pro/includes/actions/feature.php');
				/*DBTECH_PRO_END*/
				
				$templater = vB_Template::create('dbtech_vbactivity_memberinfo_featurebit');
					$templater->register('feature', $featureinfo);
					$templater->register('phrase', construct_phrase(
						$vbphrase["dbtech_vbactivity_{$feature}_earned_x"],
						vbdate($this->registry->options['dateformat'], $reward['dateline']) . ' ' . vbdate($this->registry->options['timeformat'], $reward['dateline'])
					));
				$bits["$word"] .= $templater->render();
			}
			
			if ($bits['sticky'])
			{
				// We had sticky features
				$bits['normal'] = $bits['sticky'] . '<br />' . $bits['normal'];
			}

			// Ensure this number is correct
			$num = count($features[$feature]);
			$num = (
				$this->registry->options['dbtech_vbactivity_profile_' . $feature . '_limit'] > -1 AND 
				$num > $this->registry->options['dbtech_vbactivity_profile_' . $feature . '_limit']
			) ? $this->registry->options['dbtech_vbactivity_profile_' . $feature . '_limit'] : $num;
			
			$this->block_data["{$feature}s"] 		= $bits['normal'];
			$this->block_data["{$feature}count"] 	= vb_number_format($num);
		}
		
		/*DBTECH_PRO_START*/
		if ($show['stats_block'])
		{
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'green');
				$templater->register('percent', $userinfo['levelpercent']);
			$this->block_data['profile_vbactivity_stats_tnl'] .= $templater->render();
			
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'red');
				$templater->register('percent', $userinfo['target']['daily_bar']);
			$this->block_data['profile_vbactivity_stats_daily'] .= $templater->render();
			
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'grey');
				$templater->register('percent', $userinfo['target']['weekly_bar']);
			$this->block_data['profile_vbactivity_stats_weekly'] .= $templater->render();
			
			$templater = vB_Template::create('dbtech_vbactivity_bar');
				$templater->register('color', 'green');
				$templater->register('percent', $userinfo['target']['monthly_bar']);
			$this->block_data['profile_vbactivity_stats_monthly'] .= $templater->render();
		}

		if ($this->registry->options['dbtech_vbactivity_enable_trophies'] AND ($this->registry->options['dbtech_vbactivity_trophies_profile'] & 1))
		{
			// We're showing trophys
			$show['trophy_block'] = true;
			
			if ($this->profile->userinfo['userid'] == $this->registry->userinfo['userid'] AND $this->registry->userinfo['dbtech_vbactivity_trophycount'] > 0)
			{
				// Flag our trophys as "read"
				VBACTIVITY::remove_notification('trophy');
			}

			// Pull our trophy log
			$trophycount 	= 0;
			$trophylog 		= array();
			
			$cacheResult = VBACTIVITY_CACHE::read('trophylog', 'trophylog.' . $this->profile->userinfo['userid']);
			if (!is_array($cacheResult))
			{
				$vbactivity_rewards = VBACTIVITY::$db->fetchAll('
					SELECT *
					FROM $dbtech_vbactivity_trophylog
					WHERE userid = ?
					ORDER BY dateline DESC
				', array(
					$this->profile->userinfo['userid']
				));

				if ($cacheResult != -1)
				{
					// Write to the cache
					VBACTIVITY_CACHE::write($vbactivity_rewards, 'trophylog', 'trophylog.' . $this->profile->userinfo['userid']);
				}
			}
			else
			{
				// Set the entry cache
				$vbactivity_rewards = $cacheResult;
			}

			foreach ($vbactivity_rewards as $vbactivity_reward)
			{
				// Cache the results
				$trophylog[$vbactivity_reward['typeid']]['earnedcount']++;
				$trophylog[$vbactivity_reward['typeid']]['lastearned'] = $vbactivity_reward['dateline'];
				//$trophylog[$vbactivity_reward['typeid']]['isactive'] = ($vbactivity_reward['addremove'] ? true : false);
				$trophylog[$vbactivity_reward['typeid']]['isactive'] = (VBACTIVITY::$cache['type'][$vbactivity_reward['typeid']]['userid'] == $this->profile->userinfo['userid'] ? true : false);
			}

			$i = array(
				'current' => 0,
				'previous' => 0,
			);
			foreach ($trophylog as $typeid => $trophy)
			{
				// Determine if this is a currently or previously held trophy
				$word = ($trophy['isactive'] ? 'current' : 'previous');
				
				// Shorthand
				$type = VBACTIVITY::$cache['type'][$typeid];
				
				// Ensure this is correct
				$type['typename'] = ($type['typename'] == 'totalpoints' ? $type['typename'] : 'per' . $type['typename']);
				
				// Set trophy info
				$trophy['name'] = ($type['trophyname'] ? $type['trophyname'] : $vbphrase["dbtech_vbactivity_condition_{$type[typename]}"]);
				$trophy['icon'] = $type['icon'];
				$trophy['description'] = $vbphrase["dbtech_vbactivity_condition_{$type[typename]}"];
				
				// Sort the date
				$trophy['dateline'] = vbdate($this->registry->options['dateformat'], $trophy['lastearned']) . ' ' . vbdate($this->registry->options['timeformat'], $trophy['lastearned']);
				
				$templater = vB_Template::create('dbtech_vbactivity_memberinfo_trophybit');
					$templater->register('typeid', $typeid);
					$templater->register('trophy', $trophy);
				$this->block_data[$word . 'trophies'] .= $templater->render();
				
				$i[$word]++;
			}

			// Set trophy counts
			$this->block_data['currenttrophycount'] 	= vb_number_format($i['current']);
			$this->block_data['previoustrophycount'] 	= vb_number_format($i['previous']);
		}
		/*DBTECH_PRO_END*/
		
		if ($this->registry->options['dbtech_vbactivity_enable_stats']/* AND ($this->registry->options['dbtech_vbactivity_achievements_profile'] & 1)*/)
		{
			$show['points_block'] = true;
			
			// Start the list of SQL subqueries
			$SQL = array();
			
			foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
			{
				if (!$type['active'] OR !($type['display'] & 1))
				{
					// This type wasn't even active
					continue;
				}
				
				// Set column name
				$SQL[$type['typename']] = $type['typename'];
			}
			
			if ($SQL)
			{
				$cacheResult = VBACTIVITY_CACHE::read('profilepoints', 'profilepoints.' . $this->profile->userinfo['userid']);
				if (!is_array($cacheResult))
				{
					$results = VBACTIVITY::$db->fetchRow('
						SELECT
							' . implode(',', $SQL) . '
						FROM $dbtech_vbactivity_points
						WHERE userid = ?
					', array(
						$this->profile->userinfo['userid']
					));

					if ($cacheResult != -1)
					{
						// Write to the cache
						VBACTIVITY_CACHE::write($results, 'profilepoints', 'profilepoints.' . $this->profile->userinfo['userid']);
					}
				}
				else
				{
					// Set the entry cache
					$results = $cacheResult;
				}
			}
			
			$pointbits = '';
			foreach ($SQL as $key => $value)
			{
				$templater = vB_Template::create('dbtech_vbactivity_memberinfo_pointsbit');
					$templater->register('phrase', $vbphrase["dbtech_vbactivity_condition_per{$key}"]);
					$templater->register('points', doubleval($results[$key]));
				$pointbits .= $templater->render();
			}
			$this->block_data['points'] = $pointbits;
		}
	}
}
?>
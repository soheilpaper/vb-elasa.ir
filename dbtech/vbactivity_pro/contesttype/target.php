<?php
class vBActivity_ContestType_Target
{
	/**
	* The vBulletin registry
	*
	* @protected	vBulletin
	*/	
	protected $registry = NULL;
	
	/**
	* The configuration array
	*
	* @public		array
	*/	
	public $config = array();
	
	
	/**
	* The constructor
	*
	* @param	vBulletin	vBulletin registry
	* @param	array		Contest info
	*/	
	public function __construct(&$registry, &$contest)
	{
		$this->registry =& $registry;
		$this->config =& $contest;
	}
	
	/**
	* Prints out an AdminCP form
	*/	
	public function adminForm()
	{
		global $vbphrase;
		print_input_row($vbphrase['dbtech_vbactivity_activity_points_target'], 	'contest[target]', 		$this->config['target']);
	}
	
	/**
	* Prints out a Front-End form
	*/	
	public function frontEndForm()
	{
		$templater = vB_Template::create('dbtech_vbactivity_contests_manage_modify_target');
			$templater->register('title', 	VBACTIVITY::$cache['contesttype'][$this->config['contesttypeid']]['title_translated']);
			$templater->register('contest', $this->config);
		return $templater->render();
	}

	/**
	* Recalculates winners
	*/
	public function recalculate()
	{
		if (!is_array($this->config['excludedcriteria']))
		{
			// For some reason
			$this->config['excludedcriteria'] = array();
		}

		// Ensure this is never empty
		$excludedTypes = array(0);

		foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
		{
			if (
				in_array($typeid, $this->config['excludedcriteria']) OR 
				!$type['active'] OR
				!($type['settings'] & 8)
			)
			{
				// We're not dealing with this criteria
				$excludedTypes[] = $typeid;
			}
		}

		if (!is_array($this->config['excludedforums']))
		{
			// For some reason
			$this->config['excludedforums'] = array();
		}

		// Ensure this is never empty
		$excludedForums = array(-1);

		foreach ($this->registry->forumcache as $forumid => $forum)
		{
			if (in_array($forumid, $this->config['excludedforums']))
			{
				// We're not dealing with this criteria
				$excludedForums[] = $forumid;
			}
		}

		$userList = VBACTIVITY::$db->fetchAll('
			SELECT userid, points
			FROM $dbtech_vbactivity_pointslog
			WHERE dateline BETWEEN ? AND ?
				AND typeid NOT :typeList
				AND forumid NOT :forumList
		', array(
			':typeList' => VBACTIVITY::$db->queryList($excludedTypes),
			':forumList' => VBACTIVITY::$db->queryList($excludedForums),
			$this->config['start'],
			$this->config['end'],
		));

		if (!count($userList))
		{
			// We don't have any users to deal with
			return true;
		}

		$userCache = array();
		foreach ($userList as $key => $user)
		{
			if (!isset($userCache[$user['userid']]))
			{
				// Grab our new user info
				$userCache[$user['userid']] = fetch_userinfo($user['userid']);
				cache_permissions($userCache[$user['userid']]);
			}

			if (
				($userCache[$user['userid']]['permissions']['dbtech_vbactivitypermissions'] & $this->registry->bf_ugp_dbtech_vbactivitypermissions['isexcluded_contests']) OR 
				$userCache[$user['userid']]['dbtech_vbactivity_excluded']
			)
			{
				// We're excluded
				unset($userList[$key]);
			}
		}

		$i = 0;

		$usersToSort = array();
		foreach ($userList as $user)
		{
			if (!isset($usersToSort[$user['userid']]))
			{
				// Ensure this is set
				$usersToSort[$user['userid']] = 0;
			}

			// Increment this counter
			$usersToSort[$user['userid']] += $user['points'];

			if ($usersToSort[$user['userid']] >= $this->config['target'])
			{
				// Add Contest winner
				VBACTIVITY::addContestWinner($user['userid'], $this->config['contestid'], $usersToSort[$user['userid']]);

				if (++$i == $this->config['numwinners'])
				{
					// And we're done here.
					break;
				}
			}
		}
	}
}
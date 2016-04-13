<?php
class vBActivity_ContestType_Total
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
	* Finishes the contest
	*/
	public function finishContest()
	{
		// Grab winners
		$winners = VBACTIVITY::getContestStanding($this->config['contestid'], true);	
		
		foreach ($winners as $winner => $points)
		{
			// Add Contest winner
			VBACTIVITY::addContestWinner($winner, $this->config['contestid'], $points);
		}
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

		$userList = VBACTIVITY::$db->fetchAllSingleKeyed('
			SELECT userid, SUM(points) AS points
			FROM $dbtech_vbactivity_pointslog
			WHERE dateline BETWEEN ? AND ?
				AND typeid NOT :typeList
				AND forumid NOT :forumList
			GROUP BY userid
			ORDER BY points DESC
		', 'userid', 'points', array(
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
		foreach ($userList as $userid => $points)
		{
			if (!isset($userCache[$userid]))
			{
				// Grab our new user info
				$userCache[$userid] = fetch_userinfo($userid);
				cache_permissions($userCache[$userid]);
			}

			if (
				($userCache[$userid]['permissions']['dbtech_vbactivitypermissions'] & $this->registry->bf_ugp_dbtech_vbactivitypermissions['isexcluded_contests']) OR 
				$userCache[$userid]['dbtech_vbactivity_excluded']
			)
			{
				// We're excluded
				unset($userList[$userid]);
			}
		}

		$i = 0;

		foreach ($userList as $winner => $points)
		{
			// Add Contest winner
			VBACTIVITY::addContestWinner($winner, $this->config['contestid'], $points);

			if (++$i == $this->config['numwinners'])
			{
				// And we're done here.
				break;
			}
		}
	}
}
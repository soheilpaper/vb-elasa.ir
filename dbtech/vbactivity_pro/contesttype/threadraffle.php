<?php
class vBActivity_ContestType_Threadraffle
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
		print_input_row($vbphrase['dbtech_vbactivity_thread_id'], 	'contest[data][threadid]', 		$this->config['data']['threadid']);
		print_input_row($vbphrase['dbtech_vbactivity_reply_delay'], 'contest[data][replydelay]', 	$this->config['data']['replydelay']);
	}
	
	/**
	* Prints out a Front-End form
	*/	
	public function frontEndForm()
	{
		$templater = vB_Template::create('dbtech_vbactivity_contests_manage_modify_thread');
			$templater->register('title', 	VBACTIVITY::$cache['contesttype'][$this->config['contesttypeid']]['title_translated']);
			$templater->register('contest', $this->config);
		return $templater->render();
	}

	/**
	* Finishes the contest
	*/
	public function finishContest()
	{
		// Functions identical to recalculate
		$this->recalculate();
	}

	/**
	* Recalculates winners
	*/
	public function recalculate()
	{
		// Convert minutes into seconds
		$delay = $this->config['data']['replydelay'] * 60;

		// First grab our thread info
		$threadinfo = VBACTIVITY::$db->fetchRow('
			SELECT *
			FROM $thread
			WHERE threadid = ?
		', array(
			$this->config['data']['threadid'],
		));

		// Grab our list of people who responded
		$userList = VBACTIVITY::$db->fetchAll('
			SELECT userid, dateline
			FROM $post
			WHERE threadid = ?
				AND postid != ?
				AND dateline BETWEEN ? AND ?
		', array(
			$this->config['data']['threadid'],
			$threadinfo['firstpostid'],
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

		$winningCandidates = $winners = $excluded = $delayArray = array();
		foreach ($userList as $user)
		{
			if (isset($delayArray[$user['userid']]) AND $delayArray[$user['userid']] > ($user['dateline'] - $delay))
			{
				// Too soon
				continue;
			}

			// Add to candidates
			$winningCandidates[] = $user['userid'];
			$delayArray[$user['userid']] = $user['dateline'];
		}

		if (!count($winningCandidates))
		{
			// We had no winning candidates
			return true;
		}

		if (count($winningCandidates) <= $this->config['numwinners'])
		{
			// Just do these
			$winners = $winningCandidates;
		}
		else
		{
			$i = 0;
			while ($i < $this->config['numwinners'])
			{
				// Select a random winner
				$key = mt_rand(0, (count($winningCandidates) - 1));

				if (!in_array($key, $excluded))
				{
					// Store this info in our array
					$winners[] = $winningCandidates[$key];

					// Increment winner count
					$i++;

					// Don't do this winner again
					$excluded = $key;
				}
			}
		}

		foreach ($winners as $winner)
		{
			// Add Contest winner
			VBACTIVITY::addContestWinner($winner, $this->config['contestid'], 0);
		}
	}
}
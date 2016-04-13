<?php
class LiveWall_ContentType_Core
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
	* @param	array		Type info
	*/	
	public function __construct(&$registry, &$block)
	{
		$this->registry =& $registry;
		$this->config =& $block;
	}
	
	/**
	* Fetches forums user cannot access
	*/
	public function getForumIds()
	{
		$forumids = '0';
		
		// get forum ids for all forums user is allowed to view
		foreach ($this->registry->forumcache AS $forumid => $forum)
		{
			$fperms = $this->registry->userinfo['forumpermissions'][$forumid];
			if (!($fperms & $this->registry->bf_ugp_forumpermissions['canview']) OR !verify_forum_password($forumid, $forum['password'], false) OR 
				in_array($forumid, (array)$this->config['code']['excludedforums'])
			)
			{
				// Set excluded forum
				$forumids .= ',' . $forumid;
			}
		}
		
		return $forumids;
	}
	
	/**
	* Fetches excluded user groups
	*/
	public function getUserGroupIds()
	{
		$usergroupids = array();
		foreach ($this->registry->usergroupcache as $usergroupid => $usergroup)
		{
			if (!$this->config['permissions'][$usergroupid]['excluded'])
			{
				// Skip this
				continue;
			}
			
			// Set excluded
			$usergroupids[] = $usergroupid;
		}
		
		if (!count($usergroupids))
		{
			// Ensure something is set
			$usergroupids[] = 0;
		}
		
		return $usergroupids;
	}
	
	/**
	* Function to call before every action
	*/	
	public function preCheck()
	{
		if (!$this->config['active'] OR
			!$this->config['enabled'] OR
			LIVEWALL::checkPermissions($this->registry->userinfo, $this->config['permissions'], 'noaccess')/* OR
			(
				isset($this->registry->userinfo[$this->config['contenttypeid'] . '_display']) AND
				$this->registry->userinfo[$this->config['contenttypeid'] . '_display'] == -1
			)
			*/
		)
		{
			// This type is inactive
			return false;
		}
		
		// We made it!
		return true;
	}
	
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		return 'N/A';
	}	
}
?>
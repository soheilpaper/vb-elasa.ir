<?php
class vBActivity_Type_attachmentviews extends vBActivity_Type_Core
{
	/**
	* The constructor
	*
	* @param	vBulletin	vBulletin registry
	* @param	array		Type info
	* 
	* @return	string	The SQL subquery
	*/	
	public function __construct(&$registry, &$type)
	{
		parent::__construct($registry, $type);
	}
	
	/**
	* Function to call before every action
	*/	
	public function action($user)
	{
		if (!parent::action($user))
		{
			// This type is inactive
			return false;
		}
		
		// We made it!
		return true;
	}
}
?>
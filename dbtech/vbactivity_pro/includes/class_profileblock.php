<?php
/**
* Trophy Block for Activity
*
* @package vBActivity Pro
*/
class vB_ProfileBlock_vBActivity_Trophies extends vB_ProfileBlock
{
	/**
	* The name of the template to be used for the block
	*
	* @var string
	*/
	var $template_name = 'dbtech_vbactivity_activity_block_trophies';
	
	var $nowrap = true;
	
	var $skip_privacy_check = true;
	
	/**
	* Variables to automatically prepare
	*
	* @var array
	*/
	var $auto_prepare = array();

	/**
	* Sets/Fetches the default options for the block
	*
	*/
	function fetch_default_options()
	{
		$this->option_defaults = array(
			'pagenumber' => 1,
			'perpage'    => 25,
		);
	}

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
	* Should we actually display anything?
	*
	* @return	bool
	*/
	function confirm_display()
	{
		return (bool)$this->block_data['trophies'];
	}	

	/**
	* Whether or not the block is enabled
	*
	* @return bool
	*/
	function block_is_enabled($id)
	{
		return ($this->registry->options['dbtech_vbactivity_active'] ? true : false);
	}

	/**
	* Prepare any data needed for the output
	*
	* @param	string	The id of the block
	* @param	array	Options specific to the block
	*/
	function prepare_output($id = '', $options = array())
	{
		global $show, $vbphrase;
		
		// Shorthands to faciliate easy copypaste
		$pagenumber = $options['pagenumber'];
		$perpage = $options['perpage'];
		
	}
}
?>
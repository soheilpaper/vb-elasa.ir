<?php
if (defined('IN_VBACTIVITY') AND $idfield == $table . 'id')
{
	$idfield = substr($table, strlen('dbtech_vbactivity_')) . 'id';
	$handled = true;
	
	$idfield = ($idfield == 'rewardsid' ? 'rewardid' : $idfield);
	$item = $vbulletin->db->query_first_slave("
		SELECT $idfield, $titlename AS title
		FROM " . TABLE_PREFIX . "$table
		WHERE $idfield = '$itemid'
	");
}
?>
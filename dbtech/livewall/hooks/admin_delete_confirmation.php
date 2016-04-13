<?php
if (defined('IN_LIVEWALL') AND $idfield == $table . 'id')
{
	$idfield = substr($table, strlen('dbtech_livewall_')) . 'id';
	$handled = true;
	
	$item = $vbulletin->db->query_first("
		SELECT $idfield, $titlename AS title
		FROM " . TABLE_PREFIX . "$table
		WHERE $idfield = '$itemid'
	");
}
?>
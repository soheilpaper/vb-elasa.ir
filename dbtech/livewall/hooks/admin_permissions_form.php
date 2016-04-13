<?php
foreach (convert_bits_to_array($user['dbtech_livewalladminperms'], $vbulletin->bf_misc_dbtech_livewalladminperms) AS $field => $value)
{
	print_yes_no_row($vbphrase["$field"], "dbtech_livewalladminperms[$field]", $value);
}
?>
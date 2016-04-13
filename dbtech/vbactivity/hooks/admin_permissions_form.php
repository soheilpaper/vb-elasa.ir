<?php
foreach (convert_bits_to_array($user['dbtech_vbactivityadminperms'], $vbulletin->bf_misc_dbtech_vbactivityadminperms) AS $field => $value)
{
	print_yes_no_row($vbphrase["$field"], "dbtech_vbactivityadminperms[$field]", $value);
}
?>
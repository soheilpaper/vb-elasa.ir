<?php
if (can_administer('canadminvbactivity'))
{
	print_table_break('', $INNERTABLEWIDTH);
	print_table_header($vbphrase['dbtech_vbactivity']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_isexcluded'], 		'user[dbtech_vbactivity_excluded]', 		$user['dbtech_vbactivity_excluded']);
}
?>
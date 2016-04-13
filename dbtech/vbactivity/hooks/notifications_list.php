<?php
if (
	$vbulletin->options['dbtech_vbactivity_notifications'] & 1
	AND $vbulletin->options['dbtech_vbactivity_enable_achievements']
	AND !$vbulletin->userinfo['dbtech_vbactivity_excluded']
	AND !($vbulletin->userinfo['dbtech_vbactivity_settings'] & 256)
)
{
	$notifications['dbtech_vbactivity_achievementcount'] = array(
		'phrase' => $vbphrase['dbtech_vbactivity_new_achievements'],
		'link'   => 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . '&amp;tab=vbactivity',
		'order'  => 100
	);
}

if (
	$vbulletin->options['dbtech_vbactivity_notifications'] & 4
	AND !$vbulletin->userinfo['dbtech_vbactivity_excluded']
	AND !($vbulletin->userinfo['dbtech_vbactivity_settings'] & 1024)
)
{
	$notifications['dbtech_vbactivity_medalcount'] = array(
		'phrase' => $vbphrase['dbtech_vbactivity_new_medals'],
		'link'   => 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . '&amp;tab=vbactivity',
		'order'  => 120
	);
}
?>
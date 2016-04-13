<?php
if (
	$vbulletin->options['dbtech_vbactivity_notifications'] & 2
	AND !$vbulletin->userinfo['dbtech_vbactivity_excluded']
	AND !($vbulletin->userinfo['dbtech_vbactivity_settings'] & 512)
)
{
	$notifications['dbtech_vbactivity_trophycount'] = array(
		'phrase' => $vbphrase['dbtech_vbactivity_new_trophies'],
		'link'   => 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . '&amp;tab=vbactivity',
		'order'  => 110
	);
}

/*
if (
	$vbulletin->options['dbtech_vbactivity_notifications'] & 8
	AND !$vbulletin->userinfo['dbtech_vbactivity_excluded']
	AND !($vbulletin->userinfo['dbtech_vbactivity_settings'] & 2048)
)
{
	$notifications['dbtech_vbactivity_promotioncount'] = array(
		'phrase' => $vbphrase['dbtech_vbactivity_new_promotions'],
		'link'   => 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . '&amp;tab=vbactivity',
		'order'  => 110
	);
}
*/

$notifications['dbtech_vbactivity_medalmoderatecount'] = array(
	'phrase' => $vbphrase['dbtech_vbactivity_new_medal_requests'],
	'link'   => $vbulletin->config['Misc']['modcpdir'] . '/vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=requests',
	'order'  => 130
);
?>
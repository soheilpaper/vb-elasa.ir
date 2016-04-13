<?php
switch($filename)
{
	case 'vbactivity.php':
		$userinfo['activity'] = 'dbtech_vbactivity_activity';

		if ($values['do'] == 'main' OR !$values['do'])
		{
			$userinfo['activity_where_link'] = 'vbactivity.php' . $vbulletin->session->vars['sessionurl_q'];
			$userinfo['activity_where_text'] = 'dbtech_vbactivity_wol_main';
		}
		else
		{
			$userinfo['activity_where_link'] = 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=' . $values['do'];
			$userinfo['activity_where_text'] = 'dbtech_vbactivity_wol_' . $values['do'];
		}
		break;
}
?>
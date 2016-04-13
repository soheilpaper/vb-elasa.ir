<?php
if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}

if (!$vbulletin AND is_object($this))
{
	$vbulletin =& $this->registry;
}

if ($vbulletin->options['dbtech_vbactivity_integration'] & 4)
{
	if (intval($vbulletin->versionnumber) == 3)
	{
		$templater = vB_Template::create('dbtech_vbactivity_memberaction_dropdown');
			$templater->register('memberinfo', $post);
		$template_hook['postbit_user_popup'] .= $templater->render();
	}
	else
	{
		$templater = vB_Template::create('dbtech_vbactivity_memberaction_dropdown');
			$templater->register('memberinfo', $memberinfo);
			$templater->register('vba', array('tab' => 'vbactivity'));
		$template_hook['memberaction_dropdown_items'] .= $templater->render();
	}
}
?>
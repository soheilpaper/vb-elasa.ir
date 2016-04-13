<?php
// #############################################################################
/**
* Prints a row containing a list of <input type="checkbox" />
*
* @param	string	Title for row
* @param	string	Name for checkbox
* @param	array	Values to use
* @param	boolean	Whether or not to check the box
* @param	boolean	Whether or not to htmlspecialchars the title
*/
function print_checkbox_array_row($title, $name, $array, $selected = '', $htmlise = false)
{
	global $vbulletin;

	if (function_exists('fetch_uniqueid_counter'))
	{
		$uniqueid = fetch_uniqueid_counter();
	}

	if (!is_array($array))
	{
		$array = array();
		$selected = array();
	}

	$check = "<div id=\"ctrl_$name\">\n";
	$check .= construct_checkbox_options($name, $array, $selected, $htmlise);
	$check .= "</div>\n";

	print_label_row($title, $check, '', 'top', $name);
}

// #############################################################################
/**
* Creates <input type="checkbox" /> from an array
*
* @param	string	Name for checkbox
* @param	array	Values to use
* @param	boolean	Whether or not to check the box
* @param	string	Value for checkbox
* @param	string	Text label for checkbox
* @param	string	Optional Javascript code to run when checkbox is clicked - example: ' onclick="do_something()"'
*/
function construct_checkbox_options($name, $array, $selected = '', $htmlise = false)
{
	global $vbulletin;

	$box = '';

	foreach ($array as $value => $title)
	{
		if (function_exists('fetch_uniqueid_counter'))
		{
			$uniqueid = fetch_uniqueid_counter();
		}
		$checked = in_array($value, $selected);
		$box .= "<div id=\"ctrl_$name_$value\"><label for=\"{$name}_$uniqueid\" class=\"smallfont\"><input type=\"checkbox\" name=\"{$name}\" id=\"{$name}_$uniqueid\" value=\"$value\" tabindex=\"1\"" . iif($onclick, " onclick=\"$onclick\"") . iif($vbulletin->debug, " title=\"name=&quot;{$name}&quot;\"") . iif($checked, ' checked="checked"') . " style=\"margin:3px;\" />" . iif($htmlise, htmlspecialchars_uni($title), $title) . "</label></div>\n";
	}

	return $box;
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of forums
*
* @param	boolean	Whether or not to display the 'Select Forum' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_contest_forum_chooser_options($displayselectforum = false, $topname = null, $category_phrase = null)
{
	global $vbulletin, $vbphrase;

	$selectoptions = array();

	if ($displayselectforum)
	{
		$selectoptions[0] = $vbphrase['select_forum'];
	}

	if ($topname)
	{
		$selectoptions['-1'] = $topname;
		$startdepth = '--';
	}
	else
	{
		$startdepth = '';
	}

	if (!$category_phrase)
	{
		$category_phrase = '%s';
	}

	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		if (!($forum['options'] & $vbulletin->bf_misc_forumoptions['cancontainthreads']))
		{
			$forum['title'] = sprintf($category_phrase, $forum['title']);
		}

		$selectoptions["$forumid"] = VBACTIVITY::getDepthMark($forum['depth'], '--', $startdepth) . ' ' . $forum['title'] . ' ' . iif(!($forum['options'] & $vbulletin->bf_misc_forumoptions['allowposting']), " ($vbphrase[forum_is_closed_for_posting])");
	}

	return $selectoptions;
}

// #############################################################################
/**
* Prints a row containing a <select> field
*
* @param	string	Name for select field
* @param	array	Array of value => text pairs representing '<option value="$key">$value</option>' fields
* @param	string	Selected option
* @param	boolean	Whether or not to htmlspecialchars the text for the options
* @param	integer	Size of select field (non-zero means multi-line)
* @param	boolean	Whether or not to allow multiple selections
*/	
function print_condition_row($name, $array, $selected = '', $printdelete = true, $htmlise = false, $size = 0, $multiple = false)
{
	global $vbulletin, $vbphrase;

	require_once(DIR . '/includes/adminfunctions.php');
	
	if (function_exists('fetch_uniqueid_counter'))
	{
		$uniqueid = fetch_uniqueid_counter();
	}
	
	print_description_row(
		"<select name=\"condition[$name]\" id=\"sel_{$name}_$uniqueid\" tabindex=\"1\" class=\"bginput\"" . iif($size, " size=\"$size\"") . iif($multiple, ' multiple="multiple"') . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . ">\n" . 
		construct_select_options($array, $selected, $htmlise) .
		"</select>\n" . 
		iif($printdelete, "<input type=\"checkbox\" name=\"removecondition[$name]\" id=\"chk_{$name}_$uniqueid\" class=\"bginput\" value=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " />") . 
		iif($printdelete, "<strong>" . $vbphrase['delete'] . "</strong>")
	);
}

// #############################################################################
/**
 * Constructs a bitfield row
 *
 * @param	string	The label text
 * @param	string	The name of the row for the form
 * @param	string	What bitfields we are using
 * @param	integer	The value of the setting
 */	
function print_bitfield_row($text, $name, $bitfield, $value)
{
	global $vbulletin, $vbphrase;

	require_once(DIR . '/includes/adminfunctions.php');
	require_once(DIR . '/includes/adminfunctions_options.php');
	
	// make sure all rows use the alt1 class
	$bgcounter--;

	$value = intval($value);
	$HTML = '';
	$bitfielddefs =& fetch_bitfield_definitions($bitfield);

	if ($bitfielddefs === NULL)
	{
		print_label_row($text, construct_phrase("<strong>$vbphrase[settings_bitfield_error]</strong>", implode(',', vB_Bitfield_Builder::fetch_errors())), '', 'top', $name, 40);
	}
	else
	{
		#$HTML .= "<fieldset><legend>$vbphrase[yes] / $vbphrase[no]</legend>";
		$HTML .= "<div id=\"ctrl_{$name}\" class=\"smallfont\">\r\n";
		$HTML .= "<input type=\"hidden\" name=\"{$name}[0]\" value=\"0\" />\r\n";
		foreach ($bitfielddefs AS $key => $val)
		{
			$val = intval($val);
			$HTML .= "<table style=\"width:175px; float:left\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">
			<td><input type=\"checkbox\" name=\"{$name}[$val]\" id=\"{$name}_$key\" value=\"$val\"" . (($value & $val) ? ' checked="checked"' : '') . " /></td>
			<td width=\"100%\" style=\"padding-top:4px\"><label for=\"{$name}_$key\" class=\"smallfont\">" . fetch_phrase_from_key($key) . "</label></td>\r\n</tr></table>\r\n";
		}

		$HTML .= "</div>\r\n";
		#$HTML .= "</fieldset>";
		print_label_row($text, $HTML, '', 'top', $name, 40);
	}		
}

if (intval($vbulletin->versionnumber) == 3)
{
// #############################################################################
/**
* Prints a dialog box asking if the user if they want to continue
*
* @param	string	Phrase that is presented to the user
* @param	string	PHP script to which the form will submit
* @param	string	'do' action for target script
* @param	mixed		If not empty, an array containing name=>value pairs to be used as hidden input fields
*/
function print_confirmation($phrase, $phpscript, $do, $hiddenfields = array())
{
	global $vbulletin, $vbphrase;

	echo "<p>&nbsp;</p><p>&nbsp;</p>";
	print_form_header($phpscript, $do, 0, 1, '', '75%');
	if (is_array($hiddenfields))
	{
		foreach($hiddenfields AS $varname => $value)
		{
			construct_hidden_code($varname, $value);
		}
	}
	print_table_header($vbphrase['confirm_action']);
	print_description_row("
		<blockquote><br />
		$phrase
		<br /></blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
}
}
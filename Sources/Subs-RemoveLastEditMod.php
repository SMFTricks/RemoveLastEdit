<?php

/**
 * @package Remove Last Edit
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

// The actions. That needs to be done first.
function rlem_actions(&$actionArray)
{
	global $modSettings;

	// Is the last edit message enabled?
	if (empty($modSettings['show_modify']))
		return;

		// Our main action, on which everything will be based.
		$actionArray['unsetedittime'] = array('Subs-RemoveLastEditMod.php', 'rlem');
}

// The permissions, allowing people to remove either their own or others' edit sign.
function rlem_permissions(&$permissionGroups, &$permissionList)
{
	global $modSettings;

	// Hide the permissions if it's disabled.
	if (empty($modSettings['show_modify']))
		return;

	// Add the language
	loadLanguage('RemoveLastEdit/');

	// Permission groups.
	$permissionGroups['membergroup']['simple'] = array('rlem_simple');
	$permissionGroups['membergroup']['classic'] = array('rlem_classic');

	// A list.
	$permissions = array(
		'rlem_do_own',
		'rlem_do_any',
	);

	// Add them.
	foreach ($permissions as $perm)
		$permissionList['membergroup'][$perm] = array(false, 'rlem_classic', 'rlem_simple');
}

// Just positions as a bridge between rlem_do and the topic view. Nothing more really.
function rlem()
{
	loadLanguage('RemoveLastEdit/');

	// Check if everything is set.
	if (empty($_REQUEST['msg']) || !isset($_REQUEST['msg']))
		fatal_lang_error('remove_last_edited_error_3', false);

	// Yeah everything's set... Now do your trick.
	rlem_do((int) $_REQUEST['msg']);
}

// The main function which does all the work.
// @param int $postid The ID of the post where the notice needs to be removed.
function rlem_do(int $postid)
{
	global $smcFunc, $scripturl, $user_info;

	// Need either permission...
	if (!allowedTo('rlem_do_any') && !allowedTo('rlem_do_own'))
		fatal_lang_error('remove_last_edited_error_2', false);

	// This empties out the parts with which SMF determines if the post was modified, thus tricking it into believing it's not modified at all.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}messages
		SET
			modified_time = {int:modified_time},
			modified_name = {string:modified_name}
		WHERE id_msg = {int:msgid}' . (allowedTo('rlem_do_own') && !allowedTo('rlem_do_any') ? '
			AND id_member = {int:userid}' : ''),
		array(
			'modified_time' => 0,
			'modified_name' => '',
			'msgid' => $postid,
			'userid' => $user_info['id'],
		)
	);

	// And we're done!
	redirectexit($scripturl . '?msg=' . $postid);
}

/**
 * Adds the "remove sign" button if they have enough permissions.
 * 
 * @param array $output The post output
 * @return void
 */
function rlem_display_context(&$output, $message)
{
	global $user_info, $scripturl, $txt;

	// Check if there's anything to do here
	if (!allowedTo('rlem_do_any') && !allowedTo('rlem_do_own') || $message['id_member'] != $user_info['id'] && !allowedTo('rlem_do_any'))
		return;

	// Load the language for the actual message...
	loadLanguage('RemoveLastEdit/');

	// Add the link, if possible...
	if (isset($output['modified']['last_edit_text']) && !empty($output['modified']['last_edit_text']))
		$output['modified']['last_edit_text'] .= ' - <a href="' . $scripturl . '?action=unsetedittime;msg=' . $message['id_msg'] . '">' . $txt['remove_edit_sign'] . '</a>';
}
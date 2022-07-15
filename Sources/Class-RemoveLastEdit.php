<?php

/**
 * @package Remove Last Edit
 * @version 1.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

class RemoveLastEdit
{
	/**
	 * Add the action to the actions array
	 * @param array $actions
	 * @return void
	 */
	public static function actions(&$actions) : void
	{
		global $modSettings;

		// Is the last edit message enabled?
		if (empty($modSettings['show_modify']))
			return;

		// Our main action, on which everything will be based.
		$actions['unsetedittime'] = array('Class-RemoveLastEdit.php', __CLASS__ . '::rlem');
	}

	/**
	 * Load the language file
	 * 
	 * @return void
	 */
	public static function language() : void
	{
		loadLanguage('RemoveLastEdit/');
	}

	/**
	 * Add the permissions
	 * 
	 * @return void
	 */
	public static function permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions) : void
	{
		global $modSettings;

		self::language();

		// Add the permission
		$permissionList['membergroup']['rlem_do'] = array(true, 'post');

		// Hide them when the feature is disabled
		if (empty($modSettings['show_modify']))
			$hiddenPermissions[] = 'rlem_do';
	}

	/**
	 * Illegal permissions to guests
	 * 
	 * @return void
	 */
	public static function guest_illegal() : void
	{
		global $context;

		$context['non_guest_permissions'][] = 'rlem_do';
	}

	/**
	 * Adds the "remove sign" button if they have enough permissions.
	 * 
	 * @param array $output The post output
	 * @return void
	 */
	public static function display_context(&$output, $message) : void
	{
		global $user_info, $scripturl, $txt;

		// Check if there's anything to do here
		if (!allowedTo('rlem_do_any') && !allowedTo('rlem_do_own') || $message['id_member'] != $user_info['id'] && !allowedTo('rlem_do_any'))
			return;

		// Load the language for the actual message...
		self::language();

		// Add the link, if possible...
		if (isset($output['modified']['last_edit_text']) && !empty($output['modified']['last_edit_text']))
			$output['modified']['last_edit_text'] .= ' - <a href="' . $scripturl . '?action=unsetedittime;msg=' . $message['id_msg'] . '">' . $txt['remove_edit_sign'] . '</a>';
	}

	/**
	 * Do the actual removal of the edit sign and verification
	 * 
	 * @return void
	 */
	public static function rlem() : void
	{
		global $user_info, $scripturl, $smcFunc, $user_info;

		// Language
		self::language();

		// Check if everything is set.
		if (empty($_REQUEST['msg']) || !isset($_REQUEST['msg']))
			fatal_lang_error('no_posts_selected', false);

		// Need either permission...
		if (!allowedTo('rlem_do_any'))
			isAllowedTo('rlem_do_own');

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
				'msgid' => (int) $_REQUEST['msg'],
				'userid' => $user_info['id'],
			)
		);

		// And we're done!
		redirectexit($scripturl . '?msg=' . $_REQUEST['msg']);
	}
}
<?php
/**
 * Elgg logout action
 *
 * @package Elgg
 * @subpackage Core
 * @author Curverider Ltd
 * @link http://elgg.org/
 */

if (isset($_SESSION['remote_login_referer']) && $_SESSION['remote_login_referer'] != '')
  $redirect_after_logout = $_SESSION['remote_login_referer'];

// Log out
$result = logout();

if (isset($redirect_after_logout) && $redirect_after_logout != '')
  forward ($redirect_after_logout);

// Set the system_message as appropriate
if ($result) {
	system_message(elgg_echo('logoutok'));
} else {
	register_error(elgg_echo('logouterror'));
}
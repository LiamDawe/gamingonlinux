<?php
define('M_KEY', $core->config('mastodon_key'));
define('M_USERNAME', $core->config('mastodon_username'));
define('M_PASSWORD', $core->config('mastodon_password'));

function post_mastodon_status($status)
{
	require_once("autoload.php");

	$t = new \theCodingCompany\Mastodon();

	$token_info = $t->createApp("GamingOnLinux", "https://www.gamingonlinux.com");

	$auth_url = $t->getAuthUrl();

	$token_info = $t->getAccessToken(M_KEY);

	$status = $t->authenticate(M_USERNAME, M_PASSWORD)->postStatus($status . ' #Linux #LinuxGaming');
}

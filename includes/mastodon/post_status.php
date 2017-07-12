<?php
define('M_ID', $core->config('mastodon_client_id'));
define('M_SECRET', $core->config('mastodon_secret'));
define('M_BEARER', $core->config('mastodon_bearer'));

function post_mastodon_status($status)
{
	require_once("autoload.php");

	$t = new \theCodingCompany\Mastodon();
	
	$credentials = ["client_id" => M_ID, "client_secret" => M_SECRET, "bearer" => M_BEARER];

	$t->setCredentials($credentials);

	$t->postStatus($status . ' #Linux #LinuxGaming');
}

<?php
class session
{
	protected $db;
	private $core;
	private $templating;
	public $cookie_domain = '';

	function __construct($dbl, $core, $templating)
	{
		$this->db = $dbl;
		$this->core = $core;
		$this->templating = $templating;

		if (empty($this->core->config('cookie_domain')))
		{
			$this->cookie_domain = NULL;
		}
		else
		{
			// if cookie is set to www., use NULL for cookie domain so it sets properly, otherwise cookie has a subdomain
			$url = preg_replace("(^https?://)", "", $this->core->config('cookie_domain') );
			if ($this->core->config('cookie_domain') == $url)
			{
				$this->cookie_domain = NULL;
			}
			else
			{
				$this->cookie_domain = $this->core->config('cookie_domain');
			}
		}
	}

	function login_form($current_page = '')
	{
		$this->templating->block('main', 'login');
		$this->templating->set('url', $this->core->config('website_url'));

		$username = '';
		if (isset($_SESSION['login_error_username']))
		{
			$username = $_SESSION['login_error_username'];
		}
		$this->templating->set('username', $username);

		$this->templating->set('current_page', $current_page);
		
		$twitter_button = '';
		if ($this->core->config('twitter_login') == 1)
		{	
			$twitter_button = '<a href="'.$this->core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img alt="" src="'.$this->core->config('website_url'). 'templates/' . $this->core->config('template') .'/images/network-icons/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a>';
		}
		$this->templating->set('twitter_button', $twitter_button);
		
		$steam_button = '';
		if ($this->core->config('steam_login') == 1)
		{
			$steam_button = '<a href="'.$this->core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img alt="" src="'.$this->core->config('website_url'). 'templates/' . $this->core->config('template') .'/images/network-icons/white/steam.png" /> </span>Sign in with <b>Steam</b></a>';
		}
		$this->templating->set('steam_button', $steam_button);
		
		$google_button = '';
		if ($this->core->config('google_login') == 1)
		{
			$client_id = $this->core->config('google_login_public'); 
			$client_secret = $this->core->config('google_login_secret');
			$redirect_uri = $this->core->config('website_url') . 'includes/google/login.php';
			require_once ($this->core->config('path') . 'includes/google/libraries/Google/autoload.php');
			$client = new Google_Client();
			$client->setClientId($client_id);
			$client->setClientSecret($client_secret);
			$client->setRedirectUri($redirect_uri);
			$client->addScope("email");
			$client->addScope("profile");
			$service = new Google_Service_Oauth2($client);
			$authUrl = $client->createAuthUrl();
			
			$google_button = '<a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img alt="" src="'.$this->core->config('website_url'). 'templates/' . $this->core->config('template') .'/images/network-icons/google.svg" /> </span>Sign in with <b>Google</b></a>';
		}
		$this->templating->set('google_button', $google_button);		
	}
}

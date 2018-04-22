<?php
error_reporting(E_ERROR | E_PARSE | E_WARNING);

require "functions.php";

class steam_user
{
	public static $apikey;
	public static $domain;
	public static $return_url;
	public $data_array;
	public $user;
	private $core;
	protected $dbl;

	function __construct($dbl, $user, $core)
	{
		$this->user = $user;
		$this->core = $core;
		$this->dbl = $dbl;
	}

	public function GetPlayerSummaries ($steamid)
	{
		$response = core::file_get_contents_curl('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
		$json = json_decode($response);
		return $json->response->players[0];
	}

	public function signIn ()
	{
		require_once 'openid.php';
		$openid = new LightOpenID($this->domain);// put your domain
		if(!$openid->mode)
		{
			$openid->identity = 'https://steamcommunity.com/openid';
			$openid->returnUrl = $this->core->config('website_url') . 'index.php?module=login&steam&real_return=' . $this->return_url;
			header('Location: ' . $openid->authUrl());
		}
		elseif($openid->mode == 'cancel')
		{
			print ('User has canceled authentication!');
		}
		else
		{
			if($openid->validate())
			{
				preg_match("/^https:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/", $openid->identity, $matches);

				$get_info = $this->GetPlayerSummaries($matches[1]);

				$steam_user = new check_user($this->dbl);
				$userdata = $steam_user->check_that_id($matches[1], $get_info->personaname);

				// linking account via usercp
				if ($steam_user->new == 0)
				{
					header("Location: /usercp.php");
				}

				// logging in via steam
				else if ($steam_user->new == 1)
				{
					$this->user->user_details = $userdata;

					// update IP address and last login
					$this->dbl->run("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $userdata['user_id']));

					$this->user->check_banned();

					$generated_session = md5(mt_rand  . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					$this->user->new_login($generated_session);

					setcookie('gol_stay', $userdata['user_id'],  time()+31556926, '/', $this->core->config('cookie_domain'));
					setcookie('gol_session', $generated_session,  time()+31556926, '/', $this->core->config('cookie_domain'));
					
					header("Location: {$_GET['real_return']}");
				}

				// registering a new account with a steam account, send them to register with the steam data
				else if($steam_user->new == 2)
				{
					$get_info = $this->GetPlayerSummaries($matches[1]);
					$_SESSION['steam_id'] = $get_info->steamid;
					$_SESSION['steam_username'] = $get_info->personaname;
					header("Location: /index.php?module=register&steam_new");
				}
			}
			else
            {
                print ('fail');
            }
        }
    }
}

<?php
error_reporting(E_ERROR | E_PARSE | E_WARNING);

require "functions.php";

class steam_user
{
	public static $apikey;
	public static $domain;
	public static $return_url;
	public $data_array;

	public function GetPlayerSummaries ($steamid)
	{
		global $core;

		$response = $core->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
		$json = json_decode($response);
		return $json->response->players[0];
	}

	public function signIn ()
	{
		global $db, $core;

		require_once 'openid.php';
		$openid = new LightOpenID($this->domain);// put your domain
		if(!$openid->mode)
		{
			$openid->identity = 'http://steamcommunity.com/openid';
			$openid->returnUrl = core::config('website_url') . 'index.php?module=login&steam&real_return=' . $this->return_url;
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
				preg_match("/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/", $openid->identity, $matches); // steamID: $matches[1]
				setcookie('steamID', $matches[1], time()+(60*60*24*7), '/'); // 1 week

				$get_info = $this->GetPlayerSummaries($matches[1]);

				$steam_user = new check_user();
				$userdata = $steam_user->check_that_id($matches[1], $get_info->personaname);

				// linking account via usercp
				if ($steam_user->new == 0)
				{
					header("Location: /usercp.php");
				}

				// logging in via steam
				else if ($steam_user->new == 1)
				{
					// update IP address and last login
					$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $userdata['user_id']));

					$user->check_banned($userdata['user_id']);

					$generated_session = md5(mt_rand  . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					user::new_login($userdata, $generated_session);

					if ($_COOKIE['request_stay'] == 1)
					{
						setcookie('gol_stay', $userdata['user_id'],  time()+31556926, '/', core::config('cookie_domain'));
						setcookie('gol_session', $generated_session,  time()+31556926, '/', core::config('cookie_domain'));
					}

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

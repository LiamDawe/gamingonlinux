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
		$response = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
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
			$openid->returnUrl = 'http://www.gamingonlinux.com/index.php?module=login&steam&real_return=' . $this->return_url;
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
					$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array($core->ip, $core->date, $userdata['user_id']));

					// check if they are banned
					$db->sqlquery("SELECT `banned` FROM `users` WHERE `user_id` = ?", array($userdata['user_id']), 'class_user.php');
					$ban_check = $db->fetch();

					if ($ban_check['banned'] == 1)
					{
						setcookie('gol_stay', "",  time()-60, '/');
						header("Location: /home/banned");
						exit;
					}

					$device_id = '';
					// register the new device to their account, could probably add a small hook here to allow people to turn this email off at their own peril
					if ($new_device == 1)
					{
						$device_id = md5(mt_rand() . $user['user_id'] . $_SERVER['HTTP_USER_AGENT']);

						setcookie('gol-device', $device_id, time()+31556926, '/', 'gamingonlinux.com');

						if ($user['login_emails'] == 1)
						{
							// send email about new device
							$message = "<p>Hello <strong>{$user['username']}</strong>,</p>
							<p>We have detected a login from a new device, if you have just logged in yourself don't be alarmed (your cookies may have just been wiped at somepoint)! However, if you haven't just logged into the GamingOnLinux website you may want to let TheBoss and Levi know and change your password immediately.</p>
							<div>
							<hr>
							Login detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s") . "
							<hr>
							<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:contact@gamingonlinux.com\" target=\"_blank\">contact@gamingonlinux.com</a> with some info about what you want us to do about it.</p>
							<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
							<p>-----------------------------------------------------------------------------------------------------------</p>
							</div>";

							$plain_message = "Hello {$user['username']},\r\nWe have detected a login from a new device, if you have just logged in yourself don't be alarmed! However, if you haven't just logged into the GamingOnLinux (https://www.gamingonlinux.com) website you may want to let TheBoss and Levi know and change your password immediately.\r\n\r\nLogin detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s");

							$mail = new mail($user['email'], "GamingOnLinux: New Login Notification", $message, $plain_message);
							$mail->send();
						}
					}
					else
					{
						$device_id = $_COOKIE['gol-device'];
					}

					$generated_session = md5(mt_rand  . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					if ($_COOKIE['request_stay'] == 1)
					{
						setcookie('gol_stay', $userdata['user_id'],  time()+31556926, '/', 'gamingonlinux.com');
						setcookie('gol_session', $generated_session,  time()+31556926, '/', 'gamingonlinux.com');
					}

					$db->sqlquery("INSERT INTO `saved_sessions` SET `user_id` = ?, `session_id` = ?, `browser_agent` = ?, `device-id` = ?, `date` = ?", array($userdata['user_id'], $generated_session, $_SERVER['HTTP_USER_AGENT'], $device_id, date("Y-m-d")));

					$_SESSION['user_id'] = $userdata['user_id'];
					$_SESSION['username'] = $userdata['username'];
					$_SESSION['user_group'] = $userdata['user_group'];
					$_SESSION['secondary_user_group'] = $userdata['secondary_user_group'];
					$_SESSION['theme'] = $userdata['theme'];
					$_SESSION['in_mod_queue'] = $userdata['in_mod_queue'];
					$_SESSION['logged_in'] = 1;

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

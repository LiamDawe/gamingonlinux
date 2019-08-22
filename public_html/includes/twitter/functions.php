<?php
class twitter_user
{
	// 0 = linking from usercp
	// 1 = logging in using twitter that has already been setup
	// 2 = making a new user
	public $new = 0;

	function checkUser($uid, $oauth_provider, $username)
	{
		global $dbl, $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$result = $dbl->run("SELECT ".user::$user_sql_fields." FROM `users` WHERE oauth_uid = ? and oauth_provider = ? AND `twitter_username` = ?", array($uid, $oauth_provider, $username))->fetch();
			if (!empty($result)) // logging in via twitter
			{
				$this->new = 1;
				return $result;
			}

			else // registering a new account with a twitter handle, send them to register with the twitter data
			{
				$this->new = 2;

				$result = array();

				$result['uid'] = $uid;
				$result['oauth_provider'] = $oauth_provider;
				$result['twitter_username'] = $username;

				return $result;
			}


		}

		// if they are linking via usercp to a logged in account
		else
		{
			$dbl->run("UPDATE `users` SET oauth_provider = ?, oauth_uid = ?, `twitter_username` = ? WHERE `user_id` = ?", array($oauth_provider, $uid, $username, $_SESSION['user_id']));
			$this->new = 0;
			return;
		}
	}
}
?>

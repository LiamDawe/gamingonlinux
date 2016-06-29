<?php
class twitter_user
{
	// 0 = linking from usercp
	// 1 = logging in using twitter that has already been setup
	// 2 = making a new user
	public $new = 0;

	function checkUser($uid, $oauth_provider, $username)
	{
		global $db, $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$db->sqlquery("SELECT `user_id`, `username`, `user_group`, `secondary_user_group`, `banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails`, `forum_type` FROM `users` WHERE oauth_uid = ? and oauth_provider = ? AND `twitter_username` = ?", array($uid, $oauth_provider, $username));
			$result = $db->fetch();
			if (!empty($result))
			{
				$this->new = 1;
				return $result;
			}

			else
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
			$db->sqlquery("UPDATE `users` SET oauth_provider = ?, oauth_uid = ?, `twitter_username` = ? WHERE `user_id` = ?", array($oauth_provider, $uid, $username, $_SESSION['user_id']));
			$this->new = 0;
			return;
		}
	}
}
?>

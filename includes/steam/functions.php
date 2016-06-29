<?php
class check_user
{
	// 0 = linking from usercp
	// 1 = logging in using steam that has already been setup
	// 2 = making a new user
	public $new = '';

	function check_that_id($steam_id, $steam_username)
	{
		global $db, $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$db->sqlquery("SELECT `user_id`, `single_article_page`, `per-page`, `articles-per-page`, `username`, `user_group`, `secondary_user_group`, `banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails` FROM `users` WHERE `steam_id` = ?", array($steam_id));
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

				$result['steam_id'] = $steam_id;

				return $result;
			}


		}

		// if they are linking via usercp to a logged in account
		else
		{
			$db->sqlquery("UPDATE `users` SET steam_id = ?, `steam_username` = ? WHERE `user_id` = ?", array($steam_id, $steam_username, $_SESSION['user_id']));
			$this->new = 0;
			return;
		}
	}
}
?>

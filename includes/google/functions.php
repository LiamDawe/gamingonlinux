<?php
class google_check
{
	// 0 = linking from usercp
	// 1 = logging in using google that has already been setup
	// 2 = making a new user
	public $new = 0;

	function checkUser($google_id, $google_email)
	{
		global $db, $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$get_user = $db->sqlquery("SELECT ".user::$user_sql_fields." FROM `users` WHERE `google_id` = ? AND `google_email` = ?", array($google_id, $google_email));
			$result = $get_user->fetch();
			if (!empty($result))
			{
				$this->new = 1;
				return $result;
			}

			else
			{
				$this->new = 2;

				$result = array();

				$result['google_id'] = $google_id;
				$result['google_email'] = $google_email;

				return $result;
			}


		}

		// if they are linking via usercp to a logged in account
		else
		{
			$db->sqlquery("UPDATE `users` SET `google_id` = ?, `google_email` = ? WHERE `user_id` = ?", array($google_id, $google_email, $_SESSION['user_id']));
			$this->new = 0;
			return;
		}
	}
}
?>

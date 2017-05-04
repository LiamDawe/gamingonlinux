<?php
class google_check
{
	// 0 = linking from usercp
	// 1 = logging in using google that has already been setup
	// 2 = making a new user
	public $new = 0;
	
	private $database;
	
	function __construct($database)
	{
		$this->database = $database;
	}

	function checkUser($google_email)
	{
		global $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$result = $this->database->run("SELECT ".user::$user_sql_fields." FROM `users` WHERE `google_email` = ?", array($google_email))->fetch();
			if (!empty($result))
			{
				$this->new = 1;
				return $result;
			}

			else
			{
				$this->new = 2;

				$result = array();

				$result['google_email'] = $google_email;

				return $result;
			}


		}

		// if they are linking via usercp to a logged in account
		else
		{
			if (isset($_SESSION['user_id']))
			{
				$this->database->run("UPDATE `users` SET `google_email` = ? WHERE `user_id` = ?", array($google_email, $_SESSION['user_id']));
				$this->new = 0;
				return;
			}
			else
			{
				die('Session error');
			}
		}
	}
}
?>

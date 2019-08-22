<?php
class check_user
{
	// 0 = linking from usercp
	// 1 = logging in using steam that has already been setup
	// 2 = making a new user
	public $new = '';

	protected $dbl;
	
	function __construct($dbl)
	{
		$this->dbl = $dbl;
	}

	function check_that_id($steam_id, $steam_username)
	{
		global $core;

		// if they are logging in
		if ($_SESSION['user_id'] == 0)
		{
			$result = $this->dbl->run("SELECT ".user::$user_sql_fields." FROM `users` WHERE `steam_id` = ?", array($steam_id))->fetch();
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
			$this->dbl->run("UPDATE `users` SET steam_id = ?, `steam_username` = ? WHERE `user_id` = ?", array($steam_id, $steam_username, $_SESSION['user_id']));
			$this->new = 0;
			return;
		}
	}
}
?>

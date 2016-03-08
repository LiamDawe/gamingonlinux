<?php
class usersOnline
{
	private $timeout = 600;
	public $count = 0;

	function __construct ()
	{
		$this->timestamp = time();
		$this->session_id = session_id();
		$this->delete_user();
		$this->new_user();
	}

	function new_user()
	{
		global $db;

		// first make sure session id doesn't exist already
		$db->sqlquery("SELECT `session_id` FROM `online_list` WHERE `session_id` = ?", array($this->session_id));

		if ($db->num_rows() != 1 && isset($_SESSION['user_id']))
		{
			$db->sqlquery("INSERT INTO online_list(user_id, timestamp, session_id) VALUES (?, ?, ?)", array($_SESSION['user_id'], $this->timestamp, $this->session_id));
		}
	}

	function delete_user()
	{
		global $db;

		$stamp = $this->timestamp - $this->timeout;
		$db->sqlquery("DELETE FROM online_list WHERE timestamp < (?)", array($stamp));
	}

	// this is done manually in block_online.php
	function count_users()
	{
		global $db;

		$db->sqlquery("SELECT DISTINCT session_id FROM online_list");
		$count = $db->num_rows();
		return $count;
	}
}
?>

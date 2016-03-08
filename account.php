<?php

/**
* 
*/
class account implements ArrayAccess
{
	
	protected static $accounts = array();
	protected $userdata = array();

	function __construct($id=NULL)
	{
		global $db;
		if ($id !== NULL){
			$db->sqlquery('SELECT * FROM `users` WHERE `user_id` = ? LIMIT 1', [$id]);
			if ($db->num_rows() === 1){
				$this->userdata = $db->fetch();
			} else {
				throw new Exception("User does not exist", 1);
			}
		} else {
			// NEW USER WOOOH
		}
		//Do house cleaning here
	}

	/** Magic methods **/

	function __get($key)
	{
		if (array_key_exists($key, $this->userdata)){
			return $this->userdata[$key];
		}
		return NULL;
	}

	function __set($key, $val)
	{
		if (array_key_exists($key, $this->userdata)){
			$this->userdata[$key] = $val;
		}
	}

	function __isset($key)
	{
		if (array_key_exists($key, $this->userdata) || array_key_exists($key, get_object_vars($this)) ){
			return true;
		}
		return false;
	}

	public function offsetSet($offset, $value) {
	    if (!is_null($offset)) {
	    	if (!array_key_exists($offset, $this->userdata)) return; //No new keys allowed
	        $this->userdata[$offset] = $value;
	    }
	}

	public function offsetExists($offset) {
	    return isset($this->userdata[$offset]);
	}

	public function offsetUnset($offset) {
		if (!array_key_exists($offset, $this->userdata)) return; //No new keys allowed
		if (in_array($offset, ['user_id', 'register_date'])) return; //protected fields
	    $this->userdata[$offset] = "";
	}

	public function offsetGet($offset) {
	    return isset($this->userdata[$offset]) ? $this->userdata[$offset] : null;
	}

	/** Statics **/

	/**
	* Get a instance of account
	* @param int $id The user id
	* @return instace of account
	**/
	public static function get($id)
	{
		if (!isset(self::$accounts[$id])){
			self::$accounts[$id] = new self($id);
		}
		return self::$accounts[$id];
	}

	/** Normal object methods **/

	/**
	* Save all current userdata to database
	*
	**/
	public function save()
	{
		global $db;// Hate this, would be awesome if one could do db::get() to get a db instance;

		$params = array();
		$sql = 'UPDATE `users` SET';
		foreach ($this->userdata as $key => $value) {
			if (in_array($key, ['user_id', 'register_date'])) continue; // Skip these
			$params[] = $value;
			$sql .= ' '.$key . '= ?,';
		}
		trim($sql, ',');
		$sql .= ' WHERE user_id = ?';

		$params[] = $this->user_id;

		$db->sqlquery($sql, $params);
		unset(self::$accounts[$this->user_id]); //Invalidate cached version to be sure
	}

	public function avatar()
	{
		global $config; //Hate this
		if ($this->avatar_gravatar == "1"){
			if (!is_null($this->gravatar_email)){
				return "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $this->gravatar_email ) ) ) . "?d={$config['website_url']}{$config['path']}/uploads/avatars/no_avatar.png";
			}
		} 
		if (!empty($this->avatar) && $this->avatar_uploaded == "1" ) {
			return $config['path']."uploads/avatars/".$this->avatar;
		}

		return $config['website_url'].$config['path'].'uploads/avatars/no_avatar.png';
	}

}
<?php

class base {
	
	protected static $table = NULL;
	protected static $primary = "id";

	protected $data = [];
	protected $changed = [];

	protected $db;

	function __construct(array $data=[]){
		//Allow for creating of new row by way of new foo(["name"=>"jan"])
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}

		if (!isset($data[static::$primary])){
			$this->${static::$primary} = NULL;
		}

		DI::injectOn($this);
	}

	function setDb(mysql $db){
		$this->db = $db;
	}

	function __get($key)
	{
		//First check if it was changed
		if (isset($this->changed[$key])){
			return $this->changed[$key];
		}
		// Nothing changed, maybe the table has the data
		if (isset($this->data[$key])){
			return $this->data[$key];
		}
	}

	function __set($key, $val)
	{
		// Only allow changes to existing table columns
		if (isset($this->data[$key])){
			$this->changed[$key] = $val;
		}
	}

	protected static function table(){
		return (!empty(static::$table)? static::$table : get_called_class());
	}

	protected static function oneOrMore(array $data){
		if (count($data) == 1){
			var_dump($data); //Check if this is an array of arrays. The first one might not have any data at all
			return new static($data);
		} else {
			$o = [];
			foreach ($data as $row) {
				$o[] = new static($data);
			}
			return $o;
		}
	}

	public static function where($column, $type, $arg=NULL)
	{
		$db = mysql::getInstance();
		if (in_array($type, ["=", ">=", "<=", "like"]) && $arg !== NULL){
			$b = $db->sqlquery( sprintf("SELECT * FROM %s WHERE %s %s ?", static::table(), $column, $type), [$arg] , mysql::DONOTSTORELAST);
			return self::oneOrMore($b->fetch_all_rows());
		} else if ($arg === NULL) {
			$b = $db->sqlquery( sprintf("SELECT * FROM %s WHERE %s = ?", static::table(), $column), [$type], mysql::DONOTSTORELAST);
			var_dump( sprintf("SELECT * FROM %s WHERE %s = ?", static::table(), $column), $type, $b->num_rows() );
			return self::oneOrMore($b->fetch_all_rows());
		}
	}


	/**
	* Find an row based on it's primary ID
	*
	**/
	public static function find($id)
	{
		return static::where(static::$primary, $id);
	}

	/**
	* Save all changed values to the database
	*
	**/
	public function save()
	{
		if ( !empty($this->changed) ){
			$cols = array_keys($this->changed);
			foreach ($cols as $key => $value) {
				$cols[$key] = $value . " = ?";
			}
			$cols = implode(", ", $cols); // Convert to "columnname = ?, columnname = ?, columnname = ?"

			$vals = array_values($this->changed);
			array_push($vals, $this->${static::$primary}); // Append the primary key value to the update vals

			$db = mysql::getInstance();
			$db->sqlquery( sprintf("UPDATE %s SET %s WHERE %s = ?", static::table(), $cols), $vals, mysql::DONOTSTORELAST);			

		}
	}


}
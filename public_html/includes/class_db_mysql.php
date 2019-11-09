<?php
class db_mysql extends PDO
{
	public $stmt;
	
	// the query counter
	public $counter = 0;

	// store all the queries for debugging
	public $debug_queries = [];
	
	public function __construct()
	{
		try
		{
			$options = [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
			];
			parent::__construct("mysql:host=".DB['DB_HOST_NAME'].";dbname=".DB['DB_DATABASE'],DB['DB_USER_NAME'],DB['DB_PASSWORD'], $options);
		}
        catch (PDOException $error)
        {
			$trace = $error->getTrace();
			// if we don't find the mysql server, wait a bit and retry (down for updates? broken?)
			if ($error->getCode() == '2002')
			{
				sleep(30); // give it 45 seconds to come back
				try
				{
					$options = [
						PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
						PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
					];
					parent::__construct("mysql:host=".DB['DB_HOST_NAME'].";dbname=".DB['DB_DATABASE'],DB['DB_USER_NAME'],DB['DB_PASSWORD'], $options);
				}
				catch (PDOException $error)
				{
					error_log('SQL ERROR ' . $error->getMessage());
					include(dirname(dirname(__FILE__)).'/sql_error.html');
					die();				
				}
			}
			else
			{
				error_log('SQL ERROR ' . $error->getMessage());
				include(dirname(dirname(__FILE__)).'/sql_error.html');
				die();	
			}
        }
	}
	
	// the most basic query
    public function run($sql, $data = NULL)
    {
		try
		{
			$start = microtime(true);
			$this->stmt = $this->prepare($sql);
			$this->stmt->execute($data);
			$end = microtime(true);
			$debug_array_items = array("query" => $this->replaced_query($sql, $data), "time" => "This query took " . ($end - $start) . " seconds.");
			$this->debug_queries[] = $debug_array_items;
			$this->counter++;
			return $this;
        }
        catch (PDOException $error)
        {
			$trace = $error->getTrace();
			error_log('SQL ERROR ' . $error->getMessage() . "\n" . 'Full SQL: ' . $sql . "\nURL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			include(dirname(dirname(__FILE__)).'/sql_error.html');
			die();
        }
	}
	
	/* For inserting multiple rows at the same time
	EXAMPLE:
	SQL = insert into `user_notifications` (field1, field2, field3) values
	Values = array([1,2,3], [4,5,6])
	*/
	public function insert_multi($sql, $rows)
	{
		$paramArray = array();

		$sqlArray = array();

		foreach($rows as $row)
		{
			$sqlArray[] = '(' . str_repeat('?,', count($row) - 1) . '?'  . ')';

			foreach($row as $element)
			{
				$paramArray[] = $element;
			}
		}

		$sql .= implode(',', $sqlArray);
		$this->stmt = $this->prepare($sql);
		$this->stmt->execute($paramArray);
		$this->counter++;
	}
	
	// This is used for grabbing a single column, setting the data to it directly, so you don't have to call it again
	// so $result instead of $result['column']
	// Also used for counting rows SELECT count(*) FROM t, returning the number of rows
	public function fetchOne()
	{		
		$this->result = $this->stmt->fetchColumn();
		
		return $this->result;
	}
	
	public function fetch($mode = PDO::FETCH_ASSOC)
	{		
		$this->result = $this->stmt->fetch($mode);
		
		return $this->result;
	}
	
	public function fetch_all($mode = NULL)
	{		
		$this->result = $this->stmt->fetchAll($mode);
		
		return $this->result;
	}
	
	// get the last auto made ID
	public function new_id()
	{
		$this->result = $this->lastInsertId();
		
		return $this->result;
	}

	public function rowcount()
	{
		$this->result = $this->stmt->rowCount();
		
		return $this->result;		
	}

	function replaced_query($query, $params)
	{
		if (isset($params))
		{
			$keys = array();

			# build a regular expression for each parameter
			foreach ($params as $key => $value) 
			{
				if (is_string($key)) 
				{
					$keys[] = '/:'.$key.'/';
				} 
				else 
				{
					$keys[] = '/[?]/';
				}
			}

			$query = preg_replace($keys, $params, $query, 1, $count);
		}
		return $query;
	}
}

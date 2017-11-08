<?php
class db_mysql extends PDO
{	
	protected static $instance;

	public $stmt;
	
	// the query counter
	public static $counter = 0;

	// store all the queries for debugging
	public static $debug_queries = [];
	
	public function __construct()
	{
		$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ];
        parent::__construct("mysql:host=".DB['DB_HOST_NAME'].";dbname=".DB['DB_DATABASE'],DB['DB_USER_NAME'],DB['DB_PASSWORD'], $options);
	}

    // a classical static method to make it universally available
    public static function instance()
    {
        if (self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }
	
	// the most basic query
    public function run($sql, $data = NULL)
    {
		try
		{
			$this->stmt = $this->prepare($sql);
			$this->stmt->execute($data);
			self::$debug_queries[] = $this->replaced_query($sql, $data);
			self::$counter++;
			return $this;
        }
        catch (PDOException $error)
        {
			$trace = $error->getTrace();
			error_log('SQL ERROR ' . $error->getMessage());
			die('SQL Error');
        }
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

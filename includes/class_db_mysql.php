<?php
$file_dir = dirname( dirname( __FILE__ ) );

require_once $file_dir. "/includes/EPDOStatement.php";
	
class db_mysql extends PDO
{		
	public $sql = '';
	
	public $query;
	
	public $values;
	
	// the query counter
	public $counter = 0;

	// store all the queries for debugging
	public $debug_queries = '';
	
	public function __construct($dsn, $username, $password)
	{
		$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STATEMENT_CLASS => array("EPDOStatement\EPDOStatement", array($this)),
            PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ];
        parent::__construct($dsn, $username, $password, $options);
	}
	
	public function select($table, $fields = '*')
	{
		$this->sql = ' SELECT ' . $fields . ' FROM `' . $table . '`';
		return $this;
	}
	
	public function where($where)
	{
		$this->sql = $this->sql . ' WHERE ' . $where;
		return $this;
	}
	
	public function update($table, $fields = NULL)
	{
		$this->sql = ' UPDATE ' . $table . ' SET ' . $fields;
		$this->query = $this->prepare($this->sql);
		$this->debug_queries .= '<pre>' . $this->query->interpolateQuery() . '</pre>';
		$this->result = $this->query->fetchAll($mode);
		$this->counter++;
	}

	// a basic plain query with nothing attached
	// EITHER THIS OR THIS QUERY STRING NEEDS RENAMING
	public function query()
	{
		$results = $this->query($this->sql);

		return $results;
	}
	
	// This is used for grabbing a single column, setting the data to it directly, so you don't have to call it again
	// so $result instead of $result['column']
	// Also used for counting rows SELECT count(1) FROM t, returning the number of rows
	public function fetchOne($mode = PDO::FETCH_ASSOC)
	{
		$this->query = $this->prepare($this->sql);

		if (!empty($this->values))
		{
			$this->query->execute($this->values);
		}
		
		$this->debug_queries .= '<pre>' . $this->query->interpolateQuery() . '</pre>';
		
		$this->result = $this->query->fetchColumn($mode);
		$this->counter++;
		
		return $this->result;
	}
	
	public function fetch($mode = PDO::FETCH_ASSOC)
	{
		$this->query = $this->prepare($this->sql);

		if (!empty($this->values))
		{
			$this->query->execute($this->values);
		}
		
		$this->debug_queries .= '<pre>' . $this->query->interpolateQuery() . '</pre>';
		
		$this->result = $this->query->fetch($mode);
		$this->counter++;
		
		return $this->result;
	}
	
	public function fetch_all($mode = NULL)
	{
		$this->query = $this->prepare($this->sql);
		
		$this->query->execute($this->values);
		
		$this->debug_queries .= '<pre>' . $this->query->interpolateQuery() . '</pre>';
		
		$this->result = $this->query->fetchAll($mode);
		$this->counter++;
		
		return $this->result;
	}
	
	// get the last auto made ID
	public function new_id()
	{
		$this->result = $this->query->lastInsertId();
		
		return $this->result;
	}
	
	// for testing/showing the final query with all vars replaced
	public function show_query()
	{
		return $this->query->interpolateQuery();
	}
}

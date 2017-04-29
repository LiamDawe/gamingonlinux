<?php
$file_dir = dirname( dirname( __FILE__ ) );

require_once $file_dir. "/includes/EPDOStatement.php";
	
class db_mysql extends PDO
{	
	public $stmt;
	
	// the query counter
	public $counter = 0;

	// store all the queries for debugging
	public $debug_queries = '';
	
	public $table_prefix = '';
	
	public function __construct($dsn, $username, $password, $table_prefix)
	{
		$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STATEMENT_CLASS => array("EPDOStatement\EPDOStatement", array($this)),
            PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ];
        parent::__construct($dsn, $username, $password, $options);
        $this->table_prefix = $table_prefix;
	}
	
	// the most basic query
    public function run($sql, $data = NULL)
    {
		try
		{
			$this->stmt = $this->prepare($sql);
			$this->stmt->execute($data);
			$this->debug_queries .= '<pre>' . $this->stmt->interpolateQuery() . '</pre>';
			return $this;
        }
        catch (PDOException $error)
        {
			echo  $error->getMessage() . '<br /><strong>Plain Query:</strong><br />' . htmlspecialchars($sql) . '<br /><strong>Replaced Query:</strong><br />' . $this->stmt->interpolateQuery();
			die();
        }
    }
	
	// This is used for grabbing a single column, setting the data to it directly, so you don't have to call it again
	// so $result instead of $result['column']
	// Also used for counting rows SELECT count(*) FROM t, returning the number of rows
	public function fetchOne()
	{		
		$this->result = $this->stmt->fetchColumn();
		$this->counter++;
		
		return $this->result;
	}
	
	public function fetch($mode = PDO::FETCH_ASSOC)
	{		
		$this->result = $this->stmt->fetch($mode);
		$this->counter++;
		
		return $this->result;
	}
	
	public function fetch_all($mode = NULL)
	{		
		$this->result = $this->stmt->fetchAll($mode);
		$this->counter++;
		
		return $this->result;
	}
	
	// get the last auto made ID
	public function new_id()
	{
		$this->result = $this->lastInsertId();
		
		return $this->result;
	}
}

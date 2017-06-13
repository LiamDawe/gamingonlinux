<?php
$file_dir = dirname( dirname( __FILE__ ) );
	
class db_mysql extends PDO
{	
	public $stmt;
	
	// the query counter
	public $counter = 0;

	// store all the queries for debugging
	public $debug_queries = '';
	
	public function __construct($dsn, $username, $password)
	{
		$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // allows LIMIT placeholders
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ];
        parent::__construct($dsn, $username, $password, $options);
	}
	
	// the most basic query
    public function run($sql, $data = NULL)
    {
		try
		{
			$this->stmt = $this->prepare($sql);
			$this->stmt->execute($data);
			$this->debug_queries .= '<pre>' . $this->replaced_query($sql, $data) . '</pre>';
			return $this;
        }
        catch (PDOException $error)
        {
			$trace = $error->getTrace();
			//$this->pdo_error($error->getMessage(), $trace[2]['file'], $this->replaced_query($sql, $data), core::current_page_url());
			echo  $error->getMessage() . '<br /><strong>Plain Query:</strong><br />' . htmlspecialchars($sql) . '<br /><strong>Replaced Query:</strong><br />' . $this->replaced_query($sql, $data);
			die('SQL Error');
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
	
	function pdo_error($exception, $page, $sql, $url)
	{
		$to = core::config('contact_email');

		// subject
		$subject = "GOL PDO Error: " . core::config('site_title');

		$make_sql_safe = core::make_safe($sql);
		$make_url_safe = core::make_safe($url);

		// message
		$message = "
		<html>
		<head>
		<title>A PDO Error Report For ".core::config('site_title')."</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
		</head>
		<body>
		<img src=\"" . core::config('website_url') . core::config('template') . "/default/images/logo.png\" alt=\"".core::config('site_title')."\">
		<br />
		$exception on page <br />
		<strong>URL:</strong> $make_url_safe<br />
		SQL QUERY<br />
		$make_sql_safe<br />
		</body>
		</html>";

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

		// Mail it
		mail($to, $subject, $message, $headers);
	}
}

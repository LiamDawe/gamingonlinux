<?php
$file_dir = dirname( dirname( __FILE__ ) );

require_once $file_dir. "/includes/EPDOStatement.php";

class mysql
{
	// the query counter
	public $counter = 0;

	// store all the queries for debugging
	public $queries = '';

	//Last query that ran
	protected $last;

	// the database connection
	protected $database;

	public function __construct($database_host, $database_username, $database_password, $database_db)
	{
		$options = array(
		    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
		);
		$this->database = new PDO("mysql:host=$database_host;dbname=$database_db", $database_username, $database_password, $options);
		$this->database->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->database->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("EPDOStatement\EPDOStatement", array($this->database)));
	}

	// for storing decimals
	function contains_decimal( $value )
	{
		return ( strpos( $value, "." ) !== false );
	}

	// the main sql query function
	public function sqlquery($sql, $objects = NULL, $page = NULL, $referrer = NULL)
	{
		global $core, $templating;

		try
		{
			$STH = $this->database->prepare($sql);
			if (is_array($objects))
			{
				// reset the array keys, to be sure it starts at zero
				$set_objects = array_values($objects);
				foreach($set_objects as $k=>$p)
				{
					// +1 is needed as arrays start at 0 where as ? placeholders start at 1 in PDO
					if(is_numeric($p))
					{
						// we need to do this or else decimals always seem to end up 'x.00', php has no decimal check, odd
						// A number with decimal places is called a float or double in programming @ Piratelv
						if ($this->contains_decimal($p) == true)
						{
							$STH->bindValue($k+1, $p, PDO::PARAM_STR);
						}

						else
						{
							$STH->bindValue($k+1, (int)$p, PDO::PARAM_INT);
						}
					}
					else
					{
						$STH->bindValue($k+1, $p, PDO::PARAM_STR);
					}
				}
			}

			// add this to the list of queries being done for debugging
			$this->queries .= '<pre>' . $STH->interpolateQuery() . '</pre>';

			$this->last = new db_result($STH);

			$this->counter++;

			//Return the result object
			$this->last->execute();
			$this->last->setID($this->grab_id());

			return $this->last;
		}

		catch (Exception $error)
		{
			$trace = $error->getTrace();
			if (isset($_SESSION['user_group']) && ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2))
			{
				$core->message( $error->getMessage() . '<br /><strong>Plain Query:</strong><br />' . htmlspecialchars($sql) . '<br /><strong>Replaced Query:</strong><br />' . $STH->interpolateQuery(), NULL, 1);
				echo $templating->output();
				die();
			}
			else
			{
				$core->message("Something went wrong. The admin will be notified", NULL, 1);
				$this->pdo_error($error->getMessage(), $trace[2]['file'], $STH->interpolateQuery(), core::current_page_url());
				echo $templating->output();
				die();
			}
		}
	}

	public function fetch()
	{
		return $this->last->fetch();
	}

	public function fetch_all_rows($mode = NULL)
	{
		return $this->last->fetch_all_rows($mode);
	}

	public function num_rows()
	{
		return $this->last->num_rows();
	}

	// get the last auto made ID
	public function grab_id()
	{
		return $this->database->lastInsertId();
	}

	function pdo_error($exception, $page, $sql, $url)
	{
		$to = core::config('contact_email');

		// subject
		$subject = "GOL PDO Error: " . core::config('site_name');

		$make_sql_safe = core::make_safe($sql);
		$make_url_safe = core::make_safe($url);

		// message
		$message = "
		<html>
		<head>
		<title>A PDO Error Report For GamingOnLinux.com</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
		</head>
		<body>
		<img src=\"" . core::config('website_url') . core::config('template') . "/default/images/logo.png\" alt=\"Gaming On Linux\">
		<br />
		$exception on page <br />
		<strong>URL:</strong> $make_url_safe<br />
		SQL QUERY<br />
		$make_sql_safe<br />
		Referring Page<br />
		$referrer
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


/**
* Mysql Result
*/
class db_result implements ArrayAccess,Iterator
{
	public $success 	= false;
	public $id 			= false;
	private $position	= 0;

	function __construct($sth)
	{
		$this->statement = $sth;
		$this->statement->setFetchMode(PDO::FETCH_ASSOC);
	}


	public function execute($values=array())
	{
		if (!empty($values)){
			foreach ($values as $k => $v) {
				if (is_numeric($v) && !is_float($v)){
					$values[$k] = (int) $v;
				}
			}
		}
		$this->success = $this->statement->execute();
	}

	public function fetch()
	{
		return $this->statement->fetch();
	}

	public function fetch_all_rows($mode = NULL)
	{
		if (!isset($this->data)){
			$this->data = $this->statement->fetchAll($mode);;
		}
		return $this->data;
	}

	public function num_rows()
	{
		return $this->statement->rowCount();
	}

	public function setID($id)
	{
		$this->id = (int) $id;
	}

	/**
	 * @return int Last inserted ID at time of this query;
	 **/
	public function grab_id()
	{
		return $this->id;
	}



	/**  Allow access like an array **/
	public function offsetExists( $offset ){
		if (!isset($this->data)){  $this->fetch_all_rows();	 }
		return (isset($this->data[$offset]));
	}
	public function offsetGet( $offset ) {
		if (!$this->offsetExists($offset)){
			return FALSE;
		}
		return $this->data[$offset];
	}
	//It is impossible to set or modify database results
	public function offsetSet( $offset,  $value ){
		return;
	}
	public function offsetUnset( $offset ) {
		return;
	}
	public function rewind() {
	    $this->position = 0;
	}
	public function current() {
		if (!isset($this->data)){  $this->fetch_all_rows();	 }
	    return $this->data[$this->position];
	}
	public function key() {
	    return $this->position;
	}
	public function next() {
	    ++$this->position;
	}
	public function valid() {
	    return $this->offsetExists($this->position);
	}
}

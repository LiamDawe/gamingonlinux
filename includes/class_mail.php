<?php

/**
* Send a mail
*/
class mail
{

	public $plain_message = '';
	public $html_message = '';
	public $subject = '';
	public $to;
	public $headers;
	public $boundary;
	public $alreadySend = false;

	//Follow the same format as the default mail function, Because I'm lazy and not in the moot to replace all "367 matches across 222 files" in one go
	function __construct($to, $subject, $html_message, $plain_message, $headers_additional="")
	{
		$this->html_message = "<html><head>
		<title>New reply to an article you follow on GamingOnLinux.com</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
		</head>
		<body>
		<img src=\"" . core::config('website_url') . core::config('path') . "/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
		<br />";

		$this->html_message .= $html_message;

		$this->html_message .= "</body></html>";

		$this->plain_message = $plain_message;
		$this->to = $to;
		$this->subject = $subject;

		$this->boundary = uniqid('np');

		// To send HTML mail, the Content-type header must be set
		$this->headers[] = 'MIME-Version: 1.0';
		$this->headers[] = "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $this->boundary;
		$this->headers[] = "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>";

		if (is_string($headers_additional) && !empty($headers_additional))
		{
			$h = [];
			$a = explode("\r\n", $headers_additional);
			foreach ($a as $k => $v)
			{
				$b = explode(": ", $v);
				$h[$b[0]] = $b[1];
			}
			$this->headers = array_merge($this->headers, $h);
		}
		// add in the additional requested headers, for things like unique reply address or whatevs
		else if (is_array($headers_additional))
		{
			$this->headers = array_merge($this->headers, $headers_additional);
		}
	}

	function __destruct()
	{
		if (!$this->alreadySend)
		{
			$this->send();
		}
	}

	function __set($key, $val)
	{
		if (isset($this->$key))
		{
			$this->$key = $val;
		}
		else
		{
			$this->headers[$key] = $val;
		}
	}

	function __get($key)
	{
		if (isset($this->$key))
		{
			return $this->$key;
		}
		else if (isset($this->headers[$key]))
		{
			return $this->headers[$key];
		}
	}

	function __isset($key)
	{
		$p = ['to', 'headers', 'html_message', 'plain_message', 'alreadySend', 'boundary'];
		if (in_array($key, $p))
		{
			return isset($this->$key);
		}
		else
		{
			return isset($this->headers[$key]);
		}
		return false;
	}

	/**
	* Send the email
	* @input $force boolean Send it, eventhought it was already send
	**/
	public function send($force=false)
	{
		if (!$this->alreadySend || $force)
		{
			$message  = "\r\n\r\n--" . $this->boundary.PHP_EOL;
			$message .= "Content-Type: text/plain;charset=utf-8".PHP_EOL;
			$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
			$message .= PHP_EOL.$this->plain_message;

			$message .= "\r\n\r\n--" . $this->boundary.PHP_EOL;
			$message .= "Content-Type: text/html;charset=utf-8".PHP_EOL;
			$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
			$message .= PHP_EOL.$this->html_message;

			$message .= "\r\n\r\n--" . $this->boundary . "--";

			mail($this->to, $this->subject, $message, implode("\r\n", $this->headers) );
			$this->alreadySend = true;
		}
	}
}

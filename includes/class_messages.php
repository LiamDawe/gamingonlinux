<?php
class message_map
{
	public $messages = [];

	function __construct($file='main')
	{
		$main_file = 'includes/messages/' . $file . '.php';
		if (!file_exists($main_file))
		{
			throw new InvalidArgumentException("Missing main message file, cannot continue!");
		}
		else
		{
			$this->messages = include $main_file;
		}
	}

	public function get_message($file = NULL, $key, $extras = NULL)
	{
		if ($file != NULL)
		{
			$module_file = 'includes/messages/' . $file . '_messages.php';
			if (file_exists($module_file))
			{
				$module_messages = include $module_file;
				$this->messages = $this->messages + $module_messages;
			}
		}
    
		if (isset($this->messages[$key]))
		{
			$extras_output = '';
			if ((isset($this->messages[$key]['additions']) && $this->messages[$key]['additions'] == 1) && $extras != NULL)
			{
				$extras_output = htmlspecialchars($extras);
			}
		
			$error = 0;
			if (isset($this->messages[$key]['error']) && is_numeric($this->messages[$key]['error']))
			{
				$error = $this->messages[$key]['error'];
			}
	
			return ["message" => sprintf($this->messages[$key]['text'], $extras_output), "error" => $error];
		}
		else
		{
			return 'We tried to give a message with the key "'.$key.'" but we couldn\'t find that message.';
		}
	}
}

?>

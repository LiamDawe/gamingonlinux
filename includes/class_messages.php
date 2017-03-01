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

	public function display_message($file = NULL, $key, $extras = NULL)
	{
		global $templating;
		
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
				$extras_output = $extras;
			}
		
			$error = 0;
			if (isset($this->messages[$key]['error']) && is_numeric($this->messages[$key]['error']))
			{
				$error = $this->messages[$key]['error'];
			}
	
			$stored_message = sprintf($this->messages[$key]['text'], $extras_output);
		}
		else
		{
			$error = 1;
			$stored_message = 'We tried to give a message with the key "'.$key.'" but we couldn\'t find that message.';
		}

		$templating->merge('messages');

		if ($error == 0)
		{
			$templating->block('message');
		}

		else if ($error == 1)
		{
			$templating->block('errormessage');
		}

		$templating->set('message', $stored_message);
		
		unset($_SESSION['message']);
		unset($_SESSION['message_extra']);
	}
}

?>

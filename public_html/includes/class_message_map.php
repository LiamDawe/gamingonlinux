<?php
class message_map
{
	public static $error = 0;
	public $messages = [];

	function __construct($file='main')
	{
		$main_file = APP_ROOT . '/includes/messages/' . $file . '.php';
		if (!file_exists($main_file))
		{
			throw new InvalidArgumentException("Missing main message file, cannot continue!");
		}
		else
		{
			$this->messages = include $main_file;
		}
	}

	public function display_message($file = NULL, $key, $extras = NULL, $plain_message = NULL)
	{
		global $templating;
		
		if ($file != NULL)
		{
			$module_file = APP_ROOT . '/includes/messages/' . $file . '_messages.php';
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
	
			$stored_message = vsprintf($this->messages[$key]['text'], $extras_output);
		}
		else
		{
			$error = 1;
			$stored_message = 'We tried to give a message with the key "'.$key.'" but we couldn\'t find that message.';
		}

		unset($_SESSION['message']);
		unset($_SESSION['message_extra']);
		unset($_SESSION['error']);

		if (!$plain_message)
		{
			$templating->load('messages');
			
			$templating->block('message');

			if ($error == 0)
			{
				$templating->set('type', '');
			}

			else if ($error == 1)
			{
				$templating->set('type', 'error');
			}

			else if ($error == 2)
			{
				$templating->set('type', 'warning');
			}

			$templating->set('message', $stored_message);
			
			self::$error = $error;
		}
		else if ($plain_message == 'return_parsed')
		{
			$templating->load('messages');
			
			$message_block_html = $templating->block_store('message', 'messages');

			if ($error == 0)
			{
				$message_block_html = $templating->store_replace($message_block_html, array('type' => ''));
			}

			else if ($error == 1)
			{
				$message_block_html = $templating->store_replace($message_block_html, array('type' => 'error'));
			}

			else if ($error == 2)
			{
				$message_block_html = $templating->store_replace($message_block_html, array('type' => 'warning'));
			}

			$message_block_html = $templating->store_replace($message_block_html, array('message' => $stored_message));
			
			self::$error = $error;

			return $message_block_html;
		}
		else if ($plain_message == 'return_plain')
		{
			return $stored_message;
		}
	}
}
?>
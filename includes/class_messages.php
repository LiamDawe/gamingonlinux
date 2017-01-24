<?php
class message_map
{
  public $messages = [];

  function __construct($file='main')
  {
    $filename = 'includes/messages/' . $file . '.php';
    if (!file_exists($filename))
    {
      throw new InvalidArgumentException("Missing message file, cannot continue!");
    }
    else
    {
      $this->messages = include $filename;
    }
  }

  public function get_message($key, $extras = NULL)
  {
    if (isset($this->messages[$key]))
    {
      $extras_output = '';
      if ((isset($this->messages[$key]['additions']) && $this->messages[$key]['additions'] == 1) && $extras != NULL)
      {
        $extras_output = $extras;
      }
      return sprintf($this->messages[$key]['text'], $extras_output);
    }
    else
    {
      return 'We tried to give a message with the key "'.$key.'" but we couldn\'t find that message.';
    }
  }
}

?>

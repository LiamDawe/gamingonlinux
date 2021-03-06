<?php
class template
{
	// the required core class
	private $core;
	
	// the current template folder, simple enough
	public $template;

	public $cache_folder;

	/*
	The files we are working with.
	Set as an array as some blocks in different files may have the same name and the parser would always use the first one found (not always the right one).
	Also then means we can use a block from a different template anywhere without conflicts.
	*/
	protected $files = array();

	// the last template file included for use, used in the blocks 2nd parameter as 99% of the time the block you want is from the last templated used, prevents using a block from the template before the current one and other stupid errors, basically it helps us use the correct blocks etc
	protected $last_file;

	// a counter for the blocks, so they each have a unique block number
	protected $block = 0;

	// an array of all the blocks merged together to later put together to finalise the template
	protected $merged = array();

	// what tags to replace
	protected $values = array();

	// the final template to show to the viewer
	protected $final_output;

	public function __construct($core, $template_folder = NULL)
	{
		$this->core = $core;
		
		if ($template_folder == NULL)
		{
			$template_folder = 'default';
		}

		if (!is_dir($this->core->config('path') . "templates/{$template_folder}"))
		{
			die("Error loading template folder ($template_folder). " . $this->core->config('path') . "templates/" . $template_folder);
		}

		$this->template = $this->core->config('path') . "templates/{$template_folder}";
		$this->cache_folder = $this->core->config('path') . 'cache/' . $template_folder;
	}

	public function load($file)
	{
		$full_path = $this->template . '/' . $file . '.html';
		if (!file_exists($full_path))
		{
			$this->core->message("Error merging template file, cannot find $full_path.", 1);
		}

		$this->files[$file] = file_get_contents("{$this->template}/{$file}.html");
		$this->last_file = $file;
	}

	/*
		set the current block to work with and later display
		$block = name
		$file = template file to use
	*/
	public function block($block, $file = NULL)
	{
		if ($file == NULL)
		{
			$file = $this->last_file;
		}
		else if (!isset($this->files[$file]))
		{
			$this->load($file);
		}

		// assign this block a number
		// block number is used rather than the block name as we could be looping through the same block multiple times (and don't want each block to have the same content)
		$this->block++;

		$pattern = '#\[block:\s*(' . $block . ')\](.*)\[/block:\s*(' . $block . ')\]#is';

		// find the content of the wanted block from the selected template file
		if(preg_match($pattern, $this->files[$file], $matches))
		{
			// put the blocks info into the merged array with the block number
			$this->merged[$this->block] = $matches[2];
		}

		else
		{
			$this->core->message("Error cannot find block named ($block).", 1);
		}
	}

	// for just grabbing the html from the block, if you want to do something with it manually
	public function block_store($block, $file = NULL)
	{
		if ($file == NULL)
		{
			$file = $this->last_file;
		}

		$pattern = '#\[block:\s*(' . $block . ')\](.*)\[/block:\s*(' . $block . ')\]#is';

		// find the content of the wanted block from the selected template file
		if(preg_match($pattern, $this->files[$file], $matches))
		{
			// put the blocks info into the merged array with the block number
			return $matches[2];
		}

		else
		{
			$this->core->message("Error cannot find block named ($block).", 1);
		}
	}

	// replacing tags inside a previously stored block_store
	public function store_replace($text, $replace)
	{
		foreach ($replace as $name => $replace)
		{
			$find = "{:$name}";
			$text = str_replace($find, $replace, $text);
		}

		return $text;
	}

	// set a value for a tag to be replaced in the current block
	public function set($key, $value)
	{
		$this->values[$this->block][$key] = $value;
	}
	
	// set multiple values to be replaced
	public function set_many($replaces)
	{
		foreach ($replaces as $key => $value)
		{
			$this->values[$this->block][$key] = $value;
		}
	}
	
	// set a value for a tag to be replaced in a previous block
	public function set_previous($key, $value, $block)
	{
		$this->values[$block][$key] = $value;
	}

	public function get($key, $block=NULL)
	{
		if ($block === NULL) { $block = $this->block; }
		return $this->values[$block][$key];
	}

	// this will replace the tags in the current block
	public function do_tags()
	{
		foreach ($this->values as $id => $block)
		{
			foreach ($block as $name => $replace)
			{
				$find = "{:$name}";
				$this->merged[$id] = str_replace($find, $replace, $this->merged[$id]);
			}
		}

	}

	public function get_cache($filename)
	{
		$this->merged[$filename] = file_get_contents($this->cache_folder . "/{$filename}.html");
	}

	// everything else is done, so show us the page
	public function output()
	{
		$this->do_tags();

		$this->final_output = '';

		foreach ($this->merged as $block)
		{
			$this->final_output .= $block;
		}

		$this->last_file = '';
		$this->values = array();
		$this->files = array();
		$this->merged = array();

		// the final template all put together
		return $this->final_output;
	}
}
?>

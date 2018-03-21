<?php
class file_cache
{
	public $file;
	public $core;
	public $folder = 'cache/';

	function __construct($core)
	{
		$this->core = $core;
		$this->folder = $this->folder . $this->core->config('template') . '/';
	}

	// check a cache for this file exists
	function check_cache($cachefile, $cachetime)
	{
		$cachefile = $this->folder . $cachefile . '.html';
		if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) 
		{
			return true;
		}
		return false;
	}

	// start making the cache
	function init()
	{
		ob_start();
	}

	// update the cache for this file
	function write($filename)
	{
		// open, but do not truncate leaving data in place for others
		$fp = fopen($this->folder . $filename . '.html', 'c'); 
		if ($fp === false) 
		{
            error_log("Couldn't open " . $filename . ' cache for updating!');
        }
		// exclusive lock, but don't block it so others can still read
		flock($fp, LOCK_EX | LOCK_NB); 
        // truncate it now we've got our exclusive lock
		ftruncate($fp, 0);
		fwrite($fp, ob_get_contents());
		flock($fp, LOCK_UN); // release lock
		fclose($fp);
		// finally send browser output
		ob_end_flush();
	}
}
?>
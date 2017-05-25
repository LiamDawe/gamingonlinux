<?php

class comment extends base
{
	protected static $table = "articles_comments";
	protected static $primary = "comment_id";

	public function article()
	{
		return article::where("article_id", $this->article_id);
	}

	public function __toString(){
		return bbcode($this->comment_text);
	}
}
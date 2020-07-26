<?php
class editor
{	
	protected $core;
	protected $templating;
	protected $bbcode;

	function __construct($core, $templating, $bbcode)
	{	
		$this->core = $core;
		$this->templating = $templating;
		$this->bbcode = $bbcode;
	}

	/* For generating a bbcode editor form, options are:
	name - name of the textarea
	content
	article_editor
	disabled
	anchor_name
	ays_ignore
	editor_id
	*/
	// include this anywhere to show the bbcode editor
	function editor($custom_options)
	{		
		if (!is_array($custom_options))
		{
			die('BBCode editor not setup correctly!');
		}
		
		// sort some defaults
		$editor['article_editor'] = 0;
		$editor['disabled'] = 0;
		$editor['ays_ignore'] = 0;
		$editor['content'] = '';
		$editor['anchor_name'] = 'commentbox';
		
		foreach ($custom_options as $option => $value)
		{
			$editor[$option] = $value;
		}
		
		$this->templating->load('editor');
		if (isset($editor['type']) && $editor['type'] == 'simple')
		{
			$this->templating->block('simple_editor');
			$this->templating->set('buttons', $custom_options['buttons']);
		}
		else
		{
			$this->templating->block('editor');
		}
		
		$this->templating->set('this_template', $this->core->config('website_url') . 'templates/' . $this->core->config('template'));
		$this->templating->set('url', $this->core->config('website_url'));
		$this->templating->set('name', $editor['name']);
		$this->templating->set('content', $editor['content']);
		$this->templating->set('anchor_name', $editor['anchor_name']);

		$emoji_location = $this->core->config('website_url') . 'templates/' . $this->core->config('template') . '/images/emoticons/';
		$emoji_set = '';
		foreach ($this->bbcode->emoji['raw'] as $key => $name)
		{
			$emoji_set .= '<li class="bb-button" data-snippet="'.$key.'"><img width="20" src="'. $emoji_location . $name . '" alt="'.$key.'" title="'.$key.'" /></li>';
		}
		$this->templating->set('emoji_list', $emoji_set);
		
		$disabled = '';
		if ($editor['disabled'] == 1)
		{
			$disabled = 'disabled';
		}
		$this->templating->set('disabled', $disabled);

		$ays_check = '';
		if ($editor['ays_ignore'] == 1)
		{
			$ays_check = 'class="ays-ignore"';
		}
		$this->templating->set('ays_ignore', $ays_check);
		
		$this->templating->set('limit_youtube', $this->core->config('limit_youtube'));
		
		$this->templating->set('editor_id', $editor['editor_id']);
	}
	
	/* For generating the HTML article editor form, options are:
	name - name of the textarea
	content
	article_editor
	disabled
	anchor_name
	ays_ignore
	editor_id
	*/
	function article_editor($custom_options)
	{		
		if (!is_array($custom_options))
		{
			die('CKEditor editor not setup correctly!');
		}
		
		// sort some defaults
		$editor['disabled'] = 0;
		$editor['ays_ignore'] = 0;
		$editor['content'] = '';
		$editor['anchor_name'] = 'commentbox';
		
		foreach ($custom_options as $option => $value)
		{
			$editor[$option] = $value;
		}
		
		$this->templating->load('ckeditor');
		$this->templating->block('editor');
		$this->templating->set('this_template', $this->core->config('website_url') . 'templates/' . $this->core->config('template'));
		$this->templating->set('url', $this->core->config('website_url'));
		$this->templating->set('content', $editor['content']);
		$this->templating->set('anchor_name', $editor['anchor_name']);
		
		$disabled = '';
		if ($editor['disabled'] == 1)
		{
			$disabled = 'disabled';
		}
		$this->templating->set('disabled', $disabled);

		$ays_check = '';
		if ($editor['ays_ignore'] == 1)
		{
			$ays_check = 'class="ays-ignore"';
		}
		$this->templating->set('ays_ignore', $ays_check);
	}
}
?>
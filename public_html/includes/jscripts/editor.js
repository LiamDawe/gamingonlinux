/*
 * Forked from Octus Editor: https://github.com/julianrichen/octus-editor MIT license
 */
function gol_editor(editor_id) 
{
	var editor_bar = 'bar_' + editor_id;
	var this_editor = document.getElementById(editor_id);

	function start() 
	{
		// Register tags
		registerElements(editor_bar);
	}

    /*
	 * registerElements()
	 *
	 * Register all tags from each editor
	 */
    function registerElements(id) 
	{
        // Get all styles
        var tags    = document.querySelectorAll('#' + editor_bar + ' .styles ul li[data-tag]'),
            snippet = document.querySelectorAll('#' + editor_bar + ' .styles ul li[data-snippet]');
        // register all the tags
		for (var i = 0; i < tags.length; i++) 
		{
            // Log editor id
            tags[i].editor_id = id;
            // Add click event
            tags[i].addEventListener("click", registerTag, false);
        }

        // register all the snippets
		for (var x = 0; x < snippet.length; x++) 
		{
            // Log editor id
            snippet[x].editor_id = id;
            // Add click event
            snippet[x].addEventListener("click", registerSnippet, false);
        }
    }

    /*
	 * registerTag()
	 *
	 * Get tag from each editor
	 */
    function registerTag() 
	{
        // Get textarea
        var dataTag = this.dataset.tag;
			
        // Do we have a sub class?
        if(this.dataset.subtag) 
		{
            // Fire tag
            createTag(dataTag, this.dataset.subtag);
        } 
        else 
		{
            // Fire tag
            createTag(dataTag);
        }
    }

    /*
	 * registerSnippet()
	 *
	 * Get tag from each editor
	 */
    function registerSnippet() 
	{		
        // Fire snippet
        snippet(this.dataset.snippet);
    }
     
     function list(text)
	 {
		text = '[li]' + text.replace(/\r\n/g, "\r\n[/li]").replace(/[\r\n]/g, '[/li]\r\n[li]') + '[/li]';
		list_nospaces = text.replace(/\[li\] +/gm, '[li]');
		return list_nospaces;
	 }

    /*
	 * bbcode()
	 *
	 * Insert tag
	 */
    function createTag(tag, subtag) 
	{
        var selected,
            ins,
            sel,
            popUpData;
        // Add a sub tag?
        if (typeof subtag != 'undefined') 
		{
            subtag = '=' + subtag;
        } 
        else 
		{
            subtag = '';
        }

		this_editor.focus();

        if (typeof this_editor.selectionStart != 'undefined') 
		{
            selected = this_editor.value.slice(this_editor.selectionStart, this_editor.selectionEnd);
        } 
        else if (document.selection && document.selection.type != 'Control') // for IE compatibility
		{ 
			selected = document.selection.createRange().text;
		}
		
		// list bbcode
		if (tag == 'list')
		{
			list_data = list(selected);
			selected = list_data;
			tag = 'ul';
		}
		
		// if there's data from a pop-up like Youtube
		popUpData = popUp(tag, subtag, selected);
		if(popUpData === null || typeof popUpData == 'undefined') 
		{
            return;
        }
        tag      = popUpData[0];
        subtag   = popUpData[1];
        selected = popUpData[2];
		
        ins = '[' + tag + '' + subtag + ']' + selected + '[/' + tag +']';
        if (!document.execCommand("insertText", false, ins)) 
		{
            this_editor.value = this_editor.value.slice(0, this_editor.selectionStart) + ins + this_editor.value.slice(this_editor.selectionEnd);
		}
    }

    /*
	 * snippet()
	 *
	 * Insert snippet
	 */
    function snippet(tag) 
	{
        var selected,
            ins,
            sel;
			
		this_editor.focus();

        if (typeof this_editor.selectionStart != 'undefined') 
		{
            selected = this_editor.value.slice(this_editor.selectionStart, this_editor.selectionEnd);
        } 
        else if (document.selection && document.selection.type != 'Control') // for IE compatibility
		{ 
			selected = document.selection.createRange().text;
		}
		
		// if there's data from a pop-up like Youtube
		popUpData = popUp(tag, selected);
		if(popUpData === null || typeof popUpData == 'undefined') 
		{
            return;
        }
        tag      = popUpData[0];
        selected = popUpData[1];
        ins = tag + selected;
        if (!document.execCommand("insertText", false, ins)) 
		{
            this_editor.value = this_editor.value.slice(0, this_editor.selectionStart) + ins + this_editor.value.slice(this_editor.selectionEnd);
		}
    }

    /*
	 * getYouTubeID()
	 *
	 * Get YouTube ID
	 */
     function getYouTubeID(input)
     {
        var video_id;
		
		var this_button = document.getElementById('youtube-bbcode');
		var yt_limit = this_button.getAttribute('data-limit');
	
        if(input === "") 
		{
            input = window.prompt('Enter YouTube URL, limited to ' + yt_limit + ' per post');
        }
        if (input != null)
		{
			video_id = input.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i);
			if(video_id === null) 
			{
				return null;
			}
			else
			{
				return video_id[1];
			}
		}
		else
		{
			return null;
		}
        
    }

    /*
	 * popUp()
	 *
	 * Checks if a pop-up needs to be called
	 */
    function popUp(tag, subtag, selected) 
	{
        var data;

        if(tag == 'youtube') 
		{
            selected = getYouTubeID(selected);
            if(selected === null) 
			{
                return null;
            }
        } 
        else if(tag == 'url' && selected != "") 
		{
            subtag = window.prompt('Enter a valid URL');
            if(subtag === null || subtag === '') 
			{
                return null;
            } 
            else 
			{
                subtag = '=' + subtag;
            }
        } 
        else if(tag == 'url' && selected === "") 
		{
            subtag = window.prompt('Enter a valid URL');
            if(subtag === null || subtag === '') 
			{
                return null;
            } 
            else 
			{
                subtag = '=' + subtag;
            }
            selected = window.prompt('URL link text');
            if(selected === null || selected === '') 
			{
                selected = 'link';
            }
        }

        data = [tag, subtag, selected];

        return data;
    }

    /*
	 * Allow keyboard shortcut
	 *
	 * All people to use keyboard shortcut
	 */
    document.onkeydown = function(e) 
	{
        var field = document.getElementById(editor_id);
		if (field === document.activeElement)
		{
			var key = e.keyCode || e.which;
			if (e.ctrlKey) 
			{
				switch (key) 
				{
					//http://help.adobe.com/en_US/AS2LCR/Flash_10.0/00000520.html
					case 66: // Ctrl+B
						e.preventDefault();
						createTag('b');
						break;
					case 73: // Ctrl+I
						e.preventDefault();
						createTag('i');
						break;
					case 85: // Ctrl+U
						e.preventDefault();
						createTag('u');
						break;
					case 76: // CTRL+L
						e.preventDefault();
						createTag('url');
						break;
				}
			}
		}
    };

    start();
}

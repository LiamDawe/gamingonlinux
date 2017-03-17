/*
 * Forked from Octus Editor: https://github.com/julianrichen/octus-editor MIT license
 */
function OctusEditor(editorPath) 
{
    /*
     * Vars
     */
    var masterEditor;

    /*
	 * Do we need a default value?
	 */
    if(!editorPath) 
	{
        editorPath = '.octus-editor';
    }

	/*
	 * init()
	 *
	 * Start editors
	 */
	function init() 
	{
		var editors = document.querySelectorAll(editorPath),
            quotes  = document.querySelectorAll('[data-quote]');

        // First register every editor
		for (var i = 0; i < editors.length; i++) 
		{
			console.log('Editor id: ' + i);
            // Set master editor
            if(i === 0) 
			{
                masterEditor =  'octus-editor-' + i;
            }
            editors[i].setAttribute('id', 'octus-editor-' + i);
            // Register tags
            registerElements('octus-editor-' + i);
		}

        // Register all possible quotes.
		for (var x = 0; x < quotes.length; x++) 
		{
            quotes[x].addEventListener("click", registerQuote, false);
		}
	}

    /*
	 * registerElements()
	 *
	 * Register all tags from each editor
	 */
    function registerElements(id) 
	{
        // Get all styles
        var tags    = document.querySelectorAll('#' + id + ' .styles ul li[data-tag]'),
            snippet = document.querySelectorAll('#' + id + ' .styles ul li[data-snippet]');
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
        var field   = document.querySelector('#' + this.editor_id + ' .textarea textarea'),
            dataTag = this.dataset.tag;
			
        // Do we have a sub class?
        if(this.dataset.subtag) 
		{
            // Fire tag
            createTag(field, dataTag, this.dataset.subtag);
        } 
        else 
		{
            // Fire tag
            createTag(field, dataTag);
        }
    }

    /*
	 * registerSnippet()
	 *
	 * Get tag from each editor
	 */
    function registerSnippet() 
	{
        // Get textarea
        var field = document.querySelector('#' + this.editor_id + ' .textarea textarea');
        // Fire snippet
        snippet(field, this.dataset.snippet);
    }

    /*
	 * registerQuote()
	 *
	 * Register all quotes
	 */
    function registerQuote() 
	{
        var username = this.dataset.quote;
        var text = this.dataset.comment;
        quote(text, username);
    }

    /*
	 * bbcode()
	 *
	 * Insert tag
	 */
    function createTag(field, tag, subtag) 
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

		field.focus();

        if (typeof field.selectionStart != 'undefined') 
		{
            selected = field.value.slice(field.selectionStart, field.selectionEnd);
        } 
        else if (document.selection && document.selection.type != 'Control') // for IE compatibility
		{ 
			selected = document.selection.createRange().text;
		}
		
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
            field.value = field.value.slice(0, field.selectionStart) + ins + field.value.slice(field.selectionEnd);
		}
    }

    /*
	 * snippet()
	 *
	 * Insert snippet
	 */
    function snippet(field, tag) 
	{
        var selected,
            ins,
            sel;
			
		field.focus();

        if (typeof field.selectionStart != 'undefined') 
		{
            selected = field.value.slice(field.selectionStart, field.selectionEnd);
        } 
        else if (document.selection && document.selection.type != 'Control') // for IE compatibility
		{ 
			selected = document.selection.createRange().text;
		}
		
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
            field.value = field.value.slice(0, field.selectionStart) + ins + field.value.slice(field.selectionEnd);
		}
    }

    /*
	 * getYouTubeID()
	 *
	 * Get YouTube ID
	 */
     function getYouTubeID(input)
     {
        var id;
		
		var this_button = document.getElementById('youtube-bbcode');
		var yt_limit = this_button.getAttribute('data-limit');
	
        if(input === "") 
		{
            input = window.prompt('Enter YouTube URL, limited to ' + yt_limit + ' per post');
        }
        id = input.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i);
        if(id === null) 
		{
            return null;
        }
        return id[1];
    }

    /*
	 * quote()
	 *
	 * Insert a quote, decodeEntities sorts out the html for the textarea
	 */
     function decodeEntities(encodedString)
     {
         var textArea = document.createElement('textarea');
         textArea.innerHTML = encodedString;
         return textArea.value;
     }

     function quote(text, name)
     {
         var field   = document.querySelector('#' + masterEditor + ' .textarea textarea'),

         text = decodeEntities(text);

         content = "[quote=" + name + "]" + text;
         content += "[/quote]";

         field.value += content;
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
        var field = document.getElementById("editor_content");
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
						createTag(field, 'b');
						break;
					case 73: // Ctrl+I
						e.preventDefault();
						createTag(field, 'i');
						break;
					case 85: // Ctrl+U
						e.preventDefault();
						createTag(field, 'u');
						break;
					case 76: // CTRL+L
						e.preventDefault();
						createTag(field, 'url');
						break;
				}
			}
		}
    };

    // Init
    init();
}

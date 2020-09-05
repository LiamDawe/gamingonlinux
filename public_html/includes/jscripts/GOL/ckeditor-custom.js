// insert tagline image html into editor
$(document).on('click', ".insert_tagline_image", function(e) 
{
    e.preventDefault();
    var tagline_image = $('.tagline-image').attr('href');
    CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><img src="'+tagline_image+'" /></p>');
});	

// insert normal image uploads into article textarea
// main image
$(document).on('click', ".uploads .add_button", function(e) 
{
    var text = $(this).data('url');
    var type = $(this).data('type');
    if (type == 'video')
    {
        CKEDITOR.instances.ckeditor_gol.insertHtml('<div class="ckeditor-html5-video" style="text-align: center;"><video controls="controls" src="'+text+'">&nbsp;</video></div>');
    }
    else if (type == 'audio')
    {
        CKEDITOR.instances.ckeditor_gol.insertHtml('<div class="ckeditor-html5-audio" style="text-align: center;"><audio controls="controls" src="'+text+'">&nbsp;</audio></div>');
    }
    else
    {
        CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><a href="'+text+'" data-fancybox="images"><img src="'+text+'" /></a></p>');
    }
});

// thumbnail insertion
$(document).on('click', ".uploads .add_thumbnail_button", function(e) 
{
    var thumbnail = $(this).data('url');
    var big_image = $(this).data('main-url');
    CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><a href="'+big_image+'" data-fancybox="images"><img src="'+thumbnail+'" /></a></p>');
});

// static image for a gif
$(document).on('click', ".uploads .add_static_button", function(e) 
{
    var actual_gif = $(this).data('url-gif');
    var static_image = $(this).data('url-static');
    CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><a href="'+actual_gif+'" class="img_anim" target="_blank"><img src="'+static_image+'" /></a></p>');
});

// approving submitted article as yourself (if you re-wrote large portions)
$(document).on('click', "#self-submit", function(e) 
{
    var username = $("#submitter-username").text();   
    var targetEditor = CKEDITOR.instances.ckeditor_gol;
    var range = targetEditor.createRange();
    range.moveToElementEditEnd(range.root);
    targetEditor.insertHtml('<p>&nbsp;</p><p style="text-align:right"><em>With thanks to the original submission from ' + username + '!</em></p>', 'html', range);
});

CKEDITOR.on('instanceCreated', function(e) 
{
	e.editor.on('change', function(event) 
	{
		var textarea = event.editor.element.$;
		$(textarea).val(event.editor.getData().trim());
		$(textarea.form).trigger('checkform.areYouSure');
	});
})
var ckeditor_skin = 'moono-lisa';
if(localStorage.getItem("theme")){
		if(localStorage.getItem("theme") == "dark")
		{
			ckeditor_skin = 'moono-dark';
        }
} 

editor = CKEDITOR.replace( 'ckeditor_gol', {
customConfig: '/includes/jscripts/ckeditor_config.js', skin: ckeditor_skin
});
editor.addCommand("spoiler", {
	exec: function(edt) {
		edt.insertHtml('<div class="collapse_container"><div class="collapse_header"><span>Spoiler, click me</span></div><div class="collapse_content"><div class="body group">hidden text here</div></div></div>');
	}
});
editor.ui.addButton('spoilerbutton', {
label: "Spoiler",
command: 'spoiler',
toolbar: 'insert',
icon: '/templates/default/images/spoiler.png'
});
CKEDITOR.on('instanceReady', function(evt)
{
	evt.editor.filter.addTransformations([
	[{
		element: 'a',
		left: function( el ) {
			return !el.attributes.target;
		},
		right: function( el, tools ) {
			el.attributes.target = '_blank';
		}
	}]
	]);
});
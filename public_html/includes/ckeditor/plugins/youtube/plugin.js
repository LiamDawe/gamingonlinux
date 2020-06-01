/*
* Youtube Embed Plugin
*
* @author Jonnas Fonini <jonnasfonini@gmail.com>
* @version 2.1.10
*/
var youtube_image_return;

(function () {
	CKEDITOR.plugins.add('youtube', {
		lang: ['en'],
		init: function (editor) {
			editor.addCommand('youtube', new CKEDITOR.dialogCommand('youtube', {
				allowedContent: 'div[*](*){*}; iframe{*}[!width,!height,!src,!frameborder,!allowfullscreen]; object param[*]; a[*]; img[*]'
			}));

			editor.ui.addButton('Youtube', {
				label : editor.lang.youtube.button,
				toolbar : 'insert',
				command : 'youtube',
				icon : this.path + 'images/icon.png'
			});

			CKEDITOR.dialog.add('youtube', function (instance) {
				var video,
					disabled = editor.config.youtube_disabled_fields || [];

				return {
					title : editor.lang.youtube.title,
					minWidth : 510,
					minHeight : 200,
					onShow: function () {
						for (var i = 0; i < disabled.length; i++) {
							this.getContentElement('youtubePlugin', disabled[i]).disable();
						}
					},
					contents :
						[{
							id : 'youtubePlugin',
							expand : true,
							elements :
								[
								{
									type : 'hbox',
									widths : [ '100%', '15%', '15%' ],
									children :
									[
										{
											id : 'txtUrl',
											type : 'text',
											label : editor.lang.youtube.txtUrl,
											onChange : function (api) {
												handleLinkChange(this, api);
											},
											onKeyUp : function (api) {
												handleLinkChange(this, api);
											},
											validate : function () {
												if (this.isEnabled()) {
													if (!this.getValue()) {
														alert(editor.lang.youtube.noCode);
														return false;
													}
													else{
														video = ytVidId(this.getValue());

														if (this.getValue().length === 0 ||  video === false)
														{
															alert(editor.lang.youtube.invalidUrl);
															return false;
														}
													}
												}
											}
										},
										{
											type : 'text',
											id : 'txtWidth',
											width : '60px',
											label : editor.lang.youtube.txtWidth,
											'default' : editor.config.youtube_width != null ? editor.config.youtube_width : '640',
											validate : function () {
												if (this.getValue()) {
													var width = parseInt (this.getValue()) || 0;

													if (width === 0) {
														alert(editor.lang.youtube.invalidWidth);
														return false;
													}
												}
												else {
													alert(editor.lang.youtube.noWidth);
													return false;
												}
											}
										},
										{
											type : 'text',
											id : 'txtHeight',
											width : '60px',
											label : editor.lang.youtube.txtHeight,
											'default' : editor.config.youtube_height != null ? editor.config.youtube_height : '360',
											validate : function () {
												if (this.getValue()) {
													var height = parseInt(this.getValue()) || 0;

													if (height === 0) {
														alert(editor.lang.youtube.invalidHeight);
														return false;
													}
												}
												else {
													alert(editor.lang.youtube.noHeight);
													return false;
												}
											}
										}
									]
								},
								{
									type : 'hbox',
									widths : [ '55%', '45%'],
									children :
									[
										{
											id : 'txtPreviewImage',
											type : 'text',
											label : editor.lang.youtube.txtPreviewImage,
											validate : function () {
												if (this.isEnabled()) {
													if (!this.getValue()) {
														alert("You must provide a YouTube preview image, otherwise it's a nasty default YouTube logo!");
														return false;
													}
													else
													{
														if (this.getValue().trim().length === 0)
														{
															alert("You must provide a YouTube preview image, otherwise it's a nasty default YouTube logo!");
															return false;
														}
													}
												}
											}
										},
										{
											id : 'txtPreviewImageLoad',
											type : 'button',
											label : editor.lang.youtube.txtPreviewImageLoad,
											onClick: function() 
											{
												grab_youtube_image(this.getDialog());
											}
										},
									]
								},
								{
									type : 'hbox',
									widths : [ '55%', '45%' ],
									children :
										[
											{
												id : 'chkResponsive',
												type : 'checkbox',
												label : editor.lang.youtube.txtResponsive,
												'default' : editor.config.youtube_responsive != null ? editor.config.youtube_responsive : false
											}
										]
								},
								{
									type : 'hbox',
									widths : [ '55%', '45%' ],
									children :
									[
										{
											id : 'chkRelated',
											type : 'checkbox',
											'default' : editor.config.youtube_related != null ? editor.config.youtube_related : true,
											label : editor.lang.youtube.chkRelated
										}
									]
								},
								{
									type : 'hbox',
									widths : [ '55%', '45%' ],
									children :
									[
										{
											id : 'chkAutoplay',
											type : 'checkbox',
											'default' : editor.config.youtube_autoplay != null ? editor.config.youtube_autoplay : false,
											label : editor.lang.youtube.chkAutoplay
										}
									]
								},
								{
									type : 'hbox',
									widths : [ '55%', '45%'],
									children :
									[
										{
											id : 'txtStartAt',
											type : 'text',
											label : editor.lang.youtube.txtStartAt,
											validate : function () {
												if (this.getValue()) {
													var str = this.getValue();

													if (!/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/i.test(str)) {
														alert(editor.lang.youtube.invalidTime);
														return false;
													}
												}
											}
										}
									]
								}
							]
						}
					],
					onOk: function()
					{
						var content = '';
						var responsiveStyle = '';

						
						var url = 'https://www.youtube-nocookie.com/', params = [], startSecs;
						var width = this.getValueOf('youtubePlugin', 'txtWidth');
						var height = this.getValueOf('youtubePlugin', 'txtHeight');

						url += 'embed/' + video;

						if (this.getContentElement('youtubePlugin', 'chkRelated').getValue() === false) 
						{
							params.push('rel=0');
						}

						if (this.getContentElement('youtubePlugin', 'chkAutoplay').getValue() === true) 
						{
							params.push('autoplay=1');
						}

						previewImageOutput = '';
						if (this.getContentElement('youtubePlugin', 'txtPreviewImage').getValue().length > 0) 
						{
							previewImageOutput = 'data-video-urlpreview="' + this.getValueOf('youtubePlugin', 'txtPreviewImage') + '"';
						}
						else
						{
							alert("You didn't provide a YouTube image preview.");
						}

						startSecs = this.getValueOf('youtubePlugin', 'txtStartAt');

						if (startSecs) 
						{
							var seconds = hmsToSeconds(startSecs);

							params.push('start=' + seconds);
						}

						if (params.length > 0) 
						{
							url = url + '?' + params.join('&');
						}

						if (this.getContentElement('youtubePlugin', 'chkResponsive').getValue() === true) 
						{
							content += '<div class="youtube-embed-wrapper" data-video-url="'+url+'" '+previewImageOutput+' style="position:relative;padding-bottom:56.25%;padding-top:30px;height:0;overflow:hidden">';
							responsiveStyle = 'style="position:absolute;top:0;left:0;width:100%;height:100%"';
						}

						content += '<iframe width="' + width + '" height="' + height + '" src="' + url + '" ' + responsiveStyle;
						content += 'frameborder="0" allowfullscreen></iframe>';

						if (this.getContentElement('youtubePlugin', 'chkResponsive').getValue() === true) 
						{
							content += '</div>';
						}
						
						var element = CKEDITOR.dom.element.createFromHtml(content);
						var instance = this.getParentEditor();
						instance.insertElement(element);
					}
				};
			});
		}
	});
})();

function handleLinkChange(el, api) {
	var video = ytVidId(el.getValue());
	var time = ytVidTime(el.getValue());

	if (video && time) {
		var seconds = timeParamToSeconds(time);
		var hms = secondsToHms(seconds);
		el.getDialog().getContentElement('youtubePlugin', 'txtStartAt').setValue(hms);
	}
}

function handleEmbedChange(el, api) {
	if (el.getValue().length > 0) {
		el.getDialog().getContentElement('youtubePlugin', 'txtUrl').disable();
	}
	else {
		el.getDialog().getContentElement('youtubePlugin', 'txtUrl').enable();
	}
}

function grab_youtube_image(el)
{
	if (el.getContentElement('youtubePlugin', 'txtUrl').getValue().length === 0) 
	{
		alert("You didn't provide a YouTube URL!!");
	}
	else
	{
		var videoID = ytVidId(el.getContentElement('youtubePlugin', 'txtUrl').getValue());
		var inputbox = el;
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() 
		{
			if (this.readyState == 4 && this.status == 200) 
			{
				var return_data = JSON.parse(this.responseText);

				// insert the new upload from the YouTube thumbnail into the uploaded media section
				var preview_image = '<div class="box"><div class="body group"><div id="'+return_data.db_id+'">YouTube Thumbnail Image: <br /><img style="max-width: 450px;" src="'+return_data.file_url+'" class="imgList"><br />URL: <input id="img' +return_data.db_id+ '" type="text" value="' +return_data.file_url+ '" /> <button class="btn" data-clipboard-target="#img' +return_data.db_id+ '">Copy</button><button data-url="'+return_data.file_url+'" data-type="image" class="add_button">Insert</button><button id="' +return_data.db_id+ '" class="trash">Delete Media</button></div></div></div>';
				var theDiv = document.getElementById("uploaded_media");
				theDiv.insertAdjacentHTML('beforeend', preview_image);

				// insert the hidden form field, to tell our script we have a new file to work with when saving
				var main_form = document.getElementById("article_editor");
				var new_hidden_field = '<input class="uploads-'+return_data.db_id+'" type="hidden" name="uploads[]" value="'+return_data.db_id+'"></input>';
				main_form.insertAdjacentHTML('beforeend',new_hidden_field);

				inputbox.getContentElement('youtubePlugin', 'txtPreviewImage').setValue(return_data.file_url);
			}
		};
		var url = new URL(window.location.href);
		var aid = url.searchParams.get("aid");

		var 	article_id = '';
		if (typeof aid !== 'undefined') 
		{
			article_id = aid;
		}

		xhttp.open("GET", "/includes/youtube_image_proxy.php?id="+videoID+'&aid='+article_id, true);
		xhttp.setRequestHeader ("Cache-Control", "no-store, no-cache, must-revalidate");
		xhttp.setRequestHeader ("X-Requested-With", "XMLHttpRequest");
		xhttp.send();
	}
}

/**
 * JavaScript function to match (and return) the video Id
 * of any valid Youtube Url, given as input string.
 * @author: Stephan Schmitz <eyecatchup@gmail.com>
 * @url: http://stackoverflow.com/a/10315969/624466
 */
function ytVidId(url) {
	var p = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;
	return (url.match(p)) ? RegExp.$1 : false;
}

/**
 * Matches and returns time param in YouTube Urls.
 */
function ytVidTime(url) {
	var p = /t=([0-9hms]+)/;
	return (url.match(p)) ? RegExp.$1 : false;
}

/**
 * Converts time in hms format to seconds only
 */
function hmsToSeconds(time) {
	var arr = time.split(':'), s = 0, m = 1;

	while (arr.length > 0) {
		s += m * parseInt(arr.pop(), 10);
		m *= 60;
	}

	return s;
}

/**
 * Converts seconds to hms format
 */
function secondsToHms(seconds) {
	var h = Math.floor(seconds / 3600);
	var m = Math.floor((seconds / 60) % 60);
	var s = seconds % 60;

	var pad = function (n) {
		n = String(n);
		return n.length >= 2 ? n : "0" + n;
	};

	if (h > 0) {
		return pad(h) + ':' + pad(m) + ':' + pad(s);
	}
	else {
		return pad(m) + ':' + pad(s);
	}
}

/**
 * Converts time in youtube t-param format to seconds
 */
function timeParamToSeconds(param) {
	var componentValue = function (si) {
		var regex = new RegExp('(\\d+)' + si);
		return param.match(regex) ? parseInt(RegExp.$1, 10) : 0;
	};

	return componentValue('h') * 3600
		+ componentValue('m') * 60
		+ componentValue('s');
}

/**
 * Converts seconds into youtube t-param value, e.g. 1h4m30s
 */
function secondsToTimeParam(seconds) {
	var h = Math.floor(seconds / 3600);
	var m = Math.floor((seconds / 60) % 60);
	var s = seconds % 60;
	var param = '';

	if (h > 0) {
		param += h + 'h';
	}

	if (m > 0) {
		param += m + 'm';
	}

	if (s > 0) {
		param += s + 's';
	}

	return param;
}

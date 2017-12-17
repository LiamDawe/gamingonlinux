// for the quote function, so we don't end up with garbled html and get the proper output instead
function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}
// update ckeditor so it can be captured and parsed by the article preview
function CKupdate() 
{
	for (instance in CKEDITOR.instances)
		CKEDITOR.instances[instance].updateElement();
	return true;
}
jQuery.fn.outerHTML = function() {
	return (this[0]) ? this[0].outerHTML : '';  
 };

// scroll to an element if it's not in view, all other ways I could find completely sucked
jQuery.fn.scrollMinimal = function(smooth)
{
	var cTop = this.offset().top;
	var cHeight = this.outerHeight(true);
	var windowTop = $(window).scrollTop();
	var visibleHeight = $(window).height();

	if (cTop < windowTop)
	{
		if (smooth) 
		{
			$('body').animate({'scrollTop': cTop}, 'slow', 'swing');
		}
		else 
		{
			$(window).scrollTop(cTop);
		}
	} 
	else if (cTop + cHeight > windowTop + visibleHeight) 
	{
		if (smooth)
		{
			$('body').animate({'scrollTop': cTop - visibleHeight + cHeight}, 'slow', 'swing');
			
		}
		else 
		{
			$(window).scrollTop(cTop - visibleHeight + cHeight);
		}
	}
};

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

jQuery.fn.highlight = function () {
    $(this).each(function () {
        var el = $(this);
        $("<div/>")
        .width(el.width())
        .height(el.height())
        .css({
            "position": "absolute",
            "left": el.offset().left,
            "top": el.offset().top,
            "background-color": "#ffff99",
            "opacity": ".7",
            "z-index": "9999999"
        }).appendTo('body').fadeOut(1000).queue(function () { $(this).remove(); });
    });
}

function disableFunction()
{
	document.getElementById("send").disabled = 'true';
}

function validateEmail(email) 
{
	var charReg = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
	return charReg.test(email);
}

function resetFormElement(e)
{
	e.wrap('<form>').closest('form').get(0).reset();
	e.unwrap();
}

function countchars()
{
	jQuery("#count").text('Tagline Characters: ' + jQuery('#tagline').val().length);
}

var slug_enabled=false;
var current_url = $(location).attr('pathname') + $(location).attr('search');
if (current_url == '/admin.php?module=add_article')
{
	var slug_enabled=true;
}

function slug(update)
{
	update = update || 0;
	if (slug_enabled==true || update == 1)
	{
		var title = document.getElementById("title").value;
		$.ajax({
			type:'POST',
			url:'/includes/ajax/slug.php',
			datatype: 'text',
			data:{'title':title},
			success: function(data)
			{
				$('#slug').val(data);
			}
		});
	}
}

$(function()
{
	$("#slug_edit").on("click", function(event)
	{
        event.preventDefault();
		slug_enabled=false;
		document.getElementById("slug").removeAttribute("readonly");
    });
});

$(function()
{
    $("#slug_update").on("click", function(event)
	{
		event.preventDefault();
		slug(1);
    });
});

$(function()
{
    $(document).on('click','.trash',function()
	{
		var image_id= $(this).attr('id');
        $.ajax({
            type:'POST',
            url:'/includes/ajax/delete_image.php',
            data:{'image_id':image_id},
            success: function(data)
			{
				if(data=="YES")
				{
					$("div[id='"+image_id+"']").replaceWith('<div class="col-md-12" style="background-color: #15e563; padding: 5px;">Image Deleted</div>');
					$('html, body').animate({scrollTop: $("#preview").offset().top}, 0);
                }
				else
				{
					alert("can't delete the row")
                 }
             }
		});
	});
});
$(function(){
    $(document).on('click','.trash_tagline',function(){
        var image_id= $(this).attr('id');
        $.ajax({
            type:'POST',
            url:'/includes/ajax/delete_tagline_image.php',
            data:{'image_id':image_id},
            success: function(data){
              if(data=="YES"){
			$("div[id='"+image_id+"']").replaceWith('<div class="col-md-12" style="background-color: #15e563; padding: 5px;">Image Deleted</div>');
			$('html, body').animate({scrollTop: $("#preview2").offset().top}, 0);
                 }else{
                        alert("can't delete the row")
                 }
             }

            });
        });
});
jQuery(document).ready(function()
{  
  // this will grab any url parameter like ?module=test and give you "test" if you search for "module"
  var getUrlParameter = function getUrlParameter(sParam) {
      var sPageURL = decodeURIComponent(window.location.search.substring(1)),
          sURLVariables = sPageURL.split('&'),
          sParameterName,
          i;

      for (i = 0; i < sURLVariables.length; i++) {
          sParameterName = sURLVariables[i].split('=');

          if (sParameterName[0] === sParam) {
              return sParameterName[1] === undefined ? true : sParameterName[1];
          }
      }
  };

  // detect if there's a # in the url
  if(window.location.hash)
  {
	var hash = window.location.hash.substring(1);
	
    var current_module = getUrlParameter('module');
    // if it isn't set, it's likely we're using apache rewrite urls, check there too
    if (typeof current_module === 'undefined')
    {
		to_check = {"users/statistics": 'statistics'};
		$.each(to_check, function(index, value) 
		{
			if (window.location.href.indexOf(index) > -1)
			{
				var current_module = value;
			}
		});
    }
    
    // if it's the stats page, show them the stats they want
    if (typeof current_module !== 'undefined' && current_module == 'statistics')
    {
      if (hash == 'trends')
      {
        $("#trends").show();
        $("#monthly").hide();
      }
      else if (hash == 'monthly')
      {
        $("#trends").hide();
        $("#monthly").show();
      }
    }
  }
	$(".remove_announce").on('click', function(event)
	{
		event.preventDefault();
		var announce_id = $(this).attr("data-announce-id");
		var days = $(this).attr('data-days');
		var expiry_days = 60;

		if (typeof days !== typeof undefined && days !== false) 
		{
			expiry_days = days;
		}
		
		setCookie("gol_announce_" + announce_id, "set", expiry_days);
		
		$(this).closest(".announce").hide();
	});

	$("#trends_link").on('click', function(event)
	{
		event.preventDefault();
		document.location.hash = "#trends";
		$("#trends").show();
		$("#monthly").hide();
	});
	$("#monthly_link").on('click', function(event)
	{
		event.preventDefault();
		document.location.hash = "#monthly";
		$("#trends").hide();
		$("#monthly").show();
	});
  
  // navbar toggle menu
	$(document).on('click', ".toggle-nav > a", function(event) {
    event.preventDefault();
    event.stopPropagation();
  	var $toggle = $(this).closest('.toggle-nav').children('.toggle-content');
    if ($toggle.hasClass('toggle-active'))
    {
      $($toggle).removeClass('toggle-active');
    }
    else
    {
      $(".toggle-content").removeClass('toggle-active');
      $($toggle).addClass('toggle-active');
    }
  });

	$(document).click(function(e) 
	{
		if (!$(e.target).is('#search-button-nav .toggle-content .search-field') && !$(e.target).is('#search-button-nav .toggle-content')) 
		{
			$(".toggle-content").removeClass('toggle-active');
		}
	});

  // for checking usernames
  var charReg = /^\s*[a-zA-Z0-9-_]+\s*$/;
  $('.keyup-char').keyup(function () {
      $('span.error-keyup-1').hide();
      var inputVal = $(this).val();

      if (!charReg.test(inputVal)) {
          $(this).parent().find(".register-warning").show();
      } else {
          $(this).parent().find(".register-warning").hide();
      }
  });

  // for checking emails
  $('.keyup-char-email').keyup(function () {
      $('div.error-keyup-1').hide();
      $('div.error-keyup-2').hide();
      var inputVal = $(this).val();

  if (!validateEmail(inputVal)) {
      $(this).parent().find(".register-warning").show();
      $(this).parent().find(".all-ok").hide();
  } else {
      $(this).parent().find(".register-warning").hide();
      $(this).parent().find(".all-ok").show();
  }
  });

	if ( $.isFunction($.fn.select2) ) 
	{
		$("#articleCategories").select2({
			selectOnClose: true,
			width: '100%',
			ajax: {
			url: "/includes/ajax/categories_ajax.php",
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
				q: params.term // search term
				};
			},
			processResults: function (data) {
				return {
				results: $.map(data, function(obj) {
					return { id: obj.id, text: obj.text };
				})
				};
			},
			cache: true,
			},
			minimumInputLength: 2
		});
		
		$("#gpu_picker").select2({
			selectOnClose: true,
			width: '100%',
			ajax: {
			url: "/includes/ajax/gpu_ajax.php",
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
				q: params.term // search term
				};
			},
			processResults: function (data) {
				return {
				results: $.map(data, function(obj) {
					return { id: obj.id, text: obj.text };
				})
				};
			},
			cache: true,
			},
			minimumInputLength: 2
		});
		
  $(".call_usernames").select2({
    selectOnClose: true,
    width: '100%',
    ajax: {
      url: "/includes/ajax/call_usernames.php",
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term // search term
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data, function(obj) {
            return { id: obj.id, text: obj.text };
          })
        };
      },
      cache: true,
    },
    minimumInputLength: 2
  });
    $(".call_user_groups").select2({
    selectOnClose: true,
    width: '100%',
    ajax: {
      url: "/includes/ajax/call_user_groups.php",
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term // search term
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data, function(obj) {
            return { id: obj.id, text: obj.text };
          })
        };
      },
      cache: true,
    },
    minimumInputLength: 2
  });
	$(".call_modules").select2({
    selectOnClose: true,
    width: '100%',
    ajax: {
      url: "/includes/ajax/call_modules.php",
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term // search term
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data, function(obj) {
            return { id: obj.id, text: obj.text };
          })
        };
      },
      cache: true,
    },
    minimumInputLength: 2
  });

}
	var clipboard = new Clipboard('.btn');

	// Enable on all forms
	$('form').areYouSure();

	var input_counter = 2;
	$("#add-poll").click(function () 
	{
		$('#pquestion').prop("disabled", false);
		$('.poll-option').prop("disabled", false);
		$('#addButton').prop("disabled", false);
		$('#removeButton').prop("disabled", false);
		$("#create-poll").show();
	});
	$("#delete-poll").click(function () 
	{
		$('#pquestion').prop("disabled", true);
		$('.poll-option').prop("disabled", true);
		$('#addButton').prop("disabled", true);
		$('#removeButton').prop("disabled", true);
	 $("#create-poll").hide();
	});
	$("#addButton").click(function ()
	{
		input_counter++;
		var newTextBoxDiv = $(document.createElement('div')).attr("id", 'TextBoxDiv' + input_counter);
		newTextBoxDiv.after().html('<input type="text" name="poption[]" class="poll-option" id="option'+ input_counter +'" value="" />');
		newTextBoxDiv.appendTo("#TextBoxesGroup");
	});
	$("#removeButton").click(function () 
	{
		$("#TextBoxDiv" + input_counter).remove();
		input_counter--;
	});

	$(document).on('click', ".collapse_header", function()
	{
		$header = $(this);
		//getting the next element
		$content = $header.next();
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		$content.slideToggle(500)
	});

	if($("#tagline").length > 0)
	{
		countchars();
		jQuery("#tagline").mousedown(countchars);
		jQuery("#tagline").keyup(countchars);
	}

	if($("#title").length > 0)
	{
		if (slug_enabled == true)
		{
			slug();
			jQuery("#title").mousedown(slug);
			jQuery("#title").keyup(slug);
		}
	}

	$('#photoimg').off('click').on('change', function()
	{
		$("#imageform").ajaxForm({
		beforeSubmit:function(){
		    $("#imageloadstatus").show();
		     $("#imageloadbutton").hide();
		},
		success:function(data)
		{
			$("#preview").append(data);
		    $("#imageloadstatus").hide();
		    $("#imageloadbutton").show();
			resetFormElement($('#photoimg'));
		},
		error:function(data)
		{
			$("#preview").append(data);
		    $("#imageloadstatus").hide();
		    $("#imageloadbutton").show();
			resetFormElement($('#photoimg'));
		}}).submit();
	});

	$('#photoimg2').off('click').on('change', function()
	{
		$("#imageform2").ajaxForm({target: '#preview2',
		beforeSubmit:function()
		{
			$("#imageloadstatus2").show();
			$("#imageloadbutton2").hide();
		},
		success:function()
		{
        	$("#imageloadstatus2").hide();
        	$("#imageloadbutton2").show();
			resetFormElement($('#photoimg2'));
		},
		error:function()
		{
			$("#imageloadstatus2").hide();
  			$("#imageloadbutton2").show();
			resetFormElement($('#photoimg2'));
		}
    }).submit();
	});

    $(".like-button").show();

	$(document).on('click', '.likebutton', function()
	{
		//Get our comment
		var comment = $(this).parents('.comment')[0];
		//Get the post ID
		var sid = $(this).attr("data-id");
		// get the author id of the comment itself
		var author_id = $(this).attr("data-author-id");
		// get the id of the article it's on
		var article_id = $(this).attr("data-article-id");
		// the type of like this is
		var type = $(this).attr("data-type");
		//Send off a like
		var $that = $(this);
		$.post('/includes/ajax/like.php', 
		{
			comment_id: sid,
			author_id: author_id,
			article_id: article_id,
			type: type,
			sta: $that.find("span").text().toLowerCase()
		}, 
		function (returndata)
		{
			if(returndata['result'] == "liked")
			{
				var likeobj = $("#"+sid+" div.likes");
				var total_likes_obj = likeobj.find('.total_likes');
				var who_likes_obj = likeobj.find('.who-likes');

				var wholikes = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='+sid+'">Who?</a>';
				total_likes_obj.text(returndata['total']);
				who_likes_obj.html(wholikes);
				
				if ( $(likeobj).css('display') == 'none' )
				{
					likeobj.show();
				}

				var button = $(comment).find(".likebutton span");
				button.text("Unlike").removeClass("like").addClass("unlike");
			}
			else if(returndata['result'] == "unliked")
			{
				var likeobj = $("#"+sid+" div.likes");
				var total_likes_obj = likeobj.find('.total_likes');
				var who_likes_obj = likeobj.find('.who-likes');
				
				var wholikes = "";
				if (returndata['total'] > 0)
				{
					wholikes = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='+sid+'">Who?</a>';
					total_likes_obj.text(returndata['total']);
					who_likes_obj.html(wholikes);
				}
				else
				{
					likeobj.hide();
				}
				var button = $(comment).find(".likebutton span");
				button.text("Like").removeClass("unlike").addClass("like");
			}
			else if (returndata['result'] == "nope") 
			{
				$that.qtip(
				{
					content: {
					text: 'You need to be <a href="/index.php?module=login">logged in</a> to like a post. Or <a href="/index.php?module=register">register</a> to become a GOL member'
					},
					position: {
						my: 'bottom center',
						at: 'top center'
					},
					style: {
						classes: 'qtip-bootstrap qtip-shadow'
					},
					hide: {
						delay: 2000
					},
					show: true
				});
			}
		});
	});

	$(document).on('click', '.likearticle', function()
	{
		// get this like link
		var this_link = $(this).parents('.article_likes')[0];
		//Get the article ID
		var article_id = $(this).attr("data-id");
		var likeobj = $("#article-likes");
		var type = $(this).attr("data-type");

		//Send off a like
		var $that = $(this);
		$.post('/includes/ajax/like.php', 
		{
			article_id: article_id,
			type: type,
			sta: $that.find("span").text().toLowerCase()
		}, function (returndata)
		{
			if(returndata['result'] == "liked")
			{
				var numlikes = likeobj.html().replace(" Likes","");
				numlikes = parseInt(numlikes) + 1;
				var wholikes = "";
				if (numlikes > 0)
				{
					wholikes = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?article_id='+article_id+'">Who?</a>';
				}
				$("#who-likes-article").html(wholikes);
				likeobj.html(numlikes + " Likes");
				var button = $(this_link).find(".likearticle span");
				button.text("Unlike").removeClass("like").addClass("unlike");
			}
			if(returndata['result'] == "unliked")
			{
				var numlikes = likeobj.html().replace(" Likes","");
				numlikes = parseInt(numlikes) - 1;
				var wholikes = "";
				if (numlikes > 0)
				{
					wholikes = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?article_id='+article_id+'">Who?</a>';
				}
				$("#who-likes-article").html(wholikes);
				likeobj.html(numlikes + " Likes" );
				var button = $(this_link).find(".likearticle span");
				button.text("Like").removeClass("unlike").addClass("like");
			}
			else if (returndata['result'] == "nope")
			{
				$that.qtip(
				{
					content: {
						text: 'You need to be <a href="/index.php?module=login">logged in</a> to like a post. Or <a href="/index.php?module=register">register</a> to become a GOL member'
					},
					position: {
						my: 'bottom center',
						at: 'top center'
					},
					style: {
						classes: 'qtip-bootstrap qtip-shadow'
					},
					hide: {
						delay: 2000
					},
					show: true
				});
			}
		});
	});

	// bookmark content
	$('.bookmark-content').click(function(event)
	{
		event.preventDefault();
		var id = $(this).data('id');
		var type = $(this).data('type');
		var method = $(this).data('method');
		var page = $(this).data('page');
		var parent_id = $(this).data('parent-id');
		var link = $(this);

		$.post('/includes/ajax/bookmark-content.php', {'id':id, 'type':type, 'method':method, 'parent_id':parent_id},
		function(data)
		{
			// we need to do this, or else it's seen as text and not a JSON
			//data = JSON.parse(data);
			if (data.result == 'added')
			{
				link.data("method", 'remove');
				link.addClass("bookmark-saved");
				link.attr('title','Remove Bookmark');
			}
			else if (data.result = 'removed')
			{
				if (page != 'usercp')
				{
					link.data("method", 'add');
					link.removeClass("bookmark-saved");
					link.attr('title','Bookmark');
				}
				else if (page == 'usercp')
				{
					link.parent().parent().parent().fadeOut(500);
				}
			}
		});
	});

	// delete a single notification from the users list
	var $this_link = $('.delete_notification').click(function(event)
	{
		event.preventDefault();
		var note_id = $(this).data('note-id');

		$.post('/includes/ajax/delete-notification.php', {'note_id':note_id},
		function(data)
		{
			// we need to do this, or else it's seen as text and not a JSON
			data = JSON.parse(data);
			if (data.result == 1)
			{
				$('#note-' + note_id).find('span').remove();
				$('#note-' + note_id).fadeOut(500);
			}
		});
	});

	$(".poll_content").on("click", ".close_poll", function()
	{
		var poll_id = $(this).data('poll-id');
		$.post('/includes/ajax/close_poll.php', {'poll_id':poll_id},
		function(data)
		{
			if (data.result == 1)
			{
				$('.poll_content').load('/includes/ajax/poll_results.php', {'poll_id':poll_id});
				window.alert("Poll closed!");
			}
			else if (data.result == 2)
			{
				window.alert("Sorry, I am unable to do that.");
			}
		});
	});

	$(".poll_content").on("click", ".open_poll", function()
	{
		var poll_id = $(this).data('poll-id');
		$.post('/includes/ajax/open_poll.php', {'poll_id':poll_id},
		function(data)
		{
			if (data.result == 1)
			{
				$('.poll_content').load('/includes/ajax/poll_results.php', {'poll_id':poll_id});
				window.alert("Poll opened!");
			}
			else if (data.result == 2)
			{
				$('.poll_content').load('/includes/ajax/poll_options.php', {'poll_id':poll_id});
				window.alert("Poll opened!");
			}
			else if (data.result == 3)
			{
				window.alert("Sorry, I am unable to do that.");
			}
		});
	});

	$(".poll_content").on("click", ".results_button", function(){
		var poll_id = $(this).data('poll-id');
		$('.poll_content').load('/includes/ajax/poll_results.php', {'poll_id':poll_id});
	});

	$(".poll_content").on("click", ".poll_button_vote", function(){
		var button = $(this);
		var poll_id = $(this).data('poll-id');
		var option_id = $(this).data('option-id');
		$.post('/includes/ajax/poll_vote.php', {'poll_id':poll_id, 'option_id':option_id},
		function(data)
		{
			if (data.result == 1)
			{
				$('.poll_content').load('/includes/ajax/poll_results.php', {'poll_id':poll_id});
			}
			else if (data.result == 2)
			{
				window.alert("Sorry, but voting is closed!");
			}
			else if (data.result == 3)
			{
				window.alert("Sorry, but you have already voted in this poll!");
			}
			else 
			{
				button.text("Try again later, something broke").attr('disabled', 'disabled');
				setTimeout(function(){ button.removeAttr('disabled') }, 2000);
			}
		});
	});

	$(".poll_content").on("click", ".back_vote_button", function()
	{
		var poll_id = $(this).data('poll-id');
		$('.poll_content').load('/includes/ajax/poll_options.php', {'poll_id':poll_id});
	});

	// claim a key from bbcode key giveaway
	$(document).on('click', '#claim_key', function(e)
	{
		e.preventDefault();

		var giveaway_id = $(this).attr('data-game-id');
		$.post('/includes/ajax/claim_key.php', { 'giveaway_id':giveaway_id },
		function(data)
		{
			if (data.result == 1)
			{
				$('#key-area').text("Here's your key: " + data.key);
			}
			if (data.result == 3)
			{
				$('#key-area').text("You have to login to redeem a key!");
			}
		});
	});

	// to mark PC info as being up to date
	$(document).on('click', '#pc_info_update', function(e)
	{
		e.preventDefault();
		$.post('/includes/ajax/pc-info-update.php', function(data)
		{
			if (data.result == 1)
			{
				$('#pc_info_done').show();
				$('#pc_info_done').html("<br />Thank you, your PC info has been updated!");
			}
		});
	});

	// this is for voting in the GOTY awards
	$('.votebutton').click(function()
	{
		var button = $(this);
		var category_id = $(this).data('category-id');
		var game_id = $(this).data('game-id');
		$.post('/includes/ajax/goty_vote.php', {'category_id':category_id, 'game_id':game_id},
		function(data)
		{
			if (data.result == 1)
			{
				$(button).html('Vote Saved!');
				$(button).addClass("vote_done");
				$.fancybox.open({type:"inline", href:"#wrap"})
			}
			else if (data.result == 2)
			{
				document.getElementById("wrap_text").innerHTML = "Sorry, but voting is closed!";
				$.fancybox.open({type:"inline", href:"#wrap"})
			}
			else if (data.result == 3)
			{
				document.getElementById("wrap_text").innerHTML = "Sorry, but you have already voted in this category!";
				$.fancybox.open({type:"inline", href:"#wrap"})
			}
			else
			{
				button.text("Try again later, something broke").attr('disabled', 'disabled');
				setTimeout(function(){ button.removeAttr('disabled') }, 2000);
			}
		});
	});

	$('#generate_preview').click(function()
	{
		var article_id = $(this).data('article-id');
		$.post('/includes/ajax/generate_preview_code.php', { article_id: article_id })
		.done (function(result)
		{
			$('#preview_code').val(result);
		});
	});

	// this controls the subscribe to comments link inside articles_full.php
	$(document).on('click', '#subscribe-link', function(e)
	{
		e.preventDefault();

		var type = $(this).attr('data-sub');
		var article_id = $(this).attr('data-article-id');
		$.post('/includes/ajax/subscribe-article.php', { 'type':type, 'article-id':article_id },
		function(data)
		{
			var myData = JSON.parse(data);
			if (myData.result == 'subscribed')
			{
				$('#subscribe-link').attr('data-sub','unsubscribe');
				$("#subscribe-link").attr("href", "/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id=" + article_id);
				$('#subscribe-link span').text('Unsubscribe from comments');
			}
			else if (myData.result == 'unsubscribed')
			{
				$('#subscribe-link').attr('data-sub','subscribe');
				$("#subscribe-link").attr("href", "/index.php?module=articles_full&amp;go=subscribe&amp;article_id=" + article_id);
				$('#subscribe-link span').text('Subscribe to comments');
			}
		});
	});

	$(document).on('click', ".gallery_item", function() 
	{
		var filename = $(this).data('filename');
		var id = $(this).data('id');
		$('#preview2').html('<img src="/uploads/tagline_gallery/' + filename + '" alt="image" />');
		$.fancybox.close();
		$.post('/includes/ajax/gallery_tagline_sessions.php', { 'id':id, 'filename':filename });
	});

	$('#preview_text_button').click(function()
	{
		var text = $('.bbcode_editor').val();
		$('.pm_text_preview').load('/includes/ajax/call_bbcode.php', {'text':text}, function() {
		$('.preview_pm').show();
		$('#preview').scrollMinimal();
		$('.preview_pm').highlight();
		});
	});
	
	$('.admin_article_comment_button').click(function(e)
	{
		e.preventDefault();
		
		var url = "/includes/ajax/article_comment_pagination.php";
		
		var form = $(this).closest('form');

		$.ajax({
			type: "POST",
			url: url,
			dataType:"json",
			data: $("#admin_comment").serialize(),
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					$('.comments').load('/includes/ajax/article_comment_pagination.php', {'type':'reload', 'article_id':data['article_id'], 'page':data['page']}, function()
					{
						$(form).removeClass('dirty'); // prevent ays dialogue when leaving
						$('.comments #r' + data['comment_id']).scrollMinimal();
						$('.comments').highlight();
					});
					$('#comment').val('');
				}
				if (data['result'] == 'error')
				{
					alert(data['message']);
				}
			}
		}).done(function() 
		{
			$('.admin_article_comment_button').prop('disabled', false);
		});
	});
	
	/* ADMIN HOME ADMIN + EDITOR COMMENTS */
	
	$('.send_admin_comment').click(function(e)
	{
		e.preventDefault();
		var form = $(this).closest('form');
		var url = "/includes/ajax/admin/admin_home_comment.php"; 
		
		$.ajax({
			type: "POST",
			url: url,
			dataType:"json",
			data: form.serialize(), 
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					$(form).removeClass('dirty'); // prevent ays dialogue when leaving
					$('.' + data['type'] + '_text').val('');
					$('.' + data['type'] + '_comments_list').html(data['text']);
					$('.' + data['type'] + '_comments_div').highlight();
				}
				else
				{
					alert(data['message']);
				}
			}
		});
	});
	
	/* ADMIN HOME ARTICLE PLANNER */
	
	$(document).on('click', ".send_editor_plan", function(e)
	{
		e.preventDefault();
		var form = $(this).closest('form');
		var url = "/includes/ajax/admin/editor_plans.php"; 
		
		$.ajax({
			type: "POST",
			url: url,
			dataType:"json",
			data: form.serialize(), 
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					$(form).removeClass('dirty'); // prevent ays dialogue when leaving
					$('.editor_plans_box').html(data['text']);
					$('.editor_plans_content').highlight();
				}
				else
				{
					alert(data['message']);
				}
			}
		});
	});
	
	$(document).on('click', ".delete_editor_plan", function(e)
	{
		e.preventDefault();
		var note_id = $(this).data('note-id');
		var owner_id = $(this).data('owner-id');
		
		$.ajax({
			type: "POST",
			url: '/includes/ajax/admin/editor_plans.php',
			dataType:"json",
			data: {'note_id':note_id, 'owner_id': owner_id, 'type':'remove'}, 
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					$('#plan-' + note_id).fadeOut(500);
				}
				else
				{
					alert(data['message']);
				}
			}
		});
	});
	
	/* to set the conflict checker session */
	$(document).on('click', ".conflict_confirmed", function(e)
	{
		var form = $(this).closest('form');
		var url = '/includes/ajax/admin/article_conflict_session.php';
		
		$.ajax({
			type: "POST",
			url: url,
			dataType:"json",
			data: form.serialize(), 
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					$('.box.warning.message').fadeOut(500);
				}
				else
				{
					alert(data['message']);
				}
			}
		});
	});

	
	function ajax_page_comments(e, element)
	{
		var url = window.location.href;
		var host = window.location.host;
		// limit to the admin review queue for now
		if(url.indexOf(host + '/admin.php?module=reviewqueue') != -1 || url.indexOf(host + '/admin.php?module=articles&view=Submitted') != -1 || url.indexOf(host + '/admin.php?module=articles&view=Submitted') != -1) 
		{
			e.preventDefault();
			var page = element.attr("data-page");
			var article_id = getUrlParameter('aid');
			$('.comments').load('/includes/ajax/article_comment_pagination.php', {'type':'reload', 'article_id':article_id, 'page':page}, function() 
			{
				$('.comments').scrollMinimal();
				$('.comments').highlight();
			});
			return true;
		}
		return false;
	}
	
	$(document).on('click', "ul.pagination li a, .head-list-position a", function(e)
	{
		var $this = $(this);
		ajax_page_comments(e, $this);
	});
	
	$(document).on('change', ".head-list-position select, .pagination", function(e)
	{
		var $this = $(this).find(':selected');
		if (!ajax_page_comments(e, $this))
		{
			var url = $(this).val(); // get selected value
			if (url) 
			{ // require a URL
				window.location = url; // redirect
			}
		}
	});
	
	/* CHARTS */
	
	$('#preview_chart').click(function()
	{
		var myform = document.getElementById("chart_form");
		var fd = new FormData(myform);
		$.ajax({
			url: '/includes/ajax/preview_chart.php',
			data: fd,
			cache: false,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function (dataofconfirm) {
				$('.chart_preview').html(dataofconfirm);
			}
		});
	});
	
	var label_inputs = $('.labels').length;
	var colour_inputs = $('.colours').length;
	var data_inputs = $('.data').length;
	// for the grouped charts admin section
	$(document).on('click', "#add_label", function() 
	{
		label_inputs++;
		colour_inputs++;
		data_inputs++;
		$('#label_container').append('<div id="label-'+label_inputs+'" class="input-field box fleft" style="width: 50%"><span class="addon">Label #'+label_inputs+':</span><input type="text" class="labels" name="labels[]" placeholder="label '+label_inputs+'" /></div><div id="colour-'+label_inputs+'" class="input-field box fleft" style="width: 50%"><span class="addon">Colour #'+label_inputs+':</span><input class="colours" type="text" name="colours[]" placeholder="#ffffff" /></div>');
		if ($('#chart_grouped').prop('checked')==true)
		{ 
			$('#data_container').append('<div id="data-'+data_inputs+'" class="box">Data for Label #'+data_inputs+'<textarea class="data" name="data-'+data_inputs+'" cols="100" rows="10"></textarea></div>');
		}
		else
		{
			$('#data_container').append('<div id="data-'+data_inputs+'" class="box">Data for Label #'+data_inputs+'<input class="data" type="text" name="data-'+data_inputs+'" /></div>');
		}
	});
	$(document).on('click', "#remove_label", function() 
	{
		$("#label-" + label_inputs).remove();
		$("#colour-" + colour_inputs).remove();
		$("#data-" + data_inputs).remove();
		label_inputs--;
		colour_inputs--;
		data_inputs--;
	});
	$(document).on('click', "#chart_grouped", function()
	{
		if ($(this).prop('checked')==true)
		{ 
			$('input.data').each(function () 
			{
				var current_name = $(this).attr("name");
				var textbox = $(document.createElement('textarea')).attr("class", "data").attr("name", current_name).attr("rows", 10);
				$(this).replaceWith(textbox);
			});
		}
		else
		{
			$('textarea.data').each(function () 
			{
				var current_name = $(this).attr("name");
				var textbox = $(document.createElement('input')).attr("class", "data").attr("name", current_name).attr("type", "text");
				$(this).replaceWith(textbox);
			});			
		}
	});
	
	/* ARTICLE PREVIEW */
	
	$('.admin_preview_article').click(function(e)
	{
		e.preventDefault();
		CKupdate();
		var url = "/includes/ajax/admin/preview_article.php";
		
		// if the preview is hidden, open it
		if ( $('#article_preview').css('display') == 'none' )
		{
			$.ajax({
				type: "POST",
				url: url,
				data: $("#article_editor").serialize(), 
				success: function(data)
				{
					$('#article_preview').html(data);
					$('#article_preview').show();
					$('.admin_preview_article').html('Close Preview');
					$('#article_preview').scrollMinimal();
				}
			});
		}
		else
		{
			$('#article_preview').hide();
			$('.admin_preview_article').html('Preview');
		}
	});
	
	/* BBCODE EDITOR BITS */
	
	// quoting a comment
	$(document).on('click', ".quote_function", function(e) 
	{
		e.preventDefault();
		
		var id = $(this).attr('data-id');
		var type = $(this).attr('data-type');
		var url = "/includes/ajax/quote_comment.php";
		
		$.ajax({
			type:'POST',
			url: url,
			dataType: 'json',
			data:{'id':id, 'type':type},
			success: function(data)
			{
				if (data['result'] == 'done')
				{
					content = "[quote=" + data['username'] + "]" + decodeEntities(data['text']);
					content += "[/quote]";
					
					var current_text = $('#comment').val();
					
					$('#comment').val(current_text + content); 
					
					$('#comment').scrollMinimal();
				}
				if (data['result'] == 'error')
				{
					alert(data['message']);
				}
			}
		});
    });
	
	/* CKEditor */
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
		CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><a href="'+text+'" data-fancybox="images"><img src="'+text+'" /></a></p>');
	});
	
	// thumbnail insertion
	$(document).on('click', ".uploads .add_thumbnail_button", function(e) 
	{
		var thumbnail = $(this).data('url');
		var big_image = $(this).data('main-url');
		CKEDITOR.instances.ckeditor_gol.insertHtml('<p style="text-align:center"><a href="'+big_image+'" data-fancybox="images"><img src="'+thumbnail+'" /></a></p>');
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
	
	// prevent accidental logouts
	$(document).on('click', ".logout_link", function(e) 
	{
		e.preventDefault();
		// Logout confirmation
		if (confirm('Are you sure you want to logout?')) 
		{
			window.location = '/index.php?act=Logout';
		}
	});

	// send comment by CTRL + Enter
	$('#comment_form').on('keydown', function(e) 
	{
		if (e.ctrlKey && e.keyCode === 13) 
		{
			$('#comment_form').trigger('submit');
		}
	});

	// hide long quotes
	// NOT FINISHED
	// Need to find the best approach to making the entire line italic, including the "from" bit
	var showChar = 600;
	var moretext = "<em>Click to view long quote </em>";
	var lesstext = "<em>Click to hide long quote </em>";
	$('.comment_quote').each(function() 
	{
		var actual_text = $(this).text();
		var content = $(this).outerHTML();

		if(actual_text.length > showChar) 
		{
			var cite = $(this).find('cite').first().text();
			var cite_link = '';

			if (cite != 'Quote')
			{
				cite_link = 'from ' + cite;
			}

			var html = '<span class="morecontent">' + content + '</span><a href="" class="morelink">' + moretext + cite_link + '</a><br />';

			$(this).replaceWith(html);
		}
	});

	$(".morelink").click(function()
	{
		var cite = $(this).prev().find('cite').first().text();
		var cite_link = '';

		if (cite != 'Quote')
		{
			cite_link = 'from ' + cite;
		}

		if($(this).hasClass("less")) 
		{
			$(this).removeClass("less");
			$(this).html(moretext + cite_link);
		} 
		else 
		{
			$(this).addClass("less");
			$(this).html(lesstext + cite_link);
		}
		$(this).prev().toggle();
		return false;
	});	
	var $gamesMulti = $("#articleGames").select2({
		selectOnClose: true,
		width: '100%',
		ajax: {
		  url: "/includes/ajax/games_ajax.php",
		  dataType: 'json',
		  delay: 250,
		  data: function (params) {
			return {
			  q: params.term // search term
			};
		  },
		  processResults: function (data) {
			return {
			  results: $.map(data, function(obj) {
				return { id: obj.id, text: obj.text };
			  })
			};
		  },
		  cache: true,
		},
		minimumInputLength: 2
		});
	  
	$(".clear-games").on("click", function (e) { e.preventDefault(); $gamesMulti.val(null).trigger("change"); });
	$("#genres").select2({
	selectOnClose: true,
	width: '100%',
	ajax: {
    url: "/includes/ajax/gamesdb/game_genres_ajax.php",
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term // search term
      };
    },
    processResults: function (data) {
      return {
        results: $.map(data, function(obj) {
          return { id: obj.id, text: obj.text };
        })
      };
    },
    cache: true,
  },
  minimumInputLength: 2
  });
  $("#game-wishlist-select").select2({
	selectOnClose: true,
	width: '100%',
	ajax: {
    url: "/includes/ajax/games_ajax.php",
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term // search term
      };
    },
    processResults: function (data) {
      return {
        results: $.map(data, function(obj) {
          return { id: obj.id, text: obj.text };
        })
      };
    },
    cache: true,
  },
  minimumInputLength: 2
  });

	/* SALES PAGE */

	// pagination ajax
	$(document).on('click', ".sales-pagination li a", function(e) 
	{
		e.preventDefault();
		var form = $("#game_filters");
		var url = "/includes/ajax/sales/display_normal.php";
		var page = $(this).attr("data-page");
	  
		$.ajax({
			type: "GET",
			url: url,
			data: {'filters': form.serialize(), 'page': page}, 
			success: function(data)
			{
				$(form).removeClass('dirty'); // prevent ays dialogue when leaving
				$('div.normal-sales').html(data);
				$('div.normal-sales').highlight();
				$('div.normal-sales').scrollMinimal();
				$('#sale-search').easyAutocomplete(sale_search_options); // required so EAC works when loaded dynamically
			}
		});
	});

	// sales & free games page filters
	$(document).on('change', "#game_filters", function(e)
	{
		var form = $("#game_filters");

		var formName = form.attr('name');
		if (formName == 'free')
		{
			var url = "/includes/ajax/gamesdb/display_free.php";
			var list_update = 'free-list';
		}
		else if (formName == 'sales')
		{
			var url = "/includes/ajax/sales/display_normal.php";
			var list_update = 'normal-sales';
		}

		$.ajax({
			type: "GET",
			url: url,
			data: {'filters': form.serialize()}, 
			success: function(data)
			{
				$(form).removeClass('dirty'); // prevent ays dialogue when leaving
				$('div.'+list_update).html(data);
				$('div.'+list_update).highlight();
				$('#sale-search').easyAutocomplete(sale_search_options); // required so EAC works when loaded dynamically
			}
		});
	});

	$(document).on('click', "#sale-search-form div.eac-item", function(e)
	{
		e.preventDefault();
		var form = $("#sales_filters");
		var text = $(this).text();
		var url = "/includes/ajax/sales/display_normal.php";

		$.ajax({
			type: "GET",
			url: url,
			data: {'q': text, 'filters': form.serialize()}, 
			success: function(data)
			{
				$('div.normal-sales').html(data);
				$('div.normal-sales').highlight();
				$('#sale-search').easyAutocomplete(sale_search_options); // required so EAC works when loaded dynamically
			}
		});		
	});

	/* GAMES DATABASE */

	// filters show/hide
	$(document).on('click', ".filter-title .filter-link", function()
	{
		$header = $(this);
		//getting the next element
		$content = $header.closest('.filter-box').find('.filter-content');
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		$content.slideToggle(500);
		$header.closest('.filter-box').find('span').toggleClass("caret-down caret-right");
	});

	// uploading a small pic
	$('#small_pic').off('click').on('change', function()
	{
		$("#small_pic").ajaxForm({target: '#preview2',
		beforeSubmit:function()
		{
			$("#imageloadstatus2").show();
			$("#imageloadbutton2").hide();
		},
		success:function()
		{
        	$("#imageloadstatus2").hide();
        	$("#imageloadbutton2").show();
			resetFormElement($('#photoimg2'));
		},
		error:function()
		{
			$("#imageloadstatus2").hide();
  			$("#imageloadbutton2").show();
			resetFormElement($('#photoimg2'));
		}
    }).submit();
	});

	/* free games page */

	// pagination ajax
	$(document).on('click', ".free-games-pagination li a", function(e) 
	{
		e.preventDefault();
		var form = $("#game_filters");
		var url = "/includes/ajax/gamesdb/display_free.php";
		var page = $(this).attr("data-page");
	  
		$.ajax({
			type: "GET",
			url: url,
			data: {'page': page, 'filters': form.serialize()}, 
			success: function(data)
			{
				$('div.free-list').html(data);
				$('div.free-list').highlight();
				$('div.free-list').scrollMinimal();
				$('#free-search').easyAutocomplete(free_search_options); // required so EAC works when loaded dynamically
			}
		});
	});

	$(document).on('click', "#free-search-form div.eac-item", function(e)
	{
		e.preventDefault();
		var text = $(this).text();
		var url = "/includes/ajax/gamesdb/display_free.php";

		$.ajax({
			type: "GET",
			url: url,
			data: {'q': text}, 
			success: function(data)
			{
				$('div.free-list').html(data);
				$('div.free-list').highlight();
				$('#free-search').easyAutocomplete(free_search_options); // required so EAC works when loaded dynamically
			}
		});		
	});
});

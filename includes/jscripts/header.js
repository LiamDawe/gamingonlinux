// scroll to an element if it's not in view, all other ways I could find completely sucked
jQuery.fn.scrollMinimal = function(smooth) {
  var cTop = this.offset().top;
  var cHeight = this.outerHeight(true);
  var windowTop = $(window).scrollTop();
  var visibleHeight = $(window).height();

  if (cTop < windowTop) {
    if (smooth) {
      $('body').animate({'scrollTop': cTop}, 'slow', 'swing');
    } else {
      $(window).scrollTop(cTop);
    }
  } else if (cTop + cHeight > windowTop + visibleHeight) {
    if (smooth) {
      $('body').animate({'scrollTop': cTop - visibleHeight + cHeight}, 'slow', 'swing');
    } else {
      $(window).scrollTop(cTop - visibleHeight + cHeight);
    }
  }
};

jQuery.fn.highlight = function () {
    $(this).each(function () {
        var el = $(this);
        $("<div/>")
        .width(el.outerWidth())
        .height(el.outerHeight())
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

function disableFunction() {
    document.getElementById("send").disabled = 'true';
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

$(function(){
    $("#slug_edit").on("click", function(event){
        event.preventDefault();
		slug_enabled=false;
		document.getElementById("slug").removeAttribute("readonly");
    });
});

$(function(){
    $("#slug_update").on("click", function(event){
        event.preventDefault();
		slug(1);
    });
});



$(function(){
    $(document).on('click','.trash',function(){
        var image_id= $(this).attr('id');
        $.ajax({
            type:'POST',
            url:'/includes/delete_image.php',
            data:{'image_id':image_id},
            success: function(data){
                 if(data=="YES"){
			$("div[id='"+image_id+"']").replaceWith('<div class="col-md-12" style="background-color: #15e563; padding: 5px;">Image Deleted</div>');
			$('html, body').animate({scrollTop: $("#preview").offset().top}, 0);
                 }else{
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
            url:'/includes/delete_tagline_image.php',
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
  // navbar toggle menu
  $(".toggle-nav > a").on('click', function(event){
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

  // hide the toggle-nav if you click outside of it
  $(document).on("click", function () {
    $(".toggle-content").removeClass('toggle-active');
  });

  if ( $.isFunction($.fn.select2) ) {
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
  $(".livestream_user_ids").select2({
    selectOnClose: true,
    width: '100%',
    ajax: {
      url: "/includes/ajax/livestream_users_ajax.php",
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
}
  var clipboard = new Clipboard('.btn');

	$(".computer_deets").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: false,
		width		: '70%',
		height		: '60%',
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none'
	});

  $(".who_likes").fancybox({
    maxWidth	: 800,
    maxHeight	: 600,
    fitToView	: false,
    width		: '70%',
    height		: '60%',
    autoSize	: false,
    closeClick	: false,
    openEffect	: 'none',
    closeEffect	: 'none'
  });

  $(".gallery_tagline").fancybox({
    fitToView	: false,
    width		: '80%',
    height		: '80%',
    autoSize	: false,
    closeClick	: false,
    openEffect	: 'none',
    closeEffect	: 'none',
    autoCenter : false
  });

  // Enable on all forms
  $('form').areYouSure();

	var input_counter = 2;
	$("#add-poll").click(function () {
		$('#pquestion').prop("disabled", false);
		$('.poll-option').prop("disabled", false);
		$('#addButton').prop("disabled", false);
		$('#removeButton').prop("disabled", false);
		$("#create-poll").show();
	});
	$("#delete-poll").click(function () {
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
	$("#removeButton").click(function () {
	 $("#TextBoxDiv" + input_counter).remove();
	 input_counter--;
	});

    $(".collapse_header").click(function () {
    $header = $(this);
    //getting the next element
    $content = $header.next();
    //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
    $content.slideToggle(500)});

	$('.quote_function').click(function()
	{
	    $('html, body').animate({
		scrollTop: $('.octus-editor').offset().top
	    }, 1000);
	    return false;
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

	$(".fancybox").fancybox();

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

    $('#vote').on('click', function(){
        var nzData = '/url.com?Zip=8000 #module';

        $('#foo').load(nzData, function(){
         var foo = $('#foo').html();
         $.fancybox(foo);
        });
    });
	});

    $(".like-button").show();

    $(".likebutton").click(function(){
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
    //Send of a like (needs a like/dislike check)
      var $that = $(this);
      $.post('/includes/ajax/like.php', {
       comment_id: sid,
       author_id: author_id,
       article_id: article_id,
       type: type,
       sta: $that.find("span").text().toLowerCase()
      }, function (returndata){
        if(returndata === "liked")
        {
          var likeobj = $("#"+sid+" div.likes");
          var numlikes = likeobj.html().replace(" Likes","");
          numlikes = parseInt(numlikes) + 1;
          var wholikes = "";
          if (numlikes > 0)
          {
            wholikes = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?comment_id='+sid+'">Who?</a>';
          }
          likeobj.html(numlikes + " Likes" + wholikes);
          var button = $(comment).find(".likebutton span");
          button.text("Unlike").removeClass("like").addClass("unlike");
      }
      else if(returndata === "unliked")
      {
          var likeobj = $("#"+sid+" div.likes");
          var numlikes = likeobj.html().replace(" Likes","");
          numlikes = parseInt(numlikes) - 1;
          var wholikes = "";
          if (numlikes > 0)
          {
            wholikes = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?comment_id='+sid+'">Who?</a>';
          }
          likeobj.html(numlikes + " Likes" + wholikes);
          var button = $(comment).find(".likebutton span");
          button.text("Like").removeClass("unlike").addClass("like");
      }
      else if ( returndata === "5" ) {
          $that.qtip({
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
      }); //end of .post callback
  }); //end of .click callback

  $(".likearticle").click(function(){
  // get this like link
  var this_link = $(this).parents('.likes')[0];
  //Get the comment ID
  var article_id = $(this).attr("data-id");
  var likeobj = $("#article-likes");
  var type = $(this).attr("data-type");

  //Send of a like (needs a like/dislike check)
    var $that = $(this);
    $.post('/includes/ajax/like.php', {
     article_id: article_id,
     type: type,
     sta: $that.find("span").text().toLowerCase()
    }, function (returndata){
      if(returndata === "liked")
      {
        var numlikes = likeobj.html().replace(" Likes","");
        numlikes = parseInt(numlikes) + 1;
        var wholikes = "";
        if (numlikes > 0)
        {
          wholikes = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?article_id='+article_id+'">Who?</a>';
        }
        $("#who-likes-article").html(wholikes);
        likeobj.html(numlikes + " Likes");
        var button = $(this_link).find(".likearticle span");
        button.text("Unlike").removeClass("like").addClass("unlike");
    }
    else if(returndata === "unliked")
    {
        var numlikes = likeobj.html().replace(" Likes","");
        numlikes = parseInt(numlikes) - 1;
        var wholikes = "";
        if (numlikes > 0)
        {
          wholikes = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?article_id='+article_id+'">Who?</a>';
        }
        $("#who-likes-article").html(wholikes);
        likeobj.html(numlikes + " Likes" );
        var button = $(this_link).find(".likearticle span");
        button.text("Like").removeClass("unlike").addClass("like");
    }
    else if ( returndata === "5" ) {
        $that.qtip({
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
    }); //end of .post callback
}); //end of .click callback

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
        // adjust the navbar counter if this was an unread item
        if($('#note-' + note_id + ' img').hasClass('envelope'))
        {
          $('#notes-counter').html(parseInt($('#notes-counter').html(), 10)-1);
        }
        $('#note-' + note_id).find('span').remove();
        $('#note-' + note_id).fadeOut(500);

        // change the alertbox to normal if there's none left and remove the counter
        var total_left = parseInt($('#notes-counter').text());
        if (total_left === 0)
        {
          $("#alert_box").toggleClass('alerts-box-new alerts-box-normal');
          $("#notes-counter").remove();
        }
      }
    });
  });

  $(".poll_content").on("click", ".close_poll", function()
  {
    var poll_id = $(this).data('poll-id');
  $.post('/includes/ajax/close_poll.php', {'poll_id':poll_id},
 function(data){
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
function(data){
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
  	function(data){
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
  		else {
  	      button.text("Try again later, something broke").attr('disabled', 'disabled');
  	      setTimeout(function(){ button.removeAttr('disabled') }, 2000);
  	    }
          });

  });

  $(".poll_content").on("click", ".back_vote_button", function(){
  	var poll_id = $(this).data('poll-id');
  	$('.poll_content').load('/includes/ajax/poll_options.php', {'poll_id':poll_id});
  });

  $('.votebutton').click(function() {
  	var button = $(this);
  	var category_id = $(this).data('category-id');
  	var game_id = $(this).data('game-id');
  	$.post('/includes/goty_vote.php', {'category_id':category_id, 'game_id':game_id},
  	function(data){
  	    if (data.result == 1){ //data needs to be valid json:  {result: true}
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
  		else {
  	      button.text("Try again later, something broke").attr('disabled', 'disabled');
  	      setTimeout(function(){ button.removeAttr('disabled') }, 2000);
  	    }
          });

  });

  $(".uploads").on("click", ".add_button", function(){
	var text = $(this).data('bbcode');
	$('#editor_content').val($('#editor_content').val() + text);
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

  $(document).on('click', ".gallery_item", function() {
    var filename = $(this).data('filename');
    var id = $(this).data('id');
    $('#preview2').html('<img src="/uploads/tagline_gallery/' + filename + '" alt="image" />');
    $.fancybox.close();
    $.post('/includes/ajax/gallery_tagline_sessions.php', { 'id':id });
  });

  $('#preview_text_button').click(function()
  {
    var text = $('#editor_content').val();
    $('.pm_text_preview').load('/includes/ajax/call_bbcode.php', {'text':text});
    $('.preview_pm').show();
    $('#preview').scrollMinimal();
    $(".preview_pm").highlight();
  });

});

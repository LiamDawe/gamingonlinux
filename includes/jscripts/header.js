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
//alert(current_url);
if (current_url == '/admin.php?module=articles&view=add')
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

	$('.contentwrap') .css({'margin-top': (($('#nav-small').height() + $('#nav-normal').height()) + 1 )+'px'});
	$(window).resize(function()
	{
        	$('.contentwrap') .css({'margin-top': (($('#nav-small').height() + $('#nav-normal').height()) + 1 )+'px'});
	});
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

    $(".likebutton").click(function(){
    //Get our comment
    var comment = $(this).parents('.comment')[0];
    //Get the comment ID
    var sid = comment.id.slice(1);
    //Send of a like (needs a like/dislike check)
      var $that = $(this);
      $.post('/includes/like.php', {
       sid: sid,
       sta: $that.find("span").text().toLowerCase()
      }, function (returndata){
        if(returndata === "liked")
        {
          var likeobj = $("#"+sid+" div.likes");
          var numlikes = likeobj.html().replace(" Likes","");
          numlikes = parseInt(numlikes) + 1;
          likeobj.html(numlikes + " Likes");
          var button = $(comment).find(".likebutton span");
          button.text("Unlike").removeClass("like").addClass("unlike");
      }
      else if(returndata === "unliked")
      {
          var likeobj = $("#"+sid+" div.likes");
          var numlikes = likeobj.html().replace(" Likes","");
          numlikes = parseInt(numlikes) - 1;
          likeobj.html(numlikes + " Likes");
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

  $(".poll_content").on("click", ".results_button", function(){
  	var poll_id = $(this).data('poll-id');
  	$('.poll_content').load('/includes/ajax/poll_results.php', {'poll_id':poll_id});
  });

  $(".poll_content").on("click", ".poll_button", function(){
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
	$('#editor_content2').val($('#editor_content2').val() + text);
  });
});

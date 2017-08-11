function update_notifications()
{	
	$.ajax({
		url: "/includes/ajax/update_notifications.php",  
		success: function(data) 
		{
			// give them a badge in the page title
			document.title = '(' + data.title_total + ') ' + current_page_title;
			
			// replace the dropdown badge
			$('#notifications_total').html(data.dropdown_indicator);
			
			// adjust the badge type
			if ($('#alert_box').hasClass( "alerts-box-new" ) && data.title_total == 0)
			{
				$('#alert_box').toggleClass('alerts-box-new alerts-box-normal');
			}
			
			if ($('#alert_box').hasClass( "alerts-box-normal" ) && data.title_total > 0)
			{
				$('#alert_box').toggleClass('alerts-box-normal alerts-box-new');
			}
			
			// update admin counter
			$('#admin_notifications').replaceWith(data.admin_badge);
			
			// update normal notifications counter
			$('#normal_notifications').replaceWith(data.normal_notifications);			
			
			// update pm counter
			$('#pm_counter').replaceWith(data.pms_badge);
		}
	});
}

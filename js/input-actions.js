/*
*  Input Actions
*
*  @description: javascript for user field functionality		
*  @author: Sam De Francesco
*  @since: 3.1.4
*/

(function($){
	
	/*
	*  Field: User
	*
	*  @description: 
	*  @since: 3.5.0
	*  @created: 03/03/2013
    */
	
	// add sortable
	$(document).live('acf/setup_fields', function(e, postbox){
		
		$(postbox).find('.acf_user').each(function(){
			
			// is clone field?
			if( acf.is_clone_field($(this).children('input[type="hidden"]')) )
			{
				return;
			}
			
			
			$(this).find('.user_right .user_list').sortable({
				axis: "y", // limit the dragging to up/down only
				items: '> li',
				forceHelperSize: true,
				forcePlaceholderSize: true,
				scroll: true
			});
			
			
			// load more
			$(this).find('.user_left .user_list').scrollTop(0).scroll( function(){
				
				// vars
				var div = $(this).closest('.acf_user');
				
				
				// validate
				if( div.hasClass('loading') )
				{
					return;
				}
				
				
				// Scrolled to bottom
				if( $(this).scrollTop() + $(this).innerHeight() >= $(this).get(0).scrollHeight )
				{
					var paged = parseInt( div.attr('data-paged') );
					
					div.attr('data-paged', (paged + 1) );
					
					acf.user_update_results( div );
				}

			});
			
			
			// ajax fetch values for left side
			acf.user_update_results( $(this) );
			
		});
		
	});
	
	
	// add from left to right
	$('.acf_user .user_left .user_list a').live('click', function(){
		
		// vars
		var id = $(this).attr('data-user_id'),
			title = $(this).html(),
			div = $(this).closest('.acf_user'),
			max = parseInt(div.attr('data-max')),
			right = div.find('.user_right .user_list');
		
		
		// max posts
		if( right.find('a').length >= max )
		{
			alert( acf.text.user_max_alert.replace('{max}', max) );
			return false;
		}
		
		
		// can be added?
		if( $(this).parent().hasClass('hide') )
		{
			return false;
		}
		
		
		// hide / show
		$(this).parent().addClass('hide');
		
		
		// create new li for right side
		var new_li = div.children('.tmpl-li').html()
			.replace( /\{user_id}/gi, id )
			.replace( /\{title}/gi, title );
			


		// add new li
		right.append( new_li );
		
		
		// validation
		div.closest('.field').removeClass('error');
		
		return false;
		
	});
	
	
	// remove from right to left
	$('.acf_user .user_right .user_list a').live('click', function(){
		
		// vars
		var id = $(this).attr('data-user_id'),
			div = $(this).closest('.acf_user'),
			left = div.find('.user_left .user_list');
		
		
		// hide
		$(this).parent().remove();
		
		
		// show
		left.find('a[data-user_id="' + id + '"]').parent('li').removeClass('hide');
		
		
		return false;
		
	});
	
	
	// search
	$('.acf_user input.user_search').live('keyup', function()
	{	
		// vars
		var val = $(this).val(),
			div = $(this).closest('.acf_user');
			
		
		// update data-s
	    div.attr('data-s', val);
	    
	    
	    // new search, reset paged
	    div.attr('data-paged', 1);
	    
	    
	    // ajax
	    clearTimeout( acf.user_timeout );
	    acf.user_timeout = setTimeout(function(){
	    	acf.user_update_results( div );
	    }, 250);
	    
	    return false;
	    
	})
	.live('focus', function(){
		$(this).siblings('label').hide();
	})
	.live('blur', function(){
		if($(this).val() == "")
		{
			$(this).siblings('label').show();
		}
	});
	
	
	// hide results
	acf.user_hide_results = function( div ){
		
		// vars
		var left = div.find('.user_left .user_list'),
			right = div.find('.user_right .user_list');
			
			
		// apply .hide to left li's
		left.find('a').each(function(){
			
			var id = $(this).attr('data-user_id');
			
			if( right.find('a[data-user_id="' + id + '"]').exists() )
			{
				$(this).parent().addClass('hide');
			}
			
		});
		
	}
	
	
	// update results
	acf.user_update_results = function( div ){
		
		
		// add loading class, stops scroll loading
		div.addClass('loading');
		
		
		// vars
		var s = div.attr('data-s'),
			paged = parseInt( div.attr('data-paged') ),
			roles = div.attr('data-roles'),
			lang = div.attr('data-lang'),
			left = div.find('.user_left .user_list'),
			right = div.find('.user_right .user_list');
		
		
		// get results
	    $.ajax({
			url: ajaxurl,
			type: 'post',
			dataType: 'html',
			data: { 
				'action' : 'acf_get_user_results', 
				's' : s,
				'paged' : paged,
				'roles' : roles,
				'lang' : lang,
				'field_name' : div.parent().attr('data-field_name'),
				'field_key' : div.parent().attr('data-field_key')
			},
			success: function( html ){
				
				div.removeClass('no-results').removeClass('loading');
				
				// new search?
				if( paged == 1 )
				{
					left.find('li:not(.load-more)').remove();
				}
				
				
				// no results?
				if( !html )
				{
					div.addClass('no-results');
					return;
				}
				
				
				// append new results
				left.find('.load-more').before( html );
				
				
				// less than 10 results?
				var ul = $('<ul>' + html + '</ul>');
				if( ul.find('li').length < 10 )
				{
					div.addClass('no-results');
				}
				
				
				// hide values
				acf.user_hide_results( div );
				
			}
		});
	};
	
})(jQuery);

jQuery(document).on('click', 'button[data-action="wcgo-book-delivery"]', function(e) {
	
	if(!jQuery(this).hasClass('is-active')) {
		
		jQuery(this).addClass('is-active');
		
		jQuery(this).siblings('.wcgo-book-action').addClass('is-active').show();
		
		// binds an event to all elements
		jQuery('*').bind('click', maybe_close_book_modal);
		
	}
	
	e.preventDefault();
	return false;
	
}).on('click', 'button[data-action="wcgo-filter"]', function(e) {
	
	var url = jQuery(this).attr('data-url');
	
	var parts = [];
	
	// order status
	if(jQuery('.wcgo-filter-order-status').val() != '')
		parts.push('order-status='+jQuery('.wcgo-filter-order-status').val());
	
	// order date
	if(jQuery('.wcgo-filter-order-date').val() != '')
		parts.push('order-date='+jQuery('.wcgo-filter-order-date').val());
	
	// delivery date
	if(jQuery('.wcgo-filter-delivery-date').val() != '')
		parts.push('delivery-date='+jQuery('.wcgo-filter-delivery-date').val());
	
	// delivery status
	if(jQuery('.wcgo-filter-delivery-status').val() != '')
		parts.push('delivery-status='+jQuery('.wcgo-filter-delivery-status').val());
		
	url += '&'+parts.join('&');
	
	window.location.href = url;
	
	e.preventDefault();
	return false;
	
}).on('click', '.wcgo-book-action a', function(e) {
	
	jQuery('.wcgo-book-action.is-active').siblings('.is-active').removeClass('is-active');
	jQuery('.wcgo-book-action.is-active').removeClass('is-active').hide();
		
	jQuery('*').unbind('click', maybe_close_book_modal);
	
	e.preventDefault();
	return false;
	
}).on('click', '.wcgo-book-action button', function(e) {
	
	// Lets build our our date
	var button = jQuery(this).parent().siblings('button[data-action="wcgo-book-delivery"]'),
		dateSel = button.siblings('.wcgo-book-action').find('select.wcgo-delivery-date'),
		hourSel = button.siblings('.wcgo-book-action').find('select.wcgo-delivery-hour'),
		minuteSel = button.siblings('.wcgo-book-action').find('select.wcgo-delivery-minute'),
		notesCont = button.siblings('.wcgo-book-action').find('textarea.wcgo-delivery-notes'),
		mainCont = jQuery(this).parent();
		
	var confirm = window.confirm("Are you sure you want to book this delivery to be delivered by\r\n"+dateSel.find('option:selected').text()+" at "+hourSel.val()+":"+minuteSel.val()+"?");
	
	if(!confirm) {
		
		e.preventDefault();
		return false;
		
	}
	
	// Displays our loading sign
	mainCont.addClass('is-loading');
	mainCont.append('<span class="spinner"></span>');
	
	// Does our ajax query
	jQuery.ajax({
		
		url: 			wcgo.ajaxurl,
		type: 			'post',
		dataType: 		'json',
		data: {
			
			action: 	'wcgo_book_order_delivery',
			nonce: 		button.parents('tr').attr('data-nonce'),
			order_id: 	button.parents('tr').attr('data-order'),
			day: 		dateSel.val(),
			hour: 		hourSel.val(),
			minute:		minuteSel.val(),
			notes: 		notesCont.val()
			
		},
		success: function(r) {
			
			// Error
			if(!r.success) {
				
				button.show();
				button.siblings('.wcgo-book-action').show();
				button.siblings('.spinner').remove();
				
				alert(r.data.message);
				
			} else {
				
				// Success
				// Lets reload this row
				button.parents('tr').addClass('loading');
				
				// Lets get the new data for this row
				jQuery.ajax({
					
					url:			wcgo.ajaxurl,
					type: 			'post',
					dataType: 		'json',
					data: {
						
						action: 	'wcgo_get_order_row',
						nonce: 		button.parents('tr').attr('data-nonce'),
						order_id: 	button.parents('tr').attr('data-order'),
						
					},
					success: function(r) {
						
						if(!r.success) {
							
							window.location.reload();
							
						} else {
							
							button.parents('tr').replaceWith(r.data.markup);
							
						}
						
					}
					
				});
				
			}
			
		}
		
	});
	
}).on('mouseenter', '*[data-wcgo-tooltip]', function(e) {
	
	console.log('a');
	
	var el = jQuery(this),
		offset = jQuery(this).offset(),
		elW = el.outerWidth(),
		text = el.attr('data-wcgo-tooltip');
		
	// Appends our tooltip to the body
	jQuery('body').append('<div class="wcgo-tooltip" style="top: '+offset.top+'px; left: '+((offset.left+(elW/2))-75)+'px;"><span><span>'+text+'</span></span></div>');
	
}).on('mouseleave', '*[data-wcgo-tooltip]', function(e) {
	
	jQuery('.wcgo-tooltip').remove();
	
});

function maybe_close_book_modal(e) {
	
	// If its nout our window close it
	if(jQuery(e.target).parents('.wcgo-book-action').length == 0 && !jQuery(e.target).is('.wcgo-book-action')) {
		
		jQuery('.wcgo-book-action.is-active').siblings('.is-active').removeClass('is-active');
		jQuery('.wcgo-book-action.is-active').removeClass('is-active').hide();
		
		jQuery('*').unbind('click', maybe_close_book_modal);
		
	}
	
}
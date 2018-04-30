var blur_wipe = true;

jQuery(function() {
	
	// Sets our interval to wait until our places is loaded
	var places_interval = setInterval(function() {
		
		// Checks if we have our library ready
		if(typeof google.maps == 'undefined')
			return;
			
		// Clears our interval
		clearInterval(places_interval);
		
		// Applies the autocomplete to each one of our address search
		jQuery('#billing_address_search, #shipping_address_search').each(function() {
			
			// Our input
			var input_cont = jQuery(this),
				field_type = (input_cont.attr('id').indexOf('billing') !== -1) ? 'billing' : 'shipping';
		
			// Ensures our fields are empty on load
			jQuery('#'+field_type+'_address_1').val('').trigger('change');
			jQuery('#'+field_type+'_address_2').val('').trigger('change');
			jQuery('#'+field_type+'_city').val('').trigger('change');
			jQuery('#'+field_type+'_state').val('').trigger('change');
			jQuery('#'+field_type+'_postcode').val('').trigger('change');
			jQuery('#'+field_type+'_country').val('').trigger('change');
			
			// Settings
			var setts = {
				
				types: 	['address']
				
			};
			
			// If we have a number of countries
			if(wcgo.countries.length > 0) {
				
				setts['componentRestrictions'] = {
					
					country: wcgo.countries
					
				};
				
			}
			
			// Adds our places autocomplete
			var gac = new google.maps.places.Autocomplete(jQuery(this).get(0), setts);
			
			// Adds our listener to when the user has changed
			gac.addListener('place_changed', function() {
				
				blur_wipe = true;
				
				// Gets the place
				var place = gac.getPlace(),
					street = '',
					number = '',
					address = '',
					suburb = '',
					postcode = '',
					state = '',
					country = '';
				
				// Loops our places compiknenets and finds the right elements
				jQuery.each(place.address_components, function(i, el) {
					
					// If our street number
					if(el.types.indexOf('street_number') !== -1)
						number = el.long_name;
						
					// If our street name
					if(el.types.indexOf('route') !== -1)
						street = el.long_name;
						
					// If our suburb
					if(el.types.indexOf('locality') !== -1)
						suburb = el.long_name;
						
					// If our state
					if(el.types.indexOf('administrative_area_level_1') !== -1)
						state = el.short_name;
						
					// If our postcode
					if(el.types.indexOf('postal_code') !== -1)
						postcode = el.long_name;
						
					// If our country
					if(el.types.indexOf('country') !== -1)
						country = el.short_name;
					
				});
				
				// Our address
				if(number.length > 0)
					address += number+' ';
				if(street.length > 0)
					address += street+' ';
				
				// Populates our fields
				jQuery('#'+field_type+'_address_1').addClass('populated').val(address).parents('p.form-row').addClass('populated');
				jQuery('#'+field_type+'_address_2').parents('p.form-row').addClass('populated');
				jQuery('#'+field_type+'_city').addClass('populated').val(suburb).parents('p.form-row').addClass('populated');
				jQuery('#'+field_type+'_state').addClass('populated').val(state).parents('p.form-row').addClass('populated');
				jQuery('#'+field_type+'_postcode').addClass('populated').val(postcode).parents('p.form-row').addClass('populated');
				jQuery('#'+field_type+'_country').addClass('populated').val(country).parents('p.form-row').addClass('populated');
				
				// Triggers our update checkout
				setTimeout(function() {
					
					jQuery(document.body).trigger('update_checkout');
					
				});
				
				setTimeout(function() {
					
					blur_wipe = true
					
				}, 500);
					
				
			});
			
		});
		
	}, 200);
	
});

jQuery(document).on('keyup', '#billing_address_search, #shipping_address_search', function() {
	
	// Resets our address fields
	var field_type = (jQuery(this).attr('id').indexOf('billing') !== -1) ? 'billing' : 'shipping';
	
	jQuery('#'+field_type+'_address_1').removeClass('populated').val('').parents('p.form-row').removeClass('populated');
	jQuery('#'+field_type+'_address_2').parents('p.form-row').removeClass('populated');
	jQuery('#'+field_type+'_city').removeClass('populated').val('').parents('p.form-row').removeClass('populated');
	jQuery('#'+field_type+'_state').removeClass('populated').val('').parents('p.form-row').removeClass('populated');
	jQuery('#'+field_type+'_postcode').removeClass('populated').val('').parents('p.form-row').removeClass('populated');
	
	console.log('keyup');
	
})
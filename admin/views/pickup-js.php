<?php
/**
* Displays some javascript for the pickup section
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	FIVE/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE

?>

<?php
	
	// Ensures we have our client side api key
	if(get_option('wcgo_gmaps_api_key_client', false) && get_option('wcgo_gmaps_api_key_client', false) != '') :
	
?>
	
	<script type="text/javascript">
		
		var the_map,
			the_marker;
			
		function initMap() {
			
			jq_int = setInterval(function() {
				
				if(!window.jQuery)
					return;
					
				clearInterval(jq_int);
			
				var coords = jQuery('#wcgo-map-cont').attr('data-coords');
				
				if(coords.indexOf('|') === -1) {
					
					var lat = -37.816446,
						lng = 144.957390;
					
				} else {
					
					var parts = coords.split('|'),
						lat = parseFloat(parts[0]),
						lng = parseFloat(parts[1]);
					
				}
				
				the_map = new google.maps.Map(document.getElementById('wcgo-map-cont'), {
					
					zoom: 15,
					center: {lat: lat, lng: lng},
					disableDefaultUI: true,
					draggable: false,
					scrollwheel: false
					
				});
				
				if(coords.indexOf('|') !== -1) {
					
					// Load marker
					the_marker = new google.maps.Marker({
						
						position: {lat: lat, lng: lng},
						map: the_map
						
					});
					
				}
				
			});
			
		}
		
		jQuery(function() {
			
			// Ensures our google places library has loaded
			placesInterval = setInterval(function() {
				
				// Checks if we have our library ready
				if(typeof google.maps == 'undefined')
					return;
				
				// Clears our interval
				clearInterval(placesInterval);
				
				// Adds our atuocomplete
				var gac = new google.maps.places.Autocomplete(jQuery('#wcgo_pickup_address').get(0), {
					
					types: ['address'],
					componentRestrictions: {
						
						country: 'AU'
						
					}
					
				});
				
				// Adds our listener to when the user has changed
				gac.addListener('place_changed', function() {
					
					// Gets the place
					var place = gac.getPlace();
					
					var street = '',
						number = '',
						address = '',
						suburb = '',
						postcode = '',
						state = '';
					
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
						
					});
					
					if(typeof the_marker == 'object') {
						
						the_marker.setMap(null);
						
					}
					
					// Does our marker
					the_marker = new google.maps.Marker({
						
						position: place.geometry.location,
						map: the_map
						
					});
					
					// centers map
					the_map.setCenter(place.geometry.location);
					
					// Populates the hidden field
					jQuery('#wcgo_pickup_location').val(place.geometry.location.lat()+'|'+place.geometry.location.lng());
					
					// Our address
					if(number.length > 0)
						address += number+' ';
					if(street.length > 0)
						address += street+' ';
						
					// Populates our hidden fields
					if(jQuery('#wcgo_pickup_address_address').length == 0)
						jQuery('#wcgo_pickup_address').after('<input type="hidden" name="wcgo_pickup_address_address" id="wcgo_pickup_address_address" value="" />');
						
					if(jQuery('#wcgo_pickup_address_suburb').length == 0)
						jQuery('#wcgo_pickup_address').after('<input type="hidden" name="wcgo_pickup_address_suburb" id="wcgo_pickup_address_suburb" value="" />');
						
					if(jQuery('#wcgo_pickup_address_postcode').length == 0)
						jQuery('#wcgo_pickup_address').after('<input type="hidden" name="wcgo_pickup_address_postcode" id="wcgo_pickup_address_postcode" value="" />');
						
					if(jQuery('#wcgo_pickup_address_state').length == 0)
						jQuery('#wcgo_pickup_address').after('<input type="hidden" name="wcgo_pickup_address_state" id="wcgo_pickup_address_state" value="" />');
						
					jQuery('#wcgo_pickup_address_address').val(address);
					jQuery('#wcgo_pickup_address_suburb').val(suburb);
					jQuery('#wcgo_pickup_address_postcode').val(postcode);
					jQuery('#wcgo_pickup_address_state').val(state);
					
				});
				
			}, 200);
			
		});
		
		function gm_authFailure() { alert('Seems like your google maps api key - browser side is incorrect. Please double check your key.') };
		
	</script>

	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('wcgo_gmaps_api_key_client') ?>&libraries=places&callback=initMap"></script>
	
<?php else : ?>

	<script type="text/javascript">
		
		jQuery(function() {
			
			jQuery('#wcgo_pickup_address').parents('td').html('<p><?php _e('Please provide your google maps api key - browser side, under general settings.', 'five'); ?></p>');
			
		});
		
	</script>

<?php endif; ?>
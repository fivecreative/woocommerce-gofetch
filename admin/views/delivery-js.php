<?php
/**
* Displays some javascript for the pickup section
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE

?>

<script type="text/javascript">
	
	jQuery(function() {
		
		// No delivery choice
		wcgo_delivery_fields();
		
	});
	
	jQuery(document).on('change', '#wcgo_same_day_delivery, #wcgo_next_day_delivery, #wcgo_enable_delivery_choice, #wcgo_asap_delivery, #wcgo_autobook', function() {
		
		wcgo_delivery_fields();
		
	});
	
	function wcgo_delivery_fields() {
		
		// Main delivery choice deisabled
		if(!jQuery('#wcgo_enable_delivery_choice').is(':checked')) {
			
			jQuery('#wcgo_same_day_delivery_cutoff').parents('tr').hide();
			jQuery('#wcgo_next_day_delivery_cutoff').parents('tr').hide();
			jQuery('#wcgo_asap_delivery_cutoff').parents('tr').hide();
			jQuery('#wcgo_delivery_day_monday').parents('tr').hide();
			jQuery('#wcgo_same_day_delivery_surcharge').parents('tr').hide();
			jQuery('#wcgo_next_day_delivery_surcharge').parents('tr').hide();
			jQuery('#wcgo_asap_delivery_surcharge').parents('tr').hide();
			jQuery('#wcgo_autobook').parents('tr').hide();
			jQuery('#wcgo_autobook_time').parents('tr').hide();
			
		} else {
			
			jQuery('#wcgo_delivery_day_monday').parents('tr').show();
			
			// Same day delivery disabled
			if(!jQuery('#wcgo_same_day_delivery').is(':checked')) {
				
				jQuery('#wcgo_same_day_delivery_cutoff').parents('tr').hide();
				jQuery('#wcgo_same_day_delivery_surcharge').parents('tr').hide();
				
			} else {
				
				jQuery('#wcgo_same_day_delivery_cutoff').parents('tr').show();
				jQuery('#wcgo_same_day_delivery_surcharge').parents('tr').show();
				
			}
			
			// Next day delivery disabled
			if(!jQuery('#wcgo_next_day_delivery').is(':checked')) {
				
				jQuery('#wcgo_next_day_delivery_cutoff').parents('tr').hide();
				jQuery('#wcgo_next_day_delivery_surcharge').parents('tr').hide();
				
			} else {
				
				jQuery('#wcgo_next_day_delivery_cutoff').parents('tr').show();
				jQuery('#wcgo_next_day_delivery_surcharge').parents('tr').show();
				
			}
			
			// ASAP delivery disabled
			if(!jQuery('#wcgo_asap_delivery').is(':checked')) {
				
				jQuery('#wcgo_asap_delivery_cutoff').parents('tr').hide();
				jQuery('#wcgo_asap_delivery_surcharge').parents('tr').hide();
				
			} else {
				
				jQuery('#wcgo_asap_delivery_cutoff').parents('tr').show();
				jQuery('#wcgo_asap_delivery_surcharge').parents('tr').show();
				
			}
			
			// Autobook disabled
			jQuery('#wcgo_autobook').parents('tr').show();
			if(!jQuery('#wcgo_autobook').is(':checked')) {
				
				jQuery('#wcgo_autobook_time').parents('tr').hide();
				
			} else {
				
				jQuery('#wcgo_autobook_time').parents('tr').show();
				
			}
				
			
		}
		
	}
	
</script>
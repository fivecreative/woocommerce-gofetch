<?php
/**
* Displays our delivery date for the gofetch deliveyr
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	WCGO/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE

?>

<tr class="shipping shipping-date">
	
	<th><?php _e('Delivery Date', 'five'); ?></th>
	<!-- th -->
	
	<td>
		
		<select name="wcgo-delivery-date">
			
			<?php
				
				// Loops our available delivery dates
				foreach(WCGO()->get_available_delivery_dates() as $value => $label) :
				
			?>
			
				<option value="<?php echo $value ?>" <?php selected(WC()->session->get('wcgo-delivery-date'), $value, true) ?>><?php echo strip_tags($label['title']) ?></option>
			
			<?php endforeach; ?>
			
		</select>
		<!-- select -->
		
		<script type="text/javascript">
			
			jQuery(document).on('change', 'select[name="wcgo-delivery-date"]', function() {
				
				jQuery(document.body).trigger('update_checkout');
				
			});
			
		</script>
		
	</td>
	<!-- td -->
	
</tr>
<!-- tr -->
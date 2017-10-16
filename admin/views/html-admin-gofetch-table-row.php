<?php
/**
* Displays the row for an order on gofetch order table
*
* @version 	1.0.3.3
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	$order = wc_get_order($order_id);

?>

<tr data-order="<?php echo $order_id ?>" data-nonce="<?php echo wp_create_nonce('wcgo_order_'.$order_id) ?>">
					
	<td class="col-order">
		
		<a target="_blank" href="<?php echo get_edit_post_link($order_id, 'shop-order') ?>"><strong><?php _e('Order #', 'five'); ?><?php echo $order_id ?></strong></a>
		
	</td>
	<!-- td -->
	
	<td class="col-order-status">
		
		<?php echo ucwords($order->get_status()) ?>
		
	</td>
	<!-- td -->
	
	<td class="col-order-date">
		
		<?php echo $order->get_date_created()->format('j F, Y'); ?>
		
	</td>
	<!-- td -->
	
	<td class="col-gofetch-delivery-date">
		
		<?php echo wcgo_get_order_delivery_date($order_id); ?>
		
	</td>
	<!-- td -->
	
	<td class="col-gofetch-job">
		
		<?php
			
			// Status
			$status = wcgo_get_order_delivery_status_formatted($order_id);
			
		?>
		
		<span data-wcgo-tooltip="<?php echo esc_html(wcgo_get_tooltip_info($status)); ?>"><?php echo $status ?></span>
		
	</td>
	<!-- td -->
	
	<td class="col-gofetch-action">
		
		<div class="wcgo-book-action">
			
			<select class="wcgo-delivery-date">
				
				<?php
					
					// Gets the selected delivery date for this order if set
					$selected_delivery_date = wcgo_get_order_chosen_delivery_date($order_id);
					
					foreach(wcgo_get_booking_delivery_dates() as $value => $label) :
					
				?>
				
					<option value="<?php echo $value ?>" <?php if($selected_delivery_date) { selected($selected_delivery_date->format('Y-m-d'), $value, true); } ?>><?php echo $label; ?></option>
				
				<?php endforeach; ?>
				
			</select>
			<!-- wcgo-delivery-date -->
			
			<?php _e('at', 'five'); ?>
			
			<select class="wcgo-delivery-hour">
				
				<?php
					
					$selected = '11';
					
					// Deals with our hours
					for($i = 6; $i <= 22; $i++) :
					
						// Hour
						$hour = (strlen($i) == 1) ? '0'.$i : $i;
						
				?>
				
					<option value="<?php echo $hour ?>" <?php selected($selected, $hour, true) ?>><?php echo $hour; ?></option>
				
				<?php endfor; ?>
				
			</select>
			<!-- wcgo-delive-hour -->
			
			<select class="wcgo-delivery-minute">
				
				<?php
					
					// Deals with our minutes
					for($i = 0; $i < 12; $i++) :
					
						$minute = $i * 5;
						
						if(strlen($minute) == 1)
							$minute = '0'.$minute;
							
				?>
				
					<option value="<?php echo $minute ?>" <?php selected('00', $minute, true) ?>><?php echo $minute; ?></option>
				
				<?php endfor; ?>
				
			</select>
			<!-- wcgo-delive-hour -->
			
			<textarea class="wcgo-delivery-notes" cols="" rows="3" placeholder="<?php _e('Type here notes for your GoFetch delivery.', 'five'); ?>"><?php echo esc_html($order->get_customer_note()); ?></textarea>
			<!-- textarea -->
			
			<button type="button" class="button button-primary" style="float: right;"><?php _e('Book Delivery', 'five'); ?></button>
			<!-- button -->
			
			<a href="#" style="float: left; padding: 6px 0 0;"><span style="position: relative; top: -1px;" class="dashicons dashicons-no-alt"></span> Close</a>
			
			<div style="clear: both;"></div>
			
		</div>
		<!-- wcgo-fetch-book-action -->
		
		<button type="button" class="button" data-action="wcgo-book-delivery"><?php _e('Book GoFetch Delivery', 'five'); ?></button>
		
	</td>
	<!-- td -->
	
</tr>
<!-- tr -->
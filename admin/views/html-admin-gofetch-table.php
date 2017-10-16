<?php
/**
* Displays our main gofetch table with all orders with gofetch delivery methods etc
*
* @version 	1.0.3.3
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	$current_page = !empty($_GET['paged']) ? $_GET['paged'] : 1;
	
	// Fetches our order ids with go fetch
	$order_ids = WCGO()->get_gofetch_order_ids();
	
	// Fetches all orders 
	$args = array(
		
		'post_type' => 'shop_order',
		'posts_per_page' => 30,
		'post_status' => array_keys(wc_get_order_statuses()),
		'post__in' => $order_ids,
		'fields' => 'ids',
		'paged' => $current_page,
		
	);
	
	// Order status
	if(!empty($_GET['order-status'])) {
		
		$args['post_status'] = $_GET['order-status'];
		
	}
		
	$today = new DateTime('now', new DateTimezone(WCGO()->get_timezone()));
	
	// Order date
	if(!empty($_GET['order-date'])) {
		
		// Today
		if($_GET['order-date'] == 'today') {
			
			$args['date_query'] = array(
				
				array(
					
					'after' => $today->format('Y-m-d'),
					'inclusive' => true,
					
				)
				
			);
			
		} elseif($_GET['order-date'] == 'week') {
			
			// This Week
			if($today->format('N') < 7)
				$today->modify('last Sunday');
				
			$args['date_query'] = array(
				
				array(
					
					'after' => $today->format('Y-m-d'),
					'inclusive' => true,
					
				)
				
			);
			
		} elseif($_GET['order-date'] == 'month') {
			
			$today->modify('first day of this month');
			
			$args['date_query'] = array(
				
				array(
					
					'after' => $today->format('Y-m-d'),
					'inclusive' => true,
					
				)
				
			);
			
		} else {
			
			$beginning_of_month = new DateTime($_GET['order-date'].'-01 00:00:00', new DateTimezone(WCGO()->get_timezone()));
			$end_of_month = clone $beginning_of_month;
			$end_of_month->modify('last day of this month');
			
			$args['date_query'] = array(
				
				array(
					
					'after' => $beginning_of_month->format('Y-m-d'),
					'before' => $end_of_month->format('Y-m-d'),
					'inclusive' => true,
					
				)
				
			);
			
		}
		
	}
	
	// Delivery date
	if(!empty($_GET['delivery-date'])) {
		
		// Today
		if($_GET['delivery-date'] == 'today') {
			
			$args['meta_query'][] = array(
				
				'key' => 'wcgo_delivery_date',
				'value' => $today->format('Ymd'),
				
			);
			
		}
		// This week
		elseif($_GET['delivery-date'] == 'week') {
			
			if($today->format('N') < 7)
				$today->modify('last Sunday');
			
			$end_of_week = clone $today;
			$end_of_week->modify('+6 days');
			
			$args['meta_query'][] = array(
				
				'key' => 'wcgo_delivery_date',
				'value' => array($today->format('Ymd'), $end_of_week->format('Ymd')),
				'compare' => 'BETWEEN',
				'type' => 'NUMERIC',
				
			);
			
		}
		// Month
		elseif($_GET['delivery-date'] == 'month') {
			
			$today->modify('first day of this month');
			$end_of_month = clone $today;
			$end_of_month->modify('last day of this month');
			
			$args['meta_query'][] = array(
				
				'key' => 'wcgo_delivery_date',
				'value' => array($today->format('Ymd'), $end_of_month->format('Ymd')),
				'compare' => 'BETWEEN',
				'type' => 'NUMERIC',
				
			);
			
		} else {
			
			$beginning_of_month = new DateTime($_GET['delivery-date'].'-01 00:00:00', new DateTimezone(WCGO()->get_timezone()));
			$end_of_month = clone $beginning_of_month;
			$end_of_month->modify('last day of this month');
			
			$args['meta_query'][] = array(
				
				'key' => 'wcgo_delivery_date',
				'value' => array($beginning_of_month->format('Ymd'), $end_of_month->format('Ymd')),
				'compare' => 'BETWEEN',
				'type' => 'NUMERIC',
				
			);
			
		}
		
	}
	
	// Delivery status
	if(!empty($_GET['delivery-status'])) {
		
		$args['meta_query'][] = array(
			
			'key' => 'wcgo_delivery_status',
			'value' => $_GET['delivery-status'],
			
		);
		
	}

	
	$q = new WP_Query($args);

?>

<h2><?php _e('GoFetch Deliveries Report', 'five'); ?></h2>

	<div class="wcgo-filters">
		
		<?php
			
			$selected_order_status = !empty($_GET['order-status']) ? $_GET['order-status'] : '';
			
		?>
		
		<select class="wcgo-filter-order-status">
			
			<option value="">&ndash; <?php _e('Order Status', 'five'); ?></option>
			
			<option value="wc-processing" <?php selected($selected_order_status, 'wc-processing', true); ?>><?php _e('Processing', 'five'); ?></option>
			<!-- option -->
			
			<option value="wc-on-hold" <?php selected($selected_order_status, 'wc-on-hold', true); ?>><?php _e('On Hold', 'five'); ?></option>
			<!-- option -->
			
			<option value="wc-completed" <?php selected($selected_order_status, 'wc-completed', true); ?>><?php _e('Completed', 'five'); ?></option>
			<!-- option -->
			
			<option value="wc-cancelled" <?php selected($selected_order_status, 'wc-cancelled', true); ?>><?php _e('Cancelled', 'five'); ?></option>
			<!-- option -->
			
			<option value="wc-refunded" <?php selected($selected_order_status, 'wc-refunded', true); ?>><?php _e('Refunded', 'five'); ?></option>
			<!-- option -->
			
			<option value="wc-pending" <?php selected($selected_order_status, 'wc-pending', true); ?>><?php _e('Pending', 'five'); ?></option>
			<!-- option -->
			
		</select>
		<!-- select -->
		
		<?php
			
			$selected_order_date = !empty($_GET['order-date']) ? $_GET['order-date'] : '';
			
		?>
		
		<select class="wcgo-filter-order-date">
			
			<option value="">&ndash; <?php _e('Order Date', 'five'); ?></option>
			<!-- option -->
			
			<option value="today" <?php selected($selected_order_date, 'today', true); ?>><?php _e('Today', 'five'); ?></option>
			<!-- option -->
			
			<option value="week" <?php selected($selected_order_date, 'week', true); ?>><?php _e('This Week', 'five'); ?></option>
			<!-- option -->
			
			<option value="month" <?php selected($selected_order_date, 'month', true); ?>><?php _e('This Month', 'five'); ?></option>
			<!-- option -->
			
			<?php
				
				foreach(wcgo_get_available_order_date_filters() as $value => $label) :
				
			?>
			
				<option value="<?php echo $value ?>" <?php selected($selected_order_date, $value, true) ?>><?php echo $label ?></option>
			
			<?php endforeach; ?>
			
		</select>
		<!-- select -->
		
		<?php
			
			$selected_delivery_date = !empty($_GET['delivery-date']) ? $_GET['delivery-date'] : '';
			
		?>
		
		<select class="wcgo-filter-delivery-date">
			
			<option value="">&ndash; <?php _e('Delivery Date', 'five'); ?></option>
			<!-- option -->
			
			<option value="today" <?php selected($selected_delivery_date, 'today', true) ?>><?php _e('Today', 'five'); ?></option>
			<!-- option -->
			
			<option value="week" <?php selected($selected_delivery_date, 'week', true) ?>><?php _e('This Week', 'five'); ?></option>
			<!-- option -->
			
			<option value="month" <?php selected($selected_delivery_date, 'month', true) ?>><?php _e('This Month', 'five'); ?></option>
			<!-- option -->
			
			<?php
				
				foreach(wcgo_get_available_order_date_filters() as $value => $label) :
				
			?>
			
				<option value="<?php echo $value ?>" <?php selected($selected_delivery_date, $value, true) ?>><?php echo $label ?></option>
			
			<?php endforeach; ?>
			
		</select>
		<!-- select -->
		
		<?php
			
			$selected_delivery_status = !empty($_GET['delivery-status']) ? $_GET['delivery-status'] : '';
			
		?>
		
		<select class="wcgo-filter-delivery-status">
			
			<option value="">&ndash; <?php _e('Delivery Status', 'five'); ?></option>
			<!-- option -->
			
			<option value="unbooked" <?php selected($selected_delivery_status, 'unbooked', true) ?>><?php _e('Not Yet Booked', 'five'); ?></option>
			<!-- option -->
			
			<option value="picking_up" <?php selected($selected_delivery_status, 'picking_up', true) ?>><?php _e('Picking Up', 'five'); ?></option>
			<!-- option -->
			
			<option value="delivering" <?php selected($selected_delivery_status, 'delivering', true) ?>><?php _e('Delivering', 'five'); ?></option>
			<!-- option -->
			
			<option value="delivered" <?php selected($selected_delivery_status, 'delivered', true) ?>><?php _e('Delivered', 'five'); ?></option>
			<!-- option -->
			
			<option value="confirmed" <?php selected($selected_delivery_status, 'Confirmed', true) ?>><?php _e('Confirmed', 'five'); ?></option>
			<!-- option -->
			
			<option value="completed" <?php selected($selected_delivery_status, 'completed', true) ?>><?php _e('Completed', 'five'); ?></option>
			<!-- option -->
			
			<option value="cancelled" <?php selected($selected_delivery_status, 'cancelled', true) ?>><?php _e('Cancelled', 'five'); ?></option>
			<!-- option -->
			
			<option value="issued" <?php selected($selected_delivery_status, 'issued', true) ?>><?php _e('Issued', 'five'); ?></option>
			<!-- option -->
			
			<option value="pending" <?php selected($selected_delivery_status, 'pending', true) ?>><?php _e('Pending', 'five'); ?></option>
			<!-- option -->
			
			<option value="ended" <?php selected($selected_delivery_status, 'ended', true) ?>><?php _e('Ended', 'five'); ?></option>
			<!-- option -->
			
			<option value="abandoned" <?php selected($selected_delivery_status, 'abandoned', true) ?>><?php _e('Abandoned', 'five'); ?></option>
			<!-- option -->
			
		</select>
		<!-- select -->
		
		<button type="button" data-action="wcgo-filter" class="button" data-url="<?php echo add_query_arg(array('page' => 'wc-settings', 'tab' => 'wcgo', 'section' => ''), admin_url('admin.php')) ?>"><?php _e('Filter', 'five'); ?></button>
		
	</div>
	<!-- wcgo-fetch-filters -->

<?php
	
	// No orders found
	if(!$q->have_posts()) :
	
?>

	<p><?php _e('No orders found.', 'five'); ?></p>

<?php else : ?>

	<table id="wcgo-fetch-table" cellpadding="0" cellspacing="0">
		
		<thead>
			
			<tr>
				
				<th class="col-order"><?php _e('Order #', 'five'); ?></th>
				<!-- th -->
				
				<th class="col-order-status"><?php _e('Order Status', 'five'); ?></th>
				<!-- th -->
				
				<th class="col-order-date"><?php _e('Order Date', 'five'); ?></th>
				<!-- th -->
				
				<th class="col-gofetch-delivery-date"><?php _e('Delivery Date', 'five'); ?></th>
				<!-- th -->
				
				<th class="col-gofetch-job"><?php _e('Delivery Status', 'five'); ?></th>
				<!-- th -->
				
				<th class="col-gofetch-action"></th>
				<!-- th -->
				
			</tr>
			<!-- tr -->
			
		</thead>
		<!-- thead -->
		
		<tbody>
			
			<?php
				
				// Loops our orders
				foreach($q->posts as $order_id) {
				
					echo wcgo_admin_get_order_row($order_id);
					
				}
				
			?>
			
		</tbody>
		<!-- tbody -->
		
	</table>
	<!-- wcgo-fetch-table -->
	
	<?php
		
		if($current_page < $q->max_num_pages) :
		
	?>
		<a href="<?php echo add_query_arg(array('paged' => ($current_page+1))) ?>" class="button" style="margin-top: 10px; float: right;"><?php _e('Next Page', 'five'); ?></a>
	
	<?php endif; ?>
	
	<?php
		
		if($current_page > 1) :
		
	?>
		<a href="<?php echo add_query_arg(array('paged' => ($current_page-1))) ?>" class="button" style="margin-top: 10px; float: left;"><?php _e('Previous Page', 'five'); ?></a>
	
	<?php endif; ?>

<?php endif; ?>

<style type="text/css">
	
	.woocommerce-save-button { display: none !important; }
	
</style>
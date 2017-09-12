<?php
/**
* WooCommerce GoFetch Integration
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch
*
* Plugin Name: WooCommerce GoFetch
* Plugin URI: https://fivecreative.com.au/
* Description: Allows your customers to use gofetch as their delivery option
* Version: 1.0.1
* Author: FIVE Creative
* Author URI: https://fivecreative.com.au
* Requires at least: 4.8.1
* Tested up to: 4.8.1
* Text Domain: five
*
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
		
	// Defines our plugin path directory and url
	define('WCGO_PLUGIN_URL', plugin_dir_url(__FILE__));
	define('WCGO_PLUGIN_PATH', plugin_dir_path(__FILE__));
	
	// Requires our classes
	require(WCGO_PLUGIN_PATH.'class-wcgo.php');
	require(WCGO_PLUGIN_PATH.'class-wcgo-updater.php');

	// Initiates our WCGO Updater class
	if(is_admin())
		WCGO_Updater(__FILE__, 'fivecreative', 'woocommerce-gofetch', '');
	
	/**
	 * Hooks
	 */
	add_action('woocommerce_shipping_init', 'wcgo_shipping_method_init'); // Adds our shipping class via hook
	add_action('wp_enqueue_scripts', 'wcgo_enqueue_scripts'); // Enqueues our javascript and css on front end
	add_action('admin_enqueue_scripts', 'wcgo_admin_enqueue_scripts'); // Enqueues our admin scripts and styles
	add_action('woocommerce_admin_field_wcgo_map', 'wcgo_admin_page_map', 10, 1); // Adds our map for the pickup address
	add_action('woocommerce_review_order_after_shipping', 'wcgo_delivery_date'); // If we are enabling the customer to choose their delivery date
	add_action('woocommerce_checkout_update_order_review', 'wcgo_save_selected_delivery_date', 20, 1); // Ensures we save the users delected delivery day choice
	add_action('woocommerce_checkout_create_order', 'wcgo_save_selected_delivery_date_to_order', 10, 2); // Ensures we save our selected delivery date to our order
	add_action('woocommerce_admin_order_totals_after_shipping', 'wcgo_show_wcgo_delivery_date_on_order_panel', 10, 1); // Shows our order delivery date on our admin panel - edit -order
	add_action('woocommerce_order_status_processing', 'wcgo_autbook_gofetch_deliveries', 100, 1); // Autobooks our gofetch deliveries if set so
	add_action('woocommerce_order_status_completed', 'wcgo_autbook_gofetch_deliveries', 100, 1); // Autobooks our gofetch deliveries if set so
	
	
	
	/**
	 * Filters
	 */
	add_filter('woocommerce_get_settings_pages', 'wcgo_settings_page'); // Includes our WCGO_Admin_Page class via hooks
	add_filter('woocommerce_shipping_methods', 'wcgo_shipping_method_add'); // Adds our shipping class to the list of methods
	add_filter('woocommerce_checkout_fields', 'wcgo_checkout_fields', 10, 1); // Adds our address search checkout field.
	add_filter('woocommerce_calculated_total', 'wcgo_add_delivery_surcharge_total', 10, 2); // Adds our delivery date surcharge to the cart total.
	add_filter('woocommerce_get_order_item_totals', 'wcgo_add_order_totals_row_delivery_date', 10, 3); // Adds our delivery date to our order totals row.
	
	
	
	/**
	 * AJAX
	 */
	add_action('wp_ajax_wcgo_book_order_delivery', 'wcgo_book_order_delivery'); // Does our job booking
	add_action('wp_ajax_wcgo_get_order_row', 'wcgo_get_order_row'); // Gets a row for our order
	
	
		
	/**
	 * Includes our WCGO Settings Page Class via hooks.
	 * 
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	function wcgo_settings_page($settings) {
		
		// Includes as class
		$settings[] = include(WCGO_PLUGIN_PATH.'admin/class-wcgo-admin-page.php');
		
		// Returns it
		return $settings;
		
	}
	
	/**
	 * Includes our shipping method class.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_shipping_method_init() {
		
		include(WCGO_PLUGIN_PATH.'/class-wcgo-shipping-method.php');
		
	}
	
	/**
	 * Adds our Gofetch method to the list.
	 * 
	 * @access public
	 * @param array $methods
	 * @return array
	 */
	function wcgo_shipping_method_add($methods) {
		
		$methods['wc_gofetch'] = 'WC_GoFetch_Shipping_Method';
		
		return $methods;
		
	}
	
	/**
	 * Enqueues our js and css on frontend.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_enqueue_scripts() {
		
		// If we have our google maps api key
		if(!get_option('wcgo_gmaps_api_key_client', false) || get_option('wcgo_gmaps_api_key_client', false) == '')
			return $fields;
		
		// Registers our css
		wp_register_style('wcgo-checkout', WCGO_PLUGIN_URL.'assets/css/style.css', null, filemtime(WCGO_PLUGIN_PATH.'assets/css/style.css'));
		
		// Registers our javascript
		wp_register_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key='.get_option('wcgo_gmaps_api_key_client').'&libraries=places', null);
		wp_register_script('wcgo-checkout', WCGO_PLUGIN_URL.'assets/js/scripts.js', array('jquery', 'google-maps'), filemtime(WCGO_PLUGIN_PATH.'assets/js/scripts.js'));
		
		// Localises our script
		wp_localize_script('wcgo-checkout', 'wcgo', array(
			
			'countries' => get_option('woocommerce_specific_allowed_countries', array()),
			
		));
		
		// Enqueues on checkout
		if(function_exists('is_checkout') && is_checkout()) {
			
			// CSS
			wp_enqueue_style('wcgo-checkout');
			
			// JS
			wp_enqueue_script('wcgo-checkout');
			
		}
		
	}
	
	/**
	 * Adds our address search checkout field.
	 * 
	 * @access public
	 * @param array $fields
	 * @return array
	 */
	function wcgo_checkout_fields($fields) {
		
		// If we have our google maps api key
		if(!get_option('wcgo_gmaps_api_key_client', false) || get_option('wcgo_gmaps_api_key_client', false) == '')
			return $fields;
		
		// Adds our address search
		$fields['billing']['billing_address_search'] = array(
			
			'label' => __('Address', 'five'),
			'autocomplete' => 'off',
			'placeholder' => __('Start typing to search address...', 'five'),
			'priority' => 115,
			'required' => true,
			
		);
		
		$fields['shipping']['shipping_address_search'] = array(
			
			'label' => __('Address', 'five'),
			'autocomplete' => 'off',
			'placeholder' => __('Start typing to search address...', 'five'),
			'priority' => 85,
			'required' => true,
			
		);
		
		return $fields;
		
	}
	
	/**
	 * Displays our map in the pickup settings page.
	 * 
	 * @access public
	 * @param array $value
	 * @return string
	 */
	function wcgo_admin_page_map($value) {
		
		include(WCGO_PLUGIN_PATH.'admin/views/html-pickup-map.php');
		
	}
	
	/**
	 * If we enable the user to select a delivery date, add our select here.
	 * 
	 * @access public
	 * @return string
	 */
	function wcgo_delivery_date() {
		
		// Check we allow the user to select their dates
		if(get_option('wcgo_enable_delivery_choice') != 'yes')
			return;
			
		// Ensures that go fetch is the selected delivery method
		$chosen = WC()->session->get('chosen_shipping_methods')[0];
		
		if(strpos($chosen, 'wc_gofetch') === false)
			return;
			
		// Ensures that we have our shipping method avialable
		$packages = WC()->shipping->get_packages();
		
		if(empty($packages))
			return;
			
		$has_package = false;
			
		foreach($packages as $i => $package) {
			
			if(empty($package['rates']))
				continue;
				
			foreach($package['rates'] as $key => $rate) {
				
				if(strpos($key, 'wc_gofetch') !== false)
					$has_package = true;
				
			}
			
		}
		
		if(!$has_package)
			return;
			
		// Displays our date selector
		include(WCGO_PLUGIN_PATH.'views/html-delivery-date.php');
		
	}
	
	/**
	 * Saves our posted delivery date.
	 * 
	 * @access public
	 * @param array $post_data
	 * @return void
	 */
	function wcgo_save_selected_delivery_date($post_data) {
		
		// If delivery choice is not an optiopn dont even bother
		if(get_option('wcgo_enable_delivery_choice') != 'yes')
			return;
			
		$data = array();
		parse_str($post_data, $data);
		
		// If we have our selected deliuvery date
		if(isset($data['wcgo-delivery-date'])) {
			
			// Checks if we need to add a fee to our cart for selecting this delivery date
			foreach(WCGO()->get_available_delivery_dates() as $value => $label) {
				
				// If we have matched the selected value
				if($data['wcgo-delivery-date'] == $value) {
					
					// Sets our value
					WC()->session->set('wcgo-delivery-date', $data['wcgo-delivery-date']);
					
				}
				
			}
		
		}
		
		
	}
	
	/**
	 * Adds our delivery surcharge to our cart total.
	 * 
	 * @access public
	 * @param float $total
	 * @param object $cart
	 * @return float
	 */
	function wcgo_add_delivery_surcharge_total($total, $cart) {
		
		// Only do it if we have a selected date option avialable
		if(get_option('wcgo_enable_delivery_choice') != 'yes')
			return $total;
			
		// Only add this during our checkout - no delivery day select during cart
		if(!is_checkout())
			return $total;
			
		// Ensures we have our wcgofetch as the chosen delivery method
		$chosen = WC()->session->get('chosen_shipping_methods')[0];
		
		if(strpos($chosen, 'wc_gofetch') === false)
			return $total;
			
		// Gets the delivery day selected
		foreach(WCGO()->get_available_delivery_dates() as $value => $label) {
			
			if(WC()->session->get('wcgo-delivery-date') == $value && !empty($label['surcharge'])) {
				
				$total += $label['surcharge'];
				
			}
			
		}
		
		return $total;
		
	}
	
	/**
	 * Ensures we save our selected delivery date to order.
	 * 
	 * @access public
	 * @param object $order
	 * @param array $data
	 * @return void
	 */
	function wcgo_save_selected_delivery_date_to_order($order, $data) {
		
		// Only do it if we have a selected date option avialable
		if(get_option('wcgo_enable_delivery_choice') != 'yes')
			return;
			
		// Ensures that our delivery method is gofetch
		$shipping_methods = $order->get_shipping_methods();
		
		$is_go_fetch = false;
		
		foreach($shipping_methods as $method) {
			
			$method_id = $method->get_method_id();
			
			if(strpos($method_id, 'wc_gofetch') !== false)
				$is_go_fetch = true;
			
		}
		
		if(!$is_go_fetch)
			return;
		
		// Gets the delivery date for this order
		foreach(WCGO()->get_available_delivery_dates() as $value => $label) {
			
			// If its this deliveyr date
			if(WC()->session->get('wcgo-delivery-date') == $value) {
				
				$delivery_date = str_replace('_asap', '', $value);
				
				// Gets our datetime object
				try {
					
					$datetime = new DateTime($delivery_date.' 00:00:00', new DateTimezone(WCGO()->get_timezone()));
					
				} catch(Exception $e) {
					
					return;
					
				}
				
				// Surcharge
				$surcharge = !empty($label['surcharge']) ? $label['surcharge'] : '';
				
				$order->add_meta_data('wcgo_delivery', array(
				
					'delivery_date_formatted' => $datetime->format('j F, Y'),
					'delivery_date' => $datetime->format('Y-m-d'),
					'surcharge' => $surcharge,
					'delivery_date_label' => $label['title'],
					
				));
				
			}
			
		}
		
	}
	
	/**
	 * Adds our delivery date to the order totals row.
	 * 
	 * @access public
	 * @param array $total_rows
	 * @param object $order
	 * @param mixed $tax_display
	 * @return void
	 */
	function wcgo_add_order_totals_row_delivery_date($total_rows, $order, $tax_display) {
		
		// If we have our wcgo_delivery metadata
		$wcgo_delivery = get_post_meta($order->get_id(), 'wcgo_delivery', true);
		
		if(empty($wcgo_delivery))
			return $total_rows;
			
		// Lets add our new delivery row after this shipping row
		$new_rows = array();
		
		foreach($total_rows as $key => $row) {
			
			$new_rows[$key] = $row;
			
			// If its our shipping
			if($key == 'shipping') {
				
				// Suffix
				if(!empty($wcgo_delivery['surcharge']))
					$suffix = ' (+'.wc_price($wcgo_delivery['surcharge']).')';
				else
					$suffix = '';
				
				$new_rows['wcgo_delivery'] = array(
					
					'label' => __('Delivery Date:', 'five'),
					'value' => $wcgo_delivery['delivery_date_formatted'].$suffix,
					
				);
				
			}
			
		}
		
		return $new_rows;
		
	}
	
	/**
	 * Displays the order delivery date on the edit order panel.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return string
	 */
	function wcgo_show_wcgo_delivery_date_on_order_panel($order_id) {
		
		// Gets our order
		$order = wc_get_order($order_id);
		
		$wcgo_delivery = get_post_meta($order->get_id(), 'wcgo_delivery', true);
		
		// No delkivery day set
		if(empty($wcgo_delivery))
			return;
			
		include(WCGO_PLUGIN_PATH.'admin/views/html-order-totals-delivery-date.php');
		
	}
	
	/**
	 * Gets the status of a gofetch delivery.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return string
	 */
	function wcgo_get_order_delivery_status($order_id) {
		
		// Gets our order object
		$order = wc_get_order($order_id);
		
		if(!$order)
			return false;
		
		// Gets the delivery status from gofetch
		$delivery_id = get_post_meta($order->get_id(), 'wcgo_delivery_id', true);
		
		// No delivery ID - job is not booked
		if(empty($delivery_id))
			return 'unbooked';
			
		// Lets fetch the status for this order
		return WCGO()->get_delivery_status($delivery_id);
		
	}
	
	/**
	 * Gets the order delivery status formatted.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return string
	 */
	function wcgo_get_order_delivery_status_formatted($order_id) {
		
		// Gets the delivery status
		$status = wcgo_get_order_delivery_status($order_id);
		
		// Maybe save as a meta for filtering
		if(get_post_meta($order_id, 'wcgo_delivery_status', true) != $status)
			update_post_meta($order_id, 'wcgo_delivery_status', $status);
		
		switch($status) {
			
			case 'unbooked' :
				$status = 'Not Booked';
				break;
			
			case 'abandoned' :
				$status = 'Abandoned';
				break;
			
			case 'picking_up' :
				$status = 'Picking Up';
				break;
				
			default :
				$status = ucwords($status);
				break;
			
		}
		
		return $status;
		
	}
	
	/**
	 * Gets the status information for the given status.
	 * 
	 * @access public
	 * @param mixed $status
	 * @return void
	 */
	function wcgo_get_tooltip_info($status) {
		
		$info = '';
		
		switch(strtolower($status)) {
			
			case 'pending' :
				$info = __('Your job has been posted and waiting for a driver to accept.', 'five');
				break;
			
			case 'picking up' :
				$info = __('Your driver is on his way to collect the item(s).', 'five');
				break;
			
			case 'delivering' :
				$info = __('Your driver is on route to deliver your item(s).', 'five');
				break;
			
			case 'delivered' :
				$info = __('Your item(s) has been delivered.', 'five');
				break;
			
			case 'confirmed' :
				$info = __('A driver has accepted this job.', 'five');
				break;
			
			case 'completed' :
				$info = __('Your item(s) has been delivery.', 'five');
				break;
			
			case 'cancelled' :
				$info = __('This job has been cancelled.', 'five');
				break;
			
			case 'ended' :
				$info = __('You have cancelled this job.', 'five');
				break;
			
			case 'abandoned' :
				$info = __('This job was automatically cancelled by GoFetch.', 'five');
				break;
			
			case 'issued' :
				$info = __('Job has been issued.', 'five');
				break;
			
			case 'not booked' :
				$info = __('This job has not yet been booked on GoFetch.', 'five');
				break;
			
		}
		
		return $info;
		
	}
	
	/**
	 * Gets the delivery date for a given order.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return string
	 */
	function wcgo_get_order_delivery_date($order_id) {
		
		// If we have a delivery id return the delivery date from that job
		if(get_post_meta($order_id, 'wcgo_delivery_id', true) != '') {
			
			// Gets the delivery status
			$delivery_date = WCGO()->get_delivery_date(get_post_meta($order_id, 'wcgo_delivery_id', true));
			
			// Maybe save to our database
			if(get_post_meta($order_id, 'wcgo_delivery_date', true) != $delivery_date->format('Ymd'))
				update_post_meta($order_id, 'wcgo_delivery_date', $delivery_date->format('Ymd'));
			
			// Returns our delivery date
			return $delivery_date->format('j F, Y - H:i');
			
		} else {
			
			// Sees if the user has selected a delivery date
			if(get_post_meta($order_id, 'wcgo_delivery', true) != '') {
				
				$delivery = get_post_meta($order_id, 'wcgo_delivery', true);
				
				try {
					
					$datetime = new DateTime($delivery['delivery_date'], new DateTimezone(WCGO()->get_timezone()));
			
					// Maybe save to our database
					if(get_post_meta($order_id, 'wcgo_delivery_date', true) != $datetime->format('Ymd'))
						update_post_meta($order_id, 'wcgo_delivery_date', $datetime->format('Ymd'));
					
					return $datetime->format('j F, Y');
					
				} catch(Exception $e) {
					
					
					
				}
				
			} else {
				
				return 'Not yet booked.';
				
			}
			
		}
		
		return 'No Delivery Date Available';
		
	}
	
	/**
	 * Gets 14 days in advance dates for booking our jobs.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_get_booking_delivery_dates() {
		
		if(get_transient('wcgo_get_booking_delivery_dates'))
			return get_transient('wcgo_get_booking_delivery_dates');
		
		$dates = array();
		
		try {
			
			$today = new DateTime('now', new DateTimezone(WCGO()->get_timezone()));
			
			for($i = 0; $i < 14; $i++) {
				
				$dates[$today->format('Y-m-d')] = $today->format('l, j F');
				$today->modify('+1 day');
				
			}
			
		} catch(Exception $e) {
			
			return $dates;
			
		}
		
		set_transient('wcgo_get_booking_delivery_dates', $dates, HOUR_IN_SECONDS);
		
		return $dates;
		
	}
	
	/**
	 * Gets the admin template for an order row on our table.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return string
	 */
	function wcgo_admin_get_order_row($order_id) {
		
		ob_start();
		include(WCGO_PLUGIN_PATH.'admin/views/html-admin-gofetch-table-row.php');
		return ob_get_clean();
		
	}
	
	/**
	 * Enqueue sour admin scripts.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_admin_enqueue_scripts() {
		
		// Main js
		wp_register_script('wcgo-admin', WCGO_PLUGIN_URL.'assets/js/admin.js', array('jquery'), filemtime(WCGO_PLUGIN_PATH.'assets/js/admin.js'));
		
		// Main CSS
		wp_register_style('wcgo-admin', WCGO_PLUGIN_URL.'assets/css/admin.css', null, filemtime(WCGO_PLUGIN_PATH.'assets/css/admin.css'));
		
		$screen = get_current_screen();
		
		if($screen->id != 'woocommerce_page_wc-settings')
			return;
			
		if(empty($_GET['tab']) || $_GET['tab'] != 'wcgo')
			return;
			
		// Localizes our script
		wp_localize_script('wcgo-admin', 'wcgo', array(
			
			'ajaxurl' => admin_url('admin-ajax.php'),
			
		));
		
		// Enqueues
		wp_enqueue_script('wcgo-admin');
		wp_enqueue_style('wcgo-admin');
		
	}
	
	/**
	 * Does our job bookings via ajax.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_book_order_delivery() {
		
		// Verifies nonce
		$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : false;
		$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
		$notes = !empty($_POST['notes']) ? $_POST['notes'] : '';
		
		if(!wp_verify_nonce($nonce, 'wcgo_order_'.$order_id))
			wp_send_json_error(array('message' => 'Invalid security token. Please refresh your page and try again.'));
			
		// Verifies our date is at least 90 minutes in the future
		try {
			
			$datetime = new DateTime($_POST['day'].' '.$_POST['hour'].':'.$_POST['minute'].':00', new DateTimezone(WCGO()->get_timezone()));
			
		} catch(Exception $e) {
			
			wp_send_json_error(array('message' => __('There as an error with the date you selected. Error: ', 'five').$e->getMessage()));
			
		}
		
		// Time right now
		$now = new DateTime('now', new DateTimezone(WCGO()->get_timezone()));
		$now->modify('+90 minutes');
		
		// Not in the future
		if($now->getTimestamp() > $datetime->getTimestamp()) {
			
			wp_send_json_error(array('message' => __('Your delivery job must be booked at least 90 minutes in the future!', 'five')));
			
		}
		
		// Lets book the job
		try {
			
			WCGO()->book_order_delivery($order_id, $datetime, $notes);
			
		} catch(Exception $e) {
			
			wp_send_json_error(array('message' => $e->getMessage()));
			
		}
		
		wp_send_json_success();
		
	}
	
	/**
	 * Gets the markup for a single order row on our gofetch deliveries.
	 * 
	 * @access public
	 * @return void
	 */
	function wcgo_get_order_row() {
		
		// Verifies nonce
		$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : false;
		$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
		
		if(!wp_verify_nonce($nonce, 'wcgo_order_'.$order_id))
			wp_send_json_error(array('message' => 'Invalid security token. Please refresh your page and try again.'));
			
		$markup = wcgo_admin_get_order_row($order_id);
		
		wp_send_json_success(array('markup' => $markup));
		
	}
	
	/**
	 * Gets the avialable filters for our order dates.
	 * 
	 * @access public
	 * @return array
	 */
	function wcgo_get_available_order_date_filters() {
		
		if(get_transient('wcgo_available_order_date_filters'))
			return get_transient('wcgo_available_order_date_filters');
			
		// Lets grab our latest order
		$q = new WP_Query(array(
			
			'post_type' => 'shop_order',
			'fields' => 'ids',
			'post_status' => array_keys(wc_get_order_statuses()),
			'meta_query' => array(
				
				array(
					
					'key' => '_shipping_method',
					'value' => "wc_gofetch",
					'compare' => 'LIKE',
					
				)
				
			),
			'orderby' => 'date',
			'order' => 'ASC',
			'posts_per_page' => 1,
			
		));
		
		$dates = array();
		
		$first_order = wc_get_order(array_shift($q->posts));
		$first_order_date_time = new DateTime($first_order->get_date_created()->format('Y-m-d H:i:s'), new DateTimezone(WCGO()->get_timezone()));
		$this_month = new DateTime('now', new DateTimezone(WCGO()->get_timezone()));
		$this_month->modify('first day of this month');
		
		while($first_order_date_time->getTimestamp() < $this_month->getTimestamp()) {
			
			if($first_order_date_time->format('Y-m') == $this_month->format('Y-m'))
				break;
			
			$dates[$first_order_date_time->format('Y-m')] = $first_order_date_time->format('F Y');
			
			$first_order_date_time->modify('first day of next month');
			
		}
		
		$dates = array_reverse($dates);
		
		set_transient('wcgo_available_order_date_filters', $dates, DAY_IN_SECONDS);
		
		return $dates;
		
	}
	
	/**
	 * Gets the avialable filters for our order delivery dates.
	 * 
	 * @access public
	 * @return array
	 */
	function wcgo_get_available_delivery_date_filters() {
		
		if(get_transient('wcgo_available_order_delivery_date_filters'))
			return get_transient('wcgo_available_order_delivery_date_filters');
			
		return array();
		
	}
	
	/**
	 * Whenever a new order is made, check if we are autobooking gofetch deliveries.
	 * 
	 * @access public
	 * @param int $order_id
	 * @return void
	 */
	function wcgo_autbook_gofetch_deliveries($order_id) {
		
		// Check we allow the user to select their dates
		if(get_option('wcgo_enable_delivery_choice') != 'yes' || get_option('wcgo_autobook') != 'yes')
			return;
			
		$order = wc_get_order($order_id);
			
		// Ensures that our delivery method is gofetch
		$shipping_methods = $order->get_shipping_methods();
		
		$is_go_fetch = false;
		
		foreach($shipping_methods as $method) {
			
			$method_id = $method->get_method_id();
			
			if(strpos($method_id, 'wc_gofetch') !== false)
				$is_go_fetch = true;
			
		}
		
		if(!$is_go_fetch)
			return;
			
		// Checks if this order already has a delivery attached to it
		if(get_post_meta($order_id, 'wcgo_delivery_id', true) != '')
			return;
			
		// Lets get the delivery time for this order
		$delivery_info = get_post_meta($order->get_id(), 'wcgo_delivery', true);
		
		try {
			
			$datetime = new DateTime($delivery_info['delivery_date'].' '.get_option('wcgo_autobook_time').':00', new DateTimezone(WCGO()->get_timezone()));
			
			WCGO()->book_order_delivery($order_id, $datetime, $order->get_customer_note());
			
		} catch(Exception $e) {
			
			return;
			
		}
		
	}

?>
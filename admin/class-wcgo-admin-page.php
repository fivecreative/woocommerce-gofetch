<?php
/**
* Deals with our global woocommerce settings page
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Admmin
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	/**
	 * WCGO_Settings_Page class.
	 * 
	 * @extends WC_Settings_Page
	 */
	class WCGO_Settings_Page extends WC_Settings_Page {
		
		/**
		 * __construct function.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			
			// ID and Label
			$this->id = 'wcgo';
			$this->label = __('GoFetch', 'five');
			
			parent::__construct();
			
		}
		
		/**
		 * Admin page sections.
		 * 
		 * @access public
		 * @return array
		 */
		public function get_sections() {
			
			// Defines our sections
			$sections = array(
				
				'' => __('GoFetch Deliveries', 'five'),
				'general' => __('General Settings', 'five'),
				'delivery' => __('Delivery Settings', 'five'),
				'pickup' => __('Pick Up Settings', 'five'),
				
			);
			
			// Returns
			return apply_filters( 'woocommerce_get_sections_'.$this->id, $sections );
			
		}
		
		/**
		 * Ouitputs a section's fields.
		 * 
		 * @access public
		 * @return void
		 */
		public function output() {
			
			// Current section global
			global $current_section;
			
			// Gets the settings based on the section we are in
			$settings = $this->get_settings($current_section);
			
			// If delivery outputs our javascript
			if($current_section == 'delivery')
				include(WCGO_PLUGIN_PATH.'admin/views/delivery-js.php');
				
			// Javascript for pickup
			if($current_section == 'pickup')
				include(WCGO_PLUGIN_PATH.'admin/views/pickup-js.php');
				
			// If its our main section
			if($current_section == '') {
				
				include(WCGO_PLUGIN_PATH.'admin/views/html-admin-gofetch-table.php');
				
			} else {
			
				// Outputs our fields
				WC_Admin_Settings::output_fields($settings);
			
			}
			
		}
		
		/**
		 * Saves settings based on the current section.
		 * 
		 * @access public
		 * @return void
		 */
		public function save() {
			
			// Current section
			global $current_section;

			// Grabs our settings
			$settings = $this->get_settings($current_section);
			
			// If its our main sections
			if($current_section == 'general') {
			
				// If its our default settings verify our credentials
				if(!WCGO()->verify_user($_POST['wcgo_email']) || !WCGO()->verify_pass($_POST['wcgo_password']) || (!isset($_POST['wcgo_sandbox']) && get_option('wcgo_sandbox') == 'yes') || (isset($_POST['wcgo_sandbox']) && get_option('wcgo_sandbox') != 'yes')) {
					
					$testmode = isset($_POST['wcgo_sandbox']) ? true : false;
					
					// If we cant log in
					if(!WCGO()->verify_credentials($_POST['wcgo_email'], $_POST['wcgo_password'], $testmode)) {
						
						// Adds error and wipes the username and password
						WC_Admin_Settings::add_error(__('Could not connect to the GoFetch API using the credentials provided. Please double check your credentials and that you are using the right credentials for test or live mode.', 'five'));
						
						// Ensures we dont save those credentials
						$_POST['wcgo_email'] = '';
						$_POST['wcgo_password'] = '';
						
					} else {
						
						// Saves our new username and password
						update_option('wcgo_user', $_POST['wcgo_email']);
						update_option('wcgo_pass', $_POST['wcgo_password']);
						
					}
					
				}
				
				// Verifies our google key api
				if(!empty($_POST['wcgo_gmaps_api_key']) && $_POST['wcgo_gmaps_api_key'] !== get_option('wcgo_gmaps_api_key')) {
					
					// Attemps our key
					try {
						
						WCGO()->verify_google_maps_key($_POST['wcgo_gmaps_api_key']);
						
					} catch(Exception $e) {
						
						WC_Admin_Settings::add_error(__('Could not connect to the Google Maps API Server using the API Key provided. Error: ', 'five').$e->getMessage());
						$_POST['wcgo_gmaps_api_key'] = '';
						
					}
					
				}
			
			} elseif($current_section == 'pickup') {
				
				// Saves our hidden address fields if they're there
				if(isset($_POST['wcgo_pickup_address_address']))
					update_option('wcgo_pickup_address_address', $_POST['wcgo_pickup_address_address']);
					
				if(isset($_POST['wcgo_pickup_address_suburb']))
					update_option('wcgo_pickup_address_suburb', $_POST['wcgo_pickup_address_suburb']);
					
				if(isset($_POST['wcgo_pickup_address_postcode']))
					update_option('wcgo_pickup_address_postcode', $_POST['wcgo_pickup_address_postcode']);
					
				if(isset($_POST['wcgo_pickup_address_state']))
					update_option('wcgo_pickup_address_state', $_POST['wcgo_pickup_address_state']);
				
			} elseif($current_section == 'delivery') {
				
				// If we are allowing same day delivery checks our time value
				if(isset($_POST['wcgo_enable_delivery_choice']) && isset($_POST['wcgo_same_day_delivery'])) {
					
					$time = $_POST['wcgo_same_day_delivery_cutoff'];
					
					// If time is blank set to 00:00
					if(empty($time)) {
						
						$_POST['wcgo_same_day_delivery_cutoff'] = '00:00';
						
					} else {
						
						if(!preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $time)) {
							
							WC_Admin_Settings::add_error(__('Invalid time for same day delivery cut-off time. Please type in HH:MM', 'five'));
							$_POST['wcgo_same_day_delivery_cutoff'] = '';
							
						}
						
					}
					
				}
				
				// If we are allowing same day delivery checks our time value
				if(isset($_POST['wcgo_enable_delivery_choice']) && isset($_POST['wcgo_next_day_delivery'])) {
					
					$time = $_POST['wcgo_next_day_delivery_cutoff'];
					
					// If time is blank set to 00:00
					if(empty($time)) {
						
						$_POST['wcgo_next_day_delivery_cutoff'] = '00:00';
						
					} else {
						
						if(!preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $time)) {
							
							WC_Admin_Settings::add_error(__('Invalid time for next day delivery cut-off time. Please type in HH:MM', 'five'));
							$_POST['wcgo_next_day_delivery_cutoff'] = '';
							
						}
						
					}
					
				}
				
				// If we are allowing same day delivery checks our time value
				if(isset($_POST['wcgo_enable_delivery_choice']) && isset($_POST['wcgo_asap_delivery'])) {
					
					$time = $_POST['wcgo_asap_delivery_cutoff'];
					
					// If time is blank set to 00:00
					if(empty($time)) {
						
						$_POST['wcgo_asap_delivery_cutoff'] = '00:00';
						
					} else {
						
						if(!preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $time)) {
							
							WC_Admin_Settings::add_error(__('Invalid time for ASAP delivery cut-off time. Please type in HH:MM', 'five'));
							$_POST['wcgo_asap_delivery_cutoff'] = '';
							
						}
						
					}
					
				}
				
				// If we are autobooking gofetch deliveries
				if(isset($_POST['wcgo_enable_delivery_choice']) && isset($_POST['wcgo_autobook'])) {
					
					$time = $_POST['wcgo_autobook_time'];
					
					// If time is blank set to 13:00
					if(empty($time)) {
						
						$_POST['wcgo_autobook_time'] = '13:00';
						
					} else {
						
						if(!preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $time)) {
							
							WC_Admin_Settings::add_error(__('Invalid time for auto book time. Please type in HH:MM', 'five'));
							$_POST['wcgo_autobook_time'] = '';
							
						}
						
					}
					
				}
				
			}
			
			// Saves the fields
			WC_Admin_Settings::save_fields($settings);
			
		}
		
		/**
		 * Gets the settings for a given section.
		 * 
		 * @access public
		 * @param string $section
		 * @return array
		 */
		public function get_settings($section = '') {
			
			// Delivery section
			if($section == 'delivery') {
				
				$timezone = get_option('timezone_string');
				
				if(empty($timezone))
					$timezone = 'UTC';
				
				$settings = array(
					
					// Delivery Days
					array(
						
						'title' => 'Delivery options',
						'type' => 'title',
						'id' => 'wcgo_delivery_days',
						
					),
					
					array(
						
						'title' => __('Delivery Date Choice', 'five'),
						'desc' => __('Enable customers to choose their delivery date', 'five'),
						'id' => 'wcgo_enable_delivery_choice',
						'type' => 'checkbox',
						'default' => '',
						'checkboxgroup'   => 'start',
						'show_if_checked' => 'option',
						
					),
					
					array(
						
						'title' => __('ASAP Delivery', 'five'),
						'desc' => __('Allow users to select deliveries to be made ASAP', 'five'),
						'id' => 'wcgo_asap_delivery',
						'type' => 'checkbox',
						'default' => '',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						
					),
					
					array(
						
						'title' => __('Same Day Delivery', 'five'),
						'desc' => __('Allow users to select same day delivery', 'five'),
						'id' => 'wcgo_same_day_delivery',
						'type' => 'checkbox',
						'default' => '',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						
					),
					
					array(
						
						'title' => __('Next Day Delivery', 'five'),
						'desc' => __('Allow users to select next day delivery', 'five'),
						'id' => 'wcgo_next_day_delivery',
						'type' => 'checkbox',
						'default' => '',
						'checkboxgroup'   => 'end',
						'show_if_checked' => 'yes',
						
					),
					
					array(
						
						'title' => __('ASAP Delivery Cut-off Time', 'five'),
						'desc' => '<p>'.__('Select the cut off time for ASAP deliveries via go fetch. After this time, ASAP deliveries will no longer be available.', 'five').'<br>Note: Your timezone is currently set to <strong>'.$timezone.'</strong>. Please ensure you are using the correct timezone (i.e Australia/Melbourne).<br>You can change your timezone <strong><a href="'.admin_url('options-general.php').'">here</a></strong>.</p>',
						'id' => 'wcgo_asap_delivery_cutoff',
						'default' => '10:00',
						'type' => 'text',
						'class' => 'wcgo-asap-day-delivery',
						
					),
					
					array(
						
						'title' => __('ASAP Delivery Surcharge', 'five'),
						'type' => 'number',
						'desc' => __('Add here a surcharge for ASAP delivery. This surcharge is applied on top of the calculated price.', 'five'),
						'id' => 'wcgo_asap_delivery_surcharge',
						'desc_tip' => true,
						'css' => 'width: 100px;',
						'default' => '',
						
					),
					
					array(
						
						'title' => __('Same Day Delivery Cut-off Time', 'five'),
						'desc' => '<p>'.__('Select the cut off time for same day deliveries via go fetch. After this time, same day deliveries will no longer be available.', 'five').'<br>Note: Your timezone is currently set to <strong>'.$timezone.'</strong>. Please ensure you are using the correct timezone (i.e Australia/Melbourne).<br>You can change your timezone <strong><a href="'.admin_url('options-general.php').'">here</a></strong>.</p>',
						'id' => 'wcgo_same_day_delivery_cutoff',
						'default' => '10:00',
						'type' => 'text',
						'class' => 'wcgo-same-day-delivery',
						
					),
					
					array(
						
						'title' => __('Same Day Delivery Surcharge', 'five'),
						'type' => 'number',
						'desc' => __('Add here a surcharge for same day delivery. This surcharge is applied on top of the calculated price.', 'five'),
						'id' => 'wcgo_same_day_delivery_surcharge',
						'desc_tip' => true,
						'css' => 'width: 100px;',
						'default' => '',
						
					),
					
					array(
						
						'title' => __('Next Day Delivery Cut-off Time', 'five'),
						'desc' => '<p>'.__('Select the cut-off time for next day deliveries. For example, if 10:00 users will only be able to select next day delivery if placing the order before 10:00.', 'five').'<br>Note: Your timezone is currently set to <strong>'.$timezone.'</strong>. Please ensure you are using the correct timezone (i.e Australia/Melbourne).<br>You can change your timezone <strong><a href="'.admin_url('options-general.php').'">here</a></strong>.</p>',
						'id' => 'wcgo_next_day_delivery_cutoff',
						'default' => '10:00',
						'type' => 'text',
						'class' => 'wcgo-next-day-delivery',
						
					),
					
					array(
						
						'title' => __('Next Day Delivery Surcharge', 'five'),
						'type' => 'number',
						'desc' => __('Add here a surcharge for next day delivery. This surcharge is applied on top of the calculated price.', 'five'),
						'id' => 'wcgo_next_day_delivery_surcharge',
						'desc_tip' => true,
						'css' => 'width: 100px;',
						'default' => '',
						
					),
					
					array(
						
						'title' => __('Auto Book GoFetch Deliveries', 'five'),
						'id' => 'wcgo_autobook',
						'desc' => 'Automatically book gofetch deliveries when orders are placed.',
						'type' => 'checkbox',
						
					),
					
					array(
						
						'title' => __('Auto Book Deliveries Time', 'five'),
						'id' => 'wcgo_autobook_time',
						'desc' => 'Set here the default time for when booking gofetch deliveries automatically',
						'desc_tip' => true,
						'css' => 'width: 100px;',
						'default' => '13:00',
						'type' => 'text',
						
					),
					
					array(
						
						'title'         => __('Delivery Days', 'five'),
						'desc'          => __('Monday', 'five'),
						'id'            => 'wcgo_delivery_day_monday',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'start',
						
					),
					
					array(
						
						'desc'          => __('Tuesday', 'five'),
						'id'            => 'wcgo_delivery_day_tuesday',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'desc'          => __('Wednesday', 'five'),
						'id'            => 'wcgo_delivery_day_wednesday',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'desc'          => __('Thursday', 'five'),
						'id'            => 'wcgo_delivery_day_thursday',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'desc'          => __('Friday', 'five'),
						'id'            => 'wcgo_delivery_day_friday',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'desc'          => __('Saturday', 'five'),
						'id'            => 'wcgo_delivery_day_saturday',
						'default'       => '',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'desc'          => __('Sunday', 'five'),
						'id'            => 'wcgo_delivery_day_sunday',
						'default'       => '',
						'type'          => 'checkbox',
						'checkboxgroup' => '',
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_delivery_days',
						
					),
					
				);
				
			} elseif($section == 'pickup') {
				
				$settings = array(
				
					// Pickup Information
					array(
						
						'title' => __('Pickup Details', 'five'),
						'type' => 'title',
						'id' => 'wcgo_pickup',
						
					),
					
					array(
						
						'title' => __('Pickup Address', 'five'),
						'type' => 'text',
						'id' => 'wcgo_pickup_address',
						'css'      => 'width: 500px;',
						'desc' => '<p>'.__('Type here pickup address for orders. Where the fetchers will pick up orders from.<br>Select the address from the dropdown.', 'five').'</p>',
						
					),
					
					array(
						
						'title' => '',
						'type' => 'wcgo_map',
						'id' => 'wcgo_pickup_location',
						'desc' => '<p>'.__('Double check the location of your pickup address on the map above.', 'five').'</p>',
						
					),
						
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_delivery_days',
						
					),
				
					// Pickup Information
					array(
						
						'title' => __('GoFetch Pickup Defaults', 'five'),
						'type' => 'title',
						'id' => 'wcgo_pickup_defaults',
						
					),
					
					array(
						
						'title' => __('Contact ID', 'five'),
						'type' => 'select',
						'id' => 'wcgo_pickup_contact_id',
						'desc' => __('Select the contact ID to be used when booking gofetch deliveries.', 'five'),
						'desc_tip' => true,
						'options' => WCGO()->get_available_contact_ids(),
						'css'      => 'width: 250px;',
						
					),
					
					array(
						
						'title' => __('Credit Card', 'five'),
						'type' => 'select',
						'id' => 'wcgo_credit_card',
						'desc' => __('Select the credit card to be used when booking gofetch deliveries.', 'five'),
						'desc_tip' => true,
						'options' => WCGO()->get_available_credit_card_ids(),
						'css'      => 'width: 250px;',
						
					),
					
					array(
						
						'title' => __('Item Type', 'five'),
						'type' => 'select',
						'id' => 'wcgo_item_type',
						'desc' => __('Select the item type to be used with go fetch deliveries.', 'five'),
						'desc_tip' => true,
						'options' => WCGO()->get_available_item_types_ids(),
						'css'      => 'width: 250px;',
						
					),
						
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_pickup_defaults',
						
					),
				
				);
				
			} elseif($section == 'general') {
				
				// General section
				$settings = array(
					
					// Account information
					array(
						
						'title' => __('GoFetch Account Information', 'five'),
						'type' => 'title',
						'id' => 'wcgo_account_info',
						
					),
					
					array(
						
						'title'    => __('GoFetch Email', 'five'),
						'desc'     => __('Your GoFetch Email Address', 'five'),
						'id'       => 'wcgo_email',
						'type'     => 'text',
						'css'      => 'width: 250px;',
						'desc_tip' => true,
						
					),
					
					array(
						
						'title' => __('GoFetch Password', 'five'),
						'desc' => __('Your GoFetch account password', 'five'),
						'id' => 'wcgo_password',
						'type' => 'password',
						'css' => 'width: 250px;',
						'desc_tip' => true,
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_account_info',
						
					),
					
					// Pricing
					array(
						
						'title' => __('Pricing', 'five'),
						'type' => 'title',
						'id' => 'wcgo_pricing',
						
					),
					
					array(
						
						'title' => __('Default Price', 'five'),
						'type' => 'number',
						'desc' => __('Insert here the default pricing to be used as a fallback whenever the API is unavailable. This price is not affected by the price buffer.', 'five'),
						'css' => 'width: 250px;',
						'default' => '15',
						'id' => 'wcgo_price_default',
						'desc_tip' => true,
						
					),
					
					array(
						
						'title' => __('Price Buffer', 'five'),
						'type' => 'number',
						'desc' => __('Insert here a price buffer for the shipping price calculator, in percentage. For example, to increase the prices returned by 10%, insert 10', 'five'),
						'css' => 'width: 250px;',
						'default' => '10',
						'id' => 'wcgo_price_buffer',
						'desc_tip' => true,
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_pricing',
						
					),
					
					// Google Maps
					array(
						
						'title' => __('Google Maps API', 'five'),
						'type' => 'title',
						'id' => 'wcgo_gmaps',
						
					),
					
					array(
						
						'title' => __('Server Side Google Maps API Key', 'five'),
						'desc' => sprintf(__('<p>Your Google Maps API Key - Server Side. To get your google maps API Key go <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">here</a>.<br>Please ensure that you have enabled the following apis: Google Maps JavaScript API, Google Maps Distance Matrix API, Google Maps Geocoding API and Google Places API Web Service.<br>If you are using a restriction for your API key, this key needs to be restricted by IP address.<br>You will need to whitelist the following IP: <strong>%s</strong></p>', 'five'), $_SERVER['SERVER_ADDR']),
						'type' => 'text',
						'id' => 'wcgo_gmaps_api_key',
						'css' => 'width: 250px;',
						
					),
					
					array(
						
						'title' => __('Browser Side Google Maps API Key', 'five'),
						'desc' => sprintf(__('<p>Your Google Maps API Key - Browser Key. To get your google maps API Key go <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">here</a>.<br>Please ensure that you have enabled the following apis: Google Maps JavaScript API, Google Maps Distance Matrix API, Google Maps Geocoding API and Google Places API Web Service.<br>If you are using a restriction for your API key, this key needs to be restricted by HTTP Referrer.<br>You will need to whitelist the following referrer: <strong>%s</strong></p>', 'five'), str_replace(array('http://', 'https://'), '', home_url().'/*')),
						'type' => 'text',
						'id' => 'wcgo_gmaps_api_key_client',
						'css' => 'width: 250px;',
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_account_info',
						
					),
					
					// Test Mode
					array(
						
						'title' => __('Test Mode', 'five'),
						'type' => 'title',
						'id' => 'wcgo_test',
						
					),
					
					array(
						
						'title' => __('Test Mode', 'five'),
						'desc' => __('Enable test mode', 'five'),
						'type' => 'checkbox',
						'id' => 'wcgo_sandbox',
						'default' => '',
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_test',
						
					),
					
				);
				
			} else {
				
				$settings = array(
					
					// Jobs
					array(
						
						'title' => __('Upcoming and Past GoFetch Deliveries Report', 'five'),
						'type' => 'title',
						'id' => 'wcgo_gofetch_jobs',
						
					),
					
					array(
						
						'type' => 'sectionend',
						'id' => 'wcgo_gofetch_jobs',
						
					),
					
				);
				
			}
			
			// Returns our settings
			return apply_filters('woocommerce_get_settings_'.$this->id, $settings, (isset($current_section)) ? $current_section : '');
			
		}
		
	}
	
	return new WCGO_Settings_Page();

?>
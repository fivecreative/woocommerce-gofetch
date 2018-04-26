<?php
/**
* Our GoFetch Shipping Method Class
*
* @version 	1.0.6
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Classes
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	/**
	 * Woocommerce GoFetch Shipping Method Class.
	 * 
	 * @extends WC_Shipping_Method
	 */
	class WC_GoFetch_Shipping_Method extends WC_Shipping_Method {
		
		/**
		 * Constructs our class.
		 * 
		 * @access public
		 * @param int $instance_id
		 */
		public function __construct($instance_id = 0) {
			
			// Vars
			$this->id = 'wc_gofetch';
			$this->instance_id = absint($instance_id);
			$this->method_title = __('GoFetch', 'five');
			$this->method_description = __('Allows users to select GoFetch as their delivery option.<br>Ensure that all required gofetch settings have been provided <a style="text-decoration: underline;" href="'.add_query_arg(array('page' => 'wc-settings', 'tab' => 'wcgo'), admin_url('admin.php')).'">here</a>.', 'five');
			
			// Enables shipping zone and modal support
			$this->supports = array(
				
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
				
			);
			
			// Inits class
			$this->init();
			
			// Ensures we process our admin options
			add_action('woocommerce_update_options_shipping_'.$this->id, array($this, 'process_admin_options'));
			
		}
		
		/**
		 * Inits our shipping methods.
		 * 
		 * @access public
		 * @return void
		 */
		public function init() {
			
			// Instance form fields
			$this->instance_form_fields = $this->get_form_fields();
			
			// Method title - to users
			$this->title = $this->get_option('title');
			$this->tax_status = $this->get_option('tax_status');
			
		}
		
		/**
		 * Gets the form fields for our instance.
		 * 
		 * @access public
		 * @return void
		 */
		public function get_form_fields() {
			
			return array(
				
				'title' => array(
					
					'title'       => __('Method Title', 'five'),
					'type'        => 'test',
					'description' => __('Enter the delivery method title displayed to customers during checkout.', 'five'),
					'default'     => __('GoFetch Delivery', 'five'),
					'desc_tip'    => true,
					
				),
				
				'tax_status' => array(
					
					'title' 		=> __('Tax status', 'five'),
					'type' 			=> 'select',
					'class'         => 'wc-enhanced-select',
					'default' 		=> 'taxable',
					'options'		=> array(
						
						'taxable' 	=> __('Taxable', 'five'),
						'none' 		=> _x('None', 'Tax status', 'five'),
					
					),
					
				),
				
			);
			
		}
		
		/**
		 * Calculates the shipping.
		 * 
		 * @access public
		 * @param array $package
		 * @return void
		 */
		public function calculate_shipping($package = array()) {
			
			// If we can calculate shipping
			if(!WCGO()->can_calculate_shipping())
				return;
			
			// Gets the destination address as a string
			$destination_address = $package['destination']['address'].', '.$package['destination']['city'].' '.$package['destination']['state'].' '.$package['destination']['postcode'].', '.$package['destination']['country'];
			
			// Gets the distance between the store and the destination
			try {
				
				$distance = WCGO()->calculate_distance($destination_address);
				
			} catch(Exception $e) {
			
				// Calculates the distance between our pickup address and destination address
				$cost = get_option('wcgo_price_default');
				
			}
			
			if(!isset($cost)) {
			
				// Total weight order
				$total_weight = 0;
				
				// Calculates our order weight
				foreach($package['contents'] as $item) {
					
					// If one or more items do not have weight - use default item type id
					$weight = $item['data']->get_weight();
					
					if(empty($weight)) {
						
						// Set total weight as 0 - get package cost will automatically use default item type id
						$total_weight = 0;
						break;
						
					} else {
						
						$total_weight += ($weight * $item['quantity']);
						
					}
					
				}
				
				// Sets our delivery date time for package cost
				// Users not allowed to set datetime - calculate next mon-fri 12:00
				if(get_option('wcgo_enable_delivery_choice') != 'yes') {
					
					$deliverby = WCGO()->get_next_business_day()->getTimestamp()*1000;
					
				} else {
					
					// User is allowed to select delivery date
					
					// In case our autobook is not selected we set the delivery by time as 12:00
					$deliverybytime = get_option('wcgo_autobook') == 'yes' ? get_option('wcgo_autobook_time', '12:00') : '12:00';
				
					// Grabs the date selected by the user
					foreach(WCGO()->get_available_delivery_dates() as $value => $label) {
						
						// Selected date by the user
						if($value == WC()->session->get('wcgo-delivery-date')) {
							
							// If its asap
							if(strpos($value, 'asap') !== false)
								$deliverby = '';
							else
								$deliverby = $value;
							
						}
						
					}
					
					// Ensures deliver by isnt empty
					if(empty($deliverby))
						$deliverby = WCGO()->get_next_business_day()->format('Y-m-d');
					
					// Appends the time to be delivered by and creates our datetime object
					$deliverby = new DateTime($deliverby." $deliverybytime:00", new DateTimezone(WCGO()->get_timezone()));
					$deliverby = $deliverby->format('Y-m-d H:i:s P');
					
				}
				
				// Delivery by hasn
				
				// Calculates our cost
				try {
				
					// Calculates our price base on our distance and our weight
					$cost = WCGO()->get_package_cost($distance, $total_weight, $deliverby);
					
					// If we have a price buffer
					if(get_option('wcgo_price_buffer') && get_option('wcgo_price_buffer') > 0) {
						
						$buffer = (get_option('wcgo_price_buffer') / 100) * $cost;
						$cost += $buffer;
						
					}
				
				} catch(Exception $e) {
					
					$cost = get_option('wcgo_price_default');
					
				}
			
			}
			
			// Adds our rate
			$this->add_rate(array(
				
				'id' => $this->get_rate_id(),
				'label' => $this->title,
				'cost' => $cost,
				'package' => $package,
				
			));
			
		}
		
	}

?>
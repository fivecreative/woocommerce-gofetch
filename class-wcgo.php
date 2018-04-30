<?php
/**
* WCGO Main class
*
* @version 	1.0.7
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Classes
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	/**
	 * WCGO class.
	 */
	class WCGO {
		
		/**
		* WCGO instance object
		*/
		protected static $_instance = null;
		
		/**
		 * @var string
		 * @access protected
		 */
		protected $user;
		
		/**
		 * @var string
		 * @access protected
		 */
		protected $pass;

		/**
		* Main WCGO Instance
		*
		* Ensures only one instance of WCGO is loaded or can be loaded.
		*
		* @static
		* @return WCGO - instance
		*/
		public static function instance() {
			
			if(is_null(self::$_instance)) {
				
				self::$_instance = new self();
			
			}
			
			return self::$_instance;
		
		}
		
		/**
		 * __construct function.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			
			// Loads our user and pass
			$this->user = get_option('wcgo_user');
			$this->pass = get_option('wcgo_pass');
			
		}
		
		/**
		 * Gets our gofetch endpoint.
		 * 
		 * @access protected
		 * @return string
		 */
		protected function get_endpoint($token = false, $api_version = 'v1') {
			
			// If test mode and token
			if($this->test_mode() && $token)
				return "http://go-fetch.staging.c66.me/public_api/$api_version";
				
			// If live token
			if(!$this->test_mode() && $token)
				return "https://go-fetch.com.au/public_api/$api_version";
				
			// Test mode
			if($this->test_mode())
				return "http://go-fetch.staging.c66.me/api/$api_version";
				
			// Live mode
			return "https://go-fetch.com.au/api/$api_version";
			
		}
		
		/**
		 * Whether or not we are in test mode.
		 * 
		 * @access public
		 * @return bool
		 */
		public function test_mode() {
			
			if(isset($this->test_mode))
				return $this->test_mode;
				
			if(get_option('wcgo_sandbox') == 'yes')
				$this->test_mode = true;
			else
				$this->test_mode = false;
				
			return $this->test_mode;
			
		}
		
		/**
		 * Verifies our username.
		 * 
		 * @access public
		 * @param string $user
		 * @return bool
		 */
		public function verify_user($user) {
			
			if($user !== $this->user)
				return false;
				
			return true;
			
		}
		
		/**
		 * Verifies our password.
		 * 
		 * @access public
		 * @param string $pass
		 * @return bool
		 */
		public function verify_pass($pass) {
			
			if($pass !== $this->pass)
				return false;
				
			return true;
			
		}
		
		/**
		 * Verifies our credentials have access to gofetch.
		 * 
		 * @access public
		 * @param string $user
		 * @param string $pass
		 * @return bool
		 */
		public function verify_credentials($user = '', $pass = '', $testmode = false) {
			
			// Temporarily sets our user and pass
			$this->user = $user;
			$this->pass = $pass;
			$this->test_mode = $testmode;
			
			// Attempts to get a token from the passed credentials
			try {
				
				$this->get_session_token(false);
				
			} catch(Exception $e) {
				
				// Restores our old username and password
				$this->user = get_option('wcgo_user');
				$this->pass = get_option('wcgo_pass');
				
				return false;
				
			}
				
			// Restores our old username and password
			$this->user = get_option('wcgo_user');
			$this->pass = get_option('wcgo_pass');
			
			return true;
			
		}
		
		/**
		 * Verifie we have access to the google maps api with the given key.
		 * 
		 * @access public
		 * @param string $key
		 * @return void
		 */
		public function verify_google_maps_key($key) {
			
			// Does a simple query to ensure our key is valid - Geocode
			$request = wp_remote_post(add_query_arg(array(
				
				'address' => '1 Flinders Street, Melbourne VIC 3000 Australia',
				'key' => $key,
				
			), 'https://maps.googleapis.com/maps/api/geocode/json'), array(
				
				'timeout' => 20,
				
			));
			
			// If not 200
			if(wp_remote_retrieve_response_code($request) !== 200)
				throw new Exception(sprintf(__('%s - %s', 'five'), wp_remote_retrieve_response_code($request), wp_remote_retrieve_response_message($request)));
				
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(!empty($body['error_message']))
				throw new Exception($body['error_message']);
				
			// Verifies our distance matrix api
			$request = wp_remote_post(add_query_arg(array(
				
				'origins' => '1 Flinders Street, Melbourne VIC 3000 Australia',
				'destinations' => '173 Victoria Parade, Fitzroy VIC 3065 Australia',
				'key' => $key,
				
			), 'https://maps.googleapis.com/maps/api/distancematrix/json'), array(
				
				'timeout' => 20,
				
			));
			
			// If not 200
			if(wp_remote_retrieve_response_code($request) !== 200)
				throw new Exception(sprintf(__('%s - %s', 'five'), wp_remote_retrieve_response_code($request), wp_remote_retrieve_response_message($request)));
				
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(!empty($body['error_message']))
				throw new Exception($body['error_message']);
				
			// Google places API
			$request = wp_remote_post(add_query_arg(array(
				
				'query' => '1 Flinders Street, Melbourne VIC 3000 Australia',
				'key' => $key,
				
			), 'https://maps.googleapis.com/maps/api/place/textsearch/json'), array(
				
				'timeout' => 20,
				
			));
			
			// If not 200
			if(wp_remote_retrieve_response_code($request) !== 200)
				throw new Exception(sprintf(__('%s - %s', 'five'), wp_remote_retrieve_response_code($request), wp_remote_retrieve_response_message($request)));
				
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(!empty($body['error_message']))
				throw new Exception($body['error_message']);
			
			return true;
			
		}
		
		/**
		 * Ensures we have enough information to reach gofetch.
		 * 
		 * @access public
		 * @return bool
		 */
		public function is_valid() {
			
			if(empty($this->user) || empty($this->pass))
				return false;
				
			return true;
			
		}
		
		/**
		 * Gets the available contact ids from go fetch.
		 * 
		 * @access public
		 * @return array
		 */
		public function get_available_contact_ids() {
			
			// Ensures our credentials are valid
			if(!$this->is_valid()) {
				
				return array(
					
					'' => __('Invalid go fetch credentials.', 'five'),
					
				);
				
			}
			
			$contacts = array();
			
			// Does our query
			try {
				
				$request = wp_remote_get($this->get_endpoint().'/my/contacts.json', array(
					
					'timeout' => 30,
					'headers' => array(
						
						'X-User-Email' => $this->user,
						'X-User-Token' => $this->get_session_token(),
						
					),
					
				));
				
				// Bad request
				if(wp_remote_retrieve_response_code($request) !== 200)
					throw new Exception(__('Bad request.', 'five'));
					
				// Gets our body
				$body = json_decode(wp_remote_retrieve_body($request), true);
				
				// No contacts
				if(empty($body['contacts'])) {
					
					$contacts = array(
						
						'' => __('No contacts found within gofetch.', 'five'),
						
					);
					
					return $contacts;
					
				} else {
					
					$contacts = array(
						
						'' => __('&mdash; Select a pickup contact.', 'five'),
						
					);
					
					// Populates our contacts
					foreach($body['contacts'] as $contact) {
						
						$contacts[$contact['id']] = $contact['name'];
						
					}
					
				}
				
			} catch(Exception $e) {
				
				return $contacts;
				
			}
			
			return $contacts;
			
		}
		
		/**
		 * Gets the available credit card ids from go fetch.
		 * 
		 * @access public
		 * @return array
		 */
		public function get_available_credit_card_ids() {
			
			// Ensures our credentials are valid
			if(!$this->is_valid()) {
				
				return array(
					
					'' => __('Invalid go fetch credentials.', 'five'),
					
				);
				
			}
			
			$ccs = array();
			
			// Does our query
			try {
				
				$request = wp_remote_get($this->get_endpoint().'/users/current.json', array(
					
					'timeout' => 30,
					'headers' => array(
						
						'X-User-Email' => $this->user,
						'X-User-Token' => $this->get_session_token(),
						
					),
					
				));
				
				// Bad request
				if(wp_remote_retrieve_response_code($request) !== 200)
					throw new Exception(__('Bad request.', 'five'));
					
				// Gets our body
				$body = json_decode(wp_remote_retrieve_body($request), true);
				
				// No contacts
				if(empty($body['credit_cards'])) {
					
					$ccs = array(
						
						'' => __('&mdash; No credit cards found within gofetch.', 'five'),
						
					);
					
					return $ccs;
					
				} else {
					
					$ccs = array(
						
						'' => __('&mdash; Select a credit card to be used for pickups.', 'five'),
						
					);
					
					// Populates our contacts
					foreach($body['credit_cards'] as $cc) {
						
						$ccs[$cc['id']] = ucfirst($cc['brand'].' ending in '.$cc['last_4_digits']);
						
					}
					
				}
				
			} catch(Exception $e) {
				
				return $ccs;
				
			}
			
			return $ccs;
			
		}
		
		/**
		 * Gets the available item types from gofetch.
		 * 
		 * @access public
		 * @return array
		 */
		public function get_available_item_types_ids() {
			
			// Ensures our credentials are valid
			if(!$this->is_valid()) {
				
				return array(
					
					'' => __('Invalid go fetch credentials.', 'five'),
					
				);
				
			}
			
			$item_types = array();
			
			// Does our query
			try {
				
				$request = wp_remote_get($this->get_endpoint().'/item_types.json', array(
					
					'timeout' => 30,
					'headers' => array(
						
						'X-User-Email' => $this->user,
						'X-User-Token' => $this->get_session_token(),
						
					),
					
				));
				
				// Bad request
				if(wp_remote_retrieve_response_code($request) !== 200)
					throw new Exception(__('Bad request.', 'five'));
					
				// Gets our body
				$body = json_decode(wp_remote_retrieve_body($request), true);
				
				// No contacts
				if(empty($body['item_types'])) {
					
					$item_types = array(
						
						'' => __('No item types found within gofetch.', 'five'),
						
					);
					
					return $item_types;
					
				} else {
					
					// Populates our contacts
					foreach($body['item_types'] as $item) {
						
						$item_types[$item['id']] = ucfirst($item['name']);
						
					}
					
				}
				
			} catch(Exception $e) {
				
				return $item_types;
				
			}
			
			return $item_types;
			
		}
		
		/**
		 * Gets our session token.
		 * 
		 * @access protected
		 * @param bool $cache (default: true)
		 * @return string
		 */
		protected function get_session_token($cache = true) {
			
			// If already in our cache
			if(get_transient('wcgo_session_token') && $cache)
				return get_transient('wcgo_session_token');
				
			// Does our query
			$request = wp_remote_post($this->get_endpoint(true).'/sessions', array(
				
				'timeout' => 30,
				'body' => array(
					
					'email' => $this->user,
					'password' => $this->pass,
					
				),
				
			));
			
			// Something happened
			if(wp_remote_retrieve_response_code($request) !== 201)
				throw new Exception(__('Could not retrieve session token', 'five'));
				
			// Gets our response data
			$response = json_decode(wp_remote_retrieve_body($request), true);
			
			// Ensures we have our token
			if(empty($response['authentication_token']))
				throw new Exception(__('Failed to fetch session token from server response.', 'five'));
				
			// Saves our token
			set_transient('wcgo_session_token', $response['authentication_token'], HOUR_IN_SECONDS);
				
			// Returns our session token
			return $response['authentication_token'];
			
		}
		
		/**
		 * Verifies we can calculate shipping.
		 * 
		 * @access public
		 * @return bool
		 */
		public function can_calculate_shipping() {
			
			// Checks we have our session token
			try {
				
				// session token
				$this->get_session_token();
				
				// Ensures we have our coordinates
				if(!get_option('wcgo_pickup_location', '') || get_option('wcgo_pickup_location', '') == '')
					throw new Exception('');
				
			} catch(Exception $e) {
				
				return false;
				
			}
			
			return true;
			
		}
		
		/**
		 * Gets the pickup address latitude.
		 * 
		 * @access public
		 * @return string
		 */
		public function get_pickup_lat() {
			
			$coords = get_option('wcgo_pickup_location', '');
			
			if(strpos($coords, '|') !== false) {
				
				$parts = explode('|', $coords);
				
				return $parts[0];
				
			}
			
			return '';
			
		}
		
		/**
		 * Gets the pickup address latitude.
		 * 
		 * @access public
		 * @return string
		 */
		public function get_pickup_lng() {
			
			$coords = get_option('wcgo_pickup_location', '');
			
			if(strpos($coords, '|') !== false) {
				
				$parts = explode('|', $coords);
				
				return $parts[1];
				
			}
			
			return '';
			
		}
		
		/**
		 * Gets the surburb name for the pickup address.
		 * 
		 * @access public
		 * @return string
		 */
		public function get_pickup_suburb() {
			
			return get_option('wcgo_pickup_address_suburb', '');
			
		}
		
		/**
		 * Gets the surburb name for the pickup address.
		 * 
		 * @access public
		 * @return string
		 */
		public function get_pickup_postcode() {
			
			return get_option('wcgo_pickup_address_postcode', '');
			
		}
		
		/**
		 * Calculates the distance between the store and the destination.
		 * 
		 * @access public
		 * @param string $address
		 * @return int
		 */
		public function calculate_distance($address) {
			
			// Tries to get from cache
			if(get_transient('wcgo_distlo_'.md5($address)))
				return get_transient('wcgo_distlo_'.md5($address));
				
			// Does our query
			$request = wp_remote_get(add_query_arg(array(
				
				'origins' => $this->get_pickup_lat().','.$this->get_pickup_lng(),
				'destinations' => $address,
				'key' => get_option('wcgo_gmaps_api_key'),
				
			), 'https://maps.googleapis.com/maps/api/distancematrix/json'));
		
			// Could not retrieve it
			if(wp_remote_retrieve_response_code($request) != 200)
				throw new Exception('Could not retrieve distance');
		
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(empty($body['rows'][0]['elements'][0]['distance']['value']))
				throw new Exception('Could not retrieve distance');
				
			$distance = $body['rows'][0]['elements'][0]['distance']['value']; 
			
			// Looks up latitude and longitude for destination address
			$request = wp_remote_get(add_query_arg(array(
				
				'address' => $address,
				'key' => get_option('wcgo_gmaps_api_key'),
				
			), 'https://maps.googleapis.com/maps/api/geocode/json'));
		
			// Could not retrieve it
			if(wp_remote_retrieve_response_code($request) != 200)
				throw new Exception('Could not retrieve latitude and longitude for address');
		
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(empty($body['results']) || empty($body['results'][0]['geometry']['location']))
				throw new Exception('Could not retrieve latitude and longitude for address');
				
			$return = array(
				
				'distance' => $distance,
				'location' => $body['results'][0]['geometry']['location'],
				
			);
				
			// Sets our transient
			set_transient('wcgo_distlo_'.md5($address), $return, DAY_IN_SECONDS);
			
			return $return;
			
		}
		
		/**
		 * Gets our gofetch package total cost given the distance and the weight.
		 * 
		 * @access public
		 * @param int $distance
		 * @param int $weight
		 * @return int
		 */
		public function get_package_cost($distance, $weight = 0, $delivery_datetime = '', $jobdata) {
			
			// Builds our request params
			$body = array(
				
				'deliver_by' => urlencode($delivery_datetime),
				'distance_meters' => $distance,
				'suburb_name' => urlencode($jobdata['suburb_name']),
				'postcode' => urlencode($jobdata['postcode']),
				'lat' => $jobdata['lat'],
				'lon' => $jobdata['lng'],
				
			);
			
			// Delivery by date
			if(empty($body['deliver_by']))
				$body['deliver_by'] = null;
			
			// Calculate by weight or item type id
			if(!empty($weight))
				$body['item_weight'] = $weight;
			else
				$body['item_type_id'] = get_option('wcgo_item_type', '');

			// Does our request
			$request = wp_remote_get(add_query_arg($body, $this->get_endpoint(false, 'v2').'/jobs/calculate.json'), array(
				
				'timeout' => 20,
				'headers' => array(
						
						'X-User-Email' => $this->user,
						'X-User-Token' => $this->get_session_token()
						
					),
				
			));
		
			// Could not retrieve it
			if(wp_remote_retrieve_response_code($request) != 200)
				throw new Exception('Could not retrieve price');
				
			// Gets our response body
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(empty($body['price_cents']))
				throw new Exception('Could not retrieve price');
				
			return round(($body['price_cents'] / 100), 2);
			
		}
		
		/**
		 * Gets todays datetime.
		 * 
		 * @access public
		 * @return object
		 */
		public function today() {
			
			return new DateTime('27-04-2018 12:00:00', new DateTimezone($this->get_timezone()));
			
		}
		
		/**
		 * Gets the next business day delivery datetime.
		 * 
		 * @access public
		 * @return void
		 */
		public function get_next_business_day() {
			
			$date = $this->today();
			$date->modify('+24 hours');
			
			// Checks tomorrow is a mon-fri
			while(date('N', $date->getTimestamp()) >= 6) {
				
				$date->modify('+24 hours');
				
			}
				
			return $date;
			
		}
		
		/**
		 * Gets the avialable delivery dates for GoFetch.
		 * 
		 * @access public
		 * @return array
		 */
		public function get_available_delivery_dates() {
			
			// Our dates array
			$dates = array();
			$days_ahead = 6;
			
			// Gets the day today
			try {
				
				$today = new DateTime('now', new DateTimezone($this->get_timezone()));
				
			} catch(Exception $e) {
				
				return array();
				
			}
			
			// Checks asap deliveries
			if(get_option('wcgo_asap_delivery') == 'yes') {
				
				// Same day delivery is allowed - check the day of the week
				if(get_option('wcgo_delivery_day_'.strtolower($today->format('l'))) == 'yes') {
					
					// Checks our cutoff time
					if(get_option('wcgo_asap_delivery_cutoff') != '') {
						
						// Attempts to create our cutoff datetime
						try {
							
							$cutoff = new DateTime($today->format('Y-m-d '.get_option('wcgo_asap_delivery_cutoff').':00'), new DateTimezone($this->get_timezone()));
							
							// If our cutoff is in the future
							if($cutoff->getTimestamp() > $today->getTimestamp()) {
								
								// Checks if we have an extra fee for this
								if(get_option('wcgo_asap_delivery_surcharge') != '' && get_option('wcgo_asap_delivery_surcharge') > 0)
									$suffix = ' (+'.wc_price(get_option('wcgo_asap_delivery_surcharge')).')';
								else
									$suffix = '';
								
								$dates[$today->format('Y-m-d').'_asap'] = array(
								
									'title' => __('ASAP', 'five').$suffix,
									'surcharge' => get_option('wcgo_asap_delivery_surcharge'),
									
								);
								
							}
							
						} catch(Exception $e) {
							
							
							
						}
						
					}
					
				}
				
			}
			
			// Lets check same day delivery
			if(get_option('wcgo_same_day_delivery') == 'yes') {
				
				// Same day delivery is allowed - check the day of the week
				if(get_option('wcgo_delivery_day_'.strtolower($today->format('l'))) == 'yes') {
					
					// Checks our cutoff time
					if(get_option('wcgo_same_day_delivery_cutoff') != '') {
						
						// Attempts to create our cutoff datetime
						try {
							
							$cutoff = new DateTime($today->format('Y-m-d '.get_option('wcgo_same_day_delivery_cutoff').':00'), new DateTimezone($this->get_timezone()));
							
							// If our cutoff is in the future
							if($cutoff->getTimestamp() > $today->getTimestamp()) {
								
								// Checks if we have an extra fee for this
								if(get_option('wcgo_same_day_delivery_surcharge') != '' && get_option('wcgo_same_day_delivery_surcharge') > 0)
									$suffix = ' (+'.wc_price(get_option('wcgo_same_day_delivery_surcharge')).')';
								else
									$suffix = '';
								
								$dates[$today->format('Y-m-d')] = array(
								
									'title' => __('Today', 'five').$suffix,
									'surcharge' => get_option('wcgo_same_day_delivery_surcharge'),
									
								);
								
							}
							
						} catch(Exception $e) {
							
							
							
						}
						
					}
					
				}
				
			}
			
			// Checks next day delivery
			$today->modify('+1 day');
			
			// Lets check next day delivery
			if(get_option('wcgo_next_day_delivery') == 'yes') {
				
				// next day delivery is allowed - check the day of the week
				if(get_option('wcgo_delivery_day_'.strtolower($today->format('l'))) == 'yes') {
					
					// Checks our cutoff time
					if(get_option('wcgo_next_day_delivery_cutoff') != '') {
						
						// Attempts to create our cutoff datetime
						try {
							
							$cutoff = new DateTime($today->format('Y-m-d '.get_option('wcgo_next_day_delivery_cutoff').':00'), new DateTimezone($this->get_timezone()));
							
							// If our cutoff is in the future
							if($cutoff->getTimestamp() > $today->getTimestamp()) {
								
								// Checks if we have an extra fee for this
								if(get_option('wcgo_next_day_delivery_surcharge') != '' && get_option('wcgo_next_day_delivery_surcharge') > 0)
									$suffix = ' (+'.wc_price(get_option('wcgo_next_day_delivery_surcharge')).')';
								else
									$suffix = '';
								
								$dates[$today->format('Y-m-d')] = array(
								
									'title' => __('Tomorrow', 'five').$suffix,
									'surcharge' => get_option('wcgo_next_day_delivery_surcharge'),
									
								);
								
							}
							
						} catch(Exception $e) {
							
							
							
						}
						
					}
					
				}
				
			}
			
			// Checks next day delivery
			$today->modify('+1 day');
			
			// Lets loopo our days ahead
			for($i = 0; $i < $days_ahead; $i++) {
				
				// If today is an available day of the week
				if(get_option('wcgo_delivery_day_'.strtolower($today->format('l'))) != 'yes') {
					
					$today->modify('+1 day');
					continue;
					
				}
				
				$dates[$today->format('Y-m-d')] = array(
				
					'title' => $today->format('l, j F'),

				);
				
				$today->modify('+1 day');
				
			}
			
			return $dates;
			
		}
		
		/**
		 * Gets the timezone.
		 * 
		 * @access public
		 * @return string
		 */
		public function get_timezone() {
			
			$timezone = get_option('timezone_string');
			
			if(empty($timezone))
				return 'UTC';
			else
				return $timezone;
			
		}
		
		/**
		 * Gets information of a delivery.
		 * 
		 * @access public
		 * @return void
		 */
		public function get_delivery($delivery_id) {
			
			// Does our request
			$request = wp_remote_get($this->get_endpoint().'/my/customer/jobs/'.$delivery_id, array(
				
				'timeout' => 20,
				'headers' => array(
					
					'X-User-Email' => $this->user,
					'X-User-Token' => $this->get_session_token(),
					
				),
				
			));
			
			// Could not get job status
			if(wp_remote_retrieve_response_code($request) !== 200)
				throw new Exception('Could not fetch job id.');
				
			// Gets our response body
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(empty($body['job']))
				throw new Exception('Could not retrieve job information.');
				
			return $body;
			
		}
		
		/**
		 * Gets the delivery status for a given job ID.
		 * 
		 * @access public
		 * @param string $delivery_id
		 * @return array
		 */
		public function get_delivery_status($delivery_id) {
				
			// Gets our delivery information
			try {
				
				$body = $this->get_delivery($delivery_id);
			
				// Checks our job status
				$status = isset($body['job']['state']) ? $body['job']['state'] : 'Unreachable';
				
				// Sets our transient
				set_transient('wcgo_delivery_status_'.md5($delivery_id), $status, 60);
				
				return $body['job']['state'];
				
			} catch(Exception $e) {
				
				return 'Unreachable';
				
			}
			
		}
		
		/**
		 * Gets the order address form the order id.
		 * 
		 * @access public
		 * @param mixed $order_id
		 * @return string
		 */
		public function get_order_address($order_id) {
			
			// GEtst he orders formatted address
			$order = wc_get_order($order_id);
			
			// Order address
			$address = $order->get_shipping_address_1().', '.$order->get_shipping_city().' '.$order->get_shipping_state().' '.$order->get_shipping_postcode().', '.$order->get_shipping_country();
			
			return $address;
			
		}
		
		/**
		 * Gets the location coordinates for a given order.
		 * 
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		public function get_order_location($order_id) {
			
			// Order address
			$address = $this->get_order_address($order_id);
			
			// Address location transient
			if(get_transient('wcgo_addr_location_'.md5($address)))
				return get_transient('wcgo_addr_location_'.md5($address));
			
			// Gets the locationm from google maps places api
			$request = wp_remote_get(add_query_arg(array(
				
				'address' => $address,
				'key' => get_option('wcgo_gmaps_api_key'),
				
			), 'https://maps.googleapis.com/maps/api/geocode/json'), array(
				
				'timeout' => 20,
				
			));
			
			if(wp_remote_retrieve_response_code($request) !== 200)
				return array();
				
			// Gets the body
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			if(empty($body['results']) || empty($body['results'][0]['geometry']['location']))
				return array();
				
			// Saveso ur transient
			set_transient('wcgo_addr_location_'.md5($address), $body['results'][0]['geometry']['location'], DAY_IN_SECONDS);
			
			return $body['results'][0]['geometry']['location'];
			
		}
		
		/**
		 * Gets the latitude for a given order.
		 * 
		 * @access public
		 * @param int $order_id
		 * @return float
		 */
		public function get_order_lat($order_id) {
			
			$location = $this->get_order_location($order_id);
			
			if(isset($location['lat']))
				return $location['lat'];
				
			return 0;
			
		}
		
		/**
		 * Gets the longitude for a given order.
		 * 
		 * @access public
		 * @param int $order_id
		 * @return float
		 */
		public function get_order_lng($order_id) {
			
			$location = $this->get_order_location($order_id);
			
			if(isset($location['lng']))
				return $location['lng'];
				
			return 0;
			
		}
		
		/**
		 * Books the job for a delivery.
		 * 
		 * @access public
		 * @param int $order_id
		 * @param object $datetime
		 * @return void
		 */
		public function book_order_delivery($order_id, $datetime, $notes = '') {
			
			$order = wc_get_order($order_id);

			// Order not found
			if(!$order)
				throw new Exception(__('Order not found with ID ', 'five').$order_id);
				
			// Builds up our delivery arguments
			$body = array(
				
				'job' => array(
					
					'pickup_attributes' => array(
						
						'coordinates' => array(
							
							'lat' => $this->get_pickup_lat(),
							'lon' => $this->get_pickup_lng(),
							
						),
						'address' => get_option('wcgo_pickup_address', ''),
						'suburb_name' => $this->get_pickup_suburb(),
						'postcode' => $this->get_pickup_postcode(),
						'contact_id' => get_option('wcgo_pickup_contact_id', ''),
						
					),
					
					'dropoff_attributes' => array(
						
						'coordinates' => array(
							
							'lat' => $this->get_order_lat($order_id),
							'lon' => $this->get_order_lng($order_id),
							
						),
						'address' => $this->get_order_address($order_id),
						'suburb_name' => $order->get_shipping_city(),
						'postcode' => $order->get_shipping_postcode(),
						
					),
					
					'credit_card_id' => get_option('wcgo_credit_card', ''),
					'total_distance' => $this->calculate_distance($this->get_order_address($order_id)),
					'total_duration' => 1,
					'item_type_id' => get_option('wcgo_item_type', ''),
					'deliver_by' => $datetime->format('Y-m-d H:i:s P'),
					'notes' => $notes,
					
				),
				
				'distance_based_pricing' => true,
				
			);
			
			// If our order already has a contact
			if(get_post_meta($order->get_id(), 'wcgo_contact_id', true) != '') {
				
				$body['job']['dropoff_attributes']['contact_id'] = get_post_meta($order->get_id(), 'wcgo_contact_id', true);
				
			} else {
				
				$body['job']['dropoff_attributes']['contact_attributes'] = array(
				
					'name' => $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
					'phone' => $order->get_billing_phone(),
					
				);
				
			}
			
			// Performs our job request
			$request = wp_remote_post($this->get_endpoint().'/my/customer/jobs.json', array(
				
				'timeout' => 20,
				'headers' => array(
					
					'X-User-Email' => $this->user,
					'X-User-Token' => $this->get_session_token(),
					
				),
				'body' => $body,
				
			));
			
			// Checks the request
			if(wp_remote_retrieve_response_code($request) != 201) {
				
				throw new Exception(sprintf(__('An error occurred creating your gofetch delivery. Error Code: %s - Error Message : %s', 'five'), wp_remote_retrieve_response_code($request), wp_remote_retrieve_response_message($request)));
				
			}
			
			// Gets our body data
			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			// Could not create job
			if(empty($body['job']))
				throw new Exception('Could not create your gofetch job through the API. Response code: Job details failed to be received from the API');
				
			// Goes through our contacts and gets the new contact ID
			foreach($body['contacts'] as $contact) {
				
				// Our clinet ID
				if($contact['name'] == $order->get_shipping_first_name().' '.$order->get_shipping_last_name() && $contact['phone'] == $order->get_billing_phone()) {
					
					$client_id = $contact['id'];
					
					// Updates our client ID
					update_post_meta($order->get_id(), 'wcgo_contact_id', $client_id);
					
				}
				
			}
			
			// Updates our gofetch job id
			update_post_meta($order->get_id(), 'wcgo_delivery_id', $body['job']['id']);
			
			return true;
			
		}
		
		/**
		 * Gets the delivery date of a certain delivery id.
		 * 
		 * @access public
		 * @param string $delivery_id
		 * @return object
		 */
		public function get_delivery_date($delivery_id) {
		
			// Gets our delivery
			try {
				
				// Gets the delivery information
				$delivery = $this->get_delivery($delivery_id);
				
				// Checks our deliver_by
				if(empty($delivery['job']['deliver_by']))
					throw new Exception('Delivery by not found');
					
				try {
				
					// Creates our datetime object
					$datetime = new DateTime($delivery['job']['deliver_by']);
				
				} catch(Exception $e) {
					
					return false;
					
				}
				
				return $datetime;
				
			} catch(Exception $e) {
				
				return false;
				
			}
		
		}
		
		/**
		 * Fetches all order ids that gofetch as their shipping method.
		 * 
		 * @access public
		 * @return array
		 * @since 1.0.4
		 */
		public function get_gofetch_order_ids() {
			
			global $wpdb;
			
			// Does our SQL query
			$results = $wpdb->get_results($wpdb->prepare("
			
				SELECT 		oi.order_id, oi.order_item_name, oi.order_item_type, oim.meta_key, oim.meta_value
				
				FROM 		{$wpdb->prefix}woocommerce_order_items AS oi
				
				LEFT JOIN	{$wpdb->prefix}woocommerce_order_itemmeta AS oim
							ON oi.order_item_id = oim.order_item_id
				
				WHERE 		oi.order_item_type = %s
							AND oim.meta_key = %s
							AND oim.meta_value LIKE %s
				
			", "shipping", "method_id", "wc_gofetch:%"));
			
			// Nothing found
			if(empty($results))
				return array();
			
			// Extracts our order_ids
			$order_ids = wp_list_pluck($results, 'order_id');
			
			// Returns our array
			return $order_ids;
			
		}
		
	}

	/**
	 * Returns the main instance of WCGO to prevent the need to use globals.
	 */
	function WCGO() {
		
		return WCGO::instance();
		
	}

	WCGO();

?>
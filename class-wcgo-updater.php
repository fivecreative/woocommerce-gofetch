<?php
/**
* Handles our plugin updates bypassing the wordpress repository
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	GoFetch/Classes
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	/**
	 * WCGO_Updater class.
	 */
	class WCGO_Updater {
		
		/**
		* WCGO_Updater instance object
		*/
		protected static $_instance = null;
		
		/**
		 * @var string
		 * @access private
		 */
		private $slug;
		
		/**
		 * @var string
		 * @access private
		 */
		private $plugin_data;
		
		/**
		 * @var string
		 * @access private
		 */
		private $username;
		
		/**
		 * @var string
		 * @access private
		 */
		private $repo;
		
		/**
		 * @var string
		 * @access private
		 */
		private $plugin_file;
		
		/**
		 * @var string
		 * @access private
		 */
		private $github_api_result;
		
		/**
		 * @var string
		 * @access private
		 */
		private $access_token;

		/**
		 * Main WCGO_Updater Instance
		 *
		 * Ensures only one instance of WCGO_Updater is loaded or can be loaded.
		 *
		 * @static
		 * @param string $plugin_file
		 * @param string $github_username
		 * @param string $github_project_name
		 * @param string $access_token
		 * @return WCGO - instance
		 */
		public static function instance($plugin_file, $github_username, $github_project_name, $access_token) {
			
			if(is_null(self::$_instance)) {
				
				self::$_instance = new self($plugin_file, $github_username, $github_project_name, $access_token);
			
			}
			
			return self::$_instance;
		
		}
		
		/**
		 * Constructs our updater.
		 * 
		 * @access public
		 * @param string $plugin_file
		 * @param string $github_username
		 * @param string $github_project_name
		 * @param string $access_token
		 * @return self
		 */
		public function __construct($plugin_file, $github_username, $github_project_name, $access_token) {
			
			// Adds our filters
			add_filter('pre_set_site_transient_update_plugins', array($this, 'set_transient')); // Sets our transient with the wordpress update plugins so we can add our own repo.
			add_filter('plugins_api', array($this, 'set_plugin_info'), 10, 3); // Ensures we display information about our plugin in the modal popup of the plugins API.
			add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3); // Handles post update install functionality.
			
			// Sets variables
			$this->plugin_file = $plugin_file;
			$this->username = $github_username;
			$this->repo = $github_project_name;
			$this->access_token = $access_token;
			
		}
		
		/**
		 * Sets up our plugin data information
		 * 
		 * @access private
		 * @return void
		 */
		private function init_plugin_data() {
			
			// Our plugin slug
			$this->slug = plugin_basename($this->plugin_file);
			$this->plugin_data = get_plugin_data($this->plugin_file);
			
		}
		
		/**
		 * Gets release information about our plugin from github.
		 * 
		 * @access private
		 * @return void
		 */
		private function get_repository_release_info() {
			
			// If we already have gotten our release information
			if(!empty($this->github_api_result))
				return;
				
			// Lets query our github repository to get release information
			$this->github_api_result = wp_remote_retrieve_body(wp_remote_get("https://api.github.com/repos/{$this->username}/{$this->repo}/releases"));
			
			// If we have the result
			if(empty($this->github_api_result))
				return;
				
			$this->github_api_result = json_decode($this->github_api_result);
			
			// Only get the last release
			if(!is_array($this->github_api_result))
				return;
				
			$this->github_api_result = array_shift($this->github_api_result);
			
		}
		
		/**
		 * Pushes in our plugin information into the wordpress transient update notification.
		 * 
		 * @access public
		 * @param mixed $transient
		 * @return void
		 */
		public function set_transient($transient) {
			
			// If we have checked for data before skip this
			if(empty($transient->checked))
				return $transient;
				
			// Initiates our plugin data
			$this->init_plugin_data();
			
			// Gets release information from github
			$this->get_repository_release_info();
			
			// Checks if we need to do an update
			$needs_update = version_compare($this->github_api_result->tag_name, $transient->checked[$this->slug]);
			
			// No nned to update
			if(!$needs_update)
				return $transient;
				
			// Creates our update object to include in the transient
			$obj = new stdClass();
			$obj->slug = $this->slug; 									// Plugin slug
			$obj->new_version = $this->github_api_result->tag_name; 	// New Version
			$obj->url = $this->plugin_data['PluginURI'];				// Plugin URL (on the website
			$obj->package = $this->github_api_result->zipball_url;		// Package URL
			
			// Adds to our transient
			$transient->response[$this->slug] = $obj;
			
			// Returns our transient
			return $transient;
			
		}
		
		/**
		 * Push in plugin version information to display in the details lightbox.
		 * 
		 * @access public
		 * @param mixed $false
		 * @param mixed $action
		 * @param object $response
		 * @return void
		 */
		public function set_plugin_info($false, $action, $response) {
				
			// Initiates our plugin data
			$this->init_plugin_data();
			
			// Ensures its our time to display information
			if(empty($response->slug) || $response->slug != $this->slug)
				return false;
			
			// Gets release information from github
			$this->get_repository_release_info();
			
			// Adds our plugin information to the response object
			$response->last_updated = $this->github_api_result->published_at;
			$response->slug = $this->slug;
			$response->plugin_name  = $this->plugin_data["Name"];
			$response->version = $this->github_api_result->tag_name;
			$response->author = $this->plugin_data["AuthorName"];
			$response->homepage = $this->plugin_data["PluginURI"];
			$response->download_link = $this->github_api_result->zipball_url;
			
			// Lets process githubs parsedown
			if(!class_exists('Parsedown'))
				require(WCGO_PLUGIN_PATH.'class-parsedown.php');
				
			// Creates our tabs in the lightbox
			$response->sections = array(
				
				'description' => $this->plugin_data["Description"],
				'changelog' => class_exists('Parsedown') ? Parsedown::instance()->parse($this->github_api_result->body) : $this->github_api_result->body
				
			);
			
			// Checks and extracts the "requires" and "tested" values
			$matches = null;
			
			// "requires"
			preg_match("/requires:\s([\d\.]+)/i", $this->github_api_result->body, $matches);
			
			// We have "requires"
			if(!empty($matches) && is_array($matches) && count($matches) > 1)
				$response->requires = $matches[1];
				
			$matches = null;
			
			// "tested"
			preg_match("/tested:\s([\d\.]+)/i", $this->github_api_result->body, $matches);
			
			// We have "tested"
			if(!empty($matches) && is_array($matches) && count($matches) > 1)
				$response->tested = $matches[1];
			
			// Returns
			return $response;
			
		}
		
		/**
		 * Perform additional actions to successfully install our plugin.
		 * 
		 * @access public
		 * @param mixed $true
		 * @param mixed $hook_extra
		 * @param mixed $result
		 * @return void
		 */
		public function post_install($true, $hook_extra, $result) {
			
			// Initiates our plugin data
			$this->init_plugin_data();
			
			// Remember whether or not our plugin was activated to start with
			$was_activated = is_plugin_active($this->slug);
			
			// Since we are hosted in GitHub, our plugin folder would have a dirname of
			// reponame-tagname change it to our original one:
			
			// WP_Filesystem global
			global $wp_filesystem;
			
			// Our plugin folder
			$plugin_folder = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.dirname($this->slug);
			
			// ENsures we move our folder to its right destination
			$wp_filesystem->move($result['destination'], $plugin_folder);
			
			// Destination of our plugin folder
			$result['destination'] = $plugin_folder;
			
			// If our plugin was activated lets make sure we activate it again
			if($was_activated)
				$activate = activate_plugin($this->slug);
			
			// Returns
			return $result;
			
		}
		
	}

	/**
	 * Returns the main instance of WCGO_Updater to prevent the need to use globals.
	 */
	function WCGO_Updater($plugin_file, $github_username, $github_project_name, $access_token) {
		
		return WCGO_Updater::instance($plugin_file, $github_username, $github_project_name, $access_token);
		
	}

?>
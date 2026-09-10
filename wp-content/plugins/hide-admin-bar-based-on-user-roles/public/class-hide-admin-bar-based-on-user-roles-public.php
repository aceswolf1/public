<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://creovity.agency/
 * @since      1.7.0
 *
 * @package    hab_Hide_Admin_Bar_Based_On_User_Roles
 * @subpackage hab_Hide_Admin_Bar_Based_On_User_Roles/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    hab_Hide_Admin_Bar_Based_On_User_Roles
 * @subpackage hab_Hide_Admin_Bar_Based_On_User_Roles/public
 * @author     Ankit Panchal <wptoolsdev@gmail.com>
 */
class hab_Hide_Admin_Bar_Based_On_User_Roles_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.7.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.7.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.7.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.7.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in hab_Hide_Admin_Bar_Based_On_User_Roles_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The hab_Hide_Admin_Bar_Based_On_User_Roles_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hide-admin-bar-based-on-user-roles-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.7.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in hab_Hide_Admin_Bar_Based_On_User_Roles_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The hab_Hide_Admin_Bar_Based_On_User_Roles_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hide-admin-bar-based-on-user-roles-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Main function to handle admin bar visibility
	 * 
	 * @since 6.0.0
	 */
	public function hab_hide_admin_bar() {
		// Check if admin bar should be hidden based on various conditions
		if ($this->should_hide_admin_bar()) {
			$this->set_admin_bar_visibility(false);
		}
	}

	/**
	 * Set the visibility of the admin bar
	 * 
	 * @param bool $show Whether to show the admin bar
	 * @since 6.0.0
	 */
	private function set_admin_bar_visibility($show = true) {
		show_admin_bar($show);
	}

	/**
	 * Determine if admin bar should be hidden based on all conditions
	 * 
	 * @return bool True if admin bar should be hidden
	 * @since 6.0.0
	 */
	private function should_hide_admin_bar() {
		$settings = get_option("hab_settings", array(
			'hab_disableforall' => 'no',
			'hab_userRoles' => array(),
			'hab_capabilities' => '',
			'hab_disableforallGuests' => 'no'
		));

		// Free checks
		if (isset($settings["hab_disableforall"]) && $settings["hab_disableforall"] === 'yes') {
			return true;
		}
		if ($this->should_hide_for_user_role($settings)) {
			return true;
		}
		if ($this->should_hide_for_user_capability($settings)) {
			return true;
		}
		if ($this->should_hide_for_guests($settings)) {
			return true;
		}
	
		$pro_conditions = apply_filters('hab_pro_should_hide_admin_bar', false);
		if ($pro_conditions === true) {
			return true;
		}
	
		return false;
	}

	/**
	 * Check if admin bar should be hidden based on user role
	 * 
	 * @param array $settings Plugin settings
	 * @return bool True if admin bar should be hidden
	 * @since 6.0.0
	 */
	private function should_hide_for_user_role($settings) {
		$plgUserRoles = (isset($settings["hab_userRoles"])) ? $settings["hab_userRoles"] : "";
		
		if (is_array($plgUserRoles)) {
			$curUserObj = wp_get_current_user();
			if (array_intersect($plgUserRoles, $curUserObj->roles)) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Check if admin bar should be hidden based on user capability
	 * 
	 * @param array $settings Plugin settings
	 * @return bool True if admin bar should be hidden
	 * @since 6.0.0
	 */
	private function should_hide_for_user_capability($settings) {
		$hab_capabilities = (isset($settings["hab_capabilities"])) ? explode(",", $settings["hab_capabilities"]) : "";
		
		if (is_array($hab_capabilities)) {
			foreach ($hab_capabilities as $caps) {
				if (current_user_can($caps)) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Check if admin bar should be hidden for guests
	 * 
	 * @param array $settings Plugin settings
	 * @return bool True if admin bar should be hidden
	 * @since 6.0.0
	 */
	private function should_hide_for_guests($settings) {
		$hab_disableforallGuests = (isset($settings["hab_disableforallGuests"])) ? $settings["hab_disableforallGuests"] : "";
		
		if ($hab_disableforallGuests == 'yes' && !is_user_logged_in()) {
			return true;
		}
		
		return false;
	}

}

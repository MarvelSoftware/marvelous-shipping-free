<?php

/**
* Plugin Name: Marvelous Shipping
* Plugin URI: https://mrvlsol.co.il/?marvelous_shipping_details&utm_source=wp_admin_free_ver/
* Description: תוסף חכם ומתקדם לניהול מחירי משלוחים בישראל עבור WooCommerce. מתאים לעסקים בכל הגדלים, עם אפשרויות התאמה אישית, קלות, ופשוטות.
* Version: 1.0
* Requires at least: 5.2
* Requires PHP:      7.0
* Requires Plugins: woocommerce
* Author: Marvel Software Solutions
* Author URI: https://mrvlsol.co.il/?utm_source=wp_admin_free_ver
* Developer:         Marvel Software Solutions
* License:           GNU General Public License v2.0
* License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
* Text Domain:       marvelous-shipping
* Domain Path:       /languages
*
* WC requires at least: 8.0
* WC tested up to: 8.3
*/

/**
 * Die if accessed directly
 */
defined( 'ABSPATH' ) or die( __('You can not access this file directly!', 'marvelous-shipping-for-woocommerce') );


// =======================================================================================
// requires 
// =======================================================================================

require_once(plugin_dir_path(__FILE__) . "php" . DIRECTORY_SEPARATOR . "constants.php");
require_once(plugin_dir_path(__FILE__) . "php" . DIRECTORY_SEPARATOR . "kernel.php");
require_once(plugin_dir_path(__FILE__) . "php" . DIRECTORY_SEPARATOR . "shipping_method.php");
require_once(plugin_dir_path(__FILE__) . "php" . DIRECTORY_SEPARATOR . "scriber.php");
require_once(plugin_dir_path(__FILE__) . "php" . DIRECTORY_SEPARATOR . "marvel_init.php");
if (file_exists((plugin_dir_path(__FILE__) . "cities" . DIRECTORY_SEPARATOR . "site_cities.php"))) {
    require_once((plugin_dir_path(__FILE__) . "cities" . DIRECTORY_SEPARATOR . "site_cities.php"));
}

// =======================================================================================
// activation hooks
// =======================================================================================

// deactivation hook
register_deactivation_hook(__FILE__, 'marvelous_shipping_deactivate');
// uninstall hook
register_uninstall_hook(__FILE__, 'marvelous_shipping_remove_cleanup');
// activation hook
register_activation_hook(__FILE__, 'marvelous_shipping_activate');
// plugin row Free Text
add_action('admin_head', 'add_ultimate_label_to_plugin_row', 10, 3);

// =======================================================================================
// =======================================================================================

/**
 * Check if WooCommerce is active
 */
if ( (is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', array()))) ||
    in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

    class MarvelousShipping {

        // =========================================================================================
        // Construct class
        // =========================================================================================

        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init') );
        }

        // =========================================================================================
        // WC init
        // =========================================================================================
        public function init() {
            try{
                if (!file_exists(normalize_path(plugin_dir_path(__FILE__) . "cities/site_cities.php"))) {
                    generate_site_cities_file();
                    include_once(normalize_path(plugin_dir_path(__FILE__) . "cities/site_cities.php"));
                }

                $this->enqueue_scripts();
                add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );
            
                // remove optionl text
                add_filter('woocommerce_form_field', [$this, 'remove_optional_text'] , 10, 3);

                // settings area
                add_filter('woocommerce_settings_tabs_array', [$this,'marvelous_shipping_add_settings_tab'], 50);
                add_action('woocommerce_settings_tabs_marvelous_shipping_plugin_settings',  [$this,'marvelous_shipping_plugin_settings_tab_content']);
                // WP settings links
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_settings_links']);

                // add new shipping method for WC:
                add_action('woocommerce_thankyou', [$this, 'log_order_to_marvelous_shipping_table'], 10, 1);

                // show shipping fees msg to the user in the checkout
                add_action('woocommerce_review_order_before_order_total', [$this, 'add_extra_fees_notification'], 10);


                // ================================
                // AJAX handlers
                // ================================
                // frontend - update cities file 
                add_action('wp_ajax_get_site_cities', [$this, 'handle_get_site_cities']);
                add_action('wp_ajax_nopriv_get_site_cities', [$this, 'handle_get_site_cities']);

                // save user settings
                add_action('wp_ajax_marvelous_shipping_save_settings', [$this,'marvelous_shipping_save_settings_wrapper']);

                // update shipping price
                add_filter('woocommerce_package_rates', [$this, 'update_shipping_cost_based_on_city'], 10, 2);

                // reset update status ajax
                add_action('wp_ajax_reset_update_status', [$this, 'reset_update_status']);
                
                // save floor number for calculating the extra fees
                add_action('woocommerce_checkout_create_order', [$this, 'update_order_prices'], 10, 2);
        
                // frontend diagnostics
                add_action('wp_ajax_mrvl_send_diagnostics', [$this, 'mrvl_send_diagnostics_callback']);
                add_action('wp_ajax_nopriv_mrvl_send_diagnostics', [$this, 'mrvl_send_diagnostics_callback']); // Allow non-logged-in users

                // init checkout fields (frontend)
                $this->init_fields();
                // $this->init_states();
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }
        
        }

        // =========================================================================================
        // send diagnostics
        // =========================================================================================

        public function mrvl_send_diagnostics_callback() {
            try {
                // Verify nonce
                if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'mrvl_front_nonce') && !wp_verify_nonce($_POST['nonce'], 'mrvl_nonce_action'))) {
                    wp_send_json_error(false);
                    return;
                }

                global $product_package;

                // Validate input
                if (empty($_POST['message'])) {
                    wp_send_json_error(['message' => 'Invalid request'], 400);
                    return;
                }

                $current_time = current_time('mysql'); // WordPress function to get the current time

                // Convert the data to a string for logging purposes
                $logMessage = sprintf(
                    "Exception in front-end:\n" .
                    "Message: %s\n" .
                    "Site URL: %s\n" .
                    "Timestamp: %s\n" .
                    "Plugin Version: %s\n" .
                    "WordPress Version: %s\n" .
                    "WooCommerce Version: %s\n" .
                    "PHP Version: %s\n" .
                    "Stack Trace:\n%s\n",
                    sanitize_text_field($_POST['message']),
                    home_url(),
                    $current_time,
                    $product_package,
                    get_bloginfo('version'),
                    defined('WC_VERSION') ? WC_VERSION : 'N/A',
                    phpversion(),
                    sanitize_text_field($_POST['stack_trace'])
                );

                // Log locally
                marvelLog("Frontend Error Report: " . $logMessage);
            
                // Send to the diagnostics server (same function as before)
                send_diagnostics($logMessage);
                // Respond with success
                wp_send_json_success(['message' => 'Diagnostics received']);
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__, false);
            }
        }
        // =========================================================================================
        // add order to marvel db
        // =========================================================================================

        public function reset_update_status() {
             // Verify nonce
             if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrvl_nonce_action')) {
                wp_send_json_error(false);
                return;
            }

            update_option('marvelous_shipping_mrvl_updating_plugin', 0);
            update_option('marvelous_shipping_mrvl_update_plugin_status', '');
            delete_installer();
            
            wp_send_json_success(true);
        }

        // =========================================================================================
        // add order to marvel db
        // =========================================================================================

        public function update_order_prices($order, $data) {
            // Retrieve the custom shipping cost and label from the session
            $custom_shipping_cost = WC()->session->get('custom_shipping_cost');
            $custom_shipping_label = WC()->session->get('custom_shipping_label');
        
            // Ensure the values exist before adding them to the order
            if ($custom_shipping_cost !== null && $custom_shipping_label !== null) {
                foreach ($order->get_items('shipping') as $item_id => $shipping_item) {
                    $shipping_item->set_total($custom_shipping_cost); // Update the shipping cost
                    $shipping_item->set_name($custom_shipping_label); // Update the shipping label
                }
            }
        }

        // =========================================================================================
        // add order to marvel db
        // =========================================================================================
        public function log_order_to_marvelous_shipping_table($order_id) {
            try{
                if (!$order_id){
                    return;
                };
                kernel_log_order_to_marvelous_shipping_table($order_id);

            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }
        }
        
        // =========================================================================================
        // 
        // =========================================================================================
        public function marvelous_shipping_save_settings_wrapper() {
            try{
                // Check nonce
                $response = [
                    'success' => false,
                    'errors' => [],
                    'num_errors' => 0,
                    'messages' => [],
                ];
                if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrvl_nonce_action')) {
                    $response['errors'][] = 'Invalid nonce';
                    $response['num_errors']++;
                    wp_send_json_error($response);
                }

                $response = marvelous_shipping_save_settings();
                if($response['num_errors']>0){
                    wp_send_json_error($response);
                }


                wp_send_json($response);
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }

           
        }

        // =========================================================================================
        // Add a custom tab to the WooCommerce settings
        // =========================================================================================

        public function update_shipping_cost_based_on_city($rates, $package) {
            try{
                return kernel_update_shipping_cost_based_on_city($rates, $package);
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $rates;
            }

        }

        // =========================================================================================
        // Add a custom tab to the WooCommerce settings
        // =========================================================================================
        public function add_extra_fees_notification() {
            try{
                // get the message
                $message = kernel_add_extra_fees_notification();
                // Only show the message if it's not empty
                if (!empty($message)) {
                    echo '<div class="custom-shipping-fees-message" style="display: none;">';
                    echo '<p>' . nl2br( $message )  . '</p>'; // Format the message
                    echo '</div>';
                }
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }
        }
        
        // =========================================================================================
        // Add a custom tab to the WooCommerce settings
        // =========================================================================================

        public function marvelous_shipping_add_settings_tab($tabs) {
            try {
                $tabs['marvelous_shipping_plugin_settings'] = "MarvelousShipping"; // Tab label
                return $tabs;
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $tabs;
            }
        }
        // =========================================================================================
        // Add a custom tab to the WooCommerce settings
        // =========================================================================================

        public function custom_shipping_method_label($label, $method) {
            try{
                // Customize the label for specific shipping methods
                if ($method->method_id === 'flat_rate') {
                    $label = 'לפי כתובת'; // Set your custom label
                }
            
                return $label;
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $label;
            }

        }
        
        // =========================================================================================
        // Add a custom tab to the WooCommerce settings
        // =========================================================================================

        public function handle_get_site_cities() {
            try {
                // Verify the nonce for security
                if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'mrvl_front_nonce') && !wp_verify_nonce($_POST['nonce'], 'mrvl_nonce_action'))) {
                    wp_send_json_error(['message' => 'Invalid nonce'], 403);
                }
                // get data
                global $site_cities;
                global $file_sig;

                $cities_data = mrvl_encrypted($site_cities);

                if (!$cities_data) {
                    wp_send_json_error(['message' => 'Failed!'], 500);
                }

                $response = [
                    'citiesAndStreets' => $cities_data['encryptedData'],
                    'configSignature' => $file_sig,
                ];

                wp_send_json_success($response);            
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                wp_send_json_error(['message' => 'Failed!'], 500);
            }
        }

        // =========================================================================================
        // WC settings tab
        // =========================================================================================
        
        public function marvelous_shipping_plugin_settings_tab_content() {
            require_once normalize_path(plugin_dir_path(__FILE__) . 'php/admin_setting.php');
            admin_settings_ui();
        }

                
        // =========================================================================================
        // WP plugins settings links
        // =========================================================================================

        public function plugin_settings_links($links) {
            $plugin_links = array(
                '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=marvelous_shipping_plugin_settings')) . '"><strong>הגדרות</strong></a>',
                '<a href="https://mrvlsol.co.il/?marvelous_shipping_docs&utm_source=wp_plugins_free_ver" target="_blank">דוקומנטציה</a>',
                '<a href="https://mrvlsol.co.il/?marvelous_shipping_go_ultimate&utm_source=wp_plugins_free_ver" target="_blank" style="color: goldenrod; word-spacing: 2px;letter-spacing: 0.5px;; font-weight: bold;">Get Premium</a>',
            );
            return array_merge($plugin_links, $links);
        }
        
        // =========================================================================================
        // WC Fields init
        // =========================================================================================

        public function init_fields() {
            add_filter('woocommerce_default_address_fields', array($this, 'wc_change_state_and_city_order'));
            add_filter( 'woocommerce_checkout_fields' , [$this, 'customize_checkout_fields'] );
            add_action( 'woocommerce_after_order_notes', [$this, 'add_pre_defined_comments'] );
        }

        // =========================================================================================
        // msg placeholder
        // =========================================================================================

        public function add_pre_defined_comments($checkout) {
            try {
            
                // Check if the 'fast_msgs_active' option is enabled
                $fast_msgs_active = get_option('marvelous_shipping_fast_msgs_active', 0);
                if (!$fast_msgs_active) {
                    // If the option is disabled, do not render anything
                    return;
                }
                // Fetch messages dynamically
                $messages_data = get_all_messages_data();
            
                // Check if there are any active messages
                $active_messages = array_filter($messages_data, function($message) {
                    return $message->msg_active;
                });
            
                if (empty($active_messages)) {
                    // If there are no active messages, do not render anything
                    return;
                }
            
                echo '<div id="pre_defined_comments"><br><p style="margin-bottom:20px !important">הודעות משלוח מהירות:</p>';
            
                // Loop through active messages and add checkboxes dynamically
                foreach ($active_messages as $message) {
                    woocommerce_form_field('message_' . $message->id, array(
                        'type'        => 'checkbox',
                        'class'       => array('fast-msg-style'),
                        'label'       => __($message->msg_content),
                        'required'    => false,
                    ), $checkout->get_value('message_' . $message->id));
                }
            
                echo '</div>';
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }

        }
        
        // =========================================================================================
        // remove (Optionl)/(אופציונלי) text
        // =========================================================================================

        public function remove_optional_text($field, $key, $args) {
            try {
                if (isset($args['required']) && !$args['required']) {
                    // Remove "(optional)" from the label
                    $field = str_replace('(optional)', '', $field);
                    $field = str_replace('(אופציונלי)', '', $field);
                    $field = trim($field);
                }
                return $field;
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $field;
            }

        }

        // =========================================================================================
        // enqueue scripts
        // =========================================================================================

        public function enqueue_scripts() {
            add_action( 'wp_enqueue_scripts', [$this, 'my_enqueue'] );
            add_action( 'admin_enqueue_scripts', [$this, 'my_enqueue_admin'] );
        }

        // =========================================================================================
        // admin scripts
        // =========================================================================================

        public function my_enqueue_admin() {
            try {

                // Check if the file exists
                $file_path = normalize_path(plugin_dir_path(__FILE__) . "cities/IL_cities.php");
                if (!file_exists($file_path)) {
                    marvelLog('enqueue admin scripts: cities file does not exist!');
                }
                // Check if we are on the WooCommerce settings page and your specific tab
                if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'marvelous_shipping_plugin_settings') {
                    wp_enqueue_style(
                        'Fredoka',
                        plugin_dir_url(__FILE__) . 'fonts/font.css',
                        array(),
                        null
                    );
                    wp_enqueue_style(
                        'mrvl_cities_plugin_css',
                        plugin_dir_url(__FILE__) . 'css/mrvl.css',
                        array(),
                        null
                    );
                    
                    wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
                    wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');
                    wp_enqueue_script( 'crypto-js', 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js', array(), '4.2.0');
                    wp_enqueue_script( 'proj4-js', 'https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.15.0/proj4.js', array(), '2.15.0');

                    wp_enqueue_script( 'chartjs', plugin_dir_url(__FILE__) . 'js/chart.umd.min.js','4.4.1');
                    wp_enqueue_script( 'filter_cities', plugin_dir_url(__FILE__) . 'js/filter_cities.js',);
                    wp_enqueue_script( 'admin_settings', plugin_dir_url(__FILE__) . 'js/admin_settings.js',);
                    // Nonce
                    global $green_check_svg;
                    global $question_svg;
                    global $warning_svg;
                    global $small_spinner_svg;
                    global $file_sig;
                    $user_options = get_user_options();

                    wp_localize_script('admin_settings', 'mrvlAdmin', [
                        'nonce' => wp_create_nonce('mrvl_nonce_action'),
                        'questionSvg' => $question_svg, 
                        'confirmSvg'  => $green_check_svg, 
                        'warningSvg'  => $warning_svg,
                        'smallSpinnerSvg'  => $small_spinner_svg,  
                        'diagnosticsAllowed'  => $user_options['send_diagnostics_active'],
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'configSignature' => $file_sig
                    ]);

                }
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }

        }
        // =========================================================================================
        // =========================================================================================
        public function my_enqueue() {
            try {
                // Only enqueue on the cart page
                if (is_cart()) {
                    wp_enqueue_script('cart-js', plugin_dir_url(__FILE__) . 'js/cart.js', array(), '1.0', true);
                }
            
                // Only enqueue on the checkout page
                if (is_checkout()) {
                    //Add the Select2 
                    wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
                    wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');
                    wp_enqueue_script( 'crypto-js', 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js', array(), '4.2.0');

                    wp_enqueue_style(
                        'Fredoka',
                        plugin_dir_url(__FILE__) . 'fonts/font.css',
                        array(),
                        null
                    );

                    // Enqueue the custom JavaScript file
                    wp_enqueue_script('marvelous-checkout', plugin_dir_url(__FILE__) . 'js/checkout.js', array('jquery', 'select2-js'));
            
                    
                    // Pass the city-to-street mapping to JavaScript
                    global $file_sig;
                    global $info_svg_icon;
                    global $small_spinner_svg;
                    $user_options = get_user_options();

                    wp_localize_script('marvelous-checkout', 'marvelousShipping',[
                        'nonce' => wp_create_nonce('mrvl_front_nonce'),
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'configSignature' => $file_sig,
                        'infoSvgIcon' => $info_svg_icon,
                        'spinnerSvg' => $small_spinner_svg,
                        'billingCountry' => WC()->customer->get_billing_country(),
                        'shippingCity' => WC()->customer->get_shipping_city(),
                        'diagnosticsAllowed'  => $user_options['send_diagnostics_active'] 
                    ]);
                    

                    wp_enqueue_style(
                        'mrvl_cities_plugin_front_css',
                        plugin_dir_url(__FILE__) . 'css/mrvl-front.css',
                        array(),
                        null
                    );
                }
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
            }

       }

        // =========================================================================================
        // msg placeholder
        // =========================================================================================

        public function customize_checkout_fields($fields) {
            try {
                return kernel_customize_checkout_fields($fields);
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $fields;
            }
        }
        
        // =========================================================================================
        // Change the order of State and City fields to have more sense with the steps of form
        // =========================================================================================

        public function wc_change_state_and_city_order($fields) {
            try{
                $fields['postcode']['required'] = false;
                $fields['first_name']['priority'] = 10;
                $fields['last_name']['priority'] = 20;
                $fields['country']['priority'] = 30;
                $fields['city']['priority'] = 40;
                $fields['address_1']['priority'] = 50;
                $fields['address_2']['priority'] = 60;
                $fields['state']['priority'] = 70;

                // change the address 2 to not be required
                $fields['address_2']['required'] = false;

                return $fields;
            } catch (\Throwable $th) {
                logException($th, __FUNCTION__);
                return $fields;
            }
        }

        // =========================================================================================
        // Declares WooCommerce HPOS compatibility
        // =========================================================================================
        public function woocommerce_hpos_compatible() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        }
    }

    // =========================================================================================
    // Instantiate class
    // =========================================================================================

    $GLOBALS['marvelous_shipping'] = new MarvelousShipping();
};


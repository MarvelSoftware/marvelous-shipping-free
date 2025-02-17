<?php
/**
 * Plugin Name: Marvelous Cities Shipping Method
 * Description: Adds a custom shipping method "Marvelous Cities" and allows switching it dynamically.
 */

// =======================================================================================
// =======================================================================================

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// =======================================================================================
// =======================================================================================

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {


    // Custom Shipping Method Class
    function marvelous_shipping_method_init() {
        if (!class_exists('Marvelous_Shipping_Shipping_Method')) {
            class Marvelous_Shipping_Shipping_Method extends WC_Shipping_Method {

                /**
                 * Constructor. The instance ID is passed to this.
                 */
                public function __construct( $instance_id = 0 ) {
                    $this->id                    = 'marvelous_shipping';
                    $this->instance_id 			     = absint( $instance_id );
                    $this->method_title          = __( 'Marvelous Shipping' );
                    $this->method_description    = __( 'משלוחים לפי תעריפים מותאמים של פלאגין Marvelous Shipping' );
                    $this->supports              = array(
                        'shipping-zones',
                        'instance-settings',
                    );
                        
                    $this->enabled		    = true;
                    $this->title                = 'Marvelous Shipping';
            
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
            
                /**
                 * calculate_shipping function.
                 * @param array $package (default: array())
                 */
                public function calculate_shipping( $package = array() ) {
                    $this->add_rate( array(
                        'id'    => $this->id . $this->instance_id,
                        'label' => 'מחיר משלוח לפי כתובת', // Shipping price will be calculated at checkout
                        'cost'  => 1,
                    ) );
                }
            }
        }
    }
    add_action( 'woocommerce_shipping_init', 'marvelous_shipping_method_init' );
    add_filter( 'woocommerce_shipping_methods', 'marvelous_shipping_add_shipping_method' );
}

// =======================================================================================
// =======================================================================================

function add_israel_shipping_zone_with_marvelous_shipping() {
    // Check if there already a shipping zone for "Israel"
    $zone_name = 'Marvelous Israel';
    $zones = WC_Shipping_Zones::get_zones();
    $ids = array();
    foreach ($zones as $zone) {
        $zone_obj = WC_Shipping_Zones::get_zone($zone['zone_id']);
        if($zone_obj != null){
            $locations = $zone_obj->get_zone_locations();
            foreach ($locations as $location) {
                if ($location->code === 'IL' && $location->type === 'country') {
                    array_push($ids, $zone['zone_id']);
                }
            }
        }
    }

    // if $ids is not empty, get the first ID zone, then add to that shipping zone the marvel method
    if (!empty($ids)) {
        $zone_obj = WC_Shipping_Zones::get_zone($ids[0]);
        // check if method "Marvelous Shipping" already exist in that shipping zone
        $shipping_methods = $zone_obj->get_shipping_methods();
        $method_exists = false;
        foreach ($shipping_methods as $method) {
            if ($method->id === 'marvelous_shipping') {
                $method_exists = true;
                break;
            }
        }
        // Add the Marvelous Shipping shipping method if it doesn't exist
        if (!$method_exists) {
            $zone_obj->add_shipping_method('marvelous_shipping');
        }

    } else { // no shipping zone with Israel as a location
        $new_zone = new WC_Shipping_Zone();
        $new_zone->set_zone_name($zone_name);
        $new_zone->add_location('IL', 'country');
        $new_zone->add_shipping_method('marvelous_shipping');
        $new_zone->save();
    }
}



// =======================================================================================
// Add the shipping method
// =======================================================================================

function marvelous_shipping_add_shipping_method($methods) {
    $methods['marvelous_shipping'] = 'Marvelous_Shipping_Shipping_Method';

    return $methods;
}

// =======================================================================================
// 
// =======================================================================================

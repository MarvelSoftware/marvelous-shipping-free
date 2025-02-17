<?php

if (!defined('ABSPATH')) {
    exit;
}

include_once(plugin_dir_path(__FILE__) . "kernel.php");

// =======================================================================================
// Plugin's package color
// =======================================================================================


function add_ultimate_label_to_plugin_row() {
    // Add custom HTML/CSS for your plugin's row
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const pluginTitle = document.querySelector("#the-list tr[data-slug=\'marvelous-shipping\'] .plugin-title strong");
            if (pluginTitle) {
                pluginTitle.innerHTML = "Marvelous Shipping <span style=\'color: gray; font-weight: 800;\'>Free</span>";
            }
        });
    </script>';
}


// =======================================================================================
// deactivation hook
// =======================================================================================

function marvelous_shipping_deactivate() {
    try {
        
        // Search for Marvelous_Shipping_Shipping_Method ID in the shipping zones
        $shipping_zones = WC_Shipping_Zones::get_zones();
        foreach ($shipping_zones as $zone) {
            foreach ($zone['shipping_methods'] as $instance_id => $method) {
                // Check if the method is an instance of Marvelous_Shipping_Shipping_Method
                if ($method instanceof Marvelous_Shipping_Shipping_Method) {
                    // Remove the method from the array
                    $zone_obj = WC_Shipping_Zones::get_zone($zone['zone_id']);
                    $zone_obj->delete_shipping_method($instance_id);
                }
            }
        }

        // remove Marvelous Israel shipping zone in case we've created it
        $shipping_zones = WC_Shipping_Zones::get_zones();
        foreach ($shipping_zones as $zone) {
            if ($zone['zone_name'] === 'Marvelous Israel') {
                WC_Shipping_Zones::delete_zone($zone['zone_id']);
            }
        }
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    }
}

// =======================================================================================
// uninstall hook
// =======================================================================================

function marvelous_shipping_remove_cleanup() {
    try{
        global $wpdb;

        // Remove plugin-specific options
        $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'marvelous_shipping_%'");
        foreach ($options as $option) {
            delete_option($option->option_name);
        }

        // Drop plugin-specific database tables
        $tables = [
            "{$wpdb->prefix}marvelous_shipping_orders_log",
            "{$wpdb->prefix}marvelous_shipping_messages",
            "{$wpdb->prefix}marvelous_shipping_cities",
            "{$wpdb->prefix}marvelous_shipping_districts",
        ];
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        $wpdb->query("DROP TABLE IF EXISTS $table");

    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    }
}


// =======================================================================================
// =======================================================================================

// Function to initialize options with defaults during activation
function marvelous_shipping_activate() {

    try {
        global $wpdb;
        global $israel_districts_heb2en;
        global $israel_heb2en;
        global $city2district;

        // init marvelous shipping method
        add_israel_shipping_zone_with_marvelous_shipping();


        // Default options
        $default_options = [
            'fast_msgs_active' => 0,    // false
            'floor_field_active' => 0,  // false
            'apartment_field_active' => 0,  // false
            'building_code_field_active' => 0,  // false
            'send_diagnostics_active' => 1
        ];
        if(!empty($default_options)){
            foreach ($default_options as $key => $value) {
                $prefixed_key = 'marvelous_shipping_' . $key;
                if (get_option($prefixed_key) === false) {
                    add_option($prefixed_key, $value);
                }
            }
        }

        // Table creation queries
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}marvelous_shipping_districts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                heb_name VARCHAR(255) NOT NULL DEFAULT '',
                en_name VARCHAR(255) NOT NULL DEFAULT '',
                shipping_price INT UNSIGNED DEFAULT NULL,
                district_allow BOOLEAN NOT NULL DEFAULT TRUE,
                UNIQUE KEY (heb_name)
            ) $charset_collate;",
        
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}marvelous_shipping_cities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                heb_name VARCHAR(255) NOT NULL DEFAULT '',
                en_name VARCHAR(255) NOT NULL DEFAULT '',
                shipping_price INT UNSIGNED DEFAULT NULL,
                district_heb_name VARCHAR(255) NOT NULL,
                city_allowed BOOLEAN NOT NULL DEFAULT TRUE,
                FOREIGN KEY (district_heb_name) REFERENCES {$wpdb->prefix}marvelous_shipping_districts(heb_name) ON DELETE CASCADE,
                UNIQUE KEY (heb_name)
            ) $charset_collate;",
        
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}marvelous_shipping_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                msg_active BOOLEAN NOT NULL DEFAULT TRUE,
                msg_content TEXT NOT NULL DEFAULT ''
            ) $charset_collate;",
        
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}marvelous_shipping_orders_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT DEFAULT NULL,
                creation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                order_price INT UNSIGNED DEFAULT NULL,
                shipping_price INT UNSIGNED DEFAULT NULL,
                city_heb_name VARCHAR(255) NOT NULL,
                district_heb_name VARCHAR(255) NOT NULL,
                street_heb_name VARCHAR(255) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (city_heb_name) REFERENCES {$wpdb->prefix}marvelous_shipping_cities(heb_name) ON DELETE CASCADE,
                FOREIGN KEY (district_heb_name) REFERENCES {$wpdb->prefix}marvelous_shipping_districts(heb_name) ON DELETE CASCADE
            ) $charset_collate;"
        ];

       
        if(!empty($tables)){
            foreach ($tables as $sql) {
                $wpdb->query($sql);
            }
        }

        // create index
        // Add an index to `district_heb_name` in the `marvelous_shipping_orders_log` table if it does not exist
        $index_exists = $wpdb->get_var("
            SELECT COUNT(1)
            FROM information_schema.STATISTICS
            WHERE table_schema = '{$wpdb->dbname}'
            AND table_name = '{$wpdb->prefix}marvelous_shipping_orders_log'
            AND index_name = 'idx_district_heb_name'
        ");

        // Create the index if it does not exist
        if (!$index_exists) {
            $wpdb->query("
                CREATE INDEX idx_district_heb_name 
                ON {$wpdb->prefix}marvelous_shipping_orders_log(district_heb_name);
            ");
        }

        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");
        // Handle null errors
        if ($israel_heb2en === null || $city2district === null || $israel_districts_heb2en === null) {
            marvelLog('marvelous_shipping_activate: null params!');
            return;
        }
        
        if(!empty($israel_districts_heb2en)){
            // Insert data into districts table
            foreach ($israel_districts_heb2en as $heb_name => $en_name) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}marvelous_shipping_districts WHERE heb_name = %s",
                    $heb_name
                ));
                if (!$exists) {
                    $wpdb->insert("{$wpdb->prefix}marvelous_shipping_districts", [
                        'heb_name' => $heb_name,
                        'en_name' => $en_name,
                        'shipping_price' => 0,
                        'district_allow' => true
                    ]);
                }
            }
        }
        
        if(!empty($israel_heb2en)){
            // Insert data into cities table
            foreach ($israel_heb2en as $heb_name => $en_name) {
                $district_heb_name = $city2district[$heb_name] ?? '';
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}marvelous_shipping_cities WHERE heb_name = %s",
                    $heb_name
                ));
                if (!$exists) {
                    $wpdb->insert("{$wpdb->prefix}marvelous_shipping_cities", [
                        'heb_name' => $heb_name,
                        'en_name' => $en_name,
                        'shipping_price' => 0,
                        'district_heb_name' => $district_heb_name
                    ]);
                }
            }
        }

        $default_messages = [
            base64_decode("15HXnteZ15PXlCDXldeQ15nXnyDXntei16DXlCwg15TXqdeQ15nXqNeVINeQ16og15TXl9eR15nXnNeUINeR157XqNek16HXqi4="),
            base64_decode("16DXkCDXnNeU16nXkNeZ16gg15DXqiDXlNeX15HXmdec15Qg15zXmdeTINeU15PXnNeqLg=="),
            base64_decode("16DXmdeq158g15zXlNep15DXmdeoINeQ16og15TXl9eR15nXnNeUINeR15DXqNeV158g15TXl9ep157XnC4="),
            base64_decode("15DXoNeQINeU16nXkNeZ16jXlSDXkNeqINeU157Xqdec15XXlyDXkNem15wg15TXqdeb158g15HXp9eV157XlC4="),
            base64_decode("15HXkden16nXlCDXnNeQINec15PXpNeV16cg15DXlSDXnNem15zXptecINeR16TXotee15XXny4=")
        ];
        if(!empty($default_messages)){
            foreach ($default_messages as $msg_content) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}marvelous_shipping_messages WHERE msg_content = %s",
                    $msg_content
                ));
                if (!$exists) {
                    $wpdb->insert("{$wpdb->prefix}marvelous_shipping_messages", [
                        'msg_active' => true,
                        'msg_content' => $msg_content
                    ]);
                }
            }
        }
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    }
}


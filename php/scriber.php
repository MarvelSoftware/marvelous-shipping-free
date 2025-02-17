<?php

// =======================================================================================
// scriber core
// =======================================================================================

function marvelous_shipping_save_settings() {
    global $wpdb;
    $generate_new_file = false;
    include_once(plugin_dir_path(__FILE__) . "kernel.php");

    // Debug Start

    // Initialize response
    $response = [
        'success' => false,
        'errors' => [],
        'num_errors' => 0,
        'messages' => [],
    ];

    try {
        // Decode input
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : [];
        if (empty($settings)) {
            $response['errors'][] = 'No settings provided';
            $response['num_errors']++;
            return $response;
        }

        // Extract changes
        $optionsChanges = $settings['optionsChanges'] ?? [];
        $pricesChanges = $settings['pricesChanges'] ?? [];
        $restrictionsChanges = $settings['restrictionsChanges'] ?? [];

        // Process options changes
        foreach ($optionsChanges as $change) {
            $name = $change['name'];
            $value = $change['value'];

            // Update WordPress option
            $name = 'marvelous_shipping_' . $name;

            if (strpos($name, 'active') !== false) {
                $updated = update_option($name, $value ? 1 : 0);
                if ($updated === false) {
                    // Log if update_option explicitly returns false
                    marvelLog("update_option failed explicitly for $name.");
                }
                
                $current_value = (int) get_option($name); // Cast the result to an integer

                if ($updated === false && $current_value !== ($value ? 1 : 0)) {
                    $response['errors'][] = "Failed to update option: $name";
                    $response['num_errors']++;
                }
            }else{
                $updated = update_option($name, $value);
                if ($updated === false && get_option($name) !== $value) {
                    $response['errors'][] = "Failed to update option: $name";
                    $response['num_errors']++;
                }
            }
                
        }

        // Process restrictions changes
        global $israel_districts_en2heb;
        global $district2cities;

        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");

        foreach ($restrictionsChanges as $change) {
            $generate_new_file = true;
            $name = $change['name'];
            $value = $change['value'] === false ? 0 : 1;

            if (str_starts_with($name, 'city-') && str_ends_with($name, '-allow')) {
                // Update individual city
                $city = str_replace(['city-', '-allow'], '', $name);
                $city = str_replace('_', ' ', $city);

                $updated = $wpdb->update(
                    "{$wpdb->prefix}marvelous_shipping_cities",
                    ['city_allowed' => $value],
                    ['en_name' => $city],
                    ['%d'],
                    ['%s']
                );
                if ($updated === false) {
                    $response['errors'][] = "Failed to update city: $city";
                    $response['num_errors']++;
                }
            }
        }

        // Process prices changes
        foreach ($pricesChanges as $change) {
            $generate_new_file = true;
            $name = $change['name'];
            $value = str_replace(',', '', $change['value']);
            $value = floatval($value);

            if ($name === 'global-price-input') {
                // Update all prices
                $updated = $wpdb->query("UPDATE {$wpdb->prefix}marvelous_shipping_cities SET shipping_price = $value");
                if ($updated === false) {
                    $response['errors'][] = 'Failed to update global prices of cities';
                    $response['num_errors']++;
                }
                $updated = $wpdb->query("UPDATE {$wpdb->prefix}marvelous_shipping_districts SET shipping_price = $value");
                if ($updated === false) {
                    $response['errors'][] = 'Failed to update global prices of districts';
                    $response['num_errors']++;
                }
            } elseif (str_starts_with($name, 'city-price-')) {
                // Update individual city price
                $city = str_replace('city-price-', '', $name);
                $city = str_replace('_', ' ', $city);

                $updated = $wpdb->update(
                    "{$wpdb->prefix}marvelous_shipping_cities",
                    ['shipping_price' => $value],
                    ['en_name' => $city],
                    ['%f'],
                    ['%s']
                );
                if ($updated === false) {
                    $response['errors'][] = "Failed to update price for city: $city";
                    $response['num_errors']++;
                }
            }
        }

        // Final response
        $response['success'] = $response['num_errors'] === 0;

            
        if($generate_new_file != false){
            generate_site_cities_file();
        }

        return $response;
    } catch (\Throwable $th) {
        $response['errors'][] = $e->getMessage();
        $response['num_errors']++;
        // Stringify the exception details
        logException($th, __FUNCTION__);
        
    } 

}


// =======================================================================================
// 
// =======================================================================================

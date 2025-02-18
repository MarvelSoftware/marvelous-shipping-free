<?php


// =======================================================================================
// =======================================================================================

function send_diagnostics($message) {
    try {
        global $diagnostic_url;
        global $sslverify;
        $user_options = get_user_options();
        // send only if user allow diagnostics
        if($user_options['send_diagnostics_active'] === '0'){
            return;
        }

        // Get the current time
        $current_time = current_time('mysql');
        // Prepare the payload
        $payload = [
            'message' => $message,
            'timestamp' => $current_time,
        ];

        $json_payload = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Send the POST request
        wp_remote_post($diagnostic_url, [
            'timeout' => 10,
            'body' => $json_payload,
            'sslverify'  => $sslverify, // Disable SSL verification (for local development only)
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);
    
        return;
    } catch (\Throwable $th) {
        // only log, dont send
        logException($th, __FUNCTION__, false);
    }
}
   
// =======================================================================================
// =======================================================================================

function kernel_customize_checkout_fields($fields) {
    try {
        // generate select2 city field
        global $israel_heb_cities;
        global $israel_heb2en;
        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");
        
        $streets = array( '' => __( 'בחרו עיר תחילה', 'woocommerce' ) . '&hellip;' );
        $user_options = get_user_options();
        // Format in the right way the options array of cities
        $options = array( '' => __( 'בחרו עיר', 'woocommerce' ) . '&hellip;' );
        foreach ( $israel_heb_cities as $city ) {
            $options[$israel_heb2en[$city]] = $city;
        }
        // Define the options for the entrance field
        $entrance_options = array(
            '' => __( 'ללא', 'woocommerce' ), // Default placeholder option
            'א' => 'א',
            'ב' => 'ב',
            'ג' => 'ג',
            'ד' => 'ד',
            'ה' => 'ה',
            'ו' => 'ו',
            'ז' => 'ז',
            'ח' => 'ח',
            'ט' => 'ט',
            'י' => 'י',
        );

        $billing_base_priority = $fields['billing']['billing_city']['priority'];
        $shipping_base_priority = $fields['shipping']['shipping_city']['priority'];



        // Adding city select fields for billing - hidden by default!
        $fields['billing']['mrvl_billing_city'] = array(
            'type'         => 'select',
            'required'     => true,
            'label'        => 'עיר',
            'options'      => $options,
            'autocomplete' => 'mrvl-city-select',
            'class' =>  array_merge($fields['billing']['billing_city']['class'], array('hidden') ),
            'priority' => $billing_base_priority + 1,
        );

        // Adding city select fields for shipping
        $fields['shipping']['mrvl_shipping_city'] = array(
            'type'         => 'select',
            'required'     => true,
            'label'        => 'עיר',
            'options'      => $options,
            'autocomplete' => 'mrvl-city-select',
            'class' =>  array_merge($fields['shipping']['shipping_city']['class'], array('hidden') ),
            'priority' => $shipping_base_priority + 1,
        );

        // Adding street select fields
        $fields['billing']['mrvl_billing_street'] = $fields['shipping']['mrvl_shipping_street'] = array(
            'type'         => 'select',
            'label'        => 'רחוב',
            'required'     => true,
            'options'      => $streets,
            'autocomplete' => 'mrvl-street-select',
            'class'        => array('form-row-wide', 'hidden')
        );

        // street field priority
        $fields['billing']['mrvl_billing_street']['priority'] = $billing_base_priority + 2;
        $fields['shipping']['mrvl_shipping_street']['priority'] = $shipping_base_priority + 2;

        


        // add house number and apartment number fields - entry code only according user preference
        $fields['billing']['mrvl_billing_house_num'] = $fields['shipping']['mrvl_shipping_house_num'] = array(
            'type'         => 'text',
            'label'        => 'מס\' בית',
            'required'     => true,
            'autocomplete' => 'house_num',
            'class'        => array('form-row-first', 'hidden')
        );
        // priority
        $fields['billing']['mrvl_billing_house_num']['priority'] = $billing_base_priority + 3;
        $fields['shipping']['mrvl_shipping_house_num']['priority'] = $shipping_base_priority + 3;


        // Adding entrance select field for billing
        $fields['billing']['mrvl_billing_entrance'] = array(
            'type'         => 'select',
            'required'     => false, // Make it optional
            'label'        => 'כניסה',
            'options'      => $entrance_options,
            'class'        => array('form-row-last', 'hidden'),
            'autocomplete' => 'mrvl-entrance-select',
        );

        // Adding entrance select field for shipping
        $fields['shipping']['mrvl_shipping_entrance'] = array(
            'type'         => 'select',
            'required'     => false, // Make it optional
            'label'        => 'כניסה',
            'options'      => $entrance_options,
            'class'        => array('form-row-last', 'hidden'),
            'autocomplete' => 'mrvl-entrance-select',
        );
        // priority
        $fields['billing']['mrvl_billing_entrance']['priority'] = $billing_base_priority + 4;
        $fields['shipping']['mrvl_shipping_entrance']['priority'] = $shipping_base_priority + 4;


        // Array to keep track of active fields
        $active_fields = [];

        // Check which fields are active
        if ($user_options['floor_field_active']) {
            $active_fields[] = 'mrvl_billing_house_floor';
        }
        if ($user_options['apartment_field_active']) {
            $active_fields[] = 'mrvl_billing_aprt_num';
        }
        if ($user_options['building_code_field_active']) {
            $active_fields[] = 'mrvl_billing_entry_code';
        }

        // Assign classes based on the number of active fields
        foreach ($active_fields as $index => $field) {
            $class = [];
            if (count($active_fields) === 1) {
                $class = ['form-row-wide', 'hidden'];
            } elseif (count($active_fields) === 2) {
                $class = $index === 0 ? ['form-row-first', 'hidden'] : ['form-row-last', 'hidden'];
            } elseif (count($active_fields) === 3) {
                if ($index === 0) {
                    $class = ['form-row-first', 'hidden'];
                } elseif ($index === 1) {
                    $class = ['form-row-last', 'hidden'];
                } else {
                    $class = ['form-row-wide', 'hidden'];
                }
            }

            // Assign field properties
            if ($field === 'mrvl_billing_house_floor') {
                $fields['billing'][$field] = $fields['shipping']['mrvl_shipping_house_floor'] = array(
                    'type'         => 'number',
                    'label'        => 'קומה',
                    'required'     => false,
                    'autocomplete' => 'house_floor',
                    'class'        => $class,
                    'priority'     => $billing_base_priority + 5,
                );
            } elseif ($field === 'mrvl_billing_aprt_num') {
                $fields['billing'][$field] = $fields['shipping']['mrvl_shipping_aprt_num'] = array(
                    'type'         => 'text',
                    'label'        => 'דירה',
                    'required'     => false,
                    'autocomplete' => 'aprt_num',
                    'class'        => $class,
                    'priority'     => $billing_base_priority + 6,
                );
            } elseif ($field === 'mrvl_billing_entry_code') {
                $fields['billing'][$field] = $fields['shipping']['mrvl_shipping_entry_code'] = array(
                    'type'         => 'text',
                    'label'        => 'קוד בניין',
                    'required'     => false,
                    'autocomplete' => 'entry_code',
                    'class'        => $class,
                    'priority'     => $billing_base_priority + 7,
                );
            }
        }
        
        // hide fields
        if(WC()->customer->get_billing_country() === 'IL'){
            // city
            $fields['billing']['billing_city']['class'] = array_merge($fields['billing']['billing_city']['class'] , array('hidden'));
            $fields['shipping']['shipping_city']['class'] = array_merge($fields['shipping']['shipping_city']['class'] , array('hidden'));
            // address 1
            $fields['billing']['billing_address_1']['class'] = array_merge($fields['billing']['billing_address_1']['class'] , array('hidden'));
            $fields['shipping']['shipping_address_1']['class'] = array_merge($fields['shipping']['shipping_address_1']['class'] , array('hidden'));
            // address 2
            $fields['billing']['billing_address_2']['class'] = array_merge($fields['billing']['billing_address_2']['class'] , array('hidden'));
            $fields['shipping']['shipping_address_2']['class'] = array_merge($fields['shipping']['shipping_address_2']['class'] , array('hidden'));
            // billing state
            $fields['billing']['billing_state']['class'] = array_merge($fields['billing']['billing_state']['class'] , array('hidden'));
            // shipping state
            $fields['shipping']['shipping_state']['class'] = array_merge($fields['shipping']['shipping_state']['class'] , array('hidden'));

            // Unhide custom floor number, apartment number, entry code
            // floor
            if($user_options['apartment_field_active']){
                $fields['billing']['mrvl_billing_aprt_num']['class'] = array_diff(
                    $fields['billing']['mrvl_billing_aprt_num']['class'], 
                    array('hidden')
                );
                $fields['shipping']['mrvl_shipping_aprt_num']['class'] = array_diff(
                    $fields['shipping']['mrvl_shipping_aprt_num']['class'], 
                    array('hidden')
                );
            }

            // entry code
            if($user_options['building_code_field_active']){
                $fields['billing']['mrvl_billing_entry_code']['class'] = array_diff(
                    $fields['billing']['mrvl_billing_entry_code']['class'], 
                    array('hidden')
                );
                $fields['shipping']['mrvl_shipping_entry_code']['class'] = array_diff(
                    $fields['shipping']['mrvl_shipping_entry_code']['class'], 
                    array('hidden')
                );
            }

            // floor number
            if($user_options['floor_field_active']){
                $fields['billing']['mrvl_billing_house_floor']['class'] = array_diff(
                    $fields['billing']['mrvl_billing_house_floor']['class'], 
                    array('hidden')
                );
                $fields['shipping']['mrvl_shipping_house_floor']['class'] = array_diff(
                    $fields['shipping']['mrvl_shipping_house_floor']['class'], 
                    array('hidden')
                );
            }

            // Unhide house number
            $fields['billing']['mrvl_billing_house_num']['class'] = array_diff(
                $fields['billing']['mrvl_billing_house_num']['class'], 
                array('hidden')
            );  

            $fields['shipping']['mrvl_shipping_house_num']['class'] = array_diff(
                $fields['shipping']['mrvl_shipping_house_num']['class'], 
                array('hidden')
            );

            // Unhide custom city fields
            $fields['billing']['mrvl_billing_city']['class'] = array_diff(
                $fields['billing']['mrvl_billing_city']['class'], 
                array('hidden')
            );
            $fields['shipping']['mrvl_shipping_city']['class'] = array_diff(
                $fields['shipping']['mrvl_shipping_city']['class'], 
                array('hidden')
            );

            // Unhide custom street fields
            $fields['billing']['mrvl_billing_street']['class'] = array_diff(
                $fields['billing']['mrvl_billing_street']['class'], 
                array('hidden')
            );
            $fields['shipping']['mrvl_shipping_street']['class'] = array_diff(
                $fields['shipping']['mrvl_shipping_street']['class'], 
                array('hidden')
            );
            // Unhide custom entrance fields
            $fields['billing']['mrvl_billing_entrance']['class'] = array_diff(
                $fields['billing']['mrvl_billing_entrance']['class'], 
                array('hidden')
            );
            $fields['shipping']['mrvl_shipping_entrance']['class'] = array_diff(
                $fields['shipping']['mrvl_shipping_entrance']['class'], 
                array('hidden')
            );
        }

        return $fields;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return $fields;
    } 
}

// =======================================================================================
// =======================================================================================

function kernel_add_extra_fees_notification() {
    try{
        // Calculate extra fees based on floors
        $fees = kernel_get_city_data();
        if(!$fees){
            return;
        }

        // Initialize the message
        $message = '';

        if (!empty($fees['chosen_shipping_city'])) {
            if($fees['shipping_price'] === 0){
                $message .= sprintf(
                    "(משלוח חינם ל%s)\n",
                    $fees['chosen_shipping_city'],
                );
            }else{
                $message .= sprintf(
                    "(%s₪ עלות משלוח ל%s)\n",
                    isset($fees['shipping_price']) ? number_format($fees['shipping_price'], 2) : '' , // Format fees
                    $fees['chosen_shipping_city'],
                );
            }
        }
        return $message;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return '';
    }

}

// =======================================================================================
// =======================================================================================

function kernel_update_shipping_cost_based_on_city($rates, $package) {
    try{
        // Calculate extra fees based on floors
        $fees = kernel_get_city_data();
        if(!$fees){
            return;
        }
        // Loop through all shipping methods and modify the cost
        foreach ($rates as $rate_id => $rate) {
                if($rates[$rate_id]->method_id === 'marvelous_shipping'){

                 if(empty($fees['chosen_shipping_city'])){
                    $rates[$rate_id]->label = 'בחרו עיר לחישוב עלות משלוח';
                    $fees['shipping_price'] = 0;
                } else if($fees['shipping_price'] === null){
                    $rates[$rate_id]->label = 'שגיאה בחישוב עלות משלוח';
                    $fees['shipping_price'] = 0;
                } else if(!$fees['city_allowed'] ){
                    $rates[$rate_id]->label = 'אין משלוחים לאזור של ' . $fees['chosen_shipping_city'];
                    $fees['shipping_price'] = 0;
                } 
                $rates[$rate_id]->cost = $fees['shipping_price']; // Update the shipping cost
                // Save custom shipping data to the session
                WC()->session->set('custom_shipping_cost', $fees['shipping_price']);
                WC()->session->set('custom_shipping_label', $rates[$rate_id]->label);
            }
        }

        return $rates;

    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return $rates;
    }

}

// =======================================================================================
// =======================================================================================

function kernel_get_city_data() {
    try{
        $shipping_price = -1;
        $city_allowed = false;
        global $wpdb;

        // selected billing and shipping street and city
        $chosen_shipping_city = WC()->customer->get_shipping_city();
        // Sanitize the chosen city to prevent SQL injection
        $chosen_shipping_city = sanitize_text_field($chosen_shipping_city);

        $table_name = "{$wpdb->prefix}marvelous_shipping_cities";
        $query = $wpdb->prepare(
            "SELECT shipping_price, city_allowed 
            FROM $table_name 
            WHERE heb_name = %s",
            str_replace('–', '-', $chosen_shipping_city)
        );
    
        $result = $wpdb->get_row($query, ARRAY_A);

        if ($result) {
            $shipping_price = isset($result['shipping_price']) ? (int) $result['shipping_price'] : null;
            $city_allowed = isset($result['city_allowed']) ? (bool) $result['city_allowed'] : false;
        } else {
            $shipping_price = null;
            $city_allowed = false; // Default values if no city is found
        }

        return [
            'chosen_shipping_city' => $chosen_shipping_city,
            'shipping_price' => $shipping_price,
            'city_allowed' => $city_allowed,
        ];
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return null;
    }

}
// =======================================================================================
// =======================================================================================

function kernel_log_order_to_marvelous_shipping_table($order_id) {
    try{
        if (!$order_id) return;

        global $wpdb;
        global $city2district;
        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");

        // Get the order object
        $order = wc_get_order($order_id);
    
        // Get shipping details
        $shipping_city = empty($order->get_shipping_city()) ? $order->get_billing_city() : $order->get_shipping_city();
        $shipping_address_1 = $order->get_shipping_address_1();
        $order_price = $order->get_total() ?? 0;
        $shipping_price = $order->get_shipping_total() ?? 0;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null;
    
        // Extract street name using regex (excluding "מספר" and house numbers)
        $street_name = null;
        if (preg_match('/רחוב\s+([\p{Hebrew}\s\-]+?)(?:\s+מספר|\s+\d|$)/u', $shipping_address_1, $matches)) {
            $street_name = trim($matches[1]);
        }


    
        // Handle missing values
        $street_name = $street_name ? $street_name: 'לא ידוע'; // Default to 'לא ידוע' if no street name is found
    
        // For this example, we'll use placeholders for district and city (these may need additional logic or mappings)
        $city_heb_name = $shipping_city ?: 'לא ידוע';
        // Determine the district based on city
        $district_heb_name = isset($city2district[str_replace('–', '-', $city_heb_name)]) ? $city2district[str_replace('–', '-', $city_heb_name)] : 'לא ידוע';
    
        // Check if the order ID already exists in the table
        $table_name = $wpdb->prefix . 'marvelous_shipping_orders_log';
        $existing_entry = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE order_id = %d",
            $order_id
        ));
    
        if ($existing_entry > 0) {
            marvelLog("Order #{$order_id} already exists in marvelous_shipping_orders_log. Skipping insertion.");
            return; // Exit the function to prevent duplicate insertion
        }
    
        // Insert into the table
        $data = [
            'order_id' => $order_id,
            'order_price' => $order_price,
            'shipping_price' => $shipping_price,
            'city_heb_name' => str_replace('–', '-', $city_heb_name),
            'district_heb_name' => str_replace('–', '-', $district_heb_name),
            'street_heb_name' => $street_name,
            'user_agent' => $user_agent,
        ];
    
        $format = [
            '%d', // order_id
            '%d', // order_price
            '%d', // shipping_price
            '%s', // city_heb_name
            '%s', // district_heb_name
            '%s', // street_heb_name
            '%s', // user_agent
        ];
    
        $inserted = $wpdb->insert($table_name, $data, $format);
    
        if ($inserted === false) {
            marvelLog("Failed to insert order #{$order_id} into marvelous_shipping_orders_log.");
        } else {
            marvelLog("Order #{$order_id} successfully logged in marvelous_shipping_orders_log.");
        }
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    } 

}
// =======================================================================================
// =======================================================================================

function get_all_cities_data() {
    try{
        global $wpdb;

        // Define the table name with the WordPress prefix
        $table_name = $wpdb->prefix . 'marvelous_shipping_cities';

        // Query to fetch all rows from the table
        $query = "SELECT heb_name, en_name, shipping_price, district_heb_name, city_allowed FROM $table_name";

        // Execute the query
        $results = $wpdb->get_results($query);

        // Initialize the array to store city data
        $cities_data = [];

        // Populate the array with the results
        if ($results) {
            foreach ($results as $row) {
                $cities_data[$row->heb_name] = (object) [
                    'heb_name' => $row->heb_name,
                    'en_name' => $row->en_name,
                    'shipping_price' => $row->shipping_price,
                    'district_heb_name' => $row->district_heb_name,
                    'city_allowed' => $row->city_allowed,
                ];
            }
        }

        return $cities_data;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return null;
    }

}

// =======================================================================================
// =======================================================================================

function logException(\Throwable $th, $func_name, $send_diagnostic = true) {
    // Collect all necessary diagnostic data
    $exceptionData = [
        'function'       => $func_name,
        'error_message'  => $th->getMessage(),
        'file'           => $th->getFile(),
        'line'           => $th->getLine(),
        'stack_trace'    => $th->getTraceAsString(),
        'wc_version'     => defined('WC_VERSION') ? WC_VERSION : 'N/A',
        'wp_version'     => get_bloginfo('version'),
        'php_version'    => phpversion(),
        'site_url'       => home_url(), // Ensure user consent before sending this
    ];

    // Convert the data to a string for logging purposes
    $logMessage = sprintf(
        "Exception in %s:\nMessage: %s\nFile: %s\nLine: %d\nTrace:\n%s\nWC Version: %s\nWP Version: %s\nPHP Version: %s\nSite URL: %s",
        $func_name,
        $exceptionData['error_message'],
        $exceptionData['file'],
        $exceptionData['line'],
        $exceptionData['stack_trace'],
        $exceptionData['wc_version'],
        $exceptionData['wp_version'],
        $exceptionData['php_version'],
        $exceptionData['site_url']
    );

    // Log the message locally
    marvelLog($logMessage);

    // Send the diagnostic report - will be sent only if user allowed sending diagnostic
    if($send_diagnostic){
        send_diagnostics($exceptionData);
    }
}

// =======================================================================================
// =======================================================================================

function get_user_options() {
    try{
        // Default options
        $default_options = [
            'fast_msgs_active' => 0,    // false
            'floor_field_active' => 0,  // false
            'apartment_field_active' => 0,  // false
            'building_code_field_active' => 0,  // false
            'send_diagnostics_active' => 1  // true
        ];

        $options = [];
        foreach ($default_options as $key => $default_value) {
            $prefixed_key = 'marvelous_shipping_' . $key;
            $options[$key] = get_option($prefixed_key, $default_value);
        }

        return $options;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return [];
    }

}


// =======================================================================================
// =======================================================================================

function get_server_signature() {
    try{
        $file_path = normalize_path(plugin_dir_path(__FILE__) . "../cities/IL_cities.php"); // Adjust the path as needed

        // Check if the file exists
        if (!file_exists($file_path)) {
            marvelLog("get_server_signature: File does not exist: " . $file_path);
            return null; // Return null or handle the case where the file is missing
        }

        // Read the file content
        $file_content = file_get_contents($file_path);

        if ($file_content === false) {
            marvelLog("get_server_signature: Failed to read the file: " . $file_path);
            return null; // Return null or handle the error
        }

        // Use a regex pattern to extract the "Server Signature"
        $pattern = '/Server Signature:\s*([a-f0-9\-]+)/i';
        if (preg_match($pattern, $file_content, $matches)) {
            return $matches[1]; // Return the extracted Server Signature
        } else {
            marvelLog("get_server_signature: Server Signature not found in the file: " . $file_path);
            return null; // Return null if the signature is not found
        }
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return null;
    }

}

// =======================================================================================
// =======================================================================================

function normalize_path($path) {
    // Replace both slashes and backslashes with the correct DIRECTORY_SEPARATOR
    return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
}


// =======================================================================================
// =======================================================================================

function getDeviceType($userAgent) {
    if (empty($userAgent)) {
        return 'Unknown'; // Handle cases with no user agent
    }

    $userAgent = strtolower($userAgent);

    // Check for iOS devices
    if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false || strpos($userAgent, 'ipod') !== false) {
        return 'iOS';
    }

    // Check for Android devices
    if (strpos($userAgent, 'android') !== false) {
        return 'Android';
    }

    // Default to Desktop
    return 'Desktop';
}

// =======================================================================================
// =======================================================================================

function get_all_messages_data() {
    try{
        global $wpdb;

        // Define the table name with the WordPress prefix
        $table_name = $wpdb->prefix . 'marvelous_shipping_messages';

        // Query to fetch all rows from the table
        $query = "SELECT id, msg_active, msg_content FROM $table_name";

        // Execute the query
        $results = $wpdb->get_results($query);

        // Initialize the array to store messages data
        $messages_data = [];

        // Populate the array with the results
        if ($results) {
            foreach ($results as $row) {
                $messages_data[$row->id] = (object) [
                    'id' => $row->id,
                    'msg_active' => (bool)$row->msg_active,
                    'msg_content' => $row->msg_content,
                ];
            }
        }

        return $messages_data;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    }

}

// =======================================================================================
// =======================================================================================

function mrvl_GUID() {
    if (function_exists('com_create_guid') === true){
        return trim(com_create_guid(), '{}');
    }
    
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

// =======================================================================================
// =======================================================================================

function generateDisclaimer($disclaimer, $add_as_note=false) {
    try{
        // Get the WordPress timezone
        $wp_timezone = wp_timezone_string();
        try {
            // Check if the timezone is valid
            if (preg_match('/^[\+\-]\d{2}:\d{2}$/', $wp_timezone)) {
                // Convert offset to a named timezone (default to 'UTC' if mapping fails)
                $timezone = timezone_name_from_abbr('', strtotime($wp_timezone) - time(), 0) ?: 'UTC';
            } else {
                $timezone = $wp_timezone;
            }

            // Set the default timezone
            date_default_timezone_set($timezone);
        } catch (\Throwable $th) {
            logException($th, __FUNCTION__);
            // Fallback to UTC in case of error
            date_default_timezone_set('UTC');
        }

        // Format the current date and time
        $currentDateTime = date('d-m-Y H:i T');

        // Fixed parts
        $fixedLeft = "_______________________";
        $fixedRight = "________________________";
        

        // Total desired length excluding the newline character
        $totalLength = 94;
        if($add_as_note == true){
            $fixedLeft = "// _______________________ ";
            $fixedRight = "________________________ //";
            $totalLength = 100;
        }

        // Calculate the length of the fixed parts
        $fixedLength = strlen($fixedLeft) + strlen($fixedRight);

        // Calculate the available space for the date and padding
        $dateLength = strlen($currentDateTime);
        $remainingSpace = $totalLength - $fixedLength - $dateLength;

        // Divide the remaining space into left and right padding
        $leftPadding = floor($remainingSpace / 2);
        $rightPadding = ceil($remainingSpace / 2);

        // Construct the final string for the row with the placeholder
        $formattedDateRow = $fixedLeft 
            . str_repeat(' ', $leftPadding) 
            . $currentDateTime 
            . str_repeat(' ', $rightPadding) 
            . $fixedRight;

        // Replace the row containing "DateGoesHerePPPPPPPPP"
        $rows = explode("\n", $disclaimer); // Split disclaimer into rows
        foreach ($rows as &$row) {
            if (strpos($row, "DateGoesHerePPPPPPPPP") !== false) {
                $row = $formattedDateRow; // Replace the row
                break; // Stop after replacing the first match
            }
        }

        // Reconstruct the disclaimer
        $disclaimer = implode("\n", $rows);

        // Output the updated disclaimer
        return $disclaimer;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return '';
    }

}

// =======================================================================================
// =======================================================================================

function getLogFilePath() {
    // Get the current month and year
    $currentMonth = date('m'); // Current month (01 to 12)
    $currentYear = date('Y'); // Current year (e.g., 2025)
    $start_date = date('d-m-Y H:i:s'); // Current year (e.g., 2025)

    // Define the log folder path
    $upload_dir = wp_upload_dir();
    $logFolder = normalize_path($upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'marvelous-shipping-logs' . DIRECTORY_SEPARATOR);

    // Check if the log folder exists, create it if not
    if (!file_exists($logFolder)) {
        mkdir($logFolder, 0755, true);
    }

    // Define the log file path
    $logFile = $logFolder . "marvel-{$currentMonth}-{$currentYear}.log";

    // If the log file doesn't exist, create it
    if (!file_exists($logFile)) {
        file_put_contents($logFile, "Log started at {$start_date}\n");
        chmod($logFile, 0644);  // Set permissions to 0644 for the log file
    }

    return $logFile;
}

// =======================================================================================
// =======================================================================================

function marvelLog($message) {
    try{
        // Get the log file path for the current month
        $logFile = getLogFilePath();

        // Create the log message with a timestamp
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}]: {$message}\n";

        // Append the message to the log file
        file_put_contents($logFile, $logMessage, FILE_APPEND);
                        
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
    }

}

// =======================================================================================
// =======================================================================================

function isMobile() {
    $useragent=$_SERVER['HTTP_USER_AGENT'];
    return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
}
// =======================================================================================
// =======================================================================================

function generate_site_cities_file() {
    try {
        global $wpdb;
        global $israel_cities;
        global $site_file_header;
        include_once(plugin_dir_path(__FILE__) . "constants.php");
        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");

        // Handle database query errors
        if ($site_file_header === null || $israel_cities === null) {
            marvelLog('generate_site_cities_file: null params!');
            return false;
        }
        // File path
        $file_path = plugin_dir_path(__FILE__) . '../cities/site_cities.php';

        // Initialize the new array
        $site_cities = [];

        // Query the database for city details
        $query = "SELECT en_name, shipping_price, city_allowed FROM {$wpdb->prefix}marvelous_shipping_cities";
        $cities_data = $wpdb->get_results($query, ARRAY_A);

        // Handle database query errors
        if ($wpdb->last_error) {
            marvelLog('generate_site_cities_file: Database error: ' . $wpdb->last_error);
            return false;
        }

        // Create a map for quick lookup
        $cities_map = [];
        foreach ($cities_data as $city) {
            $cities_map[$city['en_name']] = [
                'shipping_price' => $city['shipping_price'],
                'city_allowed' => (bool) $city['city_allowed'],
            ];
        }

        // Iterate through the israel_cities array and build the new array
        foreach ($israel_cities as $en_name => $streets) {
            $shipping_price = $cities_map[$en_name]['shipping_price'] ?? null;
            $city_allowed = $cities_map[$en_name]['city_allowed'] ?? true;

            $site_cities[$en_name] = [
                'city_shipping_price' => $shipping_price,
                'city_allowed' => $city_allowed,
                'city_streets' => $streets,
            ];
        }

        $site_file_header = generateDisclaimer($site_file_header, true);

        // Generate the PHP content
        $file_content = "<?php\n\n";
        $file_content .= $site_file_header . "\n";
        $file_content .= "// Global variable declaration\n";
        $file_content .= "global \$site_cities;\n";
        $file_content .= "global \$site_settings;\n";
        $file_content .= "global \$file_sig;\n\n";

        $file_content .= "// config file GUID\n";
        $file_content .= "\$file_sig = '" . mrvl_GUID() . "';\n\n";


        $file_content .= "\$site_cities = " . var_export($site_cities, true) . ";\n";

        // Write to the file
        if (!is_dir(dirname($file_path))) {
            mkdir(dirname($file_path), 0755, true);
        }

        if (file_put_contents($file_path, $file_content) === false) {
            marvelLog('generate_site_cities_file: Failed to write to file: ' . $file_path);
            return false;
        }

        return true;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return false;
    }
}

// =======================================================================================
// =======================================================================================

function mrvl_encrypted($data) {
    try{
        if (is_array($data)) {
            // Convert array to JSON string
            $data = json_encode($data);
            if ($data === false) {
                return null;
            }
        } elseif (!is_string($data)) {
            return null;
        }

        $encryptedData = base64_encode($data);

        return [
            'encryptedData' => $encryptedData,
        ];
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return null;
    }
}


// =======================================================================================
// =======================================================================================

function countOrdersByDistrict() {
    try{
        global $wpdb;

        // Define the table name with the WordPress prefix
        $ordersLogTable = $wpdb->prefix . 'marvelous_shipping_orders_log';

        // Query to count the number of orders grouped by district
        $query = "
            SELECT district_heb_name, COUNT(*) AS order_count
            FROM $ordersLogTable
            GROUP BY district_heb_name
            ORDER BY order_count DESC
        ";

        // Execute the query and get results
        $results = $wpdb->get_results($query);

        // Prepare the result in an associative array
        $orderCounts = [];
        foreach ($results as $row) {
            $orderCounts[$row->district_heb_name] = $row->order_count;
        }
        return $orderCounts;
    } catch (\Throwable $th) {
        logException($th, __FUNCTION__);
        return [];
    }

}


// =======================================================================================
// =======================================================================================

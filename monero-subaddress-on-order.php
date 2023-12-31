<?php
/**
 * Plugin Name: Monero Subaddress on Order
 * Description: Generates a Monero subaddress for each order.
 * Version: 1.0
 * Author: Your Name
 */

function generate_monero_subaddress_on_order($order_id) {
    // Ensure WooCommerce is active
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        // Load WooCommerce functions
        include_once(WC()->plugin_path() . '/includes/wc-core-functions.php');

        $rpc_url = 'http://127.0.0.1:18080/json_rpc'; // Replace with your Monero RPC URL

        $request_body = json_encode([
            'jsonrpc' => '2.0',
            'id' => '0',
            'method' => 'create_address',
            'params' => [
                'count' => 1,
            ],
        ]);

        // Log the request for debugging
        error_log('Monero RPC Request for Order ' . $order_id . ': ' . $request_body);

        $response = wp_remote_post($rpc_url, [
            'body' => $request_body,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            // Log error details for debugging
            error_log('Monero RPC Error for Order ' . $order_id . ': ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);

            // Log the response for debugging
            error_log('Monero RPC Response for Order ' . $order_id . ': ' . $body);

            $result = json_decode($body, true);

            if (isset($result['result']['address'])) {
                // Update the order with the generated subaddress
                update_post_meta($order_id, '_monero_subaddress', $result['result']['address']);
            } else {
                // Log an error message if the response format is unexpected
                error_log('Monero RPC Error: Unexpected response format for Order ' . $order_id);

                // Log the unexpected response for further investigation
                error_log('Monero RPC Unexpected Response: ' . $body);
            }
        }
    }
}

add_action('woocommerce_new_order', 'generate_monero_subaddress_on_order', 10, 1);

// Enqueue script for AJAX request on checkout page
add_action('wp_enqueue_scripts', 'monero_subaddress_ajax_script');

function monero_subaddress_ajax_script() {
    wp_enqueue_script('monero-subaddress-ajax', plugin_dir_url(__FILE__) . 'monero-subaddress-ajax.js', array('jquery'), '1.0', true);
    
    // Pass the necessary variables to script.js
    wp_localize_script('monero-subaddress-ajax', 'monero_subaddress_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}

// Display the subaddress on the checkout page
add_action('woocommerce_before_checkout_form', 'display_monero_subaddress_on_checkout');

function display_monero_subaddress_on_checkout() {
    $order_id = wc_get_checkout_order_received_id();
    $subaddress = get_post_meta($order_id, '_monero_subaddress', true);

    if (!empty($subaddress)) {
        echo '<p id="monero-subaddress-container"><strong>Monero Subaddress:</strong> ' . esc_html($subaddress) . '</p>';
    }
}

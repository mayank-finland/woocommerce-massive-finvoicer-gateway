<?php
/**
 * Plugin Name: WooCommerce MASSIVE Finvoicer
 * Plugin URI: https://github.com/mayank-finland
 * Description: Accept billing option for payments in WooCommerce with Finvoicer gateway
 * Author: Mayank Jain / MASSIVE Helsinki
 * Author URI: http://www.massivehelsinki.fi
 * Version: 1.0
 * Text Domain: woocommerce-gateway-finvoicer
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2016 MASSIVE Helsinki. (mail@massivehelsinki.fi)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Finvoicer
 * @author    MASSIVE Helsinki
 * @category  Payment-Gateways
 * @copyright Copyright (c) 2016, MASSIVE Helsinki.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

// !1 Functions used by plugins

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include our Gateway Class and Register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'massive_finvoicer_gateway_init', 0 );
function massive_finvoicer_gateway_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	// If we made it this far, then include our Gateway Class
	include_once( 'woocommerce-finvoicer-class.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_finvoicer_gateway' );
	function add_finvoicer_gateway( $methods ) {
		$methods[] = 'MASSIVE_Finvoicer_Gateway';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'massive_finvoicer_gateway_action_links' );
function massive_finvoicer_gateway_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'massive-finvoicer-gateway' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

 ?>
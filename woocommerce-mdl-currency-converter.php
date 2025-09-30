<?php
/**
 * Plugin Name: WooCommerce MDL Currency Converter
 * Plugin URI: https://github.com/vadikonline1/woocommerce-mdl-currency-converter/
 * Author URI: https://github.com/vadikonline1/woocommerce-mdl-currency-converter/
 * GitHub Plugin URI: https://github.com/vadikonline1/woocommerce-mdl-currency-converter/
 * Description: Converteste preturile din EUR in MDL la checkout cu comision configurable
 * Version: 0.0.1
 * Author: Steel..xD
 * Text Domain: wc-mdl-converter
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Verifica daca WooCommerce este activ
add_action('plugins_loaded', 'wc_mdl_converter_init');

function wc_mdl_converter_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_mdl_converter_missing_wc_notice');
        return;
    }
    
    // Incarca clasele principale
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-mdl-settings.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-mdl-converter.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-mdl-api-handler.php';
    
    // Initializeaza plugin-ul
    new WC_MDL_Settings();
    new WC_MDL_Converter();
    new WC_MDL_API_Handler();
    
    // Incarca textdomain pentru internationalizare
    load_plugin_textdomain('wc-mdl-converter', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function wc_mdl_converter_missing_wc_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce MDL Currency Converter necesita WooCommerce pentru a functiona!', 'wc-mdl-converter'); ?></p>
    </div>
    <?php
}

// Hook-uri de activare/dezactivare
register_activation_hook(__FILE__, 'wc_mdl_converter_activate');
register_deactivation_hook(__FILE__, 'wc_mdl_converter_deactivate');

function wc_mdl_converter_activate() {
    // Seteaza optiuni default la activare
    if (false === get_option('wc_mdl_exchange_rate')) {
        update_option('wc_mdl_exchange_rate', '19.50');
    }
    if (false === get_option('wc_mdl_commission')) {
        update_option('wc_mdl_commission', '2');
    }
    if (false === get_option('wc_mdl_use_maib_api')) {
        update_option('wc_mdl_use_maib_api', 'yes');
    }
    
    // Sterge cache-ul vechi
    delete_transient('wc_mdl_maib_exchange_rate');
}

function wc_mdl_converter_deactivate() {
    // Sterge cache-ul la dezactivare
    delete_transient('wc_mdl_maib_exchange_rate');
}
?>

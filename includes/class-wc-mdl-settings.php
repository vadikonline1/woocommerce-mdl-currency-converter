<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_MDL_Settings {
    
    public function __construct() {
        add_filter('woocommerce_get_settings_pages', array($this, 'add_settings_tab'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }
    
    public function add_settings_tab($settings) {
        $settings[] = include plugin_dir_path(__FILE__) . 'class-wc-mdl-settings-page.php';
        return $settings;
    }
    
    public function admin_scripts($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }
        
        if (isset($_GET['tab']) && 'mdl_converter' === $_GET['tab']) {
            wp_enqueue_script('wc-mdl-admin', plugin_dir_url(__FILE__) . '../assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('wc-mdl-admin', plugin_dir_url(__FILE__) . '../assets/admin.css', array(), '1.0.0');
        }
    }
}
?>

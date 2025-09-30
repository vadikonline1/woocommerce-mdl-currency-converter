<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_MDL_Converter {
    
    private $api_handler;
    
    public function __construct() {
        $this->api_handler = new WC_MDL_API_Handler();
        
        add_action('wp_loaded', array($this, 'init'));
    }
    
    public function init() {
        if (!class_exists('WooCommerce') || get_option('wc_mdl_enabled') !== 'yes') {
            return;
        }
        
        // Adauga afișarea prețului în MDL la checkout
        add_action('woocommerce_review_order_before_order_total', array($this, 'display_mdl_total_at_checkout'));
        
        // Modifica totalul pentru procesatori de plăți
        add_action('woocommerce_calculate_totals', array($this, 'modify_checkout_total'), 1000);
        
        // Salveaza datele conversiei in comanda
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_conversion_data_to_order'));
        
        // Afișează detaliile conversiei în admin order
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_conversion_data_in_admin'));
    }
    
    public function calculate_mdl_total($eur_amount) {
        $exchange_rate = $this->api_handler->get_exchange_rate();
        $commission = floatval(get_option('wc_mdl_commission', 2)) / 100;
        
        // Calculeaza totalul in MDL
        $mdl_amount = $eur_amount * $exchange_rate;
        
        // Aplica comisionul
        $mdl_with_commission = $mdl_amount * (1 + $commission);
        
        // Rotunjeste la 2 zecimale
        return round($mdl_with_commission, 2);
    }
    
    public function display_mdl_total_at_checkout() {
        if (!is_checkout()) {
            return;
        }
        
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return;
        }
        
        $cart_total_eur = $cart->total;
        $exchange_rate = $this->api_handler->get_exchange_rate();
        $commission = floatval(get_option('wc_mdl_commission', 2));
        $final_mdl = $this->calculate_mdl_total($cart_total_eur);
        
        echo '<tr class="mdl-conversion-info">';
        echo '<th>' . __('Total de plată în MDL', 'wc-mdl-converter') . '</th>';
        echo '<td class="mdl-total-display">';
        echo '<div class="mdl-calculation">';
        echo number_format($cart_total_eur, 2) . ' EUR × ';
        echo $exchange_rate . ' (curs) × ';
        echo '(' . $commission . '% comision) = ';
        echo '</div>';
        echo '<strong>' . number_format($final_mdl, 2) . ' MDL</strong>';
        echo '</td>';
        echo '</tr>';
        
        // Salveaza in sesiune pentru procesatori de plata
        WC()->session->set('checkout_total_mdl', $final_mdl);
        WC()->session->set('exchange_rate_used', $exchange_rate);
        WC()->session->set('commission_used', $commission);
    }
    
    public function modify_checkout_total($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (is_checkout() || (defined('DOING_AJAX') && DOING_AJAX)) {
            $cart_total_eur = $cart->total;
            $final_mdl = $this->calculate_mdl_total($cart_total_eur);
            
            WC()->session->set('checkout_total_mdl', $final_mdl);
        }
    }
    
    public function save_conversion_data_to_order($order_id) {
        $order = wc_get_order($order_id);
        
        $mdl_total = WC()->session->get('checkout_total_mdl');
        $exchange_rate = WC()->session->get('exchange_rate_used');
        $commission = WC()->session->get('commission_used');
        $eur_total = $order->get_total();
        
        if ($mdl_total && $exchange_rate) {
            $order->update_meta_data('_converted_to_mdl', 'yes');
            $order->update_meta_data('_original_total_eur', $eur_total);
            $order->update_meta_data('_converted_total_mdl', $mdl_total);
            $order->update_meta_data('_exchange_rate_used', $exchange_rate);
            $order->update_meta_data('_commission_used', $commission);
            $order->save();
        }
    }
    
    public function display_conversion_data_in_admin($order) {
        $converted = $order->get_meta('_converted_to_mdl');
        
        if ($converted === 'yes') {
            $original_eur = $order->get_meta('_original_total_eur');
            $converted_mdl = $order->get_meta('_converted_total_mdl');
            $exchange_rate = $order->get_meta('_exchange_rate_used');
            $commission = $order->get_meta('_commission_used');
            
            ?>
            <div class="order_data_column">
                <h3><?php _e('Conversie MDL', 'wc-mdl-converter'); ?></h3>
                <p>
                    <strong><?php _e('Total original:', 'wc-mdl-converter'); ?></strong>
                    <?php echo number_format($original_eur, 2); ?> EUR
                </p>
                <p>
                    <strong><?php _e('Curs de schimb:', 'wc-mdl-converter'); ?></strong>
                    <?php echo $exchange_rate; ?>
                </p>
                <p>
                    <strong><?php _e('Comision:', 'wc-mdl-converter'); ?></strong>
                    <?php echo $commission; ?>%
                </p>
                <p>
                    <strong><?php _e('Total MDL:', 'wc-mdl-converter'); ?></strong>
                    <?php echo number_format($converted_mdl, 2); ?> MDL
                </p>
            </div>
            <?php
        }
    }
}
?>

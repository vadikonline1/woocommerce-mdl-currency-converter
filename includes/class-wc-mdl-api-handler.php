<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_MDL_API_Handler {
    
    private $cache_duration;
    
    public function __construct() {
        $this->cache_duration = intval(get_option('wc_mdl_cache_hours', 24)) * HOUR_IN_SECONDS;
        
        add_action('wp_ajax_wc_mdl_force_update_rate', array($this, 'force_update_exchange_rate'));
    }
    
    public function get_exchange_rate() {
        // Verifica daca este forțată rata manuala
        if (get_option('wc_mdl_force_manual') === 'yes') {
            $this->log('Folosire rata manuală (forțat)');
            return floatval(get_option('wc_mdl_exchange_rate', '19.50'));
        }
        
        // Verifica cache
        $cached_rate = get_transient('wc_mdl_maib_exchange_rate');
        
        if ($cached_rate !== false) {
            $this->log('Folosire rata din cache: ' . $cached_rate);
            return floatval($cached_rate);
        }
        
        // Daca API-ul este activat, incearca sa obtii rata
        if (get_option('wc_mdl_use_maib_api') === 'yes') {
            $api_rate = $this->fetch_maib_exchange_rate();
            
            if ($api_rate !== false) {
                // Salveaza in cache
                set_transient('wc_mdl_maib_exchange_rate', $api_rate, $this->cache_duration);
                set_transient('wc_mdl_maib_last_updated', time(), $this->cache_duration);
                
                $this->log('Rata obținută de la MAIB API: ' . $api_rate);
                return $api_rate;
            }
        }
        
        // Fallback la rata manuala
        $manual_rate = floatval(get_option('wc_mdl_exchange_rate', '19.50'));
        $this->log('Folosire rata manuală (fallback): ' . $manual_rate);
        return $manual_rate;
    }
    
    private function fetch_maib_exchange_rate() {
        $current_date = date('Ymd');
        $api_url = "http://pki.maib.md/rates/VIR{$current_date}.json";
        
        $this->log('Încercare preluare curs de la: ' . $api_url);
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            $this->log('Eroare la conectarea la API-ul MAIB: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log('Răspuns HTTP neașteptat: ' . $response_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->log('Răspuns JSON invalid de la API-ul MAIB: ' . json_last_error_msg());
            return false;
        }
        
        // Cauta EUR in date
        foreach ($data as $currency) {
            if (isset($currency['currencies_name']) && $currency['currencies_name'] === 'EUR') {
                if (isset($currency['sell']) && is_numeric($currency['sell'])) {
                    $rate = floatval($currency['sell']);
                    $this->log('Rata EUR/MDL găsită: ' . $rate);
                    return $rate;
                }
            }
        }
        
        $this->log('Nu s-a găsit rata EUR în răspunsul API-ului MAIB');
        return false;
    }
    
    public function force_update_exchange_rate() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(-1);
        }
        
        check_ajax_referer('wc_mdl_force_update', 'nonce');
        
        // Sterge cache-ul pentru a forța o reimprospătare
        delete_transient('wc_mdl_maib_exchange_rate');
        delete_transient('wc_mdl_maib_last_updated');
        
        // Obține noua rată
        $new_rate = $this->get_exchange_rate();
        
        if ($new_rate !== false) {
            wp_send_json_success(array(
                'rate' => $new_rate,
                'updated' => date('d.m.Y H:i:s'),
                'message' => __('Cursul a fost actualizat cu succes!', 'wc-mdl-converter')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Eroare la actualizarea cursului. Verifica log-urile pentru detalii.', 'wc-mdl-converter')
            ));
        }
    }
    
    private function log($message) {
        if (get_option('wc_mdl_enable_logging') === 'yes' && function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'wc-mdl-converter'));
        }
    }
}
?>

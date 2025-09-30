<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Settings_MDL_Converter extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'mdl_converter';
        $this->label = __('Converter MDL', 'wc-mdl-converter');
        
        parent::__construct();
        
        add_action('woocommerce_admin_field_exchange_rate_info', array($this, 'exchange_rate_info_field'));
    }
    
    public function get_settings() {
        $settings = array(
            array(
                'title' => __('Setari Converter EUR to MDL', 'wc-mdl-converter'),
                'type'  => 'title',
                'desc'  => __('Configureaza conversia automata din EUR in MDL pentru checkout cu comision.', 'wc-mdl-converter'),
                'id'    => 'mdl_converter_options',
            ),
            
            array(
                'title'    => __('Activeaza Conversia', 'wc-mdl-converter'),
                'desc'     => __('Activeaza conversia automata EUR to MDL la checkout', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => __('Daca este dezactivat, preturile vor ramane in EUR.', 'wc-mdl-converter'),
            ),
            
            array(
                'title'    => __('Foloseste API MAIB', 'wc-mdl-converter'),
                'desc'     => __('Activeaza preluarea automata a cursului de la MAIB', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_use_maib_api',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => __('Cursul se va actualiza automat zilnic de la Banca MAIB.', 'wc-mdl-converter'),
            ),
            
            array(
                'title'    => __('Rata de Schimb Manuala', 'wc-mdl-converter'),
                'desc'     => __('Setati rata de schimb manuala (folosita daca API-ul este dezactivat sau esueaza)', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_exchange_rate',
                'default'  => '19.50',
                'type'     => 'text',
                'css'      => 'width:100px;',
                'desc_tip' => true,
            ),
            
            array(
                'title'    => __('Comision Conversie (%)', 'wc-mdl-converter'),
                'desc'     => __('Comisionul aplicat la conversia in MDL (ex: 2 pentru 2%)', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_commission',
                'default'  => '2',
                'type'     => 'number',
                'css'      => 'width:100px;',
                'custom_attributes' => array(
                    'step' => '0.1',
                    'min'  => '0',
                    'max'  => '10',
                ),
                'desc_tip' => __('Acest comision se adauga la suma convertita in MDL.', 'wc-mdl-converter'),
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'mdl_converter_options',
            ),
            
            array(
                'title' => __('Informatii Curs Valutar', 'wc-mdl-converter'),
                'type'  => 'title',
                'desc'  => __('Statusul curent al cursului valutar.', 'wc-mdl-converter'),
                'id'    => 'mdl_converter_info',
            ),
            
            array(
                'type' => 'exchange_rate_info',
                'id'   => 'wc_mdl_exchange_rate_info',
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'mdl_converter_info',
            ),
            
            array(
                'title' => __('Setari Avansate', 'wc-mdl-converter'),
                'type'  => 'title',
                'desc'  => __('Setari avansate pentru controlul cache-ului si depanare.', 'wc-mdl-converter'),
                'id'    => 'mdl_converter_advanced',
            ),
            
            array(
                'title'    => __('Forteaza Rata Manuala', 'wc-mdl-converter'),
                'desc'     => __('Foloseste intotdeauna rata manuala (ignora API-ul)', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_force_manual',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => __('Util pentru testare sau in caz de probleme cu API-ul.', 'wc-mdl-converter'),
            ),
            
            array(
                'title'    => __('Durata Cache (ore)', 'wc-mdl-converter'),
                'desc'     => __('Cat timp sa se pastreze in cache cursul valutar', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_cache_hours',
                'default'  => '24',
                'type'     => 'number',
                'css'      => 'width:100px;',
                'custom_attributes' => array(
                    'min'  => '1',
                    'max'  => '168',
                ),
                'desc_tip' => __('Recomandat 24 de ore. Minim 1 ora, maxim 168 ore (7 zile).', 'wc-mdl-converter'),
            ),
            
            array(
                'title'    => __('Activeaza Logging', 'wc-mdl-converter'),
                'desc'     => __('Inregistreaza erorile si activitatea in log-urile WooCommerce', 'wc-mdl-converter'),
                'id'       => 'wc_mdl_enable_logging',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => __('Util pentru depanare. Verifica log-urile in WooCommerce > Status > Logs.', 'wc-mdl-converter'),
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'mdl_converter_advanced',
            ),
        );

        return apply_filters('woocommerce_mdl_converter_settings', $settings);
    }
    
    public function exchange_rate_info_field($value) {
        $exchange_rate = get_transient('wc_mdl_maib_exchange_rate');
        $last_updated = get_transient('wc_mdl_maib_last_updated');
        $manual_rate = get_option('wc_mdl_exchange_rate', '19.50');
        $using_api = get_option('wc_mdl_use_maib_api') === 'yes';
        $force_manual = get_option('wc_mdl_force_manual') === 'yes';
        
        $current_rate = $force_manual ? $manual_rate : ($exchange_rate ? $exchange_rate : $manual_rate);
        $rate_source = $force_manual ? 'manuală (forțat)' : ($using_api && $exchange_rate ? 'MAIB API' : 'manuală');
        
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php _e('Curs Valutar Curent', 'wc-mdl-converter'); ?>
            </th>
            <td class="forminp">
                <div class="exchange-rate-info">
                    <p><strong><?php echo esc_html($current_rate); ?> MDL</strong> pentru 1 EUR</p>
                    <p class="description">
                        <?php 
                        printf(
                            __('Sursă: %s', 'wc-mdl-converter'),
                            '<strong>' . esc_html($rate_source) . '</strong>'
                        );
                        ?>
                    </p>
                    <?php if ($last_updated): ?>
                    <p class="description">
                        <?php 
                        printf(
                            __('Actualizat la: %s', 'wc-mdl-converter'),
                            date('d.m.Y H:i:s', $last_updated)
                        );
                        ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($using_api && !$force_manual): ?>
                    <p>
                        <button type="button" class="button button-secondary" id="wc_mdl_force_update">
                            <?php _e('Actualizează acum', 'wc-mdl-converter'); ?>
                        </button>
                        <span class="spinner" id="wc_mdl_update_spinner" style="float:none; margin-left:5px;"></span>
                    </p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
}

return new WC_Settings_MDL_Converter();
?>

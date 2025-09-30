<?php
add_action('admin_head', 'wc_mdl_admin_script_vars');

function wc_mdl_admin_script_vars() {
    if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
        return;
    }
    
    if (isset($_GET['tab']) && 'mdl_converter' === $_GET['tab']) {
        ?>
        <script type="text/javascript">
            var wc_mdl_admin = {
                nonce: '<?php echo wp_create_nonce('wc_mdl_force_update'); ?>'
            };
        </script>
        <?php
    }
}
?>

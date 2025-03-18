<?php
/**
 * Compatibility features for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Declarar compatibilidad con HPOS una vez que WooCommerce está cargado
function mam_declare_hpos_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            plugin_basename(MAM_PLUGIN_DIR . 'my-account-manager.php'),
            true
        );
    }
}
add_action('before_woocommerce_init', 'mam_declare_hpos_compatibility');

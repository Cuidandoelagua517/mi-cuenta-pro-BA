<?php
/**
 * Core functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Core Class
 */
class MAM_Core {

    /**
     * Constructor
     */
    public function __construct() {
        // Filtros para personalizar "Mi cuenta"
        add_filter('woocommerce_account_menu_items', array($this, 'custom_account_menu_items'), 10, 1);
        add_filter('woocommerce_get_endpoint_url', array($this, 'custom_endpoint_urls'), 10, 4);
        
        // Registrar endpoints personalizados
        add_action('init', array($this, 'register_custom_endpoints'));
        
        // Reemplazar templates
        add_filter('woocommerce_locate_template', array($this, 'override_woocommerce_templates'), 10, 3);

        // Solo para administradores
add_action('woocommerce_account_dashboard', 'mam_debug_user_data', 1);
    }
/**
 * Verificar los valores de CUIT y empresa almacenados
 */
function mam_debug_user_data() {
    if (!current_user_can('manage_options') || !isset($_GET['mam_debug'])) {
        return;
    }
    
    $user_id = get_current_user_id();
    $data = array(
        'cuit' => get_user_meta($user_id, 'cuit', true),
        'billing_cuit' => get_user_meta($user_id, 'billing_cuit', true),
        'shipping_cuit' => get_user_meta($user_id, 'shipping_cuit', true),
        'company_name' => get_user_meta($user_id, 'company_name', true),
        'billing_company' => get_user_meta($user_id, 'billing_company', true),
        'shipping_company' => get_user_meta($user_id, 'shipping_company', true),
    );
    
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
    /**
     * Personalizar los elementos del menú de Mi cuenta
     */
    public function custom_account_menu_items($menu_items) {
        // Reordenar o renombrar elementos del menú
        $new_menu_items = array();
        
        // Dashboard
        if (isset($menu_items['dashboard'])) {
            $new_menu_items['dashboard'] = __('Panel Principal', 'my-account-manager');
        }
        
        // Pedidos
        if (isset($menu_items['orders'])) {
            $new_menu_items['orders'] = __('Mis Pedidos', 'my-account-manager');
        }
        
        // Descargas
        if (isset($menu_items['downloads'])) {
            $new_menu_items['downloads'] = __('Mis Descargas', 'my-account-manager');
        }
        
        // Direcciones
        if (isset($menu_items['edit-address'])) {
            $new_menu_items['edit-address'] = __('Mis Direcciones', 'my-account-manager');
        }
        
        // Detalles de cuenta
        if (isset($menu_items['edit-account'])) {
            $new_menu_items['edit-account'] = __('Datos de Cuenta', 'my-account-manager');
        }
        
        // Método de pago (si existe)
        if (isset($menu_items['payment-methods'])) {
            $new_menu_items['payment-methods'] = __('Métodos de Pago', 'my-account-manager');
        }
        
        // Cerrar sesión siempre al final
        if (isset($menu_items['customer-logout'])) {
            $new_menu_items['customer-logout'] = __('Cerrar Sesión', 'my-account-manager');
        }
        
        // Añadir elementos personalizados
        // $new_menu_items['custom-endpoint'] = __('Elemento Personalizado', 'my-account-manager');
        
        return $new_menu_items;
    }

    /**
     * Personalizar URLs de endpoints
     */
    public function custom_endpoint_urls($url, $endpoint, $value, $permalink) {
        // Personalizar URLs específicas si es necesario
        return $url;
    }

    /**
     * Registrar endpoints personalizados
     */
    public function register_custom_endpoints() {
        // Añadir endpoints personalizados para páginas específicas
        // add_rewrite_endpoint('custom-endpoint', EP_ROOT | EP_PAGES);
        
        // No olvidar hacer flush de las reglas de reescritura cuando se active el plugin
    }

    /**
     * Sobreescribir templates de WooCommerce
     */
public function override_woocommerce_templates($template, $template_name, $template_path) {
    // Remover logs de depuración
    
    $plugin_path = MAM_PLUGIN_DIR . 'templates/';
    $template_in_plugin = $plugin_path . $template_name;
    
    if (file_exists($template_in_plugin)) {
        return $template_in_plugin;
    }
    
    return $template;
}
}
// Inicializar la clase core
new MAM_Core();

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
    global $woocommerce;
    
    // Rutas para buscar templates
    $plugin_path = MAM_PLUGIN_DIR . 'templates/';
    
    // Buscar el template en nuestro plugin (asegurando ruta completa)
    $template_in_plugin = $plugin_path . $template_name;
    
    // Verificar si existe con ruta exacta
    if (file_exists($template_in_plugin)) {
        return $template_in_plugin;
    }
    
    // En caso de debug, descomentar estas líneas
    /*
    if (strpos($template_name, 'dashboard') !== false) {
        error_log('Buscando template: ' . $template_in_plugin);
        error_log('Template original: ' . $template);
    }
    */
    
    return $template;
}
}
// Inicializar la clase core
new MAM_Core();

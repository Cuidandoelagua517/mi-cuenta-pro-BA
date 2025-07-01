<?php
/**
 * MAM CUIT Integration Hooks
 * 
 * Archivo para agregar a includes/integrations/class-mam-cuit-integration.php
 * Asegura que el CUIT se propague correctamente en todo WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAM_CUIT_Integration {
    
    private static $instance = null;
    
    private function __construct() {
        // Hooks para el checkout
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_cuit_on_checkout'));
        
        // Hooks para la API REST de WooCommerce
        add_action('woocommerce_rest_insert_customer', array($this, 'save_cuit_via_rest_api'), 10, 3);
        add_filter('woocommerce_rest_customer_schema', array($this, 'add_cuit_to_rest_schema'));
        
        // Hooks para importación/exportación
        add_filter('woocommerce_customer_import_mapping_options', array($this, 'add_cuit_to_import_mapping'));
        add_filter('woocommerce_customer_import_mapping_default_columns', array($this, 'add_cuit_default_mapping'));
        
        // Hooks para reportes
        add_filter('woocommerce_admin_reports_customer_columns', array($this, 'add_cuit_to_reports'));
        
        // Hooks para emails
        add_action('woocommerce_email_customer_details', array($this, 'add_cuit_to_emails'), 15, 4);
        
        // Hooks para la API de clientes
        add_filter('woocommerce_customer_meta_fields', array($this, 'add_cuit_to_customer_meta'));
        
        // Hook para sincronización automática
        add_action('user_register', array($this, 'sync_cuit_on_registration'), 10, 1);
        add_action('woocommerce_created_customer', array($this, 'sync_cuit_on_wc_registration'), 10, 3);
        
        // Agregar CUIT a los datos del cliente en sesión
        add_filter('woocommerce_checkout_get_value', array($this, 'get_cuit_value_for_checkout'), 10, 2);
        
        // Validación global
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_cuit_globally'), 10, 2);
        
        // Agregar a las facturas PDF (si usa algún plugin de facturas)
        add_filter('wpo_wcpdf_billing_address', array($this, 'add_cuit_to_pdf_invoice'));
        
        // Agregar al customer lookup table para mejorar performance
        add_action('woocommerce_update_customer', array($this, 'update_customer_lookup_cuit'));
    }
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Guardar CUIT en el checkout
     */
    public function save_cuit_on_checkout($order_id) {
        if (isset($_POST['billing_cuit'])) {
            $cuit = sanitize_text_field($_POST['billing_cuit']);
            update_post_meta($order_id, '_billing_cuit', $cuit);
            
            // También actualizar el usuario si está logueado
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                update_user_meta($user_id, 'billing_cuit', $cuit);
                update_user_meta($user_id, 'cuit', $cuit);
            }
        }
    }
    
    /**
     * Guardar CUIT via REST API
     */
    public function save_cuit_via_rest_api($user, $request, $creating) {
        if (isset($request['billing_cuit'])) {
            update_user_meta($user->ID, 'billing_cuit', sanitize_text_field($request['billing_cuit']));
            update_user_meta($user->ID, 'cuit', sanitize_text_field($request['billing_cuit']));
        }
    }
    
    /**
     * Agregar CUIT al schema REST
     */
    public function add_cuit_to_rest_schema($schema) {
        $schema['properties']['billing_cuit'] = array(
            'description' => __('CUIT del cliente', 'my-account-manager'),
            'type'        => 'string',
            'context'     => array('view', 'edit'),
        );
        return $schema;
    }
    
    /**
     * Agregar CUIT a opciones de importación
     */
    public function add_cuit_to_import_mapping($options) {
        $options['billing_cuit'] = __('CUIT', 'my-account-manager');
        return $options;
    }
    
    /**
     * Mapeo por defecto para importación
     */
    public function add_cuit_default_mapping($columns) {
        $columns[__('CUIT', 'my-account-manager')] = 'billing_cuit';
        $columns[__('Billing CUIT', 'my-account-manager')] = 'billing_cuit';
        return $columns;
    }
    
    /**
     * Agregar CUIT a reportes
     */
    public function add_cuit_to_reports($columns) {
        $columns['billing_cuit'] = array(
            'title' => __('CUIT', 'my-account-manager'),
            'type'  => 'string',
        );
        return $columns;
    }
    
    /**
     * Agregar CUIT a emails
     */
    public function add_cuit_to_emails($order, $sent_to_admin, $plain_text, $email) {
        if ($cuit = get_post_meta($order->get_id(), '_billing_cuit', true)) {
            if ($plain_text) {
                echo "\n" . __('CUIT:', 'my-account-manager') . ' ' . $cuit . "\n";
            } else {
                echo '<p><strong>' . __('CUIT:', 'my-account-manager') . '</strong> ' . esc_html($cuit) . '</p>';
            }
        }
    }
    
    /**
     * Agregar CUIT a meta fields del cliente
     */
    public function add_cuit_to_customer_meta($fields) {
        $fields['billing']['fields']['billing_cuit'] = array(
            'label'       => __('CUIT', 'my-account-manager'),
            'description' => __('Clave Única de Identificación Tributaria', 'my-account-manager'),
        );
        return $fields;
    }
    
    /**
     * Sincronizar CUIT en registro
     */
    public function sync_cuit_on_registration($user_id) {
        if (isset($_POST['billing_cuit'])) {
            $cuit = sanitize_text_field($_POST['billing_cuit']);
            update_user_meta($user_id, 'billing_cuit', $cuit);
            update_user_meta($user_id, 'cuit', $cuit);
        }
    }
    
    /**
     * Sincronizar CUIT en registro WooCommerce
     */
    public function sync_cuit_on_wc_registration($customer_id, $new_customer_data, $password_generated) {
        if (isset($_POST['billing_cuit'])) {
            $cuit = sanitize_text_field($_POST['billing_cuit']);
            update_user_meta($customer_id, 'billing_cuit', $cuit);
            update_user_meta($customer_id, 'cuit', $cuit);
        }
    }
    
    /**
     * Obtener valor CUIT para checkout
     */
    public function get_cuit_value_for_checkout($value, $input) {
        if ($input === 'billing_cuit' && is_user_logged_in() && empty($value)) {
            $user_id = get_current_user_id();
            $value = get_user_meta($user_id, 'billing_cuit', true);
            if (empty($value)) {
                $value = get_user_meta($user_id, 'cuit', true);
            }
        }
        return $value;
    }
    
    /**
     * Validación global de CUIT
     */
    public function validate_cuit_globally($data, $errors) {
        if (!empty($data['billing_cuit'])) {
            $cuit = $data['billing_cuit'];
            
            // Validar formato
            if (!$this->validate_cuit_format($cuit)) {
                $errors->add('validation', __('El formato del CUIT no es válido. Debe tener el formato XX-XXXXXXXX-X', 'my-account-manager'));
            }
        }
    }
    
    /**
     * Agregar CUIT a facturas PDF
     */
    public function add_cuit_to_pdf_invoice($address) {
        global $wpo_wcpdf;
        
        if (isset($wpo_wcpdf->export->order)) {
            $order = $wpo_wcpdf->export->order;
            $cuit = get_post_meta($order->get_id(), '_billing_cuit', true);
            
            if ($cuit) {
                $address .= "\n" . __('CUIT:', 'my-account-manager') . ' ' . $cuit;
            }
        }
        
        return $address;
    }
    
    /**
     * Actualizar customer lookup table
     */
    public function update_customer_lookup_cuit($customer_id) {
        global $wpdb;
        
        $cuit = get_user_meta($customer_id, 'billing_cuit', true);
        
        // Guardar en una tabla personalizada si existe
        $table_name = $wpdb->prefix . 'wc_customer_lookup';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            // Verificar si la columna existe, si no, crearla
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'cuit'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN cuit VARCHAR(15) DEFAULT NULL");
            }
            
            // Actualizar el valor
            $wpdb->update(
                $table_name,
                array('cuit' => $cuit),
                array('customer_id' => $customer_id)
            );
        }
    }
    
/**
     * Validar formato de CUIT (método auxiliar)
     */
    private function validate_cuit_format($cuit) {
        // Primero intentar validar con el formato completo con guiones
        if (preg_match('/^\d{2}-\d{8}-\d$/', $cuit)) {
            // Si tiene el formato correcto, validar el dígito verificador
            $cuit_limpio = str_replace('-', '', $cuit);
            return $this->validate_cuit_checksum($cuit_limpio);
        }
        
        // Luego intentar con guiones opcionales
        if (preg_match('/^\d{2}[-]?\d{8}[-]?\d$/', $cuit)) {
            $cuit_limpio = preg_replace('/[^0-9]/', '', $cuit);
            return $this->validate_cuit_checksum($cuit_limpio);
        }
        
        // Finalmente, validar solo números (11 dígitos)
        $cuit_numeros = preg_replace('/[^0-9]/', '', $cuit);
        if (preg_match('/^\d{11}$/', $cuit_numeros)) {
            return $this->validate_cuit_checksum($cuit_numeros);
        }
        
        return false;
    }
    
    /**
     * Validar dígito verificador del CUIT
     */
    private function validate_cuit_checksum($cuit) {
        // Validación del dígito verificador
        $base = array(5, 4, 3, 2, 7, 6, 5, 4, 3, 2);
        $aux = 0;
        for ($i = 0; $i < 10; $i++) {
            $aux += $cuit[$i] * $base[$i];
        }
        $aux = 11 - ($aux % 11);
        if ($aux == 11) {
            $aux = 0;
        }
        if ($aux == 10) {
            $aux = 9;
        }
        
        return $aux == $cuit[10];
    }
}

// Inicializar
add_action('init', array('MAM_CUIT_Integration', 'init'));

/**
 * FUNCIONES AUXILIARES GLOBALES
 */

/**
 * Obtener CUIT de un usuario
 */
function mam_get_user_cuit($user_id) {
    $cuit = get_user_meta($user_id, 'billing_cuit', true);
    if (empty($cuit)) {
        $cuit = get_user_meta($user_id, 'cuit', true);
    }
    return $cuit;
}

/**
 * Guardar CUIT de un usuario
 */
function mam_save_user_cuit($user_id, $cuit) {
    $cuit = sanitize_text_field($cuit);
    update_user_meta($user_id, 'billing_cuit', $cuit);
    update_user_meta($user_id, 'cuit', $cuit);
    return true;
}

/**
 * Obtener CUIT de un pedido
 */
function mam_get_order_cuit($order_id) {
    return get_post_meta($order_id, '_billing_cuit', true);
}

/**
 * Formatear CUIT
 */
function mam_format_cuit($cuit) {
    $cuit = preg_replace('/[^0-9]/', '', $cuit);
    if (strlen($cuit) == 11) {
        return substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1);
    }
    return $cuit;
}

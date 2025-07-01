<?php
/**
 * MAM Admin Orders
 * 
 * Extiende la funcionalidad de pedidos en el admin para mostrar CUIT
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAM_Admin_Orders {
    
    private static $instance = null;
    
    private function __construct() {
        // Agregar columna CUIT a la lista de pedidos
        add_filter('manage_edit-shop_order_columns', array($this, 'add_cuit_column_to_orders'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'show_cuit_in_order_column'), 10, 2);
        
        // Para HPOS (High Performance Order Storage)
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_cuit_column_to_orders'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'show_cuit_in_order_column_hpos'), 10, 2);
        
        // Hacer la columna ordenable
        add_filter('manage_edit-shop_order_sortable_columns', array($this, 'make_order_cuit_sortable'));
        add_filter('manage_woocommerce_page_wc-orders_sortable_columns', array($this, 'make_order_cuit_sortable'));
        
        // Agregar búsqueda por CUIT en pedidos
        add_filter('woocommerce_shop_order_search_fields', array($this, 'add_cuit_to_order_search'));
        
        // Agregar información de CUIT en el detalle del pedido
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_cuit_in_order_detail'), 10, 1);
    }
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Agregar columna CUIT a pedidos
     */
    public function add_cuit_column_to_orders($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Insertar después de la columna de cliente
            if ($key === 'order_number') {
                $new_columns['order_cuit'] = __('CUIT', 'my-account-manager');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Mostrar CUIT en la columna (CPT)
     */
    public function show_cuit_in_order_column($column, $order_id) {
        if ('order_cuit' === $column) {
            $order = wc_get_order($order_id);
            if ($order) {
                $cuit = $order->get_meta('_billing_cuit');
                echo $cuit ? esc_html($cuit) : '—';
            }
        }
    }
    
    /**
     * Mostrar CUIT en la columna (HPOS)
     */
    public function show_cuit_in_order_column_hpos($column, $order) {
        if ('order_cuit' === $column) {
            $cuit = $order->get_meta('_billing_cuit');
            echo $cuit ? esc_html($cuit) : '—';
        }
    }
    
    /**
     * Hacer columna ordenable
     */
    public function make_order_cuit_sortable($columns) {
        $columns['order_cuit'] = 'order_cuit';
        return $columns;
    }
    
    /**
     * Agregar CUIT a la búsqueda de pedidos
     */
    public function add_cuit_to_order_search($search_fields) {
        $search_fields[] = '_billing_cuit';
        return $search_fields;
    }
    
    /**
     * Mostrar CUIT en el detalle del pedido
     */
    public function display_cuit_in_order_detail($order) {
        $cuit = $order->get_meta('_billing_cuit');
        if ($cuit) {
            echo '<p><strong>' . __('CUIT:', 'my-account-manager') . '</strong> ' . esc_html($cuit) . '</p>';
        }
    }
}

// Inicializar
MAM_Admin_Orders::init();

// ==========================================
// 4. AGREGAR VALIDACIÓN MEJORADA EN class-mam-addresses.php
// ==========================================
// Reemplazar el método validate_cuit_format con esta versión mejorada:

/**
 * Validar formato de CUIT con algoritmo oficial AFIP
 */
private function validate_cuit_format($cuit) {
    // Eliminar espacios y guiones
    $cuit = str_replace(array(' ', '-'), '', $cuit);
    
    // Verificar que tenga exactamente 11 dígitos
    if (!preg_match('/^[0-9]{11}$/', $cuit)) {
        return false;
    }
    
    // Validar tipo de CUIT (primeros 2 dígitos)
    $tipo = substr($cuit, 0, 2);
    $tipos_validos = array('20', '23', '24', '27', '30', '33', '34');
    if (!in_array($tipo, $tipos_validos)) {
        return false;
    }
    
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

// ==========================================
// 5. AGREGAR A includes/admin/class-mam-admin.php
// ==========================================
// En el constructor, agregar:

// Agregar metabox para información fiscal en pedidos
add_action('add_meta_boxes', array($this, 'add_fiscal_info_metabox'));

// Y agregar este método:
/**
 * Agregar metabox de información fiscal
 */
public function add_fiscal_info_metabox() {
    add_meta_box(
        'mam_fiscal_info',
        __('Información Fiscal', 'my-account-manager'),
        array($this, 'render_fiscal_info_metabox'),
        'shop_order',
        'side',
        'high'
    );
}

/**
 * Renderizar metabox de información fiscal
 */
public function render_fiscal_info_metabox($post) {
    $order = wc_get_order($post->ID);
    $cuit = $order->get_meta('_billing_cuit');
    $company = $order->get_billing_company();
    ?>
    <p>
        <strong><?php _e('Empresa:', 'my-account-manager'); ?></strong><br>
        <?php echo $company ? esc_html($company) : '—'; ?>
    </p>
    <p>
        <strong><?php _e('CUIT:', 'my-account-manager'); ?></strong><br>
        <?php echo $cuit ? esc_html($cuit) : '—'; ?>
    </p>
    <?php
}

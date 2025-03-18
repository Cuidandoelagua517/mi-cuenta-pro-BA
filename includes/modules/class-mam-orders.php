<?php
/**
 * Orders functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Orders Class
 */
class MAM_Orders {

    /**
     * Inicializar la clase
     */
    public static function init() {
        $instance = new self();
           $instance->register_ajax_handlers(); // Añadir esta línea
        return $instance;
    }

    /**
     * Constructor
     */
  public function __construct() {
    // Verificar si HPOS está activo
    $this->is_hpos_enabled = $this->check_hpos_enabled();
        // Personalizar títulos de los endpoints
        add_filter('woocommerce_endpoint_orders_title', array($this, 'custom_orders_title'));
        add_filter('woocommerce_endpoint_view-order_title', array($this, 'custom_view_order_title'));
        
        // Personalizar contenido de la página de pedidos
        add_action('woocommerce_before_account_orders', array($this, 'before_account_orders'));
        add_action('woocommerce_after_account_orders', array($this, 'after_account_orders'));
        
        // Añadir filtro de pedidos
        add_action('woocommerce_before_account_orders', array($this, 'add_orders_filter'));
        
        // Filtrar consulta de pedidos
        add_filter('woocommerce_my_account_my_orders_query', array($this, 'filter_orders_query'));
        
        // Añadir columnas personalizadas a la tabla de pedidos
        add_filter('woocommerce_account_orders_columns', array($this, 'add_account_orders_columns'));
        
        // Añadir contenido a las columnas personalizadas
        add_action('woocommerce_my_account_my_orders_column_order_products', array($this, 'add_order_products_column_content'));
        
        // Personalizar botones de acción en pedidos
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'custom_orders_actions'), 10, 2);
        
        // Personalizar la vista detallada de un pedido
        add_action('woocommerce_view_order', array($this, 'customize_view_order'), 1);
        
        // Añadir mapa de seguimiento de pedido
        add_action('woocommerce_view_order', array($this, 'add_order_tracking_map'), 25);
        
        // Añadir comentarios y valoraciones para pedidos completados
        add_action('woocommerce_view_order', array($this, 'add_order_review_form'), 30);
        
        // Procesar el formulario de valoración
        add_action('template_redirect', array($this, 'process_order_review_form'));
    }
/**
 * Verifica si High-Performance order storage está activo
 */
private function check_hpos_enabled() {
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
    return false;
}
    public function register_ajax_handlers() {
    add_action('wp_ajax_mam_filter_orders', array($this, 'ajax_filter_orders'));
    add_action('wp_ajax_mam_load_order_details', array($this, 'ajax_load_order_details'));
    add_action('wp_ajax_mam_paginate_orders', array($this, 'ajax_paginate_orders'));
}

public function ajax_filter_orders() {
    check_ajax_referer('mam-nonce', 'security');
    
    $status = isset($_POST['status']) ? wc_clean($_POST['status']) : '';
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    
    $args = array(
        'customer_id' => get_current_user_id(),
        'page'        => $page,
        'limit'       => 10
    );
    
    if (!empty($status)) {
        $args['status'] = $status;
    }
    
    $customer_orders = wc_get_orders($args);
    
    ob_start();
    // Renderizar la tabla de órdenes actualizada
    // ...
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'count' => count($customer_orders)
    ));
}
    /**
 * Método para obtener pedidos de manera compatible
 */
private function get_orders_compatible($args) {
    if ($this->is_hpos_enabled) {
        // Usar la nueva API para HPOS
        return wc_get_orders($args);
    } else {
        // Usar el método tradicional
        return wc_get_orders($args);
    }
}
    /**
     * Personalizar título de la página de pedidos
     */
    public function custom_orders_title($title) {
        return __('Mis Pedidos', 'my-account-manager');
    }

    /**
     * Personalizar título de la página de detalle de pedido
     */
    public function custom_view_order_title($title) {
        return __('Detalle del Pedido', 'my-account-manager');
    }

    /**
     * Añadir contenido antes de la lista de pedidos
     */
    public function before_account_orders() {
        // Obtener usuario actual
        $current_user = wp_get_current_user();
        
        // Obtener estadísticas de pedidos
        $stats = $this->get_orders_stats($current_user->ID);
        
        ?>
        <div class="mam-orders-overview">
            <div class="mam-orders-stats">
                <div class="mam-orders-stat-item">
                    <div class="mam-orders-stat-number"><?php echo esc_html($stats['total']); ?></div>
                    <div class="mam-orders-stat-label"><?php _e('Total de pedidos', 'my-account-manager'); ?></div>
                </div>
                
                <div class="mam-orders-stat-item">
                    <div class="mam-orders-stat-number"><?php echo esc_html($stats['completed']); ?></div>
                    <div class="mam-orders-stat-label"><?php _e('Completados', 'my-account-manager'); ?></div>
                </div>
                
                <div class="mam-orders-stat-item">
                    <div class="mam-orders-stat-number"><?php echo esc_html($stats['processing']); ?></div>
                    <div class="mam-orders-stat-label"><?php _e('En proceso', 'my-account-manager'); ?></div>
                </div>
                
                <?php if ($stats['total'] > 0) : ?>
                <div class="mam-orders-stat-item">
                    <div class="mam-orders-stat-number"><?php echo wc_price($stats['total_spent']); ?></div>
                    <div class="mam-orders-stat-label"><?php _e('Total gastado', 'my-account-manager'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
/**
 * Obtener metadatos de pedido de manera compatible con HPOS
 *
 * @param WC_Order $order Objeto de pedido
 * @param string $key Clave del metadato
 * @param bool $single Valor único o array
 * @return mixed
 */
private function get_order_meta_compatible($order, $key, $single = true) {
    if ($this->is_hpos_enabled) {
        // Método para HPOS
        return $order->get_meta($key, $single);
    } else {
        // Método tradicional para WP post meta
        return get_post_meta($order->get_id(), $key, $single);
    }
}

/**
 * Actualizar metadatos de pedido de manera compatible con HPOS
 *
 * @param WC_Order $order Objeto de pedido
 * @param string $key Clave del metadato
 * @param mixed $value Valor a guardar
 * @return void
 */
private function update_order_meta_compatible($order, $key, $value) {
    if ($this->is_hpos_enabled) {
        // Método para HPOS
        $order->update_meta_data($key, $value);
        $order->save();
    } else {
        // Método tradicional para WP post meta
        update_post_meta($order->get_id(), $key, $value);
    }
}

/**
 * Eliminar metadatos de pedido de manera compatible con HPOS
 *
 * @param WC_Order $order Objeto de pedido
 * @param string $key Clave del metadato
 * @return void
 */
private function delete_order_meta_compatible($order, $key) {
    if ($this->is_hpos_enabled) {
        // Método para HPOS
        $order->delete_meta_data($key);
        $order->save();
    } else {
        // Método tradicional para WP post meta
        delete_post_meta($order->get_id(), $key);
    }
}
    /**
     * Añadir contenido después de la lista de pedidos
     */
    public function after_account_orders() {
        // Aquí podríamos añadir información adicional o enlaces útiles
        // Por ejemplo, información sobre políticas de devolución, etc.
        ?>
        <div class="mam-orders-footer">
            <div class="mam-orders-help">
                <h4><?php _e('¿Necesitas ayuda con tu pedido?', 'my-account-manager'); ?></h4>
                <p><?php _e('Si tienes alguna pregunta sobre tus pedidos, consulta nuestra sección de ayuda o contáctanos directamente.', 'my-account-manager'); ?></p>
                <div class="mam-orders-help-buttons">
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_help_page_id'))); ?>" class="mam-button mam-button-secondary">
                        <?php _e('Centro de Ayuda', 'my-account-manager'); ?>
                    </a>
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_contact_page_id'))); ?>" class="mam-button mam-button-primary">
                        <?php _e('Contactar', 'my-account-manager'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir filtro de pedidos
     */
    public function add_orders_filter() {
        $current_status = isset($_GET['order_status']) ? sanitize_text_field($_GET['order_status']) : '';
        
        // Opciones de estado de pedido
        $statuses = wc_get_order_statuses();
        
        ?>
        <div class="mam-orders-filter">
            <form method="get" action="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                <div class="mam-orders-filter-row">
                    <div class="mam-orders-filter-item">
                        <label for="order_status"><?php _e('Filtrar por estado:', 'my-account-manager'); ?></label>
                        <select name="order_status" id="order_status" class="mam-select">
                            <option value=""><?php _e('Todos los pedidos', 'my-account-manager'); ?></option>
                            <?php foreach ($statuses as $status_key => $status_name) : ?>
                                <option value="<?php echo esc_attr(str_replace('wc-', '', $status_key)); ?>" <?php selected($current_status, str_replace('wc-', '', $status_key)); ?>>
                                    <?php echo esc_html($status_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mam-orders-filter-item">
                        <button type="submit" class="mam-button mam-button-secondary">
                            <?php _e('Filtrar', 'my-account-manager'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Filtrar consulta de pedidos
     */
    public function filter_orders_query($args) {
        if (isset($_GET['order_status']) && !empty($_GET['order_status'])) {
            $status = sanitize_text_field($_GET['order_status']);
            $args['status'] = $status;
        }
        
        return $args;
    }

    /**
     * Añadir columnas personalizadas a la tabla de pedidos
     */
    public function add_account_orders_columns($columns) {
        $new_columns = array();
        
        // Reordenar y añadir columnas
        foreach ($columns as $key => $name) {
            if ($key === 'order-status') {
                $new_columns[$key] = $name;
                $new_columns['order_products'] = __('Productos', 'my-account-manager');
            } else {
                $new_columns[$key] = $name;
            }
        }
        
        return $new_columns;
    }

    /**
     * Añadir contenido a la columna de productos
     */
    public function add_order_products_column_content($order) {
        $items = $order->get_items();
        $count = count($items);
        
        // Si hay pocos productos, mostrarlos directamente
        if ($count <= 2) {
            $product_list = array();
            
            foreach ($items as $item) {
                $product = $item->get_product();
                
                if ($product) {
                    $product_list[] = '<span class="mam-order-product">' . 
                        $item->get_name() . ' <strong>&times; ' . 
                        $item->get_quantity() . '</strong></span>';
                }
            }
            
            echo implode(', ', $product_list);
        } else {
            // Si hay muchos productos, mostrar solo la cantidad
            echo sprintf(
                _n('%s producto', '%s productos', $count, 'my-account-manager'),
                $count
            );
        }
    }

    /**
     * Personalizar botones de acción en pedidos
     */
    public function custom_orders_actions($actions, $order) {
        // Añadir clases a los botones
        if (isset($actions['view'])) {
            $actions['view']['class'] = array_merge(
                isset($actions['view']['class']) && is_array($actions['view']['class']) ? $actions['view']['class'] : array(),
                array('mam-button', 'mam-button-secondary', 'mam-view-button')
            );
        }
        
        if (isset($actions['pay'])) {
            $actions['pay']['class'] = array_merge(
                isset($actions['pay']['class']) && is_array($actions['pay']['class']) ? $actions['pay']['class'] : array(),
                array('mam-button', 'mam-button-primary', 'mam-pay-button')
            );
        }
        
        if (isset($actions['cancel'])) {
            $actions['cancel']['class'] = array_merge(
                isset($actions['cancel']['class']) && is_array($actions['cancel']['class']) ? $actions['cancel']['class'] : array(),
                array('mam-button', 'mam-button-danger', 'mam-cancel-button')
            );
        }
        
        // Añadir acción para reordenar (si el pedido está completado)
        if ($order->has_status('completed') && !isset($actions['reorder'])) {
            $actions['reorder'] = array(
                'url'  => wp_nonce_url(add_query_arg('reorder', $order->get_id(), wc_get_cart_url()), 'woocommerce-reorder'),
                'name' => __('Reordenar', 'my-account-manager'),
                'class' => array('mam-button', 'mam-button-secondary', 'mam-reorder-button')
            );
        }
        
        return $actions;
    }

    /**
     * Personalizar la vista detallada de un pedido
     */
    public function customize_view_order($order_id) {
        // Obtener detalles del pedido
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Añadir wrapper personalizado
        echo '<div class="mam-order-details-wrapper">';
        
        // Añadir encabezado personalizado
        $this->add_order_header($order);
        
        // Añadir progreso del pedido
        $this->add_order_progress($order);
    }

    /**
     * Añadir encabezado personalizado para la vista de pedido
     */
    private function add_order_header($order) {
        ?>
        <div class="mam-order-header">
            <div class="mam-order-title">
                <h2><?php printf(__('Pedido #%s', 'my-account-manager'), $order->get_order_number()); ?></h2>
                <div class="mam-order-date">
                    <?php 
                    echo sprintf(
                        __('Realizado el %s', 'my-account-manager'),
                        $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
                    ); 
                    ?>
                </div>
            </div>
            
            <div class="mam-order-status">
                <span class="mam-status-badge mam-status-<?php echo esc_attr($order->get_status()); ?>">
                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                </span>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir progreso del pedido
     */
    private function add_order_progress($order) {
        $status = $order->get_status();
        $steps = array(
            'pending'    => array(
                'label' => __('Pendiente', 'my-account-manager'),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            ),
            'processing' => array(
                'label' => __('En proceso', 'my-account-manager'),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>'
            ),
            'shipped'    => array(
                'label' => __('Enviado', 'my-account-manager'),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>'
            ),
            'completed'  => array(
                'label' => __('Completado', 'my-account-manager'),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
            )
        );
        
        // Mapeo de estados WooCommerce a nuestros estados simplificados
        $status_mapping = array(
            'pending'    => 'pending',
            'on-hold'    => 'pending',
            'processing' => 'processing',
            'shipped'    => 'shipped', // Si usamos un plugin que añade este estado
            'completed'  => 'completed',
            'cancelled'  => 'pending',
            'refunded'   => 'completed',
            'failed'     => 'pending',
        );
        
        // Si el pedido está en tránsito (con algún plugin de envío)
        if (in_array($status, array('in-transit', 'out-for-delivery'))) {
            $status_mapping[$status] = 'shipped';
        }
        
        // Obtener el estado simplificado actual
        $current_step = isset($status_mapping[$status]) ? $status_mapping[$status] : 'pending';
        
        // Determinar qué pasos están completados
        $step_complete = array(
            'pending'    => in_array($current_step, array('pending', 'processing', 'shipped', 'completed')),
            'processing' => in_array($current_step, array('processing', 'shipped', 'completed')),
            'shipped'    => in_array($current_step, array('shipped', 'completed')),
            'completed'  => in_array($current_step, array('completed')),
        );
        
        // Cambiar a "activo" para el paso actual
        foreach ($step_complete as $step => $completed) {
            if ($step === $current_step && $step !== 'completed') {
                $step_complete[$step] = 'active';
            }
        }
        
        // Renderizar el progreso
        ?>
        <div class="mam-order-progress">
            <?php foreach ($steps as $step_key => $step) : ?>
                <div class="mam-progress-step <?php echo $step_complete[$step_key] === true ? 'is-complete' : ($step_complete[$step_key] === 'active' ? 'is-active' : ''); ?>">
                    <div class="mam-progress-icon">
                        <?php echo $step['icon']; ?>
                    </div>
                    <div class="mam-progress-label"><?php echo esc_html($step['label']); ?></div>
                </div>
                
                <?php if ($step_key !== 'completed') : ?>
                    <div class="mam-progress-connector <?php echo $step_complete[$step_key] === true ? 'is-complete' : ''; ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Añadir mapa de seguimiento de pedido
     */
    public function add_order_tracking_map($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Solo mostrar para pedidos enviados o en proceso
        if (!in_array($order->get_status(), array('processing', 'in-transit', 'out-for-delivery', 'shipped'))) {
            return;
        }
        
        // Obtener información de seguimiento (necesita plugin de seguimiento)
        $tracking_number = $this->get_order_tracking_number($order);
        
        if (!$tracking_number) {
            return;
        }
        
        ?>
        <div class="mam-order-tracking">
            <h3><?php _e('Seguimiento del Envío', 'my-account-manager'); ?></h3>
            
            <div class="mam-tracking-info">
                <div class="mam-tracking-number">
                    <strong><?php _e('Número de seguimiento:', 'my-account-manager'); ?></strong>
                    <?php echo esc_html($tracking_number); ?>
                </div>
                
                <div class="mam-tracking-provider">
                    <strong><?php _e('Transportista:', 'my-account-manager'); ?></strong>
                    <?php echo esc_html($this->get_order_tracking_provider($order)); ?>
                </div>
            </div>
            
            <div class="mam-tracking-map">
                <!-- Aquí iría el mapa de seguimiento o un link al servicio de tracking -->
                <a href="<?php echo esc_url($this->get_tracking_url($order)); ?>" class="mam-button mam-button-primary" target="_blank">
                    <?php _e('Seguir Envío', 'my-account-manager'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener número de seguimiento del pedido
     */
    private function get_order_tracking_number($order) {
        // Intentar obtener desde diferentes plugins de seguimiento
        
        // WooCommerce Shipment Tracking
        $tracking_items = $order->get_meta('_wc_shipment_tracking_items');
        if (!empty($tracking_items) && is_array($tracking_items)) {
            $tracking_item = array_shift($tracking_items);
            if (isset($tracking_item['tracking_number'])) {
                return $tracking_item['tracking_number'];
            }
        }
        
        // Advanced Shipment Tracking
        $ast_tracking_items = $order->get_meta('_ast_tracking_items');
        if (!empty($ast_tracking_items) && is_array($ast_tracking_items)) {
            $tracking_item = array_shift($ast_tracking_items);
            if (isset($tracking_item['tracking_number'])) {
                return $tracking_item['tracking_number'];
            }
        }
        
        // Fallback a un campo personalizado
        return $order->get_meta('_tracking_number');
    }

    /**
     * Obtener proveedor de seguimiento
     */
    private function get_order_tracking_provider($order) {
        // Similar al método anterior, pero para obtener el proveedor
        
        // WooCommerce Shipment Tracking
        $tracking_items = $order->get_meta('_wc_shipment_tracking_items');
        if (!empty($tracking_items) && is_array($tracking_items)) {
            $tracking_item = array_shift($tracking_items);
            if (isset($tracking_item['tracking_provider'])) {
                return $tracking_item['tracking_provider'];
            }
        }
        
        // Advanced Shipment Tracking
        $ast_tracking_items = $order->get_meta('_ast_tracking_items');
        if (!empty($ast_tracking_items) && is_array($ast_tracking_items)) {
            $tracking_item = array_shift($ast_tracking_items);
            if (isset($tracking_item['shipping_provider'])) {
                return $tracking_item['shipping_provider'];
            }
        }
        
        // Fallback a un campo personalizado
        return $order->get_meta('_tracking_provider');
    }

    /**
     * Obtener URL de seguimiento
     */
    private function get_tracking_url($order) {
        // Intentar obtener desde diferentes plugins
        
        // WooCommerce Shipment Tracking
        $tracking_items = $order->get_meta('_wc_shipment_tracking_items');
        if (!empty($tracking_items) && is_array($tracking_items)) {
            $tracking_item = array_shift($tracking_items);
            if (isset($tracking_item['custom_tracking_link']) && !empty($tracking_item['custom_tracking_link'])) {
                return $tracking_item['custom_tracking_link'];
            }
        }
        
        // Advanced Shipment Tracking
        $ast_tracking_items = $order->get_meta('_ast_tracking_items');
        if (!empty($ast_tracking_items) && is_array($ast_tracking_items)) {
            $tracking_item = array_shift($ast_tracking_items);
            if (isset($tracking_item['tracking_page_link']) && !empty($tracking_item['tracking_page_link'])) {
                return $tracking_item['tracking_page_link'];
            }
        }
        
        // Fallback a sitios conocidos basados en el proveedor
        $provider = $this->get_order_tracking_provider($order);
        $tracking_number = $this->get_order_tracking_number($order);
        
        if ($provider && $tracking_number) {
            // Mapeo básico de proveedores a URLs
            $provider_urls = array(
                'dhl' => 'https://www.dhl.com/en/express/tracking.html?AWB=' . $tracking_number,
                'fedex' => 'https://www.fedex.com/apps/fedextrack/?tracknumbers=' . $tracking_number,
                'ups' => 'https://www.ups.com/track?tracknum=' . $tracking_number,
                'usps' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_number,
                // Añadir más proveedores según sea necesario
            );
            
            // Buscar coincidencia parcial en la clave del proveedor
            foreach ($provider_urls as $key => $url) {
                if (stripos($provider, $key) !== false) {
                    return $url;
                }
            }
        }
        
        // Si todo falla, devolver un enlace genérico a PackageRadar
        return 'https://www.packageradar.com/search?n=' . urlencode($tracking_number);
    }

    /**
     * Añadir formulario de valoración para pedidos completados
     */
    public function add_order_review_form($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->has_status('completed')) {
            return;
        }
        
        // Verificar si ya se ha dejado una valoración
        $review_left = get_post_meta($order_id, '_mam_customer_review', true);
        
        if ($review_left) {
            $this->show_existing_review($order_id);
            return;
        }
        
        ?>
        <div class="mam-order-review">
            <h3><?php _e('¿Qué te ha parecido tu pedido?', 'my-account-manager'); ?></h3>
            
            <form method="post" class="mam-review-form">
                <p><?php _e('Nos encantaría conocer tu opinión sobre este pedido. Tu feedback nos ayuda a mejorar.', 'my-account-manager'); ?></p>
                
                <div class="mam-form-row">
                    <label for="mam-review-rating"><?php _e('Valoración:', 'my-account-manager'); ?></label>
                    <div class="mam-rating-select">
                        <div class="mam-stars">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="mam_review_rating" value="<?php echo $i; ?>" />
                                <label for="star<?php echo $i; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mam-form-row">
                    <label for="mam-review-comment"><?php _e('Comentarios:', 'my-account-manager'); ?></label>
                    <textarea id="mam-review-comment" name="mam_review_comment" rows="4" placeholder="<?php esc_attr_e('Cuéntanos tu experiencia con este pedido...', 'my-account-manager'); ?>"></textarea>
                </div>
                
                <?php wp_nonce_field('mam_order_review', 'mam_review_nonce'); ?>
                <input type="hidden" name="mam_order_id" value="<?php echo esc_attr($order_id); ?>" />
                
                <div class="mam-form-row">
                    <button type="submit" name="mam_submit_review" class="mam-button mam-button-primary">
                        <?php _e('Enviar Valoración', 'my-account-manager'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Mostrar valoración existente
     */
    private function show_existing_review($order_id) {
        $review_data = get_post_meta($order_id, '_mam_customer_review', true);
        
        if (!is_array($review_data)) {
            return;
        }
        
        $rating = isset($review_data['rating']) ? intval($review_data['rating']) : 0;
        $comment = isset($review_data['comment']) ? $review_data['comment'] : '';
        $date = isset($review_data['date']) ? $review_data['date'] : '';
        
        ?>
        <div class="mam-order-review mam-review-submitted">
            <h3><?php _e('Tu valoración', 'my-account-manager'); ?></h3>
            
            <div class="mam-review-content">
                <div class="mam-review-rating">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span class="mam-star <?php echo $i <= $rating ? 'mam-star-filled' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </span>
                    <?php endfor; ?>
                    <span class="mam-review-date"><?php echo esc_html($date); ?></span>
                </div>
                
                <?php if (!empty($comment)) : ?>
                    <div class="mam-review-comment">
                        <p><?php echo esc_html($comment); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mam-review-actions">
                    <form method="post" class="mam-review-edit-form">
                        <?php wp_nonce_field('mam_edit_review', 'mam_edit_review_nonce'); ?>
                        <input type="hidden" name="mam_order_id" value="<?php echo esc_attr($order_id); ?>" />
                        <button type="submit" name="mam_edit_review" class="mam-button mam-button-secondary">
                            <?php _e('Editar Valoración', 'my-account-manager'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Procesar formulario de valoración
     */
    public function process_order_review_form() {
        if (!isset($_POST['mam_submit_review'])) {
            return;
        }
        
        // Verificar nonce
        if (!isset($_POST['mam_review_nonce']) || !wp_verify_nonce($_POST['mam_review_nonce'], 'mam_order_review')) {
            wc_add_notice(__('Ha ocurrido un error, por favor intenta de nuevo.', 'my-account-manager'), 'error');
            return;
        }
        
        // Verificar ID del pedido
        $order_id = isset($_POST['mam_order_id']) ? intval($_POST['mam_order_id']) : 0;
        if (!$order_id) {
            return;
        }
        
        // Verificar que el pedido pertenece al usuario actual
        $order = wc_get_order($order_id);
        if (!$order || $order->get_customer_id() !== get_current_user_id()) {
            wc_add_notice(__('No tienes permiso para realizar esta acción.', 'my-account-manager'), 'error');
            return;
        }
        
        // Validar campos
        $rating = isset($_POST['mam_review_rating']) ? intval($_POST['mam_review_rating']) : 0;
        if ($rating < 1 || $rating > 5) {
            wc_add_notice(__('Por favor, selecciona una valoración válida.', 'my-account-manager'), 'error');
            return;
        }
        
        $comment = isset($_POST['mam_review_comment']) ? sanitize_textarea_field($_POST['mam_review_comment']) : '';
        
        // Guardar la valoración
        $review_data = array(
            'rating'  => $rating,
            'comment' => $comment,
            'date'    => date_i18n(get_option('date_format')),
            'user_id' => get_current_user_id()
        );
        
        update_post_meta($order_id, '_mam_customer_review', $review_data);
        
        // Crear comentarios de producto si se proporcionaron
        $this->maybe_create_product_reviews($order, $rating, $comment);
        
        // Mostrar mensaje de éxito
        wc_add_notice(__('¡Gracias por tu valoración!', 'my-account-manager'), 'success');
        
        // Redireccionar para evitar reenvío de formulario
        wp_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')));
        exit;
    }

    /**
     * Crear reseñas de productos
     */
    private function maybe_create_product_reviews($order, $rating, $comment) {
        // Si no hay comentario, no crear reseñas de productos
        if (empty($comment)) {
            return;
        }
        
        // Preguntar al usuario si quiere dejar reseñas para los productos específicos
        // Esto se haría en una pantalla adicional después de enviar la valoración del pedido
        
        // Por ahora, simplemente registramos una reseña genérica para el pedido
    }

    /**
     * Obtener estadísticas de pedidos
     */
    private function get_orders_stats($user_id) {
        $stats = array(
            'total'       => 0,
            'completed'   => 0,
            'processing'  => 0,
            'total_spent' => 0
        );
        
        // Obtener todos los pedidos del usuario
        $args = array(
            'customer_id' => $user_id,
            'limit'       => -1,
            'return'      => 'ids',
        );
        
        $order_ids = wc_get_orders($args);
        $stats['total'] = count($order_ids);
        
        if ($stats['total'] > 0) {
            // Contar por estado
            $processing_ids = wc_get_orders(array(
                'customer_id' => $user_id,
                'status'      => array('processing', 'on-hold'),
                'limit'       => -1,
                'return'      => 'ids',
            ));
            $stats['processing'] = count($processing_ids);
            
            $completed_ids = wc_get_orders(array(
                'customer_id' => $user_id,
                'status'      => 'completed',
                'limit'       => -1,
                'return'      => 'ids',
            ));
            $stats['completed'] = count($completed_ids);
            
            // Calcular total gastado
            $args = array(
                'customer_id' => $user_id,
                'status'      => array('completed', 'processing'),
                'limit'       => -1,
            );
            
            $orders = wc_get_orders($args);
            foreach ($orders as $order) {
                $stats['total_spent'] += $order->get_total();
            }
        }
        
        return $stats;
    }
}

<?php
/**
 * Template para el Dashboard personalizado de Mi Cuenta
 *
 * Este template reemplaza al dashboard por defecto de WooCommerce.
 * Debe ubicarse en: /templates/myaccount/dashboard.php
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Obtener usuario actual
$current_user = wp_get_current_user();
$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$display_name = $first_name ? $first_name : $current_user->display_name;

// Obtener información de pedidos
$customer_orders = wc_get_orders(array(
    'customer' => $current_user->ID,
    'limit' => -1,
));

$total_orders = count($customer_orders);
$completed_orders = 0;
$processing_orders = 0;

foreach ($customer_orders as $order) {
    if ($order->get_status() === 'completed') {
        $completed_orders++;
    } elseif ($order->get_status() === 'processing') {
        $processing_orders++;
    }
}

// Obtener descargas disponibles
$downloads = WC()->customer->get_downloadable_products();
$total_downloads = count($downloads);

// Obtener direcciones
$shipping = wc_get_customer_default_location();
$has_shipping = false;

if (wc_shipping_enabled() && !wc_ship_to_billing_address_only()) {
    $shipping_address = wc_get_account_formatted_address('shipping');
    $has_shipping = !empty($shipping_address);
}

$billing_address = wc_get_account_formatted_address('billing');
$has_billing = !empty($billing_address);
?>

<div class="mam-dashboard">
    <div class="mam-dashboard-header">
        <h2><?php printf(__('¡Hola %s!', 'my-account-manager'), esc_html($display_name)); ?></h2>
        <p><?php _e('Desde tu panel de cuenta puedes ver tus pedidos recientes, gestionar tus direcciones de envío y facturación, y editar tu contraseña y detalles de la cuenta.', 'my-account-manager'); ?></p>
    </div>

    <div class="mam-dashboard-wrapper">
        <!-- Tarjeta de Pedidos -->
        <div class="mam-dashboard-card">
            <div class="mam-dashboard-card-header">
                <span class="mam-dashboard-card-title"><?php _e('Mis Pedidos', 'my-account-manager'); ?></span>
                <div class="mam-dashboard-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
            </div>
            <div class="mam-dashboard-card-content">
                <div class="mam-dashboard-card-number"><?php echo esc_html($total_orders); ?></div>
                <div class="mam-dashboard-card-text"><?php _e('Total de pedidos', 'my-account-manager'); ?></div>
            </div>
            <div class="mam-dashboard-card-footer">
                <a href="<?php echo esc_url(wc_get_endpoint_url('orders')); ?>">
                    <?php _e('Ver mis pedidos', 'my-account-manager'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Tarjeta de Direcciones -->
        <div class="mam-dashboard-card">
            <div class="mam-dashboard-card-header">
                <span class="mam-dashboard-card-title"><?php _e('Mis Direcciones', 'my-account-manager'); ?></span>
                <div class="mam-dashboard-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div class="mam-dashboard-card-content">
                <div class="mam-dashboard-card-text">
                    <?php 
                    if ($has_billing) {
                        echo '<div class="mam-address-info">';
                        echo '<span class="mam-address-label">' . __('Dirección de facturación:', 'my-account-manager') . '</span> ';
                        echo '<span class="mam-address-status mam-status-complete">' . __('Configurada', 'my-account-manager') . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="mam-address-info">';
                        echo '<span class="mam-address-label">' . __('Dirección de facturación:', 'my-account-manager') . '</span> ';
                        echo '<span class="mam-address-status mam-status-pending">' . __('No configurada', 'my-account-manager') . '</span>';
                        echo '</div>';
                    }
                    
                    if (wc_shipping_enabled()) {
                        if ($has_shipping) {
                            echo '<div class="mam-address-info">';
                            echo '<span class="mam-address-label">' . __('Dirección de envío:', 'my-account-manager') . '</span> ';
                            echo '<span class="mam-address-status mam-status-complete">' . __('Configurada', 'my-account-manager') . '</span>';
                            echo '</div>';
                        } else {
                            echo '<div class="mam-address-info">';
                            echo '<span class="mam-address-label">' . __('Dirección de envío:', 'my-account-manager') . '</span> ';
                            echo '<span class="mam-address-status mam-status-pending">' . __('No configurada', 'my-account-manager') . '</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="mam-dashboard-card-footer">
                <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address')); ?>">
                    <?php _e('Gestionar direcciones', 'my-account-manager'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Tarjeta de Descargas -->
        <?php if (wc_get_customer_order_count($current_user->ID) > 0) : ?>
        <div class="mam-dashboard-card">
            <div class="mam-dashboard-card-header">
                <span class="mam-dashboard-card-title"><?php _e('Mis Descargas', 'my-account-manager'); ?></span>
                <div class="mam-dashboard-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </div>
            </div>
            <div class="mam-dashboard-card-content">
                <div class="mam-dashboard-card-number"><?php echo esc_html($total_downloads); ?></div>
                <div class="mam-dashboard-card-text"><?php _e('Archivos disponibles', 'my-account-manager'); ?></div>
            </div>
            <div class="mam-dashboard-card-footer">
                <a href="<?php echo esc_url(wc_get_endpoint_url('downloads')); ?>">
                    <?php _e('Ver mis descargas', 'my-account-manager'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
        <?php else : ?>
        <!-- Tarjeta de Datos de la Cuenta -->
        <div class="mam-dashboard-card">
            <div class="mam-dashboard-card-header">
                <span class="mam-dashboard-card-title"><?php _e('Mi Cuenta', 'my-account-manager'); ?></span>
                <div class="mam-dashboard-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <div class="mam-dashboard-card-content">
                <div class="mam-dashboard-card-text">
                    <div class="mam-account-details">
                        <span class="mam-account-label"><?php _e('Nombre:', 'my-account-manager'); ?></span>
                        <span class="mam-account-value"><?php echo esc_html($first_name . ' ' . $last_name); ?></span>
                    </div>
                    <div class="mam-account-details">
                        <span class="mam-account-label"><?php _e('Email:', 'my-account-manager'); ?></span>
                        <span class="mam-account-value"><?php echo esc_html($current_user->user_email); ?></span>
                    </div>
                </div>
            </div>
            <div class="mam-dashboard-card-footer">
                <a href="<?php echo esc_url(wc_get_endpoint_url('edit-account')); ?>">
                    <?php _e('Editar mi información', 'my-account-manager'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($total_orders > 0) : ?>
    <!-- Sección de pedidos recientes -->
    <div class="mam-dashboard-section">
        <h3><?php _e('Pedidos recientes', 'my-account-manager'); ?></h3>
        
        <table class="mam-orders-table">
            <thead>
                <tr>
                    <th><?php _e('Pedido', 'my-account-manager'); ?></th>
                    <th><?php _e('Fecha', 'my-account-manager'); ?></th>
                    <th><?php _e('Estado', 'my-account-manager'); ?></th>
                    <th><?php _e('Total', 'my-account-manager'); ?></th>
                    <th><?php _e('Acciones', 'my-account-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_orders = wc_get_orders(array(
                    'customer' => $current_user->ID,
                    'limit' => 5,
                ));

                foreach ($recent_orders as $order) {
                    $order_number = $order->get_order_number();
                    $order_date = wc_format_datetime($order->get_date_created());
                    $order_status = wc_get_order_status_name($order->get_status());
                    $order_total = $order->get_formatted_order_total();
                    $order_view_url = $order->get_view_order_url();
                    
                    echo '<tr>';
                    echo '<td>#' . esc_html($order_number) . '</td>';
                    echo '<td>' . esc_html($order_date) . '</td>';
                    echo '<td><span class="mam-order-status mam-status-' . esc_attr($order->get_status()) . '">' . esc_html($order_status) . '</span></td>';
                    echo '<td>' . $order_total . '</td>';
                    echo '<td><a href="' . esc_url($order_view_url) . '" class="mam-view-button">' . __('Ver', 'my-account-manager') . '</a></td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
        <?php if (count($recent_orders) < $total_orders) : ?>
        <div class="mam-view-all">
            <a href="<?php echo esc_url(wc_get_endpoint_url('orders')); ?>" class="mam-button mam-button-secondary">
                <?php _e('Ver todos mis pedidos', 'my-account-manager'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php 
    /**
     * Hook para añadir contenido adicional al dashboard
     */
    do_action('mam_dashboard_after_content'); 
    ?>
</div>

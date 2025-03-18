<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * Modified version for My Account Manager.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('woocommerce_before_account_orders', $has_orders);
?>

<?php if ($has_orders) : ?>

<div class="mam-orders-container">
    <?php if (isset($_GET['order_status'])) : ?>
        <div class="mam-orders-filtered-notice">
            <?php 
            $status = wc_clean($_GET['order_status']);
            $status_label = wc_get_order_status_name('wc-' . $status);
            printf(__('Mostrando pedidos con estado: %s', 'my-account-manager'), '<strong>' . esc_html($status_label) . '</strong>'); 
            ?>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="mam-clear-filter">
                <?php _e('Mostrar todos', 'my-account-manager'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="mam-orders-search-sort">
        <div class="mam-orders-search">
            <input type="text" id="mam-search-orders" placeholder="<?php esc_attr_e('Buscar pedidos...', 'my-account-manager'); ?>" />
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        
        <div class="mam-orders-sort">
            <label for="mam-sort-orders"><?php _e('Ordenar por:', 'my-account-manager'); ?></label>
            <select id="mam-sort-orders" class="mam-select">
                <option value="date_desc" selected><?php _e('Más recientes primero', 'my-account-manager'); ?></option>
                <option value="date_asc"><?php _e('Más antiguos primero', 'my-account-manager'); ?></option>
                <option value="total_desc"><?php _e('Mayor importe', 'my-account-manager'); ?></option>
                <option value="total_asc"><?php _e('Menor importe', 'my-account-manager'); ?></option>
            </select>
        </div>
    </div>

    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table mam-orders-table">
        <thead>
            <tr>
                <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr($column_id); ?>">
                        <span class="nobr">
                            <?php echo esc_html($column_name); ?>
                            <?php if ($column_id === 'order-date' || $column_id === 'order-total') : ?>
                                <span class="mam-sort-icon mam-sort-<?php echo $column_id === 'order-date' ? 'date' : 'total'; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </span>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php
            foreach ($customer_orders->orders as $customer_order) {
                $order = wc_get_order($customer_order); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                $order_date = $order->get_date_created();
                $order_status = $order->get_status();
                ?>
                <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($order_status); ?> order mam-order-row" data-order-date="<?php echo esc_attr($order_date->getTimestamp()); ?>" data-order-total="<?php echo esc_attr($order->get_total()); ?>">
                    <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
                        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
                            <?php if ('order-number' === $column_id) : ?>
                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                    <?php echo esc_html(_x('#', 'hash before order number', 'woocommerce') . $order->get_order_number()); ?>
                                </a>

                            <?php elseif ('order-date' === $column_id) : ?>
                                <time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></time>

                            <?php elseif ('order-status' === $column_id) : ?>
                                <span class="mam-order-status mam-status-<?php echo esc_attr($order_status); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>

                            <?php elseif ('order-total' === $column_id) : ?>
                                <?php
                                /* translators: 1: formatted order total 2: total order items */
                                echo wp_kses_post(sprintf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce'), $order->get_formatted_order_total(), $item_count));
                                ?>

                            <?php elseif ('order-actions' === $column_id) : ?>
                                <?php
                                $actions = wc_get_account_orders_actions($order);

                                if (!empty($actions)) {
                                    foreach ($actions as $key => $action) {
                                        echo '<a href="' . esc_url($action['url']) . '" class="woocommerce-button ' . esc_attr($key) . ' mam-button mam-button-' . esc_attr($key) . '">' . esc_html($action['name']) . '</a>';
                                    }
                                }
                                ?>
                            <?php elseif ('order_products' === $column_id) : ?>
                                <?php 
                                // Mostrar productos de manera resumida
                                $items = $order->get_items();
                                $count = count($items);
                                
                                if ($count > 0) {
                                    $product_list = array();
                                    
                                    $i = 0;
                                    foreach ($items as $item) {
                                        if ($i < 2) {
                                            $product_list[] = '<span class="mam-order-product">' . 
                                                $item->get_name() . ' <strong>&times; ' . 
                                                $item->get_quantity() . '</strong></span>';
                                        }
                                        $i++;
                                    }
                                    
                                    echo implode(', ', $product_list);
                                    
                                    if ($count > 2) {
                                        echo ' <span class="mam-order-more-products">+ ' . ($count - 2) . ' ' . __('más', 'my-account-manager') . '</span>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <tr class="mam-order-details-row" style="display: none;">
                    <td colspan="<?php echo count(wc_get_account_orders_columns()); ?>">
                        <div class="mam-order-details">
                            <div class="mam-order-products">
                                <h4><?php _e('Productos', 'my-account-manager'); ?></h4>
                                <ul class="mam-products-list">
                                    <?php 
                                    foreach ($order->get_items() as $item) {
                                        $product = $item->get_product();
                                        $product_permalink = $product ? $product->get_permalink() : '';
                                        
                                        echo '<li class="mam-product-item">';
                                        if ($product_permalink) {
                                            echo '<a href="' . esc_url($product_permalink) . '" class="mam-product-link">';
                                        }
                                        
                                        // Imagen del producto
                                        if ($product) {
                                            echo '<div class="mam-product-image">';
                                            echo $product->get_image('thumbnail');
                                            echo '</div>';
                                        }
                                        
                                        echo '<div class="mam-product-details">';
                                        echo '<div class="mam-product-name">' . $item->get_name() . '</div>';
                                        echo '<div class="mam-product-quantity">' . __('Cantidad:', 'my-account-manager') . ' ' . $item->get_quantity() . '</div>';
                                        echo '<div class="mam-product-total">' . __('Precio:', 'my-account-manager') . ' ' . wc_price($item->get_total()) . '</div>';
                                        echo '</div>';
                                        
                                        if ($product_permalink) {
                                            echo '</a>';
                                        }
                                        echo '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <div class="mam-order-address">
                                <div class="mam-order-shipping">
                                    <h4><?php _e('Dirección de envío', 'my-account-manager'); ?></h4>
                                    <address>
                                        <?php echo wp_kses_post($order->get_formatted_shipping_address() ?: __('No disponible', 'my-account-manager')); ?>
                                    </address>
                                </div>
                                
                                <div class="mam-order-billing">
                                    <h4><?php _e('Dirección de facturación', 'my-account-manager'); ?></h4>
                                    <address>
                                        <?php echo wp_kses_post($order->get_formatted_billing_address() ?: __('No disponible', 'my-account-manager')); ?>
                                    </address>
                                </div>
                            </div>
                            
                            <div class="mam-order-actions">
                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="mam-button mam-button-primary">
                                    <?php _e('Ver detalles completos', 'my-account-manager'); ?>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

    <div class="mam-orders-empty-results" style="display: none;">
        <div class="mam-empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        <p><?php _e('No se encontraron pedidos que coincidan con tu búsqueda.', 'my-account-manager'); ?></p>
        <button type="button" class="mam-button mam-button-secondary mam-reset-search">
            <?php _e('Mostrar todos los pedidos', 'my-account-manager'); ?>
        </button>
    </div>

    <?php do_action('woocommerce_before_account_orders_pagination'); ?>

    <?php if (1 < $customer_orders->max_num_pages) : ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers mam-pagination">
            <?php if (1 !== $current_page) : ?>
                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button--previous mam-button mam-button-secondary mam-prev-page" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <?php esc_html_e('Página anterior', 'woocommerce'); ?>
                </a>
            <?php endif; ?>

            <?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
                <a class="woocommerce-button woocommerce-button--next woocommerce-Button--next mam-button mam-button-secondary mam-next-page" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>">
                    <?php esc_html_e('Página siguiente', 'woocommerce'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php else : ?>
    <div class="mam-no-orders">
        <div class="mam-no-orders-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
        </div>
        <h3><?php esc_html_e('Aún no has realizado ningún pedido', 'my-account-manager'); ?></h3>
        <p><?php esc_html_e('Aquí aparecerán todos tus pedidos una vez que realices tu primera compra.', 'my-account-manager'); ?></p>
        <a href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>" class="mam-button mam-button-primary mam-start-shopping">
            <?php esc_html_e('Ir a la tienda', 'my-account-manager'); ?>
        </a>
    </div>
<?php endif; ?>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>

<script>
    (function($) {
        $(document).ready(function() {
            // Expandir/contraer filas de pedidos
            $('.mam-order-row').on('click', function(e) {
                // No expandir si se hace clic en enlaces o botones
                if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) {
                    return;
                }
                
                $(this).toggleClass('mam-expanded');
                $(this).next('.mam-order-details-row').slideToggle(300);
            });
            
            // Buscar pedidos
            $('#mam-search-orders').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                var $rows = $('.mam-order-row');
                var $emptyMessage = $('.mam-orders-empty-results');
                var visibleRows = 0;
                
                $rows.each(function() {
                    var orderText = $(this).text().toLowerCase();
                    if (orderText.indexOf(searchTerm) > -1) {
                        $(this).show();
                        $(this).next('.mam-order-details-row').hide();
                        $(this).removeClass('mam-expanded');
                        visibleRows++;
                    } else {
                        $(this).hide();
                        $(this).next('.mam-order-details-row').hide();
                    }
                });
                
                // Mostrar mensaje de resultados vacíos
                if (visibleRows === 0 && searchTerm !== '') {
                    $emptyMessage.show();
                } else {
                    $emptyMessage.hide();
                }
            });
            
            // Restablecer búsqueda
            $('.mam-reset-search').on('click', function() {
                $('#mam-search-orders').val('').trigger('keyup');
            });
            
            // Ordenar pedidos
            $('#mam-sort-orders').on('change', function() {
                var sortOption = $(this).val();
                sortOrders(sortOption);
            });
            
            // Ordenar por encabezados de tabla
            $('.mam-sort-date, .mam-sort-total').on('click', function() {
                var sortType = $(this).hasClass('mam-sort-date') ? 'date' : 'total';
                var currentDirection = $(this).hasClass('mam-sort-asc') ? 'asc' : 'desc';
                var newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                
                // Actualizar clases
                $('.mam-sort-icon').removeClass('mam-sort-asc mam-sort-desc');
                $(this).addClass('mam-sort-' + newDirection);
                
                // Ordenar
                sortOrders(sortType + '_' + newDirection);
                
                // Actualizar select
                $('#mam-sort-orders').val(sortType + '_' + newDirection);
            });
            
            // Función para ordenar pedidos
            function sortOrders(option) {
                var $rows = $('.mam-order-row');
                
                $rows.sort(function(a, b) {
                    var valueA, valueB;
                    
                    if (option === 'date_desc' || option === 'date_asc') {
                        valueA = parseInt($(a).data('order-date'));
                        valueB = parseInt($(b).data('order-date'));
                    } else {
                        valueA = parseFloat($(a).data('order-total'));
                        valueB = parseFloat($(b).data('order-total'));
                    }
                    
                    // Ordenar ascendente o descendente
                    var direction = option.endsWith('_asc') ? 1 : -1;
                    
                    return (valueA - valueB) * direction;
                });
                
                // Reordenar en el DOM
                var $table = $('.mam-orders-table tbody');
                $rows.each(function(index, row) {
                    var $detailsRow = $(row).next('.mam-order-details-row');
                    $table.append(row);
                    $table.append($detailsRow);
                });
            }
        });
    })(jQuery);
</script>

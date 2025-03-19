<?php
/**
 * Dashboard functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Dashboard Class
 */
class MAM_Dashboard {

    /**
     * Inicializar la clase
     */
    public static function init() {
        $instance = new self();
        return $instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Reemplazar el contenido del dashboard
         add_action('woocommerce_account_dashboard', array($this, 'dashboard_content'), 0);
        
        // Añadir widgets personalizados al dashboard
        add_action('mam_dashboard_after_content', array($this, 'add_custom_widgets'));
        
        // Personalizar título de la página
        add_filter('woocommerce_endpoint_dashboard_title', array($this, 'custom_dashboard_title'));
        
        // Añadir estilos específicos para el dashboard
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_scripts'));
    }

    /**
     * Reemplazar el contenido del dashboard
     */
public function dashboard_content() {
    // Eliminar el contenido por defecto de WooCommerce
    remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');
    
    $options = get_option('mam_options', array());
    $enable_custom_dashboard = isset($options['enable_custom_dashboard']) ? $options['enable_custom_dashboard'] : 1;
    
    if ($enable_custom_dashboard) {
        // Cargar el template personalizado una sola vez con la ruta absoluta
        wc_get_template(
            'myaccount/dashboard.php',
            array(), // Variables para el template
            '', // Template path (vacío para usar la ruta absoluta)
            MAM_PLUGIN_DIR . 'templates/' // Ruta absoluta
        );
    }
}
    /**
     * Añadir widgets personalizados al dashboard
     */
    public function add_custom_widgets() {
        // Obtener usuario actual
        $current_user = wp_get_current_user();
        
        // Calcular estadísticas adicionales
        
        // Widget de puntos o recompensas (si existe un programa de fidelización)
        $this->render_rewards_widget($current_user);
        
        // Widget de actividad reciente
        $this->render_recent_activity_widget($current_user);
        
        // Widget de productos sugeridos
        $this->render_suggested_products_widget($current_user);
    }

    /**
     * Widget de recompensas
     */
    private function render_rewards_widget($user) {
        // Verificar si hay sistema de puntos/recompensas instalado
        if (!function_exists('WC_Points_Rewards') && !class_exists('WC_Points_Rewards_Manager')) {
            return;
        }
        
        // Obtener puntos del usuario (ejemplo para WooCommerce Points and Rewards)
        $points = WC_Points_Rewards_Manager::get_users_points($user->ID);
        $points_value = WC_Points_Rewards_Manager::get_users_points_value($user->ID);
        
        ?>
        <div class="mam-dashboard-section">
            <h3><?php _e('Mis Puntos y Recompensas', 'my-account-manager'); ?></h3>
            
            <div class="mam-dashboard-card">
                <div class="mam-dashboard-card-header">
                    <span class="mam-dashboard-card-title"><?php _e('Tu Balance', 'my-account-manager'); ?></span>
                    <div class="mam-dashboard-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mam-dashboard-card-content">
                    <div class="mam-dashboard-card-number"><?php echo esc_html($points); ?></div>
                    <div class="mam-dashboard-card-text"><?php _e('Puntos disponibles', 'my-account-manager'); ?></div>
                    <div class="mam-dashboard-card-text"><?php printf(__('Valor: %s', 'my-account-manager'), wc_price($points_value)); ?></div>
                </div>
                <div class="mam-dashboard-card-footer">
                    <a href="<?php echo esc_url(wc_get_endpoint_url('points-rewards')); ?>">
                        <?php _e('Ver detalles de mis puntos', 'my-account-manager'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Widget de actividad reciente
     */
    private function render_recent_activity_widget($user) {
        ?>
        <div class="mam-dashboard-section">
            <h3><?php _e('Actividad Reciente', 'my-account-manager'); ?></h3>
            
            <div class="mam-activity-list">
                <?php
                // Obtener actividad reciente: pedidos, reseñas, etc.
                $recent_activities = $this->get_recent_activities($user->ID);
                
                if (!empty($recent_activities)) {
                    foreach ($recent_activities as $activity) {
                        ?>
                        <div class="mam-activity-item">
                            <div class="mam-activity-icon">
                                <?php echo $activity['icon']; ?>
                            </div>
                            <div class="mam-activity-content">
                                <div class="mam-activity-text"><?php echo $activity['text']; ?></div>
                                <div class="mam-activity-date"><?php echo $activity['date']; ?></div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>' . __('No hay actividad reciente que mostrar.', 'my-account-manager') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener actividades recientes
     */
    private function get_recent_activities($user_id) {
        $activities = array();
        
        // Obtener pedidos recientes
        $args = array(
            'customer_id' => $user_id,
            'limit'       => 3,
            'orderby'     => 'date',
            'order'       => 'DESC',
        );
        
        $orders = wc_get_orders($args);
        
        if ($orders) {
            foreach ($orders as $order) {
                $date = $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
                
                $activities[] = array(
                    'type' => 'order',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>',
                    'text' => sprintf(__('Pedido #%s cambiado a: %s', 'my-account-manager'), 
                        $order->get_order_number(), 
                        wc_get_order_status_name($order->get_status())
                    ),
                    'date' => $date,
                );
            }
        }
        
        // Obtener reseñas si existen
        if (function_exists('wc_get_product_id_by_sku')) {
            $args = array(
                'user_id' => $user_id,
                'number'  => 3,
                'status'  => 'approve',
                'type'    => 'review',
            );
            
            $reviews = get_comments($args);
            
            if ($reviews) {
                foreach ($reviews as $review) {
                    $product_id = get_comment_meta($review->comment_ID, 'rating', true);
                    $product = wc_get_product($review->comment_post_ID);
                    
                    if ($product) {
                        $activities[] = array(
                            'type' => 'review',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>',
                            'text' => sprintf(__('Publicaste una reseña para: %s', 'my-account-manager'), 
                                $product->get_name()
                            ),
                            'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($review->comment_date)),
                        );
                    }
                }
            }
        }
        
        // Ordenar por fecha (más reciente primero)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, 5);
    }

    /**
     * Widget de productos sugeridos
     */
    private function render_suggested_products_widget($user) {
        // Obtener productos sugeridos basados en compras anteriores
        $suggested_products = $this->get_suggested_products($user->ID);
        
        if (empty($suggested_products)) {
            return;
        }
        
        ?>
        <div class="mam-dashboard-section">
            <h3><?php _e('Productos Recomendados', 'my-account-manager'); ?></h3>
            
            <div class="mam-products-grid">
                <?php
                foreach ($suggested_products as $product_id) {
                    $product = wc_get_product($product_id);
                    
                    if (!$product) {
                        continue;
                    }
                    
                    ?>
                    <div class="mam-product-card">
                        <div class="mam-product-image">
                            <?php echo $product->get_image('thumbnail'); ?>
                        </div>
                        <div class="mam-product-details">
                            <h4 class="mam-product-title">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </h4>
                            <div class="mam-product-price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                        </div>
                        <div class="mam-product-action">
                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="mam-button mam-button-primary mam-add-to-cart">
                                <?php _e('Añadir al carrito', 'my-account-manager'); ?>
                            </a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener productos sugeridos
     */
    private function get_suggested_products($user_id) {
        $suggested_products = array();
        
        // Obtener todos los pedidos del usuario
        $args = array(
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => array('completed', 'processing'),
        );
        
        $orders = wc_get_orders($args);
        
        if (empty($orders)) {
            return array();
        }
        
        $purchased_products = array();
        $product_categories = array();
        
        // Obtener productos comprados y sus categorías
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $purchased_products[] = $product_id;
                
                $terms = get_the_terms($product_id, 'product_cat');
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        if (!isset($product_categories[$term->term_id])) {
                            $product_categories[$term->term_id] = 0;
                        }
                        $product_categories[$term->term_id]++;
                    }
                }
            }
        }
        
        // Obtener categorías preferidas
        arsort($product_categories);
        $preferred_categories = array_slice(array_keys($product_categories), 0, 3);
        
        // Obtener productos de las categorías preferidas que el usuario aún no ha comprado
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 4,
            'post__not_in'   => $purchased_products,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $preferred_categories,
                ),
            ),
        );
        
        $products = get_posts($args);
        
        if ($products) {
            foreach ($products as $product) {
                $suggested_products[] = $product->ID;
            }
        }
        
        // Si no hay suficientes productos, obtener productos populares
        if (count($suggested_products) < 4) {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 4 - count($suggested_products),
                'post__not_in'   => array_merge($purchased_products, $suggested_products),
                'meta_key'       => 'total_sales',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            );
            
            $popular_products = get_posts($args);
            
            if ($popular_products) {
                foreach ($popular_products as $product) {
                    $suggested_products[] = $product->ID;
                }
            }
        }
        
        return $suggested_products;
    }

    /**
     * Personalizar título del dashboard
     */
    public function custom_dashboard_title($title) {
        return __('Panel Principal', 'my-account-manager');
    }

    /**
     * Enqueue scripts específicos para el dashboard
     */
    public function enqueue_dashboard_scripts() {
        if (is_account_page() && is_wc_endpoint_url('dashboard')) {
            // Aquí se podrían cargar scripts específicos para el dashboard
            // Por ejemplo, para gráficos o visualizaciones de datos
            wp_enqueue_script('mam-dashboard-charts', MAM_PLUGIN_URL . 'assets/js/dashboard-charts.js', array('jquery'), MAM_VERSION, true);
        }
    }
}

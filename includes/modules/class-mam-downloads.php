<?php
/**
 * Downloads functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Downloads Class
 */
class MAM_Downloads {

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
        // Personalizar título de la página
        add_filter('woocommerce_endpoint_downloads_title', array($this, 'custom_downloads_title'));
        
        // Personalizar contenido de la página de descargas
        add_action('woocommerce_before_account_downloads', array($this, 'before_downloads_content'));
        add_action('woocommerce_after_account_downloads', array($this, 'after_downloads_content'));
        
        // Personalizar tabla de descargas
        add_filter('woocommerce_account_downloads_columns', array($this, 'customize_downloads_columns'));
        
        // Añadir información adicional a las descargas
        add_action('woocommerce_account_downloads_column_download-remaining', array($this, 'add_download_remaining_column'), 10, 2);
        add_action('woocommerce_account_downloads_column_download-expires', array($this, 'add_download_expires_column'), 10, 2);
        
        // Añadir filtro de descargas
        add_action('woocommerce_before_account_downloads', array($this, 'add_downloads_filter'));
        
        // Personalizar botones de descarga
        add_filter('woocommerce_account_downloads_actions', array($this, 'customize_download_actions'), 10, 2);
        
        // Añadir vista previa de archivos
        add_action('woocommerce_before_available_downloads', array($this, 'add_file_previews'));
        
        // Añadir estadísticas de descarga
        add_action('woocommerce_before_account_downloads', array($this, 'add_download_statistics'));
        
        // Añadir paginación personalizada
        add_action('woocommerce_after_account_downloads', array($this, 'add_custom_pagination'));
        
        // Añadir visualización de tipo grilla/lista
        add_action('woocommerce_before_account_downloads', array($this, 'add_view_switcher'));
        
        // Modificar la consulta de descargas
        add_filter('woocommerce_get_customer_available_downloads', array($this, 'filter_customer_downloads'), 10, 1);
        
        // Mostrar archivos organizados por categorías
        add_action('woocommerce_before_account_downloads', array($this, 'organize_downloads_by_category'));
        
        // Enqueue scripts específicos para la página de descargas
        add_action('wp_enqueue_scripts', array($this, 'enqueue_downloads_scripts'));
    }

    // Añadir a class-mam-downloads.php
public function register_ajax_handlers() {
    add_action('wp_ajax_mam_filter_downloads', array($this, 'ajax_filter_downloads'));
    add_action('wp_ajax_mam_search_downloads', array($this, 'ajax_search_downloads'));
    add_action('wp_ajax_mam_load_file_preview', array($this, 'ajax_load_file_preview'));
}

public function ajax_filter_downloads() {
    check_ajax_referer('mam-nonce', 'security');
    
    $category_id = isset($_POST['category']) ? absint($_POST['category']) : 0;
    $view_mode = isset($_POST['view_mode']) ? sanitize_text_field($_POST['view_mode']) : 'list';
    
    // Obtener descargas del cliente
    $downloads = WC()->customer->get_downloadable_products();
    
    // Filtrar por categoría si es necesario
    if ($category_id > 0) {
        $filtered_downloads = array();
        
        foreach ($downloads as $download) {
            $product_id = $download['product_id'];
            $product_categories = get_the_terms($product_id, 'product_cat');
            
            if ($product_categories && !is_wp_error($product_categories)) {
                $category_ids = wp_list_pluck($product_categories, 'term_id');
                
                if (in_array($category_id, $category_ids)) {
                    $filtered_downloads[] = $download;
                }
            }
        }
        
        $downloads = $filtered_downloads;
    }
    
    // Renderizar las descargas actualizadas
    ob_start();
    
    if ($view_mode === 'grid') {
        // Renderizar vista de cuadrícula
        // ...
    } else {
        // Renderizar vista de lista
        // ...
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'count' => count($downloads)
    ));
}

// Implementar ajax_search_downloads y ajax_load_file_preview similarmente
    /**
     * Personalizar título de la página de descargas
     */
    public function custom_downloads_title($title) {
        return __('Mis Descargas', 'my-account-manager');
    }

    /**
     * Añadir contenido antes de la lista de descargas
     */
    public function before_downloads_content() {
        ?>
        <div class="mam-downloads-header">
            <p><?php _e('Aquí encontrarás todos los archivos disponibles para su descarga.', 'my-account-manager'); ?></p>
        </div>
        <?php
    }

    /**
     * Añadir contenido después de la lista de descargas
     */
    public function after_downloads_content() {
        ?>
        <div class="mam-downloads-footer">
            <div class="mam-downloads-help">
                <h4><?php _e('¿Problemas con tus descargas?', 'my-account-manager'); ?></h4>
                <p><?php _e('Si tienes problemas para descargar tus archivos, por favor contáctanos.', 'my-account-manager'); ?></p>
                <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_help_page_id'))); ?>" class="mam-button mam-button-secondary">
                    <?php _e('Centro de Ayuda', 'my-account-manager'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Personalizar columnas de la tabla de descargas
     */
    public function customize_downloads_columns($columns) {
        $new_columns = array();
        
        // Establecer nuestro orden de columnas
        if (isset($columns['download-product'])) {
            $new_columns['download-product'] = $columns['download-product'];
        }
        
        if (isset($columns['download-file'])) {
            $new_columns['download-file'] = $columns['download-file'];
        }
        
        // Añadir columna de descargas restantes
        $new_columns['download-remaining'] = __('Descargas Restantes', 'my-account-manager');
        
        // Añadir columna de fecha de expiración
        $new_columns['download-expires'] = __('Expiración', 'my-account-manager');
        
        if (isset($columns['download-actions'])) {
            $new_columns['download-actions'] = $columns['download-actions'];
        }
        
        return $new_columns;
    }

    /**
     * Añadir contenido a la columna de descargas restantes
     */
    public function add_download_remaining_column($download) {
        if ($download['downloads_remaining'] === 'unlimited') {
            echo '<span class="mam-download-unlimited">' . __('Ilimitadas', 'my-account-manager') . '</span>';
        } else {
            $remaining = absint($download['downloads_remaining']);
            $total = absint(get_post_meta($download['product_id'], '_download_limit', true));
            
            if ($total > 0 && $remaining > 0) {
                // Mostrar barra de progreso para visualizar fácilmente las descargas restantes
                ?>
                <div class="mam-download-remaining">
                    <span class="mam-download-count"><?php echo sprintf(__('%s de %s', 'my-account-manager'), $remaining, $total); ?></span>
                    <div class="mam-download-progress">
                        <div class="mam-download-progress-bar" style="width: <?php echo esc_attr(($remaining / $total) * 100); ?>%"></div>
                    </div>
                </div>
                <?php
            } else {
                echo sprintf(__('%s restantes', 'my-account-manager'), $remaining);
            }
        }
    }

    /**
     * Añadir contenido a la columna de fecha de expiración
     */
    public function add_download_expires_column($download) {
        if (empty($download['access_expires']) || strtotime($download['access_expires']) <= 0) {
            echo '<span class="mam-download-never-expires">' . __('No expira', 'my-account-manager') . '</span>';
        } else {
            $expires = strtotime($download['access_expires']);
            $now = current_time('timestamp');
            $days_left = ceil(($expires - $now) / (60 * 60 * 24));
            
            if ($days_left < 0) {
                echo '<span class="mam-download-expired">' . __('Expirado', 'my-account-manager') . '</span>';
            } elseif ($days_left <= 7) {
                echo '<span class="mam-download-expiring-soon">' . sprintf(__('Expira en %s días', 'my-account-manager'), $days_left) . '</span>';
                echo '<br><small>' . date_i18n(get_option('date_format'), $expires) . '</small>';
            } else {
                echo date_i18n(get_option('date_format'), $expires);
            }
        }
    }

    /**
     * Añadir filtro de descargas
     */
    public function add_downloads_filter() {
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads)) {
            return;
        }
        
        // Obtener categorías de productos disponibles en las descargas
        $product_categories = array();
        $product_ids = array();
        
        foreach ($customer_downloads as $download) {
            $product_ids[] = $download['product_id'];
        }
        
        $product_ids = array_unique($product_ids);
        
        foreach ($product_ids as $product_id) {
            $product_cats = get_the_terms($product_id, 'product_cat');
            
            if ($product_cats && !is_wp_error($product_cats)) {
                foreach ($product_cats as $cat) {
                    $product_categories[$cat->term_id] = $cat->name;
                }
            }
        }
        
        // Si no hay categorías, no mostrar el filtro
        if (empty($product_categories)) {
            return;
        }
        
        $current_category = isset($_GET['download_category']) ? absint($_GET['download_category']) : 0;
        
        ?>
        <div class="mam-downloads-filter">
            <form method="get" action="<?php echo esc_url(wc_get_account_endpoint_url('downloads')); ?>">
                <div class="mam-filter-row">
                    <div class="mam-filter-select">
                        <label for="download_category"><?php _e('Filtrar por categoría:', 'my-account-manager'); ?></label>
                        <select name="download_category" id="download_category" class="mam-select">
                            <option value="0"><?php _e('Todas las categorías', 'my-account-manager'); ?></option>
                            <?php foreach ($product_categories as $cat_id => $cat_name) : ?>
                                <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($current_category, $cat_id); ?>>
                                    <?php echo esc_html($cat_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mam-filter-actions">
                        <button type="submit" class="mam-button mam-button-secondary mam-filter-button">
                            <?php _e('Filtrar', 'my-account-manager'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Personalizar botones de descarga
     */
    public function customize_download_actions($actions, $download) {
        if (isset($actions['download'])) {
            // Añadir clases para estilizar
            $actions['download']['class'] = array('mam-button', 'mam-button-primary', 'mam-download-button');
            
            // Añadir ícono al botón
            $actions['download']['name'] = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg> ' . $actions['download']['name'];
        }
        
        // Añadir botón de información adicional si es un tipo de archivo conocido
        $file_extension = pathinfo($download['file']['file'], PATHINFO_EXTENSION);
        $preview_extensions = array('pdf', 'jpg', 'jpeg', 'png', 'gif');
        
        if (in_array(strtolower($file_extension), $preview_extensions)) {
            $actions['preview'] = array(
                'url'  => '#download-preview-' . esc_attr($download['download_id']),
                'name' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg> ' . __('Vista Previa', 'my-account-manager'),
                'class' => array('mam-button', 'mam-button-secondary', 'mam-preview-button', 'mam-preview-trigger')
            );
        }
        
        return $actions;
    }

    /**
     * Añadir vista previa de archivos
     */
    public function add_file_previews() {
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads)) {
            return;
        }
        
        ?>
        <div class="mam-file-previews">
            <?php foreach ($customer_downloads as $download) : 
                $file_extension = pathinfo($download['file']['file'], PATHINFO_EXTENSION);
                $preview_extensions = array('pdf', 'jpg', 'jpeg', 'png', 'gif');
                
                if (in_array(strtolower($file_extension), $preview_extensions)) :
                    ?>
                    <div id="download-preview-<?php echo esc_attr($download['download_id']); ?>" class="mam-file-preview" style="display: none;">
                        <div class="mam-preview-header">
                            <h3><?php echo esc_html($download['download_name']); ?></h3>
                            <span class="mam-preview-close">×</span>
                        </div>
                        
                        <div class="mam-preview-content">
                            <?php if (in_array(strtolower($file_extension), array('jpg', 'jpeg', 'png', 'gif'))) : ?>
                                <div class="mam-image-preview">
                                    <img src="<?php echo esc_url($download['file']['file']); ?>" alt="<?php echo esc_attr($download['download_name']); ?>">
                                </div>
                            <?php elseif (strtolower($file_extension) === 'pdf') : ?>
                                <div class="mam-pdf-preview">
                                    <iframe src="<?php echo esc_url($download['file']['file']); ?>" width="100%" height="500px"></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mam-preview-footer">
                            <a href="<?php echo esc_url($download['download_url']); ?>" class="mam-button mam-button-primary">
                                <?php _e('Descargar Archivo', 'my-account-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Añadir estadísticas de descarga
     */
    public function add_download_statistics() {
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads)) {
            return;
        }
        
        // Calcular estadísticas
        $total_downloads = count($customer_downloads);
        $total_products = count(array_unique(array_column($customer_downloads, 'product_id')));
        $expiring_soon = 0;
        $file_types = array();
        
        foreach ($customer_downloads as $download) {
            // Contar descargas que expiran pronto (en los próximos 7 días)
            if (!empty($download['access_expires'])) {
                $expires = strtotime($download['access_expires']);
                $now = current_time('timestamp');
                $days_left = ceil(($expires - $now) / (60 * 60 * 24));
                
                if ($days_left > 0 && $days_left <= 7) {
                    $expiring_soon++;
                }
            }
            
            // Contar tipos de archivo
            $file_extension = strtolower(pathinfo($download['file']['file'], PATHINFO_EXTENSION));
            
            if (!isset($file_types[$file_extension])) {
                $file_types[$file_extension] = 0;
            }
            
            $file_types[$file_extension]++;
        }
        
        ?>
        <div class="mam-download-stats">
            <div class="mam-stat-item">
                <div class="mam-stat-number"><?php echo esc_html($total_downloads); ?></div>
                <div class="mam-stat-label"><?php _e('Total de archivos', 'my-account-manager'); ?></div>
            </div>
            
            <div class="mam-stat-item">
                <div class="mam-stat-number"><?php echo esc_html($total_products); ?></div>
                <div class="mam-stat-label"><?php _e('Productos', 'my-account-manager'); ?></div>
            </div>
            
            <?php if ($expiring_soon > 0) : ?>
                <div class="mam-stat-item mam-stat-warning">
                    <div class="mam-stat-number"><?php echo esc_html($expiring_soon); ?></div>
                    <div class="mam-stat-label"><?php _e('Expiran pronto', 'my-account-manager'); ?></div>
                </div>
            <?php endif; ?>
            
            <?php
            // Mostrar gráfico de tipos de archivo si hay variedad
            if (count($file_types) > 1) :
                arsort($file_types); // Ordenar por cantidad (mayor a menor)
                $file_types = array_slice($file_types, 0, 3); // Tomar solo los 3 más comunes
                ?>
                <div class="mam-stat-item mam-stat-types">
                    <div class="mam-stat-label"><?php _e('Tipos de archivo', 'my-account-manager'); ?></div>
                    <div class="mam-file-types-chart">
                        <?php foreach ($file_types as $extension => $count) : ?>
                            <div class="mam-file-type">
                                <span class="mam-file-extension"><?php echo esc_html(strtoupper($extension)); ?></span>
                                <span class="mam-file-count"><?php echo esc_html($count); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Añadir paginación personalizada
     */
    public function add_custom_pagination() {
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads) || count($customer_downloads) <= 10) {
            return;
        }
        
        $total_downloads = count($customer_downloads);
        $per_page = 10;
        $total_pages = ceil($total_downloads / $per_page);
        $current_page = isset($_GET['download_page']) ? absint($_GET['download_page']) : 1;
        
        if ($total_pages < 2) {
            return;
        }
        
        ?>
        <div class="mam-downloads-pagination">
            <ul class="mam-pagination">
                <?php if ($current_page > 1) : ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('download_page', $current_page - 1)); ?>" class="mam-page-link mam-prev-page">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <?php _e('Anterior', 'my-account-manager'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                // Mostrar páginas
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) {
                    echo '<li><a href="' . esc_url(add_query_arg('download_page', 1)) . '" class="mam-page-link">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="mam-pagination-dots">...</li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li>';
                    echo '<a href="' . esc_url(add_query_arg('download_page', $i)) . '" class="mam-page-link' . ($i === $current_page ? ' mam-active-page' : '') . '">' . $i . '</a>';
                    echo '</li>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="mam-pagination-dots">...</li>';
                    }
                    echo '<li><a href="' . esc_url(add_query_arg('download_page', $total_pages)) . '" class="mam-page-link">' . $total_pages . '</a></li>';
                }
                ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('download_page', $current_page + 1)); ?>" class="mam-page-link mam-next-page">
                            <?php _e('Siguiente', 'my-account-manager'); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Añadir switch de visualización (grilla/lista)
     */
    public function add_view_switcher() {
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads)) {
            return;
        }
        
        $current_view = isset($_COOKIE['mam_downloads_view']) ? $_COOKIE['mam_downloads_view'] : 'list';
        
        ?>
        <div class="mam-view-switcher">
            <span class="mam-view-label"><?php _e('Ver como:', 'my-account-manager'); ?></span>
            
            <button type="button" class="mam-view-button mam-list-view <?php echo $current_view === 'list' ? 'active' : ''; ?>" data-view="list">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <?php _e('Lista', 'my-account-manager'); ?>
            </button>
            
            <button type="button" class="mam-view-button mam-grid-view <?php echo $current_view === 'grid' ? 'active' : ''; ?>" data-view="grid">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <?php _e('Cuadrícula', 'my-account-manager'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Filtrar las descargas del cliente
     */
    public function filter_customer_downloads($downloads) {
        if (empty($downloads)) {
            return $downloads;
        }
        
        // Filtrar por categoría si se ha seleccionado una
        if (isset($_GET['download_category']) && absint($_GET['download_category']) > 0) {
            $category_id = absint($_GET['download_category']);
            
            $filtered_downloads = array();
            
            foreach ($downloads as $download) {
                $product_id = $download['product_id'];
                $product_categories = get_the_terms($product_id, 'product_cat');
                
                if ($product_categories && !is_wp_error($product_categories)) {
                    $category_ids = wp_list_pluck($product_categories, 'term_id');
                    
                    if (in_array($category_id, $category_ids)) {
                        $filtered_downloads[] = $download;
                    }
                }
            }
            
            $downloads = $filtered_downloads;
        }
        
        // Implementar paginación
        if (isset($_GET['download_page']) && absint($_GET['download_page']) > 0) {
            $per_page = 10;
            $current_page = absint($_GET['download_page']);
            
            $offset = ($current_page - 1) * $per_page;
            $downloads = array_slice($downloads, $offset, $per_page);
        } else {
            // Si no hay paginación, limitar a 10 elementos por defecto
            $downloads = array_slice($downloads, 0, 10);
        }
        
        return $downloads;
    }

    /**
     * Organizar descargas por categoría
     */
    public function organize_downloads_by_category() {
        // Verificar si se ha activado esta vista
        if (!isset($_GET['view']) || $_GET['view'] !== 'categories') {
            return;
        }
        
        // Obtener descargas disponibles
        $customer_downloads = WC()->customer->get_downloadable_products();
        
        if (empty($customer_downloads)) {
            return;
        }
        
        // Organizar las descargas por categoría
        $downloads_by_category = array();
        
        foreach ($customer_downloads as $download) {
            $product_id = $download['product_id'];
            $product_categories = get_the_terms($product_id, 'product_cat');
            
            if (!$product_categories || is_wp_error($product_categories)) {
                // Si no tiene categoría, asignarla a "Sin categoría"
                if (!isset($downloads_by_category['uncategorized'])) {
                    $downloads_by_category['uncategorized'] = array(
                        'name' => __('Sin categoría', 'my-account-manager'),
                        'downloads' => array()
                    );
                }
                
                $downloads_by_category['uncategorized']['downloads'][] = $download;
            } else {
                foreach ($product_categories as $category) {
                    $category_id = $category->term_id;
                    
                    if (!isset($downloads_by_category[$category_id])) {
                        $downloads_by_category[$category_id] = array(
                            'name' => $category->name,
                            'downloads' => array()
                        );
                    }
                    
                    $downloads_by_category[$category_id]['downloads'][] = $download;
                }
            }
        }
        
        // Renderizar las descargas organizadas por categoría
        if (!empty($downloads_by_category)) {
            // Ocultar la tabla predeterminada de WooCommerce
            remove_action('woocommerce_account_downloads_endpoint', 'woocommerce_account_downloads');
            
            ?>
            <div class="mam-downloads-by-category">
                <?php foreach ($downloads_by_category as $category_id => $category_data) : ?>
                    <div class="mam-download-category">
                        <h3 class="mam-category-title"><?php echo esc_html($category_data['name']); ?></h3>
                        
                        <div class="mam-category-downloads">
                            <?php foreach ($category_data['downloads'] as $download) : ?>
                                <div class="mam-download-item">
                                    <div class="mam-download-info">
                                        <h4 class="mam-download-title"><?php echo esc_html($download['download_name']); ?></h4>
                                        <div class="mam-download-product"><?php echo esc_html($download['product_name']); ?></div>
                                        
                                        <?php
                                        // Mostrar información sobre expiración
                                        if (!empty($download['access_expires'])) {
                                            $expires = strtotime($download['access_expires']);
                                            $now = current_time('timestamp');
                                            $days_left = ceil(($expires - $now) / (60 * 60 * 24));
                                            
                                            if ($days_left < 0) {
                                                echo '<div class="mam-download-expires mam-expired">' . __('Expirado', 'my-account-manager') . '</div>';
                                            } elseif ($days_left <= 7) {
                                                echo '<div class="mam-download-expires mam-expiring-soon">' . sprintf(__('Expira en %s días', 'my-account-manager'), $days_left) . '</div>';
                                            } else {
                                                echo '<div class="mam-download-expires">' . sprintf(__('Expira: %s', 'my-account-manager'), date_i18n(get_option('date_format'), $expires)) . '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="mam-download-actions">
                                        <a href="<?php echo esc_url($download['download_url']); ?>" class="mam-button mam-button-primary mam-download-button">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <?php _e('Descargar', 'my-account-manager'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }

    /**
     * Enqueue scripts específicos para la página de descargas
     */
    public function enqueue_downloads_scripts() {
        if (is_account_page() && is_wc_endpoint_url('downloads')) {
            wp_enqueue_script('mam-downloads', MAM_PLUGIN_URL . 'assets/js/downloads.js', array('jquery'), MAM_VERSION, true);
        }
    }
}

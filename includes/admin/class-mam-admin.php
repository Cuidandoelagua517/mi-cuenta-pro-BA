<?php
/**
 * Admin functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Admin Class
 */
class MAM_Admin {

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
        // Añadir menú de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar opciones del plugin
        add_action('admin_init', array($this, 'register_settings'));
        
        // Añadir enlaces de acción en la página de plugins
        add_filter('plugin_action_links_' . MAM_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        // Añadir metabox en pedidos
        add_action('add_meta_boxes', array($this, 'add_order_meta_boxes'));
        
        // Guardar datos del metabox
        add_action('save_post', array($this, 'save_order_meta_box_data'));
        
        // Enqueue scripts para el admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('My Account Manager', 'my-account-manager'),
            __('My Account Manager', 'my-account-manager'),
            'manage_options',
            'my-account-manager',
            array($this, 'admin_page_content'),
            'dashicons-id-alt',
            58
        );
        
        // Submenu para configuración
        add_submenu_page(
            'my-account-manager',
            __('Configuración', 'my-account-manager'),
            __('Configuración', 'my-account-manager'),
            'manage_options',
            'my-account-manager',
            array($this, 'admin_page_content')
        );
        
        // Submenu para apariencia
        add_submenu_page(
            'my-account-manager',
            __('Apariencia', 'my-account-manager'),
            __('Apariencia', 'my-account-manager'),
            'manage_options',
            'my-account-manager-appearance',
            array($this, 'appearance_page_content')
        );
    }

    /**
     * Registrar opciones del plugin
     */
    public function register_settings() {
        // Grupo de opciones
        register_setting('mam_options_group', 'mam_options');
        
        // Sección de opciones generales
        add_settings_section(
            'mam_general_section',
            __('Opciones Generales', 'my-account-manager'),
            array($this, 'general_section_callback'),
            'my-account-manager'
        );
        
        // Campos de opciones generales
        add_settings_field(
            'mam_enable_ajax_login',
            __('Habilitar login por AJAX', 'my-account-manager'),
            array($this, 'enable_ajax_login_callback'),
            'my-account-manager',
            'mam_general_section'
        );
        
        add_settings_field(
            'mam_enable_custom_dashboard',
            __('Habilitar dashboard personalizado', 'my-account-manager'),
            array($this, 'enable_custom_dashboard_callback'),
            'my-account-manager',
            'mam_general_section'
        );
        
        // Sección de opciones de apariencia
        add_settings_section(
            'mam_appearance_section',
            __('Opciones de Apariencia', 'my-account-manager'),
            array($this, 'appearance_section_callback'),
            'my-account-manager-appearance'
        );
        
        // Campos de opciones de apariencia
        add_settings_field(
            'mam_primary_color',
            __('Color principal', 'my-account-manager'),
            array($this, 'primary_color_callback'),
            'my-account-manager-appearance',
            'mam_appearance_section'
        );
        
        add_settings_field(
            'mam_secondary_color',
            __('Color secundario', 'my-account-manager'),
            array($this, 'secondary_color_callback'),
            'my-account-manager-appearance',
            'mam_appearance_section'
        );
    }

    /**
     * Callback para la sección general
     */
    public function general_section_callback() {
        echo '<p>' . __('Configura las opciones generales del plugin My Account Manager.', 'my-account-manager') . '</p>';
    }

    /**
     * Callback para el campo de login AJAX
     */
    public function enable_ajax_login_callback() {
        $options = get_option('mam_options');
        $value = isset($options['enable_ajax_login']) ? $options['enable_ajax_login'] : 1;
        
        echo '<input type="checkbox" id="mam_enable_ajax_login" name="mam_options[enable_ajax_login]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="mam_enable_ajax_login">' . __('Activar inicio de sesión mediante AJAX', 'my-account-manager') . '</label>';
    }

    /**
     * Callback para el campo de dashboard personalizado
     */
    public function enable_custom_dashboard_callback() {
        $options = get_option('mam_options');
        $value = isset($options['enable_custom_dashboard']) ? $options['enable_custom_dashboard'] : 1;
        
        echo '<input type="checkbox" id="mam_enable_custom_dashboard" name="mam_options[enable_custom_dashboard]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="mam_enable_custom_dashboard">' . __('Activar dashboard personalizado', 'my-account-manager') . '</label>';
    }

    /**
     * Callback para la sección de apariencia
     */
    public function appearance_section_callback() {
        echo '<p>' . __('Personaliza la apariencia visual del plugin.', 'my-account-manager') . '</p>';
    }

    /**
     * Callback para el campo de color principal
     */
    public function primary_color_callback() {
        $options = get_option('mam_options');
        $value = isset($options['primary_color']) ? $options['primary_color'] : '#4a6cf7';
        
        echo '<input type="color" id="mam_primary_color" name="mam_options[primary_color]" value="' . esc_attr($value) . '" class="mam-color-picker" />';
        echo '<p class="description">' . __('Color principal para botones y elementos destacados.', 'my-account-manager') . '</p>';
    }

    /**
     * Callback para el campo de color secundario
     */
    public function secondary_color_callback() {
        $options = get_option('mam_options');
        $value = isset($options['secondary_color']) ? $options['secondary_color'] : '#6b7280';
        
        echo '<input type="color" id="mam_secondary_color" name="mam_options[secondary_color]" value="' . esc_attr($value) . '" class="mam-color-picker" />';
        echo '<p class="description">' . __('Color secundario para elementos complementarios.', 'my-account-manager') . '</p>';
    }

    /**
     * Contenido de la página principal de administración
     */
    public function admin_page_content() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes suficientes permisos para acceder a esta página.', 'my-account-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('mam_options_group');
                do_settings_sections('my-account-manager');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Contenido de la página de apariencia
     */
    public function appearance_page_content() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes suficientes permisos para acceder a esta página.', 'my-account-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('mam_options_group');
                do_settings_sections('my-account-manager-appearance');
                submit_button();
                ?>
            </form>
            
            <div class="mam-preview-section">
                <h2><?php _e('Vista previa', 'my-account-manager'); ?></h2>
                <div class="mam-preview-container">
                    <!-- Aquí se mostraría una vista previa de los cambios de estilo -->
                    <div class="mam-preview-button mam-preview-primary"><?php _e('Botón Principal', 'my-account-manager'); ?></div>
                    <div class="mam-preview-button mam-preview-secondary"><?php _e('Botón Secundario', 'my-account-manager'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir enlaces de acción en la página de plugins
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=my-account-manager') . '">' . __('Configuración', 'my-account-manager') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }

    /**
     * Añadir metabox en pedidos
     */
    public function add_order_meta_boxes() {
        add_meta_box(
            'mam_order_custom_info',
            __('My Account Manager - Información adicional', 'my-account-manager'),
            array($this, 'render_order_meta_box'),
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Renderizar metabox de pedidos
     */
    public function render_order_meta_box($post) {
        // Verificar nonce por seguridad
        wp_nonce_field('mam_save_order_meta_box_data', 'mam_order_meta_box_nonce');
        
        // Obtener valor actual
        $order_note = get_post_meta($post->ID, '_mam_order_admin_note', true);
        
        ?>
        <p>
            <label for="mam_order_admin_note"><?php _e('Nota interna del pedido:', 'my-account-manager'); ?></label>
            <textarea id="mam_order_admin_note" name="mam_order_admin_note" style="width: 100%;"><?php echo esc_textarea($order_note); ?></textarea>
            <span class="description"><?php _e('Esta nota es solo visible para administradores.', 'my-account-manager'); ?></span>
        </p>
        <?php
    }

    /**
     * Guardar datos del metabox
     */
    public function save_order_meta_box_data($post_id) {
        // Verificar si es autoguardado
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verificar nonce
        if (!isset($_POST['mam_order_meta_box_nonce']) || !wp_verify_nonce($_POST['mam_order_meta_box_nonce'], 'mam_save_order_meta_box_data')) {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar datos
        if (isset($_POST['mam_order_admin_note'])) {
            update_post_meta(
                $post_id,
                '_mam_order_admin_note',
                sanitize_textarea_field($_POST['mam_order_admin_note'])
            );
        }
    }

    /**
     * Enqueue scripts para el admin
     */
    public function enqueue_admin_scripts($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'my-account-manager') === false) {
            return;
        }
        
        // Cargar script de WordPress para selectores de color
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Script personalizado para la página de admin
        wp_enqueue_script(
            'mam-admin-script',
            MAM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            MAM_VERSION,
            true
        );
    }
}

// Inicializar la clase admin
add_action('plugins_loaded', array('MAM_Admin', 'init'));

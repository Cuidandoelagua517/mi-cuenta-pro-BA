<?php
/**
 * Plugin Name: My Account Manager
 * Plugin URI: https://tudominio.com/my-account-manager
 * Description: Plugin personalizado para la gestión de cuentas de usuario, optimizado bajo los principios de UX y UI.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: my-account-manager
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin
 */
class My_Account_Manager {

    /**
     * Versión del plugin
     */
    const VERSION = '1.0.0';

    /**
     * Instancia única
     */
    protected static $_instance = null;

    /**
     * Instancia única (Singleton)
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        
        // Inicializar módulos
        add_action('init', array($this, 'init_modules'), 0);
    }

    /**
     * Definir constantes
     */
    private function define_constants() {
        $this->define('MAM_VERSION', self::VERSION);
        $this->define('MAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
        $this->define('MAM_PLUGIN_URL', plugin_dir_url(__FILE__));
        $this->define('MAM_PLUGIN_BASENAME', plugin_basename(__FILE__));
        $this->define('MAM_HPOS_COMPATIBLE', true);
    }
// Añadir después de define_constants() en el constructor
private function declare_compatibility() {
    // Declarar compatibilidad con HPOS si la clase existe
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
}
    /**
     * Definir si no está definido
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Incluir archivos necesarios
     */
    private function includes() {
        // Core
        include_once MAM_PLUGIN_DIR . 'includes/class-mam-core.php';
        
        // Admin
        if (is_admin()) {
            include_once MAM_PLUGIN_DIR . 'includes/admin/class-mam-admin.php';
        }
        
        // Módulos
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-dashboard.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-orders.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-addresses.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-account-details.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-downloads.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-payments.php';
        include_once MAM_PLUGIN_DIR . 'includes/modules/class-mam-login-register.php';
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
        
        // Cargar traducción
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Cargar assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Inicializar módulos
     */
    public function init_modules() {
        // Inicializar si WooCommerce está activo
        if ($this->is_woocommerce_active()) {
            // Inicializar los módulos
            MAM_Dashboard::init();
            MAM_Orders::init();
            MAM_Addresses::init();
            MAM_Account_Details::init();
            MAM_Downloads::init();
            MAM_Payments::init();
            MAM_Login_Register::init();
        } else {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
    }

    /**
     * Verificar si WooCommerce está activo
     */
    public function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * Activación del plugin
     */
    public function activation() {
        // Crear páginas personalizadas si es necesario
        // Inicializar opciones del plugin
        
        // Vaciar el caché de reglas de reescritura
        flush_rewrite_rules();
    }

    /**
     * Desactivación del plugin
     */
    public function deactivation() {
        // Limpiar opciones si es necesario
        
        // Vaciar el caché de reglas de reescritura
        flush_rewrite_rules();
    }

    /**
     * Cargar traducción
     */
    public function load_textdomain() {
        load_plugin_textdomain('my-account-manager', false, dirname(MAM_PLUGIN_BASENAME) . '/languages/');
    }

    /**
     * Enqueue de assets para el frontend
     */
    public function enqueue_frontend_assets() {
        // Registrar y encolar estilos CSS
        wp_register_style('mam-styles', MAM_PLUGIN_URL . 'assets/css/frontend.css', array(), MAM_VERSION);
        wp_enqueue_style('mam-styles');
        
        // Registrar y encolar scripts JS
        wp_register_script('mam-scripts', MAM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), MAM_VERSION, true);
        wp_enqueue_script('mam-scripts');
        
        // Localizar el script
        wp_localize_script('mam-scripts', 'mam_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mam-nonce')
        ));
    }

    /**
     * Enqueue de assets para el admin
     */
    public function enqueue_admin_assets() {
        // Registrar y encolar estilos CSS
        wp_register_style('mam-admin-styles', MAM_PLUGIN_URL . 'assets/css/admin.css', array(), MAM_VERSION);
        wp_enqueue_style('mam-admin-styles');
        
        // Registrar y encolar scripts JS
        wp_register_script('mam-admin-scripts', MAM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MAM_VERSION, true);
        wp_enqueue_script('mam-admin-scripts');
    }

    /**
     * Aviso de WooCommerce faltante
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('My Account Manager requiere WooCommerce para funcionar. Por favor, instala y activa WooCommerce.', 'my-account-manager'); ?></p>
        </div>
        <?php
    }
}

/**
 * Función principal para acceder a la instancia de My_Account_Manager
 */
function MAM() {
    return My_Account_Manager::instance();
}

// Iniciar el plugin
$GLOBALS['my_account_manager'] = MAM();

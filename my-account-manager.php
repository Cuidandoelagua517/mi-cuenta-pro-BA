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
     * Propiedad para el módulo de login/registro
     */
    protected $login_register = null;

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

    /**
     * Declarar compatibilidad con HPOS de WooCommerce
     */
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
        include_once MAM_PLUGIN_DIR . 'includes/compatibility.php';
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
    
    // Añade este hook para declarar compatibilidad HPOS
    add_action('before_woocommerce_init', array($this, 'declare_wc_compatibility'));
}
/**
 * Declarar compatibilidad con características de WooCommerce
 */
public function declare_wc_compatibility() {
    // Verificar si la clase existe antes de usarla
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
}
    /**
     * Inicializar módulos
     */
    public function init_modules() {
        // Inicializar si WooCommerce está activo
        if ($this->is_woocommerce_active()) {
            // Inicializar los módulos creando instancias
            $dashboard = new MAM_Dashboard();
            $dashboard->init();
            
            $orders = new MAM_Orders();
            $orders->init();
            
            $addresses = new MAM_Addresses();
            $addresses->init();
            
            $account_details = new MAM_Account_Details();
            $account_details->init();
            
            $downloads = new MAM_Downloads();
            $downloads->init();
            
            $payments = new MAM_Payments();
            $payments->init();
            
            // Inicializar login/register y guardar la instancia
            $this->login_register = MAM_Login_Register::init();
            $this->login_register->init();
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
        $default_options = array(
            'enable_custom_dashboard' => 1,
            'enable_ajax_login' => 1
        );
        update_option('mam_options', $default_options);
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
     * Registrar manejadores AJAX
     */
    public function register_ajax_handlers() {
        // Verificar que login_register esté inicializado
        if ($this->login_register) {
            // Añadir handlers para usuarios no logueados
            add_action('wp_ajax_nopriv_mam_ajax_login', array($this->login_register, 'ajax_login'));
            add_action('wp_ajax_nopriv_mam_ajax_register', array($this->login_register, 'ajax_register'));
        }
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
        
        // Registrar los scripts JS
        wp_register_script('mam-scripts', MAM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), MAM_VERSION, true);
        
        // Scripts específicos para cada página
        if (is_account_page()) {
            if (is_wc_endpoint_url('orders')) {
                wp_enqueue_script('mam-orders', MAM_PLUGIN_URL . 'assets/js/orders.js', array('jquery'), MAM_VERSION, true);
            } elseif (is_wc_endpoint_url('edit-address')) {
                wp_enqueue_script('mam-addresses', MAM_PLUGIN_URL . 'assets/js/addresses.js', array('jquery'), MAM_VERSION, true);
            } elseif (is_wc_endpoint_url('edit-account')) {
                wp_enqueue_script('mam-account-details', MAM_PLUGIN_URL . 'assets/js/account-details.js', array('jquery'), MAM_VERSION, true);
            } elseif (is_wc_endpoint_url('downloads')) {
                wp_enqueue_script('mam-downloads', MAM_PLUGIN_URL . 'assets/js/downloads.js', array('jquery'), MAM_VERSION, true);
            } elseif (is_wc_endpoint_url('payment-methods') || is_wc_endpoint_url('add-payment-method')) {
                wp_enqueue_script('mam-payment-methods', MAM_PLUGIN_URL . 'assets/js/payment-methods.js', array('jquery'), MAM_VERSION, true);
            }
        }
        
        // Script principal
        wp_enqueue_script('mam-scripts');
        
        // Localizar todos los scripts con los mismos parámetros
        $mam_params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mam-nonce'),
            'i18n' => array(
                'loading' => __('Cargando...', 'my-account-manager'),
                'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'my-account-manager'),
                'success' => __('Operación completada con éxito.', 'my-account-manager'),
                'confirm_delete' => __('¿Estás seguro de que quieres eliminar este elemento?', 'my-account-manager')
            )
        );
        
        wp_localize_script('mam-scripts', 'mam_params', $mam_params);
    }

    /**
     * Agregar script de corrección para las pestañas de login/registro
     */
    public function add_login_tabs_fix() {
        // Solo cargar en la página de mi cuenta
        if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
            // Registrar y encolar el script
            wp_register_script(
                'mam-login-tabs-fix',
                MAM_PLUGIN_URL . 'assets/js/login-tabs-fix.js',
                array('jquery'),
                MAM_VERSION,
                true
            );
            
            wp_enqueue_script('mam-login-tabs-fix');
        }
    }

    /**
     * Agregar código JavaScript inline para las pestañas de login/registro
     */
    public function add_inline_login_tabs_fix() {
        // Solo cargar en la página de mi cuenta
        if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Solo inicializar si estamos en la página de login/registro
                if ($('.mam-login-register-tabs').length > 0) {
                    console.log('Inicializando manejo de pestañas login/registro');
                    
                    // Función para cambiar entre pestañas
                    function switchTab(tabType) {
                        console.log('Cambiando a pestaña:', tabType);
                        
                        // 1. Actualizar pestañas activas
                        if (tabType === 'login') {
                            $('.mam-login-tab').addClass('active');
                            $('.mam-register-tab').removeClass('active');
                        } else if (tabType === 'register') {
                            $('.mam-register-tab').addClass('active');
                            $('.mam-login-tab').removeClass('active');
                        }
                        
                        // 2. Mostrar/ocultar formularios correspondientes
                        if (tabType === 'login') {
                            $('.mam-login-form-wrapper').show();
                            $('.mam-register-form-wrapper').hide();
                        } else if (tabType === 'register') {
                            $('.mam-login-form-wrapper').hide();
                            $('.mam-register-form-wrapper').show();
                        }
                    }
                    
                    // Manejar clic en pestaña de login
                    $('.mam-login-tab').on('click', function(e) {
                        e.preventDefault();
                        switchTab('login');
                    });
                    
                    // Manejar clic en pestaña de registro
                    $('.mam-register-tab').on('click', function(e) {
                        e.preventDefault();
                        switchTab('register');
                    });
                    
                    // Establecer pestaña inicial según URL
                    var urlParams = new URLSearchParams(window.location.search);
                    var action = urlParams.get('action');
                    
                    if (action === 'register') {
                        switchTab('register');
                    } else {
                        switchTab('login');
                    }
                }
            });
            </script>
            <?php
        }
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

<?php
/**
 * Payments functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Payments Class
 */
class MAM_Payments {

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
        add_filter('woocommerce_endpoint_payment-methods_title', array($this, 'custom_payment_methods_title'));
        
        // Personalizar contenido de la página de métodos de pago
        add_action('woocommerce_before_account_payment_methods', array($this, 'before_payment_methods'));
        add_action('woocommerce_after_account_payment_methods', array($this, 'after_payment_methods'));
        
        // Personalizar tabla de métodos de pago
        add_filter('woocommerce_payment_methods_list_item', array($this, 'customize_payment_method_item'), 10, 2);
        add_filter('woocommerce_payment_methods_list_item_classes', array($this, 'add_payment_method_classes'), 10, 2);
        
        // Añadir diseño de tarjetas de crédito visuales
        add_action('woocommerce_before_account_payment_methods', array($this, 'add_visual_payment_cards'));
        
        // Añadir formulario de agregar tarjeta mejorado
        add_action('woocommerce_after_add_payment_method_form', array($this, 'enhance_add_payment_method_form'));
        
        // Añadir sección de historial de pagos
        add_action('woocommerce_after_account_payment_methods', array($this, 'add_payment_history'));
        
        // Añadir sección de facturas
        add_action('woocommerce_after_account_payment_methods', array($this, 'add_invoices_section'));
        
        // Añadir sección de suscripciones de pago (si hay plugins de suscripción activos)
        add_action('woocommerce_after_account_payment_methods', array($this, 'add_subscription_payments'));
        
        // Añadir opción para establecer método por defecto
        add_action('woocommerce_payment_methods_list_item', array($this, 'add_default_payment_option'), 10, 2);
        
        // Procesar establecer método por defecto
        add_action('template_redirect', array($this, 'process_set_default_payment_method'));
        
        // Enqueue scripts específicos para la página de métodos de pago
        add_action('wp_enqueue_scripts', array($this, 'enqueue_payment_scripts'));
        
        // Mostrar saldos y créditos (si es aplicable)
        add_action('woocommerce_before_account_payment_methods', array($this, 'show_account_balance'));
    }

    /**
     * Personalizar título de la página de métodos de pago
     */
    public function custom_payment_methods_title($title) {
        return __('Métodos de Pago', 'my-account-manager');
    }

    /**
     * Añadir contenido antes de la lista de métodos de pago
     */
    public function before_payment_methods() {
        ?>
        <div class="mam-payment-methods-header">
            <p><?php _e('Administra tus tarjetas y otros métodos de pago guardados para compras más rápidas.', 'my-account-manager'); ?></p>
        </div>
        <?php
    }

    /**
     * Añadir contenido después de la lista de métodos de pago
     */
    public function after_payment_methods() {
        ?>
        <div class="mam-payment-methods-footer">
            <div class="mam-payment-methods-info">
                <h4><?php _e('Pago Seguro', 'my-account-manager'); ?></h4>
                <p><?php _e('Tus datos de pago están cifrados y seguros. Nunca almacenamos la información completa de tu tarjeta de crédito.', 'my-account-manager'); ?></p>
                <div class="mam-security-badges">
                    <span class="mam-security-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <?php _e('Conexión SSL Segura', 'my-account-manager'); ?>
                    </span>
                    <span class="mam-security-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <?php _e('Protección contra Fraude', 'my-account-manager'); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Personalizar elementos de métodos de pago
     */
    public function customize_payment_method_item($method, $payment_token) {
        // Obtener tipo de tarjeta y últimos 4 dígitos
        $card_type = '';
        $last4 = '';
        
        if ($payment_token->get_type() === 'CC') {
            $card_type = $payment_token->get_card_type();
            $last4 = $payment_token->get_last4();
            
            // Personalizar la información mostrada
            $card_type_display = $this->get_card_type_display($card_type);
            $method['method']['last4'] = $last4;
            $method['method']['brand'] = $card_type;
            $method['method']['brand_display'] = $card_type_display;
            
            // Reemplazar el título con uno más descriptivo
            $method['method']['title'] = sprintf(
                '%s ****%s',
                $card_type_display,
                $last4
            );
            
            // Añadir fecha de expiración
            if ($payment_token->get_expiry_month() && $payment_token->get_expiry_year()) {
                $expiry = sprintf(
                    __('Expira: %s/%s', 'my-account-manager'),
                    $payment_token->get_expiry_month(),
                    substr($payment_token->get_expiry_year(), -2)
                );
                
                $method['expires'] = $expiry;
            }
        }
        
        return $method;
    }

    /**
     * Añadir clases a elementos de método de pago
     */
    public function add_payment_method_classes($classes, $payment_token) {
        if ($payment_token->get_type() === 'CC') {
            $card_type = strtolower($payment_token->get_card_type());
            $classes[] = 'mam-payment-method-' . sanitize_html_class($card_type);
        }
        
        return $classes;
    }

    /**
     * Obtener nombre de visualización para tipo de tarjeta
     */
    private function get_card_type_display($card_type) {
        $card_types = array(
            'visa' => __('Visa', 'my-account-manager'),
            'mastercard' => __('Mastercard', 'my-account-manager'),
            'amex' => __('American Express', 'my-account-manager'),
            'discover' => __('Discover', 'my-account-manager'),
            'diners' => __('Diners Club', 'my-account-manager'),
            'jcb' => __('JCB', 'my-account-manager'),
            'maestro' => __('Maestro', 'my-account-manager'),
            'unionpay' => __('UnionPay', 'my-account-manager'),
        );
        
        $card_type = strtolower($card_type);
        
        return isset($card_types[$card_type]) ? $card_types[$card_type] : $card_type;
    }

    /**
     * Añadir visualización de tarjetas de crédito mejorada
     */
    public function add_visual_payment_cards() {
        // Verificar si el usuario tiene métodos de pago guardados
        $payment_tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id());
        
        if (empty($payment_tokens)) {
            return;
        }
        
        // No mostrar si estamos en la versión de tabla de WooCommerce
        if (isset($_GET['view']) && $_GET['view'] === 'table') {
            return;
        }
        
        // Ocultar la tabla predeterminada de WooCommerce
        remove_action('woocommerce_account_payment-methods_endpoint', 'woocommerce_account_payment_methods');
        
        ?>
        <div class="mam-payment-cards">
            <?php foreach ($payment_tokens as $payment_token) : 
                if ($payment_token->get_type() !== 'CC') {
                    continue;
                }
                
                $card_type = strtolower($payment_token->get_card_type());
                $last4 = $payment_token->get_last4();
                $expiry_month = $payment_token->get_expiry_month();
                $expiry_year = substr($payment_token->get_expiry_year(), -2);
                $is_default = $payment_token->is_default();
                
                // Comprobar si la tarjeta ha expirado
                $current_month = date('n');
                $current_year = date('y');
                $is_expired = ($expiry_year < $current_year) || ($expiry_year == $current_year && $expiry_month < $current_month);
                ?>
                <div class="mam-payment-card <?php echo $is_default ? 'mam-default-card' : ''; ?> <?php echo $is_expired ? 'mam-expired-card' : ''; ?>">
                    <div class="mam-card-header">
                        <div class="mam-card-brand">
                            <?php echo $this->get_card_brand_icon($card_type); ?>
                        </div>
                        
                        <?php if ($is_default) : ?>
                            <div class="mam-default-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php _e('Predeterminada', 'my-account-manager'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_expired) : ?>
                            <div class="mam-expired-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <?php _e('Expirada', 'my-account-manager'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mam-card-number">
                        <div class="mam-card-dots">
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                        </div>
                        <div class="mam-card-dots">
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                        </div>
                        <div class="mam-card-dots">
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                            <span class="mam-card-dot"></span>
                        </div>
                        <div class="mam-card-last4"><?php echo esc_html($last4); ?></div>
                    </div>
                    
                    <div class="mam-card-footer">
                        <div class="mam-card-expiry">
                            <div class="mam-expiry-label"><?php _e('Válida hasta', 'my-account-manager'); ?></div>
                            <div class="mam-expiry-date"><?php echo sprintf('%s/%s', $expiry_month, $expiry_year); ?></div>
                        </div>
                        
                        <div class="mam-card-actions">
                            <?php if (!$is_default) : ?>
                                <form method="post" class="mam-set-default-form">
                                    <?php wp_nonce_field('mam_set_default_payment_method', 'mam_payment_nonce'); ?>
                                    <input type="hidden" name="payment_token_id" value="<?php echo esc_attr($payment_token->get_id()); ?>">
                                    <button type="submit" name="mam_set_default_payment_method" class="mam-button mam-button-small mam-button-secondary">
                                        <?php _e('Establecer como predeterminada', 'my-account-manager'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo esc_url(wc_get_account_endpoint_url('payment-methods')); ?>" class="mam-delete-payment-form">
                                <?php wp_nonce_field('delete-payment-method-' . $payment_token->get_id()); ?>
                                <input type="hidden" name="payment_token_id" value="<?php echo esc_attr($payment_token->get_id()); ?>">
                                <input type="hidden" name="delete_payment_method" value="true">
                                <button type="submit" class="mam-button mam-button-small mam-button-danger" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar esta tarjeta?', 'my-account-manager'); ?>');">
                                    <?php _e('Eliminar', 'my-account-manager'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="mam-add-card-link">
                <a href="<?php echo esc_url(wc_get_endpoint_url('add-payment-method')); ?>" class="mam-button mam-button-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php _e('Añadir Nueva Tarjeta', 'my-account-manager'); ?>
                </a>
            </div>
            
            <!-- Enlace para ver en formato tabla -->
            <div class="mam-view-as-table">
                <a href="<?php echo esc_url(add_query_arg('view', 'table', wc_get_endpoint_url('payment-methods'))); ?>">
                    <?php _e('Ver como tabla', 'my-account-manager'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener icono de marca de tarjeta
     */
    private function get_card_brand_icon($card_type) {
        $card_type = strtolower($card_type);
        
        // Iconos de tarjetas como SVG
        $icons = array(
            'visa' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.614 25.071H13.387L15.427 15.072H18.654L16.614 25.071Z" fill="#00579F"/><path d="M26.132 15.307C25.446 15.054 24.359 14.789 23.027 14.789C20.183 14.789 18.154 16.27 18.142 18.359C18.119 19.9 19.56 20.762 20.647 21.27C21.769 21.789 22.135 22.125 22.135 22.582C22.124 23.31 21.246 23.642 20.426 23.642C19.292 23.642 18.678 23.45 17.731 23.013L17.319 22.81L16.883 25.478C17.684 25.825 19.199 26.132 20.777 26.144C23.817 26.144 25.809 24.686 25.832 22.444C25.844 21.214 25.117 20.271 23.428 19.477C22.412 18.957 21.791 18.61 21.791 18.094C21.791 17.626 22.354 17.152 23.639 17.152C24.7 17.14 25.484 17.393 26.097 17.651L26.386 17.786L26.822 15.199L26.132 15.307Z" fill="#00579F"/><path d="M30.186 15.071C29.539 15.071 29.058 15.254 28.753 15.9L24.462 25.071H27.502L28.073 23.594H31.627L31.962 25.071H34.615L32.4 15.071H30.186ZM28.845 21.424C29.071 20.852 29.918 18.634 29.918 18.634C29.907 18.657 30.118 18.094 30.243 17.748L30.407 18.565C30.407 18.565 30.902 20.91 31.015 21.424H28.845Z" fill="#00579F"/><path d="M11.612 15.072L8.776 21.693L8.508 20.432C8.029 18.9 6.639 17.241 5.07 16.346L7.685 25.06H10.748L15.404 15.072H11.612Z" fill="#00579F"/><path d="M6.816 15.071H2.061L2.027 15.307C5.675 16.136 8.083 18.088 9.075 20.433L8.19 15.913C8.029 15.207 7.483 15.083 6.816 15.071Z" fill="#F9A51A"/></svg>',
            'mastercard' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.04 10.869H25.96V29.131H14.04V10.869Z" fill="#FF5F00"/><path d="M14.784 20C14.784 16.304 16.608 13.043 19.372 10.869C17.324 9.221 14.724 8.261 11.914 8.261C5.503 8.261 0.304 13.478 0.304 20C0.304 26.522 5.503 31.739 11.914 31.739C14.724 31.739 17.324 30.779 19.372 29.131C16.608 26.957 14.784 23.696 14.784 20Z" fill="#EB001B"/><path d="M39.696 20C39.696 26.522 34.498 31.739 28.087 31.739C25.277 31.739 22.676 30.779 20.628 29.131C23.392 26.957 25.216 23.696 25.216 20C25.216 16.304 23.392 13.043 20.628 10.869C22.676 9.221 25.277 8.261 28.087 8.261C34.498 8.261 39.696 13.478 39.696 20Z" fill="#F79E1B"/></svg>',
            'amex' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M35.333 28C35.333 29.841 33.841 31.333 32 31.333H8C6.159 31.333 4.667 29.841 4.667 28V12C4.667 10.159 6.159 8.667 8 8.667H32C33.841 8.667 35.333 10.159 35.333 12V28Z" fill="#006FCF"/><path d="M16.167 15.333L13.667 20.667L11.167 15.333H8V21.834L5.167 15.333H2.334L0 23H2.5L3 21.5H5.834L6.334 23H9.667V17.5L12.5 23H14.667L17.5 17.5V23H19.834V15.333H16.167ZM4.5 19.5L5.334 17L6.167 19.5H4.5Z" fill="white"/><path d="M21.167 15.333L19.167 17.333L17.167 15.333H10.5L9.5 17.333L8.5 15.333H3.167L0 23H4.833L5.5 21.333H7.5L8.167 23H14.5V20.333L16 19L17.5 20.333V23H30.167L32.167 21L34 23H37.167L32.5 18.167L37.167 15.333H34L32.167 17.333L30.167 15.333H21.167ZM28.667 17H32.834L29.667 19.167L32.834 21.333H28.667V19.834H25.834V21.333H24.167V19.834H21.334V21.333H19.667V17H21.334V18.667H24.167V17H25.834V18.667H28.667V17Z" fill="white"/></svg>',
            'discover' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 24.5C4 25.875 5.125 27 6.5 27H33.5C34.875 27 36 25.875 36 24.5V15.5C36 14.125 34.875 13 33.5 13H6.5C5.125 13 4 14.125 4 15.5V24.5Z" fill="#4D4D4D"/><path d="M33.5 28H6.5C4.567 28 3 26.433 3 24.5V15.5C3 13.567 4.567 12 6.5 12H33.5C35.433 12 37 13.567 37 15.5V24.5C37 26.433 35.433 28 33.5 28ZM6.5 14C5.673 14 5 14.673 5 15.5V24.5C5 25.327 5.673 26 6.5 26H33.5C34.327 26 35 25.327 35 24.5V15.5C35 14.673 34.327 14 33.5 14H6.5Z" fill="#F47216"/><path d="M9 20C9 21.657 10.343 23 12 23C13.657 23 15 21.657 15 20C15 18.343 13.657 17 12 17C10.343 17 9 18.343 9 20Z" fill="#F47216"/><path d="M35 19.5C35 16.462 32.538 14 29.5 14C28.075 14 26.777 14.559 25.827 15.473C24.879 16.388 24.306 17.656 24.3 18.999C24.3 19.166 24.306 19.332 24.319 19.499C24.429 20.792 25.075 21.989 26.102 22.846C27.13 23.704 28.48 24.112 29.839 23.959C31.197 23.806 32.426 23.106 33.236 22.032C34.045 20.958 34.367 19.61 34.127 18.296L24.826 18.292C24.811 18.511 24.811 18.73 24.826 18.949L29.966 18.949C29.942 19.241 29.842 19.522 29.676 19.763C29.511 20.004 29.285 20.196 29.022 20.321C28.758 20.446 28.466 20.499 28.176 20.474C27.885 20.449 27.608 20.347 27.37 20.179C27.131 20.011 26.94 19.782 26.816 19.517C26.693 19.252 26.642 18.96 26.667 18.669C26.693 18.379 26.794 18.101 26.961 17.863C27.129 17.625 27.356 17.433 27.619 17.309C27.882 17.184 28.173 17.132 28.462 17.156C28.751 17.179 29.029 17.279 29.267 17.445L33.041 14.951C31.828 13.375 29.898 12.598 27.955 12.892C26.011 13.185 24.351 14.501 23.564 16.312C22.778 18.123 22.98 20.182 24.088 21.804C25.197 23.427 27.052 24.361 29 24.249C30.948 24.138 32.693 22.998 33.612 21.256C34.53 19.515 34.487 17.445 33.507 15.742H34.994C35.004 16.994 34.533 18.203 33.68 19.126C32.827 20.048 31.654 20.615 30.396 20.715C29.139 20.814 27.889 20.441 26.893 19.668C25.897 18.896 25.225 17.778 25.01 16.547C24.796 15.316 25.052 14.057 25.731 13.005C26.41 11.954 27.462 11.181 28.677 10.839C29.892 10.496 31.19 10.608 32.325 11.16C33.461 11.711 34.356 12.659 34.836 13.832L33.287 14.492C32.962 13.719 32.368 13.08 31.607 12.679C30.847 12.277 29.968 12.14 29.119 12.29C28.271 12.44 27.504 12.867 26.952 13.498C26.399 14.131 26.094 14.927 26.087 15.75H35V19.5Z" fill="#F47216"/></svg>',
            'diners' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M31.739 28.043C31.739 29.669 30.408 31 28.782 31H11.217C9.591 31 8.26 29.669 8.26 28.043V11.956C8.26 10.331 9.591 9 11.217 9H28.782C30.408 9 31.739 10.331 31.739 11.956V28.043Z" fill="#0079BE"/><path d="M19.5 28C24.194 28 28 24.194 28 19.5C28 14.806 24.194 11 19.5 11C14.806 11 11 14.806 11 19.5C11 24.194 14.806 28 19.5 28Z" fill="#0079BE"/><path d="M17 24.444V14.556C14.661 15.356 13 17.256 13 19.5C13 21.744 14.661 23.644 17 24.444ZM22 24.444C24.339 23.644 26 21.744 26 19.5C26 17.256 24.339 15.356 22 14.556V24.444Z" fill="white"/></svg>',
            'jcb' => '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M30.805 22.879V15.844C30.805 13.573 28.939 11.707 26.668 11.707H9V26.284C9 28.555 10.866 30.421 13.137 30.421H30.805V24.297C30.805 24.297 30.382 24.351 29.908 24.351C28.378 24.351 26.668 23.736 26.668 21.62C26.668 19.504 28.378 18.889 29.908 18.889C30.392 18.889 30.805 22.879 30.805 22.879Z" fill="white"/><path d="M13.965 16.282H16.866V22.619H13.965V16.282Z" fill="#006FCF"/><path d="M15.416 20.765C15.416 21.967 16.386 22.627 17.5 22.627C18.613 22.627 19.584 21.967 19.584 20.765C19.584 19.563 18.613 18.903 17.5 18.903C16.386 18.903 15.416 19.563 15.416 20.765Z" fill="#006FCF"/><path d="M19.595 15.414H22.496V21.751H19.595V15.414Z" fill="#62C654"/><path d="M21.045 19.897C21.045 21.099 22.016 21.759 23.129 21.759C24.243 21.759 25.213 21.099 25.213 19.897C25.213 18.695 24.243 18.035 23.129 18.035C22.016 18.035 21.045 18.695 21.045 19.897Z" fill="#62C654"/><path d="M25.221 25.708H22.32V19.371H25.221V25.708Z" fill="#AA3163"/><path d="M23.771 21.225C23.771 20.023 22.8 19.363 21.686 19.363C20.573 19.363 19.602 20.023 19.602 21.225C19.602 22.427 20.573 23.087 21.686 23.087C22.8 23.087 23.771 22.427 23.771 21.225Z" fill="#AA3163"/><path d="M30.805 22.879V15.844C30.805 13.573 28.939 11.707 26.668 11.707H13.965V14.879H26.668C27.235 14.879 27.689 15.343 27.689 15.909V18.879H26.668C24.897 18.879 23.551 19.518 23.551 21.615C23.551 23.711 24.897 24.351 26.668 24.351C27.142 24.351 27.565 24.297 27.565 24.297V26.284C27.565 26.85 27.111 27.314 26.544 27.314H13.965V30.421H26.668C28.939 30.421 30.805 28.555 30.805 26.284V24.297C30.805 24.297 30.382 24.351 29.908 24.351C28.378 24.351 26.668 23.736 26.668 21.62C26.668 19.504 28.378 18.889 29.908 18.889C30.392 18.889 30.805 22.879 30.805 22.879Z" fill="#006FCF"/></svg>'
        );
        
        return isset($icons[$card_type]) ? $icons[$card_type] : esc_html(ucfirst($card_type));
    }

    /**
     * Mejorar formulario de agregar método de pago
     */
    public function enhance_add_payment_method_form() {
        ?>
        <div class="mam-card-form-help">
            <h4><?php _e('Consejos para añadir tu tarjeta', 'my-account-manager'); ?></h4>
            <ul class="mam-card-tips">
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php _e('Asegúrate de que los datos introducidos coincidan con tu tarjeta.', 'my-account-manager'); ?>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php _e('El CVV es el código de seguridad de 3 o 4 dígitos en el reverso de tu tarjeta.', 'my-account-manager'); ?>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php _e('Aceptamos Visa, Mastercard, American Express y otras tarjetas principales.', 'my-account-manager'); ?>
                </li>
            </ul>
            
            <div class="mam-card-brands">
                <?php
                $brands = array('visa', 'mastercard', 'amex', 'discover');
                foreach ($brands as $brand) {
                    echo $this->get_card_brand_icon($brand);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir sección de historial de pagos
     */
    public function add_payment_history() {
        // Verificar si el usuario tiene pedidos
        $customer_orders = wc_get_orders(array(
            'customer_id' => get_current_user_id(),
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if (empty($customer_orders)) {
            return;
        }
        
        ?>
        <div class="mam-payment-history">
            <h3><?php _e('Historial de Pagos Recientes', 'my-account-manager'); ?></h3>
            
            <table class="mam-payment-history-table">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'my-account-manager'); ?></th>
                        <th><?php _e('Pedido', 'my-account-manager'); ?></th>
                        <th><?php _e('Método de Pago', 'my-account-manager'); ?></th>
                        <th><?php _e('Importe', 'my-account-manager'); ?></th>
                        <th><?php _e('Estado', 'my-account-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_orders as $order) : 
                        $payment_method = $order->get_payment_method_title();
                        $order_date = wc_format_datetime($order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created());
                        $order_total = $order->get_formatted_order_total();
                        $order_status = wc_get_order_status_name($order->get_status());
                        
                        $status_class = '';
                        if ($order->has_status('completed') || $order->has_status('processing')) {
                            $status_class = 'mam-status-success';
                        } elseif ($order->has_status('refunded')) {
                            $status_class = 'mam-status-refunded';
                        } elseif ($order->has_status('failed')) {
                            $status_class = 'mam-status-failed';
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($order_date); ?></td>
                            <td>
                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                    #<?php echo esc_html($order->get_order_number()); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($payment_method); ?></td>
                            <td><?php echo $order_total; ?></td>
                            <td><span class="mam-payment-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($order_status); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mam-view-all-orders">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="mam-button mam-button-secondary">
                    <?php _e('Ver Todos los Pedidos', 'my-account-manager'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir sección de facturas
     */
    public function add_invoices_section() {
        // Verificar si el usuario tiene pedidos completados
        $customer_orders = wc_get_orders(array(
            'customer_id' => get_current_user_id(),
            'status' => array('completed'),
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if (empty($customer_orders)) {
            return;
        }
        
        // Verificar si hay algún plugin de facturas activo
        $has_invoice_plugin = false;
        
        // Comprobar plugins conocidos de facturas
        if (class_exists('WooCommerce_PDF_Invoices') || 
            class_exists('WC_Germanized') || 
            class_exists('WC_PDF_Invoices') || 
            class_exists('WPO_WCPDF')) {
            $has_invoice_plugin = true;
        }
        
        ?>
        <div class="mam-invoices">
            <h3><?php _e('Mis Facturas', 'my-account-manager'); ?></h3>
            
            <?php if ($has_invoice_plugin) : ?>
                <table class="mam-invoices-table">
                    <thead>
                        <tr>
                            <th><?php _e('Factura', 'my-account-manager'); ?></th>
                            <th><?php _e('Fecha', 'my-account-manager'); ?></th>
                            <th><?php _e('Pedido', 'my-account-manager'); ?></th>
                            <th><?php _e('Importe', 'my-account-manager'); ?></th>
                            <th><?php _e('Acciones', 'my-account-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_orders as $order) : 
                            $order_date = wc_format_datetime($order->get_date_completed());
                            $order_total = $order->get_formatted_order_total();
                            $invoice_number = $order->get_order_number();
                            
                            // Intentar obtener número de factura desde diferentes plugins
                            if (function_exists('wc_gzd_get_order_invoice_number') && wc_gzd_get_order_invoice_number($order)) {
                                $invoice_number = wc_gzd_get_order_invoice_number($order);
                            } elseif (method_exists($order, 'get_meta') && $order->get_meta('_wcpdf_invoice_number')) {
                                $invoice_number = $order->get_meta('_wcpdf_invoice_number');
                            }
                            
                            // Generar enlace a la factura
                            $invoice_url = '';
                            if (class_exists('WPO_WCPDF') && function_exists('wcpdf_get_document')) {
                                $invoice_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'action' => 'generate_wpo_wcpdf',
                                            'document_type' => 'invoice',
                                            'order_ids' => $order->get_id(),
                                            'my-account' => true,
                                        ),
                                        wc_get_endpoint_url('order-received', $order->get_id(), wc_get_page_permalink('checkout'))
                                    ),
                                    'generate_wpo_wcpdf'
                                );
                            }
                        ?>
                            <tr>
                                <td><?php echo esc_html(sprintf(__('Factura #%s', 'my-account-manager'), $invoice_number)); ?></td>
                                <td><?php echo esc_html($order_date); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td><?php echo $order_total; ?></td>
                                <td>
                                    <?php if ($invoice_url) : ?>
                                        <a href="<?php echo esc_url($invoice_url); ?>" class="mam-button mam-button-small mam-button-primary" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <?php _e('Descargar PDF', 'my-account-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="mam-button mam-button-small mam-button-secondary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <?php _e('Ver Detalles', 'my-account-manager'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="mam-no-invoices">
                    <?php _e('Las facturas no están disponibles en este momento. Contacta con nosotros si necesitas una factura para tus pedidos.', 'my-account-manager'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Añadir sección de suscripciones de pago
     */
    public function add_subscription_payments() {
        // Verificar si WooCommerce Subscriptions está activo
        if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) {
            return;
        }
        
        // Obtener suscripciones del usuario
        $subscriptions = wcs_get_users_subscriptions(get_current_user_id());
        
        if (empty($subscriptions)) {
            return;
        }
        
        ?>
        <div class="mam-subscriptions">
            <h3><?php _e('Mis Suscripciones', 'my-account-manager'); ?></h3>
            
            <div class="mam-subscriptions-list">
                <?php foreach ($subscriptions as $subscription) : 
                    $subscription_id = $subscription->get_id();
                    $status = $subscription->get_status();
                    $next_payment = $subscription->get_date('next_payment');
                    $end_date = $subscription->get_date('end');
                    $payment_method = $subscription->get_payment_method_title();
                    $items = $subscription->get_items();
                    $total = $subscription->get_formatted_order_total();
                    
                    // Determinar clase de estado
                    $status_class = 'mam-status-' . $status;
                    ?>
                    <div class="mam-subscription-item">
                        <div class="mam-subscription-header">
                            <div class="mam-subscription-id">
                                <a href="<?php echo esc_url($subscription->get_view_order_url()); ?>">
                                    <?php echo sprintf(__('Suscripción #%s', 'my-account-manager'), $subscription_id); ?>
                                </a>
                            </div>
                            <div class="mam-subscription-status">
                                <span class="mam-subscription-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo wcs_get_subscription_status_name($status); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mam-subscription-details">
                            <div class="mam-subscription-products">
                                <?php 
                                if (!empty($items)) {
                                    $item = array_values($items)[0];
                                    echo esc_html($item->get_name());
                                    
                                    if (count($items) > 1) {
                                        echo ' ' . sprintf(__('y %d productos más', 'my-account-manager'), count($items) - 1);
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="mam-subscription-price">
                                <?php echo $total; ?> / 
                                <?php echo esc_html(wcs_get_subscription_period_string(
                                    $subscription->get_billing_period(),
                                    $subscription->get_billing_interval()
                                )); ?>
                            </div>
                            
                            <?php if ($next_payment) : ?>
                                <div class="mam-subscription-next-payment">
                                    <strong><?php _e('Próximo pago:', 'my-account-manager'); ?></strong>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($next_payment))); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mam-subscription-payment-method">
                                <strong><?php _e('Método de pago:', 'my-account-manager'); ?></strong>
                                <?php echo esc_html($payment_method); ?>
                            </div>
                        </div>
                        
                        <div class="mam-subscription-actions">
                            <a href="<?php echo esc_url($subscription->get_view_order_url()); ?>" class="mam-button mam-button-secondary">
                                <?php _e('Ver Detalles', 'my-account-manager'); ?>
                            </a>
                            
                            <?php if ($subscription->can_be_updated_to_new_payment_method()) : ?>
                                <a href="<?php echo esc_url($subscription->get_change_payment_method_url()); ?>" class="mam-button mam-button-secondary">
                                    <?php _e('Cambiar Método de Pago', 'my-account-manager'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($subscription->can_be_cancelled()) : ?>
                                <a href="<?php echo esc_url($subscription->get_cancel_url()); ?>" class="mam-button mam-button-danger">
                                    <?php _e('Cancelar', 'my-account-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir opción para establecer método por defecto
     */
    public function add_default_payment_option($method, $payment_token) {
        if (!$payment_token->is_default()) {
            $set_default_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'set_default_payment_method' => $payment_token->get_id(),
                    ),
                    wc_get_account_endpoint_url('payment-methods')
                ),
                'mam_set_default_payment_method'
            );
            
            $method['actions']['default'] = array(
                'url' => $set_default_url,
                'name' => __('Establecer como predeterminado', 'my-account-manager'),
                'class' => array('mam-button', 'mam-button-secondary', 'mam-set-default-button')
            );
        }
        
        return $method;
    }

    /**
     * Procesar establecer método por defecto
     */
    public function process_set_default_payment_method() {
        if (!is_account_page() || !is_user_logged_in() || !isset($_POST['mam_set_default_payment_method'])) {
            return;
        }
        
        if (!isset($_POST['mam_payment_nonce']) || !wp_verify_nonce($_POST['mam_payment_nonce'], 'mam_set_default_payment_method')) {
            wc_add_notice(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'my-account-manager'), 'error');
            return;
        }
        
        $token_id = isset($_POST['payment_token_id']) ? absint($_POST['payment_token_id']) : 0;
        
        if ($token_id > 0) {
            $token = WC_Payment_Tokens::get($token_id);
            
            if ($token && $token->get_user_id() === get_current_user_id()) {
                WC_Payment_Tokens::set_users_default($token->get_user_id(), $token_id);
                wc_add_notice(__('Método de pago establecido como predeterminado.', 'my-account-manager'), 'success');
            }
        }
        
        wp_redirect(wc_get_account_endpoint_url('payment-methods'));
        exit;
    }

    /**
     * Enqueue scripts específicos para la página de métodos de pago
     */
    public function enqueue_payment_scripts() {
        if (is_account_page() && (is_wc_endpoint_url('payment-methods') || is_wc_endpoint_url('add-payment-method'))) {
            wp_enqueue_script('mam-payment-methods', MAM_PLUGIN_URL . 'assets/js/payment-methods.js', array('jquery'), MAM_VERSION, true);
        }
    }

    /**
     * Mostrar saldos y créditos
     */
    public function show_account_balance() {
        // Verificar si hay algún plugin de crédito o saldo activo
        $has_credit_system = class_exists('WC_Gateway_Account_Funds') || 
                             class_exists('WC_Account_Funds') || 
                             class_exists('Points_Rewards_For_WooCommerce');
        
        if (!$has_credit_system) {
            return;
        }
        
        $user_id = get_current_user_id();
        $credit_amount = 0;
        
        // Intentar obtener el saldo desde diferentes plugins
        if (class_exists('WC_Account_Funds') && function_exists('wc_account_funds_get_account_funds')) {
            $credit_amount = wc_account_funds_get_account_funds($user_id);
        } elseif (function_exists('WC_Points_Rewards')) {
            $points_balance = WC_Points_Rewards_Manager::get_users_points($user_id);
            $points_value = WC_Points_Rewards_Manager::get_users_points_value($user_id);
            
            if ($points_value > 0) {
                $credit_amount = $points_value;
            }
        }
        
        if ($credit_amount <= 0) {
            return;
        }
        
        ?>
        <div class="mam-account-balance">
            <div class="mam-balance-card">
                <div class="mam-balance-header">
                    <h3><?php _e('Saldo Disponible', 'my-account-manager'); ?></h3>
                    <div class="mam-balance-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
                <div class="mam-balance-amount">
                    <?php echo wc_price($credit_amount); ?>
                </div>
                
                <div class="mam-balance-info">
                    <p><?php _e('Puedes utilizar este saldo en tus próximas compras.', 'my-account-manager'); ?></p>
                </div>
                
                <?php if (class_exists('WC_Account_Funds') && function_exists('wc_account_funds_get_account_funds_url')) : ?>
                    <div class="mam-balance-actions">
                        <a href="<?php echo esc_url(wc_account_funds_get_account_funds_url()); ?>" class="mam-button mam-button-secondary">
                            <?php _e('Gestionar Saldo', 'my-account-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

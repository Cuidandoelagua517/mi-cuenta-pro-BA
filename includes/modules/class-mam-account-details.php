<?php
/**
 * Account Details functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Account_Details Class
 */
class MAM_Account_Details {

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
        // Personalizar título de la página
        add_filter('woocommerce_endpoint_edit-account_title', array($this, 'custom_account_title'));
        
        // Personalizar contenido del formulario de datos de cuenta
        add_action('woocommerce_before_edit_account_form', array($this, 'before_account_form'));
        add_action('woocommerce_after_edit_account_form', array($this, 'after_account_form'));
        
        // Añadir campos personalizados al formulario de datos de cuenta
        add_filter('woocommerce_save_account_details_required_fields', array($this, 'customize_required_fields'));
        add_action('woocommerce_edit_account_form', array($this, 'add_custom_account_fields'));
        add_action('woocommerce_save_account_details', array($this, 'save_custom_account_fields'));
        
        // Validación personalizada para los campos de cuenta
        add_action('woocommerce_save_account_details_errors', array($this, 'validate_account_fields'), 10, 2);
        
        // Añadir sugerencias de seguridad para contraseñas
        add_action('woocommerce_edit_account_form_start', array($this, 'add_password_meter'));
        
        // Añadir secciones adicionales para preferencias del usuario
        add_action('woocommerce_edit_account_form_end', array($this, 'add_user_preferences'));
        
        // Añadir opciones de privacidad
        add_action('woocommerce_edit_account_form_end', array($this, 'add_privacy_options'));
        
        // Añadir sección de sesiones activas
        add_action('woocommerce_edit_account_form_end', array($this, 'add_active_sessions'));
        
        // Añadir sección de eliminación de cuenta
        add_action('woocommerce_edit_account_form_end', array($this, 'add_delete_account_option'));
        
        // Procesar solicitud de eliminación de cuenta
        add_action('template_redirect', array($this, 'process_account_deletion'));
        
        // Enqueue scripts específicos para la página de datos de cuenta
        add_action('wp_enqueue_scripts', array($this, 'enqueue_account_scripts'));
        
        // Añadir pestañas para organizar la información de la cuenta
        add_action('woocommerce_before_edit_account_form', array($this, 'add_account_tabs'));
    }
public function register_ajax_handlers() {
    add_action('wp_ajax_mam_update_account', array($this, 'ajax_update_account'));
    add_action('wp_ajax_mam_update_password', array($this, 'ajax_update_password'));
    add_action('wp_ajax_mam_update_preferences', array($this, 'ajax_update_preferences'));
    add_action('wp_ajax_mam_revoke_session', array($this, 'ajax_revoke_session'));
}

public function ajax_update_account() {
    check_ajax_referer('mam-nonce', 'security');
    
    $user_id = get_current_user_id();
    
    // Validar campos
    $account_first_name = isset($_POST['account_first_name']) ? sanitize_text_field($_POST['account_first_name']) : '';
    $account_last_name = isset($_POST['account_last_name']) ? sanitize_text_field($_POST['account_last_name']) : '';
    $account_email = isset($_POST['account_email']) ? sanitize_email($_POST['account_email']) : '';
    
    // Validar email
    if (empty($account_email)) {
        wp_send_json_error(array(
            'message' => __('Por favor, introduce una dirección de correo electrónico.', 'my-account-manager')
        ));
        return;
    }
    
    if (!is_email($account_email)) {
        wp_send_json_error(array(
            'message' => __('Por favor, introduce una dirección de correo electrónico válida.', 'my-account-manager')
        ));
        return;
    }
    
    // Comprobar si el email ya está en uso
    if (email_exists($account_email) && email_exists($account_email) !== $user_id) {
        wp_send_json_error(array(
            'message' => __('Esta dirección de correo electrónico ya está siendo utilizada.', 'my-account-manager')
        ));
        return;
    }
    
    // Actualizar datos de usuario
    $user_data = array(
        'ID'         => $user_id,
        'first_name' => $account_first_name,
        'last_name'  => $account_last_name,
        'user_email' => $account_email,
        'display_name' => $account_first_name
    );
    
    $user_id = wp_update_user($user_data);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array(
            'message' => $user_id->get_error_message()
        ));
        return;
    }
    
    // Guardar campos personalizados
    if (isset($_POST['account_phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['account_phone']));
    }
    
    if (isset($_POST['account_birth_date'])) {
        update_user_meta($user_id, 'birth_date', sanitize_text_field($_POST['account_birth_date']));
    }
    // Guardar campos personalizados adicionales
if (isset($_POST['account_cuit'])) {
    update_user_meta($user_id, 'cuit', sanitize_text_field($_POST['account_cuit']));
    update_user_meta($user_id, 'billing_cuit', sanitize_text_field($_POST['account_cuit']));
}

if (isset($_POST['account_company_name'])) {
    update_user_meta($user_id, 'company_name', sanitize_text_field($_POST['account_company_name']));
    update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['account_company_name']));
}
    wp_send_json_success(array(
        'message' => __('Datos de cuenta actualizados correctamente.', 'my-account-manager')
    ));
}
    /**
     * Personalizar título de la página de datos de cuenta
     */
    public function custom_account_title($title) {
        return __('Datos de Cuenta', 'my-account-manager');
    }

    /**
     * Añadir contenido antes del formulario de datos de cuenta
     */
    public function before_account_form() {
        $current_user = wp_get_current_user();
        ?>
        <div class="mam-account-details-header">
            <div class="mam-account-avatar">
                <?php echo get_avatar($current_user->ID, 96, '', '', array('class' => 'mam-avatar')); ?>
            </div>
            <div class="mam-account-summary">
                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                <p><?php _e('Gestiona tus datos personales y configura tus preferencias.', 'my-account-manager'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir contenido después del formulario de datos de cuenta
     */
    public function after_account_form() {
        ?>
        <div class="mam-account-details-footer">
            <div class="mam-account-details-help">
                <p>
                    <?php _e('Mantén tus datos actualizados para garantizar una experiencia óptima con nuestra tienda.', 'my-account-manager'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Personalizar campos requeridos
     */
    public function customize_required_fields($fields) {
        // Añadir/quitar campos requeridos según sea necesario
        return $fields;
    }

/**
 * Añadir campos personalizados al formulario de datos de cuenta
 */
public function add_custom_account_fields() {
    $user_id = get_current_user_id();
    
    // Obtener valores actuales con prioridad
    $phone = get_user_meta($user_id, 'phone', true);
    $birth_date = get_user_meta($user_id, 'birth_date', true);
    
    // IMPORTANTE: Buscar CUIT en múltiples ubicaciones
    $cuit = get_user_meta($user_id, 'cuit', true);
    if (empty($cuit)) {
        $cuit = get_user_meta($user_id, 'billing_cuit', true);
    }
    
    // IMPORTANTE: Buscar empresa en múltiples ubicaciones
    $company = get_user_meta($user_id, 'company_name', true);
    if (empty($company)) {
        $company = get_user_meta($user_id, 'billing_company', true);
    }
    
    ?>
    <!-- Añadir explícitamente campos de empresa y CUIT -->
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_company_name"><?php esc_html_e('Nombre de Empresa', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_company_name" id="account_company_name" value="<?php echo esc_attr($company); ?>" />
        <span class="description"><?php _e('Empresa asociada a tu cuenta.', 'my-account-manager'); ?></span>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_cuit"><?php esc_html_e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_cuit" id="account_cuit" value="<?php echo esc_attr($cuit); ?>" required />
        <span class="description"><?php _e('CUIT asociado a tu empresa.', 'my-account-manager'); ?></span>
    </p>
    
    <!-- Campos existentes -->
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_phone"><?php _e('Teléfono', 'my-account-manager'); ?></label>
        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="account_phone" id="account_phone" value="<?php echo esc_attr($phone); ?>" />
        <span class="description"><?php _e('Utilizado para contactarte en caso de problemas con tu pedido.', 'my-account-manager'); ?></span>
    </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_birth_date"><?php _e('Fecha de nacimiento', 'my-account-manager'); ?></label>
            <input type="date" class="woocommerce-Input woocommerce-Input--date input-text" name="account_birth_date" id="account_birth_date" value="<?php echo esc_attr($birth_date); ?>" />
            <span class="description"><?php _e('Utilizada para enviarte promociones especiales en tu cumpleaños.', 'my-account-manager'); ?></span>
        </p>
        <?php
    }

    /**
     * Guardar campos personalizados
     */
    public function save_custom_account_fields($user_id) {
        // Guardar número de teléfono
        if (isset($_POST['account_phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($_POST['account_phone']));
        }
        
        // Guardar fecha de nacimiento
        if (isset($_POST['account_birth_date'])) {
            update_user_meta($user_id, 'birth_date', sanitize_text_field($_POST['account_birth_date']));
        }
        
        // Guardar preferencias de comunicación
        if (isset($_POST['communication_preferences'])) {
            $preferences = array_map('sanitize_text_field', $_POST['communication_preferences']);
            update_user_meta($user_id, 'communication_preferences', $preferences);
        } else {
            update_user_meta($user_id, 'communication_preferences', array());
        }
        
        // Guardar preferencias de privacidad
        if (isset($_POST['privacy_options'])) {
            $privacy = array_map('sanitize_text_field', $_POST['privacy_options']);
            update_user_meta($user_id, 'privacy_options', $privacy);
        } else {
            update_user_meta($user_id, 'privacy_options', array());
        }
        // Guardar CUIT
if (isset($_POST['account_cuit'])) {
    update_user_meta($user_id, 'cuit', sanitize_text_field($_POST['account_cuit']));
    // También guardarlo como meta de facturación para WooCommerce
    update_user_meta($user_id, 'billing_cuit', sanitize_text_field($_POST['account_cuit']));
}

// Guardar Nombre de Empresa
if (isset($_POST['account_company_name'])) {
    update_user_meta($user_id, 'company_name', sanitize_text_field($_POST['account_company_name']));
    // También guardarlo como meta de facturación para WooCommerce
    update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['account_company_name']));
}
    }

    /**
     * Validación personalizada para los campos de cuenta
     */
    
    public function validate_account_fields($errors, $user) {
        // Validar CUIT
if (!empty($_POST['account_cuit']) && !$this->validate_cuit_format($_POST['account_cuit'])) {
    $errors->add('cuit_error', __('Por favor, introduce un CUIT válido.', 'my-account-manager'));
}

// Validar Empresa
if (!empty($_POST['account_company_name']) && strlen($_POST['account_company_name']) < 2) {
    $errors->add('company_error', __('El nombre de empresa debe tener al menos 2 caracteres.', 'my-account-manager'));
}
        // Validar teléfono
        if (!empty($_POST['account_phone']) && !preg_match('/^[0-9+\s()-]{6,20}$/', $_POST['account_phone'])) {
            $errors->add('phone_error', __('Por favor, introduce un número de teléfono válido.', 'my-account-manager'));
        }
        
        // Validar fecha de nacimiento
        if (!empty($_POST['account_birth_date'])) {
            $birth_date = strtotime($_POST['account_birth_date']);
            $min_age = strtotime('-16 years'); // Edad mínima 16 años
            
            if ($birth_date > $min_age) {
                $errors->add('birth_date_error', __('Debes tener al menos 16 años para registrarte.', 'my-account-manager'));
            }
        }
    }
/**
 * Validar formato de CUIT
 */
private function validate_cuit_format($cuit) {
    // Eliminar guiones y espacios
    $cuit = preg_replace('/[^0-9]/', '', $cuit);
    
    // Verificar longitud
    if (strlen($cuit) !== 11) {
        return false;
    }
    
    // Aquí podrías añadir validación adicional del número de CUIT
    // como verificación del dígito de control
    
    return true;
}
    /**
     * Añadir medidor de seguridad para contraseñas
     */
    public function add_password_meter() {
        ?>
        <div class="mam-password-strength-meter">
            <h3><?php _e('Seguridad de la Contraseña', 'my-account-manager'); ?></h3>
            <div class="mam-password-meter-container">
                <div class="mam-password-meter" id="mam-password-meter">
                    <div class="mam-password-meter-bar" id="mam-password-meter-bar"></div>
                </div>
                <div class="mam-password-strength" id="mam-password-strength"></div>
            </div>
            
            <div class="mam-password-tips">
                <h4><?php _e('Recomendaciones de seguridad:', 'my-account-manager'); ?></h4>
                <ul>
                    <li><?php _e('Usa al menos 8 caracteres', 'my-account-manager'); ?></li>
                    <li><?php _e('Incluye mayúsculas y minúsculas', 'my-account-manager'); ?></li>
                    <li><?php _e('Añade números y símbolos (como !@#$%)', 'my-account-manager'); ?></li>
                    <li><?php _e('Evita información personal o común', 'my-account-manager'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir sección para preferencias del usuario
     */
    public function add_user_preferences() {
        $user_id = get_current_user_id();
        $saved_preferences = get_user_meta($user_id, 'communication_preferences', true);
        
        if (!is_array($saved_preferences)) {
            $saved_preferences = array();
        }
        
        ?>
        <div class="mam-user-preferences">
            <h3><?php _e('Preferencias de Comunicación', 'my-account-manager'); ?></h3>
            <p><?php _e('Selecciona cómo prefieres que nos comuniquemos contigo:', 'my-account-manager'); ?></p>
            
            <div class="mam-preferences-options">
                <label class="mam-checkbox">
                    <input type="checkbox" name="communication_preferences[]" value="email_marketing" <?php checked(in_array('email_marketing', $saved_preferences)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Recibir promociones y ofertas por email', 'my-account-manager'); ?></span>
                </label>
                
                <label class="mam-checkbox">
                    <input type="checkbox" name="communication_preferences[]" value="order_updates" <?php checked(in_array('order_updates', $saved_preferences)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Recibir actualizaciones de pedidos por email', 'my-account-manager'); ?></span>
                </label>
                
                <label class="mam-checkbox">
                    <input type="checkbox" name="communication_preferences[]" value="sms_marketing" <?php checked(in_array('sms_marketing', $saved_preferences)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Recibir promociones por SMS', 'my-account-manager'); ?></span>
                </label>
                
                <label class="mam-checkbox">
                    <input type="checkbox" name="communication_preferences[]" value="newsletter" <?php checked(in_array('newsletter', $saved_preferences)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Suscribirme al boletín mensual', 'my-account-manager'); ?></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir opciones de privacidad
     */
    public function add_privacy_options() {
        $user_id = get_current_user_id();
        $privacy_options = get_user_meta($user_id, 'privacy_options', true);
        
        if (!is_array($privacy_options)) {
            $privacy_options = array();
        }
        
        ?>
        <div class="mam-privacy-options">
            <h3><?php _e('Opciones de Privacidad', 'my-account-manager'); ?></h3>
            <p><?php _e('Configura tus preferencias de privacidad:', 'my-account-manager'); ?></p>
            
            <div class="mam-privacy-settings">
                <label class="mam-checkbox">
                    <input type="checkbox" name="privacy_options[]" value="share_purchase_history" <?php checked(in_array('share_purchase_history', $privacy_options)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Permitir el uso de mi historial de compras para recomendaciones personalizadas', 'my-account-manager'); ?></span>
                </label>
                
                <label class="mam-checkbox">
                    <input type="checkbox" name="privacy_options[]" value="share_with_partners" <?php checked(in_array('share_with_partners', $privacy_options)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Permitir compartir mis datos con socios de confianza', 'my-account-manager'); ?></span>
                </label>
                
                <label class="mam-checkbox">
                    <input type="checkbox" name="privacy_options[]" value="analytics_cookies" <?php checked(in_array('analytics_cookies', $privacy_options)); ?> />
                    <span class="mam-checkbox-label"><?php _e('Permitir cookies de análisis para mejorar el sitio', 'my-account-manager'); ?></span>
                </label>
            </div>
            
            <div class="mam-privacy-links">
                <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank">
                    <?php _e('Ver Política de Privacidad', 'my-account-manager'); ?>
                </a>
                |
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('privacy-data')); ?>">
                    <?php _e('Descargar mis datos personales', 'my-account-manager'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Añadir sección de sesiones activas
     */
    public function add_active_sessions() {
        $user_id = get_current_user_id();
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $all_sessions = $sessions->get_all();
        $current_session_token = wp_get_session_token();
        
        ?>
        <div class="mam-active-sessions">
            <h3><?php _e('Sesiones Activas', 'my-account-manager'); ?></h3>
            <p><?php _e('Dispositivos en los que has iniciado sesión:', 'my-account-manager'); ?></p>
            
            <div class="mam-sessions-list">
                <?php if (empty($all_sessions)) : ?>
                    <p><?php _e('No hay sesiones activas.', 'my-account-manager'); ?></p>
                <?php else : ?>
                    <?php foreach ($all_sessions as $token => $session) : 
                        $is_current = ($token === $current_session_token);
                        $device = $this->get_device_info($session);
                        $time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $session['login']);
                    ?>
                        <div class="mam-session-item <?php echo $is_current ? 'mam-current-session' : ''; ?>">
                            <div class="mam-session-info">
                                <div class="mam-session-device">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <?php echo esc_html($device); ?>
                                    <?php if ($is_current) : ?>
                                        <span class="mam-current-device-label"><?php _e('(Dispositivo actual)', 'my-account-manager'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mam-session-time">
                                    <?php echo esc_html($time); ?>
                                </div>
                            </div>
                            
                            <?php if (!$is_current) : ?>
                                <div class="mam-session-action">
                                    <form method="post" action="">
                                        <?php wp_nonce_field('mam_revoke_session', 'mam_session_nonce'); ?>
                                        <input type="hidden" name="session_token" value="<?php echo esc_attr($token); ?>">
                                        <button type="submit" name="mam_revoke_session" class="mam-button mam-button-danger mam-revoke-session">
                                            <?php _e('Cerrar Sesión', 'my-account-manager'); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mam-all-sessions-action">
                        <form method="post" action="">
                            <?php wp_nonce_field('mam_revoke_all_sessions', 'mam_all_sessions_nonce'); ?>
                            <button type="submit" name="mam_revoke_all_sessions" class="mam-button mam-button-secondary mam-revoke-all-sessions">
                                <?php _e('Cerrar Todas las Sesiones (excepto la actual)', 'my-account-manager'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener información del dispositivo
     */
    private function get_device_info($session) {
        $device = __('Dispositivo desconocido', 'my-account-manager');
        
        if (isset($session['user_agent'])) {
            $user_agent = $session['user_agent'];
            
            // Detectar SO
            if (strpos($user_agent, 'Windows') !== false) {
                $device = __('Windows', 'my-account-manager');
            } elseif (strpos($user_agent, 'Macintosh') !== false) {
                $device = __('Mac', 'my-account-manager');
            } elseif (strpos($user_agent, 'Linux') !== false) {
                $device = __('Linux', 'my-account-manager');
            } elseif (strpos($user_agent, 'iPhone') !== false) {
                $device = __('iPhone', 'my-account-manager');
            } elseif (strpos($user_agent, 'iPad') !== false) {
                $device = __('iPad', 'my-account-manager');
            } elseif (strpos($user_agent, 'Android') !== false) {
                $device = __('Android', 'my-account-manager');
            }
            
            // Detectar navegador
            if (strpos($user_agent, 'Chrome') !== false) {
                $device .= ' - Chrome';
            } elseif (strpos($user_agent, 'Safari') !== false) {
                $device .= ' - Safari';
            } elseif (strpos($user_agent, 'Firefox') !== false) {
                $device .= ' - Firefox';
            } elseif (strpos($user_agent, 'Edge') !== false) {
                $device .= ' - Edge';
            } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
                $device .= ' - Internet Explorer';
            }
        }
        
        return $device;
    }
/**
 * Cargar los valores guardados de CUIT y empresa en el formulario de cuenta
 */
public function load_account_fields_values($user) {
    // Obtener datos guardados (buscar en múltiples ubicaciones para garantizar que tengamos valores)
   $company = get_user_meta($user->ID, 'billing_company', true);
if (empty($company)) {
    $company = get_user_meta($user->ID, 'company_name', true);
}

$cuit = get_user_meta($user->ID, 'billing_cuit', true);
if (empty($cuit)) {
    $cuit = get_user_meta($user->ID, 'cuit', true);
}

    
    ?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_company"><?php _e('Empresa', 'my-account-manager'); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_company" id="account_company" value="<?php echo esc_attr($company); ?>" />
    </p>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_cuit"><?php _e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_cuit" id="account_cuit" value="<?php echo esc_attr($cuit); ?>" required />
    </p>
    <?php
}
    /**
 * Guardar los campos CUIT y empresa cuando se actualiza la cuenta
 */
public function save_account_fields($user_id) {
    if (isset($_POST['account_company'])) {
        $company = sanitize_text_field($_POST['account_company']);
        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'company_name', $company); // También guardar en campo personalizado
    }
    
    if (isset($_POST['account_cuit'])) {
        $cuit = sanitize_text_field($_POST['account_cuit']);
        
        // Validar formato del CUIT
        if (!empty($cuit) && !$this->validate_cuit_format($cuit)) {
            wc_add_notice(__('El formato del CUIT no es válido.', 'my-account-manager'), 'error');
            return;
        }
        
        update_user_meta($user_id, 'billing_cuit', $cuit);
        update_user_meta($user_id, 'cuit', $cuit); // También guardar en campo personalizado
    }
}
    /**
     * Añadir sección de eliminación de cuenta
     */
    public function add_delete_account_option() {
        ?>
        <div class="mam-delete-account">
            <h3><?php _e('Eliminar mi cuenta', 'my-account-manager'); ?></h3>
            <p><?php _e('Si eliminas tu cuenta, todos tus datos personales serán borrados y no podrás recuperarlos. Los pedidos existentes no se verán afectados.', 'my-account-manager'); ?></p>
            
            <div class="mam-delete-account-action">
                <button type="button" id="mam-show-delete-confirmation" class="mam-button mam-button-danger">
                    <?php _e('Eliminar mi cuenta', 'my-account-manager'); ?>
                </button>
            </div>
            
            <div id="mam-delete-confirmation" class="mam-delete-confirmation" style="display: none;">
                <p class="mam-confirmation-message">
                    <?php _e('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.', 'my-account-manager'); ?>
                </p>
                
                <form method="post" action="">
                    <div class="mam-confirmation-password">
                        <label for="current_password_confirm"><?php _e('Introduce tu contraseña para confirmar:', 'my-account-manager'); ?></label>
                        <input type="password" name="current_password_confirm" id="current_password_confirm" required />
                    </div>
                    
                    <div class="mam-confirmation-reason">
                        <label for="delete_reason"><?php _e('¿Por qué quieres eliminar tu cuenta? (opcional)', 'my-account-manager'); ?></label>
                        <select name="delete_reason" id="delete_reason">
                            <option value=""><?php _e('Selecciona una razón...', 'my-account-manager'); ?></option>
                            <option value="privacy"><?php _e('Preocupaciones de privacidad', 'my-account-manager'); ?></option>
                            <option value="unused"><?php _e('Ya no uso la cuenta', 'my-account-manager'); ?></option>
                            <option value="experience"><?php _e('Mala experiencia con el servicio', 'my-account-manager'); ?></option>
                            <option value="other"><?php _e('Otra razón', 'my-account-manager'); ?></option>
                        </select>
                    </div>
                    
                    <div id="delete_reason_other_container" class="mam-confirmation-reason-other" style="display: none;">
                        <label for="delete_reason_other"><?php _e('Por favor, explica por qué:', 'my-account-manager'); ?></label>
                        <textarea name="delete_reason_other" id="delete_reason_other"></textarea>
                    </div>
                    
                    <div class="mam-confirmation-actions">
                        <?php wp_nonce_field('mam_delete_account', 'mam_delete_nonce'); ?>
                        <button type="button" id="mam-cancel-delete" class="mam-button mam-button-secondary">
                            <?php _e('Cancelar', 'my-account-manager'); ?>
                        </button>
                        <button type="submit" name="mam_delete_account" class="mam-button mam-button-danger">
                            <?php _e('Sí, eliminar mi cuenta', 'my-account-manager'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Procesar solicitud de eliminación de cuenta
     */
    public function process_account_deletion() {
        if (!isset($_POST['mam_delete_account']) || !isset($_POST['mam_delete_nonce']) || !wp_verify_nonce($_POST['mam_delete_nonce'], 'mam_delete_account')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $password = isset($_POST['current_password_confirm']) ? $_POST['current_password_confirm'] : '';
        
        // Verificar contraseña
        $user = get_user_by('id', $user_id);
        
        if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
            wc_add_notice(__('La contraseña introducida es incorrecta.', 'my-account-manager'), 'error');
            return;
        }
        
        // Registrar el motivo de eliminación (opcional)
        if (!empty($_POST['delete_reason'])) {
            $reason = sanitize_text_field($_POST['delete_reason']);
            $reason_other = '';
            
            if ($reason === 'other' && !empty($_POST['delete_reason_other'])) {
                $reason_other = sanitize_textarea_field($_POST['delete_reason_other']);
            }
            
            // Guardar información de eliminación para análisis posterior
            update_option('mam_account_deletion_' . $user_id, array(
                'reason' => $reason,
                'reason_other' => $reason_other,
                'time' => current_time('mysql'),
                'user_email' => $user->user_email
            ));
        }
        
        // Eliminar datos personales pero conservar pedidos
        $this->anonymize_user_data($user_id);
        
        // Opcional: eliminar completamente al usuario (comentar esta línea para anonimizar en lugar de eliminar)
        // require_once(ABSPATH . 'wp-admin/includes/user.php');
        // wp_delete_user($user_id);
        
        // Cerrar sesión
        wp_logout();
        
        // Redirigir a la página principal con mensaje
        wp_redirect(add_query_arg('account_deleted', 'true', home_url()));
        exit;
    }

    /**
     * Anonimizar datos de usuario
     */
    private function anonymize_user_data($user_id) {
        // Generar un identificador anónimo
        $anonymous_email = 'deleted_user_' . md5($user_id . time()) . '@example.com';
        
        // Actualizar usuario con datos anónimos
        wp_update_user(array(
            'ID' => $user_id,
            'user_email' => $anonymous_email,
            'display_name' => __('Usuario eliminado', 'my-account-manager'),
            'first_name' => '',
            'last_name' => '',
        ));
        
        // Eliminar metadatos personales
        $personal_meta_keys = array(
            'phone',
            'birth_date',
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_phone',
            'billing_email',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_postcode',
            'shipping_country',
            'shipping_state',
            'shipping_phone',
            '_mam_additional_addresses',
            'communication_preferences',
            'privacy_options',
        );
        
        foreach ($personal_meta_keys as $meta_key) {
            delete_user_meta($user_id, $meta_key);
        }
    }

    /**
     * Enqueue scripts específicos para la página de datos de cuenta
     */
    public function enqueue_account_scripts() {
        if (is_account_page() && is_wc_endpoint_url('edit-account')) {
            wp_enqueue_script('mam-account-details', MAM_PLUGIN_URL . 'assets/js/account-details.js', array('jquery'), MAM_VERSION, true);
        }
    }

    /**
     * Añadir pestañas para organizar la información de la cuenta
     */
    public function add_account_tabs() {
        // Obtener pestaña actual
        $current_tab = isset($_GET['tab']) ? wc_clean($_GET['tab']) : 'details';
        
        ?>
        <div class="mam-account-tabs">
            <a href="<?php echo esc_url(add_query_arg('tab', 'details', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo $current_tab === 'details' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <?php _e('Datos Personales', 'my-account-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('tab', 'password', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo $current_tab === 'password' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <?php _e('Contraseña y Seguridad', 'my-account-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('tab', 'preferences', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo $current_tab === 'preferences' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <?php _e('Preferencias', 'my-account-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('tab', 'privacy', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo $current_tab === 'privacy' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <?php _e('Privacidad', 'my-account-manager'); ?>
            </a>
        </div>
        <?php
    }
}

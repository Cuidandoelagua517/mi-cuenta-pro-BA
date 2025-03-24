
<?php
/**
 * Login and Register functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Login_Register Class
 */
class MAM_Login_Register {

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
        // Sobreescribir formularios de inicio de sesión y registro
        //add_action('woocommerce_login_form_start', array($this, 'login_form_start'), 10);
        //add_action('woocommerce_login_form', array($this, 'login_form_custom_fields'), 15);
        //add_action('woocommerce_login_form_end', array($this, 'login_form_end'), 10);
        
        // Sobreescribir formulario de registro
        //add_action('woocommerce_register_form_start', array($this, 'register_form_start'), 10);
        //add_action('woocommerce_register_form', array($this, 'register_form_custom_fields'), 15);
        //add_action('woocommerce_register_form_end', array($this, 'register_form_end'), 10);
         // Registrar los handlers AJAX primero

        
        // Validación personalizada
        add_action('woocommerce_process_login_errors', array($this, 'validate_login'), 10, 2);
        add_action('woocommerce_process_registration_errors', array($this, 'validate_registration'), 10, 3);
        
        // Personalizar páginas de mi cuenta
        add_action('woocommerce_account_content', array($this, 'account_content_wrapper_start'), 5);
        add_action('woocommerce_account_content', array($this, 'account_content_wrapper_end'), 999);
        
        // Shortcodes personalizados
        add_shortcode('mam_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('mam_register_form', array($this, 'register_form_shortcode'));
        
add_action('woocommerce_register_form', array($this, 'register_form_custom_fields'), 15);
add_action('woocommerce_created_customer', array($this, 'save_register_fields'), 10, 1);
add_filter('woocommerce_process_registration_errors', array($this, 'validate_registration'), 10, 3);
add_action('init', array($this, 'remove_duplicate_fields'), 5);
    
    // Asegurarse de que register_ajax_handlers se llama
    $this->register_ajax_handlers();
}

/**
 * Register AJAX handlers
 */
public function register_ajax_handlers() {
    // Usar add_action para registrar los manejadores
    add_action('wp_ajax_nopriv_mam_ajax_login', array($this, 'ajax_login'));
    add_action('wp_ajax_nopriv_mam_ajax_register', array($this, 'ajax_register'));
    
    // Agregamos estos para debugging
    error_log('AJAX handlers registered: mam_ajax_login, mam_ajax_register');
}

    /**
     * Inicio del formulario de login personalizado
     */
    public function login_form_start() {
        ?>
        <div class="mam-login-form-container">
            <div class="mam-form-header">
                <h2><?php _e('Iniciar Sesión', 'my-account-manager'); ?></h2>
                <p><?php _e('Ingresa tus credenciales para acceder a tu cuenta', 'my-account-manager'); ?></p>
            </div>
        <?php
    }

    /**
     * Campos personalizados para el formulario de login
     */
    public function login_form_custom_fields() {
        // Aquí se pueden añadir campos personalizados o modificar los existentes
        ?>
        <div class="mam-login-remember">
            <label class="mam-checkbox">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                <span class="mam-checkbox-label"><?php _e('Recordarme', 'my-account-manager'); ?></span>
            </label>
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="mam-lost-password"><?php _e('¿Olvidaste tu contraseña?', 'my-account-manager'); ?></a>
        </div>
        <?php
    }

    /**
     * Fin del formulario de login personalizado
     */
    public function login_form_end() {
        ?>
            <button type="submit" class="woocommerce-button button woocommerce-form-login__submit mam-button mam-button-primary" name="login" value="<?php esc_attr_e('Iniciar Sesión', 'my-account-manager'); ?>"><?php esc_html_e('Iniciar Sesión', 'my-account-manager'); ?></button>
            
            <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>
                <div class="mam-register-link">
                    <p><?php _e('¿No tienes cuenta?', 'my-account-manager'); ?> <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>#register"><?php _e('Regístrate', 'my-account-manager'); ?></a></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Inicio del formulario de registro personalizado
     */
    public function register_form_start() {
        ?>
        <div class="mam-register-form-container">
            <div class="mam-form-header">
                <h2><?php _e('Crear Cuenta', 'my-account-manager'); ?></h2>
                <p><?php _e('Completa el formulario para crear tu cuenta', 'my-account-manager'); ?></p>
            </div>
        <?php
    }

    /**
     * Campos personalizados para el formulario de registro
     */
   public function register_form_custom_fields() {
    ?>
    <div class="mam-form-row mam-form-row-wide">
        <label for="reg_company_name"><?php _e('Nombre de Empresa', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="company_name" id="reg_company_name" value="<?php echo (!empty($_POST['company_name'])) ? esc_attr($_POST['company_name']) : ''; ?>" required />
    </div>

    <div class="mam-form-row mam-form-row-wide">
        <label for="reg_cuit"><?php _e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="cuit" id="reg_cuit" placeholder="xx-xxxxxxxx-x" value="<?php echo (!empty($_POST['cuit'])) ? esc_attr($_POST['cuit']) : ''; ?>" required />
    </div>

    <div class="mam-form-row mam-form-row-first">
        <label for="reg_first_name"><?php _e('Nombre', 'my-account-manager'); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" autocomplete="given-name" value="<?php echo (!empty($_POST['first_name'])) ? esc_attr($_POST['first_name']) : ''; ?>" />
    </div>

    <div class="mam-form-row mam-form-row-last">
        <label for="reg_last_name"><?php _e('Apellido', 'my-account-manager'); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name" id="reg_last_name" autocomplete="family-name" value="<?php echo (!empty($_POST['last_name'])) ? esc_attr($_POST['last_name']) : ''; ?>" />
    </div>

    <div class="mam-form-row mam-form-row-wide">
        <label for="reg_phone"><?php _e('Teléfono', 'my-account-manager'); ?></label>
        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="phone" id="reg_phone" autocomplete="tel" value="<?php echo (!empty($_POST['phone'])) ? esc_attr($_POST['phone']) : ''; ?>" />
    </div>

    <div class="mam-register-privacy">
        <label class="mam-checkbox">
            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="privacy_policy" type="checkbox" id="privacy_policy" value="1" required />
            <span class="mam-checkbox-label"><?php printf(__('He leído y acepto la %spolitica de privacidad%s', 'my-account-manager'), '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">', '</a>'); ?></span>
        </label>
    </div>
    <?php
}
 
    /**
     * Fin del formulario de registro personalizado
     */
    public function register_form_end() {
        ?>
            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit mam-button mam-button-primary" name="register" value="<?php esc_attr_e('Registrarse', 'my-account-manager'); ?>"><?php esc_html_e('Registrarse', 'my-account-manager'); ?></button>
            
            <div class="mam-login-link">
                <p><?php _e('¿Ya tienes cuenta?', 'my-account-manager'); ?> <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php _e('Inicia sesión', 'my-account-manager'); ?></a></p>
            </div>
        </div>
        <?php
    }
public function save_register_fields($customer_id) {
    // Guardar empresa
if (isset($_POST['company_name']) && !empty($_POST['company_name'])) {
        $company = sanitize_text_field($_POST['company_name']);
        update_user_meta($customer_id, 'company_name', $company);
        update_user_meta($customer_id, 'billing_company', $company); // También guardar como dato de facturación
    }
    
    // Guardar CUIT
  if (isset($_POST['cuit']) && !empty($_POST['cuit'])) {
        $cuit = sanitize_text_field($_POST['cuit']);
        update_user_meta($customer_id, 'cuit', $cuit);
        update_user_meta($customer_id, 'billing_cuit', $cuit); // También guardar como dato de facturación
    }
     if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
        update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
if (isset($_POST['last_name']) && !empty($_POST['last_name'])) {
        update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }
    
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        update_user_meta($customer_id, 'phone', sanitize_text_field($_POST['phone']));
    }
}
    /**
     * Validación personalizada de login
     */
    public function validate_login($validation_error, $username) {
        // Validaciones personalizadas para el inicio de sesión
        return $validation_error;
    }

    /**
 * Validación personalizada de registro
 */
public function ajax_register() {
    check_ajax_referer('mam-nonce', 'security');
    
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validar los campos obligatorios
    $errors = array();
    
    if (empty($email)) {
        $errors[] = __('El correo electrónico es obligatorio', 'my-account-manager');
    }
    
    if (isset($_POST['company_name']) && empty($_POST['company_name'])) {
        $errors[] = __('El nombre de empresa es obligatorio', 'my-account-manager');
    }
    
    if (isset($_POST['cuit']) && empty($_POST['cuit'])) {
        $errors[] = __('El CUIT es obligatorio', 'my-account-manager');
    }
    
    if (!empty($errors)) {
        wp_send_json_error(array('message' => implode('<br>', $errors)));
        exit;
    }
    
    // Validación básica de formato CUIT (xx-xxxxxxxx-x)
    if (!empty($_POST['cuit']) && !$this->validate_cuit_format($_POST['cuit'])) {
        $validation_error->add('cuit_format_error', __('El formato del CUIT no es válido. Debe ser: xx-xxxxxxxx-x', 'my-account-manager'));
    }
    
    // Validar aceptación de política de privacidad (obligatorio)
    if (isset($_POST['privacy_policy']) && empty($_POST['privacy_policy'])) {
        $validation_error->add('privacy_policy_error', __('Debes aceptar nuestra política de privacidad.', 'my-account-manager'));
    }
    
    // Eliminar validaciones de otros campos como first_name, last_name, etc.
    
    return $validation_error;
}
/**
 * Validar formato de CUIT
 */
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
     * Inicio del wrapper de contenido de mi cuenta
     */
    public function account_content_wrapper_start() {
        echo '<div class="mam-account-content-wrapper">';
    }

    /**
     * Fin del wrapper de contenido de mi cuenta
     */
    public function account_content_wrapper_end() {
        echo '</div>';
    }

    /**
     * Shortcode para formulario de login
     */
    public function login_form_shortcode($atts) {
        ob_start();
        
        if (is_user_logged_in()) {
            wc_get_template('myaccount/my-account.php');
        } else {
            wc_get_template('myaccount/form-login.php', array('form' => 'login'));
        }
        
        return ob_get_clean();
    }
      
    /**
     * Shortcode para formulario de registro
     */
    public function register_form_shortcode($atts) {
        ob_start();
        
        if (is_user_logged_in()) {
            wc_get_template('myaccount/my-account.php');
        } else {
            wc_get_template('myaccount/form-login.php', array('form' => 'register'));
        }
        
        return ob_get_clean();
    }

   /**
 * Login por AJAX
 */
public function ajax_login() {
    check_ajax_referer('mam-nonce', 'security');
    
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validación adecuada
    if (empty($username) || empty($password)) {
        wp_send_json_error(['message' => __('Complete todos los campos', 'my-account-manager')]);
        return;
    }
    
    $user = wp_signon([
        'user_login' => $username,
        'user_password' => $password,
        'remember' => isset($_POST['rememberme'])
    ]);
    
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => $user->get_error_message()]);
        return;
    }
    
    wp_send_json_success([
        'message' => __('Login exitoso', 'my-account-manager'),
        'redirect' => wc_get_account_endpoint_url('dashboard')
    ]);
}
  /**
 * En includes/modules/class-mam-login-register.php
 * Modificar la función ajax_register() para guardar correctamente los datos
 */

public function ajax_register() {
    check_ajax_referer('mam-nonce', 'security');
    
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $validation_error = new WP_Error();
    $validation_error = $this->validate_registration($validation_error, $username, $email);
    
    if ($validation_error->get_error_code()) {
        wp_send_json_error(array('message' => $validation_error->get_error_message()));
        exit;
    }
    
    $new_customer = wc_create_new_customer($email, $username, $password);
    
    if (is_wp_error($new_customer)) {
        wp_send_json_error(array('message' => $new_customer->get_error_message()));
        exit;
    }
    
    // MODIFICACIÓN IMPORTANTE: Guardar datos en múltiples ubicaciones
   // Asegurarse de usar los campos correctos (los que tienen el prefijo "reg_")
    if (isset($_POST['company_name']) && !empty($_POST['company_name'])) {
        $company = sanitize_text_field($_POST['company_name']);
        update_user_meta($new_customer, 'company_name', $company);
        update_user_meta($new_customer, 'billing_company', $company);
        update_user_meta($new_customer, 'shipping_company', $company);
    }
    
    if (isset($_POST['cuit']) && !empty($_POST['cuit'])) {
        $cuit = sanitize_text_field($_POST['cuit']);
        update_user_meta($new_customer, 'cuit', $cuit);
        update_user_meta($new_customer, 'billing_cuit', $cuit);
        update_user_meta($new_customer, 'shipping_cuit', $cuit);
    }
    
    // Iniciar sesión automáticamente
    wc_set_customer_auth_cookie($new_customer);
    
    wp_send_json_success(array(
        'message' => __('Registro exitoso, redirigiendo...', 'my-account-manager'),
        'redirect' => apply_filters('mam_registration_redirect', wc_get_page_permalink('myaccount'), $new_customer)
    ));
    
    exit;
}
public function remove_duplicate_fields() {
    // Elimina todas las acciones que podrían añadir campos al formulario de registro
    remove_all_actions('woocommerce_register_form');
    
    // Luego vuelve a añadir solo tu función personalizada
    add_action('woocommerce_register_form', array($this, 'register_form_custom_fields'), 15);
    
    // También elimina campos en estos hooks importantes
    remove_all_actions('woocommerce_register_form_start');
    remove_all_actions('woocommerce_register_form_end');
    
    // Elimina acciones específicas conocidas
    global $wp_filter;
    if (isset($wp_filter['woocommerce_register_form'])) {
        foreach ($wp_filter['woocommerce_register_form']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback_id => $callback_obj) {
                if ($callback_id !== get_class($this) . '::register_form_custom_fields') {
                    remove_action('woocommerce_register_form', $callback_obj['function'], $priority);
                }
            }
        }
    }
}
}

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
          $instance->register_ajax_handlers(); // Método para registrar handlers
        return $instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Sobreescribir formularios de inicio de sesión y registro
        add_action('woocommerce_login_form_start', array($this, 'login_form_start'), 10);
        add_action('woocommerce_login_form', array($this, 'login_form_custom_fields'), 15);
        add_action('woocommerce_login_form_end', array($this, 'login_form_end'), 10);
        
        // Sobreescribir formulario de registro
        add_action('woocommerce_register_form_start', array($this, 'register_form_start'), 10);
        add_action('woocommerce_register_form', array($this, 'register_form_custom_fields'), 15);
        add_action('woocommerce_register_form_end', array($this, 'register_form_end'), 10);
        
        // Validación personalizada
        add_action('woocommerce_process_login_errors', array($this, 'validate_login'), 10, 2);
        add_action('woocommerce_process_registration_errors', array($this, 'validate_registration'), 10, 3);
        
        // Personalizar páginas de mi cuenta
        add_action('woocommerce_account_content', array($this, 'account_content_wrapper_start'), 5);
        add_action('woocommerce_account_content', array($this, 'account_content_wrapper_end'), 999);
        
        // Shortcodes personalizados
        add_shortcode('mam_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('mam_register_form', array($this, 'register_form_shortcode'));
        
        // AJAX para login y registro
        add_action('wp_ajax_nopriv_mam_ajax_login', array($this, 'ajax_login'));
        add_action('wp_ajax_nopriv_mam_ajax_register', array($this, 'ajax_register'));
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
        // Aquí se pueden añadir campos personalizados de registro
        ?>
        <div class="mam-form-row mam-form-row-first">
            <label for="reg_first_name"><?php _e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" autocomplete="given-name" value="<?php echo (!empty($_POST['first_name'])) ? esc_attr($_POST['first_name']) : ''; ?>" />
        </div>

        <div class="mam-form-row mam-form-row-last">
            <label for="reg_last_name"><?php _e('Apellido', 'my-account-manager'); ?> <span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name" id="reg_last_name" autocomplete="family-name" value="<?php echo (!empty($_POST['last_name'])) ? esc_attr($_POST['last_name']) : ''; ?>" />
        </div>

        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_phone"><?php _e('Teléfono', 'my-account-manager'); ?></label>
            <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="phone" id="reg_phone" autocomplete="tel" value="<?php echo (!empty($_POST['phone'])) ? esc_attr($_POST['phone']) : ''; ?>" />
        </div>

        <div class="mam-register-privacy">
            <label class="mam-checkbox">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="privacy_policy" type="checkbox" id="privacy_policy" value="1" />
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
    public function validate_registration($validation_error, $username, $email) {
        // Validar campos obligatorios personalizados
        if (isset($_POST['first_name']) && empty($_POST['first_name'])) {
            $validation_error->add('first_name_error', __('El nombre es obligatorio.', 'my-account-manager'));
        }
        
        if (isset($_POST['last_name']) && empty($_POST['last_name'])) {
            $validation_error->add('last_name_error', __('El apellido es obligatorio.', 'my-account-manager'));
        }
        
        if (isset($_POST['privacy_policy']) && empty($_POST['privacy_policy'])) {
            $validation_error->add('privacy_policy_error', __('Debes aceptar nuestra política de privacidad.', 'my-account-manager'));
        }
        
        return $validation_error;
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
        
        $creds = array(
            'user_login'    => trim($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['rememberme']),
        );
        
        $validation_error = new WP_Error();
        $validation_error = $this->validate_login($validation_error, $creds['user_login']);
        
        if ($validation_error->get_error_code()) {
            wp_send_json_error(array('message' => $validation_error->get_error_message()));
            exit;
        }
        
        $user = wp_signon($creds, is_ssl());
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
            exit;
        }
        
        wp_send_json_success(array(
            'message' => __('Login exitoso, redirigiendo...', 'my-account-manager'),
            'redirect' => apply_filters('mam_login_redirect', wc_get_page_permalink('myaccount'), $user)
        ));
        
        exit;
    }

    /**
     * Registro por AJAX
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
        
        // Guardar campos personalizados
        if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
            update_user_meta($new_customer, 'first_name', sanitize_text_field($_POST['first_name']));
        }
        
        if (isset($_POST['last_name']) && !empty($_POST['last_name'])) {
            update_user_meta($new_customer, 'last_name', sanitize_text_field($_POST['last_name']));
        }
        
        if (isset($_POST['phone']) && !empty($_POST['phone'])) {
            update_user_meta($new_customer, 'phone', sanitize_text_field($_POST['phone']));
        }
        
        // Iniciar sesión automáticamente
        wc_set_customer_auth_cookie($new_customer);
        
        wp_send_json_success(array(
            'message' => __('Registro exitoso, redirigiendo...', 'my-account-manager'),
            'redirect' => apply_filters('mam_registration_redirect', wc_get_page_permalink('myaccount'), $new_customer)
        ));
        
        exit;
    }
}

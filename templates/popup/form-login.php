<?php
/**
 * Login Form for popups
 * Optimized version for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Obtener atributos personalizados
$atts = get_query_var('mam_form_atts', [
    'redirect' => '',
    'show_title' => 'yes',
    'show_register_link' => 'yes',
    'button_text' => 'Iniciar Sesión'
]);
?>

<div class="mam-popup-form mam-login-popup-form">
    <?php if ($atts['show_title'] === 'yes') : ?>
    <div class="mam-popup-form-header">
        <h3><?php esc_html_e('Iniciar Sesión', 'my-account-manager'); ?></h3>
        <p><?php esc_html_e('Ingresa tus datos para acceder a tu cuenta', 'my-account-manager'); ?></p>
    </div>
    <?php endif; ?>
    
    <form class="woocommerce-form woocommerce-form-login login mam-ajax-form" data-action="mam_ajax_login" method="post">
        <?php wp_nonce_field('mam-nonce', 'security'); ?>
        <input type="hidden" name="action" value="mam_ajax_login">
        
        <?php if (!empty($atts['redirect'])) : ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url($atts['redirect']); ?>">
        <?php endif; ?>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="username"><?php esc_html_e('Correo electrónico', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input type="email" class="woocommerce-Input input-text" name="username" id="popup_username" autocomplete="email" required />
            </div>
        </div>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="password"><?php esc_html_e('Contraseña', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-password-field mam-input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input class="woocommerce-Input input-text" type="password" name="password" id="popup_password" autocomplete="current-password" required />
                <span class="mam-password-toggle" role="button" tabindex="0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </span>
            </div>
        </div>
        
        <div class="mam-form-row mam-remember-row">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox mam-checkbox">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="popup_rememberme" value="forever" />
                <span class="mam-checkbox-label"><?php esc_html_e('Recordarme', 'my-account-manager'); ?></span>
            </label>
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="mam-lost-password-link">
                <?php esc_html_e('¿Olvidaste tu contraseña?', 'my-account-manager'); ?>
            </a>
        </div>
        
        <div class="mam-form-row">
            <button type="submit" class="mam-button mam-button-primary" name="login">
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>
        
        <?php if ($atts['show_register_link'] === 'yes' && get_option('woocommerce_enable_myaccount_registration') === 'yes') : ?>
            <div class="mam-popup-form-footer">
                <p><?php esc_html_e('¿No tienes cuenta?', 'my-account-manager'); ?> 
                <a href="#" class="mam-toggle-form" data-toggle="register"><?php esc_html_e('Regístrate', 'my-account-manager'); ?></a></p>
            </div>
        <?php endif; ?>
    </form>
</div>

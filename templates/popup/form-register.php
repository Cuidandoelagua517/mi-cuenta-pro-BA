<?php
/**
 * Register Form for popups
 * Optimized version for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Obtener atributos personalizados
$atts = get_query_var('mam_form_atts', [
    'redirect' => '',
    'show_title' => 'yes',
    'show_login_link' => 'yes',
    'button_text' => 'Crear Cuenta'
]);

// Solo mostrar si el registro está habilitado
if (get_option('woocommerce_enable_myaccount_registration') !== 'yes') {
    echo '<p>' . esc_html__('El registro de nuevos usuarios no está habilitado.', 'my-account-manager') . '</p>';
    return;
}
?>

<div class="mam-popup-form mam-register-popup-form">
    <?php if ($atts['show_title'] === 'yes') : ?>
    <div class="mam-popup-form-header">
        <h3><?php esc_html_e('Crear Cuenta', 'my-account-manager'); ?></h3>
        <p><?php esc_html_e('Completa los campos para crear tu cuenta', 'my-account-manager'); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" class="woocommerce-form woocommerce-form-register register mam-ajax-form" data-action="mam_ajax_register">
        <?php wp_nonce_field('mam-nonce', 'security'); ?>
        <input type="hidden" name="action" value="mam_ajax_register">
        
        <?php if (!empty($atts['redirect'])) : ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url($atts['redirect']); ?>">
        <?php endif; ?>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_email"><?php esc_html_e('Correo electrónico', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input type="email" class="woocommerce-Input input-text" name="email" id="popup_reg_email" autocomplete="email" required />
            </div>
        </div>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_company_name"><?php esc_html_e('Nombre de Empresa', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <input type="text" class="woocommerce-Input input-text" name="company_name" id="popup_reg_company_name" required />
            </div>
        </div>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_cuit"><?php esc_html_e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <input type="text" class="woocommerce-Input input-text" name="cuit" id="popup_reg_cuit" required />
            </div>
        </div>
        
        <div class="mam-form-row mam-form-row-first">
            <label for="reg_first_name"><?php esc_html_e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="first_name" id="popup_reg_first_name" required />
        </div>
        
        <div class="mam-form-row mam-form-row-last">
            <label for="reg_last_name"><?php esc_html_e('Apellidos', 'my-account-manager'); ?> <span class="required">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="last_name" id="popup_reg_last_name" required />
        </div>
        
        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_phone"><?php esc_html_e('Teléfono', 'my-account-manager'); ?></label>
            <input type="tel" class="woocommerce-Input input-text" name="phone" id="popup_reg_phone" />
        </div>
        
        <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
        <div class="mam-form-row mam-form-row-wide">
            <label for="reg_password"><?php esc_html_e('Contraseña', 'my-account-manager'); ?> <span class="required">*</span></label>
            <div class="mam-password-field">
                <input type="password" class="woocommerce-Input input-text" name="password" id="popup_reg_password" autocomplete="new-password" required />
                <span class="mam-password-toggle" role="button" tabindex="0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mam-form-row mam-privacy-policy">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox mam-checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="privacy_policy" id="popup_privacy_policy" value="1" required />
                <span class="mam-checkbox-label"><?php 
                    printf(
                        esc_html__('He leído y acepto la %spolitica de privacidad%s', 'my-account-manager'),
                        '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                        '</a>'
                    ); 
                ?></span>
            </label>
        </div>
        
        <div class="mam-form-row">
            <button type="submit" class="mam-button mam-button-primary" name="register">
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>
        
        <?php if ($atts['show_login_link'] === 'yes') : ?>
            <div class="mam-popup-form-footer">
                <p><?php esc_html_e('¿Ya tienes cuenta?', 'my-account-manager'); ?> 
                <a href="#" class="mam-toggle-form" data-toggle="login"><?php esc_html_e('Inicia sesión', 'my-account-manager'); ?></a></p>
            </div>
        <?php endif; ?>
    </form>
</div>

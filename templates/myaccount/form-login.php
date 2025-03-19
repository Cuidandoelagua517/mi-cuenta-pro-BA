<?php
/**
 * Login Form
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

do_action('woocommerce_before_customer_login_form');
?>

<div class="mam-login-register-container">
    <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>

    <div class="mam-login-register-tabs">
        <a href="#login" class="mam-login-tab <?php echo isset($_GET['action']) && $_GET['action'] === 'register' ? '' : 'active'; ?>">
            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                <path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            <?php esc_html_e('Iniciar Sesión', 'my-account-manager'); ?>
        </a>
        <a href="#register" class="mam-register-tab <?php echo isset($_GET['action']) && $_GET['action'] === 'register' ? 'active' : ''; ?>">
            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                <path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            <?php esc_html_e('Crear Cuenta', 'my-account-manager'); ?>
        </a>
    </div>

    <?php endif; ?>

    <div class="mam-login-register-forms">
        <!-- Formulario de Login -->
        <div class="mam-login-form-wrapper <?php echo isset($_GET['action']) && $_GET['action'] === 'register' ? 'hide' : ''; ?>">
            <form class="woocommerce-form woocommerce-form-login login mam-ajax-form" data-action="mam_ajax_login" method="post" id="login-form">
                <?php wp_nonce_field('mam-nonce', 'security'); ?>
                <?php do_action('woocommerce_login_form_start'); ?>

                <div class="mam-form-row">
                    <label for="email"><?php esc_html_e('Correo electrónico', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <input type="email" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="email" 
                            id="email"
                            autocomplete="email" 
                            value="<?php echo (!empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" 
                            required
                            placeholder="<?php esc_attr_e('nombre@ejemplo.com', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <div class="mam-form-row">
                    <label for="password"><?php esc_html_e('Contraseña', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0110 0v4"></path>
                            </svg>
                        </div>
                        <input type="password" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="password" 
                            id="password" 
                            autocomplete="current-password" 
                            required
                            placeholder="<?php esc_attr_e('Contraseña', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <?php do_action('woocommerce_login_form'); ?>

                <div class="mam-form-row mam-remember-row" style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme mam-checkbox">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                        <span class="mam-checkbox-label"><?php esc_html_e('Recordarme', 'my-account-manager'); ?></span>
                    </label>
                    <p class="woocommerce-LostPassword lost_password">
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('¿Olvidaste tu contraseña?', 'my-account-manager'); ?></a>
                    </p>
                </div>

                <div class="mam-form-row">
                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="mam-button mam-button-primary" name="login" value="<?php esc_attr_e('Iniciar Sesión', 'my-account-manager'); ?>"><?php esc_html_e('Iniciar Sesión', 'my-account-manager'); ?></button>
                </div>

                <?php do_action('woocommerce_login_form_end'); ?>
            </form>

            <div class="mam-login-divider">
                <span><?php esc_html_e('O conéctate con', 'my-account-manager'); ?></span>
            </div>

            <div class="mam-social-login">
                <?php
                // Añadir botones de login social si hay algún plugin compatible
                if (function_exists('wc_social_login_buttons') || function_exists('woocommerce_social_login_buttons')) {
                    if (function_exists('wc_social_login_buttons')) {
                        wc_social_login_buttons();
                    } elseif (function_exists('woocommerce_social_login_buttons')) {
                        woocommerce_social_login_buttons();
                    }
                } else {
                    // Botones de ejemplo (solo visuales)
                    ?>
                    <a href="#" class="mam-social-button mam-google">
                        <svg width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path>
                            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path>
                            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path>
                            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path>
                        </svg>
                        <?php esc_html_e('Continuar con Google', 'my-account-manager'); ?>
                    </a>
                    <a href="#" class="mam-social-button mam-facebook">
                        <svg width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#3F51B5" d="M42,37c0,2.762-2.238,5-5,5H11c-2.761,0-5-2.238-5-5V11c0-2.762,2.239-5,5-5h26c2.762,0,5,2.238,5,5V37z"></path>
                            <path fill="#FFF" d="M34.368,25H31v13h-5V25h-3v-4h3v-2.41c0.002-3.508,1.459-5.59,5.592-5.59H35v4h-2.287C31.104,17,31,17.6,31,18.723V21h4L34.368,25z"></path>
                        </svg>
                        <?php esc_html_e('Continuar con Facebook', 'my-account-manager'); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>

        <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>
        <!-- Formulario de Registro -->
        <div class="mam-register-form-wrapper <?php echo isset($_GET['action']) && $_GET['action'] === 'register' ? '' : 'hide'; ?>">
            <form method="post" class="woocommerce-form woocommerce-form-register register mam-ajax-form" data-action="mam_ajax_register" <?php do_action('woocommerce_register_form_tag'); ?>>
                <?php wp_nonce_field('mam-nonce', 'security'); ?>
                <?php do_action('woocommerce_register_form_start'); ?>

                <div class="mam-form-row">
                    <label for="reg_email"><?php esc_html_e('Correo electrónico', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <input type="email" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="username" 
                            id="username"
                            autocomplete="email" 
                            required
                            placeholder="<?php esc_attr_e('nombre@ejemplo.com', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <div class="mam-form-row">
                    <label for="reg_company_name"><?php esc_html_e('Nombre de Empresa', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <input type="text" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="company_name" 
                            id="reg_company_name" 
                            value="<?php echo (!empty($_POST['company_name'])) ? esc_attr(wp_unslash($_POST['company_name'])) : ''; ?>" 
                            required
                            placeholder="<?php esc_attr_e('Nombre de la empresa', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>
                
                <div class="mam-form-row">
                    <label for="reg_cuit"><?php esc_html_e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="cuit" 
                            id="reg_cuit" 
                            value="<?php echo (!empty($_POST['cuit'])) ? esc_attr(wp_unslash($_POST['cuit'])) : ''; ?>" 
                            required
                            placeholder="<?php esc_attr_e('xx-xxxxxxxx-x', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-first">
                    <label for="reg_first_name"><?php esc_html_e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="first_name" 
                            id="reg_first_name" 
                            value="<?php echo (!empty($_POST['first_name'])) ? esc_attr(wp_unslash($_POST['first_name'])) : ''; ?>" 
                            required
                            placeholder="<?php esc_attr_e('Nombre', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-last">
                    <label for="reg_last_name"><?php esc_html_e('Apellidos', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="last_name" 
                            id="reg_last_name" 
                            value="<?php echo (!empty($_POST['last_name'])) ? esc_attr(wp_unslash($_POST['last_name'])) : ''; ?>" 
                            required
                            placeholder="<?php esc_attr_e('Apellidos', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="reg_phone"><?php esc_html_e('Teléfono', 'my-account-manager'); ?></label>
                    <div class="mam-input-with-icon">
                        <div class="mam-input-icon">
                            <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <input type="tel" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="phone" 
                            id="reg_phone" 
                            value="<?php echo (!empty($_POST['phone'])) ? esc_attr(wp_unslash($_POST['phone'])) : ''; ?>" 
                            placeholder="<?php esc_attr_e('Teléfono de contacto', 'my-account-manager'); ?>"
                        />
                    </div>
                </div>

                <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="reg_password"><?php esc_html_e('Contraseña', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <div class="mam-input-with-icon">
                            <div class="mam-input-icon">
                                <svg class="mam-icon-svg" viewBox="0 0 24 24">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0110 0v4"></path>
                                </svg>
                            </div>
                            <input type="password" 
                                class="woocommerce-Input woocommerce-Input--text input-text" 
                                name="password" 
                                id="reg_password" 
                                autocomplete="new-password" 
                                required
                                placeholder="<?php esc_attr_e('Contraseña', 'my-account-manager'); ?>"
                            />
                        </div>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Se enviará una contraseña a tu correo electrónico.', 'my-account-manager'); ?></p>
                <?php endif; ?>

                <div class="mam-form-row mam-privacy-policy">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox mam-checkbox">
                        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="privacy_policy" id="privacy_policy" value="1" required />
                        <span class="mam-checkbox-label"><?php printf(esc_html__('He leído y acepto la %spolitica de privacidad%s', 'my-account-manager'), '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">', '</a>'); ?></span>
                    </label>
                </div>

                <?php do_action('woocommerce_register_form'); ?>

                <div class="mam-form-row">
                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    <button type="submit" class="mam-button mam-button-primary" name="register" value="<?php esc_attr_e('Registrarse', 'my-account-manager'); ?>"><?php esc_html_e('Registrarse', 'my-account-manager'); ?></button>
                </div>

                <?php do_action('woocommerce_register_form_end'); ?>
            </form>

            <div class="mam-register-benefits">
                <h3><?php esc_html_e('Beneficios de crear una cuenta', 'my-account-manager'); ?></h3>
                <ul>
                    <li>
                        <svg class="mam-icon-svg" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php esc_html_e('Seguimiento de pedidos', 'my-account-manager'); ?>
                    </li>
                    <li>
                        <svg class="mam-icon-svg" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php esc_html_e('Pedidos más rápidos', 'my-account-manager'); ?>
                    </li>
                    <li>
                        <svg class="mam-icon-svg" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php esc_html_e('Acceso a ofertas exclusivas', 'my-account-manager'); ?>
                    </li>
                    <li>
                        <svg class="mam-icon-svg" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php esc_html_e('Guardar dirección y método de pago', 'my-account-manager'); ?>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>

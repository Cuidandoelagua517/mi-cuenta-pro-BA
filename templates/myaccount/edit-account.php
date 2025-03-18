<?php
/**
 * Edit account form
 *
 * This template is a custom version for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_before_edit_account_form');
?>

<div class="mam-account-details-header">
    <?php 
    $current_user = wp_get_current_user();
    ?>
    <div class="mam-account-avatar">
        <?php echo get_avatar($current_user->ID, 96, '', '', array('class' => 'mam-avatar')); ?>
        <div class="mam-change-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <input type="file" style="display: none;">
        </div>
    </div>
    <div class="mam-account-summary">
        <h2><?php echo esc_html($current_user->display_name); ?></h2>
        <p><?php _e('Gestiona tus datos personales y configura tus preferencias.', 'my-account-manager'); ?></p>
    </div>
</div>

<div class="mam-account-tabs">
    <a href="<?php echo esc_url(add_query_arg('tab', 'details', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'details') ? 'active' : ''; ?>" data-tab="details">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <?php _e('Datos Personales', 'my-account-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(add_query_arg('tab', 'password', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'password') ? 'active' : ''; ?>" data-tab="password">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <?php _e('Contraseña y Seguridad', 'my-account-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(add_query_arg('tab', 'preferences', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'preferences') ? 'active' : ''; ?>" data-tab="preferences">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <?php _e('Preferencias', 'my-account-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(add_query_arg('tab', 'privacy', wc_get_endpoint_url('edit-account'))); ?>" class="mam-account-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'privacy') ? 'active' : ''; ?>" data-tab="privacy">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <?php _e('Privacidad', 'my-account-manager'); ?>
    </a>
</div>

<div class="mam-account-notices"></div>

<form class="woocommerce-EditAccountForm edit-account mam-ajax-form" data-action="mam_update_account" method="post">
    <?php wp_nonce_field('mam-nonce', 'security'); ?>

    <!-- Datos personales (predeterminado) -->
    <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
        <label for="account_first_name"><?php esc_html_e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($current_user->first_name); ?>" />
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
        <label for="account_last_name"><?php esc_html_e('Apellidos', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($current_user->last_name); ?>" />
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_email"><?php esc_html_e('Correo electrónico', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($current_user->user_email); ?>" />
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_phone"><?php esc_html_e('Teléfono', 'my-account-manager'); ?></label>
        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="account_phone" id="account_phone" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>" />
        <span class="description"><?php _e('Utilizado para contactarte en caso de problemas con tu pedido.', 'my-account-manager'); ?></span>
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_birth_date"><?php esc_html_e('Fecha de nacimiento', 'my-account-manager'); ?></label>
        <input type="date" class="woocommerce-Input woocommerce-Input--date input-text" name="account_birth_date" id="account_birth_date" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'birth_date', true)); ?>" />
        <span class="description"><?php _e('Utilizada para enviarte promociones especiales en tu cumpleaños.', 'my-account-manager'); ?></span>
    </p>
    
    <!-- Campos de contraseña -->
    <fieldset>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="password_current"><?php esc_html_e('Contraseña actual (déjala en blanco para no cambiarla)', 'my-account-manager'); ?></label>
            <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="off" />
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="password_1"><?php esc_html_e('Nueva contraseña (déjala en blanco para no cambiarla)', 'my-account-manager'); ?></label>
            <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="off" />
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="password_2"><?php esc_html_e('Confirmar nueva contraseña', 'my-account-manager'); ?></label>
            <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="off" />
        </p>
    </fieldset>
    
    <!-- Medidor de fuerza de contraseña -->
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
    
    <!-- Preferencias de comunicación -->
    <div class="mam-user-preferences">
        <h3><?php _e('Preferencias de Comunicación', 'my-account-manager'); ?></h3>
        <p><?php _e('Selecciona cómo prefieres que nos comuniquemos contigo:', 'my-account-manager'); ?></p>
        
        <?php 
        // Obtener preferencias guardadas
        $saved_preferences = get_user_meta($current_user->ID, 'communication_preferences', true);
        
        if (!is_array($saved_preferences)) {
            $saved_preferences = array();
        }
        ?>
        
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
    
    <!-- Opciones de privacidad -->
    <div class="mam-privacy-options">
        <h3><?php _e('Opciones de Privacidad', 'my-account-manager'); ?></h3>
        <p><?php _e('Configura tus preferencias de privacidad:', 'my-account-manager'); ?></p>
        
        <?php 
        // Obtener opciones guardadas
        $privacy_options = get_user_meta($current_user->ID, 'privacy_options', true);
        
        if (!is_array($privacy_options)) {
            $privacy_options = array();
        }
        ?>
        
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
        
        <!-- Sección de sesiones activas -->
        <div class="mam-active-sessions">
            <h3><?php _e('Sesiones Activas', 'my-account-manager'); ?></h3>
            <p><?php _e('Dispositivos en los que has iniciado sesión:', 'my-account-manager'); ?></p>
            
            <?php
            $sessions = WP_Session_Tokens::get_instance($current_user->ID);
            $all_sessions = $sessions->get_all();
            $current_session_token = wp_get_session_token();
            ?>
            
            <div class="mam-sessions-list">
                <?php if (empty($all_sessions)) : ?>
                    <p><?php _e('No hay sesiones activas.', 'my-account-manager'); ?></p>
                <?php else : ?>
                    <?php foreach ($all_sessions as $token => $session) : 
                        $is_current = ($token === $current_session_token);
                        $user_agent = isset($session['user_agent']) ? $session['user_agent'] : '';
                        $time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $session['login']);
                        
                        // Determinar dispositivo
                        $device = __('Dispositivo desconocido', 'my-account-manager');
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
                                    <form method="post" class="mam-ajax-form" data-action="mam_revoke_session">
                                        <?php wp_nonce_field('mam-nonce', 'security'); ?>
                                        <input type="hidden" name="session_token" value="<?php echo esc_attr($token); ?>">
                                        <button type="submit" class="mam-button mam-button-danger mam-revoke-session">
                                            <?php _e('Cerrar Sesión', 'my-account-manager'); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mam-all-sessions-action">
                        <form method="post" class="mam-ajax-form" data-action="mam_revoke_all_sessions">
                            <?php wp_nonce_field('mam-nonce', 'security'); ?>
                            <button type="submit" class="mam-button mam-button-secondary mam-revoke-all-sessions">
                                <?php _e('Cerrar Todas las Sesiones (excepto la actual)', 'my-account-manager'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sección de eliminar cuenta -->
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
                
                <form method="post" class="mam-ajax-form" data-action="mam_delete_account">
                    <?php wp_nonce_field('mam-nonce', 'security'); ?>
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
                        <button type="button" id="mam-cancel-delete" class="mam-button mam-button-secondary">
                            <?php _e('Cancelar', 'my-account-manager'); ?>
                        </button>
                        <button type="submit" class="mam-button mam-button-danger">
                            <?php _e('Sí, eliminar mi cuenta', 'my-account-manager'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Botón de guardar -->
    <p>
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="woocommerce-Button button mam-button mam-button-primary" name="save_account_details" value="<?php esc_attr_e('Guardar Cambios', 'my-account-manager'); ?>"><?php esc_html_e('Guardar Cambios', 'my-account-manager'); ?></button>
        <input type="hidden" name="action" value="save_account_details" />
    </p>

</form>

<div class="mam-account-details-footer">
    <div class="mam-account-details-help">
        <p>
            <?php _e('Mantén tus datos actualizados para garantizar una experiencia óptima con nuestra tienda.', 'my-account-manager'); ?>
        </p>
    </div>
</div>

<?php do_action('woocommerce_after_edit_account_form'); ?>

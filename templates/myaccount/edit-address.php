<?php
/**
 * Edit address form
 *
 * This template is a custom version for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = (__('Direcciones', 'my-account-manager'));
$load_address = isset($_GET['address']) ? wc_clean(wp_unslash($_GET['address'])) : 'billing';

$current_user = wp_get_current_user();
$address = wc_get_account_formatted_address($load_address);

do_action('woocommerce_before_edit_account_address_form');
?>

<div class="mam-addresses-header">
    <p><?php _e('Las siguientes direcciones se utilizarán de forma predeterminada en la página de pago.', 'my-account-manager'); ?></p>
</div>

<div class="mam-addresses-tabs">
    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'billing')); ?>" class="mam-address-tab <?php echo $load_address === 'billing' ? 'active' : ''; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
        </svg>
        <?php _e('Dirección de Facturación', 'my-account-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'shipping')); ?>" class="mam-address-tab <?php echo $load_address === 'shipping' ? 'active' : ''; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
        </svg>
        <?php _e('Dirección de Envío', 'my-account-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'additional')); ?>" class="mam-address-tab <?php echo $load_address === 'additional' ? 'active' : ''; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <?php _e('Direcciones Adicionales', 'my-account-manager'); ?>
    </a>
</div>

<div class="mam-messages"></div>

<?php if ($load_address === 'additional') : 
    // Dirección actual
    $action = isset($_GET['action']) ? wc_clean($_GET['action']) : '';
    $address_id = isset($_GET['address_id']) ? wc_clean($_GET['address_id']) : '';
    
    // Obtener direcciones adicionales guardadas
    $additional_addresses = get_user_meta($current_user->ID, '_mam_additional_addresses', true);
    
    if (!is_array($additional_addresses)) {
        $additional_addresses = array();
    }
    ?>
    
    <div class="mam-additional-addresses">
        <h3><?php _e('Mis Direcciones Adicionales', 'my-account-manager'); ?></h3>
        
        <?php if (empty($additional_addresses)) : ?>
            <p class="mam-no-addresses"><?php _e('No tienes direcciones adicionales guardadas.', 'my-account-manager'); ?></p>
        <?php else : ?>
            <div class="mam-addresses-list">
                <?php foreach ($additional_addresses as $addr_id => $addr) : ?>
                    <div class="mam-address-item">
                        <div class="mam-address-content">
                            <h4 class="mam-address-name">
                                <?php echo esc_html($addr['name']); ?>
                            </h4>
                            
                            <div class="mam-address-details">
                                <?php
                                // Formatear dirección
                                $formatted_address = array(
                                    'first_name' => isset($addr['first_name']) ? $addr['first_name'] : '',
                                    'last_name'  => isset($addr['last_name']) ? $addr['last_name'] : '',
                                    'company'    => isset($addr['company']) ? $addr['company'] : '',
                                    'address_1'  => isset($addr['address_1']) ? $addr['address_1'] : '',
                                    'address_2'  => isset($addr['address_2']) ? $addr['address_2'] : '',
                                    'city'       => isset($addr['city']) ? $addr['city'] : '',
                                    'state'      => isset($addr['state']) ? $addr['state'] : '',
                                    'postcode'   => isset($addr['postcode']) ? $addr['postcode'] : '',
                                    'country'    => isset($addr['country']) ? $addr['country'] : '',
                                );
                                
                                echo wp_kses_post(WC()->countries->get_formatted_address($formatted_address));
                                
                                if (!empty($addr['phone'])) {
                                    echo '<br>' . esc_html__('Teléfono:', 'my-account-manager') . ' ' . esc_html($addr['phone']);
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="mam-address-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'address_id' => $addr_id), wc_get_endpoint_url('edit-address', 'additional'))); ?>" class="mam-button mam-button-secondary mam-edit-address">
                                <?php _e('Editar', 'my-account-manager'); ?>
                            </a>
                            
                            <a href="#" class="mam-button mam-button-danger mam-delete-address" data-address-id="<?php echo esc_attr($addr_id); ?>">
                                <?php _e('Eliminar', 'my-account-manager'); ?>
                            </a>
                            
                            <a href="#" class="mam-button mam-button-primary mam-set-default" data-address-id="<?php echo esc_attr($addr_id); ?>" data-address-type="shipping">
                                <?php _e('Usar como Predeterminada', 'my-account-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mam-add-address">
            <a href="<?php echo esc_url(add_query_arg('action', 'add', wc_get_endpoint_url('edit-address', 'additional'))); ?>" class="mam-button mam-button-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?php _e('Añadir Nueva Dirección', 'my-account-manager'); ?>
            </a>
        </div>
        
        <?php
        // Mostrar formulario para añadir/editar dirección
        if ($action === 'add' || $action === 'edit') {
            // Valores por defecto
            $addr = array(
                'name'       => '',
                'first_name' => '',
                'last_name'  => '',
                'company'    => '',
                'country'    => WC()->countries->get_base_country(),
                'address_1'  => '',
                'address_2'  => '',
                'city'       => '',
                'state'      => '',
                'postcode'   => '',
                'phone'      => '',
            );
            
            // Si estamos editando, cargar datos existentes
            if ($action === 'edit' && isset($additional_addresses[$address_id])) {
                $addr = wp_parse_args($additional_addresses[$address_id], $addr);
            }
            
            // Título del formulario
            $form_title = $action === 'add' ? __('Añadir Nueva Dirección', 'my-account-manager') : __('Editar Dirección', 'my-account-manager');
            ?>
            
            <div class="mam-address-form-container">
                <h3><?php echo esc_html($form_title); ?></h3>
                
                <form method="post" class="mam-address-form mam-ajax-form" data-action="mam_save_address">
                    <?php wp_nonce_field('mam-nonce', 'security'); ?>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_name"><?php _e('Nombre de la dirección', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_name" id="mam_address_name" placeholder="<?php esc_attr_e('Ej. Casa, Oficina, Casa de Padres', 'my-account-manager'); ?>" value="<?php echo esc_attr($addr['name']); ?>" required>
                    </div>
                    
                    <div class="mam-form-row mam-form-row-first">
                        <label for="mam_address_first_name"><?php _e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_first_name" id="mam_address_first_name" value="<?php echo esc_attr($addr['first_name']); ?>" >
                    </div>
                    
                    <div class="mam-form-row mam-form-row-last">
                        <label for="mam_address_last_name"><?php _e('Apellidos', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_last_name" id="mam_address_last_name" value="<?php echo esc_attr($addr['last_name']); ?>" >
                    </div>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_company"><?php _e('Empresa', 'my-account-manager'); ?></label>
                        <input type="text" class="mam-input-field" name="mam_address_company" id="mam_address_company" value="<?php echo esc_attr($addr['company']); ?>">
                    </div>
                    <div class="mam-form-row mam-form-row-wide">
    <label for="<?php echo esc_attr($load_address); ?>_cuit"><?php _e('CUIT', 'my-account-manager'); ?></label>
    <input type="text" class="mam-input-field" name="<?php echo esc_attr($load_address); ?>_cuit" id="<?php echo esc_attr($load_address); ?>_cuit" 
           value="<?php echo esc_attr(get_user_meta($current_user->ID, $load_address . '_cuit', true)); ?>" />
    <span class="description"><?php _e('CUIT asociado a la empresa', 'my-account-manager'); ?></span>
</div>
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_country"><?php _e('País', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <select name="mam_address_country" id="mam_address_country" class="mam-select-field country_to_state" required>
                            <?php foreach (WC()->countries->get_shipping_countries() as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($addr['country'], $code); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_address_1"><?php _e('Dirección', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_address_1" id="mam_address_address_1" placeholder="<?php esc_attr_e('Nombre de la calle y número', 'my-account-manager'); ?>" value="<?php echo esc_attr($addr['address_1']); ?>" required>
                    </div>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_address_2"><?php _e('Información adicional', 'my-account-manager'); ?></label>
                        <input type="text" class="mam-input-field" name="mam_address_address_2" id="mam_address_address_2" placeholder="<?php esc_attr_e('Apartamento, suite, unidad, etc. (opcional)', 'my-account-manager'); ?>" value="<?php echo esc_attr($addr['address_2']); ?>">
                    </div>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_city"><?php _e('Ciudad', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_city" id="mam_address_city" value="<?php echo esc_attr($addr['city']); ?>" required>
                    </div>
                    
                    <div class="mam-form-row mam-form-row-first">
                        <label for="mam_address_state"><?php _e('Provincia', 'my-account-manager'); ?></label>
                        <input type="text" class="mam-input-field" name="mam_address_state" id="mam_address_state" value="<?php echo esc_attr($addr['state']); ?>">
                    </div>
                    
                    <div class="mam-form-row mam-form-row-last">
                        <label for="mam_address_postcode"><?php _e('Código Postal', 'my-account-manager'); ?> <span class="required">*</span></label>
                        <input type="text" class="mam-input-field" name="mam_address_postcode" id="mam_address_postcode" value="<?php echo esc_attr($addr['postcode']); ?>" required>
                    </div>
                    
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_address_phone"><?php _e('Teléfono', 'my-account-manager'); ?></label>
                        <input type="tel" class="mam-input-field" name="mam_address_phone" id="mam_address_phone" value="<?php echo esc_attr($addr['phone']); ?>">
                    </div>
                    
                    <div class="mam-form-actions">
                        <button type="submit" class="mam-button mam-button-primary" name="mam_save_address">
                            <?php _e('Guardar Dirección', 'my-account-manager'); ?>
                        </button>
                        
                        <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'additional')); ?>" class="mam-button mam-button-secondary">
                            <?php _e('Cancelar', 'my-account-manager'); ?>
                        </a>
                    </div>
                    
                    <input type="hidden" name="mam_address_action" value="<?php echo esc_attr($action); ?>">
                    <input type="hidden" name="mam_address_id" value="<?php echo esc_attr($address_id); ?>">
                </form>
            </div>
            <?php
        }
        ?>
    </div>
    
<?php else : ?>
    
    <form method="post" class="woocommerce-EditAddressForm edit-address mam-ajax-form" data-action="mam_update_<?php echo esc_attr($load_address); ?>_address">
        <?php wp_nonce_field('mam-nonce', 'security'); ?>
        
        <div class="woocommerce-address-fields">
            <div class="woocommerce-address-fields__field-wrapper">
                <?php
                $fields = wc_get_account_formatted_address_fields($load_address);

                // Añadir clases y atributos a todos los campos
                foreach ($fields as $key => $field) {
                    $field['class'] = isset($field['class']) ? array_merge($field['class'], array('mam-form-field')) : array('mam-form-field');
                    $fields[$key] = $field;
                }

                // Mostrar todos los campos
                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value']));
                }
                ?>
            </div>
            
            <?php if ($load_address === 'billing') : ?>
                <div class="mam-form-row mam-form-row-wide">
                    <label for="billing_id_number"><?php _e('NIF/CIF/DNI', 'my-account-manager'); ?></label>
                    <input type="text" class="mam-input-field" name="billing_id_number" id="billing_id_number" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_id_number', true)); ?>">
                    <span class="description"><?php _e('Para facturación', 'my-account-manager'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($load_address === 'shipping') : ?>
                <div class="mam-form-row mam-form-row-wide">
                    <label for="shipping_delivery_notes"><?php _e('Instrucciones de entrega', 'my-account-manager'); ?></label>
                    <textarea class="mam-input-field" name="shipping_delivery_notes" id="shipping_delivery_notes" placeholder="<?php esc_attr_e('Notas especiales para la entrega (opcional)', 'my-account-manager'); ?>"><?php echo esc_textarea(get_user_meta($current_user->ID, 'shipping_delivery_notes', true)); ?></textarea>
                </div>
            <?php endif; ?>
            
            <div class="mam-copy-address-option">
                <?php if ($load_address === 'shipping') : ?>
                    <label class="mam-checkbox">
                        <input id="mam_copy_billing_address" type="checkbox" />
                        <span class="mam-checkbox-label">
                            <?php _e('Usar mi dirección de facturación como dirección de envío', 'my-account-manager'); ?>
                        </span>
                    </label>
                <?php endif; ?>
                
                <?php if ($load_address === 'billing' && !empty($additional_addresses)) : ?>
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="mam_saved_addresses"><?php _e('O selecciona una dirección guardada:', 'my-account-manager'); ?></label>
                        <select id="mam_saved_addresses" class="mam-select-field">
                            <option value=""><?php _e('Seleccionar dirección...', 'my-account-manager'); ?></option>
                            <?php foreach ($additional_addresses as $addr_id => $addr) : ?>
                                <option value="<?php echo esc_attr($addr_id); ?>">
                                    <?php echo esc_html($addr['name']); ?> - 
                                    <?php echo esc_html($addr['address_1']); ?>, 
                                    <?php echo esc_html($addr['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="submit" class="mam-button mam-button-primary" name="save_address" value="<?php esc_attr_e('Guardar dirección', 'my-account-manager'); ?>"><?php esc_html_e('Guardar dirección', 'my-account-manager'); ?></button>
                <?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
                <input type="hidden" name="action" value="edit_address" />
            </p>
        </div>
    </form>
    
<?php endif; ?>

<div class="mam-addresses-footer">
    <div class="mam-addresses-help">
        <p><?php _e('Asegúrate de que tus direcciones estén actualizadas para evitar problemas con tus pedidos.', 'my-account-manager'); ?></p>
    </div>
</div>

<?php do_action('woocommerce_after_edit_account_address_form'); ?>

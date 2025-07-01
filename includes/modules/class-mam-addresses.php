<?php
/**
 * Addresses functionality for My Account Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAM_Addresses Class
 */
class MAM_Addresses {

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
        // Añadir campos a la edición de cuenta
    //add_action('woocommerce_edit_account_form', array($this, 'load_account_fields_values'));
    add_action('woocommerce_save_account_details', array($this, 'save_account_fields'));
    add_filter('woocommerce_form_field_args', array($this, 'add_form_field_args'), 10, 3);
        // Personalizar título del endpoint
        add_filter('woocommerce_endpoint_edit-address_title', array($this, 'custom_addresses_title'));
        
        // Personalizar contenido de direcciones
        add_action('woocommerce_before_edit_account_address_form', array($this, 'before_address_form'));
        add_action('woocommerce_after_edit_account_address_form', array($this, 'after_address_form'));
        
        // Añadir campos personalizados a las direcciones
        add_filter('woocommerce_default_address_fields', array($this, 'customize_default_address_fields'));
        add_filter('woocommerce_billing_fields', array($this, 'customize_billing_fields'));
        add_filter('woocommerce_shipping_fields', array($this, 'customize_shipping_fields'));
        
        // Validación personalizada para direcciones
        add_action('woocommerce_after_save_address_validation', array($this, 'validate_address_fields'), 10, 2);
        
        // Añadir clases a los campos del formulario
        add_filter('woocommerce_form_field_args', array($this, 'add_form_field_args'), 10, 3);
        
        // Añadir pestañas para las direcciones
        add_action('woocommerce_before_edit_account_address_form', array($this, 'add_address_tabs'));
        
        // Añadir libreta de direcciones adicionales
        add_action('woocommerce_after_edit_account_address_form', array($this, 'add_additional_addresses'));
        
        // Manejar acciones para direcciones adicionales
        add_action('template_redirect', array($this, 'handle_additional_address_actions'));
        
        // Añadir campos de autocompletado para direcciones
        add_action('wp_enqueue_scripts', array($this, 'enqueue_address_scripts'));
        
        // Añadir opción para copiar dirección
        add_action('woocommerce_before_checkout_shipping_form', array($this, 'add_copy_address_option'));
        // Añadir campos al checkout
add_filter('woocommerce_checkout_fields', array($this, 'add_checkout_fields'));
        // Validar CUIT en checkout
add_action('woocommerce_checkout_process', array($this, 'validate_checkout_cuit'), 10, 2);
        // Mostrar CUIT en la página de pedido
add_action('woocommerce_order_details_after_customer_details', array($this, 'display_cuit_in_order'), 10, 1);

// Mostrar CUIT en administración
add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_cuit_to_admin_order'), 10, 1);
add_filter('woocommerce_address_to_edit', array($this, 'load_address_fields_values'), 10, 2);
        add_action('wp_footer', array($this, 'load_user_data_in_forms'));
        
        add_action('profile_update', array($this, 'sync_cuit_on_profile_update'));
add_filter('woocommerce_customer_data', array($this, 'add_cuit_to_customer_data'), 10, 3);
    }
public function register_ajax_handlers() {
    add_action('wp_ajax_mam_save_address', array($this, 'ajax_save_address'));
    add_action('wp_ajax_mam_delete_address', array($this, 'ajax_delete_address'));
    add_action('wp_ajax_mam_set_default_address', array($this, 'ajax_set_default_address'));
    add_action('wp_ajax_mam_get_saved_address', array($this, 'ajax_get_saved_address'));
     add_action('wp_ajax_mam_update_account', array($this, 'ajax_update_account'));
}
/**
 * Sincronizar CUIT con WooCommerce al actualizar perfil
 */
public function sync_cuit_on_profile_update($user_id) {
    // Obtener CUIT de cualquier fuente
    $cuit = get_user_meta($user_id, 'cuit', true);
    if (empty($cuit)) {
        $cuit = get_user_meta($user_id, 'billing_cuit', true);
    }
    
    // Sincronizar en todos los campos posibles
    if (!empty($cuit)) {
        update_user_meta($user_id, 'billing_cuit', $cuit);
        update_user_meta($user_id, 'cuit', $cuit);
        
        // También actualizar en los pedidos futuros
        add_user_meta($user_id, '_default_billing_cuit', $cuit, true);
    }
}

/**
 * Hook para WooCommerce customer data
 */
public function add_cuit_to_customer_data($customer_data, $customer, $data_store) {
    $customer_data['billing_cuit'] = $customer->get_meta('billing_cuit');
    $customer_data['cuit'] = $customer->get_meta('cuit');
    return $customer_data;
}
    /**
 * Manejo AJAX de actualización de cuenta que incluye CUIT y empresa
 */
public function ajax_update_account() {
    check_ajax_referer('mam-nonce', 'security');
    
    $user_id = get_current_user_id();
    $errors = array();
    
    // Validar y guardar datos básicos
    if (isset($_POST['account_first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['account_first_name']));
        update_user_meta($user_id, 'billing_first_name', sanitize_text_field($_POST['account_first_name']));
    }
    
    if (isset($_POST['account_last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['account_last_name']));
        update_user_meta($user_id, 'billing_last_name', sanitize_text_field($_POST['account_last_name']));
    }
    
    // Guardar empresa
    if (isset($_POST['account_company'])) {
        update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['account_company']));
    }
    
    // Validar y guardar CUIT
    if (isset($_POST['account_cuit'])) {
        $cuit = sanitize_text_field($_POST['account_cuit']);
        
        // Validar formato del CUIT
        if (empty($cuit)) {
            $errors[] = __('El campo CUIT es obligatorio.', 'my-account-manager');
        } else if (!$this->validate_cuit_format($cuit)) {
            $errors[] = __('El formato del CUIT no es válido.', 'my-account-manager');
        } else {
            update_user_meta($user_id, 'billing_cuit', $cuit);
        }
    }
    
    // Manejar cambio de email si está presente
    if (isset($_POST['account_email']) && is_email($_POST['account_email'])) {
        $email = sanitize_email($_POST['account_email']);
        
        if (email_exists($email) && email_exists($email) !== $user_id) {
            $errors[] = __('Esta dirección de correo electrónico ya está registrada.', 'my-account-manager');
        } else {
            wp_update_user(array('ID' => $user_id, 'user_email' => $email));
            update_user_meta($user_id, 'billing_email', $email);
        }
    }
    
    // Manejar cambio de contraseña si está presente
    if (!empty($_POST['password_current']) && !empty($_POST['password_1']) && !empty($_POST['password_2'])) {
        $current_user = get_user_by('id', $user_id);
        
        if (!wp_check_password($_POST['password_current'], $current_user->user_pass, $user_id)) {
            $errors[] = __('Su contraseña actual es incorrecta.', 'my-account-manager');
        }
        
        if ($_POST['password_1'] !== $_POST['password_2']) {
            $errors[] = __('Las nuevas contraseñas no coinciden.', 'my-account-manager');
        }
        
        if (empty($errors)) {
            wp_update_user(array('ID' => $user_id, 'user_pass' => $_POST['password_1']));
        }
    }
    
    // Responder con éxito o errores
    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode('<br>', $errors)
        ));
    } else {
        wp_send_json_success(array(
            'message' => __('Datos de cuenta actualizados correctamente.', 'my-account-manager')
        ));
    }
}
public function ajax_save_address() {
    check_ajax_referer('mam-nonce', 'security');
    
    $user_id = get_current_user_id();
    $action = isset($_POST['address_action']) ? wc_clean($_POST['address_action']) : '';
    $address_id = isset($_POST['address_id']) ? wc_clean($_POST['address_id']) : '';
    
    // Validar campos requeridos
    $required_fields = array(
        'address_name'      => __('Nombre de la dirección', 'my-account-manager'),
        'first_name'        => __('Nombre', 'my-account-manager'),
        'last_name'         => __('Apellidos', 'my-account-manager'),
        'country'           => __('País', 'my-account-manager'),
        'address_1'         => __('Dirección', 'my-account-manager'),
        'city'              => __('Ciudad', 'my-account-manager'),
        'postcode'          => __('Código Postal', 'my-account-manager'),
    );
    
    $errors = array();
    foreach ($required_fields as $field => $label) {
        $field_name = 'mam_address_' . $field;
        if (empty($_POST[$field_name])) {
            $errors[] = sprintf(__('El campo %s es obligatorio.', 'my-account-manager'), $label);
        }
    }
    
    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode('<br>', $errors)
        ));
        return;
    }
    
    // Obtener direcciones existentes
    $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
    if (!is_array($additional_addresses)) {
        $additional_addresses = array();
    }
    
    // Crear nuevo ID si estamos añadiendo
    if ($action === 'add' || empty($address_id)) {
        $address_id = 'addr_' . time() . '_' . wp_rand(100, 999);
    }
    
    // Preparar datos de la dirección
    $address = array(
        'name'       => sanitize_text_field($_POST['mam_address_name']),
        'first_name' => sanitize_text_field($_POST['mam_address_first_name']),
        'last_name'  => sanitize_text_field($_POST['mam_address_last_name']),
        'company'    => sanitize_text_field($_POST['mam_address_company']),
        'country'    => sanitize_text_field($_POST['mam_address_country']),
        'address_1'  => sanitize_text_field($_POST['mam_address_address_1']),
        'address_2'  => sanitize_text_field($_POST['mam_address_address_2']),
        'city'       => sanitize_text_field($_POST['mam_address_city']),
        'state'      => sanitize_text_field($_POST['mam_address_state']),
        'postcode'   => sanitize_text_field($_POST['mam_address_postcode']),
        'phone'      => sanitize_text_field($_POST['mam_address_phone']),
    );
    
    // Añadir CUIT si está presente
    if (isset($_POST['mam_address_cuit'])) {
        $address['cuit'] = sanitize_text_field($_POST['mam_address_cuit']);
    }
    
    // Guardar dirección
    $additional_addresses[$address_id] = $address;
    update_user_meta($user_id, '_mam_additional_addresses', $additional_addresses);
    
    // Renderizar HTML de la nueva lista de direcciones
    ob_start();
    // Renderizar la lista de direcciones actualizada
    // ...
    $html = ob_get_clean();
    
    // Mensaje de éxito
    $message = $action === 'add' ? 
        __('Dirección añadida correctamente.', 'my-account-manager') : 
        __('Dirección actualizada correctamente.', 'my-account-manager');
    
    wp_send_json_success(array(
        'message' => $message,
        'html' => $html,
        'address_id' => $address_id
    ));
}

    /**
     * Personalizar título de la página de direcciones
     */
    public function custom_addresses_title($title) {
        return __('Mis Direcciones', 'my-account-manager');
    }

    /**
     * Añadir contenido antes del formulario de direcciones
     */
    public function before_address_form() {
        ?>
        <div class="mam-addresses-header">
            <p><?php _e('Las siguientes direcciones se utilizarán de forma predeterminada en la página de pago.', 'my-account-manager'); ?></p>
        </div>
        <?php
    }

    /**
     * Añadir contenido después del formulario de direcciones
     */
    public function after_address_form() {
        ?>
        <div class="mam-addresses-footer">
            <div class="mam-addresses-help">
                <p><?php _e('Asegúrate de que tus direcciones estén actualizadas para evitar problemas con tus pedidos.', 'my-account-manager'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Personalizar campos predeterminados de dirección
     */
    public function customize_default_address_fields($fields) {
        // Reordenar campos
        $fields['first_name']['priority'] = 10;
        $fields['last_name']['priority'] = 20;
        $fields['company']['priority'] = 30;
        $fields['country']['priority'] = 40;
        $fields['address_1']['priority'] = 50;
        $fields['address_2']['priority'] = 60;
        $fields['city']['priority'] = 70;
        $fields['state']['priority'] = 80;
        $fields['postcode']['priority'] = 90;
        
        // Personalizar etiquetas y placeholders
        $fields['address_1']['label'] = __('Dirección', 'my-account-manager');
        $fields['address_1']['placeholder'] = __('Nombre de la calle y número', 'my-account-manager');
        $fields['address_2']['label'] = __('Información adicional', 'my-account-manager');
        $fields['address_2']['placeholder'] = __('Apartamento, suite, unidad, etc. (opcional)', 'my-account-manager');
        $fields['city']['label'] = __('Ciudad', 'my-account-manager');
        $fields['city']['placeholder'] = __('Nombre de la ciudad', 'my-account-manager');
        $fields['postcode']['label'] = __('Código Postal', 'my-account-manager');
        $fields['postcode']['placeholder'] = __('Código postal', 'my-account-manager');
        
        // Añadir clases para estilos personalizados
        foreach ($fields as $key => $field) {
            $fields[$key]['class'] = isset($field['class']) ? array_merge($field['class'], array('mam-form-field')) : array('mam-form-field');
        }
        
        return $fields;
    }

  /**
 * También mejorar la función customize_billing_fields para usar valores por defecto
 */
public function customize_billing_fields($fields) {
    // IMPORTANTE: Añadir valores predeterminados para CUIT y empresa
    $user_id = get_current_user_id();
    $cuit = get_user_meta($user_id, 'cuit', true) ?: get_user_meta($user_id, 'billing_cuit', true);
    $company = get_user_meta($user_id, 'company_name', true) ?: get_user_meta($user_id, 'billing_company', true);
    
    // Modificar campo CUIT
    $fields['billing_cuit'] = array(
        'label'       => __('CUIT', 'my-account-manager'),
        'placeholder' => __('Formato: xx-xxxxxxxx-x', 'my-account-manager'),
        'required'    => true,
        'class'       => array('mam-form-field', 'form-row-wide'),
        'clear'       => true,
        'priority'    => 115,
        'default'     => $cuit  // AÑADIDO: Valor predeterminado
    );
    
    // Modificar campo empresa
    if (isset($fields['billing_company'])) {
        $fields['billing_company']['default'] = $company; // AÑADIDO: Valor predeterminado
    }
    
    return $fields;
}
/**
 * Modificar los campos del formulario de edición de cuenta
 */
public function customize_account_fields($fields) {
    // Añadir campo de empresa al formulario de cuenta
    $fields['account_company'] = array(
        'type'        => 'text',
        'label'       => __('Empresa', 'my-account-manager'),
        'placeholder' => __('Nombre de su empresa', 'my-account-manager'),
        'required'    => false,
        'class'       => array('mam-form-field', 'form-row-wide'),
        'clear'       => true,
        'priority'    => 25, // Justo después del apellido
    );
    
    // Añadir campo CUIT al formulario de cuenta
    $fields['account_cuit'] = array(
        'type'        => 'text',
        'label'       => __('CUIT', 'my-account-manager'),
        'placeholder' => __('Formato: xx-xxxxxxxx-x', 'my-account-manager'),
        'required'    => true,
        'class'       => array('mam-form-field', 'form-row-wide'),
        'clear'       => true,
        'priority'    => 30, // Después de empresa
    );
    
    return $fields;
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
     * Personalizar campos de envío
     */
    public function customize_shipping_fields($fields) {
        // Añadir campo para CUIT en dirección de envío
$fields['shipping_cuit'] = array(
    'label'       => __('CUIT', 'my-account-manager'),
    'placeholder' => __('Formato: xx-xxxxxxxx-x', 'my-account-manager'),
    'required'    => false,
    'class'       => array('mam-form-field', 'form-row-wide'),
    'clear'       => true,
    'priority'    => 95,
);
        // Añadir o modificar campos específicos de envío
        if (isset($fields['shipping_phone'])) {
            $fields['shipping_phone']['label'] = __('Teléfono de contacto', 'my-account-manager');
            $fields['shipping_phone']['placeholder'] = __('Para contactarte sobre el envío', 'my-account-manager');
            $fields['shipping_phone']['required'] = true;
            $fields['shipping_phone']['priority'] = 100;
            $fields['shipping_phone']['class'] = array('mam-form-field', 'form-row-wide');
        } else {
            // Añadir campo de teléfono si no existe
            $fields['shipping_phone'] = array(
                'label'       => __('Teléfono de contacto', 'my-account-manager'),
                'placeholder' => __('Para contactarte sobre el envío', 'my-account-manager'),
                'required'    => true,
                'class'       => array('mam-form-field', 'form-row-wide'),
                'clear'       => true,
                'priority'    => 100,
            );
        }
        
        // Añadir campo para instrucciones de entrega
        $fields['shipping_delivery_notes'] = array(
            'label'       => __('Instrucciones de entrega', 'my-account-manager'),
            'placeholder' => __('Notas especiales para la entrega (opcional)', 'my-account-manager'),
            'required'    => false,
            'class'       => array('mam-form-field', 'form-row-wide'),
            'clear'       => true,
            'priority'    => 110,
            'type'        => 'textarea',
        );
        
        return $fields;
    }

    /**
     * Validación personalizada para direcciones
     */
    public function validate_address_fields($user_id, $load_address) {
        // Validar NIF/CIF/DNI en España
        if (isset($_POST['billing_country']) && 'ES' === $_POST['billing_country'] && !empty($_POST['billing_id_number'])) {
            $id_number = sanitize_text_field($_POST['billing_id_number']);
            
            // Validación básica de formato de NIF/CIF/DNI
            $valid_id = $this->validate_spanish_id($id_number);
            
            if (!$valid_id) {
                wc_add_notice(__('El formato del NIF/CIF/DNI no es válido.', 'my-account-manager'), 'error');
            }
        }
        
        // Validar número de teléfono
        if ($load_address === 'billing' && !empty($_POST['billing_phone'])) {
            $phone = sanitize_text_field($_POST['billing_phone']);
            
            // Validación básica de formato de teléfono
            if (!preg_match('/^[0-9+\s()-]{6,20}$/', $phone)) {
                wc_add_notice(__('Por favor, introduce un número de teléfono válido.', 'my-account-manager'), 'error');
            }
        }
        
        if ($load_address === 'shipping' && !empty($_POST['shipping_phone'])) {
            $phone = sanitize_text_field($_POST['shipping_phone']);
            
            // Validación básica de formato de teléfono
            if (!preg_match('/^[0-9+\s()-]{6,20}$/', $phone)) {
                wc_add_notice(__('Por favor, introduce un número de teléfono válido para el envío.', 'my-account-manager'), 'error');
            }
        }
    
// Validar CUIT
if (!empty($_POST['billing_cuit'])) {
    $cuit = sanitize_text_field($_POST['billing_cuit']);
    
    // Validación básica de formato de CUIT
    $valid_cuit = $this->validate_cuit_format($cuit);
    
    if (!$valid_cuit) {
        wc_add_notice(__('El formato del CUIT no es válido.', 'my-account-manager'), 'error');
    }
}
}
/**
 * Validar formato de CUIT - Versión mejorada
 */
private function validate_cuit_format($cuit) {
    // Eliminar guiones y espacios
    $cleanCuit = preg_replace('/[^0-9]/', '', $cuit);
    
    // Verificar longitud
    if (strlen($cleanCuit) !== 11) {
        return false;
    }
    
    // Verificación básica del formato XX-XXXXXXXX-X
    $pattern = '/^[0-9]{2}-?[0-9]{8}-?[0-9]$/';
    if (preg_match($pattern, $cuit)) {
        return true;
    }
    
    // Si no tiene guiones pero tiene la longitud correcta, también es válido
    if (strlen($cleanCuit) === 11) {
        return true;
    }
    
    return false;
}
/**
 * Formatear CUIT para guardarlo en formato estándar
 */
private function format_cuit($cuit) {
    // Eliminar cualquier formato existente
    $cleanCuit = preg_replace('/[^0-9]/', '', $cuit);
    
    // Si no tiene 11 dígitos, devolver como está
    if (strlen($cleanCuit) !== 11) {
        return $cuit;
    }
    
    // Aplicar formato XX-XXXXXXXX-X
    return substr($cleanCuit, 0, 2) . '-' . 
           substr($cleanCuit, 2, 8) . '-' . 
           substr($cleanCuit, 10, 1);
}
/**
 * Añadir esta función para guardar el CUIT en ambos lugares
 */
public function save_cuit_data($user_id, $cuit) {
    $formattedCuit = $this->format_cuit($cuit);
    
    // Guardar en metadatos personalizados
    update_user_meta($user_id, 'cuit', $formattedCuit);
    
    // Guardar en metadatos de facturación de WooCommerce
    update_user_meta($user_id, 'billing_cuit', $formattedCuit);
    
    return $formattedCuit;
}
    /**
     * Validar NIF/CIF/DNI español
     */
    private function validate_spanish_id($id) {
        // Implementación básica de validación
        // Una validación completa requeriría algo más sofisticado
        
        // Limpiar el ID de espacios y guiones
        $id = str_replace(array(' ', '-'), '', strtoupper($id));
        
        // Verificar longitud
        if (strlen($id) < 8 || strlen($id) > 9) {
            return false;
        }
        
        // Validar NIF (8 números + 1 letra)
        if (preg_match('/^[0-9]{8}[A-Z]$/', $id)) {
            return true;
        }
        
        // Validar NIE (X/Y/Z + 7 números + 1 letra)
        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $id)) {
            return true;
        }
        
        // Validar CIF (1 letra + 7 números + 1 letra/número)
        if (preg_match('/^[A-HJNPQRSUVW][0-9]{7}[A-J0-9]$/', $id)) {
            return true;
        }
        
        return false;
    }
/**
 * Adicionalmente, añadir esta función para facilitar la manipulación de direcciones
 */
public function load_address_fields_values($fields, $load_address) {
    $user_id = get_current_user_id();
    
    // Cargar CUIT desde los metadatos del usuario
    if (isset($fields['billing_cuit']) && $load_address == 'billing') {
        // Priorizar el valor guardado en billing_cuit, sino usar cuit
        $cuit = get_user_meta($user_id, 'billing_cuit', true);
        if (empty($cuit)) {
            $cuit = get_user_meta($user_id, 'cuit', true);
        }
        $fields['billing_cuit']['default'] = $cuit;
    }
    
    // Cargar empresa desde los metadatos del usuario
    if (isset($fields['billing_company']) && $load_address == 'billing') {
        $company = get_user_meta($user_id, 'billing_company', true);
        if (empty($company)) {
            $company = get_user_meta($user_id, 'company_name', true);
        }
        $fields['billing_company']['default'] = $company;
    }
    
    return $fields;
}
/**
 * En includes/modules/class-mam-addresses.php
 * 
 * Añadir nueva función para autocompletar campos en los formularios
 */

public function load_user_data_in_forms() {
    if (!is_account_page()) return;
    
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    // Obtener los mejores valores disponibles
    $cuit = get_user_meta($user_id, 'cuit', true) ?: 
            get_user_meta($user_id, 'billing_cuit', true);
            
    $company = get_user_meta($user_id, 'company_name', true) ?: 
              get_user_meta($user_id, 'billing_company', true);
    
    // Solo emitir el script si hay datos para autocompletar
    if (empty($cuit) && empty($company)) return;
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Autocompletar campos de empresa
        <?php if (!empty($company)) : ?>
        $('input[name="account_company"], input[name="account_company_name"], input[name="billing_company"]').val('<?php echo esc_js($company); ?>');
        <?php endif; ?>
        
        // Autocompletar campos de CUIT
        <?php if (!empty($cuit)) : ?>
        $('input[name="account_cuit"], input[name="billing_cuit"]').val('<?php echo esc_js($cuit); ?>');
        <?php endif; ?>
    });
    </script>
    <?php
}
    /**
     * Añadir clases a los campos del formulario
     */
    public function add_form_field_args($args, $key, $value) {
        // Añadir clases personalizadas a todos los campos
        $args['class'] = isset($args['class']) ? array_merge($args['class'], array('mam-input-field')) : array('mam-input-field');
        
        if (!empty($args['label'])) {
            $args['label_class'] = isset($args['label_class']) ? array_merge($args['label_class'], array('mam-field-label')) : array('mam-field-label');
        }
        
        // Modificar el diseño de campos específicos
        if (in_array($key, array('billing_first_name', 'shipping_first_name'))) {
            $args['class'][] = 'mam-field-first-name';
        }
        
        if (in_array($key, array('billing_last_name', 'shipping_last_name'))) {
            $args['class'][] = 'mam-field-last-name';
        }
        
        if (in_array($key, array('billing_address_1', 'shipping_address_1'))) {
            $args['class'][] = 'mam-field-address';
        }
        
        if (in_array($key, array('billing_postcode', 'shipping_postcode'))) {
            $args['class'][] = 'mam-field-postcode';
        }
        
        return $args;
    }

    /**
     * Añadir pestañas para las direcciones
     */
    public function add_address_tabs() {
        // Obtener dirección actual
        $load_address = isset($_GET['address']) ? wc_clean($_GET['address']) : 'billing';
        
        ?>
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
        <?php
    }

    /**
     * Añadir libreta de direcciones adicionales
     */
    public function add_additional_addresses() {
        // Verificar si estamos en la pestaña de direcciones adicionales
        $load_address = isset($_GET['address']) ? wc_clean($_GET['address']) : 'billing';
        
        if ($load_address !== 'additional') {
            return;
        }
        
        // Obtener direcciones adicionales guardadas
        $user_id = get_current_user_id();
        $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
        
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
                    <?php foreach ($additional_addresses as $address_id => $address) : ?>
                        <div class="mam-address-item">
                            <div class="mam-address-content">
                                <h4 class="mam-address-name">
                                    <?php echo esc_html($address['name']); ?>
                                </h4>
                                
                                <div class="mam-address-details">
                                    <?php
                                    // Formatear dirección
                                    $formatted_address = array(
                                        'first_name' => isset($address['first_name']) ? $address['first_name'] : '',
                                        'last_name'  => isset($address['last_name']) ? $address['last_name'] : '',
                                        'company'    => isset($address['company']) ? $address['company'] : '',
                                        'address_1'  => isset($address['address_1']) ? $address['address_1'] : '',
                                        'address_2'  => isset($address['address_2']) ? $address['address_2'] : '',
                                        'city'       => isset($address['city']) ? $address['city'] : '',
                                        'state'      => isset($address['state']) ? $address['state'] : '',
                                        'postcode'   => isset($address['postcode']) ? $address['postcode'] : '',
                                        'country'    => isset($address['country']) ? $address['country'] : '',
                                    );
                                    
                                    echo wp_kses_post(WC()->countries->get_formatted_address($formatted_address));
                                    
                                    if (!empty($address['phone'])) {
                                        echo '<br>' . esc_html__('Teléfono:', 'my-account-manager') . ' ' . esc_html($address['phone']);
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="mam-address-actions">
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'address_id' => $address_id), wc_get_endpoint_url('edit-address', 'additional'))); ?>" class="mam-button mam-button-secondary mam-edit-address">
                                    <?php _e('Editar', 'my-account-manager'); ?>
                                </a>
                                
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'delete', 'address_id' => $address_id, 'mam_nonce' => wp_create_nonce('delete_address_' . $address_id)), wc_get_endpoint_url('edit-address', 'additional'))); ?>" class="mam-button mam-button-danger mam-delete-address" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar esta dirección?', 'my-account-manager'); ?>');">
                                    <?php _e('Eliminar', 'my-account-manager'); ?>
                                </a>
                                
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'set-default', 'address_id' => $address_id, 'type' => 'shipping', 'mam_nonce' => wp_create_nonce('set_default_address_' . $address_id)), wc_get_endpoint_url('edit-address', 'additional'))); ?>" class="mam-button mam-button-primary mam-set-default">
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
            $action = isset($_GET['action']) ? wc_clean($_GET['action']) : '';
            $address_id = isset($_GET['address_id']) ? wc_clean($_GET['address_id']) : '';
            
            if ($action === 'add' || $action === 'edit') {
                $this->render_additional_address_form($action, $address_id, $additional_addresses);
            }
            ?>
        </div>
        <?php
    }
// También necesitamos añadir estos campos al registro
public function add_registration_fields() {
    ?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_billing_company"><?php _e('Empresa', 'my-account-manager'); ?></label>
        <input type="text" class="input-text" name="billing_company" id="reg_billing_company" value="<?php if (!empty($_POST['billing_company'])) echo esc_attr($_POST['billing_company']); ?>" />
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_billing_cuit"><?php _e('CUIT', 'my-account-manager'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_cuit" id="reg_billing_cuit" value="<?php if (!empty($_POST['billing_cuit'])) echo esc_attr($_POST['billing_cuit']); ?>" required />
    </p>
    <?php
}

// Validar campos del registro
public function validate_registration_fields($errors, $username, $email) {
    if (isset($_POST['billing_cuit']) && empty($_POST['billing_cuit'])) {
        $errors->add('billing_cuit_error', __('El campo CUIT es obligatorio.', 'my-account-manager'));
    }
    
    if (isset($_POST['billing_cuit']) && !empty($_POST['billing_cuit']) && !$this->validate_cuit_format($_POST['billing_cuit'])) {
        $errors->add('billing_cuit_error', __('El formato del CUIT no es válido.', 'my-account-manager'));
    }
    
    return $errors;
}

// Guardar campos del registro
public function save_registration_fields($customer_id) {
    if (isset($_POST['billing_company'])) {
        update_user_meta($customer_id, 'billing_company', sanitize_text_field($_POST['billing_company']));
    }
    
    if (isset($_POST['billing_cuit'])) {
        update_user_meta($customer_id, 'billing_cuit', sanitize_text_field($_POST['billing_cuit']));
    }
}
    /**
     * Renderizar formulario para direcciones adicionales
     */
    private function render_additional_address_form($action, $address_id, $additional_addresses) {
        // Valores por defecto
        $address = array(
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
            $address = wp_parse_args($additional_addresses[$address_id], $address);
        }
        
        // Título del formulario
        $form_title = $action === 'add' ? __('Añadir Nueva Dirección', 'my-account-manager') : __('Editar Dirección', 'my-account-manager');
        
        ?>
        <div class="mam-address-form-container">
            <h3><?php echo esc_html($form_title); ?></h3>
            
            <form method="post" class="mam-address-form">
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_name"><?php _e('Nombre de la dirección', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_name" id="mam_address_name" placeholder="<?php esc_attr_e('Ej. Casa, Oficina, Casa de Padres', 'my-account-manager'); ?>" value="<?php echo esc_attr($address['name']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-first">
                    <label for="mam_address_first_name"><?php _e('Nombre', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_first_name" id="mam_address_first_name" value="<?php echo esc_attr($address['first_name']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-last">
                    <label for="mam_address_last_name"><?php _e('Apellidos', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_last_name" id="mam_address_last_name" value="<?php echo esc_attr($address['last_name']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_company"><?php _e('Empresa', 'my-account-manager'); ?></label>
                    <input type="text" class="mam-input-field" name="mam_address_company" id="mam_address_company" value="<?php echo esc_attr($address['company']); ?>">
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_country"><?php _e('País', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <select name="mam_address_country" id="mam_address_country" class="mam-select-field" required>
                        <?php foreach (WC()->countries->get_shipping_countries() as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($address['country'], $code); ?>><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_address_1"><?php _e('Dirección', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_address_1" id="mam_address_address_1" placeholder="<?php esc_attr_e('Nombre de la calle y número', 'my-account-manager'); ?>" value="<?php echo esc_attr($address['address_1']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_address_2"><?php _e('Información adicional', 'my-account-manager'); ?></label>
                    <input type="text" class="mam-input-field" name="mam_address_address_2" id="mam_address_address_2" placeholder="<?php esc_attr_e('Apartamento, suite, unidad, etc. (opcional)', 'my-account-manager'); ?>" value="<?php echo esc_attr($address['address_2']); ?>">
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_city"><?php _e('Ciudad', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_city" id="mam_address_city" value="<?php echo esc_attr($address['city']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-first">
                    <label for="mam_address_state"><?php _e('Provincia', 'my-account-manager'); ?></label>
                    <input type="text" class="mam-input-field" name="mam_address_state" id="mam_address_state" value="<?php echo esc_attr($address['state']); ?>">
                </div>
                
                <div class="mam-form-row mam-form-row-last">
                    <label for="mam_address_postcode"><?php _e('Código Postal', 'my-account-manager'); ?> <span class="required">*</span></label>
                    <input type="text" class="mam-input-field" name="mam_address_postcode" id="mam_address_postcode" value="<?php echo esc_attr($address['postcode']); ?>" required>
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_address_phone"><?php _e('Teléfono', 'my-account-manager'); ?></label>
                    <input type="tel" class="mam-input-field" name="mam_address_phone" id="mam_address_phone" value="<?php echo esc_attr($address['phone']); ?>">
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
                <?php wp_nonce_field('mam_save_address', 'mam_address_nonce'); ?>
            </form>
        </div>
        <?php
    }
/**
 * Validar campo CUIT en el checkout
 */
public function validate_checkout_cuit($fields, $errors) {
    if (!empty($_POST['billing_cuit'])) {
        $cuit = sanitize_text_field($_POST['billing_cuit']);
        
        // Validación básica de formato de CUIT
        if (!$this->validate_cuit_format($cuit)) {
            $errors->add('billing_cuit_error', __('El formato del CUIT no es válido.', 'my-account-manager'));
        }
    } else if (isset($_POST['billing_country']) && $_POST['billing_country'] === 'AR') {
        // Si el país es Argentina, hacer el campo obligatorio
        $errors->add('billing_cuit_error', __('El campo CUIT es obligatorio para compradores de Argentina.', 'my-account-manager'));
    }
}
    /**
     * Manejar acciones para direcciones adicionales
     */
    public function handle_additional_address_actions() {
        // Solo en la página de mi cuenta y endpoint de direcciones
        if (!is_account_page() || !is_wc_endpoint_url('edit-address')) {
            return;
        }
        
        // Verificar si estamos guardando una dirección
        if (isset($_POST['mam_save_address']) && isset($_POST['mam_address_nonce']) && wp_verify_nonce($_POST['mam_address_nonce'], 'mam_save_address')) {
            $this->save_additional_address();
        }
        
        // Verificar si estamos eliminando una dirección
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['address_id']) && isset($_GET['mam_nonce'])) {
            $address_id = wc_clean($_GET['address_id']);
            
            if (wp_verify_nonce($_GET['mam_nonce'], 'delete_address_' . $address_id)) {
                $this->delete_additional_address($address_id);
            }
        }
        
        // Verificar si estamos estableciendo una dirección como predeterminada
        if (isset($_GET['action']) && $_GET['action'] === 'set-default' && isset($_GET['address_id']) && isset($_GET['type']) && isset($_GET['mam_nonce'])) {
            $address_id = wc_clean($_GET['address_id']);
            $type = wc_clean($_GET['type']);
            
            if (wp_verify_nonce($_GET['mam_nonce'], 'set_default_address_' . $address_id)) {
                $this->set_default_address($address_id, $type);
            }
        }
    }
/**
 * Mostrar el CUIT en la página de pedido y correos
 */
public function display_cuit_in_order($order) {
    $order_id = $order->get_id();
    $cuit = get_post_meta($order_id, '_billing_cuit', true);
    
    if (!empty($cuit)) {
        echo '<p><strong>' . __('CUIT:', 'my-account-manager') . '</strong> ' . esc_html($cuit) . '</p>';
    }
}
/**
 * Añadir CUIT a los datos de administración de pedidos
 */
public function add_cuit_to_admin_order($order) {
    $order_id = $order->get_id();
    $cuit = get_post_meta($order_id, '_billing_cuit', true);
    
    echo '<p><strong>' . __('CUIT:', 'my-account-manager') . '</strong> ' . esc_html($cuit) . '</p>';
}
    /**
     * Guardar dirección adicional
     */
    private function save_additional_address() {
    $user_id = get_current_user_id();
    
    // Obtener datos del formulario
    $action = isset($_POST['mam_address_action']) ? wc_clean($_POST['mam_address_action']) : '';
    $address_id = isset($_POST['mam_address_id']) ? wc_clean($_POST['mam_address_id']) : '';
    
    // Validar campos requeridos
    $required_fields = array(
        'mam_address_name'      => __('Nombre de la dirección', 'my-account-manager'),
        'mam_address_first_name' => __('Nombre', 'my-account-manager'),
        'mam_address_last_name'  => __('Apellidos', 'my-account-manager'),
        'mam_address_country'    => __('País', 'my-account-manager'),
        'mam_address_address_1'  => __('Dirección', 'my-account-manager'),
        'mam_address_city'       => __('Ciudad', 'my-account-manager'),
        'mam_address_postcode'   => __('Código Postal', 'my-account-manager'),
    );
    
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            wc_add_notice(sprintf(__('El campo %s es obligatorio.', 'my-account-manager'), $label), 'error');
            return;
        }
    }
    
    // Obtener direcciones existentes
    $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
    
    if (!is_array($additional_addresses)) {
        $additional_addresses = array();
    }
    
    // Crear nuevo ID si estamos añadiendo
    if ($action === 'add' || empty($address_id)) {
        $address_id = 'addr_' . time() . '_' . wp_rand(100, 999);
    }
    
    // Preparar datos de la dirección
    $address = array(
        'name'       => sanitize_text_field($_POST['mam_address_name']),
        'first_name' => sanitize_text_field($_POST['mam_address_first_name']),
        'last_name'  => sanitize_text_field($_POST['mam_address_last_name']),
        'company'    => sanitize_text_field($_POST['mam_address_company']),
        'country'    => sanitize_text_field($_POST['mam_address_country']),
        'address_1'  => sanitize_text_field($_POST['mam_address_address_1']),
        'address_2'  => sanitize_text_field($_POST['mam_address_address_2']),
        'city'       => sanitize_text_field($_POST['mam_address_city']),
        'state'      => sanitize_text_field($_POST['mam_address_state']),
        'postcode'   => sanitize_text_field($_POST['mam_address_postcode']),
        'phone'      => sanitize_text_field($_POST['mam_address_phone']),
    );
    
    // Incluir CUIT si está presente
    if (isset($_POST['mam_address_cuit'])) {
        $address['cuit'] = sanitize_text_field($_POST['mam_address_cuit']);
    }
    
    // Guardar dirección
    $additional_addresses[$address_id] = $address;
    update_user_meta($user_id, '_mam_additional_addresses', $additional_addresses);
    
    // Mensaje de éxito
    $message = $action === 'add' ? __('Dirección añadida correctamente.', 'my-account-manager') : __('Dirección actualizada correctamente.', 'my-account-manager');
    wc_add_notice($message, 'success');
    
    // Redireccionar para evitar reenvío del formulario
    wp_redirect(wc_get_endpoint_url('edit-address', 'additional'));
    exit;
}

    /**
     * Eliminar dirección adicional
     */
    private function delete_additional_address($address_id) {
        $user_id = get_current_user_id();
        
        // Obtener direcciones existentes
        $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
        
        if (!is_array($additional_addresses) || !isset($additional_addresses[$address_id])) {
            wc_add_notice(__('La dirección no existe.', 'my-account-manager'), 'error');
            return;
        }
        
        // Eliminar dirección
        unset($additional_addresses[$address_id]);
        update_user_meta($user_id, '_mam_additional_addresses', $additional_addresses);
        
        // Mensaje de éxito
        wc_add_notice(__('Dirección eliminada correctamente.', 'my-account-manager'), 'success');
        
        // Redireccionar
        wp_redirect(wc_get_endpoint_url('edit-address', 'additional'));
        exit;
    }
/**
 * Añadir campos personalizados al checkout
 */
public function add_checkout_fields($fields) {
    // Añadir campo CUIT a la sección de facturación en checkout
    $fields['billing']['billing_cuit'] = array(
        'label'       => __('CUIT', 'my-account-manager'),
        'placeholder' => __('Formato: xx-xxxxxxxx-x', 'my-account-manager'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'priority'    => 115, // Posicionarlo después de la empresa y antes del email
    );
    
    return $fields;
}
    /**
     * Establecer dirección como predeterminada
     */
    private function set_default_address($address_id, $type) {
        $user_id = get_current_user_id();
        
        // Validar tipo
        if (!in_array($type, array('billing', 'shipping'))) {
            wc_add_notice(__('Tipo de dirección no válido.', 'my-account-manager'), 'error');
            return;
        }
        
        // Obtener direcciones existentes
        $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
        
        if (!is_array($additional_addresses) || !isset($additional_addresses[$address_id])) {
            wc_add_notice(__('La dirección no existe.', 'my-account-manager'), 'error');
            return;
        }
        
        // Obtener datos de la dirección
        $address = $additional_addresses[$address_id];
        
        // Mapear campos a WooCommerce
        $field_map = array(
            'first_name' => 'first_name',
            'last_name'  => 'last_name',
            'company'    => 'company',
            'country'    => 'country',
            'address_1'  => 'address_1',
            'address_2'  => 'address_2',
            'city'       => 'city',
            'state'      => 'state',
            'postcode'   => 'postcode',
            'phone'      => 'phone',
            'cuit'       => 'cuit',
        );
        
        // Actualizar direcciones predeterminadas
        foreach ($field_map as $from => $to) {
            if (isset($address[$from])) {
                update_user_meta($user_id, $type . '_' . $to, $address[$from]);
            }
        }
        
        // Mensaje de éxito
        $type_label = $type === 'billing' ? __('facturación', 'my-account-manager') : __('envío', 'my-account-manager');
        wc_add_notice(sprintf(__('Dirección establecida como predeterminada para %s.', 'my-account-manager'), $type_label), 'success');
        
        // Redireccionar
        wp_redirect(wc_get_endpoint_url('edit-address', $type));
        exit;
    }

    /**
     * Enqueue scripts para autocompletado de direcciones
     */
    public function enqueue_address_scripts() {
        // Solo en páginas relevantes
        if (!is_account_page() && !is_checkout()) {
            return;
        }
        
        // Ejemplo básico usando API de Google Places (requiere clave de API)
        if (is_account_page() && is_wc_endpoint_url('edit-address')) {
            $api_key = get_option('mam_google_places_api_key');
            
            if (!empty($api_key)) {
                wp_enqueue_script('google-places', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', array(), null, true);
                wp_enqueue_script('mam-address-autocomplete', MAM_PLUGIN_URL . 'assets/js/address-autocomplete.js', array('jquery', 'google-places'), MAM_VERSION, true);
            }
        }
    }

    /**
     * Añadir opción para copiar dirección
     */
    public function add_copy_address_option() {
        if (!is_user_logged_in()) {
            return;
        }
        
        ?>
        <div class="mam-copy-address-option">
            <label class="mam-checkbox">
                <input id="mam_copy_billing_address" type="checkbox" />
                <span class="mam-checkbox-label">
                    <?php _e('Usar mi dirección de facturación como dirección de envío', 'my-account-manager'); ?>
                </span>
            </label>
            
            <?php
            // Si el usuario tiene direcciones adicionales, mostrar selector
            $user_id = get_current_user_id();
            $additional_addresses = get_user_meta($user_id, '_mam_additional_addresses', true);
            
            if (is_array($additional_addresses) && !empty($additional_addresses)) {
                ?>
                <div class="mam-form-row mam-form-row-wide">
                    <label for="mam_saved_addresses"><?php _e('O selecciona una dirección guardada:', 'my-account-manager'); ?></label>
                    <select id="mam_saved_addresses" class="mam-select-field">
                        <option value=""><?php _e('Seleccionar dirección...', 'my-account-manager'); ?></option>
                        <?php foreach ($additional_addresses as $address_id => $address) : ?>
                            <option value="<?php echo esc_attr($address_id); ?>">
                                <?php echo esc_html($address['name']); ?> - 
                                <?php echo esc_html($address['address_1']); ?>, 
                                <?php echo esc_html($address['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
}

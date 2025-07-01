<?php
/**
 * Configuración de CUIT para My Account Manager
 * 
 * Agregar esto al archivo de configuración del plugin o crear un nuevo archivo
 * includes/admin/class-mam-cuit-settings.php
 */

// Agregar opciones de configuración al panel de administración
add_filter('mam_admin_settings_fields', 'mam_add_cuit_validation_settings', 10, 1);

function mam_add_cuit_validation_settings($settings) {
    // Agregar nueva sección de CUIT si no existe
    $settings['cuit'] = array(
        'title' => __('Configuración CUIT', 'my-account-manager'),
        'fields' => array(
            'cuit_validation_type' => array(
                'title' => __('Tipo de validación de CUIT', 'my-account-manager'),
                'type' => 'select',
                'default' => 'flexible',
                'options' => array(
                    'strict' => __('Estricto - Requiere formato XX-XXXXXXXX-X', 'my-account-manager'),
                    'flexible' => __('Flexible - Acepta con o sin guiones', 'my-account-manager'),
                    'numbers_only' => __('Solo números - Acepta 11 dígitos sin guiones', 'my-account-manager'),
                ),
                'desc' => __('Selecciona cómo se validará el formato del CUIT', 'my-account-manager')
            ),
            'cuit_auto_format' => array(
                'title' => __('Formateo automático', 'my-account-manager'),
                'type' => 'checkbox',
                'default' => 'yes',
                'label' => __('Formatear automáticamente el CUIT mientras se escribe', 'my-account-manager'),
                'desc' => __('Agrega guiones automáticamente al escribir el CUIT', 'my-account-manager')
            ),
            'cuit_validate_checksum' => array(
                'title' => __('Validar dígito verificador', 'my-account-manager'),
                'type' => 'checkbox',
                'default' => 'yes',
                'label' => __('Validar el dígito verificador del CUIT', 'my-account-manager'),
                'desc' => __('Verifica que el último dígito del CUIT sea correcto según el algoritmo oficial', 'my-account-manager')
            ),
            'cuit_required' => array(
                'title' => __('CUIT obligatorio', 'my-account-manager'),
                'type' => 'checkbox',
                'default' => 'yes',
                'label' => __('El CUIT es obligatorio en el registro y checkout', 'my-account-manager'),
                'desc' => __('Si está desactivado, el campo CUIT será opcional', 'my-account-manager')
            ),
            'cuit_regex_pattern' => array(
                'title' => __('Patrón Regex personalizado', 'my-account-manager'),
                'type' => 'text',
                'default' => '',
                'desc' => __('Deja vacío para usar los patrones predeterminados. Ejemplo: ^\d{2,3}-\d{7,8}-\d$', 'my-account-manager'),
                'placeholder' => '^\d{2}-\d{8}-\d$'
            )
        )
    );
    
    return $settings;
}

/**
 * Función helper para obtener el tipo de validación configurado
 */
function mam_get_cuit_validation_type() {
    return get_option('mam_cuit_validation_type', 'flexible');
}

/**
 * Función mejorada de validación que usa la configuración
 */
function mam_validate_cuit_with_config($cuit) {
    $validation_type = mam_get_cuit_validation_type();
    $custom_regex = get_option('mam_cuit_regex_pattern', '');
    
    // Si hay un regex personalizado, usarlo primero
    if (!empty($custom_regex)) {
        return preg_match('/' . $custom_regex . '/', $cuit);
    }
    
    // Usar el tipo de validación configurado
    switch ($validation_type) {
        case 'strict':
            // Solo acepta formato con guiones obligatorios
            return preg_match('/^\d{2}-\d{8}-\d$/', $cuit);
            
        case 'numbers_only':
            // Solo acepta números sin guiones
            $cuit_numbers = preg_replace('/[^0-9]/', '', $cuit);
            return preg_match('/^\d{11}$/', $cuit_numbers);
            
        case 'flexible':
        default:
            // Acepta cualquier formato válido
            if (preg_match('/^\d{2}-\d{8}-\d$/', $cuit)) {
                return true;
            }
            if (preg_match('/^\d{2}[-]?\d{8}[-]?\d$/', $cuit)) {
                return true;
            }
            $cuit_numbers = preg_replace('/[^0-9]/', '', $cuit);
            if (preg_match('/^\d{11}$/', $cuit_numbers)) {
                return true;
            }
            return false;
    }
}

/**
 * Agregar script para pasar la configuración al frontend
 */
add_action('wp_enqueue_scripts', 'mam_cuit_localize_settings');

function mam_cuit_localize_settings() {
    if (is_account_page() || is_checkout()) {
        wp_localize_script('mam-scripts', 'mam_cuit_settings', array(
            'validation_type' => mam_get_cuit_validation_type(),
            'auto_format' => get_option('mam_cuit_auto_format', 'yes') === 'yes',
            'custom_regex' => get_option('mam_cuit_regex_pattern', ''),
            'is_required' => get_option('mam_cuit_required', 'yes') === 'yes',
            'error_message' => __('El formato del CUIT no es válido', 'my-account-manager'),
            'format_hint' => __('Formato: XX-XXXXXXXX-X', 'my-account-manager')
        ));
    }
}

/**
 * Ejemplos de uso de las expresiones regulares para CUIT
 */
class MAM_CUIT_Regex_Examples {
    
    // Regex estricto: exactamente 2 dígitos, guion, 8 dígitos, guion, 1 dígito
    const REGEX_STRICT = '/^\d{2}-\d{8}-\d$/';
    
    // Regex flexible: guiones opcionales
    const REGEX_FLEXIBLE = '/^\d{2}[-]?\d{8}[-]?\d$/';
    
    // Regex solo números: 11 dígitos consecutivos
    const REGEX_NUMBERS_ONLY = '/^\d{11}$/';
    
    // Regex para 2 o 3 dígitos iniciales (si fuera necesario)
    const REGEX_VARIABLE_PREFIX = '/^\d{2,3}-\d{7,8}-\d$/';
    
    // Regex ultra flexible: acepta varios formatos
    const REGEX_ULTRA_FLEXIBLE = '/^(\d{2}[-\s]?\d{8}[-\s]?\d|\d{11})$/';
    
    /**
     * Validar CUIT con diferentes niveles de flexibilidad
     */
    public static function validate($cuit, $level = 'flexible') {
        switch ($level) {
            case 'strict':
                return preg_match(self::REGEX_STRICT, $cuit);
            case 'numbers':
                $clean = preg_replace('/[^0-9]/', '', $cuit);
                return preg_match(self::REGEX_NUMBERS_ONLY, $clean);
            case 'ultra_flexible':
                return preg_match(self::REGEX_ULTRA_FLEXIBLE, $cuit);
            case 'flexible':
            default:
                return preg_match(self::REGEX_FLEXIBLE, $cuit);
        }
    }
    
    /**
     * Ejemplos de CUITs válidos
     */
    public static function get_valid_examples() {
        return array(
            '20-12345678-9',  // Formato estándar con guiones
            '20123456789',    // Sin guiones
            '27-87654321-0',  // Otro ejemplo con guiones
            '30-71234567-8',  // Empresa con guiones
            '33987654321',    // Empresa sin guiones
        );
    }
}

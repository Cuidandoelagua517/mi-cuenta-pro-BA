<?php
/**
 * MAM Admin CUIT
 * 
 * Agrega funcionalidad para mostrar y gestionar el CUIT en el dashboard de usuarios de WooCommerce
 *
 * @package MyAccountManager
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase MAM_Admin_CUIT
 */
class MAM_Admin_CUIT {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct() {
        // Agregar columna CUIT al listado de usuarios
        add_filter('manage_users_columns', array($this, 'add_cuit_column'));
        add_filter('manage_users_custom_column', array($this, 'show_cuit_column_content'), 10, 3);
        
        // Hacer la columna ordenable
        add_filter('manage_users_sortable_columns', array($this, 'make_cuit_column_sortable'));
        add_action('pre_get_users', array($this, 'cuit_column_orderby'));
        
        // Agregar búsqueda por CUIT
        add_action('pre_user_query', array($this, 'cuit_search'));
        
        // Agregar campo CUIT al perfil de usuario en admin
        add_action('show_user_profile', array($this, 'add_cuit_field_to_profile'));
        add_action('edit_user_profile', array($this, 'add_cuit_field_to_profile'));
        add_action('user_new_form', array($this, 'add_cuit_field_to_new_user'));
        
        // Guardar campo CUIT desde el perfil de usuario
        add_action('personal_options_update', array($this, 'save_cuit_field_from_profile'));
        add_action('edit_user_profile_update', array($this, 'save_cuit_field_from_profile'));
        add_action('user_register', array($this, 'save_cuit_field_for_new_user'));
        
        // Agregar edición rápida en el listado de usuarios
        add_filter('user_row_actions', array($this, 'add_quick_edit_cuit_link'), 10, 2);
        add_action('admin_footer', array($this, 'quick_edit_cuit_javascript'));
        add_action('wp_ajax_save_quick_edit_cuit', array($this, 'save_quick_edit_cuit'));
        
        // Agregar exportación masiva
        add_filter('bulk_actions-users', array($this, 'add_bulk_export_cuit'));
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_export_cuit'), 10, 3);
        
        // Agregar estilos para el admin
        add_action('admin_head', array($this, 'add_admin_styles'));
        
        // Agregar filtro por rol en el listado de usuarios
        add_action('restrict_manage_users', array($this, 'add_cuit_filter'));
        add_filter('pre_get_users', array($this, 'filter_users_by_cuit'));
    }

    /**
     * Obtener instancia
     */
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Agregar columna CUIT al listado de usuarios
     */
    public function add_cuit_column($columns) {
        // Insertar después de la columna de email
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'email') {
                $new_columns['cuit'] = __('CUIT', 'my-account-manager');
                $new_columns['company'] = __('Empresa', 'my-account-manager');
            }
        }
        return $new_columns;
    }

    /**
     * Mostrar contenido de la columna CUIT
     */
    public function show_cuit_column_content($value, $column_name, $user_id) {
        if ('cuit' === $column_name) {
            $cuit = $this->get_user_cuit($user_id);
            if ($cuit) {
                // Mostrar CUIT con formato y opción de edición rápida
                $value = '<span class="mam-cuit-value" data-user-id="' . esc_attr($user_id) . '">' . esc_html($cuit) . '</span>';
                $value .= ' <a href="#" class="mam-quick-edit-cuit" data-user-id="' . esc_attr($user_id) . '" style="display:none;">' . __('Editar', 'my-account-manager') . '</a>';
            } else {
                $value = '<span class="mam-cuit-empty" data-user-id="' . esc_attr($user_id) . '">—</span>';
                $value .= ' <a href="#" class="mam-quick-add-cuit" data-user-id="' . esc_attr($user_id) . '">' . __('Agregar', 'my-account-manager') . '</a>';
            }
        } elseif ('company' === $column_name) {
            $company = $this->get_user_company($user_id);
            $value = $company ? esc_html($company) : '—';
        }
        return $value;
    }

    /**
     * Obtener CUIT del usuario
     */
    private function get_user_cuit($user_id) {
        // Buscar en múltiples ubicaciones posibles
        $cuit = get_user_meta($user_id, 'billing_cuit', true);
        if (empty($cuit)) {
            $cuit = get_user_meta($user_id, 'cuit', true);
        }
        return $cuit;
    }

    /**
     * Obtener empresa del usuario
     */
    private function get_user_company($user_id) {
        $company = get_user_meta($user_id, 'billing_company', true);
        if (empty($company)) {
            $company = get_user_meta($user_id, 'company_name', true);
        }
        return $company;
    }

    /**
     * Hacer columna CUIT ordenable
     */
    public function make_cuit_column_sortable($columns) {
        $columns['cuit'] = 'cuit';
        $columns['company'] = 'company';
        return $columns;
    }

    /**
     * Ordenar por CUIT
     */
    public function cuit_column_orderby($query) {
        if (!is_admin()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ('cuit' === $orderby) {
            $query->set('meta_key', 'billing_cuit');
            $query->set('orderby', 'meta_value');
        } elseif ('company' === $orderby) {
            $query->set('meta_key', 'billing_company');
            $query->set('orderby', 'meta_value');
        }
    }

    /**
     * Buscar usuarios por CUIT
     */
    public function cuit_search($query) {
        global $wpdb;

        if (!is_admin() || empty($_REQUEST['s'])) {
            return;
        }

        $search = trim($_REQUEST['s']);
        
        // Si parece un CUIT (contiene números y guiones)
        if (preg_match('/[0-9\-]+/', $search)) {
            $query->query_from .= " LEFT JOIN {$wpdb->usermeta} AS cuit_meta ON ({$wpdb->users}.ID = cuit_meta.user_id AND cuit_meta.meta_key IN ('billing_cuit', 'cuit'))";
            $query->query_where = str_replace(
                "WHERE 1=1",
                "WHERE 1=1 AND (cuit_meta.meta_value LIKE '%{$search}%' OR {$wpdb->users}.user_login LIKE '%{$search}%' OR {$wpdb->users}.user_email LIKE '%{$search}%')",
                $query->query_where
            );
        }
    }

    /**
     * Agregar campo CUIT al perfil de usuario
     */
    public function add_cuit_field_to_profile($user) {
        $cuit = $this->get_user_cuit($user->ID);
        $company = $this->get_user_company($user->ID);
        ?>
        <h3><?php _e('Información Fiscal', 'my-account-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="billing_company"><?php _e('Empresa', 'my-account-manager'); ?></label></th>
                <td>
                    <input type="text" name="billing_company" id="billing_company" value="<?php echo esc_attr($company); ?>" class="regular-text" />
                    <p class="description"><?php _e('Nombre de la empresa asociada al usuario.', 'my-account-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="billing_cuit"><?php _e('CUIT', 'my-account-manager'); ?></label></th>
                <td>
                    <input type="text" name="billing_cuit" id="billing_cuit" value="<?php echo esc_attr($cuit); ?>" class="regular-text" placeholder="XX-XXXXXXXX-X" />
                    <p class="description"><?php _e('Clave Única de Identificación Tributaria (formato: XX-XXXXXXXX-X)', 'my-account-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Agregar campo CUIT al formulario de nuevo usuario
     */
    public function add_cuit_field_to_new_user($operation) {
        if ('add-new-user' !== $operation) {
            return;
        }
        ?>
        <h3><?php _e('Información Fiscal', 'my-account-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="billing_company"><?php _e('Empresa', 'my-account-manager'); ?></label></th>
                <td>
                    <input type="text" name="billing_company" id="billing_company" value="" class="regular-text" />
                    <p class="description"><?php _e('Nombre de la empresa asociada al usuario.', 'my-account-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="billing_cuit"><?php _e('CUIT', 'my-account-manager'); ?></label></th>
                <td>
                    <input type="text" name="billing_cuit" id="billing_cuit" value="" class="regular-text" placeholder="XX-XXXXXXXX-X" />
                    <p class="description"><?php _e('Clave Única de Identificación Tributaria (formato: XX-XXXXXXXX-X)', 'my-account-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guardar campo CUIT desde el perfil
     */
    public function save_cuit_field_from_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (isset($_POST['billing_cuit'])) {
            $cuit = sanitize_text_field($_POST['billing_cuit']);
            
            // Validar formato si no está vacío
            if (!empty($cuit) && !$this->validate_cuit_format($cuit)) {
                add_action('user_profile_update_errors', function($errors) {
                    $errors->add('invalid_cuit', __('El formato del CUIT no es válido. Debe ser XX-XXXXXXXX-X', 'my-account-manager'));
                });
                return;
            }
            
            // Guardar en ambos campos para compatibilidad
            update_user_meta($user_id, 'billing_cuit', $cuit);
            update_user_meta($user_id, 'cuit', $cuit);
        }

        if (isset($_POST['billing_company'])) {
            $company = sanitize_text_field($_POST['billing_company']);
            update_user_meta($user_id, 'billing_company', $company);
            update_user_meta($user_id, 'company_name', $company);
        }
    }

    /**
     * Guardar CUIT para nuevo usuario
     */
    public function save_cuit_field_for_new_user($user_id) {
        if (isset($_POST['billing_cuit'])) {
            $cuit = sanitize_text_field($_POST['billing_cuit']);
            update_user_meta($user_id, 'billing_cuit', $cuit);
            update_user_meta($user_id, 'cuit', $cuit);
        }

        if (isset($_POST['billing_company'])) {
            $company = sanitize_text_field($_POST['billing_company']);
            update_user_meta($user_id, 'billing_company', $company);
            update_user_meta($user_id, 'company_name', $company);
        }
    }

    /**
     * Validar formato de CUIT
     */
    private function validate_cuit_format($cuit) {
        // Eliminar espacios y guiones
        $cuit = str_replace(array(' ', '-'), '', $cuit);
        
        // Verificar que tenga exactamente 11 dígitos
        if (!preg_match('/^[0-9]{11}$/', $cuit)) {
            return false;
        }
        
        // Validación del dígito verificador (algoritmo oficial AFIP)
        $base = array(5, 4, 3, 2, 7, 6, 5, 4, 3, 2);
        $aux = 0;
        for ($i = 0; $i < 10; $i++) {
            $aux += $cuit[$i] * $base[$i];
        }
        $aux = 11 - ($aux % 11);
        if ($aux == 11) {
            $aux = 0;
        }
        if ($aux == 10) {
            $aux = 9;
        }
        
        return $aux == $cuit[10];
    }

    /**
     * Agregar link de edición rápida
     */
    public function add_quick_edit_cuit_link($actions, $user) {
        if (current_user_can('edit_users')) {
            $actions['edit_cuit'] = '<a href="#" class="mam-edit-cuit-inline" data-user-id="' . $user->ID . '">' . __('Editar CUIT', 'my-account-manager') . '</a>';
        }
        return $actions;
    }

    /**
     * JavaScript para edición rápida
     */
    public function quick_edit_cuit_javascript() {
        if (get_current_screen()->id !== 'users') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Edición rápida de CUIT
            $(document).on('click', '.mam-quick-edit-cuit, .mam-quick-add-cuit, .mam-edit-cuit-inline', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var $cell = $('.mam-cuit-value[data-user-id="' + userId + '"], .mam-cuit-empty[data-user-id="' + userId + '"]').parent();
                var currentValue = $('.mam-cuit-value[data-user-id="' + userId + '"]').text() || '';
                
                // Crear campo de edición
                var $input = $('<input type="text" class="mam-cuit-edit" placeholder="XX-XXXXXXXX-X" value="' + currentValue + '" />');
                var $saveBtn = $('<button class="button button-small mam-save-cuit">' + '<?php _e('Guardar', 'my-account-manager'); ?>' + '</button>');
                var $cancelBtn = $('<a href="#" class="mam-cancel-cuit">' + '<?php _e('Cancelar', 'my-account-manager'); ?>' + '</a>');
                
                $cell.html($input).append(' ').append($saveBtn).append(' ').append($cancelBtn);
                $input.focus();
                
                // Guardar al presionar Enter
                $input.on('keypress', function(e) {
                    if (e.which === 13) {
                        $saveBtn.click();
                    }
                });
                
                // Botón guardar
                $saveBtn.on('click', function() {
                    var newCuit = $input.val();
                    
                    $.post(ajaxurl, {
                        action: 'save_quick_edit_cuit',
                        user_id: userId,
                        cuit: newCuit,
                        _wpnonce: '<?php echo wp_create_nonce('edit_cuit'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php _e('Error al guardar el CUIT', 'my-account-manager'); ?>');
                        }
                    });
                });
                
                // Botón cancelar
                $cancelBtn.on('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
            });
            
            // Hover para mostrar editar
            $(document).on('mouseenter', 'td.cuit', function() {
                $(this).find('.mam-quick-edit-cuit').show();
            }).on('mouseleave', 'td.cuit', function() {
                $(this).find('.mam-quick-edit-cuit').hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Guardar CUIT mediante AJAX
     */
    public function save_quick_edit_cuit() {
        check_ajax_referer('edit_cuit');
        
        if (!current_user_can('edit_users')) {
            wp_die(-1);
        }
        
        $user_id = intval($_POST['user_id']);
        $cuit = sanitize_text_field($_POST['cuit']);
        
        // Validar formato si no está vacío
        if (!empty($cuit) && !$this->validate_cuit_format($cuit)) {
            wp_send_json_error(__('El formato del CUIT no es válido. Debe ser XX-XXXXXXXX-X', 'my-account-manager'));
        }
        
        // Guardar en ambos campos
        update_user_meta($user_id, 'billing_cuit', $cuit);
        update_user_meta($user_id, 'cuit', $cuit);
        
        wp_send_json_success();
    }

    /**
     * Agregar acción masiva de exportación
     */
    public function add_bulk_export_cuit($bulk_actions) {
        $bulk_actions['export_cuit'] = __('Exportar CUIT a CSV', 'my-account-manager');
        return $bulk_actions;
    }

    /**
     * Manejar exportación masiva
     */
    public function handle_bulk_export_cuit($redirect_to, $action, $user_ids) {
        if ($action !== 'export_cuit') {
            return $redirect_to;
        }

        if (empty($user_ids)) {
            return $redirect_to;
        }

        // Generar CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=usuarios-cuit-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, array('ID', 'Usuario', 'Email', 'Empresa', 'CUIT'));
        
        // Datos
        foreach ($user_ids as $user_id) {
            $user = get_user_by('id', $user_id);
            $cuit = $this->get_user_cuit($user_id);
            $company = $this->get_user_company($user_id);
            
            fputcsv($output, array(
                $user_id,
                $user->user_login,
                $user->user_email,
                $company,
                $cuit
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Agregar estilos personalizados
     */
    public function add_admin_styles() {
        if (get_current_screen()->id !== 'users') {
            return;
        }
        ?>
        <style>
            .column-cuit { width: 12%; }
            .column-company { width: 15%; }
            .mam-cuit-edit { width: 120px; }
            .mam-quick-edit-cuit { font-size: 12px; }
            .mam-cuit-empty { color: #999; }
            .mam-save-cuit { margin-left: 5px; }
            .mam-cancel-cuit { margin-left: 5px; font-size: 12px; }
            .mam-cuit-filter { margin-left: 5px; }
        </style>
        <?php
    }

    /**
     * Agregar filtro por CUIT vacío/lleno
     */
    public function add_cuit_filter() {
        $selected = isset($_GET['cuit_filter']) ? $_GET['cuit_filter'] : '';
        ?>
        <select name="cuit_filter" class="mam-cuit-filter">
            <option value=""><?php _e('Todos los CUIT', 'my-account-manager'); ?></option>
            <option value="with_cuit" <?php selected($selected, 'with_cuit'); ?>><?php _e('Con CUIT', 'my-account-manager'); ?></option>
            <option value="without_cuit" <?php selected($selected, 'without_cuit'); ?>><?php _e('Sin CUIT', 'my-account-manager'); ?></option>
        </select>
        <?php
    }

    /**
     * Filtrar usuarios por CUIT
     */
    public function filter_users_by_cuit($query) {
        if (!is_admin() || !isset($_GET['cuit_filter']) || empty($_GET['cuit_filter'])) {
            return;
        }

        global $wpdb;
        
        if ($_GET['cuit_filter'] === 'with_cuit') {
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => 'billing_cuit',
                    'value' => '',
                    'compare' => '!='
                ),
                array(
                    'key' => 'cuit',
                    'value' => '',
                    'compare' => '!='
                )
            ));
        } elseif ($_GET['cuit_filter'] === 'without_cuit') {
            $query->set('meta_query', array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'billing_cuit',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'billing_cuit',
                        'compare' => 'NOT EXISTS'
                    )
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'cuit',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'cuit',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));
        }
    }
}

// Inicializar
MAM_Admin_CUIT::init();

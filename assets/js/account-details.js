/**
 * My Account Manager - Account Details Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Account = {
        /**
         * Inicialización
         */
        init: function() {
            this.initTabs();
            this.initPasswordStrength();
            this.initDeleteAccount();
            this.initImageUpload();
            this.handleSessionManagement();
        },

        /**
         * Inicializar pestañas
         */
        initTabs: function() {
            var self = this;
            var $tabs = $('.mam-account-tab');
            var $content = $('.woocommerce-EditAccountForm');
            var currentTab = this.getUrlParameter('tab') || 'details';

            // Manejar la visualización inicial
            self.showTabContent(currentTab);

            // Manejar clics en las pestañas
            $tabs.on('click', function(e) {
                var tab = $(this).attr('href').split('tab=')[1];
                self.showTabContent(tab);
            });
        },

        /**
         * Mostrar contenido de la pestaña
         */
        showTabContent: function(tab) {
            var $form = $('.woocommerce-EditAccountForm');
            
            // Ocultar todos los campos
            $form.find('p.woocommerce-form-row').hide();
            
            // Mostrar campos según la pestaña
            switch(tab) {
                case 'details':
                    // Mostrar campos de datos personales
                    $form.find('p.woocommerce-form-row--first').show();
                    $form.find('p.woocommerce-form-row--last').show();
                    $form.find('p.woocommerce-form-row--wide').not(':has(#password_current, #password_1, #password_2)').show();
                    $form.find('fieldset').hide();
                    $('.mam-password-strength-meter').hide();
                    $('.mam-user-preferences, .mam-privacy-options, .mam-active-sessions, .mam-delete-account').hide();
                    break;
                    
                case 'password':
                    // Mostrar campos de contraseña
                    $form.find('p:has(#password_current), p:has(#password_1), p:has(#password_2)').show();
                    $form.find('fieldset').show();
                    $('.mam-password-strength-meter').show();
                    $('.mam-user-preferences, .mam-privacy-options, .mam-active-sessions, .mam-delete-account').hide();
                    break;
                    
                case 'preferences':
                    // Mostrar preferencias
                    $form.find('p.woocommerce-form-row').hide();
                    $('.mam-user-preferences').show();
                    $('.mam-privacy-options, .mam-active-sessions, .mam-delete-account').hide();
                    break;
                    
                case 'privacy':
                    // Mostrar opciones de privacidad
                    $form.find('p.woocommerce-form-row').hide();
                    $('.mam-privacy-options, .mam-active-sessions, .mam-delete-account').show();
                    $('.mam-user-preferences').hide();
                    break;
            }
            
            // Mostrar siempre el botón de guardar
            $form.find('p:has(button[type="submit"])').show();
        },
// Añadir a assets/js/account-details.js (existente)
// Modificar la función init para añadir nuevos métodos

initAjaxAccount: function() {
    var self = this;
    
    // Form de detalles de cuenta
    $('.woocommerce-EditAccountForm').on('submit', function(e) {
        // No procesar si es otro tab que no sea "details"
        if ($('.mam-account-tab[data-tab="details"]').hasClass('active')) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            // Añadir acción y nonce
            formData += '&action=mam_update_account&security=' + mam_params.nonce;
            
            // Mostrar loader
            var $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).addClass('mam-loading');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                    
                    // Restaurar botón
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                },
                error: function() {
                    self.showNotice('error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                }
            });
        }
    });
},

initAjaxPassword: function() {
    var self = this;
    
    // Form de cambio de contraseña
    $('.woocommerce-EditAccountForm').on('submit', function(e) {
        // Solo procesar si estamos en el tab "password"
        if ($('.mam-account-tab[data-tab="password"]').hasClass('active')) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            // Añadir acción y nonce
            formData += '&action=mam_update_password&security=' + mam_params.nonce;
            
            // Mostrar loader
            var $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).addClass('mam-loading');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                        
                        // Limpiar campos de contraseña
                        $('#password_current, #password_1, #password_2').val('');
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                    
                    // Restaurar botón
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                },
                error: function() {
                    self.showNotice('error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                }
            });
        }
    });
},

showNotice: function(type, message) {
    var $noticeContainer = $('.mam-account-notices');
    
    if ($noticeContainer.length === 0) {
        $noticeContainer = $('<div class="mam-account-notices"></div>');
        $('.mam-account-details-header').after($noticeContainer);
    }
    
    var $notice = $('<div class="mam-notice mam-notice-' + type + '">' + message + '</div>');
    $noticeContainer.html($notice);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $notice.fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
    
    // Scroll para mostrar la notificación
    $('html, body').animate({
        scrollTop: $noticeContainer.offset().top - 50
    }, 300);
}
        /**
         * Inicializar medidor de fuerza de contraseña
         */
        initPasswordStrength: function() {
            var $password = $('#password_1');
            var $meter = $('#mam-password-meter-bar');
            var $text = $('#mam-password-strength');
            
            if (!$password.length) {
                return;
            }
            
            // Actualizar medidor cuando cambie la contraseña
            $password.on('keyup', function() {
                var password = $(this).val();
                var score = MAM_Account.checkPasswordStrength(password);
                
                // Actualizar barra
                $meter.css('width', score.percent + '%');
                $meter.removeClass('weak medium strong very-strong').addClass(score.class);
                
                // Actualizar texto
                $text.text(score.text);
                $text.removeClass('weak medium strong very-strong').addClass(score.class);
            });
        },

        /**
         * Comprobar fuerza de contraseña
         */
        checkPasswordStrength: function(password) {
            var score = 0;
            var result = {
                class: 'weak',
                text: 'Débil',
                percent: 25
            };
            
            // Calcular puntuación
            if (password.length >= 8) score++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
            if (password.match(/\d+/)) score++;
            if (password.match(/[^a-zA-Z0-9]/)) score++;
            
            // Establecer resultado según puntuación
            if (score === 1) {
                result = {
                    class: 'weak',
                    text: 'Débil',
                    percent: 25
                };
            } else if (score === 2) {
                result = {
                    class: 'medium',
                    text: 'Media',
                    percent: 50
                };
            } else if (score === 3) {
                result = {
                    class: 'strong',
                    text: 'Fuerte',
                    percent: 75
                };
            } else if (score >= 4) {
                result = {
                    class: 'very-strong',
                    text: 'Muy Fuerte',
                    percent: 100
                };
            }
            
            return result;
        },

        /**
         * Inicializar funcionalidad de eliminar cuenta
         */
        initDeleteAccount: function() {
            var $deleteBtn = $('#mam-show-delete-confirmation');
            var $cancelBtn = $('#mam-cancel-delete');
            var $confirmation = $('#mam-delete-confirmation');
            var $reasonSelect = $('#delete_reason');
            var $reasonOther = $('#delete_reason_other_container');
            
            // Mostrar confirmación
            $deleteBtn.on('click', function() {
                $confirmation.fadeIn(300);
                $(this).hide();
            });
            
            // Cancelar eliminación
            $cancelBtn.on('click', function() {
                $confirmation.fadeOut(300);
                $deleteBtn.show();
            });
            
            // Mostrar campo de "otra razón" si se selecciona
            $reasonSelect.on('change', function() {
                if ($(this).val() === 'other') {
                    $reasonOther.fadeIn(300);
                } else {
                    $reasonOther.fadeOut(300);
                }
            });
        },

        /**
         * Inicializar carga de imágenes de perfil
         */
        initImageUpload: function() {
            // Esta funcionalidad requiere un plugin adicional de gestión de imágenes
            // como "Simple Local Avatars" para permitir a los usuarios cambiar su avatar
            
            var $avatarContainer = $('.mam-account-avatar');
            
            // Si existe un botón de cambiar avatar, añadir evento
            if ($avatarContainer.find('.mam-change-avatar').length) {
                $avatarContainer.on('click', '.mam-change-avatar', function(e) {
                    e.preventDefault();
                    
                    // Activar el input file
                    $avatarContainer.find('input[type="file"]').trigger('click');
                });
                
                // Manejar cambio de archivo
                $avatarContainer.on('change', 'input[type="file"]', function() {
                    // Aquí se manejaría la subida de imagen mediante AJAX
                    // Esta es una implementación básica que requiere personalización
                    // según el plugin de avatares utilizado
                    
                    var file = this.files[0];
                    var formData = new FormData();
                    
                    formData.append('action', 'mam_upload_avatar');
                    formData.append('avatar', file);
                    formData.append('security', mam_params.nonce);
                    
                    $.ajax({
                        url: mam_params.ajax_url,
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (response.success) {
                                // Actualizar avatar
                                $avatarContainer.find('img.mam-avatar').attr('src', response.data.url);
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                });
            }
        },

        /**
         * Manejar gestión de sesiones
         */
        handleSessionManagement: function() {
            $('.mam-revoke-session, .mam-revoke-all-sessions').on('click', function() {
                return confirm('¿Estás seguro de que quieres cerrar esta sesión?');
            });
        },

        /**
         * Obtener parámetro de URL
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Account.init();
    });

})(jQuery);

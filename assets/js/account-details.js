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
            this.initAjaxAccount();
            this.initAjaxPassword();
        },

        // Los métodos anteriores permanecen igual

               /**
         * Inicializar AJAX para detalles de cuenta
         */
        initAjaxAccount: function() {
            var self = this;
            
            // Form de detalles de cuenta
            $('.woocommerce-EditAccountForm').on('submit', function(e) {
                // Solo procesar si estamos en el tab "details"
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
            
            // Manejar respuestas AJAX exitosas
            $('.woocommerce-EditAccountForm').on('mam_ajax_success', function(event, data) {
                // Mostrar notificación de éxito
                if (data.message) {
                    self.showNotice('success', data.message);
                }
                
                // Otras acciones específicas si es necesario
            });
        },
    
    // Manejar respuestas AJAX exitosas
    $('.woocommerce-EditAccountForm').on('mam_ajax_success', function(event, data) {
        // Mostrar notificación de éxito
        if (data.message) {
            self.showNotice('success', data.message);
        }
        
        // Otras acciones específicas si es necesario
    });
},

/**
 * Inicializar AJAX para cambio de contraseña
 */
/**
         * Inicializar AJAX para cambio de contraseña
         */
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
                    
                    // Validación de contraseñas
                    var currentPassword = $('#password_current').val();
                    var newPassword = $('#password_1').val();
                    var confirmPassword = $('#password_2').val();
                    
                    if (!currentPassword) {
                        self.showNotice('error', 'Por favor, introduce tu contraseña actual.');
                        return;
                    }
                    
                    if (newPassword && newPassword !== confirmPassword) {
                        self.showNotice('error', 'Las contraseñas no coinciden.');
                        return;
                    }
                    
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

/**
         * Mostrar notificaciones de cuenta
         */
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

            $(document).ready(function() {
        MAM_Account.init();
    });

})(jQuery);

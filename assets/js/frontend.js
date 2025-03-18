/**
 * My Account Manager Frontend Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM = {
        /**
         * Inicialización
         */
        init: function() {
            this.initTabs();
            this.initAjaxLogin();
            this.initAjaxRegister();
            this.initPasswordToggle();
            this.initFormValidation();
            this.initMobileMenu();
        },

        /**
         * Inicializar pestañas para login/registro
         */
        initTabs: function() {
            var $loginTab = $('.mam-login-tab');
            var $registerTab = $('.mam-register-tab');
            var $loginForm = $('.mam-login-form-container');
            var $registerForm = $('.mam-register-form-container');

            // Mostrar formulario de login por defecto
            $loginForm.show();
            $registerForm.hide();
            $loginTab.addClass('active');

            // Click en la pestaña de login
            $loginTab.on('click', function(e) {
                e.preventDefault();
                $loginForm.fadeIn(300);
                $registerForm.hide();
                $loginTab.addClass('active');
                $registerTab.removeClass('active');
            });

            // Click en la pestaña de registro
            $registerTab.on('click', function(e) {
                e.preventDefault();
                $registerForm.fadeIn(300);
                $loginForm.hide();
                $registerTab.addClass('active');
                $loginTab.removeClass('active');
            });

            // Verificar hash de URL para cambiar pestaña si es necesario
            if (window.location.hash === '#register') {
                $registerTab.trigger('click');
            }
        },

        /**
         * Inicializar login por AJAX
         */
        initAjaxLogin: function() {
            var self = this;

            $('.woocommerce-form-login').on('submit', function(e) {
                // Solo procesar si tiene la clase de AJAX
                if (!$(this).hasClass('mam-ajax-login')) {
                    return true;
                }

                e.preventDefault();

                var $form = $(this);
                var $submitButton = $form.find('button[type="submit"]');
                var formData = $form.serialize();

                // Deshabilitar botón y mostrar loader
                self.startLoading($submitButton);

                // Añadir nonce
                formData += '&security=' + mam_params.nonce + '&action=mam_ajax_login';

                // Enviar solicitud AJAX
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            self.showSuccessMessage($form, response.data.message);
                            
                            // Redirigir después de un pequeño retraso
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            self.showErrorMessage($form, response.data.message);
                            self.stopLoading($submitButton);
                        }
                    },
                    error: function(xhr, status, error) {
                        self.showErrorMessage($form, 'Ha ocurrido un error. Por favor, inténtalo de nuevo.');
                        self.stopLoading($submitButton);
                    }
                });
            });
        },

        /**
         * Inicializar registro por AJAX
         */
        initAjaxRegister: function() {
            var self = this;

            $('.woocommerce-form-register').on('submit', function(e) {
                // Solo procesar si tiene la clase de AJAX
                if (!$(this).hasClass('mam-ajax-register')) {
                    return true;
                }

                e.preventDefault();

                var $form = $(this);
                var $submitButton = $form.find('button[type="submit"]');
                var formData = $form.serialize();

                // Deshabilitar botón y mostrar loader
                self.startLoading($submitButton);

                // Añadir nonce
                formData += '&security=' + mam_params.nonce + '&action=mam_ajax_register';

                // Enviar solicitud AJAX
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            self.showSuccessMessage($form, response.data.message);
                            
                            // Redirigir después de un pequeño retraso
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            self.showErrorMessage($form, response.data.message);
                            self.stopLoading($submitButton);
                        }
                    },
                    error: function(xhr, status, error) {
                        self.showErrorMessage($form, 'Ha ocurrido un error. Por favor, inténtalo de nuevo.');
                        self.stopLoading($submitButton);
                    }
                });
            });
        },

        /**
         * Inicializar toggle de visibilidad de contraseña
         */
        initPasswordToggle: function() {
            $('.mam-password-toggle').on('click', function(e) {
                e.preventDefault();
                
                var $passwordField = $(this).siblings('input');
                var currentType = $passwordField.attr('type');
                var $icon = $(this).find('i');
                
                if (currentType === 'password') {
                    $passwordField.attr('type', 'text');
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $passwordField.attr('type', 'password');
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        },

        /**
         * Inicializar validación de formularios
         */
        initFormValidation: function() {
            // Validación básica de formularios
            $('input, textarea, select').on('blur', function() {
                var $field = $(this);
                var $parent = $field.closest('.form-row');
                
                // Limpiar errores previos
                $parent.removeClass('mam-form-error');
                $parent.find('.mam-error-message').remove();
                
                // Validar campo requerido
                if ($field.prop('required') && $field.val() === '') {
                    $parent.addClass('mam-form-error');
                    $parent.append('<span class="mam-error-message">Este campo es obligatorio.</span>');
                }
                
                // Validar email
                if ($field.attr('type') === 'email' && $field.val() !== '') {
                    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test($field.val())) {
                        $parent.addClass('mam-form-error');
                        $parent.append('<span class="mam-error-message">Por favor, introduce una dirección de correo válida.</span>');
                    }
                }
            });
        },

        /**
         * Inicializar menú móvil
         */
        initMobileMenu: function() {
            var $menuToggle = $('.mam-mobile-menu-toggle');
            var $navigation = $('.woocommerce-MyAccount-navigation');
            
            $menuToggle.on('click', function(e) {
                e.preventDefault();
                $navigation.slideToggle(300);
                $(this).toggleClass('active');
            });
            
            // Ocultar/mostrar menú al cambiar tamaño de ventana
            $(window).on('resize', function() {
                if ($(window).width() >= 768) {
                    $navigation.show();
                } else if (!$menuToggle.hasClass('active')) {
                    $navigation.hide();
                }
            });
        },

        /**
         * Mostrar mensaje de éxito
         */
        showSuccessMessage: function($form, message) {
            this.removeMessages($form);
            $form.prepend('<div class="mam-success-message">' + message + '</div>');
        },

        /**
         * Mostrar mensaje de error
         */
        showErrorMessage: function($form, message) {
            this.removeMessages($form);
            $form.prepend('<div class="mam-error-message">' + message + '</div>');
        },

        /**
         * Eliminar mensajes existentes
         */
        removeMessages: function($form) {
            $form.find('.mam-success-message, .mam-error-message').remove();
        },

        /**
         * Iniciar estado de carga
         */
        startLoading: function($button) {
            $button.prop('disabled', true);
            $button.addClass('mam-loading');
            
            // Guardar texto original si no está guardado
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.html());
            }
            
            $button.html('<span class="mam-spinner"></span> Procesando...');
        },

        /**
         * Detener estado de carga
         */
        stopLoading: function($button) {
            $button.prop('disabled', false);
            $button.removeClass('mam-loading');
            
            // Restaurar texto original
            if ($button.data('original-text')) {
                $button.html($button.data('original-text'));
            }
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM.init();
    });

})(jQuery);

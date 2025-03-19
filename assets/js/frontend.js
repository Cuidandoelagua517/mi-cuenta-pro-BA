// Módulo para gestionar funcionalidades de la cuenta de usuario
var MAMUserAccount = {
    /**
     * Inicializar pestañas en login/registro y otras áreas
     */
    initTabs: function() {
        // Tabs de login/registro
        $('.mam-login-tab, .mam-register-tab').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            
            // Activar tab
            $('.mam-login-tab, .mam-register-tab').removeClass('active');
            $(this).addClass('active');
            
            // Mostrar contenido correspondiente
            if (target === '#login') {
                $('.mam-login-form-wrapper').removeClass('hide');
                $('.mam-register-form-wrapper').addClass('hide');
            } else if (target === '#register') {
                $('.mam-login-form-wrapper').addClass('hide');
                $('.mam-register-form-wrapper').removeClass('hide');
            }
        });
        
        // Otras pestañas de la cuenta
        $('.mam-account-tab, .mam-address-tab').on('click', function(e) {
            // Solo gestionar el evento si no estamos navegando a otra página
            if ($(this).attr('href').indexOf('#') === 0) {
                e.preventDefault();
                
                var tab = $(this).attr('href').replace('#', '');
                
                // Activar tab
                $(this).parent().find('.mam-account-tab, .mam-address-tab').removeClass('active');
                $(this).addClass('active');
                
                // Trigger evento personalizado para módulos específicos
                $(document).trigger('mam_tab_changed', [tab]);
            }
        });
    },

    /**
     * Inicializar login por AJAX
     */
    initAjaxLogin: function() {
        var self = this;
        
        $(document).on('submit', '.mam-ajax-form[data-action="mam_ajax_login"]', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var formData = $form.serialize();
            
            // Validar campos obligatorios
            var username = $form.find('input[name="email"]').val();
            var password = $form.find('input[name="password"]').val();
            
            if (!username || !password) {
                self.showMessage($form, 'error', 'Por favor, completa todos los campos.');
                return;
            }
            
            // Mostrar loader
            $submitBtn.prop('disabled', true).addClass('mam-loading');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showMessage($form, 'success', response.data.message || 'Login exitoso. Redirigiendo...');
                        
                        // Redireccionar después de un breve retraso
                        setTimeout(function() {
                            window.location.href = response.data.redirect || '';
                        }, 1000);
                    } else {
                        self.showMessage($form, 'error', response.data.message || 'Error al iniciar sesión. Verifica tus credenciales.');
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    }
                },
                error: function() {
                    self.showMessage($form, 'error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                }
            });
        });
    },

    /**
     * Inicializar registro por AJAX
     */
    initAjaxRegister: function() {
        var self = this;
        
        $(document).on('submit', '.mam-ajax-form[data-action="mam_ajax_register"]', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var formData = $form.serialize();
            
            // Validar campos obligatorios
            var email = $form.find('input[name="email"]').val();
            var privacyPolicy = $form.find('input[name="privacy_policy"]:checked').length;
            
            if (!email) {
                self.showMessage($form, 'error', 'Por favor, introduce un correo electrónico válido.');
                return;
            }
            
            if (!privacyPolicy) {
                self.showMessage($form, 'error', 'Debes aceptar la política de privacidad.');
                return;
            }
            
            // Validación básica de email
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                self.showMessage($form, 'error', 'Por favor, introduce un correo electrónico válido.');
                return;
            }
            
            // Validar contraseña si existe campo
            var password = $form.find('input[name="password"]').val();
            if (password && password.length < 6) {
                self.showMessage($form, 'error', 'La contraseña debe tener al menos 6 caracteres.');
                return;
            }
            
            // Mostrar loader
            $submitBtn.prop('disabled', true).addClass('mam-loading');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showMessage($form, 'success', response.data.message || 'Registro exitoso. Redirigiendo...');
                        
                        // Redireccionar después de un breve retraso
                        setTimeout(function() {
                            window.location.href = response.data.redirect || '';
                        }, 1500);
                    } else {
                        self.showMessage($form, 'error', response.data.message || 'Error en el registro.');
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    }
                },
                error: function() {
                    self.showMessage($form, 'error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                    $submitBtn.prop('disabled', false).removeClass('mam-loading');
                }
            });
        });
    },

    /**
     * Validación de CUIT en tiempo real
     */
    initCUITValidation: function() {
        $('#reg_cuit').on('blur', function() {
            var $field = $(this);
            var cuit = $field.val().trim();
            
            // Eliminar guiones y espacios para la validación
            var cleanCuit = cuit.replace(/[^0-9]/g, '');
            
            if (cuit && cleanCuit.length !== 11) {
                $field.addClass('mam-field-error');
                
                // Añadir mensaje de error si no existe
                if ($field.next('.mam-field-error-message').length === 0) {
                    $field.after('<span class="mam-field-error-message">CUIT debe tener 11 dígitos (xx-xxxxxxxx-x)</span>');
                } else {
                    $field.next('.mam-field-error-message').text('CUIT debe tener 11 dígitos (xx-xxxxxxxx-x)');
                }
            }
        });
        
        // Formatear automáticamente el CUIT mientras se escribe
        $('#reg_cuit').on('input', function() {
            var $field = $(this);
            var cuit = $field.val().replace(/[^0-9]/g, '');
            
            if (cuit.length > 2 && cuit.length <= 10) {
                cuit = cuit.substring(0, 2) + '-' + cuit.substring(2);
            } else if (cuit.length > 10) {
                cuit = cuit.substring(0, 2) + '-' + cuit.substring(2, 10) + '-' + cuit.substring(10, 11);
            }
            
            $field.val(cuit);
        });
    },

    /**
     * Inicializar toggle de mostrar/ocultar contraseña
     */
    initPasswordToggle: function() {
        $('.mam-password-toggle').on('click', function() {
            var $toggleBtn = $(this);
            var $passwordInput = $toggleBtn.siblings('input');
            
            if ($passwordInput.attr('type') === 'password') {
                $passwordInput.attr('type', 'text');
                $toggleBtn.html('<i class="fa fa-eye-slash"></i>');
                $toggleBtn.addClass('showing-password');
            } else {
                $passwordInput.attr('type', 'password');
                $toggleBtn.html('<i class="fa fa-eye"></i>');
                $toggleBtn.removeClass('showing-password');
            }
        });
    },

    /**
     * Inicializar validación de formularios
     */
    initFormValidation: function() {
        // Validar campos requeridos
        $('form.mam-ajax-form input[required], form.mam-ajax-form select[required], form.mam-ajax-form textarea[required]')
            .on('blur', function() {
                var $field = $(this);
                
                if (!$field.val().trim()) {
                    $field.addClass('mam-field-error');
                    
                    // Añadir mensaje de error si no existe
                    if ($field.next('.mam-field-error-message').length === 0) {
                        $field.after('<span class="mam-field-error-message">Este campo es obligatorio</span>');
                    }
                } else {
                    $field.removeClass('mam-field-error');
                    $field.next('.mam-field-error-message').remove();
                }
            });
        
        // Validar formato de email
        $('form.mam-ajax-form input[type="email"]').on('blur', function() {
            var $field = $(this);
            var email = $field.val().trim();
            
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $field.addClass('mam-field-error');
                
                // Añadir mensaje de error si no existe
                if ($field.next('.mam-field-error-message').length === 0) {
                    $field.after('<span class="mam-field-error-message">Por favor, introduce un email válido</span>');
                } else {
                    $field.next('.mam-field-error-message').text('Por favor, introduce un email válido');
                }
            }
        });
        
        // Validar formato de teléfono
        $('form.mam-ajax-form input[type="tel"]').on('blur', function() {
            var $field = $(this);
            var phone = $field.val().trim();
            
            if (phone && !/^[0-9+\s()-]{6,20}$/.test(phone)) {
                $field.addClass('mam-field-error');
                
                // Añadir mensaje de error si no existe
                if ($field.next('.mam-field-error-message').length === 0) {
                    $field.after('<span class="mam-field-error-message">Por favor, introduce un teléfono válido</span>');
                } else {
                    $field.next('.mam-field-error-message').text('Por favor, introduce un teléfono válido');
                }
            }
        });
        
        // Limpiar errores al escribir
        $('form.mam-ajax-form input, form.mam-ajax-form select, form.mam-ajax-form textarea').on('input', function() {
            var $field = $(this);
            if ($field.val().trim()) {
                $field.removeClass('mam-field-error');
                $field.next('.mam-field-error-message').remove();
            }
        });
    },

    /**
     * Inicializar menú móvil para navegación responsiva
     */
    initMobileMenu: function() {
        // Si estamos en viewport móvil
        if (window.innerWidth < 768) {
            // Crear botón de menú si no existe
            if ($('.mam-mobile-menu-toggle').length === 0) {
                $('.woocommerce-MyAccount-navigation').before(
                    '<button class="mam-mobile-menu-toggle">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />' +
                    '</svg>' +
                    '<span>Menú de Cuenta</span>' +
                    '</button>'
                );
            }
            
            // Ocultar menú por defecto
            $('.woocommerce-MyAccount-navigation ul').addClass('mam-mobile-hidden');
            
            // Toggle para mostrar/ocultar menú
            $('.mam-mobile-menu-toggle').on('click', function() {
                $('.woocommerce-MyAccount-navigation ul').toggleClass('mam-mobile-hidden');
                $(this).toggleClass('mam-menu-active');
            });
            
            // Cerrar menú al hacer clic en un enlace
            $('.woocommerce-MyAccount-navigation li a').on('click', function() {
                if (window.innerWidth < 768) {
                    $('.woocommerce-MyAccount-navigation ul').addClass('mam-mobile-hidden');
                    $('.mam-mobile-menu-toggle').removeClass('mam-menu-active');
                }
            });
        }
    },
    
    /**
     * Mostrar mensajes al usuario
     */
    showMessage: function($form, type, message) {
        // Eliminar mensajes existentes
        $form.find('.mam-message').remove();
        
        // Crear nuevo mensaje
        var $message = $('<div class="mam-message mam-' + type + '">' + message + '</div>');
        
        // Insertar antes del formulario
        $form.prepend($message);
        
        // Auto ocultar mensajes de éxito después de un tiempo
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    },
    
    /**
     * Inicializar todas las funcionalidades
     */
    init: function() {
        this.initTabs();
        this.initAjaxLogin();
        this.initAjaxRegister();
        this.initCUITValidation();
        this.initPasswordToggle();
        this.initFormValidation();
        this.initMobileMenu();
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    MAMUserAccount.init();
});

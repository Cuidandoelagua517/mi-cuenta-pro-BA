/**
 * My Account Manager - Frontend Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAMUserAccount = {
        
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
            
            console.log('MAMUserAccount init completed');
        },
        
        /**
         * Inicializar pestañas en login/registro y otras áreas
         */
        initTabs: function() {
            var self = this;
            
            // Solo inicializar si estamos en la página de login/registro
            if ($('.mam-login-register-tabs').length === 0) {
                return;
            }
            
            console.log('Inicializando tabs de login/registro');
            
            // Tabs de login/registro
            $('.mam-login-tab').on('click', function(e) {
                e.preventDefault();
                console.log('Login tab clicked');
                
                // Activar tab
                $('.mam-login-tab').addClass('active');
                $('.mam-register-tab').removeClass('active');
                
                // Mostrar formulario correcto
                $('.mam-login-form-wrapper').removeClass('hide').show();
                $('.mam-register-form-wrapper').addClass('hide').hide();
                
                // Actualizar clases en html para mayor compatibilidad
                $('html').addClass('js-login-tab-active').removeClass('js-register-tab-active');
            });
            
            $('.mam-register-tab').on('click', function(e) {
                e.preventDefault();
                console.log('Register tab clicked');
                
                // Activar tab
                $('.mam-register-tab').addClass('active');
                $('.mam-login-tab').removeClass('active');
                
                // Mostrar formulario correcto
                $('.mam-register-form-wrapper').removeClass('hide').show();
                $('.mam-login-form-wrapper').addClass('hide').hide();
                
                // Actualizar clases en html para mayor compatibilidad
                $('html').addClass('js-register-tab-active').removeClass('js-login-tab-active');
            });
            
            // Establecer estado inicial basado en URL
            var urlParams = new URLSearchParams(window.location.search);
            var action = urlParams.get('action');
            
            if (window.location.hash === '#register' || 
                window.location.search.indexOf('action=register') > -1 ||
                action === 'register') {
                // Simular clic en la pestaña de registro
                $('.mam-register-tab').trigger('click');
            } else {
                // Por defecto, activar pestaña de login
                $('.mam-login-tab').trigger('click');
            }
        },
        
        /**
         * Inicializar login por AJAX
         */
        initAjaxLogin: function() {
            var self = this;
            
            $('.woocommerce-form-login.login').on('submit', function(e) {
                e.preventDefault();
                console.log('Login form submitted via AJAX');
                
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                var formData = $form.serialize();
                
                // Asegurarse que el action esté incluido
                if (formData.indexOf('action=') === -1) {
                    formData += '&action=mam_ajax_login';
                }
                
                console.log('Form data:', formData);
                
                // Mostrar loader
                $submitBtn.prop('disabled', true).addClass('mam-loading');
                
                $.ajax({
                    type: 'POST',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        console.log('AJAX response received:', response);
                        if (response.success) {
                            self.showMessage($form, 'success', response.data.message || 'Login exitoso. Redirigiendo...');
                            
                            setTimeout(function() {
                                window.location.href = response.data.redirect || '';
                            }, 1000);
                        } else {
                            self.showMessage($form, 'error', response.data.message || 'Error al iniciar sesión. Verifica tus credenciales.');
                            $submitBtn.prop('disabled', false).removeClass('mam-loading');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
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
            var self = this;
            
            // Selectores más amplios para capturar todos los campos CUIT posibles
            var cuitSelectors = '#reg_cuit, #billing_cuit, input[name="cuit"], input[name="billing_cuit"], input[name="reg_cuit"], [id*="cuit"]:not([type="hidden"]), [name*="cuit"]:not([type="hidden"])';
            
            console.log('Inicializando validación CUIT');
            
            // Formateo automático mientras se escribe
            $('body').on('input', cuitSelectors, function() {
                var $field = $(this);
                var cuit = $field.val();
                var cursorPos = this.selectionStart;
                
                console.log('Formateando CUIT:', cuit);
                
                // Solo formatear si el usuario no está escribiendo guiones manualmente
                if (!cuit.includes('-') || cuit.replace(/[^-]/g, '').length < 2) {
                    var cuitLimpio = cuit.replace(/[^0-9]/g, '');
                    
                    // Limitar a 11 dígitos
                    if (cuitLimpio.length > 11) {
                        cuitLimpio = cuitLimpio.substring(0, 11);
                    }
                    
                    // Aplicar formato XX-XXXXXXXX-X
                    var formattedCuit = '';
                    
                    if (cuitLimpio.length > 2 && cuitLimpio.length <= 10) {
                        formattedCuit = cuitLimpio.substring(0, 2) + '-' + cuitLimpio.substring(2);
                        if (cursorPos > 2) cursorPos++;
                    } else if (cuitLimpio.length > 10) {
                        formattedCuit = cuitLimpio.substring(0, 2) + '-' + 
                                       cuitLimpio.substring(2, 10) + '-' + 
                                       cuitLimpio.substring(10, 11);
                        if (cursorPos > 10) cursorPos++;
                        if (cursorPos > 2) cursorPos++;
                    } else {
                        formattedCuit = cuitLimpio;
                    }
                    
                    $field.val(formattedCuit);
                    
                    // Restaurar posición del cursor
                    if (this.setSelectionRange) {
                        this.setSelectionRange(cursorPos, cursorPos);
                    }
                }
                
                // Validar mientras escribe si tiene 11 dígitos
                var digitCount = $field.val().replace(/[^0-9]/g, '').length;
                if (digitCount === 11) {
                    self.validateCUITField($field);
                }
            });
            
            // Validación al perder el foco
            $('body').on('blur', cuitSelectors, function() {
                self.validateCUITField($(this));
            });
            
            // También aplicar formateo inicial a campos CUIT existentes
            $(document).ready(function() {
                setTimeout(function() {
                    $(cuitSelectors).each(function() {
                        var $field = $(this);
                        if ($field.val() && !$field.val().includes('-')) {
                            $field.trigger('input');
                        }
                    });
                }, 500);
            });
        },
        
        /**
         * Validar un campo CUIT específico
         */
        validateCUITField: function($field) {
            var cuit = $field.val().trim();
            
            if (!cuit) {
                $field.removeClass('mam-field-error');
                $field.parent().find('.mam-field-error-message').remove();
                return;
            }
            
            // Expresiones regulares para validar CUIT
            var regexEstricto = /^\d{2}-\d{8}-\d$/;  // Formato con guiones obligatorios
            var regexFlexible = /^\d{2}[-]?\d{8}[-]?\d$/;  // Formato con guiones opcionales
            var regexNumeros = /^\d{11}$/;  // Solo números
            
            // Limpiar CUIT para validación de solo números
            var cuitNumeros = cuit.replace(/[^0-9]/g, '');
            
            // Validar formato
            var esValido = regexEstricto.test(cuit) || 
                          regexFlexible.test(cuit) || 
                          (cuitNumeros.length === 11 && regexNumeros.test(cuitNumeros));
            
            // Buscar el contenedor correcto para el mensaje de error
            var $errorContainer = $field.parent();
            
            if (cuit && !esValido) {
                $field.addClass('mam-field-error');
                
                // Remover mensaje anterior
                $errorContainer.find('.mam-field-error-message').remove();
                
                // Añadir nuevo mensaje de error
                $errorContainer.append('<span class="mam-field-error-message" style="color: #e74c3c; font-size: 12px; display: block; margin-top: 5px;">El formato del CUIT debe ser: XX-XXXXXXXX-X</span>');
            } else if (esValido) {
                $field.removeClass('mam-field-error');
                $errorContainer.find('.mam-field-error-message').remove();
                
                // Mensaje de éxito opcional
                console.log('CUIT válido:', cuit);
            }
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
                    $toggleBtn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-slash-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>');
                    $toggleBtn.addClass('showing-password');
                } else {
                    $passwordInput.attr('type', 'password');
                    $toggleBtn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>');
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
            var self = this;
            
            // Función para configurar el menú móvil
            function setupMobileMenu() {
                // Si estamos en viewport móvil
                if (window.innerWidth < 768) {
                    // Crear botón de menú si no existe
                    if ($('.mam-mobile-menu-toggle').length === 0 && $('.woocommerce-MyAccount-navigation').length > 0) {
                        $('.woocommerce-MyAccount-navigation').before(
                            '<button class="mam-mobile-menu-toggle">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />' +
                            '</svg>' +
                            '<span>Menú de Cuenta</span>' +
                            '</button>'
                        );
                        
                        // Vincular eventos al nuevo botón
                        self.bindMobileMenuEvents();
                    }
                    
                    // Ocultar menú por defecto
                    $('.woocommerce-MyAccount-navigation ul').addClass('mam-mobile-hidden');
                }
            }
            
            // Vincular eventos del menú móvil
            this.bindMobileMenuEvents = function() {
                // Toggle para mostrar/ocultar menú
                $('.mam-mobile-menu-toggle').off('click').on('click', function() {
                    $('.woocommerce-MyAccount-navigation ul').toggleClass('mam-mobile-hidden');
                    $(this).toggleClass('mam-menu-active');
                });
                
                // Cerrar menú al hacer clic en un enlace
                $('.woocommerce-MyAccount-navigation li a').off('click.mobile').on('click.mobile', function() {
                    if (window.innerWidth < 768) {
                        $('.woocommerce-MyAccount-navigation ul').addClass('mam-mobile-hidden');
                        $('.mam-mobile-menu-toggle').removeClass('mam-menu-active');
                    }
                });
            };
            
            // Configurar inicialmente
            setupMobileMenu();
            
            // Reconfigurar en cambio de tamaño de ventana
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(setupMobileMenu, 250);
            });
        },
        
        /**
         * Mostrar mensajes al usuario
         */
        showMessage: function($form, type, message) {
            // Eliminar mensajes existentes
            $form.find('.mam-message').remove();
            
            // Crear nuevo mensaje
            var $message = $('<div class="mam-message mam-message-' + type + '">' + message + '</div>');
            
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
            
            // Scroll al mensaje si está fuera de vista
            if ($message.offset().top < $(window).scrollTop()) {
                $('html, body').animate({
                    scrollTop: $message.offset().top - 100
                }, 300);
            }
        }
    };

    // Ejecutar cuando el DOM esté listo
    $(document).ready(function() {
        console.log('My Account Manager: Iniciando...');
        
        // Inicializar el objeto principal
        MAMUserAccount.init();
        
        // Debug: Verificar si hay campos CUIT en la página
        setTimeout(function() {
            var cuitFields = $('input[name="cuit"], input[name="billing_cuit"], input[name="reg_cuit"], [id*="cuit"]:not([type="hidden"]), [name*="cuit"]:not([type="hidden"])');
            console.log('Campos CUIT encontrados:', cuitFields.length);
            
            if (cuitFields.length > 0) {
                console.log('Aplicando formateo inicial a campos CUIT...');
                cuitFields.each(function() {
                    var $field = $(this);
                    console.log('Campo CUIT:', $field.attr('name') || $field.attr('id'), 'Valor:', $field.val());
                    
                    // Si tiene valor sin guiones, aplicar formato
                    if ($field.val() && !$field.val().includes('-')) {
                        $field.trigger('input');
                    }
                });
            }
        }, 1000);
    });
    
    // Exponer el objeto globalmente para extensibilidad
    window.MAMUserAccount = MAMUserAccount;

})(jQuery);

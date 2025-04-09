/**
 * My Account Manager - Frontend Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAMUserAccount = {
        /**
         * Inicializar pestañas en login/registro y otras áreas
         */
        initTabs: function() {
            // Tabs de login/registro
            $('.mam-login-tab').on('click', function(e) {
                e.preventDefault();
                
                // Activar tab
                $('.mam-login-tab').addClass('active');
                $('.mam-register-tab').removeClass('active');
                
                // Mostrar formulario correcto
                $('.mam-login-form-wrapper').show();
                $('.mam-register-form-wrapper').hide();
            });
            
            $('.mam-register-tab').on('click', function(e) {
                e.preventDefault();
                
                // Activar tab
                $('.mam-register-tab').addClass('active');
                $('.mam-login-tab').removeClass('active');
                
                // Mostrar formulario correcto
                $('.mam-register-form-wrapper').show();
                $('.mam-login-form-wrapper').hide();
            });
            
            // Establecer estado inicial basado en URL
            if (window.location.hash === '#register' || 
                window.location.search.indexOf('action=register') > -1) {
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
 * Funcionalidades específicas para formularios en popups
 */
function initPopupForms() {
    // Toggle entre formularios de login/registro dentro de un popup
    $('.mam-toggle-form').on('click', function(e) {
        e.preventDefault();
        
        var targetForm = $(this).data('toggle');
        
        if (targetForm === 'register') {
            $('.mam-login-popup-form').hide();
            $('.mam-register-popup-form').show();
        } else {
            $('.mam-register-popup-form').hide();
            $('.mam-login-popup-form').show();
        }
    });
    
    // Mostrar/ocultar contraseña en popups
    $('.mam-popup-form .mam-password-toggle').on('click', function() {
        var $passwordField = $(this).closest('.mam-password-field').find('input');
        
        if ($passwordField.attr('type') === 'password') {
            $passwordField.attr('type', 'text');
            $(this).addClass('showing-password');
            $(this).find('.mam-eye-icon').html('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />');
        } else {
            $passwordField.attr('type', 'password');
            $(this).removeClass('showing-password');
            $(this).find('.mam-eye-icon').html('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />');
        }
    });
}
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
        },
        
        /**
         * Inicializar todas las funcionalidades
         */
        init: function() {
            console.log('MAMUserAccount init started'); // Depuración
init: function() {
    this.initTabs();
    this.initAjaxLogin();
    this.initAjaxRegister();
    this.initCUITValidation();
    this.initPasswordToggle();
    this.initFormValidation();
    this.initMobileMenu();
    // Añadir esta línea
    initCUITFormatting();

    
    console.log('MAMUserAccount init completed'); // Depuración
}
    };

    /**
     * Solución para las pestañas de login/registro
     * Este código debe insertarse en frontend.js
     */

    // Función para manejar las pestañas de login/registro
    function handleLoginRegisterTabs() {
        console.log('Inicializando manejo de pestañas login/registro');
        
        // Función para cambiar entre pestañas
        function switchTab(tabType) {
            console.log('Cambiando a pestaña:', tabType);
            
            // 1. Actualizar pestañas activas
            if (tabType === 'login') {
                $('.mam-login-tab').addClass('active');
                $('.mam-register-tab').removeClass('active');
            } else if (tabType === 'register') {
                $('.mam-register-tab').addClass('active');
                $('.mam-login-tab').removeClass('active');
            }
            
            // 2. Mostrar/ocultar formularios correspondientes
            if (tabType === 'login') {
                $('.mam-login-form-wrapper').show();
                $('.mam-register-form-wrapper').hide();
            } else if (tabType === 'register') {
                $('.mam-login-form-wrapper').hide();
                $('.mam-register-form-wrapper').show();
            }
        }
        
        // Manejar clic en pestaña de login
        $('.mam-login-tab').on('click', function(e) {
            e.preventDefault();
            switchTab('login');
        });
        
        // Manejar clic en pestaña de registro
        $('.mam-register-tab').on('click', function(e) {
            e.preventDefault();
            switchTab('register');
        });
        
        // Establecer pestaña inicial según URL
        var urlParams = new URLSearchParams(window.location.search);
        var action = urlParams.get('action');
        
        if (action === 'register') {
            switchTab('register');
        } else {
            switchTab('login');
        }
    }

    // Implementar una función clara para el manejo de pestañas
    function initLoginRegisterTabs() {
        $('.mam-login-tab, .mam-register-tab').on('click', function(e) {
            e.preventDefault();
            
            const isLoginTab = $(this).hasClass('mam-login-tab');
            
            // Activar pestaña correcta
            $('.mam-login-tab, .mam-register-tab').removeClass('active');
            $(this).addClass('active');
            
            // Mostrar formulario correcto
            if (isLoginTab) {
                $('.mam-login-form-wrapper').show();
                $('.mam-register-form-wrapper').hide();
            } else {
                $('.mam-register-form-wrapper').show();
                $('.mam-login-form-wrapper').hide();
            }
        });
    }
/**
 * Validación y formateo de CUIT en tiempo real
 */
function initCUITFormatting() {
    $('#reg_cuit').on('input', function() {
        var $field = $(this);
        var cuit = $field.val().replace(/[^0-9]/g, '');
        
        // Formatear automáticamente mientras el usuario escribe
        if (cuit.length > 0) {
            // Mantener solo los primeros 11 dígitos
            if (cuit.length > 11) {
                cuit = cuit.substring(0, 11);
            }
            
            // Aplicar formato XX-XXXXXXXX-X
            var formattedCuit = '';
            
            if (cuit.length > 2 && cuit.length <= 10) {
                formattedCuit = cuit.substring(0, 2) + '-' + cuit.substring(2);
            } else if (cuit.length > 10) {
                formattedCuit = cuit.substring(0, 2) + '-' + cuit.substring(2, 10) + '-' + cuit.substring(10);
            } else {
                formattedCuit = cuit;
            }
            
            $field.val(formattedCuit);
        }
    });
    
    // Validación al perder el foco
    $('#reg_cuit').on('blur', function() {
        var $field = $(this);
        var cuit = $field.val().trim();
        var cleanCuit = cuit.replace(/[^0-9]/g, '');
        
        if (cuit && cleanCuit.length !== 11) {
            $field.addClass('mam-field-error');
            
            // Mostrar mensaje de error
            if ($field.next('.mam-field-error-message').length === 0) {
                $field.after('<span class="mam-field-error-message">El CUIT debe tener 11 dígitos (formato: xx-xxxxxxxx-x)</span>');
            }
        } else {
            $field.removeClass('mam-field-error');
            $field.next('.mam-field-error-message').remove();
        }
    });
}

// Asegúrate de llamar a esta función cuando el documento esté listo
$(document).ready(function() {
    initCUITFormatting();
});
    
    // Validación al perder el foco (para ambos campos)
    $('body').on('blur', '#reg_cuit, #billing_cuit', function() {
        var $field = $(this);
        var cuit = $field.val().trim();
        var cleanCuit = cuit.replace(/[^0-9]/g, '');
        
        if (cuit && cleanCuit.length !== 11) {
            $field.addClass('mam-field-error');
            
            // Mostrar mensaje de error
            if ($field.next('.mam-field-error-message').length === 0) {
                $field.after('<span class="mam-field-error-message">El CUIT debe tener 11 dígitos (formato: xx-xxxxxxxx-x)</span>');
            }
        } else {
            $field.removeClass('mam-field-error');
            $field.next('.mam-field-error-message').remove();
        }
    });
}
        // Inicializar funciones para popups
    initPopupForms();
    // Validación al perder el foco
    $('#reg_cuit').on('blur', function() {
        var $field = $(this);
        var cuit = $field.val().trim();
        var cleanCuit = cuit.replace(/[^0-9]/g, '');
        
        if (cuit && cleanCuit.length !== 11) {
            $field.addClass('mam-field-error');
            
            // Mostrar mensaje de error
            if ($field.next('.mam-field-error-message').length === 0) {
                $field.after('<span class="mam-field-error-message">El CUIT debe tener 11 dígitos (formato: xx-xxxxxxxx-x)</span>');
            }
        } else {
            $field.removeClass('mam-field-error');
            $field.next('.mam-field-error-message').remove();
        }
    });

    // Ejecutar cuando el DOM esté listo
    $(document).ready(function() {
        // Inicializar el objeto principal
        MAMUserAccount.init();
        
        // Solo inicializar si estamos en la página de login/registro
        if ($('.mam-login-register-tabs').length > 0) {
            console.log('Inicializando tabs de login/registro');
            
            // Manejar clic en pestaña de login
            $('.mam-login-tab').on('click', function(e) {
                e.preventDefault();
                console.log('Login tab clicked');
                
                // Activar esta pestaña
                $('.mam-login-tab').addClass('active');
                $('.mam-register-tab').removeClass('active');
                
                // Mostrar el formulario correspondiente
                $('.mam-login-form-wrapper').removeClass('hide').show();
                $('.mam-register-form-wrapper').addClass('hide').hide();
                
                // Actualizar clases en html para mayor compatibilidad
                $('html').addClass('js-login-tab-active').removeClass('js-register-tab-active');
            });
            
            // Manejar clic en pestaña de registro
            $('.mam-register-tab').on('click', function(e) {
                e.preventDefault();
                console.log('Register tab clicked');
                
                // Activar esta pestaña
                $('.mam-register-tab').addClass('active');
                $('.mam-login-tab').removeClass('active');
                
                // Mostrar el formulario correspondiente
                $('.mam-register-form-wrapper').removeClass('hide').show();
                $('.mam-login-form-wrapper').addClass('hide').hide();
                
                // Actualizar clases en html para mayor compatibilidad
                $('html').addClass('js-register-tab-active').removeClass('js-login-tab-active');
            });
            
            // Verificar estado inicial basado en URL
            if (window.location.hash === '#register' || 
                window.location.search.indexOf('action=register') > -1) {
                // Simular clic en la pestaña de registro
                $('.mam-register-tab').trigger('click');
            } else {
                // Por defecto, activar pestaña de login
                $('.mam-login-tab').trigger('click');
            }
        }
        
        // Inicializar tabs de login/registro
        initLoginRegisterTabs();
    initCUITFormatting();
    });
})(jQuery);

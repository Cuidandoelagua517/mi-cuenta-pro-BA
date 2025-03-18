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
            
            // Funcionalidades generales para AJAX
            this.initAjaxUI();
        },
        
        /**
         * Inicializar elementos comunes de UI para AJAX
         */
        initAjaxUI: function() {
            // Manejar envío de formularios con clase .mam-ajax-form
            $(document).on('submit', '.mam-ajax-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                var formData = $form.serialize();
                var action = $form.data('action');
                
                if (!action) {
                    console.error('Falta el atributo data-action en el formulario AJAX');
                    return;
                }
                
                // Añadir nonce y acción
                formData += '&action=' + action + '&security=' + mam_params.nonce;
                
                // Mostrar loader
                $submitBtn.prop('disabled', true).addClass('mam-loading');
                
                $.ajax({
                    type: 'POST',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Manejar redirección
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                                return;
                            }
                            
                            // Mostrar mensaje
                            if (response.data.message) {
                                MAM.showMessage($form, 'success', response.data.message);
                            }
                            
                            // Trigger para acciones personalizadas
                            $form.trigger('mam_ajax_success', [response.data]);
                        } else {
                            MAM.showMessage($form, 'error', response.data.message || mam_params.i18n.error);
                        }
                        
                        // Restaurar botón
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    },
                    error: function() {
                        MAM.showMessage($form, 'error', mam_params.i18n.error);
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    }
                });
            });
        },
        
        /**
         * Mostrar mensajes de respuesta
         */
        showMessage: function($context, type, message) {
            var $container = $context.find('.mam-messages');
            
            if ($container.length === 0) {
                $container = $('<div class="mam-messages"></div>');
                $context.prepend($container);
            }
            
            var $message = $('<div class="mam-message mam-message-' + type + '">' + message + '</div>');
            $container.html($message).show();
            
            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                    if ($container.children().length === 0) {
                        $container.hide();
                    }
                });
            }, 5000);
            
            // Scroll para mostrar el mensaje si es necesario
            if (!this.isInViewport($container)) {
                $('html, body').animate({
                    scrollTop: $container.offset().top - 50
                }, 300);
            }
        },
        
        /**
         * Verificar si un elemento está en el viewport
         */
        isInViewport: function($element) {
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            return elementBottom > viewportTop && elementTop < viewportBottom;
        },

        // Funciones faltantes (añadidas como marcadores)
        initTabs: function() {},
        initAjaxLogin: function() {},
        initAjaxRegister: function() {},
        initPasswordToggle: function() {},
        initFormValidation: function() {},
        initMobileMenu: function() {}
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM.init();
    });

})(jQuery);

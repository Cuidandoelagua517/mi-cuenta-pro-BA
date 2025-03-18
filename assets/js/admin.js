/**
 * My Account Manager - Admin Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Admin = {
        /**
         * Inicialización
         */
        init: function() {
            this.initColorPickers();
            this.initTabs();
            this.initPreviewUpdates();
            this.initFormValidation();
            this.initTooltips();
            this.initMetaboxes();
            this.initAJAXSaving();
        },

        /**
         * Inicializar selectores de color
         */
        initColorPickers: function() {
            // Inicializar wp-color-picker para todos los campos con clase .mam-color-picker
            $('.mam-color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Actualizar vista previa cuando cambia el color
                    MAM_Admin.updateColorPreview();
                },
                palettes: true
            });
        },

        /**
         * Inicializar pestañas
         */
        initTabs: function() {
            var $tabButtons = $('.mam-admin-tab');
            var $tabContents = $('.mam-admin-tab-content');

            // Mostrar primer pestaña por defecto
            $tabContents.hide();
            $tabContents.first().show();
            $tabButtons.first().addClass('active');

            // Manejar clics en pestañas
            $tabButtons.on('click', function(e) {
                e.preventDefault();

                var targetId = $(this).data('tab');

                // Actualizar clases activas
                $tabButtons.removeClass('active');
                $(this).addClass('active');

                // Mostrar contenido de la pestaña seleccionada
                $tabContents.hide();
                $('#' + targetId).show();

                // Guardar preferencia en localStorage
                if (typeof localStorage !== 'undefined') {
                    localStorage.setItem('mam_active_tab', targetId);
                }
            });

            // Restaurar última pestaña seleccionada
            if (typeof localStorage !== 'undefined') {
                var lastTab = localStorage.getItem('mam_active_tab');
                if (lastTab) {
                    var $targetTab = $('[data-tab="' + lastTab + '"]');
                    if ($targetTab.length) {
                        $targetTab.trigger('click');
                    }
                }
            }
        },

        /**
         * Inicializar actualizaciones de vista previa
         */
        initPreviewUpdates: function() {
            // Actualizar colores en la vista previa al cambiar opciones
            $('#mam_primary_color, #mam_secondary_color').on('change', this.updateColorPreview);

            // Actualizar vista previa inicialmente
            this.updateColorPreview();
        },

        /**
         * Actualizar colores en la vista previa
         */
        updateColorPreview: function() {
            var primaryColor = $('#mam_primary_color').val() || '#4a6cf7';
            var secondaryColor = $('#mam_secondary_color').val() || '#6b7280';

            // Actualizar botones de vista previa
            $('.mam-preview-primary').css('background-color', primaryColor);
            $('.mam-preview-secondary').css('background-color', secondaryColor);

            // Generar CSS personalizado
            var customCSS = `
                :root {
                    --mam-primary-color: ${primaryColor};
                    --mam-secondary-color: ${secondaryColor};
                }
            `;

            // Actualizar o crear estilo en línea
            var $customStyle = $('#mam-custom-preview-styles');
            if ($customStyle.length === 0) {
                $('head').append('<style id="mam-custom-preview-styles"></style>');
                $customStyle = $('#mam-custom-preview-styles');
            }
            $customStyle.html(customCSS);
        },

        /**
         * Inicializar validación de formularios
         */
        initFormValidation: function() {
            // Validar formulario de configuración antes de enviar
            $('#mam-settings-form').on('submit', function(e) {
                var isValid = true;

                // Validar campos requeridos
                $(this).find('[required]').each(function() {
                    if ($(this).val() === '') {
                        isValid = false;
                        $(this).addClass('mam-invalid');
                        
                        // Mostrar mensaje de error
                        if ($(this).siblings('.mam-error-message').length === 0) {
                            $(this).after('<span class="mam-error-message">Este campo es obligatorio</span>');
                        }
                    } else {
                        $(this).removeClass('mam-invalid');
                        $(this).siblings('.mam-error-message').remove();
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    
                    // Mostrar notificación de error
                    MAM_Admin.showNotice('error', 'Por favor, completa todos los campos obligatorios.');
                    
                    // Desplazarse al primer campo con error
                    $('html, body').animate({
                        scrollTop: $('.mam-invalid').first().offset().top - 100
                    }, 500);
                }
            });

            // Limpiar errores al cambiar los campos
            $(document).on('change keyup', '.mam-invalid', function() {
                if ($(this).val() !== '') {
                    $(this).removeClass('mam-invalid');
                    $(this).siblings('.mam-error-message').remove();
                }
            });
        },

        /**
         * Mostrar notificación
         */
        showNotice: function(type, message) {
            var noticeClass = 'notice ';
            
            switch (type) {
                case 'success':
                    noticeClass += 'notice-success';
                    break;
                case 'error':
                    noticeClass += 'notice-error';
                    break;
                case 'warning':
                    noticeClass += 'notice-warning';
                    break;
                case 'info':
                default:
                    noticeClass += 'notice-info';
                    break;
            }
            
            // Crear y mostrar la notificación
            var $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Añadir notificación después del encabezado de la página
            $('.wrap h1, .wrap h2').first().after($notice);
            
            // Añadir botón de cierre
            $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Descartar este aviso.</span></button>');
            
            // Manejar cierre de notificación
            $notice.find('.notice-dismiss').on('click', function() {
                $(this).closest('.notice').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-cerrar después de 5 segundos
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Inicializar tooltips
         */
        initTooltips: function() {
            $('.mam-help-tip').on('mouseenter', function() {
                var $tip = $(this);
                var tipText = $tip.data('tip');
                
                // Crear tooltip si no existe
                if ($('#mam-tooltip').length === 0) {
                    $('body').append('<div id="mam-tooltip"></div>');
                }
                
                // Posicionar y mostrar tooltip
                var $tooltip = $('#mam-tooltip');
                $tooltip.html(tipText);
                
                var tipOffset = $tip.offset();
                var tipWidth = $tip.outerWidth();
                var tooltipWidth = $tooltip.outerWidth();
                
                $tooltip.css({
                    top: tipOffset.top - $tooltip.outerHeight() - 10,
                    left: tipOffset.left + (tipWidth / 2) - (tooltipWidth / 2)
                }).fadeIn(200);
            }).on('mouseleave', function() {
                $('#mam-tooltip').fadeOut(200);
            });
        },

        /**
         * Inicializar metaboxes
         */
        initMetaboxes: function() {
            // Hacer metaboxes ordenables
            $('.meta-box-sortables').sortable({
                placeholder: 'sortable-placeholder',
                handle: '.hndle',
                cursor: 'move',
                distance: 2,
                tolerance: 'pointer',
                forcePlaceholderSize: true,
                helper: 'clone',
                opacity: 0.65
            });
            
            // Hacer metaboxes collapsibles
            $('.postbox .hndle, .postbox .handlediv').on('click', function() {
                $(this).closest('.postbox').toggleClass('closed');
            });
        },

        /**
         * Inicializar guardado con AJAX
         */
        initAJAXSaving: function() {
            $('#mam-ajax-save-button').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $form = $button.closest('form');
                var formData = $form.serialize();
                
                // Añadir acción
                formData += '&action=mam_save_settings';
                
                // Deshabilitar botón y mostrar loader
                $button.prop('disabled', true).addClass('updating-message');
                
                // Enviar solicitud AJAX
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            MAM_Admin.showNotice('success', response.data.message);
                        } else {
                            MAM_Admin.showNotice('error', response.data.message);
                        }
                        
                        // Restaurar botón
                        $button.prop('disabled', false).removeClass('updating-message');
                    },
                    error: function() {
                        MAM_Admin.showNotice('error', 'Ha ocurrido un error al guardar la configuración.');
                        $button.prop('disabled', false).removeClass('updating-message');
                    }
                });
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Admin.init();
    });

})(jQuery);

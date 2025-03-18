// Añadir a assets/js/addresses.js (nuevo archivo)
(function($) {
    'use strict';
    
    var MAM_Addresses = {
        init: function() {
            this.initAddressForm();
            this.initDeleteAddress();
            this.initSetDefaultAddress();
        },
        
        initAddressForm: function() {
            var self = this;
            
            // Manejar envío del formulario
            $('.mam-address-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var formData = $form.serialize();
                
                // Añadir nonce y acción
                formData += '&action=mam_save_address&security=' + mam_params.nonce;
                
                // Deshabilitar botón y mostrar loader
                var $submitBtn = $form.find('button[type="submit"]');
                $submitBtn.prop('disabled', true).addClass('mam-loading');
                
                $.ajax({
                    type: 'POST',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Actualizar lista de direcciones
                            $('.mam-addresses-list').html(response.data.html);
                            
                            // Mostrar mensaje de éxito
                            self.showMessage('success', response.data.message);
                            
                            // Cerrar formulario
                            $('.mam-address-form-container').slideUp();
                        } else {
                            // Mostrar error
                            self.showMessage('error', response.data.message);
                        }
                        
                        // Restaurar botón
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    },
                    error: function() {
                        self.showMessage('error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                        $submitBtn.prop('disabled', false).removeClass('mam-loading');
                    }
                });
            });
            
            // Mostrar formulario al hacer clic en "Añadir dirección"
            $('.mam-add-address a').on('click', function(e) {
                e.preventDefault();
                $('.mam-address-form-container').slideDown();
                $('html, body').animate({
                    scrollTop: $('.mam-address-form-container').offset().top - 50
                }, 500);
            });
        },
        
        showMessage: function(type, message) {
            var $messageContainer = $('.mam-messages');
            
            if ($messageContainer.length === 0) {
                $messageContainer = $('<div class="mam-messages"></div>');
                $('.mam-addresses-header').after($messageContainer);
            }
            
            var $message = $('<div class="mam-message mam-message-' + type + '">' + message + '</div>');
            $messageContainer.html($message);
            
            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Implementar initDeleteAddress y initSetDefaultAddress
    };
    
    $(document).ready(function() {
        MAM_Addresses.init();
    });
})(jQuery);

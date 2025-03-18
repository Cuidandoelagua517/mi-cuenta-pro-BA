// Añadir a assets/js/addresses.js (nuevo archivo)
(function($) {
    'use strict';
    
    var MAM_Addresses = {
        init: function() {
            this.initAddressForm();
            this.initDeleteAddress();
            this.initSetDefaultAddress();
        },
        
        initDeleteAddress: function() {
    $('.mam-delete-address').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(mam_params.i18n.confirm_delete)) {
            var addressId = $(this).data('address-id');
            var $item = $(this).closest('.mam-address-item');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: {
                    action: 'mam_delete_address',
                    security: mam_params.nonce,
                    address_id: addressId
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                        });
                        MAM_Addresses.showMessage('success', response.data.message);
                    } else {
                        MAM_Addresses.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    MAM_Addresses.showMessage('error', mam_params.i18n.error);
                }
            });
        }
    });
},
  initSetDefaultAddress: function() {
    $('.mam-set-default').on('click', function(e) {
        e.preventDefault();
        
        var addressId = $(this).data('address-id');
        var addressType = $(this).data('address-type');
        
        $.ajax({
            type: 'POST',
            url: mam_params.ajax_url,
            data: {
                action: 'mam_set_default_address',
                security: mam_params.nonce,
                address_id: addressId,
                address_type: addressType
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    MAM_Addresses.showMessage('error', response.data.message);
                }
            },
            error: function() {
                MAM_Addresses.showMessage('error', mam_params.i18n.error);
            }
        });
    });
}      
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

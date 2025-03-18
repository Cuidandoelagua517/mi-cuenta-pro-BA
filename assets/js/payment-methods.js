/**
 * My Account Manager - Payment Methods Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Payments = {
        /**
         * Inicialización
         */
        init: function() {
            this.initCardFormatting();
            this.initCardFlip();
            this.initCardValidation();
            this.initDefaultPaymentSelection();
            this.initCardVisualEffects();
            this.attachTableModeSwitch();
        },

        /**
         * Inicializar formato de campos de tarjeta
         */
        initCardFormatting: function() {
            var self = this;
            
            // Formatear número de tarjeta
            $('input.wc-credit-card-form-card-number').on('keyup', function() {
                var value = $(this).val().replace(/\D/g, '');
                var formatted = self.formatCardNumber(value);
                $(this).val(formatted);
            });
            
            // Formatear fecha de expiración
            $('input.wc-credit-card-form-card-expiry').on('keyup', function() {
                var value = $(this).val().replace(/\D/g, '');
                
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                $(this).val(value);
            });
            
            // Limitar CVV a 3-4 dígitos
            $('input.wc-credit-card-form-card-cvc').on('keyup', function() {
                var value = $(this).val().replace(/\D/g, '');
                $(this).val(value.substring(0, 4));
            });
        },

        /**
         * Formatear número de tarjeta con espacios
         */
        formatCardNumber: function(value) {
            var result = '';
            
            // Añadir un espacio cada 4 dígitos
            for (var i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    result += ' ';
                }
                result += value.charAt(i);
            }
            
            return result;
        },

        /**
         * Inicializar efecto de flip de tarjeta
         */
        initCardFlip: function() {
            var $formContainer = $('.payment_method_stripe, .payment_box');
            
            if (!$formContainer.length) {
                return;
            }
            
            // Crear contenedor para la tarjeta visual
            if (!$('.mam-card-preview').length) {
                var cardHTML = `
                    <div class="mam-card-preview">
                        <div class="mam-card-inner">
                            <div class="mam-card-front">
                                <div class="mam-card-header">
                                    <div class="mam-card-logo">
                                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect width="40" height="40" rx="8" fill="#4a6cf7"/>
                                            <text x="20" y="25" font-family="Arial" font-size="14" fill="white" text-anchor="middle">CARD</text>
                                        </svg>
                                    </div>
                                    <div class="mam-card-chip">
                                        <svg width="30" height="25" viewBox="0 0 30 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect width="30" height="25" rx="4" fill="#FFD700"/>
                                            <rect x="5" y="5" width="20" height="15" rx="2" fill="#DAA520"/>
                                            <rect x="10" y="10" width="10" height="5" fill="#FFD700"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="mam-card-number">•••• •••• •••• ••••</div>
                                <div class="mam-card-footer">
                                    <div class="mam-card-holder">NOMBRE DEL TITULAR</div>
                                    <div class="mam-card-expiry">
                                        <div class="mam-expiry-label">Válida hasta</div>
                                        <div class="mam-expiry-date">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mam-card-back">
                                <div class="mam-card-stripe"></div>
                                <div class="mam-card-cvc">
                                    <div class="mam-cvc-label">CVC</div>
                                    <div class="mam-cvc-code">•••</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $formContainer.prepend(cardHTML);
            }
            
            var $card = $('.mam-card-preview');
            var $cardInner = $('.mam-card-inner');
            var $cardNumber = $('.mam-card-number');
            var $cardHolder = $('.mam-card-holder');
            var $cardExpiry = $('.mam-expiry-date');
            var $cardCvc = $('.mam-cvc-code');
            
            // Actualizar número de tarjeta
            $('input.wc-credit-card-form-card-number').on('keyup', function() {
                var value = $(this).val();
                
                if (value === '') {
                    $cardNumber.text('•••• •••• •••• ••••');
                } else {
                    $cardNumber.text(value);
                }
            });
            
            // Actualizar nombre del titular
            $('input[name="billing_first_name"], input[name="billing_last_name"]').on('keyup', function() {
                var firstName = $('input[name="billing_first_name"]').val();
                var lastName = $('input[name="billing_last_name"]').val();
                var fullName = (firstName + ' ' + lastName).trim();
                
                if (fullName === '') {
                    $cardHolder.text('NOMBRE DEL TITULAR');
                } else {
                    $cardHolder.text(fullName.toUpperCase());
                }
            });
            
            // Actualizar fecha de expiración
            $('input.wc-credit-card-form-card-expiry').on('keyup', function() {
                var value = $(this).val();
                
                if (value === '') {
                    $cardExpiry.text('MM/AA');
                } else {
                    $cardExpiry.text(value);
                }
            });
            
            // Mostrar parte trasera cuando se enfoca en CVC
            $('input.wc-credit-card-form-card-cvc').on('focus', function() {
                $cardInner.addClass('flipped');
            }).on('blur', function() {
                $cardInner.removeClass('flipped');
            }).on('keyup', function() {
                var value = $(this).val();
                
                if (value === '') {
                    $cardCvc.text('•••');
                } else {
                    $cardCvc.text(value);
                }
            });
            
            // Detectar tipo de tarjeta
            $('input.wc-credit-card-form-card-number').on('keyup', function() {
                var value = $(this).val().replace(/\s/g, '');
                var cardType = MAM_Payments.detectCardType(value);
                
                // Actualizar logo según tipo de tarjeta
                if (cardType !== '') {
                    var cardLogo = `
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="8" fill="#4a6cf7"/>
                            <text x="20" y="25" font-family="Arial" font-size="12" fill="white" text-anchor="middle">${cardType}</text>
                        </svg>
                    `;
                    
                    $('.mam-card-logo').html(cardLogo);
                }
            });
        },

        /**
         * Detectar tipo de tarjeta
         */
        detectCardType: function(number) {
            // Patrones de tarjeta
            var patterns = {
                visa: /^4/,
                mastercard: /^5[1-5]/,
                amex: /^3[47]/,
                discover: /^6(?:011|5[0-9]{2})/,
                diners: /^3(?:0[0-5]|[68][0-9])/,
                jcb: /^(?:2131|1800|35\d{3})/
            };
            
            // Comprobar cada patrón
            for (var card in patterns) {
                if (patterns[card].test(number)) {
                    return card.toUpperCase();
                }
            }
            
            // Si no coincide con ningún patrón, devolver cadena vacía
            return '';
        },

        /**
         * Inicializar validación de tarjeta
         */
        initCardValidation: function() {
            // Validar número de tarjeta con algoritmo de Luhn
            $('input.wc-credit-card-form-card-number').on('blur', function() {
                var value = $(this).val().replace(/\D/g, '');
                
                if (value.length > 0 && !MAM_Payments.validateCardNumber(value)) {
                    $(this).addClass('mam-invalid');
                    
                    // Mostrar error solo si no existe
                    if ($(this).siblings('.mam-error-message').length === 0) {
                        $(this).after('<span class="mam-error-message">Número de tarjeta no válido</span>');
                    }
                } else {
                    $(this).removeClass('mam-invalid');
                    $(this).siblings('.mam-error-message').remove();
                }
            });
            
            // Validar fecha de expiración
            $('input.wc-credit-card-form-card-expiry').on('blur', function() {
                var value = $(this).val();
                var valid = MAM_Payments.validateExpiryDate(value);
                
                if (value.length > 0 && !valid) {
                    $(this).addClass('mam-invalid');
                    
                    // Mostrar error solo si no existe
                    if ($(this).siblings('.mam-error-message').length === 0) {
                        $(this).after('<span class="mam-error-message">Fecha de expiración no válida</span>');
                    }
                } else {
                    $(this).removeClass('mam-invalid');
                    $(this).siblings('.mam-error-message').remove();
                }
            });
            
            // Validar CVC
            $('input.wc-credit-card-form-card-cvc').on('blur', function() {
                var value = $(this).val();
                
                if (value.length > 0 && value.length < 3) {
                    $(this).addClass('mam-invalid');
                    
                    // Mostrar error solo si no existe
                    if ($(this).siblings('.mam-error-message').length === 0) {
                        $(this).after('<span class="mam-error-message">CVC no válido</span>');
                    }
                } else {
                    $(this).removeClass('mam-invalid');
                    $(this).siblings('.mam-error-message').remove();
                }
            });
        },

        /**
         * Validar número de tarjeta con algoritmo de Luhn
         */
        validateCardNumber: function(number) {
            var sum = 0;
            var shouldDouble = false;
            
            // Recorrer los dígitos de derecha a izquierda
            for (var i = number.length - 1; i >= 0; i--) {
                var digit = parseInt(number.charAt(i));
                
                if (shouldDouble) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
                shouldDouble = !shouldDouble;
            }
            
            return (sum % 10) === 0;
        },

        /**
         * Validar fecha de expiración
         */
        validateExpiryDate: function(value) {
            // Formato esperado: MM/YY
            if (!/^\d{2}\/\d{2}$/.test(value)) {
                return false;
            }
            
            var parts = value.split('/');
            var month = parseInt(parts[0], 10);
            var year = parseInt('20' + parts[1], 10);
            
            // Validar mes
            if (month < 1 || month > 12) {
                return false;
            }
            
            // Validar año (debe ser el actual o futuro)
            var currentDate = new Date();
            var currentYear = currentDate.getFullYear();
            var currentMonth = currentDate.getMonth() + 1; // getMonth() devuelve 0-11
            
            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                return false;
            }
            
            return true;
        },

        /**
         * Inicializar selección de método predeterminado
         */
       initDefaultPaymentSelection: function() {
    var self = this;
    
    $('.mam-set-default-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button');
        var token_id = $form.find('input[name="payment_token_id"]').val();
        
        // Mostrar loader
        $button.prop('disabled', true).addClass('mam-loading');
        
        $.ajax({
            type: 'POST',
            url: mam_params.ajax_url,
            data: {
                action: 'mam_set_default_payment',
                security: mam_params.nonce,
                payment_token_id: token_id
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar HTML de las tarjetas
                    $('.mam-payment-cards').html(response.data.html);
                    
                    // Reinicializar eventos después de actualizar el DOM
                    self.initCardFlip();
                    self.initVisualEffects();
                    
                    // Mostrar mensaje de éxito
                    self.showMessage('success', response.data.message);
                } else {
                    self.showMessage('error', response.data.message);
                }
                
                // Restaurar botón
                $button.prop('disabled', false).removeClass('mam-loading');
            },
            error: function() {
                self.showMessage('error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                $button.prop('disabled', false).removeClass('mam-loading');
            }
        });
    });
},


        /**
         * Inicializar efectos visuales de tarjetas
         */
       initCardVisualEffects: function() {
    var self = this;
    
    // Añadir efecto hover
    $('.mam-payment-card').hover(
        function() {
            $(this).addClass('mam-card-hover');
        },
        function() {
            $(this).removeClass('mam-card-hover');
        }
    );
    
    // Añadir animación al eliminar por AJAX
    $('.mam-delete-payment-form').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('¿Estás seguro de que quieres eliminar esta tarjeta?')) {
            var $form = $(this);
            var $card = $form.closest('.mam-payment-card');
            var token_id = $form.find('input[name="payment_token_id"]').val();
            
            // Añadir efecto visual de eliminación
            $card.addClass('mam-card-removing');
            
            $.ajax({
                type: 'POST',
                url: mam_params.ajax_url,
                data: {
                    action: 'mam_delete_payment',
                    security: mam_params.nonce,
                    payment_token_id: token_id
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar HTML de las tarjetas
                        setTimeout(function() {
                            $('.mam-payment-cards').html(response.data.html);
                            
                            // Reinicializar eventos
                            self.initCardFlip();
                            self.initVisualEffects();
                            
                            // Mostrar mensaje
                            self.showMessage('success', response.data.message);
                        }, 300); // Esperar a que termine la animación
                    } else {
                        $card.removeClass('mam-card-removing');
                        self.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    $card.removeClass('mam-card-removing');
                    self.showMessage('error', 'Error de conexión. Por favor, inténtalo de nuevo.');
                }
            });
        }
    });
},
showMessage: function(type, message) {
    var $messageContainer = $('.mam-payment-messages');
    
    if ($messageContainer.length === 0) {
        $messageContainer = $('<div class="mam-payment-messages"></div>');
        $('.mam-payment-methods-header').after($messageContainer);
    }
    
    var $message = $('<div class="mam-message mam-message-' + type + '">' + message + '</div>');
    $messageContainer.html($message);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $message.fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}
        /**
         * Adjuntar interruptor de modo tabla
         */
        attachTableModeSwitch: function() {
            // Manejar cambio entre vista de tabla y tarjetas
            $('.mam-view-as-table a').on('click', function(e) {
                e.preventDefault();
                
                // Guardar preferencia en cookie
                document.cookie = 'mam_payment_view=table; path=/; max-age=31536000'; // 1 año
                window.location.href = $(this).attr('href');
            });
            
            // Manejar cambio de tabla a tarjetas
            $('.mam-view-as-cards a').on('click', function(e) {
                e.preventDefault();
                
                // Guardar preferencia en cookie
                document.cookie = 'mam_payment_view=cards; path=/; max-age=31536000'; // 1 año
                window.location.href = $(this).attr('href');
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Payments.init();
    });

})(jQuery);

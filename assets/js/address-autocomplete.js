/**
 * My Account Manager - Address Autocomplete
 * Implementa autocompletado de direcciones utilizando Google Places API
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Address = {
        /**
         * Inicialización
         */
        init: function() {
            this.initAutocomplete();
            this.initAddressCopy();
            this.initCountryStateUpdates();
            this.initValidation();
            this.initAddressCardSelection();
        },

        /**
         * Inicializar autocompletado de direcciones
         */
        initAutocomplete: function() {
            var self = this;
            
            // Verificar si Google Places API está disponible
            if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
                console.log('Google Places API no está disponible');
                return;
            }
            
            // Campos de dirección en el formulario
            var addressFields = {
                shipping: [
                    'shipping_address_1',
                    'mam_address_address_1'
                ],
                billing: [
                    'billing_address_1',
                    'mam_address_address_1'
                ]
            };
            
            // Inicializar autocompletado para cada campo
            $.each(addressFields, function(type, fields) {
                $.each(fields, function(index, field) {
                    var input = document.getElementById(field);
                    
                    if (input) {
                        self.setupAutocomplete(input, type);
                    }
                });
            });
        },

        /**
         * Configurar autocompletado para un campo
         */
        setupAutocomplete: function(input, addressType) {
            var self = this;
            
            // Opciones para el autocompletado
            var options = {
                types: ['address']
            };
            
            // Restringir autocompletado al país seleccionado
            var $countryField = $('#' + addressType + '_country, #mam_address_country');
            if ($countryField.length && $countryField.val()) {
                options.componentRestrictions = {
                    country: $countryField.val()
                };
            }
            
            // Crear instancia de autocompletado
            var autocomplete = new google.maps.places.Autocomplete(input, options);
            
            // Almacenar tipo de dirección
            autocomplete.addressType = addressType;
            
            // Manejar selección de lugar
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    // El usuario presionó enter sin seleccionar un lugar
                    return;
                }
                
                // Procesar los componentes de la dirección
                self.fillAddressFields(place, autocomplete.addressType);
            });
            
            // Prevenir envío del formulario al presionar enter
            $(input).on('keydown', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                }
            });
            
            // Actualizar restricciones al cambiar país
            $countryField.on('change', function() {
                var country = $(this).val();
                
                if (country) {
                    autocomplete.setComponentRestrictions({
                        country: country
                    });
                } else {
                    autocomplete.setComponentRestrictions({});
                }
            });
        },

        /**
         * Rellenar campos de dirección
         */
        fillAddressFields: function(place, addressType) {
            var prefix = addressType === 'shipping' ? 'shipping_' : 'billing_';
            
            // Si estamos en el formulario de direcciones adicionales
            if ($('#mam_address_address_1').length) {
                prefix = 'mam_address_';
            }
            
            // Componentes de dirección
            var componentMapping = {
                street_number: { field: 'address_1', value: '', isNumber: true },
                route: { field: 'address_1', value: '', isStreet: true },
                locality: { field: 'city', value: '' },
                administrative_area_level_1: { field: 'state', value: '' },
                country: { field: 'country', value: '' },
                postal_code: { field: 'postcode', value: '' }
            };
            
            // Extraer componentes
            for (var i = 0; i < place.address_components.length; i++) {
                var addressType = place.address_components[i].types[0];
                
                if (componentMapping[addressType]) {
                    var val = place.address_components[i]['long_name'];
                    componentMapping[addressType].value = val;
                }
            }
            
            // Direcciones especiales para algunos países
            var countryCode = '';
            for (var i = 0; i < place.address_components.length; i++) {
                if (place.address_components[i].types[0] === 'country') {
                    countryCode = place.address_components[i]['short_name'];
                    break;
                }
            }
            
            // Formatear dirección según el país
            var address1 = '';
            
            if (componentMapping.street_number.value && componentMapping.route.value) {
                if (countryCode === 'ES' || countryCode === 'FR' || countryCode === 'IT') {
                    // Formato: Calle, Número (España, Francia, Italia)
                    address1 = componentMapping.route.value + ', ' + componentMapping.street_number.value;
                } else {
                    // Formato: Número Calle (EE.UU., Reino Unido, etc.)
                    address1 = componentMapping.street_number.value + ' ' + componentMapping.route.value;
                }
            } else if (componentMapping.route.value) {
                address1 = componentMapping.route.value;
            }
            
            // Rellenar campos
            if (address1) {
                $('#' + prefix + 'address_1').val(address1);
            }
            
            if (componentMapping.locality.value) {
                $('#' + prefix + 'city').val(componentMapping.locality.value);
            }
            
            if (componentMapping.postal_code.value) {
                $('#' + prefix + 'postcode').val(componentMapping.postal_code.value);
            }
            
            // País y estado requieren un tratamiento especial
            if (componentMapping.country.value && $('#' + prefix + 'country').length) {
                $('#' + prefix + 'country').val(countryCode).trigger('change');
                
                // Esperar a que se carguen los estados y luego seleccionar
                if (componentMapping.administrative_area_level_1.value) {
                    var stateField = prefix + 'state';
                    var stateName = componentMapping.administrative_area_level_1.value;
                    
                    // Esperar a que se carguen los campos de estado
                    setTimeout(function() {
                        // Intento de seleccionar por nombre o código
                        var $stateField = $('#' + stateField);
                        
                        if ($stateField.is('select')) {
                            // Buscar coincidencia aproximada
                            var stateFound = false;
                            
                            $stateField.find('option').each(function() {
                                var optionText = $(this).text().toLowerCase();
                                var stateNameLower = stateName.toLowerCase();
                                
                                if (optionText === stateNameLower || optionText.indexOf(stateNameLower) !== -1) {
                                    $stateField.val($(this).val()).trigger('change');
                                    stateFound = true;
                                    return false; // Romper bucle
                                }
                            });
                            
                            // Si no se encontró, establecer como texto
                            if (!stateFound && $stateField.is('input')) {
                                $stateField.val(stateName);
                            }
                        } else {
                            // Es un campo de texto
                            $stateField.val(stateName);
                        }
                    }, 500);
                }
            }
        },

        /**
         * Inicializar copia de dirección
         */
        initAddressCopy: function() {
            // Copiar dirección de facturación a envío
            $('#mam_copy_billing_address').on('change', function() {
                if ($(this).is(':checked')) {
                    MAM_Address.copyAddress('billing', 'shipping');
                }
            });
            
            // Seleccionar dirección guardada
            $('#mam_saved_addresses').on('change', function() {
                var addressId = $(this).val();
                
                if (addressId) {
                    // Obtener datos de la dirección guardada
                    $.ajax({
                        type: 'POST',
                        url: mam_params.ajax_url,
                        data: {
                            action: 'mam_get_saved_address',
                            address_id: addressId,
                            security: mam_params.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                MAM_Address.fillAddressFromData(response.data, 'shipping');
                            }
                        }
                    });
                }
            });
        },

        /**
         * Copiar dirección de un tipo a otro
         */
        copyAddress: function(fromType, toType) {
            // Campos a copiar
            var fields = [
                'first_name',
                'last_name',
                'company',
                'country',
                'address_1',
                'address_2',
                'city',
                'state',
                'postcode',
                'phone'
            ];
            
            // Copiar cada campo
            $.each(fields, function(index, field) {
                var fromField = '#' + fromType + '_' + field;
                var toField = '#' + toType + '_' + field;
                
                if ($(fromField).length && $(toField).length) {
                    var value = $(fromField).val();
                    
                    $(toField).val(value);
                    
                    // Para campos select, activar evento change
                    if ($(toField).is('select')) {
                        $(toField).trigger('change');
                    }
                }
            });
        },

        /**
         * Rellenar dirección a partir de datos
         */
        fillAddressFromData: function(data, type) {
            // Campos a rellenar
            var fields = [
                'first_name',
                'last_name',
                'company',
                'country',
                'address_1',
                'address_2',
                'city',
                'state',
                'postcode',
                'phone'
            ];
            
            // Rellenar cada campo
            $.each(fields, function(index, field) {
                var selector = '#' + type + '_' + field;
                
                if ($(selector).length && data[field]) {
                    $(selector).val(data[field]);
                    
                    // Para campos select, activar evento change
                    if ($(selector).is('select')) {
                        $(selector).trigger('change');
                    }
                }
            });
        },

        /**
         * Inicializar actualizaciones de país/estado
         */
        initCountryStateUpdates: function() {
            // Manejar cambio de país para cargar estados
            $(document).on('change', 'select.country_to_state, #billing_country, #shipping_country, #mam_address_country', function() {
                var $this = $(this);
                var country = $this.val();
                var $stateField;
                
                // Determinar campo de estado correspondiente
                if ($this.attr('id') === 'billing_country') {
                    $stateField = $('#billing_state');
                } else if ($this.attr('id') === 'shipping_country') {
                    $stateField = $('#shipping_state');
                } else if ($this.attr('id') === 'mam_address_country') {
                    $stateField = $('#mam_address_state');
                } else {
                    return;
                }
                
                // Si hay un campo de estado, actualizar opciones
                if ($stateField.length) {
                    // Esta funcionalidad depende de WooCommerce
                    // y se activa automáticamente al cambiar el país
                }
            });
        },

        /**
         * Inicializar validación de campos
         */
        initValidation: function() {
            // Validar código postal
            $(document).on('blur', 'input[name$="_postcode"]', function() {
                var $this = $(this);
                var postcode = $this.val();
                var country = $('#' + $this.attr('id').replace('postcode', 'country')).val();
                
                if (postcode && country) {
                    // Validar según formato del país
                    var isValid = MAM_Address.validatePostcode(postcode, country);
                    
                    if (!isValid) {
                        $this.addClass('mam-invalid');
                        
                        // Añadir mensaje de error si no existe
                        if ($this.siblings('.mam-error-message').length === 0) {
                            $this.after('<span class="mam-error-message">Código postal no válido para este país</span>');
                        }
                    } else {
                        $this.removeClass('mam-invalid');
                        $this.siblings('.mam-error-message').remove();
                    }
                }
            });
            
            // Validar número de teléfono
            $(document).on('blur', 'input[name$="_phone"]', function() {
                var $this = $(this);
                var phone = $this.val();
                
                if (phone) {
                    // Validación básica de formato de teléfono
                    var isValid = MAM_Address.validatePhone(phone);
                    
                    if (!isValid) {
                        $this.addClass('mam-invalid');
                        
                        // Añadir mensaje de error si no existe
                        if ($this.siblings('.mam-error-message').length === 0) {
                            $this.after('<span class="mam-error-message">Número de teléfono no válido</span>');
                        }
                    } else {
                        $this.removeClass('mam-invalid');
                        $this.siblings('.mam-error-message').remove();
                    }
                }
            });
        },

        /**
         * Validar código postal
         */
        validatePostcode: function(postcode, country) {
            // Formatos comunes de código postal por país
            var patterns = {
                'ES': /^(?:0[1-9]|[1-4]\d|5[0-2])\d{3}$/, // España: 5 dígitos (01000-52999)
                'US': /^\d{5}(-\d{4})?$/, // Estados Unidos: 5 dígitos o 5+4
                'GB': /^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i, // Reino Unido
                'CA': /^[ABCEGHJKLMNPRSTVXY]\d[ABCEGHJ-NPRSTV-Z] ?\d[ABCEGHJ-NPRSTV-Z]\d$/i, // Canadá
                'DE': /^\d{5}$/, // Alemania: 5 dígitos
                'FR': /^\d{5}$/, // Francia: 5 dígitos
                'IT': /^\d{5}$/, // Italia: 5 dígitos
                'AU': /^\d{4}$/, // Australia: 4 dígitos
                'NL': /^\d{4} ?[A-Z]{2}$/i, // Países Bajos
                'BE': /^\d{4}$/ // Bélgica: 4 dígitos
            };
            
            // Si no hay patrón para el país, permitir cualquier formato
            if (!patterns[country]) {
                return true;
            }
            
            return patterns[country].test(postcode);
        },

        /**
         * Validar número de teléfono
         */
        validatePhone: function(phone) {
            // Validación básica: permitir dígitos, espacios, paréntesis, guiones y signos +
            var pattern = /^[0-9+\s()-]{6,20}$/;
            return pattern.test(phone);
        },

        /**
         * Inicializar selección de tarjetas de dirección
         */
        initAddressCardSelection: function() {
            // Seleccionar tarjeta de dirección al hacer clic
            $('.mam-address-item').on('click', function(e) {
                // No activar si se hace clic en botones o enlaces
                if ($(e.target).is('a, button, .mam-button') || $(e.target).closest('a, button, .mam-button').length) {
                    return;
                }
                
                $('.mam-address-item').removeClass('mam-address-selected');
                $(this).addClass('mam-address-selected');
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Address.init();
    });

})(jQuery);

(function($) {
    'use strict';
    
    var MAM_Orders = {
        init: function() {
            this.initFilterOrders();
            this.initOrderDetails();
            this.initPagination();
        },
        
        initFilterOrders: function() {
            $('#order_status, #mam-sort-orders').on('change', function() {
                var filterForm = $(this).closest('form');
                var formData = filterForm.serialize();
                
                // Añadir nonce
                formData += '&action=mam_filter_orders&security=' + mam_params.nonce;
                
                // Mostrar loader
                $('.mam-orders-container').addClass('mam-loading');
                
                $.ajax({
                    type: 'POST',
                    url: mam_params.ajax_url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('.mam-orders-table tbody').html(response.data.html);
                            
                            // Actualizar contadores si es necesario
                            if (response.data.count === 0) {
                                $('.mam-orders-empty-results').show();
                            } else {
                                $('.mam-orders-empty-results').hide();
                            }
                        } else {
                            // Mostrar error
                            alert(response.data.message || 'Error al filtrar pedidos');
                        }
                        
                        // Ocultar loader
                        $('.mam-orders-container').removeClass('mam-loading');
                    },
                    error: function() {
                        alert('Error de conexión. Por favor, inténtalo de nuevo.');
                        $('.mam-orders-container').removeClass('mam-loading');
                    }
                });
            });
        },
        
        // Implementar initOrderDetails y initPagination
    };
    
    $(document).ready(function() {
        MAM_Orders.init();
    });
})(jQuery);

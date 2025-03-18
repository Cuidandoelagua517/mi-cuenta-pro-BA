(function($) {
    'use strict';
    
    var MAM_Orders = {
        init: function() {
            this.initFilterOrders();
            this.initOrderDetails();
            this.initPagination();
        },
      initOrderDetails: function() {
    // Manejo de carga AJAX de detalles de pedido
    $('.mam-order-details-button').on('click', function(e) {
        e.preventDefault();
        
        var orderId = $(this).data('order-id');
        var $row = $(this).closest('tr');
        var $detailsRow = $row.next('.mam-order-details-row');
        
        // Si ya está cargado, solo mostrar/ocultar
        if ($detailsRow.length) {
            $detailsRow.slideToggle(300);
            return;
        }
        
        // Cargar detalles vía AJAX
        $.ajax({
            type: 'POST',
            url: mam_params.ajax_url,
            data: {
                action: 'mam_load_order_details',
                security: mam_params.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    // Insertar HTML y mostrar
                    $row.after(response.data.html);
                    $row.next('.mam-order-details-row').slideDown(300);
                } else {
                    alert(response.data.message || mam_params.i18n.error);
                }
            },
            error: function() {
                alert(mam_params.i18n.error);
            }
        });
    });
}, 
        initPagination: function() {
    $('.mam-pagination a').on('click', function(e) {
        e.preventDefault();
        
        var page = $(this).data('page');
        var status = $('#order_status').val() || '';
        
        // Mostrar loader
        $('.mam-orders-container').addClass('mam-loading');
        
        $.ajax({
            type: 'POST',
            url: mam_params.ajax_url,
            data: {
                action: 'mam_paginate_orders',
                security: mam_params.nonce,
                page: page,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    $('.mam-orders-table tbody').html(response.data.html);
                    
                    // Actualizar URL sin recargar la página
                    var url = new URL(window.location.href);
                    url.searchParams.set('paged', page);
                    window.history.pushState({}, '', url);
                } else {
                    alert(response.data.message || mam_params.i18n.error);
                }
                
                // Ocultar loader
                $('.mam-orders-container').removeClass('mam-loading');
            },
            error: function() {
                alert(mam_params.i18n.error);
                $('.mam-orders-container').removeClass('mam-loading');
            }
        });
    });
}
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

/**
 * My Account Manager - Dashboard Charts
 * Este archivo proporciona visualizaciones para el panel de usuario
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Charts = {
        /**
         * Inicialización
         */
        init: function() {
            // Inicializar gráficos solo si la librería está disponible
            if (typeof Chart !== 'undefined') {
                this.initOrdersChart();
                this.initSpendingChart();
            } else {
                console.log('Chart.js no está disponible. Los gráficos no se inicializarán.');
            }
            
            // Inicializar counters animados
            this.initAnimatedCounters();
            
            // Inicializar widgets de resumen
            this.initSummaryWidgets();
        },

        /**
         * Inicializar gráfico de pedidos
         */
        initOrdersChart: function() {
            var $ordersChart = $('#mam-orders-chart');
            
            if (!$ordersChart.length) {
                return;
            }
            
            // Obtener datos desde el elemento de datos
            var orderData = $ordersChart.data('orders') || {};
            
            // Crear gráfico de línea para pedidos por mes
            var ctx = $ordersChart[0].getContext('2d');
            
            // Definir colores
            var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--mam-primary-color').trim() || '#4a6cf7';
            var secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--mam-secondary-color').trim() || '#6b7280';
            
            // Si no hay datos, mostrar datos de ejemplo
            if (!orderData.labels || !orderData.values) {
                orderData = {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    values: [2, 4, 3, 5, 6, 4]
                };
            }
            
            // Crear gráfico
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: orderData.labels,
                    datasets: [{
                        label: 'Pedidos',
                        data: orderData.values,
                        backgroundColor: this.hexToRGBA(primaryColor, 0.1),
                        borderColor: primaryColor,
                        borderWidth: 2,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#fff',
                            titleColor: '#374151',
                            bodyColor: '#374151',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: 10,
                            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' pedidos';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Inicializar gráfico de gastos
         */
        initSpendingChart: function() {
            var $spendingChart = $('#mam-spending-chart');
            
            if (!$spendingChart.length) {
                return;
            }
            
            // Obtener datos desde el elemento de datos
            var spendingData = $spendingChart.data('spending') || {};
            
            // Crear gráfico de barras para gastos por categoría
            var ctx = $spendingChart[0].getContext('2d');
            
            // Definir colores
            var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--mam-primary-color').trim() || '#4a6cf7';
            
            // Si no hay datos, mostrar datos de ejemplo
            if (!spendingData.labels || !spendingData.values) {
                spendingData = {
                    labels: ['Ropa', 'Electrónica', 'Hogar', 'Deportes', 'Belleza'],
                    values: [125, 300, 180, 90, 150]
                };
            }
            
            // Generar colores para cada barra
            var colors = [];
            for (var i = 0; i < spendingData.values.length; i++) {
                var alpha = 0.7 + (i * 0.05);
                if (alpha > 1) alpha = 1;
                colors.push(this.hexToRGBA(primaryColor, alpha));
            }
            
            // Crear gráfico
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: spendingData.labels,
                    datasets: [{
                        label: 'Gasto',
                        data: spendingData.values,
                        backgroundColor: colors,
                        borderColor: 'transparent',
                        borderWidth: 0,
                        barThickness: 20,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#fff',
                            titleColor: '#374151',
                            bodyColor: '#374151',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: 10,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    // Formatear como moneda
                                    var value = context.parsed.y;
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('es-ES', { 
                                        style: 'currency', 
                                        currency: 'EUR' 
                                    }).format(value);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    // Formatear como moneda
                                    return new Intl.NumberFormat('es-ES', { 
                                        style: 'currency', 
                                        currency: 'EUR',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Inicializar contadores animados
         */
        initAnimatedCounters: function() {
            $('.mam-dashboard-card-number').each(function() {
                var $this = $(this);
                var value = parseInt($this.text(), 10);
                
                if (isNaN(value)) {
                    return;
                }
                
                // Iniciar contador desde cero
                $this.text('0');
                
                // Animar hasta el valor final
                $({ Counter: 0 }).animate({
                    Counter: value
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.ceil(this.Counter));
                    },
                    complete: function() {
                        $this.text(value);
                    }
                });
            });
        },

        /**
         * Inicializar widgets de resumen
         */
        initSummaryWidgets: function() {
            // Mostrar/ocultar detalles al hacer clic en tarjetas
            $('.mam-dashboard-card').on('click', function(e) {
                // No activar si se hace clic en enlaces o botones
                if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) {
                    return;
                }
                
                $(this).toggleClass('mam-card-expanded');
                
                // Alternar visibilidad de detalles adicionales
                var $details = $(this).find('.mam-card-details');
                if ($details.length) {
                    $details.slideToggle(300);
                }
            });
        },

        /**
         * Convertir color HEX a RGBA
         */
        hexToRGBA: function(hex, alpha) {
            // Eliminar # si existe
            hex = hex.replace('#', '');
            
            // Convertir hexadecimal a RGB
            var r = parseInt(hex.length == 3 ? hex.slice(0, 1).repeat(2) : hex.slice(0, 2), 16);
            var g = parseInt(hex.length == 3 ? hex.slice(1, 2).repeat(2) : hex.slice(2, 4), 16);
            var b = parseInt(hex.length == 3 ? hex.slice(2, 3).repeat(2) : hex.slice(4, 6), 16);
            
            // Devolver color en formato RGBA
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Charts.init();
    });

})(jQuery);

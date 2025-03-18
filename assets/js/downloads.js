/**
 * My Account Manager - Downloads Scripts
 */
(function($) {
    'use strict';

    // Objeto principal del plugin
    var MAM_Downloads = {
        /**
         * Inicialización
         */
        init: function() {
            this.initViewSwitcher();
            this.initFilePreviews();
            this.initDownloadTracking();
            this.initSortingOptions();
            this.initFilterSelector();
            this.initSearchDownloads();
            this.initExpandableRows();
        },

        /**
         * Inicializar interruptor de vista (lista/cuadrícula)
         */
        initViewSwitcher: function() {
            var self = this;
            var $viewButtons = $('.mam-view-button');
            var $downloadsList = $('.woocommerce-table--downloads, .mam-downloads-by-category');
            
            // Establecer vista actual basada en la cookie
            var currentView = self.getCookie('mam_downloads_view') || 'list';
            self.setViewMode(currentView);
            
            // Manejar clics en botones de vista
            $viewButtons.on('click', function() {
                var viewMode = $(this).data('view');
                
                // Actualizar botones
                $viewButtons.removeClass('active');
                $(this).addClass('active');
                
                // Establecer modo de vista
                self.setViewMode(viewMode);
                
                // Guardar preferencia en cookie
                self.setCookie('mam_downloads_view', viewMode, 365);
            });
        },

        /**
         * Establecer modo de visualización
         */
        setViewMode: function(mode) {
            var $downloadsTable = $('.woocommerce-table--downloads');
            var $downloadsList = $('.mam-downloads-by-category');
            
            if (mode === 'grid') {
                $downloadsTable.addClass('mam-grid-view').removeClass('mam-list-view');
                $downloadsList.addClass('mam-grid-view').removeClass('mam-list-view');
                
                // Convertir tabla a cuadrícula si es necesario
                if ($downloadsTable.length && !$downloadsTable.hasClass('mam-grid-converted')) {
                    this.convertTableToGrid();
                    $downloadsTable.addClass('mam-grid-converted');
                }
            } else {
                $downloadsTable.addClass('mam-list-view').removeClass('mam-grid-view');
                $downloadsList.addClass('mam-list-view').removeClass('mam-grid-view');
            }
        },

        /**
         * Convertir tabla a formato cuadrícula
         */
        convertTableToGrid: function() {
            var $table = $('.woocommerce-table--downloads');
            
            if (!$table.length) {
                return;
            }
            
            var $rows = $table.find('tbody tr');
            var $container = $('<div class="mam-downloads-grid"></div>');
            
            // Procesar cada fila
            $rows.each(function() {
                var $row = $(this);
                var productName = $row.find('td.download-product').text().trim();
                var fileName = $row.find('td.download-file').text().trim();
                var remainingDownloads = $row.find('td.download-remaining').html();
                var expiryDate = $row.find('td.download-expires').html();
                var downloadButton = $row.find('td.download-actions').html();
                
                // Crear tarjeta
                var $card = $('<div class="mam-download-card"></div>');
                
                // Añadir icono según tipo de archivo
                var fileExtension = MAM_Downloads.getFileExtension(fileName);
                var fileIcon = MAM_Downloads.getFileIcon(fileExtension);
                
                $card.append('<div class="mam-download-icon">' + fileIcon + '</div>');
                $card.append('<h3 class="mam-download-title">' + fileName + '</h3>');
                $card.append('<div class="mam-download-product">' + productName + '</div>');
                
                // Añadir información adicional
                var $info = $('<div class="mam-download-info"></div>');
                
                if (remainingDownloads) {
                    $info.append('<div class="mam-download-remaining">' + remainingDownloads + '</div>');
                }
                
                if (expiryDate) {
                    $info.append('<div class="mam-download-expires">' + expiryDate + '</div>');
                }
                
                $card.append($info);
                
                // Añadir botón de descarga
                if (downloadButton) {
                    $card.append('<div class="mam-download-actions">' + downloadButton + '</div>');
                }
                
                $container.append($card);
            });
            
            // Reemplazar tabla con cuadrícula
            $table.hide().after($container);
            
            // Guardar referencia a la tabla original
            $container.data('original-table', $table);
        },

        /**
         * Inicializar previsualización de archivos
         */
        initFilePreviews: function() {
            var self = this;
            
            // Abrir modal de previsualización al hacer clic en botón
            $(document).on('click', '.mam-preview-trigger', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                if (target.startsWith('#download-preview-')) {
                    var $preview = $(target);
                    
                    // Mostrar modal
                    $preview.fadeIn(300);
                    
                    // Añadir overlay si no existe
                    if ($('.mam-modal-overlay').length === 0) {
                        $('body').append('<div class="mam-modal-overlay"></div>');
                        $('.mam-modal-overlay').fadeIn(300);
                    }
                }
            });
            
            // Cerrar modal al hacer clic en botón de cierre o overlay
            $(document).on('click', '.mam-preview-close, .mam-modal-overlay', function() {
                $('.mam-file-preview').fadeOut(300);
                $('.mam-modal-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Cerrar modal al pulsar ESC
            $(document).keyup(function(e) {
                if (e.key === "Escape") {
                    $('.mam-file-preview').fadeOut(300);
                    $('.mam-modal-overlay').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        },

        /**
         * Inicializar seguimiento de descargas
         */
        initDownloadTracking: function() {
            // Seguimiento de clics en botones de descarga
            $('.mam-download-button, .download-button').on('click', function() {
                // Si existe Google Analytics (GA4)
                if (typeof gtag === 'function') {
                    var fileName = $(this).closest('tr, .mam-download-card, .mam-download-item').find('.download-file, .mam-download-title').text().trim();
                    
                    gtag('event', 'download', {
                        'file_name': fileName,
                        'content_type': MAM_Downloads.getFileExtension(fileName)
                    });
                }
            });
        },

        /**
         * Inicializar opciones de ordenación
         */
        initSortingOptions: function() {
            var self = this;
            
            // Si existe el selector de ordenación
            if ($('.mam-sort-selector').length) {
                $('.mam-sort-selector').on('change', function() {
                    var sortBy = $(this).val();
                    
                    // Agregar parámetro de ordenación a la URL y recargar
                    var url = new URL(window.location.href);
                    url.searchParams.set('sort', sortBy);
                    window.location.href = url.toString();
                });
            }
        },

        /**
         * Inicializar selector de filtro
         */
        initFilterSelector: function() {
            // Enviar formulario al cambiar categoría
            $('#download_category').on('change', function() {
                if ($(this).closest('form').find('.mam-filter-auto-submit').length) {
                    $(this).closest('form').submit();
                }
            });
        },

        /**
         * Inicializar búsqueda de descargas
         */
        initSearchDownloads: function() {
            var self = this;
            var $searchInput = $('.mam-downloads-search input');
            
            if (!$searchInput.length) {
                return;
            }
            
            // Realizar búsqueda en tiempo real
            $searchInput.on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                // Buscar en modo lista
                $('.woocommerce-table--downloads tbody tr').each(function() {
                    var productName = $(this).find('td.download-product').text().toLowerCase();
                    var fileName = $(this).find('td.download-file').text().toLowerCase();
                    
                    if (productName.indexOf(searchTerm) > -1 || fileName.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Buscar en modo cuadrícula
                $('.mam-download-card').each(function() {
                    var productName = $(this).find('.mam-download-product').text().toLowerCase();
                    var fileName = $(this).find('.mam-download-title').text().toLowerCase();
                    
                    if (productName.indexOf(searchTerm) > -1 || fileName.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Buscar en vista por categorías
                $('.mam-download-item').each(function() {
                    var productName = $(this).find('.mam-download-product').text().toLowerCase();
                    var fileName = $(this).find('.mam-download-title').text().toLowerCase();
                    
                    if (productName.indexOf(searchTerm) > -1 || fileName.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Mostrar/ocultar categorías vacías
                $('.mam-download-category').each(function() {
                    var $category = $(this);
                    var visibleItems = $category.find('.mam-download-item:visible').length;
                    
                    if (visibleItems === 0) {
                        $category.hide();
                    } else {
                        $category.show();
                    }
                });
            });
        },

        /**
         * Inicializar filas expandibles
         */
        initExpandableRows: function() {
            // Expandir/contraer filas al hacer clic
            $('.woocommerce-table--downloads tbody tr').on('click', function(e) {
                // No expandir si se hace clic en botones o enlaces
                if ($(e.target).is('a, button, .mam-button') || $(e.target).closest('a, button, .mam-button').length) {
                    return;
                }
                
                $(this).toggleClass('mam-expanded-row');
                
                // Mostrar detalles adicionales si existen
                if ($(this).next('.mam-download-details').length) {
                    $(this).next('.mam-download-details').slideToggle(300);
                } else {
                    // Crear fila de detalles
                    var $row = $(this);
                    var productName = $row.find('td.download-product').text().trim();
                    var fileName = $row.find('td.download-file').text().trim();
                    var fileExt = MAM_Downloads.getFileExtension(fileName);
                    
                    // Crear HTML para detalles adicionales
                    var detailsHTML = '<tr class="mam-download-details" style="display: none;">';
                    detailsHTML += '<td colspan="' + $row.find('td').length + '">';
                    detailsHTML += '<div class="mam-download-details-content">';
                    
                    // Añadir previsualización si es posible
                    if (MAM_Downloads.isPreviewableFile(fileExt)) {
                        detailsHTML += '<div class="mam-download-preview">';
                        detailsHTML += '<h4>Vista previa</h4>';
                        detailsHTML += '<div class="mam-preview-placeholder">';
                        detailsHTML += '<p>Vista previa no disponible. Descarga el archivo para verlo.</p>';
                        detailsHTML += '</div>';
                        detailsHTML += '</div>';
                    }
                    
                    // Añadir información del archivo
                    detailsHTML += '<div class="mam-download-file-info">';
                    detailsHTML += '<h4>Información del archivo</h4>';
                    detailsHTML += '<ul>';
                    detailsHTML += '<li><strong>Tipo de archivo:</strong> ' + fileExt.toUpperCase() + '</li>';
                    detailsHTML += '<li><strong>Producto:</strong> ' + productName + '</li>';
                    detailsHTML += '</ul>';
                    detailsHTML += '</div>';
                    
                    detailsHTML += '</div>';
                    detailsHTML += '</td>';
                    detailsHTML += '</tr>';
                    
                    // Insertar después de la fila actual
                    $row.after(detailsHTML);
                    $row.next('.mam-download-details').slideDown(300);
                }
            });
        },

        /**
         * Obtener extensión de archivo
         */
        getFileExtension: function(fileName) {
            return fileName.split('.').pop().toLowerCase();
        },

        /**
         * Verificar si el archivo es previsualizable
         */
        isPreviewableFile: function(extension) {
            var previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            return previewableExtensions.indexOf(extension) !== -1;
        },

        /**
         * Obtener icono de archivo
         */
        getFileIcon: function(extension) {
            var iconSVG = '';
            
            switch (extension) {
                case 'pdf':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M9 15h6"></path><path d="M9 11h6"></path><path d="M9 19h6"></path></svg>';
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                    break;
                case 'zip':
                case 'rar':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>';
                    break;
                case 'doc':
                case 'docx':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
                    break;
                case 'xls':
                case 'xlsx':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><polyline points="8 16 12 12 16 16"></polyline><polyline points="16 12 12 8 8 12"></polyline></svg>';
                    break;
                case 'mp3':
                case 'wav':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>';
                    break;
                case 'mp4':
                case 'avi':
                case 'mov':
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>';
                    break;
                default:
                    iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>';
            }
            
            return iconSVG;
        },

        /**
         * Obtener valor de cookie
         */
        getCookie: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        /**
         * Establecer cookie
         */
        setCookie: function(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + value + expires + "; path=/";
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        MAM_Downloads.init();
    });

})(jQuery);

/**
 * Frontend scripts for Show Product Tab as Category plugin.
 */
jQuery(document).ready(function($) {
    // Store the current category and page
    let currentCategory = 'all';
    let currentPage = 1;

    // Debug: Log when script is loaded
    console.log('SPTAC: Script loaded', sptac_ajax);

    // Debounce function to limit search input events
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Handle tab click
    $('.sptac-tab').on('click', function(e) {
        e.preventDefault();
        console.log('SPTAC: Tab clicked', $(this).data('category'));
        // Remove active class from all tabs
        $('.sptac-tab').removeClass('active');
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Update current category and reset page
        currentCategory = $(this).data('category');
        currentPage = 1;
        
        // Trigger product load with current search term
        loadProducts(currentCategory, $('.sptac-search-input').val(), currentPage);
    });

    // Handle search input with debounce
    $('.sptac-search-input').on('input', debounce(function() {
        console.log('SPTAC: Search input', $(this).val());
        // Reset page to 1 on search
        currentPage = 1;
        // Trigger product load with current category and search term
        loadProducts(currentCategory, $(this).val(), currentPage);
    }, 300));

    // Handle pagination click
    $(document).on('click', '.sptac-page', function(e) {
        e.preventDefault();
        console.log('SPTAC: Page clicked', $(this).data('page'));
        // Update current page
        currentPage = $(this).data('page');
        // Remove active class from all pages
        $('.sptac-page').removeClass('active');
        // Add active class to clicked page
        $(this).addClass('active');
        // Trigger product load
        loadProducts(currentCategory, $('.sptac-search-input').val(), currentPage);
    });

    // Function to load products
    function loadProducts(category, search, page) {
        console.log('SPTAC: Loading products', { category, search, page });
        // Show loading
        $('.sptac-products').html('<p>' + sptac_ajax.i18n.loading + '</p>');
        
        // Get products per page from data attribute
        var productsPerPage = $('.sptac-container').data('products-per-page') || 12;

        // AJAX request
        $.ajax({
            url: sptac_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sptac_load_products',
                nonce: sptac_ajax.nonce,
                category: category,
                search: search,
                page: page,
                products_per_page: productsPerPage
            },
            success: function(response) {
                console.log('SPTAC: AJAX success', response);
                if (response.success) {
                    $('.sptac-products').html(response.data.data);
                } else {
                    console.log('SPTAC: AJAX error response', response.data);
                    $('.sptac-products').html('<p>' + sptac_ajax.i18n.error_loading + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('SPTAC: AJAX error', { status, error });
                $('.sptac-products').html('<p>' + sptac_ajax.i18n.error_loading + '</p>');
            }
        });
    }
});
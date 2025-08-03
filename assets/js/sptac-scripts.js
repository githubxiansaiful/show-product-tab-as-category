/**
 * Frontend scripts for Show Product Tab as Category plugin.
 */
jQuery(document).ready(function($) {
    // Store the current category and page
    let currentCategory = 'all';
    let currentPage = 1;

    // Handle tab click
    $('.sptac-tab').on('click', function(e) {
        e.preventDefault();
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

    // Handle search input
    $('.sptac-search-input').on('input', function() {
        // Reset page to 1 on search
        currentPage = 1;
        // Trigger product load with current category and search term
        loadProducts(currentCategory, $(this).val(), currentPage);
    });

    // Handle pagination click
    $(document).on('click', '.sptac-page', function(e) {
        e.preventDefault();
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
        // Show loading
        $('.sptac-products').html('<p>' + sptac_i18n.loading + '</p>');
        
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
                if (response.success) {
                    $('.sptac-products').html(response.data);
                } else {
                    $('.sptac-products').html('<p>' + sptac_i18n.error_loading + '</p>');
                }
            },
            error: function() {
                $('.sptac-products').html('<p>' + sptac_i18n.error_loading + '</p>');
            }
        });
    }
});
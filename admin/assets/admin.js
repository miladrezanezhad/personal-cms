/*
// ==============================================
// FILE: admin/assets/admin.js
// ==============================================
*/

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-danger').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Toggle sidebar on mobile (if implemented)
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('show');
    });
    
    // Table row click handler for edit
    $('.table tbody tr').on('click', function(e) {
        if ($(e.target).is('a') || $(e.target).is('button')) {
            return;
        }
        var editLink = $(this).find('a.btn-primary');
        if (editLink.length) {
            window.location.href = editLink.attr('href');
        }
    });
    
    // Sync button loading state
    $('.sync-btn').on('click', function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Syncing...').prop('disabled', true);
    });
    
    // Auto-save draft (optional feature)
    var autoSaveTimer;
    $('#content').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Implement auto-save via AJAX if needed
            console.log('Auto-save triggered');
        }, 30000);
    });
    
    // Slug auto-generation from title
    $('#title').on('input', function() {
        var slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        if ($('#slug').val() === '') {
            $('#slug').val(slug);
        }
    });
});
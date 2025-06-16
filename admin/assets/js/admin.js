/**
 * IQ Admin - Main JavaScript File
 * Handles common functionality across all admin pages
 */

// Document Ready
$(document).ready(function() {
    // Initialize DataTables if they exist on the page
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "No entries found",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    }

    // Initialize select2 if it exists
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Initialize Summernote if it exists
    if ($.fn.summernote) {
        $('.summernote').summernote({
            height: 300,
            minHeight: null,
            maxHeight: null,
            focus: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    // Confirm before deleting
    $('.confirm-delete').on('click', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4361ee',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $($(this).attr('toggle'));
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // File input preview
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Initialize datepicker
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }

    // Initialize timepicker
    if ($.fn.timepicker) {
        $('.timepicker').timepicker({
            showMeridian: false,
            minuteStep: 5
        });
    }

    // Handle form submissions with loading state
    $('form.ajax-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');
        
        // Submit form via AJAX
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: response.message || 'Operation completed successfully.',
                        icon: 'success',
                        confirmButtonColor: '#4361ee',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else if (response.reload) {
                            window.location.reload();
                        }
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMessage = xhr.statusText;
                }
                
                Swal.fire({
                    title: 'Error!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Handle logout confirmation
    $('a[href*="logout"]').on('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
});

// Global functions
function showAlert(type, message, title = '') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${title ? `<strong>${title}</strong> ` : ''}${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Prepend to alerts container if it exists, otherwise to the page
    const $alertsContainer = $('.alerts-container');
    if ($alertsContainer.length) {
        $alertsContainer.prepend(alertHtml);
    } else {
        $('main.container-fluid').prepend(alertHtml);
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').fadeTo(500, 0).slideUp(500, function(){
            $(this).remove(); 
        });
    }, 5000);
}

// Initialize tooltips on dynamically added elements
$(document).on('mouseover', '[data-bs-toggle="tooltip"]', function() {
    new bootstrap.Tooltip(this);
});

// Initialize popovers on dynamically added elements
$(document).on('mouseover', '[data-bs-toggle="popover"]', function() {
    new bootstrap.Popover(this);
});

// Handle file upload preview
function readURL(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            $(`#${previewId}`).attr('src', e.target.result);
            $(`#${previewId}`).parent().show();
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Toggle all checkboxes
function toggleCheckboxes(checkbox, className) {
    $('.' + className).prop('checked', $(checkbox).prop('checked'));
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Debounce function for window resize/scroll events
function debounce(func, wait, immediate) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}



// Loading overlay handling
let loadingTimeout = null;

function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="typewriter">
            <div class="slide"><i></i></div>
            <div class="paper"></div>
            <div class="keyboard"></div>
        </div>
    `;
    document.body.appendChild(overlay);
    
    // Clear any existing timeout
    if (loadingTimeout) {
        clearTimeout(loadingTimeout);
    }
    
    // Set timeout to fade out after 4 seconds
    loadingTimeout = setTimeout(() => {
        fadeOutLoadingOverlay();
    }, 4000);
}

function fadeOutLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease-in-out';
        
        // Remove after transition completes
        setTimeout(() => {
            overlay.remove();
        }, 5000); // Match transition duration
    }
}

function hideLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Show loading overlay when page loads
document.addEventListener('DOMContentLoaded', function() {
    showLoadingOverlay();
    
    // Hide loader immediately when page is loaded
    hideLoadingOverlay();
});

// Also hide loader when page is fully loaded
window.addEventListener('load', function() {
    hideLoadingOverlay();
});

// Add loading overlay for AJAX requests
$(document).ajaxStart(function() {
    showLoadingOverlay();
}).ajaxStop(function() {
    // Clear timeout since AJAX is complete
    if (loadingTimeout) {
        clearTimeout(loadingTimeout);
    }
    hideLoadingOverlay();
});

// Add loading overlay for form submissions
$(document).on('submit', 'form', function() {
    showLoadingOverlay();
    // Clear timeout since form submission is happening
    if (loadingTimeout) {
        clearTimeout(loadingTimeout);
    }
});
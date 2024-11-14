jQuery(document).ready(function($) {
    // Loading States
    function setLoading(element, isLoading) {
        if (isLoading) {
            element.addClass('is-loading')
                  .prop('disabled', true)
                  .find('.spinner')
                  .addClass('is-active');
        } else {
            element.removeClass('is-loading')
                  .prop('disabled', false)
                  .find('.spinner')
                  .removeClass('is-active');
        }
    }

    // Error Handling
    function showError(message, type = 'error') {
        const notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.xautoposter-wrap > h1').after(notice);
        notice.hide().slideDown();
        
        setTimeout(() => {
            notice.slideUp(() => notice.remove());
        }, 5000);
    }

    // Posts Table
    var $postsTable = $('.posts-table');
    if ($postsTable.length) {
        // Select All
        $('#select-all-posts').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('input[name="posts[]"]:not(:disabled)').prop('checked', isChecked);
            updateShareButtonState();
        });

        // Individual Selections
        $postsTable.on('change', 'input[name="posts[]"]', function() {
            updateShareButtonState();
        });

        // Share Button State
        function updateShareButtonState() {
            var $shareButton = $('#share-selected');
            var checkedCount = $('input[name="posts[]"]:checked').length;
            
            if (checkedCount > 0) {
                $shareButton.prop('disabled', false);
                $shareButton.find('.count').text(` (${checkedCount})`);
            } else {
                $shareButton.prop('disabled', true);
                $shareButton.find('.count').text('');
            }
        }

        // Share Posts
        $('#share-selected').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const posts = $('input[name="posts[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (posts.length === 0) {
                showError(xautoposter.strings.no_posts_selected);
                return;
            }

            if ($button.prop('disabled') || $button.hasClass('is-loading')) {
                return;
            }

            setLoading($button, true);

            $.ajax({
                url: xautoposter.ajax_url,
                type: 'POST',
                data: {
                    action: 'xautoposter_share_posts',
                    posts: posts,
                    nonce: xautoposter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showError(response.data.message, 'success');
                        
                        // Paylaşılan gönderileri güncelle
                        posts.forEach(postId => {
                            const $checkbox = $(`input[name="posts[]"][value="${postId}"]`);
                            const $row = $checkbox.closest('tr');
                            $checkbox.prop('disabled', true).prop('checked', false);
                            $row.find('.status-not-shared')
                                .removeClass('status-not-shared')
                                .addClass('status-shared')
                                .text('Paylaşıldı (şimdi)');
                        });
                        
                        updateShareButtonState();
                    } else {
                        showError(response.data.message || xautoposter.strings.error);
                    }
                },
                error: function() {
                    showError(xautoposter.strings.error);
                },
                complete: function() {
                    setLoading($button, false);
                }
            });
        });

        // Initialize button state
        updateShareButtonState();
    }

    // API Settings
    $('#unlock-api-settings').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(xautoposter.strings.confirm_unlock)) {
            return;
        }
        
        const $button = $(this);
        setLoading($button, true);

        $.ajax({
            url: xautoposter.ajax_url,
            type: 'POST',
            data: {
                action: 'xautoposter_reset_api_verification',
                nonce: xautoposter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('input[name^="xautoposter_options"]').prop('readonly', false);
                    $('.api-status-bar').slideUp();
                    showError(response.data.message, 'success');
                    $('input[type="submit"]').prop('disabled', false);
                } else {
                    showError(response.data.message || xautoposter.strings.error);
                }
            },
            error: function() {
                showError(xautoposter.strings.error);
            },
            complete: function() {
                setLoading($button, false);
            }
        });
    });

    // Filters
    $('#category-filter, #orderby, #order-sort').on('change', function() {
        $(this).closest('form').submit();
    });
});
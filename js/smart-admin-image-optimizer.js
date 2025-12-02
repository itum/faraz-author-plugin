jQuery(document).ready(function($) {
    // Debug: بررسی وجود متغیرها
    console.log('Smart Admin Image Optimizer loaded');
    console.log('smartAdminImage:', typeof smartAdminImage !== 'undefined' ? smartAdminImage : 'undefined');
    
    const container = $('#smart-admin-image-search-container');
    const searchButton = $('#smart-image-search-button');
    const autoSuggestButton = $('#auto-suggest-images-button');
    const searchInput = $('#image-search-keyword');
    const resultsContainer = $('#image-search-results');
    const loadingContainer = $('#image-search-loading');
    const toolbarButton = $('#smart-admin-image-search-button');
    
    // Debug: بررسی وجود المان‌ها
    console.log('Container found:', container.length > 0);
    console.log('Search button found:', searchButton.length > 0);
    console.log('Toolbar button found:', toolbarButton.length > 0);

    // جستجوی تصاویر
    searchButton.on('click', function() {
        console.log('Search button clicked');
        
        if (typeof smartAdminImage === 'undefined') {
            alert('خطا: متغیرهای JavaScript بارگذاری نشده‌اند.');
            return;
        }
        
        const keyword = searchInput.val();
        if (!keyword) {
            alert('لطفاً یک کلمه کلیدی وارد کنید.');
            return;
        }

        showLoading();
        resultsContainer.html('');

        $.ajax({
            url: smartAdminImage.ajax_url,
            type: 'POST',
            data: {
                action: 'smart_admin_search_images',
                nonce: smartAdminImage.nonce,
                keyword: keyword
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    console.log('Displaying images:', response.data);
                    console.log('Results container found:', resultsContainer.length > 0);
                    displayImages(response.data, keyword);
                } else {
                    console.log('Error in response:', response.data);
                    resultsContainer.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                var errorMessage = 'خطای ناشناخته در هنگام جستجو رخ داد.';
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (xhr.statusText) {
                    errorMessage = 'خطای HTTP: ' + xhr.status + ' - ' + xhr.statusText;
                }
                
                resultsContainer.html('<div class="notice notice-error"><p>❌ ' + errorMessage + '</p></div>');
                
                console.error('Smart Admin Image Search Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
            },
            complete: function() {
                hideLoading();
            }
        });
    });

    // پیشنهاد خودکار تصاویر
    autoSuggestButton.on('click', function() {
        const postId = getPostId();
        if (!postId) {
            alert('شناسه پست یافت نشد.');
            return;
        }

        showLoading();
        resultsContainer.html('');

        $.ajax({
            url: smartAdminImage.ajax_url,
            type: 'POST',
            data: {
                action: 'smart_admin_auto_suggest_images',
                nonce: smartAdminImage.nonce,
                post_id: postId
            },
            success: function(response) {
                console.log('Auto suggest response:', response);
                if (response.success) {
                    displayAutoSuggestedImages(response.data);
                } else {
                    resultsContainer.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Auto suggest error:', {xhr, status, error});
                resultsContainer.html('<div class="notice notice-error"><p>❌ خطا در پیشنهاد خودکار تصاویر</p></div>');
            },
            complete: function() {
                hideLoading();
            }
        });
    });

    // نمایش تصاویر
    function displayImages(images, keyword) {
        if (!images || images.length === 0) {
            resultsContainer.html('<div class="notice notice-warning"><p>⚠️ هیچ تصویری برای "' + keyword + '" یافت نشد.</p></div>');
            return;
        }

        let html = '<div class="image-grid">';
        images.forEach(function(image) {
            html += createImageCard(image);
        });
        html += '</div>';

        resultsContainer.html(html);
    }

    // نمایش تصاویر پیشنهادی
    function displayAutoSuggestedImages(data) {
        if (!data.images || data.images.length === 0) {
            resultsContainer.html('<div class="notice notice-warning"><p>⚠️ هیچ تصویر پیشنهادی یافت نشد.</p></div>');
            return;
        }

        let html = '<div class="auto-suggest-results">';
        html += '<h4>تصاویر پیشنهادی بر اساس محتوای پست:</h4>';
        html += '<div class="image-grid">';
        data.images.forEach(function(image) {
            html += createImageCard(image);
        });
        html += '</div>';
        html += '</div>';

        resultsContainer.html(html);
    }

    // ایجاد کارت تصویر با مدیریت خطا بهتر
    function createImageCard(image) {
        console.log('Creating image card for:', image);
        
        // بررسی و تنظیم مقادیر پیش‌فرض
        const imageData = {
            id: image.id || 'unknown-' + Math.random(),
            url: image.url || '',
            thumb_url: image.thumb_url || image.url || '',
            alt: image.alt || 'تصویر مرتبط',
            description: image.description || '',
            user: image.user || { name: 'نامشخص', link: '#' }
        };
        
        // بررسی وجود URL تصویر
        if (!imageData.url) {
            console.error('Missing URL for image:', image);
            return '<div class="image-card error"><p>خطا: URL تصویر موجود نیست</p></div>';
        }
        
        let html = '<div class="image-card" data-image-id="' + imageData.id + '">';
        html += '<div class="image-preview">';
        html += '<img src="' + imageData.thumb_url + '" alt="' + imageData.alt + '" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';" />';
        html += '<div class="image-error" style="display: none; padding: 20px; text-align: center; color: #666;">تصویر بارگذاری نشد</div>';
        html += '</div>';
        html += '<div class="image-info">';
        html += '<p class="image-alt">' + imageData.alt + '</p>';
        if (imageData.description) {
            html += '<p class="image-description">' + imageData.description.substring(0, 100) + '...</p>';
        }
        html += '<p class="image-author">عکاس: <a href="' + imageData.user.link + '" target="_blank">' + imageData.user.name + '</a></p>';
        html += '</div>';
        html += '<div class="image-actions">';
        html += '<button type="button" class="button button-small insert-as-featured" data-image-url="' + imageData.url + '" data-alt-text="' + imageData.alt + '">';
        html += '<span class="dashicons dashicons-star-filled"></span> تصویر شاخص';
        html += '</button>';
        html += '<button type="button" class="button button-small insert-into-content" data-image-url="' + imageData.url + '" data-alt-text="' + imageData.alt + '">';
        html += '<span class="dashicons dashicons-format-image"></span> درج در محتوا';
        html += '</button>';
        html += '</div>';
        html += '</div>';

        console.log('Created image card HTML length:', html.length);
        return html;
    }

    // درج تصویر به عنوان تصویر شاخص
    $(document).on('click', '.insert-as-featured', function() {
        const imageUrl = $(this).data('image-url');
        const altText = $(this).data('alt-text');
        
        if (!imageUrl) {
            alert('خطا: URL تصویر موجود نیست.');
            return;
        }
        
        insertImage(imageUrl, altText, 'featured');
    });

    // درج تصویر در محتوا
    $(document).on('click', '.insert-into-content', function() {
        const imageUrl = $(this).data('image-url');
        const altText = $(this).data('alt-text');
        
        if (!imageUrl) {
            alert('خطا: URL تصویر موجود نیست.');
            return;
        }
        
        insertImage(imageUrl, altText, 'content');
    });

    // درج تصویر با مدیریت خطا بهتر
    function insertImage(imageUrl, altText, insertType) {
        const postId = getPostId();
        if (!postId) {
            alert('شناسه پست یافت نشد.');
            return;
        }

        if (!imageUrl) {
            alert('خطا: URL تصویر موجود نیست.');
            return;
        }

        showLoading();

        $.ajax({
            url: smartAdminImage.ajax_url,
            type: 'POST',
            data: {
                action: 'smart_admin_insert_image',
                nonce: smartAdminImage.nonce,
                post_id: postId,
                image_url: imageUrl,
                alt_text: altText,
                insert_type: insertType
            },
            success: function(response) {
                console.log('Insert image response:', response);
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    
                    if (insertType === 'featured') {
                        // به‌روزرسانی پیش‌نمایش تصویر شاخص
                        updateFeaturedImagePreview(response.data.image_url);
                        // به‌روزرسانی صفحه
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        // درج در ویرایشگر
                        insertImageIntoEditor(imageUrl, altText);
                    }
                } else {
                    showErrorMessage(response.data || 'خطا در درج تصویر');
                }
            },
            error: function(xhr, status, error) {
                console.error('Insert image error:', {xhr, status, error});
                showErrorMessage('خطا در درج تصویر: ' + error);
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    // درج تصویر در ویرایشگر
    function insertImageIntoEditor(imageUrl, altText) {
        const editor = wp.data.select('core/editor');
        if (editor) {
            // برای Gutenberg
            const currentContent = editor.getCurrentPost().content;
            const imageHtml = '<img src="' + imageUrl + '" alt="' + altText + '" style="max-width: 100%; height: auto;" />';
            wp.data.dispatch('core/editor').editPost({ content: currentContent + '\n\n' + imageHtml });
        } else {
            // برای ویرایشگر کلاسیک
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<img src="' + imageUrl + '" alt="' + altText + '" style="max-width: 100%; height: auto;" />');
            } else {
                // برای textarea ساده
                const textarea = $('#content');
                if (textarea.length > 0) {
                    const currentContent = textarea.val();
                    const imageHtml = '\n\n<img src="' + imageUrl + '" alt="' + altText + '" style="max-width: 100%; height: auto;" />\n\n';
                    textarea.val(currentContent + imageHtml);
                }
            }
        }
    }

    // به‌روزرسانی پیش‌نمایش تصویر شاخص
    function updateFeaturedImagePreview(imageUrl) {
        const featuredImageContainer = $('#postimagediv');
        if (featuredImageContainer.length > 0) {
            // به‌روزرسانی پیش‌نمایش
            const preview = featuredImageContainer.find('.inside img');
            if (preview.length > 0) {
                preview.attr('src', imageUrl);
            } else {
                // ایجاد پیش‌نمایش جدید
                const newPreview = '<img src="' + imageUrl + '" style="max-width: 100%; height: auto;" />';
                featuredImageContainer.find('.inside').append(newPreview);
            }
        }
    }

    // نمایش پیام موفقیت
    function showSuccessMessage(message) {
        const notice = '<div class="notice notice-success is-dismissible"><p>✅ ' + message + '</p></div>';
        $('.wrap h1').after(notice);
        
        // حذف خودکار پیام بعد از 5 ثانیه
        setTimeout(function() {
            $('.notice-success').fadeOut();
        }, 5000);
    }

    // نمایش پیام خطا
    function showErrorMessage(message) {
        const notice = '<div class="notice notice-error is-dismissible"><p>❌ ' + message + '</p></div>';
        $('.wrap h1').after(notice);
        
        // حذف خودکار پیام بعد از 10 ثانیه
        setTimeout(function() {
            $('.notice-error').fadeOut();
        }, 10000);
    }

    // نمایش لودینگ
    function showLoading() {
        if (loadingContainer.length > 0) {
            loadingContainer.show();
        } else {
            resultsContainer.html('<div class="loading">در حال بارگذاری...</div>');
        }
    }

    // مخفی کردن لودینگ
    function hideLoading() {
        if (loadingContainer.length > 0) {
            loadingContainer.hide();
        }
    }

    // دریافت شناسه پست
    function getPostId() {
        // بررسی در URL
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post');
        if (postId) {
            return postId;
        }
        
        // بررسی در input hidden
        const postIdInput = $('#post_ID');
        if (postIdInput.length > 0) {
            return postIdInput.val();
        }
        
        // بررسی در متغیرهای جاوااسکریپت
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            return wp.data.select('core/editor').getCurrentPost().id;
        }
        
        return null;
    }

    // باز کردن مودال جستجوی تصویر
    function openImageSearchModal() {
        const modal = $('<div id="image-search-modal" class="image-search-modal">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h3>جستجوی تصویر هوشمند</h3>' +
            '<span class="close">&times;</span>' +
            '</div>' +
            '<div class="modal-body">' +
            '<input type="text" id="modal-search-input" placeholder="کلمه کلیدی را وارد کنید..." />' +
            '<button type="button" id="modal-search-button" class="button">جستجو</button>' +
            '<div id="modal-results"></div>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        modal.show();
        
        // بستن مودال
        modal.find('.close').on('click', function() {
            modal.remove();
        });
        
        // جستجو در مودال
        $('#modal-search-button').on('click', function() {
            const keyword = $('#modal-search-input').val();
            if (keyword) {
                searchImagesFromModal(keyword);
            }
        });
    }

    // جستجوی تصاویر در مودال
    function searchImagesFromModal(keyword) {
        const modalResults = $('#modal-results');
        modalResults.html('<div class="loading">در حال جستجو...</div>');
        
        $.ajax({
            url: smartAdminImage.ajax_url,
            type: 'POST',
            data: {
                action: 'smart_admin_search_images',
                nonce: smartAdminImage.nonce,
                keyword: keyword
            },
            success: function(response) {
                if (response.success) {
                    displayImagesInModal(response.data, keyword);
                } else {
                    modalResults.html('<div class="error">خطا: ' + response.data + '</div>');
                }
            },
            error: function() {
                modalResults.html('<div class="error">خطا در جستجو</div>');
            }
        });
    }

    // نمایش تصاویر در مودال
    function displayImagesInModal(images, keyword) {
        const modalResults = $('#modal-results');
        
        if (!images || images.length === 0) {
            modalResults.html('<div class="no-results">هیچ تصویری یافت نشد.</div>');
            return;
        }
        
        let html = '<div class="image-grid">';
        images.forEach(function(image) {
            html += createImageCard(image);
        });
        html += '</div>';
        
        modalResults.html(html);
    }

    // باز کردن مودال با کلیک روی دکمه نوار ابزار
    toolbarButton.on('click', function() {
        openImageSearchModal();
    });
}); 
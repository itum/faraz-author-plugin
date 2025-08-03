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
                if (response.success) {
                    displayAutoSuggestedImages(response.data);
                } else {
                    resultsContainer.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'خطای ناشناخته در هنگام پیشنهاد خودکار رخ داد.';
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                resultsContainer.html('<div class="notice notice-error"><p>❌ ' + errorMessage + '</p></div>');
            },
            complete: function() {
                hideLoading();
            }
        });
    });

    // دکمه جستجوی تصویر در نوار ابزار
    toolbarButton.on('click', function() {
        console.log('Toolbar button clicked');
        openImageSearchModal();
    });

    // اضافه کردن event listener برای دکمه نوار ابزار (در صورت عدم وجود)
    $(document).on('click', '#smart-admin-image-search-button', function() {
        console.log('Toolbar button clicked via document listener');
        openImageSearchModal();
    });

    // نمایش تصاویر
    function displayImages(images, keyword) {
        console.log('displayImages called with:', {images, keyword});
        console.log('resultsContainer:', resultsContainer);
        
        if (images.length === 0) {
            console.log('No images found');
            resultsContainer.html('<div class="notice notice-warning"><p>🔍 هیچ تصویری برای "' + keyword + '" یافت نشد.</p></div>');
            return;
        }

        console.log('Creating HTML for', images.length, 'images');
        
        let html = '<div class="image-search-header">';
        html += '<h4>نتایج جستجو برای "' + keyword + '" (' + images.length + ' تصویر)</h4>';
        html += '</div>';
        html += '<div class="image-grid">';

        images.forEach(function(image, index) {
            console.log('Creating card for image', index, ':', image);
            html += createImageCard(image);
        });

        html += '</div>';
        
        console.log('Final HTML length:', html.length);
        console.log('Setting HTML to resultsContainer');
        
        resultsContainer.html(html);
        
        console.log('HTML set successfully');
    }

    // نمایش تصاویر پیشنهادی خودکار
    function displayAutoSuggestedImages(data) {
        const { images, keyword, suggested_keywords } = data;

        let html = '<div class="image-search-header">';
        html += '<h4>پیشنهاد خودکار بر اساس کلمه کلیدی: "' + keyword + '"</h4>';
        
        if (suggested_keywords && suggested_keywords.length > 0) {
            html += '<div class="suggested-keywords">';
            html += '<p>کلمات کلیدی پیشنهادی دیگر:</p>';
            html += '<div class="keyword-tags">';
            suggested_keywords.forEach(function(keyword) {
                html += '<span class="keyword-tag" data-keyword="' + keyword + '">' + keyword + '</span>';
            });
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '<div class="image-grid">';

        images.forEach(function(image) {
            html += createImageCard(image);
        });

        html += '</div>';
        resultsContainer.html(html);

        // اضافه کردن event listener برای کلمات کلیدی پیشنهادی
        $('.keyword-tag').on('click', function() {
            const keyword = $(this).data('keyword');
            searchInput.val(keyword);
            searchButton.click();
        });
    }

    // ایجاد کارت تصویر
    function createImageCard(image) {
        console.log('Creating image card for:', image);
        
        // بررسی وجود فیلدهای مورد نیاز
        if (!image.thumb_url) {
            console.error('Missing thumb_url for image:', image);
            image.thumb_url = image.url || '';
        }
        
        if (!image.alt) {
            console.error('Missing alt for image:', image);
            image.alt = 'تصویر مرتبط';
        }
        
        if (!image.user) {
            console.error('Missing user info for image:', image);
            image.user = { name: 'نامشخص', link: '#' };
        }
        
        let html = '<div class="image-card" data-image-id="' + (image.id || 'unknown') + '">';
        html += '<div class="image-preview">';
        html += '<img src="' + image.thumb_url + '" alt="' + image.alt + '" loading="lazy">';
        html += '</div>';
        html += '<div class="image-info">';
        html += '<p class="image-alt">' + image.alt + '</p>';
        if (image.description) {
            html += '<p class="image-description">' + image.description.substring(0, 100) + '...</p>';
        }
        html += '<p class="image-author">عکاس: <a href="' + image.user.link + '" target="_blank">' + image.user.name + '</a></p>';
        html += '</div>';
        html += '<div class="image-actions">';
        html += '<button type="button" class="button button-small insert-as-featured" data-image-url="' + image.url + '" data-alt-text="' + image.alt + '">';
        html += '<span class="dashicons dashicons-star-filled"></span> تصویر شاخص';
        html += '</button>';
        html += '<button type="button" class="button button-small insert-into-content" data-image-url="' + image.url + '" data-alt-text="' + image.alt + '">';
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
        
        insertImage(imageUrl, altText, 'featured');
    });

    // درج تصویر در محتوا
    $(document).on('click', '.insert-into-content', function() {
        const imageUrl = $(this).data('image-url');
        const altText = $(this).data('alt-text');
        
        insertImage(imageUrl, altText, 'content');
    });

    // درج تصویر
    function insertImage(imageUrl, altText, insertType) {
        const postId = getPostId();
        if (!postId) {
            alert('شناسه پست یافت نشد.');
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
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    
                    if (insertType === 'featured') {
                        // به‌روزرسانی پیش‌نمایش تصویر شاخص
                        updateFeaturedImagePreview(response.data.image_url);
                    } else {
                        // درج در ویرایشگر
                        insertImageIntoEditor(imageUrl, altText);
                    }
                } else {
                    showErrorMessage(response.data);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'خطا در درج تصویر.';
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showErrorMessage(errorMessage);
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    // درج تصویر در ویرایشگر
    function insertImageIntoEditor(imageUrl, altText) {
        // بررسی نوع ویرایشگر
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            // گوتنبرگ
            const imageBlock = wp.blocks.createBlock('core/image', {
                url: imageUrl,
                alt: altText
            });
            
            wp.data.dispatch('core/block-editor').insertBlock(imageBlock);
        } else {
            // ویرایشگر کلاسیک
            const imageHtml = '<img src="' + imageUrl + '" alt="' + altText + '" class="aligncenter size-full wp-image-100" />';
            
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                tinyMCE.activeEditor.execCommand('mceInsertContent', false, imageHtml);
            } else if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.insert('content', imageHtml);
            }
        }
    }

    // به‌روزرسانی پیش‌نمایش تصویر شاخص
    function updateFeaturedImagePreview(imageUrl) {
        const featuredImageContainer = $('#postimagediv .inside');
        if (featuredImageContainer.length > 0) {
            featuredImageContainer.html('<img src="' + imageUrl + '" alt="تصویر شاخص" style="max-width: 100%; height: auto;" />');
        }
    }

    // نمایش پیام موفقیت
    function showSuccessMessage(message) {
        const notice = $('<div class="notice notice-success is-dismissible"><p>✅ ' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // حذف خودکار پس از 5 ثانیه
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }

    // نمایش پیام خطا
    function showErrorMessage(message) {
        const notice = $('<div class="notice notice-error is-dismissible"><p>❌ ' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // حذف خودکار پس از 10 ثانیه
        setTimeout(function() {
            notice.fadeOut();
        }, 10000);
    }

    // نمایش loading
    function showLoading() {
        loadingContainer.show();
        searchButton.prop('disabled', true);
        autoSuggestButton.prop('disabled', true);
    }

    // مخفی کردن loading
    function hideLoading() {
        loadingContainer.hide();
        searchButton.prop('disabled', false);
        autoSuggestButton.prop('disabled', false);
    }

    // دریافت شناسه پست
    function getPostId() {
        // تلاش برای دریافت از URL
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post');
        
        if (postId) {
            return postId;
        }
        
        // تلاش برای دریافت از input hidden
        const postIdInput = $('#post_ID');
        if (postIdInput.length > 0) {
            return postIdInput.val();
        }
        
        return null;
    }

    // باز کردن مودال جستجوی تصویر
    function openImageSearchModal() {
        console.log('Opening image search modal');
        
        // اگر متاباکس وجود دارد، روی آن کلیک کنید
        if (container.length > 0) {
            console.log('Metabox found, clicking on it');
            container.closest('.postbox').find('.hndle').click();
        } else {
            console.log('Creating modal dialog');
            // ایجاد مودال ساده
            const modal = $('<div id="image-search-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999;">' +
                '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 5px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">' +
                '<h3>جستجوی تصویر هوشمند</h3>' +
                '<p><input type="text" id="modal-search-keyword" placeholder="کلمه کلیدی را وارد کنید..." style="width: 100%; padding: 8px; margin: 10px 0;"></p>' +
                '<p><button type="button" id="modal-search-button" class="button button-primary">جستجو</button> ' +
                '<button type="button" id="modal-close" class="button">بستن</button></p>' +
                '<div id="modal-results"></div>' +
                '</div></div>');
            
            $('body').append(modal);
            modal.show();
            
            // event listeners برای مودال
            $('#modal-search-button').on('click', function() {
                const keyword = $('#modal-search-keyword').val();
                if (keyword) {
                    console.log('Modal search for keyword:', keyword);
                    // جستجوی مستقیم از مودال
                    searchImagesFromModal(keyword);
                }
            });
            
            $('#modal-close').on('click', function() {
                modal.remove();
            });
            
            // بستن مودال با کلیک روی پس‌زمینه
            modal.on('click', function(e) {
                if (e.target === this) {
                    modal.remove();
                }
            });
        }
    }
    
    // جستجوی تصاویر از مودال
    function searchImagesFromModal(keyword) {
        if (typeof smartAdminImage === 'undefined') {
            alert('خطا: متغیرهای JavaScript بارگذاری نشده‌اند.');
            return;
        }
        
        const modalResults = $('#modal-results');
        modalResults.html('<div style="text-align: center; padding: 20px;"><span class="spinner is-active"></span> در حال جستجو...</div>');
        
        $.ajax({
            url: smartAdminImage.ajax_url,
            type: 'POST',
            data: {
                action: 'smart_admin_search_images',
                nonce: smartAdminImage.nonce,
                keyword: keyword
            },
            success: function(response) {
                console.log('Modal AJAX response:', response);
                if (response.success) {
                    displayImagesInModal(response.data, keyword);
                } else {
                    modalResults.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Modal AJAX error:', {xhr, status, error});
                modalResults.html('<div class="notice notice-error"><p>❌ خطا در جستجوی تصاویر</p></div>');
            }
        });
    }
    
    // نمایش تصاویر در مودال
    function displayImagesInModal(images, keyword) {
        const modalResults = $('#modal-results');
        
        if (images.length === 0) {
            modalResults.html('<div class="notice notice-warning"><p>🔍 هیچ تصویری برای "' + keyword + '" یافت نشد.</p></div>');
            return;
        }
        
        let html = '<div class="image-search-header">';
        html += '<h4>نتایج جستجو برای "' + keyword + '" (' + images.length + ' تصویر)</h4>';
        html += '</div>';
        html += '<div class="image-grid">';
        
        images.forEach(function(image) {
            html += createImageCard(image);
        });
        
        html += '</div>';
        modalResults.html(html);
    }

    // اضافه کردن استایل‌های CSS
    const styles = `
        <style>
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .image-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .image-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .image-preview {
            position: relative;
            height: 150px;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-info {
            padding: 10px;
        }
        
        .image-alt {
            font-weight: bold;
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .image-description {
            font-size: 12px;
            color: #666;
            margin: 0 0 5px 0;
        }
        
        .image-author {
            font-size: 11px;
            color: #999;
            margin: 0;
        }
        
        .image-author a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .image-actions {
            padding: 10px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 5px;
        }
        
        .image-actions .button {
            flex: 1;
            font-size: 11px;
            padding: 5px 8px;
        }
        
        .image-search-header {
            margin-bottom: 15px;
        }
        
        .image-search-header h4 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .suggested-keywords {
            margin-top: 10px;
        }
        
        .keyword-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .keyword-tag {
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 15px;
            padding: 3px 8px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .keyword-tag:hover {
            background: #e1e1e1;
        }
        
        #image-search-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        #image-search-loading .spinner {
            float: none;
            margin: 0 10px 0 0;
        }
        </style>
    `;
    
    $('head').append(styles);
}); 
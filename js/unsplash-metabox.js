jQuery(document).ready(function($) {
    const container = $('#unsplash-metabox-container');
    const searchButton = $('#unsplash-search-button');
    const searchInput = $('#unsplash-search-keyword');
    const resultsContainer = $('#unsplash-results');

    // Search for images
    searchButton.on('click', function() {
        const keyword = searchInput.val();
        if (!keyword) {
            alert('لطفاً یک کلمه کلیدی وارد کنید.');
            return;
        }

        container.addClass('loading');
        resultsContainer.html('');

        $.ajax({
            url: unsplash_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'faraz_unsplash_search_images',
                nonce: unsplash_ajax.nonce,
                keyword: keyword
            },
            success: function(response) {
                if (response.success) {
                    displayImages(response.data);
                } else {
                    resultsContainer.html('<p>' + response.data.message + '</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p>خطای ناشناخته در هنگام جستجو رخ داد.</p>');
            },
            complete: function() {
                container.removeClass('loading');
            }
        });
    });

    // Display images
    function displayImages(images) {
        if (images.length === 0) {
            resultsContainer.html('<p>هیچ تصویری یافت نشد.</p>');
            return;
        }

        images.forEach(function(image) {
            const imageUrl = image.urls.small;
            const altText = image.alt_description || searchInput.val();
            const wrapper = $('<div class="unsplash-image-wrapper"></div>');
            const img = $('<img>').attr('src', imageUrl).attr('alt', altText);
            
            wrapper.append(img);
            resultsContainer.append(wrapper);

            // Set as featured image on click
            wrapper.on('click', function() {
                setFeaturedImage(image.urls.regular, altText);
            });
        });
    }

    // Set featured image
    function setFeaturedImage(imageUrl, altText) {
        container.addClass('loading');
        
        $.ajax({
            url: unsplash_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'faraz_unsplash_set_image',
                nonce: unsplash_ajax.nonce,
                post_id: unsplash_ajax.post_id,
                image_url: imageUrl,
                alt_text: altText
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Update the featured image preview in WordPress admin
                    if (WPSetThumbnailHTML && response.data.thumbnail_url) {
                        // This is a simplified way; a more robust solution might be needed
                        // depending on the WordPress version and other plugins.
                        $('#postimagediv .inside').html(
                            '<img src="' + response.data.thumbnail_url + '" alt="Featured Image">'
                        );
                    } else {
                         // Fallback to reload the page to see the change
                        location.reload();
                    }
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            error: function() {
                alert('خطای ناشناخته در هنگام تنظیم تصویر شاخص رخ داد.');
            },
            complete: function() {
                container.removeClass('loading');
            }
        });
    }
});

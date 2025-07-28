/**
 * اسکریپت بهینه‌ساز خودکار SEO برای ویرایشگر کلاسیک
 */
(function($) {
    'use strict';

    // مودال نمایش نتایج
    var seoModal = $('<div class="smart-admin-seo-modal">' +
        '<div class="smart-admin-seo-modal-content">' +
            '<div class="smart-admin-seo-modal-header">' +
                '<div class="smart-admin-seo-modal-title">نتایج بهینه‌سازی SEO</div>' +
                '<div class="smart-admin-seo-modal-close">&times;</div>' +
            '</div>' +
            '<div class="smart-admin-seo-modal-body"></div>' +
        '</div>' +
    '</div>');

    // افزودن مودال به body
    $('body').append(seoModal);

    // بستن مودال
    $('.smart-admin-seo-modal-close').on('click', function() {
        $('.smart-admin-seo-modal').hide();
    });

    // کلیک خارج از مودال
    $(document).on('click', '.smart-admin-seo-modal', function(e) {
        if ($(e.target).hasClass('smart-admin-seo-modal')) {
            $('.smart-admin-seo-modal').hide();
        }
    });

    // کلیک روی دکمه بهینه‌سازی
    $(document).on('click', '#smart-admin-seo-optimizer-button', function() {
        var postId = $('#post_ID').val();
        
        if (!postId) {
            alert('شناسه پست یافت نشد!');
            return;
        }
        
        // نمایش مودال با حالت بارگذاری
        $('.smart-admin-seo-modal-body').html(
            '<div class="smart-admin-seo-loading">' +
                '<div class="smart-admin-seo-spinner"></div>' +
                '<div>در حال بهینه‌سازی SEO...</div>' +
            '</div>'
        );
        $('.smart-admin-seo-modal').css('display', 'flex');
        
        // ارسال درخواست AJAX
        $.ajax({
            url: smartAdminSEO.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'smart_admin_auto_optimize_seo',
                post_id: postId,
                nonce: smartAdminSEO.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    displayError(response.data);
                }
            },
            error: function(xhr, status, error) {
                displayError('خطا در ارتباط با سرور: ' + status);
            }
        });
    });

    /**
     * نمایش نتایج بهینه‌سازی
     */
    function displayResults(data) {
        var html = '';
        
        console.log("SEO Data received:", data); // اضافه شده برای عیب‌یابی
        
        // امتیاز SEO
        var seoScore = (data.seo_score !== undefined && data.seo_score !== null) ? parseInt(data.seo_score) : 50;
        var scoreClass = 'bad';
        
        if (seoScore >= 80) {
            scoreClass = 'good';
        } else if (seoScore >= 50) {
            scoreClass = 'ok';
        }
        
        html += '<div class="smart-admin-seo-score">' +
            '<div class="smart-admin-seo-score-circle smart-admin-seo-score-' + scoreClass + '">' + seoScore + '</div>' +
            '<div class="smart-admin-seo-score-text">امتیاز SEO شما</div>' +
        '</div>';
        
        // عنوان SEO
        if (data.seo_title) {
            html += '<div class="smart-admin-seo-result-item">' +
                '<div class="smart-admin-seo-result-title">عنوان SEO:</div>' +
                '<div class="smart-admin-seo-result-value">' + data.seo_title + '</div>' +
            '</div>';
        }
        
        // توضیحات متا
        if (data.meta_description) {
            html += '<div class="smart-admin-seo-result-item">' +
                '<div class="smart-admin-seo-result-title">توضیحات متا:</div>' +
                '<div class="smart-admin-seo-result-value">' + data.meta_description + '</div>' +
            '</div>';
        }
        
        // نامک (URL)
        if (data.slug) {
            html += '<div class="smart-admin-seo-result-item">' +
                '<div class="smart-admin-seo-result-title">نامک (URL):</div>' +
                '<div class="smart-admin-seo-result-value">' + data.slug + '</div>' +
            '</div>';
        }
        
        // کلمات کلیدی
        if (data.focus_keywords) {
            html += '<div class="smart-admin-seo-result-item">' +
                '<div class="smart-admin-seo-result-title">کلمات کلیدی:</div>' +
                '<div class="smart-admin-seo-result-value">' + data.focus_keywords + '</div>' +
            '</div>';
        }
        
        // توصیه‌ها
        if (data.recommendations && data.recommendations.length > 0) {
            html += '<div class="smart-admin-seo-recommendations">' +
                '<div class="smart-admin-seo-result-title">توصیه‌ها برای بهبود SEO:</div>';
            
            $.each(data.recommendations, function(index, recommendation) {
                html += '<div class="smart-admin-seo-recommendation-item">' + recommendation + '</div>';
            });
            
            html += '</div>';
        }
        
        // دکمه بستن
        html += '<div style="text-align: center; margin-top: 20px;">' +
            '<button type="button" class="button button-primary" onclick="$(\'.smart-admin-seo-modal\').hide();">بستن</button>' +
        '</div>';
        
        $('.smart-admin-seo-modal-body').html(html);
        // به‌روزرسانی اطلاعات سئو در بخش ثابت صفحه
        if (data.focus_keywords) {
            $('#focus_keywords_display').text(data.focus_keywords);
        }
        if (data.seo_score !== undefined && data.seo_score !== null) {
            $('#seo_score_display').text(parseInt(data.seo_score));
        }
        
        // بروزرسانی فیلدهای Rank Math
        setTimeout(function() {
            location.reload();
        }, 2000);
    }

    /**
     * نمایش خطا
     */
    function displayError(message) {
        var html = '<div style="color: #dc3232; text-align: center; padding: 20px;">' +
            '<span class="dashicons dashicons-warning" style="font-size: 48px; width: 48px; height: 48px;"></span>' +
            '<p style="font-size: 16px; margin-top: 10px;">' + message + '</p>' +
            '<button type="button" class="button" onclick="$(\'.smart-admin-seo-modal\').hide();">بستن</button>' +
        '</div>';
        
        $('.smart-admin-seo-modal-body').html(html);
    }

})(jQuery); 
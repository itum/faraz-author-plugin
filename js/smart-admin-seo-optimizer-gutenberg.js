/**
 * اسکریپت بهینه‌ساز خودکار SEO برای ویرایشگر گوتنبرگ
 */
(function(wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var { Fragment } = wp.element;
    var { Button } = wp.components;
    var { withSelect, withDispatch } = wp.data;
    var { compose } = wp.compose;
    var el = wp.element.createElement;
    
    /**
     * ساخت آیکن SEO
     */
    var SeoIcon = function() {
        return el(
            'svg',
            {
                width: 20,
                height: 20,
                viewBox: '0 0 24 24',
                xmlns: 'http://www.w3.org/2000/svg'
            },
            el('path', {
                d: 'M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 16h-2v-2h2v2zm0-4h-2V7h2v7z',
                fill: 'currentColor'
            })
        );
    };
    
    /**
     * کامپوننت دکمه بهینه‌سازی SEO
     */
    var SeoOptimizerButton = compose(
        withSelect(function(select) {
            return {
                postId: select('core/editor').getCurrentPostId()
            };
        }),
        withDispatch(function(dispatch) {
            return {
                optimizeSeo: function(postId) {
                    // نمایش مودال
                    var modal = document.querySelector('.smart-admin-seo-modal');
                    var modalBody = document.querySelector('.smart-admin-seo-modal-body');
                    
                    if (!modal || !modalBody) {
                        return;
                    }
                    
                    // نمایش حالت بارگذاری
                    modalBody.innerHTML = '<div class="smart-admin-seo-loading">' +
                        '<div class="smart-admin-seo-spinner"></div>' +
                        '<div>در حال بهینه‌سازی SEO...</div>' +
                        '</div>';
                    modal.style.display = 'flex';
                    
                    // ارسال درخواست AJAX
                    jQuery.ajax({
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
                }
            };
        })
    )(function(props) {
        return el(
            Button,
            {
                isPrimary: true,
                className: 'smart-admin-seo-gutenberg-button',
                onClick: function() {
                    props.optimizeSeo(props.postId);
                }
            },
            el('span', { className: 'dashicons dashicons-chart-line' }),
            'بهینه‌سازی خودکار SEO'
        );
    });
    
    /**
     * کامپوننت پنل بهینه‌ساز SEO
     */
    var SeoOptimizerPanel = function() {
        return el(
            Fragment,
            {},
            el(
                PluginSidebarMoreMenuItem,
                {
                    target: 'smart-admin-seo-optimizer'
                },
                'بهینه‌ساز خودکار SEO'
            ),
            el(
                PluginSidebar,
                {
                    name: 'smart-admin-seo-optimizer',
                    title: 'بهینه‌ساز خودکار SEO'
                },
                el(
                    'div',
                    {
                        className: 'smart-admin-seo-optimizer-panel'
                    },
                    el(
                        'p',
                        {},
                        'با کلیک روی دکمه زیر، نوشته شما بر اساس استانداردهای Rank Math بهینه‌سازی خواهد شد.'
                    ),
                    el(SeoOptimizerButton, {})
                )
            )
        );
    };
    
    /**
     * نمایش نتایج بهینه‌سازی
     */
    function displayResults(data) {
        var modalBody = document.querySelector('.smart-admin-seo-modal-body');
        
        if (!modalBody) {
            return;
        }
        
        console.log("SEO Data received (Gutenberg):", data);
        
        var html = '';
        
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
            
            data.recommendations.forEach(function(recommendation) {
                html += '<div class="smart-admin-seo-recommendation-item">' + recommendation + '</div>';
            });
            
            html += '</div>';
        }
        
        // دکمه بستن
        html += '<div style="text-align: center; margin-top: 20px;">' +
            '<button type="button" class="button button-primary" onclick="document.querySelector(\'.smart-admin-seo-modal\').style.display = \'none\';">بستن</button>' +
            '</div>';
        
        modalBody.innerHTML = html;
        
        // بروزرسانی فیلدهای Rank Math
        setTimeout(function() {
            location.reload();
        }, 2000);
    }
    
    /**
     * نمایش خطا
     */
    function displayError(message) {
        var modalBody = document.querySelector('.smart-admin-seo-modal-body');
        
        if (!modalBody) {
            return;
        }
        
        var html = '<div style="color: #dc3232; text-align: center; padding: 20px;">' +
            '<span class="dashicons dashicons-warning" style="font-size: 48px; width: 48px; height: 48px;"></span>' +
            '<p style="font-size: 16px; margin-top: 10px;">' + message + '</p>' +
            '<button type="button" class="button" onclick="document.querySelector(\'.smart-admin-seo-modal\').style.display = \'none\';">بستن</button>' +
            '</div>';
        
        modalBody.innerHTML = html;
    }
    
    /**
     * ایجاد مودال نتایج
     */
    function createModal() {
        var modal = document.createElement('div');
        modal.className = 'smart-admin-seo-modal';
        modal.innerHTML = '<div class="smart-admin-seo-modal-content">' +
            '<div class="smart-admin-seo-modal-header">' +
                '<div class="smart-admin-seo-modal-title">نتایج بهینه‌سازی SEO</div>' +
                '<div class="smart-admin-seo-modal-close">&times;</div>' +
            '</div>' +
            '<div class="smart-admin-seo-modal-body"></div>' +
        '</div>';
        
        document.body.appendChild(modal);
        
        // بستن مودال
        modal.querySelector('.smart-admin-seo-modal-close').addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // کلیک خارج از مودال
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    /**
     * ثبت پلاگین
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createModal();
            registerPlugin('smart-admin-seo-optimizer', {
                render: SeoOptimizerPanel
            });
        });
    } else {
        createModal();
        registerPlugin('smart-admin-seo-optimizer', {
            render: SeoOptimizerPanel
        });
    }
    
})(window.wp); 
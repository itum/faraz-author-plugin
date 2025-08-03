<?php
/**
 * توابع دریافت و اضافه کردن تصویر از Unsplash برای نوشته‌ها
 *
 * این فایل شامل یک تابع کمکی است که با استفاده از API رسمی Unsplash
 * مرتبط-ترین تصویر را بر اساس واژهٔ کلیدی واکشی کرده و به عنوان تصویر شاخص
 * (Featured Image) به پست مورد نظر اختصاص می‌دهد.
 *
 * هشدار: برای استفاده از این تابع باید «کلید دسترسی» (Access Key) مربوط به
 * اپلیکیشن Unsplash خود را در تنظیمات «نویسندهٔ خودکار هوشمند» وارد کنید.
 */

if ( ! function_exists( 'smart_admin_fetch_unsplash_image_for_post' ) ) {
    /**
     * واکشی تصویر از Unsplash و تنظیم آن به عنوان تصویر شاخص یک پست
     *
     * @param int    $post_id  شناسهٔ پست وردپرس
     * @param string $keyword  واژهٔ کلیدی برای جست‌وجوی تصویر
     *
     * @return int|false  شناسهٔ فایل رسانه در صورت موفقیت یا false در صورت خطا
     */
    function smart_admin_fetch_unsplash_image_for_post( $post_id, $keyword = '' ) {
        error_log( 'Smart Admin Unsplash: شروع فرآیند دریافت تصویر برای پست ' . $post_id );
        
        // دریافت کلید دسترسی از تنظیمات افزونه
        $access_key = get_option( 'smart_admin_unsplash_access_key', '' );
        $auto_image = get_option( 'smart_admin_enable_auto_image', '0' );

        error_log( 'Smart Admin Unsplash: کلید دسترسی: ' . ( ! empty( $access_key ) ? 'موجود' : 'خالی' ) );
        error_log( 'Smart Admin Unsplash: فعال‌سازی خودکار: ' . $auto_image );

        // در صورت غیرفعال بودن قابلیت یا خالی بودن کلید، خروج
        if ( empty( $access_key ) || $auto_image !== '1' ) {
            error_log( 'Smart Admin Unsplash: قابلیت غیرفعال یا کلید دسترسی خالی است' );
            return false;
        }

        $keyword = trim( $keyword );
        if ( empty( $keyword ) ) {
            error_log( 'Smart Admin Unsplash: کلیدواژه خالی است' );
            return false;
        }

        error_log( 'Smart Admin Unsplash: جستجو برای کلیدواژه: ' . $keyword );

        // تشکیل آدرس API
        $api_url = add_query_arg( array(
            'query'       => urlencode( $keyword ),
            'orientation' => 'landscape',
            'per_page'    => 1,
            'client_id'   => $access_key,
        ), 'https://api.unsplash.com/search/photos' );

        error_log( 'Smart Admin Unsplash: آدرس API: ' . $api_url );

        // درخواست به API
        $response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            error_log( 'Smart Admin Unsplash Error: ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        error_log( 'Smart Admin Unsplash: پاسخ API دریافت شد. تعداد نتایج: ' . ( isset( $data['total'] ) ? $data['total'] : 'نامشخص' ) );
        
        if ( empty( $data['results'][0]['urls']['regular'] ) ) {
            error_log( 'Smart Admin Unsplash: هیچ تصویری یافت نشد' );
            return false;
        }

        $image_url   = esc_url_raw( $data['results'][0]['urls']['regular'] );
        $alt_text    = ! empty( $data['results'][0]['alt_description'] ) ? sanitize_text_field( $data['results'][0]['alt_description'] ) : sanitize_text_field( $keyword );
        $photographer = $data['results'][0]['user']['name'] ?? '';
        $photographer_url = $data['results'][0]['user']['links']['html'] ?? '';

        error_log( 'Smart Admin Unsplash: تصویر انتخاب شد. آدرس: ' . $image_url );
        error_log( 'Smart Admin Unsplash: عکاس: ' . $photographer );

        // بارگذاری ماژول‌های رسانه در صورت نیاز
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // دانلود و بارگذاری تصویر در وردپرس
        $image_id = media_sideload_image( $image_url, $post_id, $alt_text, 'id' );
        if ( is_wp_error( $image_id ) ) {
            error_log( 'Smart Admin Unsplash Error: ' . $image_id->get_error_message() );
            return false;
        }

        error_log( 'Smart Admin Unsplash: تصویر با موفقیت دانلود شد. شناسه: ' . $image_id );

        // تنظیم تصویر شاخص
        set_post_thumbnail( $post_id, $image_id );
        error_log( 'Smart Admin Unsplash: تصویر شاخص تنظیم شد' );

        // افزودن کرِدیت عکاس به متادیتا برای شفافیت لایسنس
        if ( $photographer ) {
            $credit_text = sprintf( 'Photo by %s on Unsplash', $photographer );
            if ( $photographer_url ) {
                $credit_text = sprintf( 'Photo by <a href="%s" target="_blank" rel="noopener">%s</a> on Unsplash', esc_url( $photographer_url ), esc_html( $photographer ) );
            }
            update_post_meta( $post_id, '_unsplash_credit', wp_kses_post( $credit_text ) );
            error_log( 'Smart Admin Unsplash: کرِدیت عکاس ذخیره شد' );
        }

        error_log( 'Smart Admin Unsplash: فرآیند با موفقیت به پایان رسید' );
        return (int) $image_id;
    }
}

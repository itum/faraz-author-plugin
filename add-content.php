<?php
include_once 'jdf.php';

add_filter('wp_feed_cache_transient_lifetime', function() {
    return 600;  
});
function deleted_post1() { 
    if ((microtime(true) - $start_time) > 25) {
        if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
            return new WP_REST_Response(array('processed_items' => $processed_items), 200);
        } 
    }
    
    $processed_items = [];
    $start_time = microtime(true);  

    global $wpdb;

    $unused_images = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%' 
        AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND ID NOT IN (
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_thumbnail_id'
        )
        ORDER BY post_date DESC
        LIMIT 50
    ");
      
    foreach ($unused_images as $image) { 
        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            } 
        }

        $post_id = $wpdb->get_var("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_value = $image->ID 
            AND meta_key = '_thumbnail_id'
        ");
     
        if (empty($post_id)) {
            wp_delete_attachment($image->ID, true);  
    
            $processed_items[] = $image->ID;
        }
     

    }
}
function deleted_post()
{
    $processed_items = [];
    $start_time = microtime(true);  
    return new WP_REST_Response(array('processed_items' => $processed_items), 200);
    global $wpdb;
 

 
    $unused_images = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%' 
        AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND ID NOT IN (
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_thumbnail_id'
        )
        LIMIT 80
    ");
      
    foreach ($unused_images as $image) { 
        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            } 
        }

        $post_id = $wpdb->get_var("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_value = $image->ID 
            AND meta_key = '_thumbnail_id'
        ");
     
        if (empty($post_id)) {
            wp_delete_attachment($image->ID, true);  
    
            $processed_items[] = $image->ID;
        }
     

    }
}
function check_post_title_exists($title , $excerpt , $post_content) {
    global $wpdb;
  
    $query = $wpdb->prepare("
        SELECT COUNT(*) 
        FROM $wpdb->posts 
        WHERE post_title = %s OR post_excerpt = %s OR post_content = %s
    ", $title , $excerpt , $post_content);

    $count = intval($wpdb->get_var($query)); 
    return $count ;  
}
 
function stp_check_for_new_rss_items() { 
    $start_time = microtime(true);  
    global $wpdb;

    
    $date_threshold = date('Y-m-d H:i:s', current_time('timestamp') - (12 * 3600));
 
    $posts = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE (post_status = 'draft' OR post_status = 'faraz') 
        AND post_date < '$date_threshold'
    ");
        
    foreach ($posts as $post) { 
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            wp_delete_attachment($thumbnail_id, true);  
        } 
        wp_delete_post($post->ID, true);

        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            }
        }
    }








    $entries = get_option('stp_entries', array());
    include_once(ABSPATH . WPINC . '/feed.php');
  
 
    try {
        foreach ($entries as $entry) {
            $rss = fetch_feed($entry['url']);
            $class = $entry['class'];
            $type = $entry['type'];

            if (!is_wp_error($rss)) {
                $max_items = $rss->get_item_quantity(4);
                $rss_items = $rss->get_items(0, $max_items);
                $a = 0;
                foreach ($rss_items as $item) { 
                    $date = strtotime($item->get_date('Y-m-d H:i:s'));
                    if (empty($date)) sendErrorToTelegram('$item');
                
                    if ($date !== false AND ((3600 * 2) < (time() - $date))) continue;
                
                    if ((microtime(true) - $start_time) > 25) {
                        if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                            return new WP_REST_Response(array('processed_items' => $processed_items), 200);
                        } else break 2;
                    }
                


                    $datej = jdate('Y-m-d H:i:s', $date);
                
                    $link = $item->get_permalink();
                    $thumbnail_url = fetch_thumbnail($link);
                    $thumbnail_url = $thumbnail_url['image'];
                    if (empty($thumbnail_url)){
                        $processed_items[] = ['error_thumbnail' => [$title, $datej , $link , $thumbnail_url  ]];
                        continue; 
                    }
                    
                    $source = parse_url($link, PHP_URL_HOST);
                    $title = $item->get_title();
 
                    $processed_items[] = [$title, $datej , $link , $source];
                
                    $full_text = fetch_full_text($link, $type, $class);
                    $excerpt = $item->get_content();

                    // Add signature if enabled
                    if (get_option('farazautur_signature_enabled')) {
                        $signature = get_option('farazautur_signature_text');
                        if (!empty($signature)) {
                            $full_text .= "\n\n" . wp_strip_all_tags($signature);
                            $excerpt .= "\n\n" . wp_strip_all_tags($signature);
                        }
                    }
                
                    if (check_post_title_exists($title, $excerpt, $full_text) > 0) continue;
                
                    $category_id = $entry['channel_title'];
                

                    $post_data = [
                        'post_title'    => $title,
                        'post_content'  => $full_text,
                        'post_excerpt'  => $excerpt,
                        'post_status'   => 'faraz',
                        'post_author'   => 1,
                        'post_category' => [(int) $category_id]
                    ];
                    $post_id = wp_insert_post($post_data);
                
                    if (is_wp_error($post_id) AND (sendErrorToTelegram('Error creating post: ' . $post_id->get_error_message())  OR True)) continue;
                
                    if ((check_post_title_exists($title, $excerpt, $full_text) >= 2) AND (wp_delete_post($post_id, true) OR true)) continue;
                    if ($post_id && $thumbnail_url) attach_thumbnail($post_id, $thumbnail_url);
                
                    if ($post_id && $thumbnail_url) { 
                        $cat = get_cat_name(intval(esc_html($entry['channel_title'])));
                        $message = "$title \n\n$excerpt \n\nدسته بندی : $cat \n\nنام سایت : $source \n\n  $datej ";
                        send_telegram_photo_with_caption($thumbnail_url, $message, $post_id);
                    }
                
                    usleep(333);  
                }
                
            }
        }
 
        
        
    } catch (Exception $e) {
        sendErrorToTelegram('An error occurred: ' . $e->getMessage());
        if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 500);
        }
        return 'An error occurred: ' . $e->getMessage();
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
        return new WP_REST_Response(array('processed_items' => $processed_items), 200);
    }

    return 'RSS items have been processed.';
}




    


function fetch_full_text($url, $type, $class) {
    $content = fetch_content($url);
    if ($content === false) {
        return '';  
    }
 
    libxml_use_internal_errors(true);
 
    $dom = new DOMDocument();
     
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    
    @$dom->loadHTML($content, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
 
    $xpath = new DOMXPath($dom);
    $query = "//{$type}[contains(@class, '{$class}')]";
    $nodes = $xpath->query($query);

    // Check if nodes are found
    if ($nodes->length > 0) {
        $full_text = '';
        foreach ($nodes as $node) {
            $full_text .= $dom->saveHTML($node);
        }
        return $full_text;
    } else {
        return '';  
    }
}


function fetch_thumbnail($url)
{
    $content = fetch_content($url);
    if ($content === false) return ['image' => '', 'date_published' => ''];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8" ?>' . $content);

    $xpath = new DOMXPath($dom);
 
    $image = '';
    $nodes = $xpath->query('//meta[@property="og:image"]');
    if ($nodes->length > 0) {
        $image = $nodes->item(0)->getAttribute('content');
    } else { 
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptNodes as $node) {
            $jsonContent = $node->textContent;
            $jsonData = json_decode($jsonContent, true);

            if (isset($jsonData['image'])) {
                if (is_string($jsonData['image'])) {
                    $image = $jsonData['image'];
                } elseif (is_array($jsonData['image']) && count($jsonData['image']) > 0) {
                    $image = $jsonData['image'][0];
                }
            }
        }
    } 
    if (empty($image)) {
        $twitterNodes = $xpath->query('//meta[@name="twitter:image"]');
        if ($twitterNodes->length > 0) {
            $image = $twitterNodes->item(0)->getAttribute('content');
        }
    }
 
    $date_published = '';
    $dateNodes = $xpath->query('//meta[@property="article:published_time"]');
    if ($dateNodes->length > 0) {
        $date_published = $dateNodes->item(0)->getAttribute('content');
    } else { 
        $modifiedNodes = $xpath->query('//meta[@property="article:modified_time"]');
        if ($modifiedNodes->length > 0) {
            $date_published = $modifiedNodes->item(0)->getAttribute('content');
        }
    }

    return ['image' => $image, 'date_published' => $date_published];
}

function fetch_content($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $output = curl_exec($ch);
    if(curl_error($ch))
    {
        // sendErrorToTelegram(json_encode($output));
    }
    
    curl_close($ch);

    return $output ? $output : false;
}

 
function attach_thumbnail($post_id, $thumbnail_url) { 
    if (!function_exists('media_sideload_image')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
 
    $image_id = media_sideload_image($thumbnail_url, $post_id, null, 'id');
 
    if (!is_wp_error($image_id)) {
        set_post_thumbnail($post_id, $image_id);
    } else { 
        error_log('Failed to sideload image: ' . $image_id->get_error_message());
    }
}


?>

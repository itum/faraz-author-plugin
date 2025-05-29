<?php
if (!defined('ABSPATH')) {
    exit;
}

// Register settings
add_action('admin_init', 'farazautur_signature_settings_init');
function farazautur_signature_settings_init() {
    register_setting('farazautur_signature', 'farazautur_signature_enabled');
    register_setting('farazautur_signature', 'farazautur_signature_text');
}

// Add TinyMCE scripts
add_action('admin_enqueue_scripts', 'farazautur_add_editor_scripts');
function farazautur_add_editor_scripts($hook) {
    if ($hook == 'faraz-plugin_page_faraz-telegram-plugin') {
        wp_enqueue_editor();
        wp_enqueue_media();
    }
}

// Create the settings page
function farazautur_signature_page() {
    ?>
    <div class="wrap" style="direction: rtl;">
        <h1 style="color: #2c3e50; font-size: 2.2em; margin-bottom: 30px; border-bottom: 3px solid #3498db; padding-bottom: 10px; display: inline-block;">تنظیمات امضا</h1>
        
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-top: 20px;">
            <form method="post" action="options.php">
                <?php settings_fields('farazautur_signature'); ?>
                
                <table class="form-table" style="margin-top: 20px;">
                    <tr valign="top">
                        <th scope="row" style="padding: 20px 0;">
                            <label style="font-size: 15px; color: #2c3e50;">فعال‌سازی امضا</label>
                        </th>
                        <td style="padding: 15px 0;">
                            <label class="switch">
                                <input type="checkbox" name="farazautur_signature_enabled" value="1" <?php checked(1, get_option('farazautur_signature_enabled'), true); ?>>
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" style="padding: 20px 0;">
                            <label style="font-size: 15px; color: #2c3e50;">متن امضا</label>
                        </th>
                        <td style="padding: 15px 0;">
                            <?php
                            $content = get_option('farazautur_signature_text');
                            $editor_id = 'farazautur_signature_text';
                            $settings = array(
                                'textarea_name' => 'farazautur_signature_text',
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'tinymce' => array(
                                    'directionality' => 'rtl',
                                    'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignright,alignleft,aligncenter,link,wp_more,spellchecker,fullscreen,wp_adv,emoticons',
                                    'toolbar2' => 'strikethrough,hr,forecolor,backcolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                                ),
                                'quicktags' => true,
                            );
                            wp_editor($content, $editor_id, $settings);
                            ?>
                            <p class="description" style="margin-top: 10px; color: #7f8c8d;">این متن در انتهای هر پست نمایش داده خواهد شد.</p>
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 30px;">
                    <?php 
                    submit_button('ذخیره تنظیمات', 'primary', 'submit', false, array(
                        'style' => 'background: #3498db; 
                                  border: none; 
                                  padding: 10px 25px; 
                                  border-radius: 6px; 
                                  font-size: 14px; 
                                  cursor: pointer; 
                                  transition: background 0.3s ease;'
                    )); 
                    ?>
                </div>
            </form>
        </div>
    </div>

    <style>
    /* Switch styles */
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #3498db;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #3498db;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    /* Form styles */
    .button-primary:hover {
        background: #2980b9 !important;
    }

    /* Success message styles */
    div.updated {
        background: #2ecc71;
        border-left: 4px solid #27ae60;
        padding: 12px 15px;
        margin: 20px 0;
        color: white;
        border-radius: 4px;
    }

    /* Editor styles */
    .wp-editor-container {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .wp-editor-container:focus-within {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .wp-editor-area {
        direction: rtl;
    }
    </style>
    <?php
}

// Add signature to post content
add_filter('the_content', 'farazautur_add_signature');
function farazautur_add_signature($content) {
    // Check if signature is enabled
    if (get_option('farazautur_signature_enabled')) {
        // Get signature text
        $signature = get_option('farazautur_signature_text');
        
        // Only add signature to single posts
        if (is_single()) {
            $content .= '<div class="farazautur-signature" style="margin-top: 30px; padding: 15px; border-top: 1px solid #ddd;">';
            $content .= wpautop($signature); // Use wpautop to properly format HTML content
            $content .= '</div>';
        }
    }
    return $content;
} 
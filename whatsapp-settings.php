<?php
function farazautur_whatsapp_settings_page() {
    ?>
    <div class="wrap">
        <h1>تنظیمات واتس‌اپ</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('farazautur_whatsapp_settings_group');
            do_settings_sections('faraz-telegram-plugin-whatsapp');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function farazautur_register_whatsapp_settings() {
    register_setting('farazautur_whatsapp_settings_group', 'farazautur_whatsapp_api_key');
    register_setting('farazautur_whatsapp_settings_group', 'farazautur_whatsapp_group_id');

    add_settings_section(
        'farazautur_whatsapp_api_section',
        'API Settings',
        null,
        'faraz-telegram-plugin-whatsapp'
    );

    add_settings_field(
        'farazautur_whatsapp_api_key',
        'API Key',
        'farazautur_whatsapp_api_key_callback',
        'faraz-telegram-plugin-whatsapp',
        'farazautur_whatsapp_api_section'
    );

    add_settings_field(
        'farazautur_whatsapp_group_id',
        'Group ID',
        'farazautur_whatsapp_group_id_callback',
        'faraz-telegram-plugin-whatsapp',
        'farazautur_whatsapp_api_section'
    );
}
add_action('admin_init', 'farazautur_register_whatsapp_settings');

function farazautur_whatsapp_api_key_callback() {
    $api_key = get_option('farazautur_whatsapp_api_key');
    echo '<input type="text" id="farazautur_whatsapp_api_key" name="farazautur_whatsapp_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

function farazautur_whatsapp_group_id_callback() {
    $group_id = get_option('farazautur_whatsapp_group_id');
    echo '<input type="text" id="farazautur_whatsapp_group_id" name="farazautur_whatsapp_group_id" value="' . esc_attr($group_id) . '" class="regular-text">';
} 
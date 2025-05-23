<?php 
define( 'CUSTOM_STATUS', 'faraz' );  
function custom_post_status_faraz() {
    register_post_status('faraz', array(
        'label'                     => _x('فراز', 'post status'),
        'public'                    => false,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('فراز <span class="count">(%s)</span>', 'فراز <span class="count">(%s)</span>')
    ));
}
add_action('init', 'custom_post_status_faraz');
 
function add_custom_status_to_dropdown() {
  ?>
  <script>
  (function() {
    var select = document.querySelector('select[name="_status"]');
    var option = document.createElement('option');
    option.value = '<?php echo CUSTOM_STATUS; ?>';
    option.textContent = '<?php echo __( 'فراز' ); ?>'; // Change label accordingly (optional)
    select.appendChild(option);

    var label = document.querySelector('.post-status-label');
    var labelSpan = document.createElement('span');
    labelSpan.classList.add('post-status-label-inner');
    labelSpan.textContent = '<?php echo __( 'faraz' ); ?>';  
    label.appendChild(labelSpan);
  })();
  </script>
  <?php
}
add_action('admin_footer-edit.php','add_custom_status_to_dropdown');
add_action('admin_footer-post.php', 'add_custom_status_to_dropdown');
add_action('admin_footer-post-new.php', 'add_custom_status_to_dropdown');
 
function custom_status_plugin_textdomain() {
  load_plugin_textdomain( 'custom-status-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'custom_status_plugin_textdomain' );
 
function include_custom_status_on_all_sort( $query ) {
  if ( is_admin() && $query->is_main_query() && !in_array( $query->post_type, array( 'your_post_type' ) ) ) {
    if ( isset($_GET['post_status']) && $_GET['post_status'] === 'all' ) {
      $query->set( 'post_status', array( 'publish', 'other_public_statuses', CUSTOM_STATUS ) );
    }
  }
}
add_action( 'pre_get_posts', 'include_custom_status_on_all_sort' );
 
function stp_create_category($channel_title)
{
    $category_name = sanitize_text_field($channel_title);
    $existing_category = get_term_by('name', $category_name, 'category');

    if ($existing_category) {
        return $existing_category->term_id;
    } else {
        $category_id = wp_create_category($category_name);
        return $category_id;
    }
}
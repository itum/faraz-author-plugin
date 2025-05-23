<?php

function stp_render_page() { 
    $entries = get_option('stp_entries', array());

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['stp_add_entry'])) { 
            $new_entry = array(
                'url' => !empty($_POST['stp_input']) ? sanitize_text_field($_POST['stp_input']) : '',
                'type' => !empty($_POST['rss_fetcher_type']) ? sanitize_text_field($_POST['rss_fetcher_type']) : '',
                'class' => !empty($_POST['rss_fetcher_class']) ? sanitize_text_field($_POST['rss_fetcher_class']) : '',
                'channel_title' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : 0,
                'channel_description' => ''
            );

            if (isset($_POST['entry_index']) && $_POST['entry_index'] !== '') { 
                $entries[intval($_POST['entry_index'])] = $new_entry;
            } else { 
                $new_entry = stp_update_entry_with_channel_data($new_entry);
                $entries[] = $new_entry;
            }
            update_option('stp_entries', $entries);
        } elseif (isset($_POST['stp_delete_entry'])) { 
            $index_to_delete = intval($_POST['entry_index']);
            if (isset($entries[$index_to_delete])) {
                unset($entries[$index_to_delete]);
                $entries = array_values($entries);  
                update_option('stp_entries', $entries);
            }
        } elseif (isset($_POST['stp_submit_entries'])) { 
            stp_schedule_rss_check();
        }
    }
?>
<style>
[data-ml-modal] {
	position:fixed;
	top:0;
	bottom:0;
	left:0;
	right:0;
	overflow-x:hidden;
	overflow-y:auto;
	-webkit-overflow-scrolling:touch;
	z-index:999;
	width:0;
	height:0;
	opacity:0;
}
[data-ml-modal]:target {
	width:auto;
	height:auto;
	opacity:1;
	-webkit-transition:  opacity 1s ease;
	transition: opacity 1s ease;
}
[data-ml-modal]:target .modal-overlay {
	position:fixed;
	top:0;
	bottom:0;
	left:0;
	right:0;
	cursor:pointer;
	background-color:#000;
	background-color:rgba(0, 0, 0, 0.7);
	z-index:1;
}
[data-ml-modal] .modal-dialog {
	border-radius:6px;
	box-shadow:0 11px 15px -7px rgba(0, 0, 0, 0.2), 0 24px 38px 3px rgba(0, 0, 0, 0.14), 0 9px 46px 8px rgba(0, 0, 0, 0.12);
	position:relative;
	width: 90%;
	max-width:660px;
	max-height:70%;
	margin:10% auto;
	overflow-x:hidden;
	overflow-y:auto;
	z-index:2;
}
.modal-dialog-lg {max-width:820px !important;}

[data-ml-modal] .modal-dialog > h3 {
	background-color:#eee;
	border-bottom:1px solid #b3b3b3;
	font-size:24px;
	font-weight: 400;
	margin:0;
	padding:0.8em 56px .8em 27px; 
}
.button-p {
  font: bold 11px Arial;
  text-decoration: none;
  background-color: #EEEEEE;
  color: #333333 !important;
  padding: 2px 6px 2px 6px;
  border: 1px solid #cccccc;

}
[data-ml-modal] .modal-content {background:#fff; padding:23px 27px;}
 
    .modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .button-p {
        background-color:  #EEEEEE;
        color: white;
        padding: 14px 20px;
        margin: 8px 0;
        border: none;
        cursor: pointer;
    }
    .button-p:hover {
        opacity: 0.8;
    }
</style>
<div class="wrap">
    <h1>faraz Telegram Plugin</h1>
    <p><a href="#modal-10" class="button-p" id="add-new-item">Add an item</a></p>
    <form method="post">
        <div data-ml-modal id="modal-10">
            <a href="#!" class="modal-overlay"></a>
            <div class="modal-dialog modal-dialog-lg">
                <h3 id="modal-title">Add an item</h3>
                <div class="modal-content newspaper">
                    <input type="text" name="stp_input" id="stp-input" placeholder="Enter RSS URL">
                    <input type="text" name="rss_fetcher_type" id="rss_fetcher_type" placeholder="Enter Type (e.g., div)">
                    <input type="text" name="rss_fetcher_class" id="rss_fetcher_class" placeholder="Enter Class (e.g., article-content)">
                    
                    <select name="category_id" id="category-select">
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <br><hr>
                    <input type="hidden" name="entry_index" id="entry_index" value="">
                    <button type="submit" name="stp_add_entry" id="save-button">Add to Table</button>
                    <a href="#!" class="modal-close button-p">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<table id="stp-table" style="width: 100%; margin-top: 20px; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="border: 1px solid #000; padding: 8px;">RSS URL</th>
            <th style="border: 1px solid #000; padding: 8px;">Channel Title</th>
            <th style="border: 1px solid #000; padding: 8px;">Channel Description</th>
            <th style="border: 1px solid #000; padding: 8px;">Type</th>
            <th style="border: 1px solid #000; padding: 8px;">Class</th>
            <th style="border: 1px solid #000; padding: 8px;">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entries as $index => $entry) : ?>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo esc_html($entry['url']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo get_cat_name(esc_html($entry['channel_title'])); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo esc_html($entry['channel_description']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo esc_html($entry['type']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo esc_html($entry['class']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <a href="#modal-10" class="button-p edit-entry" data-index="<?php echo $index; ?>">Edit</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="entry_index" value="<?php echo $index; ?>">
                        <button type="submit" name="stp_delete_entry">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br><br>
<form method="post">
    <button type="submit" name="stp_submit_entries">Submit</button>
</form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-entry').forEach(function(button) {
        button.addEventListener('click', function() {
            var index = this.getAttribute('data-index');
            var row = this.closest('tr');

            document.getElementById('stp-input').value = row.cells[0].textContent.trim();
            document.getElementById('rss_fetcher_type').value = row.cells[3].textContent.trim();
            document.getElementById('rss_fetcher_class').value = row.cells[4].textContent.trim();
            document.getElementById('category-select').value = row.cells[1].getAttribute('data-category-id');
            document.getElementById('entry_index').value = index;
            document.getElementById('save-button').textContent = 'Update';
            document.getElementById('modal-title').textContent = 'Edit Item';
        });
    });

    document.getElementById('add-new-item').addEventListener('click', function() {
        document.getElementById('stp-input').value = '';
        document.getElementById('rss_fetcher_type').value = '';
        document.getElementById('rss_fetcher_class').value = '';
        document.getElementById('category-select').selectedIndex = 0;
        document.getElementById('entry_index').value = '';
        document.getElementById('save-button').textContent = 'Add to Table';
        document.getElementById('modal-title').textContent = 'Add an Item';
    });
});
</script>
<?php
}

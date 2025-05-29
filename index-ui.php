<?php

function stp_render_page() { 
    // Get current tab
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : '';

    // If we're on signature tab, show signature settings
    if ($current_tab === 'signature') {
        farazautur_signature_page();
        return;
    }

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
        }
    }
?>
<style>
/* Reset and base styles */
.wrap {
    font-family: 'IRANSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

/* Tab styles */
.nav-tab-wrapper {
    border-bottom: 1px solid #ccc;
    margin: 0;
    padding-top: 9px;
    padding-bottom: 0;
    line-height: inherit;
    direction: rtl;
    margin-bottom: 20px;
}

.nav-tab {
    float: right;
    border: 1px solid #ccc;
    border-bottom: none;
    margin-right: .5em;
    padding: 5px 10px;
    font-size: 14px;
    line-height: 24px;
    background: #e5e5e5;
    color: #555;
    text-decoration: none;
}

.nav-tab:hover,
.nav-tab:focus {
    background-color: #fff;
    color: #444;
}

.nav-tab-active,
.nav-tab-active:focus,
.nav-tab-active:focus:active,
.nav-tab-active:hover {
    border-bottom: 1px solid #fff;
    background: #fff;
    color: #000;
}

/* Add New Item button style */
#add-new-item {
    background: #3498db;
    color: white;
    padding: 12px 25px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 20px;
    transition: background 0.3s ease;
}

#add-new-item:hover {
    background: #2980b9;
}

h1 {
    color: #2c3e50;
    font-size: 2.2em;
    margin-bottom: 30px;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
    display: inline-block;
}

/* Modal styles */
[data-ml-modal] {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    direction: rtl;
}

[data-ml-modal]:target {
    opacity: 1;
    visibility: visible;
}

.modal-dialog {
    background: #fff;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    margin: 40px auto;
    position: relative;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.modal-dialog h3 {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 1.5em;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.modal-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Form styles in modal */
.modal-content input[type="text"],
.modal-content select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    text-align: right;
}

.modal-content input[type="text"]:focus,
.modal-content select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    outline: none;
}

.modal-content input[type="text"]::placeholder {
    color: #95a5a6;
    text-align: right;
}

.modal-content select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a0aec0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: left 1rem center;
    background-size: 1em;
    padding-left: 2.5rem;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    justify-content: flex-start;
}

.button-group button[type="submit"],
.button-group .modal-close {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    min-width: 120px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button-group button[type="submit"] {
    background: #3498db;
    color: white;
    border: none;
}

.button-group button[type="submit"]:hover {
    background: #2980b9;
}

.button-group .modal-close {
    background: #e74c3c;
    color: white;
    text-decoration: none;
}

.button-group .modal-close:hover {
    background: #c0392b;
}

/* Table styles */
#stp-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 30px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    table-layout: fixed;
}

#stp-table th {
    background: #3498db;
    color: white;
    font-weight: 500;
    text-align: center;
    padding: 15px;
    white-space: nowrap;
}

#stp-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
    text-align: center;
    vertical-align: middle;
}

#stp-table tr:last-child td {
    border-bottom: none;
}

#stp-table tr:hover {
    background: #f8f9fa;
}

/* Column widths */
#stp-table th:nth-child(1), 
#stp-table td:nth-child(1) { /* RSS آدرس */
    width: 25%;
}

#stp-table th:nth-child(2),
#stp-table td:nth-child(2) { /* عنوان کانال */
    width: 15%;
}

#stp-table th:nth-child(3),
#stp-table td:nth-child(3) { /* توضیحات کانال */
    width: 20%;
}

#stp-table th:nth-child(4),
#stp-table td:nth-child(4) { /* نوع */
    width: 10%;
}

#stp-table th:nth-child(5),
#stp-table td:nth-child(5) { /* کلاس */
    width: 15%;
}

#stp-table th:nth-child(6),
#stp-table td:nth-child(6) { /* عملیات */
    width: 15%;
}

/* Action buttons in table */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    padding: 5px;
}

.button-p {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s ease;
    color: white;
    min-width: 70px;
    text-align: center;
}

.edit-entry {
    background: #2ecc71;
}

.edit-entry:hover {
    background: #27ae60;
}

.delete-btn {
    background: #e74c3c;
}

.delete-btn:hover {
    background: #c0392b;
}

/* Remove any form styles that might interfere */
.action-buttons form {
    margin: 0;
    padding: 0;
    display: inline;
}

/* Submit button at bottom */
button[name="stp_submit_entries"] {
    margin-top: 20px;
    background: #9b59b6;
    padding: 12px 30px;
    font-size: 16px;
}

button[name="stp_submit_entries"]:hover {
    background: #8e44ad;
}

/* Loading indicator */
.loading {
    display: none;
    margin-left: 10px;
    color: #3498db;
}

/* Success message */
.success-message {
    display: none;
    background: #2ecc71;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    margin-top: 20px;
}

/* Error message */
.error-message {
    display: none;
    background: #e74c3c;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    margin-top: 20px;
}

/* Delete confirmation modal styles */
.delete-confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.delete-confirm-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transform: translateY(-20px);
    animation: slideIn 0.3s ease forwards;
}

@keyframes slideIn {
    to {
        transform: translateY(0);
    }
}

.delete-confirm-content h3 {
    color: #e74c3c;
    margin: 0 0 20px 0;
    font-size: 1.5em;
}

.delete-confirm-content p {
    color: #2c3e50;
    margin-bottom: 25px;
    line-height: 1.6;
}

.delete-confirm-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.delete-confirm-buttons button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s ease;
}

.confirm-delete-btn {
    background: #e74c3c;
    color: white;
}

.confirm-delete-btn:hover {
    background: #c0392b;
}

.cancel-delete-btn {
    background: #95a5a6;
    color: white;
}

.cancel-delete-btn:hover {
    background: #7f8c8d;
}
</style>
<div class="wrap">
    <h1>پلاگین تلگرام فراز</h1>
    
    <!-- Add tabs navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="?page=faraz-telegram-plugin" class="nav-tab <?php echo empty($current_tab) ? 'nav-tab-active' : ''; ?>">تنظیمات اصلی</a>
        <a href="?page=faraz-telegram-plugin&tab=signature" class="nav-tab <?php echo $current_tab === 'signature' ? 'nav-tab-active' : ''; ?>">تنظیمات امضا</a>
    </h2>

    <?php if (empty($current_tab)): // Only show main content on main tab ?>
    <a href="#add-new-modal" id="add-new-item" class="button">افزودن آیتم جدید</a>
    <form method="post">
        <div data-ml-modal id="modal-10">
            <a href="#!" class="modal-overlay"></a>
            <div class="modal-dialog">
                <h3 id="modal-title">افزودن آیتم جدید</h3>
                <div class="modal-content">
                    <input type="text" name="stp_input" id="stp-input" placeholder="آدرس RSS را وارد کنید">
                    <input type="text" name="rss_fetcher_type" id="rss_fetcher_type" placeholder="نوع را وارد کنید (مثال: div)">
                    <input type="text" name="rss_fetcher_class" id="rss_fetcher_class" placeholder="کلاس را وارد کنید (مثال: article-content)">
                    
                    <select name="category_id" id="category-select">
                        <option value="">دسته‌بندی را انتخاب کنید</option>
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="hidden" name="entry_index" id="entry_index" value="">
                    <div class="button-group" style="margin-top: 20px;">
                        <button type="submit" name="stp_add_entry" id="save-button">افزودن به جدول</button>
                        <a href="#!" class="modal-close button-p">انصراف</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <table id="stp-table">
        <thead>
            <tr>
                <th>آدرس RSS</th>
                <th>عنوان کانال</th>
                <th>توضیحات کانال</th>
                <th>نوع</th>
                <th>کلاس</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $index => $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry['url']); ?></td>
                    <td><?php echo get_cat_name(esc_html($entry['channel_title'])); ?></td>
                    <td><?php echo esc_html($entry['channel_description']); ?></td>
                    <td><?php echo esc_html($entry['type']); ?></td>
                    <td><?php echo esc_html($entry['class']); ?></td>
                    <td class="action-buttons">
                        <a href="#modal-10" class="button-p edit-entry" data-index="<?php echo $index; ?>">ویرایش</a>
                        <button type="button" class="button-p delete-btn" style="background: #e74c3c;" onclick="showDeleteConfirmation(<?php echo $index; ?>)">حذف</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="success-message">عملیات با موفقیت انجام شد!</div>
    <div class="error-message">خطا در انجام عملیات!</div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-confirm-modal" id="delete-confirm-modal">
    <div class="delete-confirm-content">
        <h3>تأیید حذف</h3>
        <p>آیا از حذف این مورد اطمینان دارید؟</p>
        <div class="delete-confirm-buttons">
            <form method="post" id="delete-form">
                <input type="hidden" name="entry_index" id="delete-entry-index" value="">
                <button type="submit" name="stp_delete_entry" class="confirm-delete-btn">بله، حذف شود</button>
            </form>
            <button type="button" class="cancel-delete-btn">انصراف</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit button functionality
    document.querySelectorAll('.edit-entry').forEach(function(button) {
        button.addEventListener('click', function() {
            var index = this.getAttribute('data-index');
            var row = this.closest('tr');

            document.getElementById('stp-input').value = row.cells[0].textContent.trim();
            document.getElementById('rss_fetcher_type').value = row.cells[3].textContent.trim();
            document.getElementById('rss_fetcher_class').value = row.cells[4].textContent.trim();
            
            // Find and select the category in dropdown
            var categoryName = row.cells[1].textContent.trim();
            var categorySelect = document.getElementById('category-select');
            for(var i = 0; i < categorySelect.options.length; i++) {
                if(categorySelect.options[i].text === categoryName) {
                    categorySelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('entry_index').value = index;
            document.getElementById('save-button').textContent = 'به‌روزرسانی';
            document.getElementById('modal-title').textContent = 'ویرایش آیتم';
        });
    });

    // Add new item button functionality
    document.getElementById('add-new-item').addEventListener('click', function() {
        document.getElementById('stp-input').value = '';
        document.getElementById('rss_fetcher_type').value = '';
        document.getElementById('rss_fetcher_class').value = '';
        document.getElementById('category-select').selectedIndex = 0;
        document.getElementById('entry_index').value = '';
        document.getElementById('save-button').textContent = 'افزودن به جدول';
        document.getElementById('modal-title').textContent = 'افزودن آیتم جدید';
    });

    // Form submission handling
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            document.querySelector('.loading').style.display = 'inline-block';
        });
    });

    // Success/Error message handling
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST') : ?>
        var successMessage = document.querySelector('.success-message');
        var errorMessage = document.querySelector('.error-message');
        
        <?php if (isset($_POST['stp_add_entry']) || isset($_POST['stp_delete_entry'])) : ?>
            successMessage.style.display = 'block';
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 3000);
        <?php else : ?>
            errorMessage.style.display = 'block';
            setTimeout(function() {
                errorMessage.style.display = 'none';
            }, 3000);
        <?php endif; ?>
    <?php endif; ?>

    // Delete confirmation handling
    const deleteModal = document.getElementById('delete-confirm-modal');
    const deleteForm = document.getElementById('delete-form');
    const deleteEntryIndex = document.getElementById('delete-entry-index');

    window.showDeleteConfirmation = function(index) {
        deleteEntryIndex.value = index;
        deleteModal.style.display = 'flex';
    };

    document.querySelector('.cancel-delete-btn').addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });

    // Close modal when clicking outside
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
            deleteModal.style.display = 'none';
        }
    });
});
</script>
<?php
}

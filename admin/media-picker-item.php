<?php
/**
 * Media Picker for sub-items (dishes/features) - Upload new file
 * Usage: <?php include 'media-picker-item.php'; ?> with $itemImage var
 */
?>
<div class="form-group media-picker-item">
    <label>Image</label>
    <input type="hidden" name="item_image[]" value="<?php echo htmlspecialchars($itemImage); ?>">
    <?php if (!empty($itemImage)): ?>
        <div class="current-image-wrap">
            <img src="<?php echo htmlspecialchars($itemImage); ?>" alt="Current" style="max-width:200px; border-radius:8px;">
            <p class="current-filename">Current: <?php echo htmlspecialchars(basename($itemImage)); ?></p>
            <p>To keep, leave empty. To replace, upload new below.</p>
    <button type="button" class="btn btn-primary item-upload-btn" data-target="item-image-file">Replace</button>
        </div>
    <?php else: ?>
        <p class="image-option-hint">Upload image for this item.</p>
        <button type="button" class="btn btn-primary item-upload-btn" data-target="item-image-file">Upload Image</button>
    <?php endif; ?>
    <input type="file" class="item-image-file" name="item_image_file[]" accept="image/*" style="display:none;">

    <div class="item-image-preview" style="margin-top:10px;"></div>
    <small>Optional. JPG/PNG max 5MB. Uploads on save.</small>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup upload buttons
    document.querySelectorAll('.item-upload-btn').forEach(btn => {
        btn.onclick = function() {
            const targetId = this.dataset.target;
            const input = this.parentElement.querySelector('.item-image-file');
            input.click();
        };
    });
    
    // Preview on change
    document.querySelectorAll('.media-picker-item .item-image-file').forEach(input => {
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    input.closest('.media-picker-item').querySelector('.item-image-preview').innerHTML = `<img src="${ev.target.result}" style="max-width:200px;"> Selected: ${file.name}`;
                };
                reader.readAsDataURL(file);
            }
        };
    });
    
    // Rebind for dynamic items
    document.addEventListener('itemAdded', function(e) {
        const newItem = e.target;
        const newBtn = newItem.querySelector('.item-upload-btn');
        const newInput = newItem.querySelector('.item-image-file');
        if (newBtn && newInput) {
            newBtn.onclick = function() {
                newInput.click();
            };
            newInput.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        newInput.closest('.media-picker-item').querySelector('.item-image-preview').innerHTML = `<img src="${ev.target.result}" style="max-width:200px;"> Selected: ${file.name}`;
                    };
                    reader.readAsDataURL(file);
                }
            };
        }
    });
});
</script>

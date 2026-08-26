<?php
/**
 * Media Picker - Select from DB media_files or upload new (optional)
 * For use in add-*.php modules
 * DB: tagum_media.media_files (id, name, type, file_path)
 */
?>
<div class="form-group media-picker">
    <label>Image</label>
    <input type="hidden" name="image" value="<?php echo htmlspecialchars($storedImage ?? $image ?? ''); ?>">
    <?php if (!empty($image)): ?>
        <div class="current-image-wrap">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Current" style="max-width:200px; border-radius:8px; display:block;" loading="lazy">
            <p class="current-filename">Current file: <?php echo htmlspecialchars(basename($image)); ?></p>
            <p class="image-option-hint">To <strong>keep</strong> this image, leave the file field empty and save. To <strong>replace</strong> it, choose a new file below.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('image-file').click();">Replace with new image</button>
        </div>
    <?php else: ?>
        <p class="image-option-hint">Upload an image for this destination.</p>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('image-file').click();">Choose image</button>
    <?php endif; ?>
    <input type="file" id="image-file" name="image_file" accept="image/*" style="display:none;" <?php echo !$image ? 'required' : ''; ?>>
    <div id="image-preview" style="margin-top:10px;"></div>
    <small>Optional. Uploads when you click Save.</small>
</div>

<script>
document.getElementById('image-file').onchange = function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('image-preview').innerHTML = `<img src="${ev.target.result}" style="max-width:200px;" loading="lazy"> Selected: ${file.name}`;
        };
        reader.readAsDataURL(file);
    }
};
</script>

<script>
function updateMediaPath(select) {
    const option = select.selectedOptions[0];
    const path = option.dataset.path;
    const preview = option.dataset.preview;
    
    if (path) {
        document.getElementById('media-preview').src = preview;
        document.getElementById('media-preview').style.display = 'block';
        document.getElementById('media-path').textContent = path;
        document.getElementById('selected-media-path').value = path;
    } else {
        document.getElementById('media-preview').style.display = 'none';
        document.getElementById('media-path').textContent = '';
        document.getElementById('selected-media-path').value = '';
    }
}

function handleLocalUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('media-preview').src = e.target.result;
            document.getElementById('media-preview').style.display = 'block';
            document.getElementById('media-path').textContent = file.name;
            document.getElementById('selected-media-path').value = file.name;
        };
        reader.readAsDataURL(file);
    }
}


// Handle return from media-manager
<?php if (isset($_GET['new_media_id'])): ?>
document.querySelector('select[name="media_id"]').value = '<?php echo $_GET['new_media_id']; ?>';
updateMediaPath(document.querySelector('select[name="media_id"]'));
<?php endif; ?>
</script>

<style>
.media-picker .media-preview {
    background: var(--light-gray);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}
</style>

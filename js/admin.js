// Admin JavaScript - 300 char limit on descriptions
document.addEventListener('DOMContentLoaded', function() {
    // Description textarea 300 char limit
    const descriptionTextareas = document.querySelectorAll('textarea[name="description"]');
    descriptionTextareas.forEach(textarea => {
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.style.cssText = 'display: block; color: var(--gray); font-size: 0.8rem; text-align: right; margin-top: 0.25rem;';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = `${len}/300 characters`;
            counter.style.color = len > 300 ? 'var(--error)' : 'var(--gray)';
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
        
        textarea.maxLength = 300;
    });
    
    // Image optional in edit mode (preserve existing) - FIXED browser warning
    const imageInputs = document.querySelectorAll('input[name="image_file"]');
    imageInputs.forEach(input => {
        input.required = false; // Always optional - PHP handles validation
    });
    
    // Form row balance
    const formRows = document.querySelectorAll('.form-row');
    formRows.forEach(row => {
        const groups = row.querySelectorAll('.form-group');
        if (groups.length === 2) {
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr 1fr';
            row.style.gap = '1rem';
        }
    });
    
    // Checkbox styling
    const checkboxes = document.querySelectorAll('.form-group.checkbox input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.style.marginRight = '0.5rem';
        cb.parentNode.style.display = 'flex';
        cb.parentNode.style.alignItems = 'center';
    });
});


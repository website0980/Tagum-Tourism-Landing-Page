    // Upload progress tracking
    const uploadForm = document.querySelector('form[method="POST"][enctype="multipart/form-data"]');
    const uploadProgress = document.getElementById("uploadProgress");
    const progressBar = document.getElementById("progressBar");
    const progressText = document.getElementById("progressText");
    const uploadStatus = document.getElementById("uploadStatus");

    if (uploadForm && uploadProgress) {
        uploadForm.addEventListener("submit", function(e) {
            const fileInput = uploadForm.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) return;

            e.preventDefault();
            uploadProgress.style.display = "block";
            progressBar.style.width = "0%";
            progressText.textContent = "0%";
            uploadStatus.textContent = "Uploading " + fileInput.files.length + " image(s)...";

            // Simulate progress (since PHP doesn't support real progress without extensions)
            let progress = 0;
            const totalFiles = fileInput.files.length;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 95) {
                    progress = 95;
                    clearInterval(interval);
                }
                progressBar.style.width = progress + "%";
                progressText.textContent = Math.round(progress) + "%";
            }, 200);

            // Submit form after showing progress
            setTimeout(() => {
                uploadForm.submit();
            }, 500);
        });
    }

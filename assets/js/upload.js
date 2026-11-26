/**
 * Client-Side Upload Manager
 * Handles Drag-and-Drop, Multi-file previews, validation, and submission
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('photoInput');
    const previewGrid = document.getElementById('previewGrid');
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const uploadAlert = document.getElementById('uploadAlert');
    const fileCountBadge = document.getElementById('fileCountBadge');

    if (!uploadForm || !fileInput) return;

    // In-memory array of selected File objects
    let selectedFiles = [];
    const MAX_FILES = 10;
    const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    // Format bytes helper
    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Show error message banner
    function showAlert(msg, type = 'danger') {
        if (!uploadAlert) return;
        uploadAlert.className = `alert alert-${type} shadow-sm`;
        uploadAlert.innerHTML = `<i class="fa-solid fa-circle-exclamation me-2"></i> ${msg}`;
        uploadAlert.classList.remove('d-none');
        uploadAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideAlert() {
        if (uploadAlert) uploadAlert.classList.add('d-none');
    }

    // Trigger file dialog on dropzone click
    if (dropzone) {
        dropzone.addEventListener('click', () => fileInput.click());

        // Drag and drop events
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFilesSelection(files);
        });
    }

    // File input change handler
    fileInput.addEventListener('change', () => {
        handleFilesSelection(fileInput.files);
    });

    // Process newly selected files
    function handleFilesSelection(fileList) {
        hideAlert();
        const incoming = Array.from(fileList);

        if (selectedFiles.length + incoming.length > MAX_FILES) {
            showAlert(`You can only upload a maximum of ${MAX_FILES} photos per submission.`);
            return;
        }

        for (const file of incoming) {
            // Check file type
            if (!ALLOWED_TYPES.includes(file.type)) {
                showAlert(`"${file.name}" is not an accepted format. Only JPEG, PNG, and WebP are allowed.`);
                continue;
            }

            // Check file size
            if (file.size > MAX_SIZE_BYTES) {
                showAlert(`"${file.name}" exceeds the 10 MB maximum size limit.`);
                continue;
            }

            // Avoid exact duplicates in current batch
            const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
            if (!exists) {
                selectedFiles.push(file);
            }
        }

        renderPreviews();
    }

    // Render image preview thumbnails
    function renderPreviews() {
        if (!previewGrid) return;
        previewGrid.innerHTML = '';

        if (fileCountBadge) {
            fileCountBadge.innerText = `${selectedFiles.length} / ${MAX_FILES} photos selected`;
        }

        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'preview-item';

            const img = document.createElement('img');
            img.alt = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn';
            removeBtn.title = 'Remove photo';
            removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeFile(index);
            });

            // Size badge
            const badge = document.createElement('span');
            badge.className = 'file-size-badge';
            badge.innerText = formatSize(file.size);

            item.appendChild(img);
            item.appendChild(removeBtn);
            item.appendChild(badge);
            previewGrid.appendChild(item);
        });
    }

    // Remove single photo from preview
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    // AJAX Submission Handler
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();

        const nameInput = document.getElementById('visitorName');
        const emailInput = document.getElementById('visitorEmail');
        const consentCheckbox = document.getElementById('consentCheckbox');

        if (nameInput && nameInput.value.trim() === '') {
            showAlert('Please enter your full name.');
            nameInput.focus();
            return;
        }

        if (emailInput && emailInput.value.trim() === '') {
            showAlert('Please provide your email address.');
            emailInput.focus();
            return;
        }

        if (consentCheckbox && !consentCheckbox.checked) {
            showAlert('You must explicitly consent to displaying your photos in the public slideshow.');
            consentCheckbox.focus();
            return;
        }

        if (selectedFiles.length === 0) {
            showAlert('Please select at least one photo to upload.');
            return;
        }

        // Prepare FormData
        const formData = new FormData(uploadForm);
        // Remove the default file input payload and append the managed selectedFiles
        formData.delete('photos[]');
        selectedFiles.forEach((file) => {
            formData.append('photos[]', file);
        });

        // Set Loading UI state
        submitBtn.disabled = true;
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Uploading & Processing...';

        try {
            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showAlert(data.message || 'Photos uploaded successfully!', 'success');
                // Store access key / dashboard redirect
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.href = '../visitor/dashboard.php';
                    }
                }, 1200);
            } else {
                const errMessage = (data.errors && Array.isArray(data.errors)) 
                    ? data.errors.join('<br>') 
                    : (data.error || 'Upload failed. Please check the requirements.');
                showAlert(errMessage, 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        } catch (err) {
            console.error('Upload Error:', err);
            showAlert('An unexpected network or server error occurred. Please try again.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    });
});

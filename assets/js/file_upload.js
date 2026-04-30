/* file_upload.js - File Upload Page Scripts */

function showFilePreview(input) {
    if (input.files.length > 0) {
        const file = input.files[0];
        const previewName = document.getElementById('previewName');
        const previewSize = document.getElementById('previewSize');
        const fileTitleInput = document.getElementById('fileTitleInput');
        const fileTypeInput = document.getElementById('fileTypeInput');
        const filePreview = document.getElementById('filePreview');

        if (previewName) {
            previewName.textContent = file.name;
        }
        if (fileTitleInput) {
            fileTitleInput.value = file.name;
        }

        // Determine type from extension
        const ext = file.name.split('.').pop().toLowerCase();
        const typeMap = {
            pdf: 'PDF',
            csv: 'CSV',
            xlsx: 'Dataset',
            docx: 'Report',
            png: 'Image',
            jpg: 'Image',
            jpeg: 'Image',
            zip: 'Archive'
        };
        if (fileTypeInput) {
            fileTypeInput.value = typeMap[ext] || 'Other';
        }

        // Format size
        const bytes = file.size;
        let size = '';
        if (bytes >= 1073741824) {
            size = (bytes / 1073741824).toFixed(1) + ' GB';
        } else if (bytes >= 1048576) {
            size = (bytes / 1048576).toFixed(1) + ' MB';
        } else {
            size = (bytes / 1024).toFixed(1) + ' KB';
        }
        if (previewSize) {
            previewSize.textContent = size;
        }

        if (filePreview) {
            filePreview.style.display = 'block';
        }
    }
}

// Drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');

    if (dropZone && fileInput) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (ev) => {
                ev.preventDefault();
                dropZone.style.borderColor = '#c5a022';
                dropZone.style.background = '#fffdf5';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (ev) => {
                ev.preventDefault();
                dropZone.style.borderColor = '#ddd';
                dropZone.style.background = '#fdfcf8';
            });
        });

        dropZone.addEventListener('drop', (ev) => {
            ev.preventDefault();
            fileInput.files = ev.dataTransfer.files;
            showFilePreview(fileInput);
        });
    }
});

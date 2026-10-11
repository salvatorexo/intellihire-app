/* =========================================================
   IntelliHire - CV Upload
   Validates file type & size, uploads via AJAX
   ========================================================= */

const dropZone    = document.getElementById('dropZone');
const cvInput     = document.getElementById('cvInput');
const fileInfo    = document.getElementById('fileInfo');
const fileName    = document.getElementById('fileName');
const fileSize    = document.getElementById('fileSize');
const progressBar = document.getElementById('progressBar');
const progressFill = document.getElementById('progressFill');
const uploadBtn   = document.getElementById('uploadBtn');
const cvForm      = document.getElementById('cvForm');
const alertBox    = document.getElementById('alertBox');
const currentCV   = document.getElementById('currentCV');
const currentCVInfo = document.getElementById('currentCVInfo');

/* ---------- Config ---------- */
const ALLOWED_TYPES = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
const ALLOWED_EXTS = ['pdf', 'docx'];
const MAX_SIZE_MB = 5;
const MAX_SIZE = MAX_SIZE_MB * 1024 * 1024;

let selectedFile = null;

/* ---------- Sidebar ---------- */
document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

/* ---------- Alert ---------- */
function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ---------- Load Current CV ---------- */
async function loadCurrentCV() {
    try {
        const res = await fetch('../php/get-cv.php');
        const data = await res.json();
        if (data.success && data.cv) {
            currentCV.style.display = 'block';
            currentCVInfo.innerHTML = `
                <strong>${data.cv.file_name}</strong><br>
                <small>Uploaded ${data.cv.uploaded_at}</small>
            `;
            uploadBtn.textContent = 'Replace CV';
        } else {
            currentCV.style.display = 'none';
            uploadBtn.textContent = 'Upload CV';
        }
    } catch (e) {
        console.error(e);
    }
}

/* ---------- Drag & Drop ---------- */
dropZone.addEventListener('click', () => cvInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        handleFile(e.dataTransfer.files[0]);
    }
});

cvInput.addEventListener('change', (e) => {
    if (e.target.files.length) handleFile(e.target.files[0]);
});

/* ---------- Validate & Preview ---------- */
function handleFile(file) {
    alertBox.style.display = 'none';
    const ext = file.name.split('.').pop().toLowerCase();

    if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
        showAlert('Only PDF and DOCX files are allowed.');
        resetFile();
        return;
    }
    if (file.size > MAX_SIZE) {
        showAlert(`File too large. Maximum size is ${MAX_SIZE_MB} MB.`);
        resetFile();
        return;
    }

    selectedFile = file;
    fileName.textContent = file.name;
    fileSize.textContent = formatSize(file.size);
    fileInfo.classList.add('visible');
    uploadBtn.disabled = false;
}

function resetFile() {
    selectedFile = null;
    cvInput.value = '';
    fileInfo.classList.remove('visible');
    uploadBtn.disabled = true;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(2) + ' MB';
}

/* ---------- Upload ---------- */
cvForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!selectedFile) return;

    const fd = new FormData();
    fd.append('cv', selectedFile);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../php/upload-cv.php');

    // Progress
    progressBar.classList.add('visible');
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = pct + '%';
        }
    });

    xhr.onload = () => {
        progressBar.classList.remove('visible');
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                showAlert('CV uploaded successfully!', 'success');
                resetFile();
                loadCurrentCV();
            } else {
                showAlert(res.message || 'Upload failed.');
            }
        } catch (err) {
            showAlert('Unexpected response from server.');
        }
    };

    xhr.onerror = () => {
        progressBar.classList.remove('visible');
        showAlert('Network error during upload.');
    };

    xhr.send(fd);
});

loadCurrentCV();
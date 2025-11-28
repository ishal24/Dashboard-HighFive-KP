(function() {
    // Fungsi untuk memeriksa status import
    function checkImportStatus() {
        fetch('/revenue/import-status', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Status completed - import selesai
            if (data.status === 'completed') {
                stopCheckingStatus();
                showImportResult(data.message, 'success', data.error_details);
                reloadDataTable();
            }
            // Status error/failed - import gagal
            else if (data.status === 'error' || data.status === 'failed') {
                stopCheckingStatus();
                showImportResult(data.message, 'error');
            }
            // Jika masih processing, lanjutkan polling
        })
        .catch(error => {
            console.error('Error checking import status:', error);
        });
    }

    // Fungsi untuk menampilkan hasil import
    function showImportResult(message, type, errorDetails = []) {
        // Cek jika ada elemen snackbar di halaman
        let snackbar = document.getElementById('snackbar');

        if (type === 'success') {
            // Tampilkan modal sukses jika ada banyak error details
            if (errorDetails && errorDetails.length > 0) {
                showDetailModal('Import Selesai', message, errorDetails);
            } else {
                // Gunakan snackbar untuk pesan singkat
                if (snackbar) {
                    snackbar.className = `show ${type}`;
                    snackbar.textContent = message;
                    setTimeout(() => {
                        snackbar.className = snackbar.className.replace('show', '');
                    }, 5000);
                } else {
                    alert(message);
                }
            }
        } else {
            // Error message
            if (snackbar) {
                snackbar.className = `show ${type}`;
                snackbar.textContent = message;
                setTimeout(() => {
                    snackbar.className = snackbar.className.replace('show', '');
                }, 5000);
            } else {
                alert('Error: ' + message);
            }
        }
    }

    // Fungsi untuk menampilkan modal dengan detail
    function showDetailModal(title, message, errorDetails) {
        // Cek apakah Bootstrap tersedia
        if (typeof bootstrap !== 'undefined') {
            // Buat modal dinamis jika tidak ada
            let modalElement = document.getElementById('importResultModal');

            if (!modalElement) {
                const modalHTML = `
                <div class="modal fade" id="importResultModal" tabindex="-1" aria-labelledby="importResultModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="importResultModalLabel">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-success" id="importResultMessage">${message}</div>

                                ${errorDetails.length > 0 ? `
                                <div class="alert alert-warning">
                                    <p><strong>Ada beberapa data yang tidak berhasil diimpor:</strong></p>
                                    <div style="max-height: 200px; overflow-y: auto; margin-top: 10px;">
                                        <ul id="importErrorList">
                                            ${errorDetails.map(error => `<li>${error}</li>`).join('')}
                                        </ul>
                                    </div>
                                </div>` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
                `;

                // Tambahkan modal ke DOM
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modalElement = document.getElementById('importResultModal');
            } else {
                // Update konten modal jika sudah ada
                document.getElementById('importResultModalLabel').textContent = title;
                document.getElementById('importResultMessage').textContent = message;

                const errorList = document.getElementById('importErrorList');
                if (errorList) {
                    errorList.innerHTML = errorDetails.map(error => `<li>${error}</li>`).join('');
                }
            }

            // Tampilkan modal
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            // Fallback jika Bootstrap tidak tersedia
            alert(message + '\n\nDetail Error:\n' + errorDetails.join('\n'));
        }
    }

    // Fungsi untuk reload data table
    function reloadDataTable() {
        // Jika halaman memiliki fungsi reload data
        if (typeof window.reloadRevenueData === 'function') {
            window.reloadRevenueData();
        } else {
            // Fallback: reload halaman setelah delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    // Menangani form submit untuk memulai proses import
    function initImportForm() {
        const importForm = document.getElementById('revenueImportForm');

        if (importForm) {
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const fileInput = this.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('Silakan pilih file Excel/CSV terlebih dahulu!');
                    return;
                }

                // Tampilkan loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Mengunggah...';

                // Submit form via AJAX
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Tutup modal setelah berhasil mulai import
                        const modal = document.getElementById('importRevenueModal');
                        if (modal && typeof bootstrap !== 'undefined') {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }

                        // Reset form
                        importForm.reset();
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;

                        // Tampilkan pesan proses
                        showImportResult(data.message, 'info');

                        // Mulai polling status
                        startCheckingStatus();
                    } else {
                        // Tampilkan pesan error
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        showImportResult(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error importing file:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    showImportResult('Terjadi kesalahan saat mengunggah file', 'error');
                });
            });
        }
    }

    // Variable untuk menyimpan interval timer
    let statusCheckInterval = null;

    // Mulai periodic checking
    function startCheckingStatus() {
        // Batalkan interval sebelumnya jika ada
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }

        // Mulai interval baru, cek setiap 5 detik
        statusCheckInterval = setInterval(checkImportStatus, 5000);

        // Juga cek langsung pertama kali
        checkImportStatus();
    }

    // Berhenti checking
    function stopCheckingStatus() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
    }

    // Inisialisasi ketika dokumen ready
    document.addEventListener('DOMContentLoaded', function() {
        initImportForm();
    });
})();
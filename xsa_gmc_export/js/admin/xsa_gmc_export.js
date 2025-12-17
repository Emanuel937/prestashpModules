// File: modules/xsa_gmc_export/js/admin/xsa_gmc_export.js
document.addEventListener('DOMContentLoaded', function() {
    const categoryEl = document.getElementById('category_id');
    const batchSizeEl = document.getElementById('batch_size');
    const progressEl = document.getElementById('progress');
    const reportEl = document.getElementById('report');
    const feedBtnEl = document.getElementById('feed_btn');
    
    const startBtn = document.querySelector('#start');
    const ajaxUrl = categoryEl.dataset.ajaxUrl;

    function runBatch() {
        const categoryId = categoryEl.value;
        const batchSize = parseInt(batchSizeEl.value, 10) || 50;
        let offset = 0;

        progressEl.textContent = 'Starting export...';
        reportEl.innerHTML = '';
        if (feedBtnEl) feedBtnEl.style.display = 'none';

        function nextBatch() {
            const formData = new URLSearchParams();
            formData.append('ajax', 1);
            formData.append('action', 'exportBatch');
            formData.append('category_id', categoryId);
            formData.append('batch_size', batchSize);
            formData.append('offset', offset);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                
                if (!data || typeof data.total_products === 'undefined') {
                    progressEl.textContent = 'Error: invalid response from server.';
                    return;
                }

                offset += data.processed || 0;
                progressEl.textContent = `Processed ${offset} / ${data.total_products} products`;

                if (data.missing && Object.keys(data.missing).length > 0) {
                    Object.entries(data.missing).forEach(([pid, fields]) => {
                        reportEl.innerHTML += `Product ID ${pid} missing: ${fields.join(', ')}<br>`;
                    });
                }

                if (!data.done) {
                    setTimeout(nextBatch, 200);
                } else {
                    progressEl.textContent = `Processed ${offset} / ${data.total_products} products – Export complete`;
                    if (feedBtnEl) {
                        feedBtnEl.href = data.feed_link;
                        feedBtnEl.style.display = 'inline-block';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                progressEl.textContent = 'Error during export. Check console.';
            });
        }

        nextBatch();
    }

    if (startBtn) {
        startBtn.removeAttribute('onclick'); // Remove inline onclick
        startBtn.addEventListener('click', runBatch);
    }
});

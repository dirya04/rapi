<div class="field">
    <label for="photo">Foto bukti kerusakan (JPG, PNG, WEBP; maksimal 10 MB)</label>
    <input
        type="file"
        id="photo"
        name="photo"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        aria-describedby="photo-help"
        required
        onchange="previewComplaintPhoto(this)"
    >
    <small id="photo-help" class="mt-1 block text-xs text-slate-500">Pilih foto yang jelas agar tim PIC / Sarpras dapat segera mengenali komponen yang rusak. Maksimal 10 MB.</small>
    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.76rem; color: #166534; background: #f0fdf4; padding: 6px 10px; border-radius: 6px; border: 1px solid #bbf7d0; margin-top: 6px;">
        <span>⚡ <strong>Kompresi Otomatis:</strong> Foto akan otomatis dikompres ke resolusi optimal agar hemat kuota dan penyimpanan server tanpa mengurangi kejelasan bukti.</span>
    </div>
    <div id="photo-preview-wrap" style="display: none; margin-top: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; flex-wrap: wrap; gap: 6px;">
            <div style="font-size: 0.8rem; font-weight: 600; color: #2d5a43;">📷 Pratinjau Foto yang Dipilih:</div>
            <span id="photo-size-info" style="font-size: 0.74rem; color: #166534; background: #e8f5e9; padding: 2px 8px; border-radius: 4px; font-weight: 600;"></span>
        </div>
        <img id="photo-preview-elem" src="" alt="Pratinjau Bukti Kerusakan" style="max-height: 200px; max-width: 100%; border-radius: 8px; border: 1px solid #ccd8d2; object-fit: contain; background: #fff; padding: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
    </div>
</div>

<script>
    if (typeof previewComplaintPhoto !== 'function') {
        function previewComplaintPhoto(input) {
            const wrap = document.getElementById('photo-preview-wrap');
            const img = document.getElementById('photo-preview-elem');
            const sizeInfo = document.getElementById('photo-size-info');
            if (!wrap || !img) return;

            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (sizeInfo) {
                    const sizeFormatted = file.size > 1024 * 1024 
                        ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' 
                        : (file.size / 1024).toFixed(1) + ' KB';
                    sizeInfo.textContent = 'Ukuran awal: ' + sizeFormatted + ' (akan dikompres)';
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                img.src = '';
                wrap.style.display = 'none';
            }
        }
    }
</script>

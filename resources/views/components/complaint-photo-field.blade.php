<div class="field">
    <label for="photo">Foto bukti kerusakan (JPG, PNG; maksimal 10 MB)</label>
    <input
        type="file"
        id="photo"
        name="photo"
        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
        aria-describedby="photo-help"
        required
        onchange="previewComplaintPhoto(this)"
    >
    <small id="photo-help" class="mt-1 block text-xs text-slate-500">Pilih foto yang jelas agar tim PIC / Sarpras dapat segera mengenali komponen yang rusak. Maksimal 10 MB.</small>
    <div id="photo-preview-wrap" style="display: none; margin-top: 10px;">
        <div style="font-size: 0.8rem; font-weight: 600; color: #2d5a43; margin-bottom: 4px;">📷 Pratinjau Foto yang Dipilih:</div>
        <img id="photo-preview-elem" src="" alt="Pratinjau Bukti Kerusakan" style="max-height: 200px; max-width: 100%; border-radius: 8px; border: 1px solid #ccd8d2; object-fit: contain; background: #fff; padding: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
    </div>
</div>

<script>
    if (typeof previewComplaintPhoto !== 'function') {
        function previewComplaintPhoto(input) {
            const wrap = document.getElementById('photo-preview-wrap');
            const img = document.getElementById('photo-preview-elem');
            if (!wrap || !img) return;

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                img.src = '';
                wrap.style.display = 'none';
            }
        }
    }
</script>

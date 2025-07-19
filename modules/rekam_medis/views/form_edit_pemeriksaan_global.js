// Fungsi global agar inline onclick dapat berjalan
window.tambahkanObatTerpilih = function() {
    const resepField = document.getElementById('resep');
    if (!resepField) return; // Safety check

    // Kumpulkan semua checkbox yang tercentang
    const lines = [];
    document.querySelectorAll('.obat-checkbox:checked').forEach(cb => {
        const nama          = cb.dataset.nama || '';
        const bentukSediaan = cb.dataset.bentukSediaan || '';
        const dosis         = cb.dataset.dosis || '';

        // Format:
        // [nama_obat]     No.X
        //          [bentuk_sediaan] [dosis]
        let text = nama.trim();
        if (text) {
            text += '     No.X';
        }
        const dosisLine = [bentukSediaan, dosis].filter(Boolean).join(' ').trim();
        if (dosisLine) {
            text += '\n         ' + dosisLine;
        }
        if (text) {
            lines.push(text);
        }
    });

    // Jika tidak ada obat yang dipilih, hentikan fungsi
    if (!lines.length) return;

    // Gabungkan dengan resep yang telah ada (jika ada)
    const newValue = lines.join('\n\n');
    if (resepField.value && resepField.value.trim() !== '') {
        resepField.value = resepField.value.trimEnd() + '\n\n' + newValue;
    } else {
        resepField.value = newValue;
    }

    // Sesuaikan tinggi textarea agar sesuai dengan konten
    if (typeof autoResizeTextarea === 'function') {
        autoResizeTextarea(resepField);
    }
    resepField.dispatchEvent(new Event('input'));

    // Tutup modal Daftar Formularium
    const modalElement = document.getElementById('modalDaftarTemplateResep');
    let closed = false;
    if (modalElement) {
        // Bootstrap 5
        try {
            const modalInstance = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(modalElement) : null;
            if (modalInstance) {
                modalInstance.hide();
                closed = true;
            }
        } catch (e) {}
        // Fallback ke jQuery Bootstrap 4
        if (!closed && typeof $ !== 'undefined' && typeof $().modal === 'function') {
            try {
                $('#modalDaftarTemplateResep').modal('hide');
                closed = true;
            } catch (e) {}
        }
        // Trigger event close dan klik tombol close jika ada
        if (!closed) {
            modalElement.dispatchEvent(new Event('hide.bs.modal'));
            const closeBtn = modalElement.querySelector('[data-bs-dismiss="modal"], .btn-close');
            if (closeBtn) closeBtn.click();
        }
        // Paksa hapus backdrop jika masih ada (antifreeze)
        setTimeout(function() {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            // Cek jika modal masih terlihat
            if (modalElement.classList.contains('show')) {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                console.warn('Modal Daftar Formularium dipaksa tutup karena tidak responsif.');
            }
        }, 300);
        if (!closed) {
            // Log jika gagal menutup modal
            console.error('Gagal menutup modal Daftar Formularium');
        }
    }
    // Fokus ke textarea resep dan scroll ke sana
    if (resepField) {
        resepField.focus();
        resepField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

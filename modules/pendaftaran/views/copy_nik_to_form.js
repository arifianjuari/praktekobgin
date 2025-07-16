// copy_nik_to_form.js
// Script untuk menyalin NIK dari daftar pasien ke form pendaftaran pasien

document.addEventListener('DOMContentLoaded', function() {
    // Temukan semua tombol salin NIK
    const copyButtons = document.querySelectorAll('.copy-nik-btn');

    // Tambahkan event listener ke setiap tombol
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Ambil NIK dari atribut data
            const nik = this.getAttribute('data-nik');

            // Temukan input NIK di form
            const nikInput = document.getElementById('no_ktp');

            // Isi nilai NIK ke input
            if (nikInput) {
                nikInput.value = nik;

                // Trigger event input untuk memicu pencarian data pasien
                const inputEvent = new Event('input', { bubbles: true });
                nikInput.dispatchEvent(inputEvent);

                // Scroll ke form pendaftaran
                const formHeader = document.querySelector('.card-header.bg-primary');
                if (formHeader) {
                    formHeader.scrollIntoView({ behavior: 'smooth' });
                }

                // Berikan feedback visual
                nikInput.classList.add('bg-success', 'text-white');
                setTimeout(() => {
                    nikInput.classList.remove('bg-success', 'text-white');
                }, 1000);
            }
        });
    });
});

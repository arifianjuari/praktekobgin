// Robust autoresize for all relevant textarea fields after page load
window.addEventListener('DOMContentLoaded', function() {
    const textareaIds = [
        'riwayat_sekarang', // RPS
        'ultrasonografi',   // Ultrasonografi
        'laboratorium',     // Laboratorium
        'diagnosis',        // Diagnosis
        'tatalaksana',      // Tatalaksana
        'edukasi',          // Edukasi
        'resume',           // Resume
        'resep'             // Resep
    ];
    textareaIds.forEach(function(id) {
        var ta = document.getElementById(id);
        if (ta) {
            // Immediate resize
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
            // Delayed resize in case browser hasn't rendered all content yet
            setTimeout(function() {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            }, 100);
            setTimeout(function() {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            }, 300);
        }
    });
});

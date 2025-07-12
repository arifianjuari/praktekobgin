/**
 * Script pembantu untuk fitur Status Obstetri
 * Ini adalah versi produksi yang menggantikan fungsi debug dengan console.log biasa
 */

// Fungsi untuk menggantikan debugStatusObstetri
function debugStatusObstetri(message) {
    // Hanya log ke konsol tanpa menampilkan di UI
    console.log('[Status Obstetri] ' + message);
}

// Override fungsi lama jika ada
if (window.debugStatusObstetri) {
    console.log('Replacing debug function with production version');
}

console.log('Status Obstetri Helper loaded - debug mode off'); 
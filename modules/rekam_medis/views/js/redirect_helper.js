/**
 * Helper function to ensure no_rawat parameter is properly passed when navigating between pages
 * This will be included in the form_penilaian_medis_ralan_kandungan.php page
 */

// Function to ensure no_rawat parameter is included in all links to CRUD forms
function addNoRawatToLinks() {
    // Get current no_rawat from URL
    const urlParams = new URLSearchParams(window.location.search);
    const no_rawat = urlParams.get('no_rawat');
    
    if (no_rawat) {
        // Add no_rawat to all links to status obstetri, status ginekologi, and riwayat kehamilan forms
        const targetLinks = document.querySelectorAll('a[href*="tambah_status_obstetri"], a[href*="edit_status_obstetri"], a[href*="tambah_status_ginekologi"], a[href*="edit_status_ginekologi"], a[href*="tambah_riwayat_kehamilan"], a[href*="edit_riwayat_kehamilan"]');
        
        targetLinks.forEach(link => {
            const href = link.getAttribute('href');
            const linkUrl = new URL(href, window.location.origin);
            const linkParams = new URLSearchParams(linkUrl.search);
            
            // Add source parameter to indicate we're coming from form_penilaian_medis_ralan_kandungan
            linkParams.set('source', 'form_penilaian_medis_ralan_kandungan');
            
            // Add no_rawat parameter
            linkParams.set('no_rawat', no_rawat);
            
            // Update the link's href
            linkUrl.search = linkParams.toString();
            link.setAttribute('href', linkUrl.toString().replace(window.location.origin, ''));
        });
        
        // Also add no_rawat to delete links
        const deleteLinks = document.querySelectorAll('a[href*="hapus_status_obstetri"], a[href*="hapus_status_ginekologi"], a[href*="hapus_riwayat_kehamilan"]');
        
        deleteLinks.forEach(link => {
            const href = link.getAttribute('href');
            const linkUrl = new URL(href, window.location.origin);
            const linkParams = new URLSearchParams(linkUrl.search);
            
            // Add source parameter
            linkParams.set('source', 'form_penilaian_medis_ralan_kandungan');
            
            // Add no_rawat parameter
            linkParams.set('no_rawat', no_rawat);
            
            // Update the link's href
            linkUrl.search = linkParams.toString();
            link.setAttribute('href', linkUrl.toString().replace(window.location.origin, ''));
        });
    }
}

// Run the function when the page loads
document.addEventListener('DOMContentLoaded', addNoRawatToLinks);

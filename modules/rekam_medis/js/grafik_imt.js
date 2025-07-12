// Fungsi untuk grafik IMT
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi variabel grafik
    let grafikIMT = null;
    
    // Data batas untuk setiap kategori IMT
    const batasIMT = {
        'IMT < 18.5': {
            batasBawah: [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.5, 13, 13.5, 14, 14.5, 15, 15.5, 16, 16.5, 17, 17.5, 18, 18, 18],
            batasAtas: [0, 0.7, 1.4, 2.1, 2.8, 3.5, 4.2, 4.9, 5.6, 6.3, 7, 7.7, 8.4, 9.1, 9.8, 10.5, 11.2, 11.9, 12.6, 13.3, 14, 14.7, 15.4, 16.1, 16.8, 17.5, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18]
        },
        'IMT 18.5 - 24.9': {
            batasBawah: [0, 0.4, 0.8, 1.2, 1.6, 2, 2.4, 2.8, 3.2, 3.6, 4, 4.4, 4.8, 5.2, 5.6, 6, 6.4, 6.8, 7.2, 7.6, 8, 8.4, 8.8, 9.2, 9.6, 10, 10.4, 10.8, 11.2, 11.6, 12, 12.4, 12.8, 13.2, 13.6, 14, 14.4, 14.8, 15.2, 15.6, 16, 16],
            batasAtas: [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.5, 13, 13.5, 14, 14.5, 15, 15.5, 16, 16, 16, 16, 16, 16, 16, 16, 16, 16]
        },
        'IMT 25 - 29.9': {
            batasBawah: [0, 0.2, 0.4, 0.6, 0.8, 1, 1.2, 1.4, 1.6, 1.8, 2, 2.2, 2.4, 2.6, 2.8, 3, 3.2, 3.4, 3.6, 3.8, 4, 4.2, 4.4, 4.6, 4.8, 5, 5.2, 5.4, 5.6, 5.8, 6, 6.2, 6.4, 6.6, 6.8, 7, 7.2, 7.4, 7.6, 7.8, 8, 8.2, 8.4, 8.6, 8.8, 9, 9.2, 9.4, 9.6, 9.8, 10, 10.2, 10.4, 10.6, 10.8, 11, 11.2, 11.4, 11.5, 11.5],
            batasAtas: [0, 0.3, 0.6, 0.9, 1.2, 1.5, 1.8, 2.1, 2.4, 2.7, 3, 3.3, 3.6, 3.9, 4.2, 4.5, 4.8, 5.1, 5.4, 5.7, 6, 6.3, 6.6, 6.9, 7.2, 7.5, 7.8, 8.1, 8.4, 8.7, 9, 9.3, 9.6, 9.9, 10.2, 10.5, 10.8, 11.1, 11.4, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5, 11.5]
        },
        'IMT > 30': {
            batasBawah: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 4, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 7, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 9, 9],
            batasAtas: [0, 0.2, 0.4, 0.6, 0.8, 1, 1.2, 1.4, 1.6, 1.8, 2, 2.2, 2.4, 2.6, 2.8, 3, 3.2, 3.4, 3.6, 3.8, 4, 4.2, 4.4, 4.6, 4.8, 5, 5.2, 5.4, 5.6, 5.8, 6, 6.2, 6.4, 6.6, 6.8, 7, 7.2, 7.4, 7.6, 7.8, 8, 8.2, 8.4, 8.6, 8.8, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9]
        }
    };
    
    // Fungsi untuk menentukan kategori IMT
    function getKategoriIMT(imt) {
        if (imt < 18.5) {
            return 'IMT < 18.5';
        } else if (imt >= 18.5 && imt <= 24.9) {
            return 'IMT 18.5 - 24.9';
        } else if (imt >= 25 && imt <= 29.9) {
            return 'IMT 25 - 29.9';
        } else {
            return 'IMT > 30';
        }
    }
    
    // Fungsi untuk mendapatkan rekomendasi berdasarkan kategori IMT
    function getRekomendasi(kategori) {
        switch(kategori) {
            case 'IMT < 18.5':
                return '12,5 - 18 kg';
            case 'IMT 18.5 - 24.9':
                return '11,5 - 16 kg';
            case 'IMT 25 - 29.9':
                return '7 - 11,5 kg';
            case 'IMT > 30':
                return '5 - 9 kg';
            default:
                return 'Tidak tersedia';
        }
    }
    
    // Fungsi untuk mendapatkan warna berdasarkan kategori IMT
    function getWarnaKategori(kategori) {
        switch(kategori) {
            case 'IMT < 18.5':
                return 'rgba(0, 0, 0, 0.2)';
            case 'IMT 18.5 - 24.9':
                return 'rgba(255, 182, 193, 0.5)';
            case 'IMT 25 - 29.9':
                return 'rgba(255, 105, 180, 0.5)';
            case 'IMT > 30':
                return 'rgba(144, 238, 144, 0.5)';
            default:
                return 'rgba(200, 200, 200, 0.5)';
        }
    }
    
    // Fungsi untuk membuat grafik
    function buatGrafik(kategoriIMT) {
        // Data minggu kehamilan (x-axis)
        const mingguKehamilan = Array.from({length: 42}, (_, i) => i);
        
        // Hapus grafik sebelumnya jika ada
        if (grafikIMT) {
            grafikIMT.destroy();
        }
        
        // Dapatkan warna untuk kategori
        const warnaKategori = getWarnaKategori(kategoriIMT);
        
        // Inisialisasi grafik baru
        const ctx = document.getElementById('grafikIMT').getContext('2d');
        grafikIMT = new Chart(ctx, {
            type: 'line',
            data: {
                labels: mingguKehamilan,
                datasets: [
                    {
                        label: 'Batas Atas',
                        data: batasIMT[kategoriIMT].batasAtas,
                        borderColor: 'rgba(0, 0, 0, 0.7)',
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: 'Batas Bawah',
                        data: batasIMT[kategoriIMT].batasBawah,
                        borderColor: 'rgba(0, 0, 0, 0.7)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: warnaKategori,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Grafik Peningkatan Berat Badan untuk Kategori ${kategoriIMT}`,
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Minggu Kehamilan'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Peningkatan Berat Badan (kg)'
                        }
                    }
                }
            }
        });
        
        return grafikIMT;
    }
    
    // Fungsi untuk menambahkan data berat badan pasien ke grafik
    function tambahDataPasien(minggu, beratBadan, beratAwal) {
        if (!grafikIMT) return;
        
        // Hitung peningkatan berat badan
        const peningkatan = beratBadan - beratAwal;
        
        // Tambahkan dataset baru atau update yang ada
        const datasetIndex = grafikIMT.data.datasets.findIndex(ds => ds.label === 'Berat Badan Pasien');
        
        if (datasetIndex === -1) {
            // Tambahkan dataset baru
            grafikIMT.data.datasets.push({
                label: 'Berat Badan Pasien',
                data: [{x: minggu, y: peningkatan}],
                borderColor: 'rgba(255, 0, 0, 1)',
                backgroundColor: 'rgba(255, 0, 0, 0.5)',
                pointRadius: 5,
                pointHoverRadius: 7,
                showLine: true
            });
        } else {
            // Update dataset yang ada
            const dataset = grafikIMT.data.datasets[datasetIndex];
            const pointIndex = dataset.data.findIndex(point => point.x === minggu);
            
            if (pointIndex === -1) {
                dataset.data.push({x: minggu, y: peningkatan});
            } else {
                dataset.data[pointIndex].y = peningkatan;
            }
            
            // Urutkan data berdasarkan minggu
            dataset.data.sort((a, b) => a.x - b.x);
        }
        
        grafikIMT.update();
    }
    
    // Event listener untuk tombol Update Grafik
    if (document.getElementById('updateGrafik')) {
        document.getElementById('updateGrafik').addEventListener('click', function() {
            const imtInput = document.getElementById('imt_pra_kehamilan');
            const bbPraInput = document.getElementById('bb_pra_kehamilan');
            const mingguInput = document.getElementById('minggu_kehamilan');
            const bbSekarangInput = document.getElementById('bb_sekarang');
            
            // Validasi input
            if (!imtInput.value) {
                alert('Mohon masukkan nilai IMT pra-kehamilan');
                imtInput.focus();
                return;
            }
            
            if (!bbPraInput.value) {
                alert('Mohon masukkan berat badan pra-kehamilan');
                bbPraInput.focus();
                return;
            }
            
            if (!mingguInput.value) {
                alert('Mohon masukkan minggu kehamilan saat ini');
                mingguInput.focus();
                return;
            }
            
            if (!bbSekarangInput.value) {
                alert('Mohon masukkan berat badan saat ini');
                bbSekarangInput.focus();
                return;
            }
            
            // Ambil nilai input
            const imt = parseFloat(imtInput.value);
            const bbPra = parseFloat(bbPraInput.value);
            const minggu = parseInt(mingguInput.value);
            const bbSekarang = parseFloat(bbSekarangInput.value);
            
            // Tentukan kategori IMT
            const kategoriIMT = getKategoriIMT(imt);
            
            // Update tampilan kategori
            document.getElementById('kategori_imt').value = kategoriIMT;
            
            // Update rekomendasi
            const rekomendasi = getRekomendasi(kategoriIMT);
            document.getElementById('rekomendasiText').textContent = `Untuk kategori ${kategoriIMT}, rekomendasi peningkatan berat badan adalah ${rekomendasi}.`;
            
            // Buat grafik
            buatGrafik(kategoriIMT);
            
            // Tambahkan data pasien
            tambahDataPasien(minggu, bbSekarang, bbPra);
        });
    }
    
    // Event listener untuk tombol Hitung IMT
    if (document.getElementById('hitungIMT')) {
        document.getElementById('hitungIMT').addEventListener('click', function() {
            const bb = parseFloat(document.getElementById('kalkulator_bb').value);
            const tb = parseFloat(document.getElementById('kalkulator_tb').value);
            
            if (!bb || !tb) {
                alert('Mohon masukkan berat badan dan tinggi badan');
                return;
            }
            
            // Hitung IMT (BB dalam kg / (TB dalam m)²)
            const tbMeter = tb / 100;
            const imt = bb / (tbMeter * tbMeter);
            
            // Tampilkan hasil
            document.getElementById('hasil_imt').value = imt.toFixed(2);
            
            // Tentukan kategori
            const kategori = getKategoriIMT(imt);
            document.getElementById('kategori_hasil_imt').value = kategori;
        });
    }
    
    // Event listener untuk tombol Gunakan IMT
    if (document.getElementById('gunakanIMT')) {
        document.getElementById('gunakanIMT').addEventListener('click', function() {
            const imt = document.getElementById('hasil_imt').value;
            const kategori = document.getElementById('kategori_hasil_imt').value;
            const bb = document.getElementById('kalkulator_bb').value;
            const tb = document.getElementById('kalkulator_tb').value;
            
            if (!imt) {
                alert('Silahkan hitung IMT terlebih dahulu');
                return;
            }
            
            // Isi nilai ke form utama
            document.getElementById('imt_pra_kehamilan').value = imt;
            document.getElementById('kategori_imt').value = kategori;
            document.getElementById('bb_pra_kehamilan').value = bb;
            document.getElementById('tb_ibu').value = tb;
            
            // Update rekomendasi
            const rekomendasi = getRekomendasi(kategori);
            document.getElementById('rekomendasiText').textContent = `Untuk kategori ${kategori}, rekomendasi peningkatan berat badan adalah ${rekomendasi}.`;
            
            // Tutup modal
            const modalHitungIMT = bootstrap.Modal.getInstance(document.getElementById('modalHitungIMT'));
            if (modalHitungIMT) {
                modalHitungIMT.hide();
            } else {
                // Fallback untuk JQuery
                try {
                    $('#modalHitungIMT').modal('hide');
                } catch (error) {
                    console.error('Tidak dapat menutup modal: ', error);
                }
            }
        });
    }
    
    // Event listener untuk tombol Cetak Grafik
    if (document.getElementById('printGrafikIMT')) {
        document.getElementById('printGrafikIMT').addEventListener('click', function() {
            const imtInput = document.getElementById('imt_pra_kehamilan');
            
            // Validasi input
            if (!imtInput.value || !grafikIMT) {
                alert('Mohon update grafik terlebih dahulu sebelum mencetak');
                return;
            }
            
            // Ambil data yang diperlukan
            const imt = parseFloat(imtInput.value);
            const kategoriIMT = getKategoriIMT(imt);
            const rekomendasi = getRekomendasi(kategoriIMT);
            const no_rkm_medis = document.getElementById('hidden_no_rkm_medis') ? document.getElementById('hidden_no_rkm_medis').value : '';
            const nama = document.getElementById('hidden_nm_pasien') ? document.getElementById('hidden_nm_pasien').value : '';
            
            // Konversi canvas ke image data URL
            const imageData = document.getElementById('grafikIMT').toDataURL('image/png');
            
            // Buat form untuk submit data ke halaman cetak
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'index.php?module=rekam_medis&action=print_grafik_imt';
            form.target = '_blank';
            
            // Tambahkan input hidden untuk data
            const inputNoRm = document.createElement('input');
            inputNoRm.type = 'hidden';
            inputNoRm.name = 'no_rm';
            inputNoRm.value = no_rkm_medis;
            
            const inputNama = document.createElement('input');
            inputNama.type = 'hidden';
            inputNama.name = 'nama';
            inputNama.value = nama;
            
            const inputKategori = document.createElement('input');
            inputKategori.type = 'hidden';
            inputKategori.name = 'kategori_imt';
            inputKategori.value = kategoriIMT;
            
            const inputRekomendasi = document.createElement('input');
            inputRekomendasi.type = 'hidden';
            inputRekomendasi.name = 'rekomendasi';
            inputRekomendasi.value = rekomendasi;
            
            const inputImage = document.createElement('input');
            inputImage.type = 'hidden';
            inputImage.name = 'image_data';
            inputImage.value = imageData;
            
            form.appendChild(inputNoRm);
            form.appendChild(inputNama);
            form.appendChild(inputKategori);
            form.appendChild(inputRekomendasi);
            form.appendChild(inputImage);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }
});

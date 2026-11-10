# Smart Complaint – Sistem Pengaduan Masyarakat Kota Bandar Lampung
## Implementasi Live Coding (PHP + SQLite Dinamis & Database Nyata)

Turunan dari:
- **PRD**: `PRD.md` (v1.0, 10 November 2026)
- **Desain Antarmuka**: `desaind.md` (14 Layar Google Stitch)
- **Penyelarasan**: `PENYESUAIAN_PRD_DESAIN.md`

**Pemilik Proyek:** Zeindra Adi Verqiawan  
**Dosen Pembimbing:** Dr. Erliyan Redy Susanto, M.Kom  
**Mata Kuliah:** Perancangan dan Pengembangan Sistem Informasi (PPSI) - Ujian Tengah Semester (UTS)

---

## 1. Cara Menjalankan Aplikasi (Live Server)

Aplikasi dibangun dengan arsitektur **Zero-Config Database** (SQLite otomatis terbuat saat pertama kali dijalankan, tanpa perlu impor SQL manual).

### Cara Cepat (Windows):
Cukup klik ganda (double-click) file:
```
run.bat
```
Atau jalankan melalui terminal:
```bash
php -S localhost:8000
```
Buka peramban di: **`http://localhost:8000`**

---

## 2. Akun Demo Pengujian (1-Click Login)

Pada halaman login (`login.php`), telah disediakan tombol **1-Click Quick Demo Login** untuk mempermudah evaluasi dosen:

| Peran | Email | Kata Sandi | Halaman Utama |
|---|---|---|---|
| **Warga** | `budi@warga.com` | `password123` | `dashboard.php` |
| **Petugas Lapangan** | `petugas@bandarlampung.go.id` | `password123` | `petugas/index.php` |
| **Admin Dinas** | `admin@bandarlampung.go.id` | `password123` | `admin/index.php` |

---

## 3. Pemetaan 14 Layar ke Berkas Kode (PHP)

| Kode Layar | Nama Layar | Berkas Implementasi | Keterangan & Fitur Utama |
|---|---|---|---|
| **S-01** | Beranda Publik | `index.php` | Hero, 5 Kategori, Peta Leaflet sebaran koordinat dinamis dari basis data, statistik real-time. |
| **S-02** | Masuk (Login) | `login.php` | Autentikasi multi-peran dengan `password_verify` & tombol Quick Demo Login. |
| **S-03** | Registrasi Warga | `register.php` | Registrasi akun warga **tanpa NIK** demi perlindungan privasi (PRD FR-01 & NFR-08). |
| **S-04** | Buat Laporan (Web) | `lapor.php` | Form aduan, upload foto bukti real ke folder `uploads/`, peta Leaflet picker koordinat GPS. |
| **S-05** | Buat Laporan (Mobile) | `lapor.php?view=mobile` | Tampilan responsif frame smartphone dengan tombol panggilan darurat 112. |
| **S-06** | Konfirmasi Laporan | `konfirmasi_tiket.php` | Nomor tiket unik otomatis (e.g. `SC-2026-11001`), tombol salin tiket, status *Submitted*. |
| **S-07** | Dashboard Warga | `dashboard.php` | Riwayat laporan warga, filter tiket, widget notifikasi tindak lanjut *"Perlu Respon Anda"*. |
| **S-08** | Lacak Pengaduan | `lacak.php` | Mesin pencari tiket, stepper linimasa audit trail dinamis dari tabel `report_history`. |
| **S-09** | Detail & Konfirmasi | `detail.php` | Validasi kepuasan warga pasca pengerjaan, rating bintang 1–5, formulir sanggahan. |
| **S-10** | Dashboard Petugas | `petugas/index.php` | Daftar SPK tugas, navigasi GPS, form Berita Acara (BAP) penanganan fisik lapangan. |
| **S-11** | Pusat Kontrol GIS | `admin/index.php` | Command center pemetaan GIS sebaran 20 kecamatan Bandar Lampung. |
| **S-12** | Monitoring Rekapitulasi| `admin/index.php` | 7 status lengkap, filter dinas/kecamatan, ekspor CSV real (`?export=csv`). |
| **S-13** | Verifikasi Laporan | `admin/verifikasi.php` | Deteksi duplikasi spasial radius 100 meter, disposisi ke dinas & petugas, form tolak ber-alasan. |
| **S-14** | Dokumen Siap Cetak A4| `cetak_bap.php` | Format lembar kerja resmi BAP-PM A4 dengan kop 2 logo resmi & tanda tangan 3 pihak. |
| **S-15** | Master Data Sistem | `admin/master.php` | Manajemen OPD/dinas, kategori laporan, dan konfigurasi batas upload 5 MB. |

---

## 4. Kepatuhan Wajib Branding & Hak Cipta

- **Dua Logo Berdampingan (UI-LOGO-01 & UI-LOGO-02)**: Lambang Pemkot Bandar Lampung & Logo Universitas Teknokrat Indonesia (UTI) dalam format vektor SVG konsisten di seluruh berkas header dan cetak.
- **Teks Footer Wajib (UI-FOOTER-01 sampai 05)**:
  ```text
  Copyright©2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom
  ```

---
Copyright©2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom


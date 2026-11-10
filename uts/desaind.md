# Dokumentasi Desain Stitch – Smart Complaint

**Sistem Pengaduan Masyarakat Kota Bandar Lampung**
Turunan dari PRD `PRD-Smart-Complaint-Rust-PostgreSQL.md` (v1.0)

| Informasi | Keterangan |
|---|---|
| Sumber | Hasil generate Google Stitch (HTML + Tailwind CDN) |
| Jumlah layar | 14 layar |
| Platform | Web responsif (desktop, tablet) dan 2 layar mobile |
| Bahasa antarmuka | Bahasa Indonesia |
| Pemilik | Zeindra Adi Verqiawan |

**Footer wajib (dipakai persis di semua layar):**

```
Copyright©2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom
```

---

## 1. Design system

### 1.1 Warna

| Token | Hex | Pemakaian |
|---|---|---|
| `primary` | `#00163a` | Teks judul, tombol utama gelap |
| `primary-container` | `#0b2a5b` | Navy utama: header, footer, tombol utama, menu aktif |
| `secondary` | `#005cbd` | Aksi, tautan, ikon aksen |
| `secondary-container` | `#4e92fe` | Aksen terang, indikator aktif |
| `secondary-fixed` | `#d7e2ff` | Badge info, latar chip |
| `surface` / `background` | `#f9f9ff` | Latar halaman |
| `surface-container-lowest` | `#ffffff` | Kartu |
| `surface-container-low` | `#f0f3ff` | Panel sekunder |
| `surface-container` | `#e7eeff` | Chip, input |
| `surface-container-high` | `#dee8ff` | Banner info, tab |
| `on-surface` | `#111c2d` | Teks utama |
| `on-surface-variant` | `#44474f` | Teks pendukung |
| `outline-variant` | `#c4c6d0` | Garis halus |
| `error` / `error-container` | `#ba1a1a` / `#ffdad6` | Tolak, darurat |

### 1.2 Badge status

Status selalu memakai teks + ikon + warna (sesuai PRD bagian 9.1).

| Status | Warna | Ikon |
|---|---|---|
| Menunggu verifikasi | Amber (`#FEF3C7` / `#D97706`) | `hourglass_top` |
| Diproses | Biru (`#DBEAFE` / `#2563EB`) | `settings_suggest` / `sync` |
| Selesai | Hijau (`#DCFCE7` / `#16A34A`) | `done_all` / `task_alt` |
| Perlu revisi | Oranye (`#FFEDD5` / `#EA580C`) | `edit_note` |
| Ditolak | Merah (`#FEE2E2` / `#DC2626`) | `cancel` |
| Di luar wilayah | Abu-abu | `wrong_location` |
| Menunggu konfirmasi warga | Amber | `notifications_active` |

### 1.3 Tipografi

| Peran | Font | Ukuran / bobot |
|---|---|---|
| Headline XL | Plus Jakarta Sans | 36/44, 700 |
| Headline LG | Plus Jakarta Sans | 28/36, 600 |
| Headline MD | Plus Jakarta Sans | 20/28, 600 |
| Headline SM | Plus Jakarta Sans | 16/24, 600 |
| Body LG / MD / SM | Inter | 16 / 14 / 12, 400 |
| Label MD / SM | Inter | 13 / 11, 600 |

Nomor tiket memakai font monospace.

### 1.4 Bentuk dan jarak

- Radius: `0.25rem` (default), `0.5rem` (lg), `0.75rem` (xl), `9999px` (pill).
- Jarak: xs 0.25rem, sm 0.5rem, md 1rem, lg 1.5rem, xl 2.5rem; margin 2rem (mobile 1rem).
- Ikon: Material Symbols Outlined.
- Gaya: kartu tanpa garis tegas, bayangan lembut, latar berlapis (tonal layering).

---

## 2. Komponen bersama

| Komponen | Isi | Keterangan |
|---|---|---|
| Banner prototipe | "Prototipe akademik – bukan kanal resmi" | Di atas header atau dekat footer pada semua layar |
| Header dua logo | Logo Pemda + logo UTI berdampingan, tinggi sekitar 40 px | UI-LOGO-01/02 |
| Footer wajib | Teks copyright persis | UI-FOOTER-01/02 |
| Navigasi web warga | Beranda, Dashboard Saya, Buat Laporan, Lacak Aduan, Bantuan | Header atas |
| Navigasi dashboard | Sidebar kiri (petugas: lebar 72, admin: lebar 64) | Admin: Dashboard, Laporan, Petugas, Unit/Dinas, Kategori, Pengaturan |
| Navigasi mobile | Bottom bar: Beranda, Aduan, Lapor, Pantau, Profil | Layar mobile |
| Badge status | Lihat 1.2 | Konsisten di semua layar |
| Kartu tiket | Nomor tiket, status, kategori, lokasi, aksi | Daftar laporan |
| Linimasa status | Stepper vertikal | Detail laporan, lacak |

---

## 3. Daftar layar

### 3.1 Layar publik dan autentikasi

#### S-01 Beranda Publik
- **Pengguna:** semua
- **Isi:** hero "SMART COMPLAINT" dengan tombol Buat Laporan / Lacak Laporan; 5 kartu kategori (Jalan Rusak, Lampu PJU, Sampah, Banjir & Drainase, Lingkungan); 4 langkah alur penanganan; statistik agregat; peta publik tersamar dengan filter wilayah dan legenda status; kartu kolaborasi riset.
- **Pemetaan PRD:** Beranda publik (9.2), FR-29.

#### S-02 Masuk ke Smart Complaint
- **Isi:** kartu login, dua logo di atas judul, tab peran (Warga / Petugas-Admin), kolom email/nomor kontak, kata sandi dengan tampil/sembunyi, "Ingat saya", "Lupa kata sandi", tautan daftar, kotak info lapor cepat.
- **Pemetaan PRD:** FR-02, FR-03.

#### S-03 Registrasi Akun Warga
- **Isi:** nama lengkap, email/nomor kontak, kata sandi + indikator kekuatan, konfirmasi kata sandi, checkbox kebijakan privasi, validasi inline berbahasa Indonesia.
- **Pemetaan PRD:** FR-01.

### 3.2 Layar warga

#### S-04 Beranda & Buat Laporan (web)
- **Isi:** hero + statistik; formulir 5 bagian (kategori kartu, judul/deskripsi, unggah foto dengan pratinjau, lokasi GPS + peta + pilihan kecamatan, identitas pelapor); feed transparansi laporan terkini dengan legenda 6 status.
- **Pemetaan PRD:** FR-05 sampai FR-08, FR-12.

#### S-05 Buat Laporan (mobile)
- **Isi:** callout darurat banjir/keselamatan; stepper 4 tahap (Uraian, Foto Bukti, Lokasi, Tinjau); batas foto 5 file x 5 MB; tombol GPS Otomatis dan Pilih di Peta; peta dengan pin yang bisa digeser; verifikasi wilayah; tombol Simpan Draft ("Belum terkirim") dan Kirim Laporan.
- **Pemetaan PRD:** FR-05 sampai FR-09, FR-37, NFR-13, NFR-14.

#### S-06 Konfirmasi Laporan (mobile)
- **Isi:** layar berhasil, nomor tiket `SC-2026-00001` dengan tombol salin, ringkasan laporan, status "Menunggu Verifikasi", tombol Lacak Laporan / Kembali ke Beranda / Buat Pengaduan Lainnya.
- **Pemetaan PRD:** FR-10.

#### S-07 Dashboard Warga
- **Isi:** sapaan, 3 kartu ringkasan (Laporan Saya, Diproses, Selesai), daftar laporan dengan filter/pencarian/paginasi, widget "Perlu Respon Anda", peta laporan saya, kartu darurat 112.
- **Pemetaan PRD:** FR-11, FR-25, FR-26.

#### S-08 Lacak Pengaduan
- **Isi:** pencarian nomor tiket + filter kecamatan/kategori, kartu ringkasan tiket, linimasa 5 tahap, dokumentasi sebelum/sesudah, catatan lapangan, panduan 6 status, aduan terdekat.
- **Pemetaan PRD:** FR-24, FR-23.

#### S-09 Detail Laporan Pengaduan
- **Isi:** header tiket, status "Menunggu Konfirmasi Warga", dokumentasi visual, narasi aduan, peta lokasi, catatan petugas (BAP), linimasa 8 tahap, kartu konfirmasi warga (Sudah selesai / Belum selesai + kolom sanggahan), rating bintang 1 sampai 5 dan ulasan.
- **Pemetaan PRD:** FR-12, FR-24, FR-25, FR-33.

### 3.3 Layar petugas

#### S-10 Dashboard Petugas Lapangan
- **Isi:** sidebar, 3 kartu ringkasan tugas, antrean tugas (jarak dan estimasi tiba), tombol Buka Rute Navigasi GPS, panel formulir Unggah Bukti Lapangan (kamera, geotagging otomatis, catatan tindakan, Selesaikan Pekerjaan, Minta Dukungan Alat Berat).
- **Pemetaan PRD:** FR-19 sampai FR-22.

### 3.4 Layar admin

#### S-11 Pusat Kontrol Admin Dinas
- **Isi:** 7 kartu status (Total, Menunggu, Diproses, Selesai, Perlu Revisi, Ditolak, Luar Wilayah), peta sebaran (GIS heatmap), panel penugasan cepat regu, antrean verifikasi dan disposisi cepat.
- **Pemetaan PRD:** FR-17, FR-18, FR-27, FR-28, FR-29.

#### S-12 Dashboard Admin
- **Isi:** filter rentang tanggal dan kategori, tombol Ekspor (PDF/CSV), 4 kartu KPI, laporan per kategori, peta sebaran, tabel antrean (tiket, bukti, kategori/OPD, lokasi, waktu, status, aksi cepat) dengan paginasi.
- **Pemetaan PRD:** FR-27, FR-28, FR-29, FR-31.

#### S-13 Verifikasi Laporan (Admin)
- **Isi:** galeri bukti + metadata foto, peta dan status wilayah, deteksi kemungkinan duplikat, informasi pelapor, jejak audit, panel keputusan (Verifikasi & Disposisikan, Minta Revisi, Tandai Duplikat, Di Luar Wilayah, Tolak dengan alasan wajib), formulir disposisi (OPD, bidang, regu, SLA, catatan).
- **Pemetaan PRD:** FR-13 sampai FR-18, FR-30, FR-36, FR-38.

#### S-14 Laporan Siap Cetak (A4)
- **Isi:** header dua logo, kode verifikasi dokumen, QR, metadata, substansi aduan, berita acara penanganan, foto awal/akhir/peta, tabel jejak audit 7 tahap, lembar pengesahan 3 kolom, footer wajib. Tombol Unduh PDF dan Cetak (disembunyikan saat cetak).
- **Pemetaan PRD:** FR-31, UI-LOGO-01/02, UI-FOOTER-01.

---

## 4. Pemeriksaan kepatuhan branding

### 4.1 Footer wajib

| Layar | Teks footer sesuai | Catatan |
|---|---|---|
| S-01 sampai S-14 | Ya (persis) | Terdapat pada semua layar |

Beberapa layar menampilkan teks yang sama dua kali (di dalam konten dan di footer): S-08, S-10, S-11, S-12, S-13, S-14. Tidak melanggar, tetapi sebaiknya dijadikan satu komponen footer agar konsisten (UI-FOOTER-02).

### 4.2 Logo Pemda dan logo UTI

Bentuk logo belum seragam antar layar:

| Bentuk | Layar |
|---|---|
| Gambar logo (`<img>`) | S-04, S-08, S-10, S-11 |
| Placeholder teks `[Logo ...]` | S-01, S-03, S-05, S-06, S-07, S-09, S-13 (header/konten) |
| Ikon + teks ("Pemkot", "UTI Lampung", "PEMDA KOTA", "TEKNOKRAT") | S-02, S-12, S-13 (header), S-14 |

**Tindakan:** samakan semua layar memakai satu komponen header logo. Ganti dengan file resmi (SVG/PNG) setelah tersedia dan ikuti pedoman identitas visual masing-masing instansi (UI-LOGO-03).

---

## 5. Hal yang perlu diperbaiki agar selaras dengan PRD

| No | Temuan pada desain | Acuan PRD | Saran |
|---|---|---|---|
| 1 | Beberapa teks menyiratkan kanal resmi, misalnya "Dokumen ini diterbitkan oleh Smart Complaint Pemerintah Kota Bandar Lampung", "Laporan Resmi", "tanda tangan digital BSrE", "Peraturan Walikota SLA" | 1.3 (prototipe, bukan kanal resmi, SLA simulasi) | Ganti dengan "Dokumen simulasi prototipe akademik" dan hapus klaim hukum/SLA resmi |
| 2 | NIK lengkap tampil di S-14 dan input NIK/KTP di S-04, S-07 | NFR-08 (privasi), FR-01 (tidak meminta NIK) | Hapus NIK dari MVP, atau samarkan penuh |
| 3 | Validasi Dukcapil, WhatsApp/SMS/Telegram, klasifikasi dan prioritas AI, hash SHA-256 foto | 3.2 (out-of-scope: AI, WhatsApp/SMS, integrasi eksternal) | Hapus dari layar MVP atau beri label "Pengembangan lanjutan" |
| 4 | Angka kinerja ("92,8% selesai", "respons 2,4 jam", "100% kepuasan", "<2 jam") tampil seperti fakta | 1.3 butir 4 (data demo harus berlabel jelas) | Beri label "Data simulasi" |
| 5 | Nomor telepon darurat dan Posko (misalnya `(0721) 252741`), nama pejabat dan NIP | Keabsahan data | Verifikasi atau ganti placeholder sebelum dipakai di luar demo |
| 6 | Tanggal sampel Maret 2026, sedangkan PRD bertanggal 10 November 2026 | Konsistensi dokumen | Samakan tanggal data demo |
| 7 | Menu Petugas, Unit/Dinas, Kategori, Pengaturan hanya ada di sidebar, layarnya belum dibuat | 9.2 (manajemen petugas/unit, kategori, pengaturan) | Buat layar tambahan di tahap berikutnya |
| 8 | Layar Registrasi/Login tidak memakai satu pola header yang sama dengan layar lain | UI-LOGO-01 | Satukan komponen header |
| 9 | Peta memakai gambar statis hasil generate | NFR-21, FR-08 | Pada implementasi pakai peta nyata (Leaflet + OpenStreetMap) dan PostGIS |

---

## 6. Catatan serah terima implementasi (Rust + PostgreSQL)

- Hasil Stitch berupa HTML dengan Tailwind CDN dan Material Symbols. Untuk produksi, kompilasi Tailwind secara lokal dan hosting font/ikon sendiri.
- Rekomendasi sederhana untuk tugas kuliah: template server-side (Askama atau Tera) pada Axum, dengan komponen header, footer, dan badge status sebagai partial bersama.
- Pindahkan token warna dan tipografi di bagian 1 ke konfigurasi Tailwind tunggal.
- Pemetaan layar ke data: tiket, kategori, lampiran, lokasi (PostGIS), riwayat status, unit/dinas, notifikasi, sesuai PRD bagian 10.
- Logika yang sekarang berupa skrip demo (alert, toast, peralihan status) perlu diganti dengan endpoint API.

---

## 7. Ringkasan

| Item | Status |
|---|---|
| 14 layar tersedia (publik, warga, petugas, admin, cetak) | Selesai |
| Footer wajib pada semua layar | Sesuai |
| Dua logo pada semua layar | Perlu diseragamkan |
| Kesesuaian lingkup PRD (tanpa AI, NIK, WhatsApp, klaim resmi) | Perlu revisi (lihat bagian 5) |
| Layar manajemen (petugas, unit, kategori, pengaturan) | Belum dibuat |

Copyright©2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom

# LAPORAN AUDIT KEAMANAN SISTEM SMART COMPLAINT

**Sistem Pengaduan Masyarakat Kota Bandar Lampung**

| Informasi Audit | Detail |
| --- | --- |
| **Jenis Audit** | Static Security Review / Audit dokumen |
| **Dokumen Acuan** | PRD v1.0 dan Dokumentasi Desain Stitch (14 layar) |
| **Tanggal** | 10 November 2026 |
| **Status** | BELUM DAPAT DINYATAKAN AMAN (perlu validasi implementasi) |
| **Cakupan Teknis** | Requirement, alur layar, rancangan API, arsitektur, privasi, dan kontrol keamanan |

*Disusun untuk evaluasi keamanan dan pengembangan prototipe akademik*  
*Copyright © 2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom*

---

## 1. Ringkasan Eksekutif

Audit menilai apakah kebutuhan dan desain Smart Complaint sudah menetapkan kontrol keamanan yang cukup untuk melindungi akun, laporan, foto, lokasi, dan data pribadi warga. Dokumen PRD telah memuat fondasi yang baik seperti HTTPS, Argon2id, RBAC, pemeriksaan kepemilikan objek, validasi server-side, proteksi unggahan, audit log, redaksi log, backup, serta retensi/anonimisasi.

Namun, keberadaan requirement belum membuktikan kontrol tersebut telah diimplementasikan atau diuji. Beberapa detail operasional masih perlu dikunci, terutama mitigasi CSRF untuk session cookie, pembatasan percobaan login, alur reset kata sandi, otorisasi per objek pada seluruh endpoint, perlindungan akses file, privasi lokasi, dan tata kelola data.

> **Kesimpulan:** Dokumen memiliki arah keamanan yang cukup baik, tetapi kesiapan keamanan **belum dapat disahkan**. Temuan di bawah ini adalah risiko dan celah spesifikasi yang perlu ditutup sebelum uji keamanan implementasi.

### 1.1 Ringkasan Status

| Area | Penilaian Dokumen | Catatan |
| --- | --- | --- |
| **Autentikasi** | Perlu diperjelas | Hashing disebutkan; kontrol login dan pemulihan akun belum terinci. |
| **Otorisasi** | Ada fondasi; perlu uji | RBAC dan ownership disebutkan; perlu policy per endpoint dan per objek. |
| **Privasi** | Perlu perbaikan | Peta publik tersamar; lokasi, foto, identitas, dan tracking perlu aturan rinci. |
| **Unggahan** | Ada requirement | Validasi tipe/ukuran/signature; proses karantina/scan dan akses file perlu dibuktikan. |
| **API & Input** | Perlu diperjelas | Validasi server disebut; CSRF, rate limit, error handling dan abuse controls perlu ditetapkan. |
| **Audit & Logging** | Ada requirement | Audit log append-only dan redaksi rahasia disebut; akses dan retensi log perlu ditetapkan. |
| **Backup & Retensi** | Perlu dilengkapi | Backup/restore dan kebijakan retensi harus diuji dan didokumentasikan. |

### 1.2 Batasan Audit

- Tidak tersedia source code frontend/backend Rust, konfigurasi deployment, database, object storage, atau instance aplikasi.
- Tidak dilakukan pemindaian kerentanan, penetration testing, fuzzing, pemeriksaan dependency, atau uji konfigurasi server.
- Tidak ada eksploitasi atau akses ke data pengguna. Temuan merupakan review spesifikasi dan desain, bukan klaim adanya kerentanan terkonfirmasi.

---

## 2. Ruang Lingkup dan Dasar Penilaian

Sistem dirancang sebagai prototipe akademik web responsif dengan target Rust/Axum/Tokio, SQLx, PostgreSQL/PostGIS, frontend Bootstrap 5/JavaScript, dan penyimpanan foto MinIO/S3-compatible. Peran utama yang tampak dalam rancangan adalah warga, petugas, dan admin.

**Komponen yang ditinjau:**
- Registrasi, login, lupa kata sandi, sesi pengguna, dan pengelolaan peran.
- Pembuatan laporan, lampiran foto, lokasi GPS/peta, pelacakan tiket, dan riwayat status.
- Penugasan petugas, perubahan status, komentar, dashboard admin, peta laporan, ekspor, dan audit log.
- PostgreSQL/PostGIS, object storage, reverse proxy/TLS, logging, backup, serta kebijakan retensi.

---

## 3. Temuan Audit Keamanan

*Skala prioritas:*
- **Kritis:** Risiko langsung terhadap data/akun atau kontrol inti
- **Tinggi:** Berpotensi menyebabkan akses tidak sah atau kebocoran bermakna
- **Sedang:** Kontrol penting yang perlu dipastikan sebelum rilis
- **Rendah:** Penguatan tambahan

---

### SEC-01 | Tinggi: CSRF pada autentikasi berbasis session belum dirinci
- **Dasar Temuan:** PRD merekomendasikan session cookie `HttpOnly`, `Secure`, `SameSite` dan menyebut perlindungan CSRF, tetapi tidak menetapkan pola token, validasi `Origin`/`Referer`, atau cakupan endpoint yang wajib dilindungi.
- **Rekomendasi:** Tambahkan token CSRF untuk semua operasi mutasi berbasis cookie; validasi `Origin`/`Referer`; gunakan `SameSite` sebagai lapisan tambahan, bukan satu-satunya kontrol. Uji create/update/delete, upload, assignment, status, dan perubahan role.

---

### SEC-02 | Tinggi: Brute force dan penyalahgunaan autentikasi belum memiliki acceptance criteria
- **Dasar Temuan:** Dokumen menyebut hashing Argon2id dan fitur login/lupa kata sandi, tetapi batas percobaan login, rate limiting, cooldown, deteksi credential stuffing, dan notifikasi aktivitas mencurigakan belum ditetapkan.
- **Rekomendasi:** Terapkan rate limit adaptif per akun dan sumber, jeda progresif, respons login yang tidak membocorkan apakah akun terdaftar, serta pencatatan percobaan gagal. Hindari lockout permanen yang mudah dipakai untuk denial-of-service.

---

### SEC-03 | Tinggi: Reset kata sandi dan verifikasi kontak belum memiliki alur aman yang lengkap
- **Dasar Temuan:** Desain menampilkan fitur lupa kata sandi, tetapi PRD/desain yang ditinjau belum menjelaskan token reset sekali pakai, expiry, invalidasi sesi, dan verifikasi kepemilikan email/nomor.
- **Rekomendasi:** Gunakan token acak berentropi tinggi, simpan hash token, sekali pakai, masa berlaku singkat, respons generik, rate limit, dan cabut sesi aktif setelah reset. Jangan mengirim password baru melalui pesan.

---

### SEC-04 | Tinggi: Risiko IDOR/BOLA pada endpoint berbasis ID laporan dan lampiran
- **Dasar Temuan:** API memuat endpoint detail, update, attachment, history, verify, reject, assign, status, comments, confirm, feedback, notifications, dan export dengan batas akses berbasis peran/relasi.
- **Rekomendasi:** Buat matriks otorisasi per endpoint dan uji setiap objek dengan ID milik akun lain. Validasi ownership/assignment di backend pada setiap request, bukan hanya menyembunyikan tombol di frontend. Gunakan penolakan konsisten tanpa membocorkan keberadaan objek.

---

### SEC-05 | Tinggi: Privasi lokasi dan lampiran pada peta publik perlu aturan teknis
- **Dasar Temuan:** Desain menyebut peta publik tersamar/agregat dan PRD melarang data pribadi tampil publik. Namun detail presisi koordinat, metadata EXIF, foto sensitif, dan ambang agregasi belum ditentukan.
- **Rekomendasi:** Pisahkan endpoint publik dari endpoint admin. Kurangi presisi koordinat atau agregasikan; hapus EXIF GPS dari foto; moderasi/blur identitas dan plat nomor; jangan menampilkan alamat rumah atau foto asli secara publik tanpa dasar dan persetujuan yang sesuai.

---

### SEC-06 | Tinggi: Kontrol keamanan file upload belum mencakup seluruh siklus penanganan
- **Dasar Temuan:** PRD meminta validasi ekstensi, MIME, ukuran, signature, dimensi, nama acak, serta penyimpanan privat. Belum tampak kewajiban eksplisit untuk decode/re-encode gambar, malware scanning, quarantine, dan pembatasan akses unduhan.
- **Rekomendasi:** Allowlist format gambar; verifikasi magic bytes; decode lalu encode ulang; batasi dimensi/pixel untuk mencegah decompression bomb; scan bila tersedia; simpan di luar webroot/private bucket; gunakan authorization atau signed URL singkat; uji file polyglot dan file rusak.

---

### SEC-07 | Sedang: Kebijakan data pribadi, persetujuan, dan retensi belum operasional
- **Dasar Temuan:** PRD menyebut data pribadi tidak tampil publik dan retensi/anonimisasi perlu ditentukan. Desain mengumpulkan identitas pelapor dan lokasi, sementara dokumen desain sebelumnya menampilkan NIK/KTP yang perlu diselaraskan dengan prinsip minimisasi data.
- **Rekomendasi:** Tentukan field yang benar-benar diperlukan, tujuan pemrosesan, dasar pemrosesan/pemberitahuan privasi, masa simpan, penghapusan/anonimisasi, akses internal, dan prosedur permintaan subjek data. Jangan mengumpulkan NIK jika tidak diperlukan.

---

### SEC-08 | Sedang: CSRF, CORS, security headers, dan konfigurasi browser belum menjadi checklist rilis
- **Dasar Temuan:** PRD menyebut HTTPS dan reverse proxy, tetapi kebijakan CORS, CSP, HSTS, frame-ancestors, nosniff, referrer policy, dan cache-control untuk halaman sensitif belum dirinci.
- **Rekomendasi:** Tetapkan allowlist CORS yang spesifik; CSP ketat; HSTS setelah HTTPS siap; `X-Content-Type-Options: nosniff`; Referrer-Policy; frame-ancestors; `Cache-Control: no-store` untuk data sensitif. Hindari wildcard credentialed CORS.

---

### SEC-09 | Sedang: Validasi input dan pencegahan injeksi perlu diuji per konteks
- **Dasar Temuan:** PRD mensyaratkan validasi server-side dan SQLx, tetapi acceptance criteria tidak merinci SQL injection, stored/reflected XSS, HTML pada komentar, parameter path, dan validasi koordinat.
- **Rekomendasi:** Gunakan parameterized query; validasi tipe/rentang/enum di server; encode output sesuai konteks; sanitasi rich text bila memang diizinkan; batasi panjang input; uji payload injeksi pada judul, deskripsi, komentar, filter, pencarian, dan ekspor.

---

### SEC-10 | Sedang: Audit log perlu perlindungan dan cakupan event yang lebih jelas
- **Dasar Temuan:** NFR-16 meminta aktor, waktu, aksi, objek dan append-only policy. NFR-22 meminta redaksi password/token/data sensitif. Retensi, akses log, korelasi request, dan alert atas perubahan privilege belum terinci.
- **Rekomendasi:** Catat login sukses/gagal, perubahan role, akses/ekspor data, assignment, perubahan status, unduhan lampiran, dan aksi admin. Batasi akses log, cegah modifikasi, redaksi PII/secret, sinkronkan waktu, dan buat alert untuk aksi berisiko.

---

### SEC-11 | Sedang: Backup, restore, secret management, dan keamanan deployment perlu bukti
- **Dasar Temuan:** PRD meminta prosedur backup dan uji restore, serta menyebut Docker Compose/reverse proxy. Belum ada detail enkripsi backup, akses secret, isolasi jaringan, patching, dan pemulihan kredensial.
- **Rekomendasi:** Simpan secret di environment/secret manager, bukan repository; batasi port database/object storage; gunakan akun DB least-privilege; enkripsi backup saat disimpan/ditransfer; uji restore berkala; dokumentasikan patching dan rotasi kunci.

---

### SEC-12 | Sedang: Tracking tiket dan API publik berpotensi disalahgunakan
- **Dasar Temuan:** Desain menampilkan pelacakan nomor tiket. Dokumen API memisahkan data publik agregat, tetapi mekanisme akses detail laporan melalui tiket belum dijelaskan secara lengkap.
- **Rekomendasi:** Jangan jadikan nomor tiket berurutan sebagai satu-satunya rahasia. Gunakan token tracking acak berentropi tinggi atau verifikasi tambahan; rate limit; batasi field yang ditampilkan; jangan expose identitas, foto privat, komentar internal, atau koordinat presisi.

---

## 4. Matriks Area Serangan dan Kontrol Minimum

| Area | Risiko Utama | Kontrol Minimum yang Harus Diverifikasi |
| --- | --- | --- |
| **Login & Sesi** | Brute force, session fixation, pencurian sesi | Argon2id; session rotation saat login; cookie HttpOnly/Secure/SameSite; expiry idle/absolute; logout invalidate; CSRF; rate limit. |
| **RBAC & Objek** | IDOR/BOLA, privilege escalation | Policy backend per endpoint + ownership; deny-by-default; uji warga/petugas/admin lintas objek. |
| **Laporan & Komentar** | Stored XSS, injeksi, mass assignment | Validasi server-side; output encoding; allowlist field yang dapat diubah; parameterized SQL. |
| **Foto / Lampiran** | Malware, file polyglot, EXIF, akses publik | Allowlist + signature + decode/re-encode; limit ukuran/dimensi; private storage; authorized download. |
| **Peta / GPS** | Kebocoran alamat rumah dan pola aktivitas | Agregasi/precision reduction; endpoint publik terpisah; buang EXIF; akses lokasi presisi terbatas. |
| **API Publik** | Enumeration, scraping, denial-of-service | Token acak; rate limit; pagination; response minim; monitoring dan throttling. |
| **Admin & Ekspor** | Eksfiltrasi data, penyalahgunaan privilege | Least privilege; konfirmasi aksi sensitif; audit log; pembatasan ekspor; re-auth untuk aksi kritis. |
| **Database & Infra** | Kebocoran secret, backup terbuka, lateral movement | Private network; least-privilege DB; secret manager; TLS; backup terenkripsi; restore drill. |

---

## 5. Rencana Pengujian Keamanan yang Disarankan

*Skenario berikut adalah rencana uji yang harus dijalankan pada aplikasi yang sudah dibangun. Status saat ini seluruhnya **Belum diuji** karena implementasi belum tersedia.*

| ID | Skenario | Hasil yang Diharapkan |
| --- | --- | --- |
| **T-01** | Login dengan password salah berulang | Rate limit/cooldown bekerja; tidak ada enumerasi akun. |
| **T-02** | Session cookie setelah login | Cookie memiliki HttpOnly, Secure, SameSite; sesi lama diganti. |
| **T-03** | Request mutasi tanpa token CSRF | Ditolak; request valid dengan token diterima. |
| **T-04** | Warga A membuka ID laporan milik warga B | Akses ditolak tanpa membocorkan data. |
| **T-05** | Petugas membuka tugas yang bukan assignment-nya | Akses ditolak. |
| **T-06** | Warga mencoba endpoint admin/ubah role | 403/penolakan konsisten; event tercatat. |
| **T-07** | Nomor tiket ditebak/di-scrape | Tidak dapat mengakses detail privat; rate limit aktif. |
| **T-08** | Unggah file executable/HTML/SVG berbahaya | Ditolak; tidak disajikan sebagai konten aktif. |
| **T-09** | Unggah gambar dengan MIME palsu atau polyglot | Validasi signature/decode menolak atau mengubah ke format aman. |
| **T-10** | Unggah gambar sangat besar/decompression bomb | Ditolak dengan batas ukuran/dimensi; resource tetap stabil. |
| **T-11** | Unduh lampiran laporan orang lain | Ditolak; URL objek tidak menjadi akses publik. |
| **T-12** | Foto mengandung EXIF GPS | Metadata sensitif dibuang sebelum publikasi. |
| **T-13** | Payload SQL injection pada filter/pencarian | Tidak mengubah query atau mengakses data di luar hak. |
| **T-14** | Payload XSS pada judul/deskripsi/komentar | Ditampilkan sebagai teks aman; script tidak berjalan. |
| **T-15** | CORS dari origin tak dikenal | Browser tidak diberi izin akses credentialed. |
| **T-16** | Security headers pada halaman sensitif | CSP, HSTS, nosniff, frame-ancestors, cache policy sesuai. |
| **T-17** | Log setelah login/reset/upload | Tidak mengandung password, token, atau PII berlebihan. |
| **T-18** | Akun admin mengekspor data | Akses dibatasi, event audit tercatat, data sesuai filter. |
| **T-19** | Restore backup database dan file | Data pulih konsisten; relasi metadata dan object storage cocok. |
| **T-20** | Perubahan role dan assignment | Hanya role berwenang; perubahan tercatat dan tidak dapat diubah diam-diam. |

---

## 6. Prioritas Perbaikan

| Prioritas | Pekerjaan | Kriteria Selesai |
| --- | --- | --- |
| **P0 — Sebelum Data Nyata** | Kunci matriks RBAC + ownership; CSRF; reset password; rate limit login; privasi peta/lampiran; akses privat file. | Seluruh T-01 s.d. T-12 lulus; tidak ada akses lintas akun/role. |
| **P1 — Sebelum Demo Publik** | Validasi input, XSS/SQLi, security headers, CORS, audit log, pembatasan ekspor, secret management. | T-13 s.d. T-18 lulus; konfigurasi terdokumentasi. |
| **P2 — Kesiapan Operasi** | Backup terenkripsi, restore drill, retensi/anonimisasi, monitoring, alert, patch/dependency review. | T-19/T-20 dan latihan pemulihan terdokumentasi. |

---

## 7. Rekomendasi Teknis

- **Backend:** Terapkan middleware autentikasi dan otorisasi terpusat di Axum; gunakan policy domain untuk ownership; semua query SQL memakai parameter binding SQLx.
- **Session:** Untuk web first-party, prioritaskan session cookie dengan atribut aman, rotasi sesi, expiry, logout invalidation, dan CSRF token.
- **Upload:** Simpan di bucket privat; buat endpoint download yang memeriksa relasi pengguna ke laporan; hindari nama file asli sebagai object key.
- **Database:** Akun aplikasi bukan superuser; pisahkan kredensial migrasi dan runtime; batasi akses jaringan; gunakan transaksi untuk perubahan status dan histori.
- **Frontend:** Jangan mengandalkan validasi JavaScript untuk keamanan; hindari menyisipkan konten pengguna ke HTML; gunakan encoding sesuai konteks.
- **Operasional:** Jangan masukkan `.env`, token, private key, atau backup ke repository; siapkan patching, monitoring, dan prosedur insiden.

---

## 8. Kesimpulan Audit

Berdasarkan dokumen PRD dan desain yang ditinjau, Smart Complaint telah mengidentifikasi sejumlah kontrol keamanan penting, tetapi masih terdapat celah spesifikasi yang perlu diperjelas. Temuan prioritas tertinggi berkaitan dengan CSRF, brute force, pemulihan akun, otorisasi per objek, privasi lokasi/foto, dan keamanan unggahan.

**Keputusan Audit:** `NEEDS SECURITY HARDENING - BELUM LULUS VALIDASI KEAMANAN IMPLEMENTASI`

> Status ini bukan pernyataan bahwa aplikasi terbukti rentan; artinya kontrol belum dapat diverifikasi tanpa source code, konfigurasi, dan pengujian langsung.

Agar audit dapat dinaikkan menjadi audit teknis, siapkan source code frontend dan backend Rust, file konfigurasi deployment (hapus seluruh secret), skema/migration database, konfigurasi object storage, serta lingkungan staging dengan akun uji untuk setiap role.

---

## 9. Referensi Internal

1. `PRD.md` — Smart Complaint, versi 1.0, 10 November 2026: NFR-03 sampai NFR-08, NFR-15 sampai NFR-17, NFR-22 sampai NFR-23; bagian autentikasi, penyimpanan foto, API, dan keputusan arsitektur.
2. `desaind.md` — Dokumentasi Desain Stitch: layar S-01 sampai S-14, khususnya login, registrasi, pembuatan laporan, tracking, peta publik, dan dashboard admin.

*Catatan: Laporan ini merupakan dokumen evaluasi internal untuk prototipe akademik, bukan sertifikasi keamanan, audit kepatuhan hukum, atau pengganti penetration test profesional.*

*Copyright © 2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom*
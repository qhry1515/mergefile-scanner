# MergeFile — Tool PDF Lokal

Aplikasi PHP untuk memproses PDF **100% offline** di komputer Anda sendiri. Tidak ada file yang dikirim ke internet.

## Fitur

| Tool | Fungsi |
|---|---|
| **Gabung** | Gabungkan banyak PDF + gambar (JPG/PNG/GIF) jadi satu PDF, dengan drag & drop urutan |
| **Transfer** | Kirim foto/file dari HP ke laptop lewat WiFi lokal, Tailscale, atau link internet (Cloudflare tunnel + PIN) |
| **Split** | Ekstrak atau hapus halaman tertentu, termasuk pengaturan ulang urutan halaman |
| **Rotasi** | Putar halaman 90°/180°/270° (per halaman atau seluruh dokumen) |
| **Nomor** | Stempel nomor halaman (posisi, format, font, warna, ukuran, mulai dari) |
| **Kompres** | Perkecil ukuran PDF — mode kuat via Ghostscript (opsional), mode ringan bawaan; bisa kompres & unduh otomatis begitu file dipilih |
| **PDF→Gambar** | Render halaman ke PNG/JPG langsung di browser, hasil ZIP |
| **PDF→Teks** | Ekstrak semua teks PDF ke `.txt` |
| **Gambar→Teks** | OCR di browser — baca teks dari foto (SN, kode barang, label, screenshot) + crop area + tombol Salin |

Tampilan modern dengan tema terang/gelap (ikon bulu mata di kanan atas, tersimpan otomatis).

## Instalasi

1. Pastikan Composer terpasang, lalu di folder proyek jalankan:

```bash
composer install
```

2. Pastikan `uploads/`, `output/`, dan `inbox/` dapat ditulisi web server.

3. Jalankan lewat XAMPP: letakkan folder `mergefile` di `htdocs`, lalu buka
   `http://localhost/mergefile`.

Atau server bawaan PHP:

```bash
php -S 0.0.0.0:8000 -t .
```

### Menjalankan otomatis di Windows

- Klik dua kali `start-merge.bat` (sesuaikan baris `set PHP_EXE=...` bila `php.exe` di lokasi lain).
- Agar jalan saat login Windows, buat shortcut `start-merge.bat` ke folder Startup (`Win+R` → `shell:startup`).

## Transfer HP → Laptop

Buka tool **Transfer** di laptop. Ada tiga cara akses, semuanya tanpa WhatsApp/email/kabel:

### 1. WiFi lokal (bawaan)
Pindai QR dengan kamera iPhone atau ketik URL WiFi di Safari.
Laptop dan HP harus satu WiFi. Saat Windows Firewall bertanya, pilih **Izinkan akses** (jaringan pribadi).

### 2. Link internet sementara — Cloudflare tunnel
Klik **Buat link internet** di tool Transfer. Setelah ±10 detik muncul QR + link
`https://…trycloudflare.com` — HP bisa mengirim file dari **data seluler / jaringan mana pun**.

- **Cukup pindai QR, tidak ada yang perlu diketik atau disalin.** Kunci akses tertanam di dalam
  QR (`?k=…`), jadi HP langsung terbuka ke halaman Transfer dalam keadaan sudah terotorisasi.
- PIN 6 digit hanya jadi **cadangan** — ditampilkan di laptop untuk kasus QR tidak bisa dipindai
  (mis. kamera HP gagal memindai). Tidak perlu dihafal.
- Tidak perlu akun, tidak perlu instal apa pun di HP.
- Link hanya hidup selama tombol **Matikan link** belum ditekan (dan laptop menyala + online).
- Pengaman: kunci 128-bit acak, dan setelah 25× PIN salah link otomatis **terkunci** sampai Anda
  matikan & buat link baru (mencegah tebak PIN dari internet).
- Berbasis `bin/cloudflared.exe` (bawaan repo, tanpa CDN).

### 3. Jaringan pribadi Tailscale (opsional, permanen & privat)
Instal Tailscale di laptop + HP (login akun sama), lalu buka tool Transfer lewat IP Tailscale
(muncul sebagai tombol **Tailscale: 100.x.x.x** di halaman tool).
HP bisa akses dari jaringan apa pun, kapan pun, tanpa link publik & tanpa PIN.

Unduh Tailscale: <https://tailscale.com/download>

> Detail teknis: permintaan lewat link internet selalu melewati `cloudflared` dari `127.0.0.1`,
> sehingga kunci PIN dibedakan berdasarkan **Host** (alamat situs), bukan alamat IP — agar link publik
> tetap terkunci meski request-nya datang dari localhost.

Opsi: centang **Kompres gambar otomatis** agar foto besar diubah ke JPG maks. 1920px sebelum dikirim
(jauh lebih cepat) — matikan bila ingin file asli 100% utuh.

## Kompres kuat (opsional)

Mode **Kompres & Unduh** memakai Ghostscript:

1. Unduh & instal Ghostscript sekali dari https://www.ghostscript.com/
2. Buka tool Kompres, klik **Cari** — lokasi `gswin64c.exe` terdeteksi otomatis.
3. Bila tidak terdeteksi, isi lokasi file `.exe`-nya secara manual.

Tanpa Ghostscript, gunakan **Optimasi Ringan** (re-save PDF, tanpa instal apa pun).
Secara bawaan file dikompres & diunduh **otomatis** begitu dipilih (bisa dicentang mati).

## Gambar → Teks (OCR)

Foto label/kotak barang → tarik kotak di area teks → klik **Baca Teks** → **Salin**.

- Mesin OCR **Tesseract.js** (WASM) berjalan **di browser** — tanpa instalasi, tanpa internet,
  gambar tidak pernah dikirim ke mana pun. File inti + data bahasa Indonesia/Inggris disimpan
  lokal di `assets/vendor/tesseract/` (sekali unduh, ±21MB).
- Opsional: **Perjelas otomatis** (grayscale + rentang kontras) menaikkan akurasi foto agak buram.
- Pilihan bahasa: Indo + Inggris / Indonesia / Inggris.
- Akurasi terbaik: foto dekat, teks jelas, atau gunakan crop agar hanya teks yang dibaca.

## Struktur

```
index.php        → shell + navigasi tool
pages/           → tampilan tiap tool
api/             → endpoint pemrosesan PDF
lib/             → helpers + PdfProcessor (FPDI/FPDF)
assets/css|js    → design system + JS (dark mode, toast, drag & drop)
assets/vendor/   → pdf.js + jszip + qrcode + tesseract.js (lokal, tanpa CDN)
bin/             → cloudflared.exe untuk link internet (resmi Cloudflare, untuk tool Transfer)
tests/           → skrip uji (gen.php, verify.php)
uploads|output|inbox/ → ditolak akses web, dibersihkan otomatis >6 jam (inbox tidak dibersihkan)
```

## Uji cepat

```bash
php tests/gen.php      # buat PDF & gambar contoh di output/tests/
php tests/verify.php output/tests/sample-3hal.pdf
```

## Catatan keamanan

- Dirancang untuk pemakaian **lokal** (localhost / LAN terpercaya).
- Upload divalidasi lewat ekstensi + deteksi MIME (fileinfo); nama acak; folder `uploads/`, `output/`, `inbox/`, `vendor/`, `tests/` ditolak akses web.
- File masuk tool Transfer disimpan dengan **nama acak tanpa ekstensi** + metadata `.meta` (tidak bisa dieksekusi), bisa dihapus dari UI.
- Saat **link internet aktif**, semua API Transfer (kirim, daftar, unduh, hapus) berada di balik **PIN 6 digit** (sesi). Bypass hanya untuk permintaan dari browser laptop sendiri (localhost/IP lokal/Tailscale). PIN baru dibuat tiap kali link dibuat.
- File hasil & upload lama dibersihkan otomatis setiap 6 jam.
- Batas ukuran mengikuti `upload_max_filesize` PHP (mis. 40 MB di XAMPP) — ubah di `php.ini` bila perlu.

# REDESIGN BLUEPRINT & SYSTEM SPECIFICATION: LUMERO KALIBUNDER GRAND OPENING
**Technical Specifications, UI/UX Architecture, Logic Flow, & Marketing Copywriting**
**Focus:** Online-to-Offline (O2O) Traffic, Impulse Generation, & Frictionless Gamification.

---

## I. STRATEGIC CONCEPTS & ARCHITECTURAL DECISIONS

1. **Horizontal Slot-Machine Roulette:**
   Menggunakan desain UI *Slot-Machine* horizontal (bergeser ke samping) sesuai referensi gambar, menggantikan roda putar konvensional. Ini memberikan kesan modern, *rewarding*, dan lebih terintegrasi dengan layar *smartphone*.
2. **Zero-Loss Gamification (100% Win Rate):**
   Setiap tarikan *roulette* pasti mendapatkan hadiah. Tidak ada kata "Coba Lagi". 
3. **Visual Hype & Zero-Stock Protection:**
   Hadiah bernilai tinggi (Handphone, Paket Ayam 1 Ekor) akan selalu muncul di *slot-machine* untuk memicu *desire*. Namun, jika stok habis di backend, sistem probabilitas akan mengunci *chance* ke `0%`.
4. **Copywriting Manipulatif (Gimmick Marketing):**
   Seluruh narasi dibuat *non-teknis* dan seolah-olah sistem memberikan "hak istimewa", bukan "meminta data". Data (Nomor HP) diposisikan sebagai "Kunci Brankas" untuk mengamankan hadiah, bukan formulir pendaftaran.

---

## II. UI/UX REDESIGN STRUCTURE (`member/index.php`)

### A. Top Navigation Bar (Navbar)
* **Desain:** *Borderless* / menyatu dengan *background* (tanpa garis bawah). 
* **Layout:**
  * **Tengah:** Logo Lumero.
  * **Kanan:** Tombol login elegan (misal: *outline button* "Masuk / Cek Tiket").
* **Fungsi:** Mengarahkan user ke alur login reguler (`login.php?source=organic`). Jika berhasil masuk, akan diarahkan langsung ke Dashboard Member.

### B. Hero Section (Horizontal Slot Roulette)
* **Visual:** Mesin slot horizontal. Kotak-kotak hadiah bergeser cepat dari kanan ke kiri.
* **Hadiah Event (Bisa Disesuaikan di Admin):**
  1. Es Krim Lumero
  2. Paket Ayam 1 Ekor
  3. Paket Ayam + Saos Favorit
  4. Tumbler Eksklusif
  5. Handphone
* **Copywriting:**
  * **Headline:** *"Kejutan Poin Hadiah! ✨"* (Atau variasi: *"Raih Kejutan Spesial Kalibunder! ✨"*)
  * **Sub-headline:** *"Pesananmu telah dikonversi. Undi roulette sekarang untuk mengamankan hadiah kejutanmu!"*
  * **Tombol Aksi:** *"⚡ Putar Roulette Sekarang"*

### C. Supporting Content Sections (Di Bawah Hero)
1. **Section 1: Live Social Proof (Ticker/Marquee)**
   * Menampilkan popup/teks berjalan fiktif/real-time untuk memicu FOMO. 
   * *Copy: "0812-xxxx-9912 baru saja mengamankan Paket Ayam + Saos Favorit! 🍗"*
2. **Section 2: Temptation Gallery (Produk Unggulan)**
   * Grid foto sinematik produk unggulan Lumero. Tanpa harga. Hanya visual yang menggugah selera.
   * *Copy: "Rasa premium yang menanti Anda di outlet terbaru kami. Buktikan sendiri."*
3. **Section 3: Location & Scarcity (Info Outlet Kalibunder)**
   * Menampilkan peta (Google Maps embed), jam operasional, dan penghitung waktu mundur event.
   * *Copy: "Kejutan ini akan hangus dalam waktu [02:14:59]. Temukan kami di Kalibunder."*

---

## III. LOGIC FLOW ARCHITECTURE & SYSTEM INTEGRATION

### Flow A: Login Reguler (Navbar) & Klaim Struk Non-Event
* User login via navbar atau *scan* QR struk (`?claim=`).
* Dialihkan ke `login.php`.
* **Output:** Masuk ke Dashboard Utama (`member/dashboard.php`). Saldo bertambah jika dari struk.

### Flow B: Event Roulette Flow (Gimmick Flow)
1. User klik *"⚡ Putar Roulette Sekarang"*. Mesin berputar tanpa meminta login (Frictionless).
2. Mendapatkan Hadiah (misal: Es Krim).
3. **Pop-up Kemenangan (The Hook):** 
   * *Copy:* *"Selamat! 1 [Nama Hadiah] resmi menjadi milik Anda. Ke nomor WhatsApp mana kami harus menitipkan tiket pengambilannya?"*
   * User memasukkan Nomor HP.
4. **OTP Verification (Friction Shifting):**
   * *Copy:* *"Satu langkah terakhir. Masukkan 6 digit kunci brankas yang kami kirimkan ke WhatsApp Anda agar tiket tidak diklaim orang lain."*
5. **Post-OTP Branching:**
   * Jika sukses, sistem mencatat tiket di `reward_claims`.
   * User diarahkan ke **Halaman Hadiah Khusus (`member/reward-claim.php`)** yang menampilkan QR Code. Halaman ini juga bisa diakses dari Dashboard nanti.

---

## IV. RESOLUSI CELAH LOGIKA (HOLES)

| No | Celah Logika (Hole) | Solusi yang Disepakati (Benang Merah) |
| :--- | :--- | :--- |
| 1. | **Gacha Rerolling (Incognito Abuse):** User bisa memutar roda berkali-kali di mode Incognito sebelum memasukkan nomor HP untuk mengincar Handphone. | **Dibiarkan secara Frontend, Dijaga ketat di Backend.** Karena alur harus *frictionless*, kita biarkan mereka memutar roda berkali-kali. Namun admin akan mengunci *chance* barang mahal (Handphone) ke `0%` secara *default* / otomatis saat stok habis. Mereka hanya akan membuang waktu mendapat Es Krim/Tumbler berulang kali, yang mana ujungnya hanya bisa diklaim 1x per Nomor HP. |
| 2. | **Bentrok Alur Login:** Bagaimana `login.php` tahu user datang dari Navbar vs Roulette? | **Tracking Parameter.** Tombol Navbar menggunakan `login.php?source=organic`. Sistem Roulette akan menggunakan *session* atau parameter `login.php?source=event_kalibunder`. |
| 3. | **Greedy Claiming:** 1 Nomor HP mencoba klaim hadiah Roulette 2x. | **Manipulative Redirect.** Jika nomor yang dimasukkan sudah pernah klaim hadiah Kalibunder, mereka tidak akan mendapat QR baru. Mereka dilempar langsung ke Dashboard dengan notifikasi manipulatif: *"Sistem mendeteksi Anda sudah memiliki tiket VIP Kalibunder di dompet Anda. Jangan serakah, simpan keberuntungan untuk kunjungan berikutnya. 😉"* |

---
*Blueprint disahkan dan dikunci. Siap untuk tahap implementasi.*

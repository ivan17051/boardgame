      <div class="score-card guide-card">
        <h2>Panduan Mahjong</h2>
        <p class="small text-secondary">Ringkasan aturan turnamen Mahjong.</p>

        <h3>Cara bermain</h3>
        <ul>
          <li>Susun tangan menang dari 4 set dan 1 pasangan (umumnya 14 ubin).</li>
          <li>Giliran berjalan berlawanan arah jarum jam: ambil satu ubin dari dinding, lalu buang satu ubin ke river.</li>
          <li><strong>Pong:</strong> klaim buangan untuk membentuk tiga ubin identik.</li>
          <li><strong>Kong:</strong> klaim buangan untuk membentuk empat ubin identik, lalu ambil ubin pengganti dari sea.</li>
          <li><strong>Chii:</strong> klaim buangan untuk membuat urutan, hanya dari pemain di sebelah kiri.</li>
          <li><strong>Ron / Hu:</strong> menang dengan mengklaim ubin buangan yang melengkapi tangan. <strong>Tsumo:</strong> menang dengan mengambil ubin pelengkap dari dinding.</li>
        </ul>

        <h3>Contoh set ubin</h3>
        <p class="small text-secondary mb-2">Setiap ilustrasi menunjukkan ubin yang membentuk satu kelompok.</p>
        <div class="guide-meld-examples" aria-label="Ilustrasi pasangan, urutan, pong, dan kong">
          <div class="guide-meld">
            <span class="guide-meld-label">Urutan<br /><small>(Chii)</small></span>
            <div class="guide-tiles" role="img" aria-label="2, 3, 4 bambu">
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou3.png') }}" alt="3 bambu" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou4.png') }}" alt="4 bambu" width="300" height="400" loading="lazy" decoding="async" />
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Triplet<br /><small>(Pong)</small></span>
            <div class="guide-tiles" role="img" aria-label="Tiga ubin 5 lingkaran">
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Pin5.png') }}" alt="5 lingkaran" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Pin5.png') }}" alt="5 lingkaran" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Pin5.png') }}" alt="5 lingkaran" width="300" height="400" loading="lazy" decoding="async" />
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Empat identik<br /><small>(Kong)</small></span>
            <div class="guide-tiles" role="img" aria-label="Empat ubin 8 karakter">
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man8.png') }}" alt="8 karakter" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man8.png') }}" alt="8 karakter" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man8.png') }}" alt="8 karakter" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man8.png') }}" alt="8 karakter" width="300" height="400" loading="lazy" decoding="async" />
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Pasangan</span>
            <div class="guide-tiles" role="img" aria-label="Dua ubin naga putih">
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Haku.png') }}" alt="Naga putih" width="300" height="400" loading="lazy" decoding="async" />
              <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Haku.png') }}" alt="Naga putih" width="300" height="400" loading="lazy" decoding="async" />
            </div>
          </div>
        </div>

        <h3>Nilai set</h3>
        <p class="mb-2">Nilai tangan dasar = <strong>1 base point + total nilai set + bonus pola khusus</strong>. Simples adalah ubin angka 2–8; terminal adalah angka 1 dan 9; honor adalah ubin angin dan naga.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead>
              <tr><th>Set</th><th>Simples</th><th>Terminal &amp; Honor</th></tr>
            </thead>
            <tbody>
              <tr><td>Pasangan</td><td>0</td><td>+1</td></tr>
              <tr><td>Pong (tiga identik)</td><td>+1</td><td>+2</td></tr>
              <tr><td>Kong (empat identik)</td><td>+2</td><td>+4</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Bonus pola khusus</h3>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead>
              <tr><th>Pola</th><th>Bonus</th><th>Kriteria</th></tr>
            </thead>
            <tbody>
              <tr><td>All Simples</td><td>+1</td><td>Tidak ada terminal atau honor.</td></tr>
              <tr><td>Closed Hand</td><td>+2</td><td>Menang tanpa memanggil ubin dari lawan.</td></tr>
              <tr><td>All Sequence</td><td>+2</td><td>Empat urutan dan satu pasangan; tanpa honor atau triplet.</td></tr>
              <tr><td>All Triplets</td><td>+6</td><td>Empat triplet dan satu pasangan.</td></tr>
              <tr><td>Half-Flush</td><td>+6</td><td>Satu jenis ubin angka dan ubin honor.</td></tr>
              <tr><td>Seven Pairs</td><td>+7</td><td>Tujuh pasangan berbeda.</td></tr>
              <tr><td>Full Flush</td><td>+13</td><td>Satu jenis ubin angka tanpa honor.</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Pembayaran saat menang</h3>
        <ul>
          <li><strong>Ron / Hu:</strong> pemenang menerima 2× nilai tangan dari pemain yang membuang ubin kemenangan; pemain lain tidak membayar.</li>
          <li><strong>Tsumo / Zi Mo:</strong> pemenang menerima 1× nilai tangan dari masing-masing pemain lain.</li>
        </ul>
        <h3>Contoh tangan menang</h3>
        <p class="small text-secondary mb-2">Kelompokkan 4 set dan 1 pasangan seperti contoh berikut. Geser ke samping untuk melihat seluruh tangan di layar kecil.</p>
        <div class="guide-example">
          <h4>Ron / Hu</h4>
          <p class="example-caption">Dua pong simples, satu pong honor, satu urutan, dan satu pasangan honor.</p>
          <div class="guide-winning-hand" role="group" aria-label="Contoh tangan Ron: dua pong bambu, pong naga merah, urutan karakter, dan pasangan naga putih">
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man2.png') }}" alt="2 karakter" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man3.png') }}" alt="3 karakter" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Man4.png') }}" alt="4 karakter" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Urutan +0</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Haku.png') }}" alt="Naga putih" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Haku.png') }}" alt="Naga putih" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pasangan +1</small>
            </div>
          </div>
          <p class="example-score mb-0"><strong>1 base + 5 nilai set = 6.</strong> Ron membayar 2×6 = <strong>12 poin</strong>.</p>
        </div>
        <div class="guide-example">
          <h4>Tsumo / Zi Mo</h4>
          <p class="example-caption">Empat pong, satu pasangan, All Triplets, dan Closed Hand.</p>
          <div class="guide-winning-hand" role="group" aria-label="Contoh tangan Tsumo: dua pong bambu, pong naga merah, pong naga hijau, dan pasangan lingkaran">
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou2.png') }}" alt="2 bambu" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Sou7.png') }}" alt="7 bambu" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Chun.png') }}" alt="Naga merah" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Hatsu.png') }}" alt="Naga hijau" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Hatsu.png') }}" alt="Naga hijau" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Hatsu.png') }}" alt="Naga hijau" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Pin5.png') }}" alt="5 lingkaran" width="300" height="400" loading="lazy" decoding="async" /><img class="guide-tile" src="{{ asset('public/assets/img/mahjong/Pin5.png') }}" alt="5 lingkaran" width="300" height="400" loading="lazy" decoding="async" />
              </div>
              <small>Pasangan +0</small>
            </div>
          </div>
          <p class="example-score mb-0"><strong>1 base + 6 nilai set + 8 bonus (All Triplets 6, Closed Hand 2) = 15.</strong> Tsumo membayar 15 poin dari masing-masing 3 pemain, total <strong>45 poin</strong>.</p>
        </div>

        <h3>Format turnamen &amp; penalti</h3>
        <ul>
          <li>Permainan berlangsung sampai batas waktu 50 menit atau 2 putaran penuh. Skor awal setiap peserta adalah 0.</li>
          <li>Jika dinding habis tanpa pemenang, pemain Tenpai menerima 5 poin dari setiap pemain yang tidak Tenpai.</li>
          <li>Uma di akhir ronde: peringkat 1 sampai 4 mendapat +15, +5, −5, dan −15 poin.</li>
          <li>Panggilan Chi/Pong ilegal setelah set terbuka dapat menyebabkan Dead Hand: tidak bisa menang pada ronde itu.</li>
          <li>Merobohkan 7 ubin atau lebih dari dinding: penalti 5 poin kepada setiap pemain lain. Permainan lambat berulang dapat dikenai penalti serupa setelah peringatan.</li>
          <li>Jika beberapa pemain menyatakan Hu pada satu buangan, hanya pemain terdekat berikutnya sesuai urutan giliran yang menang.</li>
          <li>Kecurangan yang disengaja dapat menyebabkan diskualifikasi.</li>
        </ul>

        <p class="small text-secondary mb-3">Ilustrasi ubin: <a href="https://github.com/FluffyStuff/riichi-mahjong-tiles" target="_blank" rel="noopener noreferrer">FluffyStuff</a>.</p>
        <!-- <div class="guide-note">
          <strong>Catatan Chombo:</strong> dokumen menyebut dua ketentuan berbeda untuk klaim menang yang salah: penalti 10 poin kepada setiap lawan pada bagian pelanggaran, serta 30 poin dibagi di antara pemain lain pada bagian etiket. Konfirmasikan penerapan yang berlaku kepada panitia.
        </div>
        <p class="small text-secondary mt-3 mb-0">Aturan ini menggunakan House Rule Threefrogs dengan referensi Riichi yang disederhanakan. Hal yang belum diatur mengikuti Bamboo Tournament atau World Riichi Rules; keputusan penyelenggara bersifat final.</p> -->
      </div>
      <!-- <div class="score-card guide-card mt-3">
        <h2>Panduan tambahan: Belajar Mahjong</h2>
        <p class="small text-secondary">Sumber: MahjongGuide.pdf dari Toko Mahjong / PONG Mahjong Parlor Surabaya. Panduan ini memakai sistem poin tersendiri, berbeda dari aturan Bamboo di atas. Sepakati sistem yang digunakan sebelum bermain.</p>
        <a class="btn btn-sm btn-outline-success mb-3" href="{{ asset('public/assets/docs/MahjongGuide.pdf') }}" target="_blank" rel="noopener">Buka panduan asli (PDF)</a>

        <h3>Komponen &amp; cara bermain</h3>
        <ul>
          <li>Dimainkan oleh 4 orang. Tiga jenis ubin angka adalah bulat (Tongzi), karakter (Wanzi), dan bambu (Suozi), masing-masing bernomor 1 sampai 9.</li>
          <li>Ubin royal terdiri dari angin Timur, Selatan, Barat, Utara serta naga merah, hijau, dan putih. Terminal adalah ubin angka 1 dan 9.</li>
          <li>Ambil 1 ubin dan buang 1 ubin sampai ada yang menang. Tangan menang umumnya terdiri dari <strong>4 set dan 1 pasangan</strong>, dengan nilai minimal <strong>600 poin</strong>.</li>
          <li><strong>Chow:</strong> urutan 3 angka dalam jenis yang sama; hanya boleh mengklaim buangan pemain di sebelah kiri.</li>
          <li><strong>Pong:</strong> 3 ubin identik; dapat mengklaim buangan pemain mana pun.</li>
          <li><strong>Kong:</strong> 4 ubin identik; dapat berasal dari buangan pemain mana pun atau dikumpulkan sendiri.</li>
          <li>Jika tinggal menunggu 1 ubin untuk menang, klaim buangan dengan <strong>Hu</strong> atau ambil sendiri dari tembok dengan <strong>Zi Mo</strong>.</li>
        </ul>

        <h3>Pola &amp; nilai poin</h3>
        <p class="small text-secondary mb-2">Istilah ?logo? pada dokumen berarti jenis ubin angka. Nilai berikut mengikuti kelompok poin pada panduan asli.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead><tr><th>Poin</th><th>Pola</th><th>Kriteria</th></tr></thead>
            <tbody>
              <tr><td>300</td><td>Set Mulia</td><td>Triplet/quad naga atau angin sesuai tempat duduk.</td></tr>
              <tr><td>300</td><td>Petapa</td><td>Menang dengan tangan sepenuhnya tertutup atau sepenuhnya terbuka.</td></tr>
              <tr><td>300</td><td>Tengah Murni</td><td>Hanya ubin angka 2 sampai 8, tanpa royal.</td></tr>
              <tr><td>300</td><td>Urutan Murni (Pinhu)</td><td>Semua set berupa urutan, tanpa kartu mulia.</td></tr>
              <tr><td>300</td><td>Urutan Berantai</td><td>Dua urutan sejenis dengan angka akhir set pertama menjadi angka awal set kedua, misalnya 123 dan 345. Satu set hanya boleh dipakai dalam satu urutan berantai.</td></tr>
              <tr><td>600</td><td>Ujung Separuh</td><td>Setiap set dan pasangan mengandung terminal atau royal; minimal satu set berupa urutan.</td></tr>
              <tr><td>1.200</td><td>Triplet Murni</td><td>Semua set berupa triplet atau quad.</td></tr>
              <tr><td>1.200</td><td>Lima Serangkai</td><td>Mengandung kelima kelompok: bambu, bulat, karakter, naga, dan angin.</td></tr>
              <tr><td>1.200</td><td>Serupa Separuh</td><td>Hanya satu jenis ubin angka dan ubin royal.</td></tr>
              <tr><td>1.200</td><td>Urutan Mirip</td><td>Tiga urutan dengan angka sama pada ketiga jenis ubin, misalnya 234 karakter, 234 bulat, dan 234 bambu.</td></tr>
              <tr><td>1.200</td><td>Urutan Lengkap Murni</td><td>Memiliki urutan 123, 456, dan 789 dalam satu jenis ubin.</td></tr>
              <tr><td>2.400</td><td>Komodo (Naga Kecil)</td><td>Dua triplet/quad naga dan satu pasangan naga.</td></tr>
              <tr><td>2.400</td><td>Ujung Murni</td><td>Semua set yang dipakai untuk menang mengandung terminal.</td></tr>
              <tr><td>3.900</td><td>Tujuh Keajaiban</td><td>Tujuh pasangan berbeda; tidak boleh ada quad.</td></tr>
              <tr><td>5.400</td><td>Serupa Murni (Satu Warna)</td><td>Hanya satu jenis ubin angka.</td></tr>
              <tr><td>12.000</td><td>Nantaboga (3 Naga Besar)</td><td>Triplet/quad ketiga naga.</td></tr>
              <tr><td>12.000</td><td>Topan (4 Angin Besar/Kecil)</td><td>Empat triplet/quad angin, atau tiga triplet/quad angin dan satu pasangan angin.</td></tr>
              <tr><td>12.000</td><td>Royal Murni</td><td>Tangan hanya terdiri dari ubin angin dan naga.</td></tr>
              <tr><td>12.000</td><td>Terminal Murni</td><td>Hanya ubin angka 1 dan 9.</td></tr>
              <tr><td>12.000</td><td>Quad Murni</td><td>Memiliki empat quad.</td></tr>
              <tr><td>12.000</td><td>13 Rakyat</td><td>Ubin 1 dan 9 dari ketiga jenis, semua naga, dan semua angin, dengan satu ubin sebagai pasangan.</td></tr>
              <tr><td>12.000</td><td>7 Tangga Surga</td><td>Tujuh pasangan berurutan dalam satu jenis ubin.</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Cara pembayaran poin</h3>
        <ul>
          <li><strong>Hu:</strong> pemain yang membuang ubin kemenangan membayar nilai komposisi tangan. Dua pemain lainnya masing-masing membayar 300 poin.</li>
          <li>Contoh: Aming menang Hu senilai 2.400 dari Budi. Budi membayar 2.400, Caca dan Doni masing-masing 300; Aming menerima total <strong>3.000 poin</strong>.</li>
          <li><strong>Zi Mo:</strong> nilai poin dikalikan 2, lalu pembayaran dibagi rata kepada ketiga pemain lain.</li>
        </ul>
        <div class="guide-note">Nilai dan pembayaran di bagian ini adalah referensi panduan tambahan. Catat hasil sesuai sistem yang telah disepakati oleh semua pemain.</div>
      </div> -->

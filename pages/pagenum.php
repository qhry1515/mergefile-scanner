<div class="page-head">
  <h2>Nomor Halaman</h2>
  <p>Beri penomoran otomatis di setiap halaman PDF — atur posisi, format, font, ukuran, dan warna.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik file PDF di sini, atau klik untuk memilih</div>
  <div class="dz-sub">Penomoran ditempel langsung ke setiap halaman</div>
</div>
<input type="file" id="fileInput" accept=".pdf,application/pdf" hidden>

<div class="filechip" id="fileChip" hidden>
  <div class="fc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l4 4v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v4h4"/></svg></div>
  <div class="grow">
    <div class="fc-name" id="fcName"></div>
    <div class="muted small" id="fcMeta"></div>
  </div>
  <button class="btn btn-outline btn-sm" id="btnChange" type="button">Ganti</button>
  <button class="btn btn-danger-soft btn-sm" id="btnClear" type="button">Hapus</button>
</div>

<div class="toolbar" id="toolbar" hidden>
  <div class="field">
    <label for="pos">Posisi</label>
    <select id="pos">
      <option value="bc" selected>Bawah — tengah</option>
      <option value="bl">Bawah — kiri</option>
      <option value="br">Bawah — kanan</option>
      <option value="tc">Atas — tengah</option>
      <option value="tl">Atas — kiri</option>
      <option value="tr">Atas — kanan</option>
    </select>
  </div>
  <div class="field">
    <label for="format">Format</label>
    <select id="format">
      <option value="{n}/{t}" selected>5/20</option>
      <option value="{n}">5</option>
      <option value="Halaman {n} dari {t}">Halaman 5 dari 20</option>
      <option value="Page {n} of {t}">Page 5 of 20</option>
      <option value="{n} — {t}">5 — 20</option>
    </select>
  </div>
  <div class="field">
    <label for="font">Font</label>
    <select id="font">
      <option value="Helvetica" selected>Helvetica</option>
      <option value="Times">Times</option>
      <option value="Courier">Courier</option>
    </select>
  </div>
  <div class="field">
    <label>Gaya</label>
    <div class="seg">
      <input type="radio" name="style" id="stN" value="" checked>
      <label for="stN">Normal</label>
      <input type="radio" name="style" id="stB" value="B">
      <label for="stB">Tebal</label>
      <input type="radio" name="style" id="stI" value="I">
      <label for="stI">Miring</label>
    </div>
  </div>
  <div class="field">
    <label for="size">Ukuran (pt)</label>
    <input type="number" id="size" value="10" min="6" max="40" step="1" style="width:90px">
  </div>
  <div class="field">
    <label for="color">Warna</label>
    <input type="color" id="color" value="#333333">
  </div>
  <div class="field">
    <label for="margin">Margin (mm)</label>
    <input type="number" id="margin" value="12" min="2" max="50" step="1" style="width:90px">
  </div>
  <div class="field">
    <label for="start">Mulai dari</label>
    <input type="number" id="start" value="1" min="0" max="9999" step="1" style="width:90px">
  </div>
  <div class="field grow" style="min-width:200px">
    <label for="outName">Nama file hasil</label>
    <input type="text" id="outName" value="bernomor" spellcheck="false">
  </div>
</div>

<div id="empty" class="empty">Belum ada file. Pilih PDF untuk menambahkan nomor halaman.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary"></div>
  <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Terapkan &amp; Unduh</button>
</div>
<div class="progress" id="progress" hidden><i id="progressBar"></i></div>

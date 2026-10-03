<div class="page-head">
  <h2>Split, Ekstrak &amp; Hapus Halaman</h2>
  <p>Bagi PDF: centang halaman yang mau diambil atau dibuang, urutkan dengan drag, lalu unduh hasilnya.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik file PDF di sini, atau klik untuk memilih</div>
  <div class="dz-sub">Satu PDF per proses · semua diproses lokal</div>
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
    <label>Mode</label>
    <div class="seg">
      <input type="radio" name="smode" id="modeExtract" value="extract" checked>
      <label for="modeExtract">Ekstrak terpilih</label>
      <input type="radio" name="smode" id="modeDelete" value="delete">
      <label for="modeDelete">Hapus terpilih</label>
    </div>
  </div>
  <div class="field">
    <label>Seleksi cepat</label>
    <div class="row">
      <button class="btn btn-outline btn-sm" id="btnAll" type="button">Pilih semua</button>
      <button class="btn btn-outline btn-sm" id="btnNone" type="button">Lepas semua</button>
    </div>
  </div>
  <div class="field grow" style="min-width:200px">
    <label for="outName">Nama file hasil</label>
    <input type="text" id="outName" value="hasil-split" spellcheck="false">
  </div>
</div>

<div class="progress" id="progress" hidden><i id="progressBar"></i></div>
<div class="hint-bar" id="hintBar" hidden>
  <span>Centang halaman untuk diproses</span><span>·</span>
  <span>Tarik kartu untuk mengubah urutan hasil</span>
</div>

<div id="grid" class="grid sortable" hidden></div>
<div id="empty" class="empty">Belum ada file. Pilih PDF untuk melihat daftar halamannya.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary"></div>
  <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Ekstrak &amp; Unduh</button>
</div>

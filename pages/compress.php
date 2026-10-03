<div class="page-head">
  <h2>Kompres PDF</h2>
  <p>Perkecil ukuran PDF. Mode kuat memakai Ghostscript (sekali instal), mode ringan langsung jalan tanpa apa pun.</p>
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

<div class="notice" id="gsStatus" style="margin-top:14px">
  <div class="spinner" style="margin-top:3px"></div>
  <div>Mendeteksi Ghostscript…</div>
</div>

<div class="hint-bar" style="margin-top:10px">
  <label class="row" style="cursor:pointer;gap:8px">
    <input type="checkbox" id="autoRun" checked style="width:16px;height:16px;accent-color:var(--accent)">
    <span>Kompres &amp; unduh otomatis begitu file dipilih</span>
  </label>
</div>

<div class="toolbar" id="toolbar" hidden>
  <div class="field">
    <label>Tingkat kompresi</label>
    <div class="seg">
      <input type="radio" name="preset" id="psScreen" value="screen">
      <label for="psScreen">Kuat (layar)</label>
      <input type="radio" name="preset" id="psEbook" value="ebook" checked>
      <label for="psEbook">Sedang (e-book)</label>
      <input type="radio" name="preset" id="psPrinter" value="printer">
      <label for="psPrinter">Ringan (cetak)</label>
    </div>
  </div>
  <div class="field grow" style="min-width:220px">
    <label for="gsPath">Lokasi Ghostscript (opsional)</label>
    <div class="row">
      <input type="text" id="gsPath" class="grow" placeholder="C:\Program Files\gs\...\gswin64c.exe" spellcheck="false">
      <button class="btn btn-outline btn-sm" id="btnGsFind" type="button">Cari</button>
    </div>
  </div>
  <div class="field grow" style="min-width:200px">
    <label for="outName">Nama file hasil</label>
    <input type="text" id="outName" value="terkompres" spellcheck="false">
  </div>
</div>

<div id="empty" class="empty">Belum ada file. Pilih PDF untuk dikompres.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary">Ukuran asli dibandingkan setelah diproses.</div>
  <div class="row wrap">
    <button id="lightBtn" class="btn btn-outline btn-lg" type="button"><span class="spinner"></span>Optimasi Ringan</button>
    <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Kompres &amp; Unduh</button>
  </div>
</div>
<div class="progress" id="progress" hidden><i id="progressBar"></i></div>

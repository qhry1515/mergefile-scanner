<div class="page-head">
  <h2>PDF ke Gambar</h2>
  <p>Render tiap halaman PDF menjadi PNG/JPG langsung di browser — file tidak pernah keluar dari komputer Anda.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik file PDF di sini, atau klik untuk memilih</div>
  <div class="dz-sub">Hasil berupa gambar per halaman (ZIP bila lebih dari satu)</div>
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
  <div class="field grow" style="min-width:180px">
    <label for="range">Halaman</label>
    <input type="text" id="range" placeholder="kosong = semua · contoh: 1-3,7" spellcheck="false">
  </div>
  <div class="field">
    <label>Resolusi</label>
    <div class="seg">
      <input type="radio" name="scale" id="sc1" value="1">
      <label for="sc1">1×</label>
      <input type="radio" name="scale" id="sc2" value="2" checked>
      <label for="sc2">2×</label>
      <input type="radio" name="scale" id="sc3" value="3">
      <label for="sc3">3×</label>
    </div>
  </div>
  <div class="field">
    <label>Format</label>
    <div class="seg">
      <input type="radio" name="fmt" id="fmtJpg" value="jpeg" checked>
      <label for="fmtJpg">JPG</label>
      <input type="radio" name="fmt" id="fmtPng" value="png">
      <label for="fmtPng">PNG</label>
    </div>
  </div>
  <div class="field" id="qualityField">
    <label for="quality">Kualitas JPG: <span id="qualityVal">80</span></label>
    <input type="range" id="quality" min="40" max="95" value="80">
  </div>
  <div class="field grow" style="min-width:180px">
    <label for="outName">Nama hasil</label>
    <input type="text" id="outName" value="halaman" spellcheck="false">
  </div>
</div>

<div id="empty" class="empty">Belum ada file. Pilih PDF untuk melihat opsinya.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary"></div>
  <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Render &amp; Unduh</button>
</div>
<div class="progress" id="progress" hidden><i id="progressBar"></i></div>
<div class="hint-bar" id="progressLabel" hidden></div>

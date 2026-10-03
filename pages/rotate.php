<div class="page-head">
  <h2>Rotasi Halaman</h2>
  <p>Putar seluruh halaman atau halaman tertentu (90° / 180° / 270°) lalu unduh PDF baru.</p>
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
    <label>Putar semua</label>
    <div class="row">
      <button class="btn btn-outline btn-sm" id="rotAll90" type="button">↻ +90°</button>
      <button class="btn btn-outline btn-sm" id="rotAll180" type="button">↻ +180°</button>
      <button class="btn btn-outline btn-sm" id="rotAll270" type="button">↻ +270°</button>
      <button class="btn btn-outline btn-sm" id="rotReset" type="button">Reset</button>
    </div>
  </div>
  <div class="field grow" style="min-width:200px">
    <label for="outName">Nama file hasil</label>
    <input type="text" id="outName" value="diputar" spellcheck="false">
  </div>
</div>

<div class="progress" id="progress" hidden><i id="progressBar"></i></div>
<div class="hint-bar" id="hintBar" hidden>
  <span>Klik "Putar" pada kartu untuk memutar halaman itu saja</span><span>·</span>
  <span id="rotCount"></span>
</div>

<div id="grid" class="grid" hidden></div>
<div id="empty" class="empty">Belum ada file. Pilih PDF untuk melihat daftar halamannya.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary"></div>
  <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Terapkan &amp; Unduh</button>
</div>

<div class="page-head">
  <h2>Gabung PDF &amp; Gambar</h2>
  <p>Susun beberapa file menjadi satu dokumen PDF. Tarik kartu untuk mengatur urutan, lalu unduh hasilnya.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik &amp; lepas file di sini, atau klik untuk memilih</div>
  <div class="dz-sub">PDF, JPG, PNG, GIF · maksimal <span id="dzLimit">40 MB</span> per file · <span id="dzCount">60</span> file</div>
</div>
<input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,application/pdf,image/jpeg,image/png,image/gif" hidden>

<div id="settings" class="toolbar" hidden>
  <div class="field grow" style="min-width:200px">
    <label for="outName">Nama file hasil</label>
    <input type="text" id="outName" value="gabungan" spellcheck="false">
  </div>
  <div class="field">
    <label>Ukuran halaman gambar</label>
    <div class="seg">
      <input type="radio" name="imgmode" id="imgA4" value="a4" checked>
      <label for="imgA4">Pas A4</label>
      <input type="radio" name="imgmode" id="imgReal" value="real">
      <label for="imgReal">Ukuran asli</label>
    </div>
  </div>
  <div class="field" id="marginField">
    <label for="margin">Margin (mm)</label>
    <input type="number" id="margin" value="10" min="0" max="60" step="1">
  </div>
</div>

<div class="hint-bar" id="hintBar" hidden>
  <span>Tarik kartu untuk mengatur urutan</span>
  <span>·</span>
  <span id="hintStats"></span>
</div>

<div id="thumbs" class="grid sortable" hidden></div>
<div id="empty" class="empty">Belum ada file. Tambahkan PDF atau gambar untuk memulai.</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary"></div>
  <button id="mergeBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Gabung &amp; Unduh</button>
</div>
<div class="progress" id="progress" hidden><i id="progressBar"></i></div>

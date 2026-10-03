<div class="page-head">
  <h2>Gambar → Teks (OCR)</h2>
  <p>Baca teks dari foto/gambar — nomor SN, kode barang, label, screenshot — langsung di browser. Tidak ada gambar yang dikirim ke mana pun.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-6 6-2-2-5 4"/></svg></div>
  <div class="dz-main">Tarik gambar ke sini, atau klik untuk pilih</div>
  <div class="dz-sub">JPG · PNG · WEBP · BMP — foto label, kotak barang, atau screenshot</div>
</div>
<input type="file" id="fileInput" accept="image/*" hidden>

<div class="filechip" id="fileChip" hidden>
  <div class="fc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-6 6-2-2-5 4"/></svg></div>
  <div class="grow">
    <div class="fc-name" id="fcName"></div>
    <div class="muted small" id="fcMeta"></div>
  </div>
  <button class="btn btn-outline btn-sm" id="btnChange" type="button">Ganti</button>
  <button class="btn btn-danger-soft btn-sm" id="btnClear" type="button">Hapus</button>
</div>

<div class="panel" id="canvasPanel" hidden style="margin-top:14px">
  <div class="muted small" style="margin-bottom:8px">
    Tarik kotak di atas area yang berisi teks agar hasil lebih akurat (opsional). Tanpa kotak, seluruh gambar dibaca.
  </div>
  <canvas id="ocrCanvas" style="display:block;max-width:100%;height:auto;touch-action:none;cursor:crosshair;border-radius:10px;background:var(--surface-2)"></canvas>
</div>

<div class="toolbar" id="toolbar" hidden>
  <div class="field">
    <label>Bahasa teks</label>
    <div class="seg">
      <input type="radio" name="ocrlang" id="olIndEng" value="ind+eng" checked>
      <label for="olIndEng">Indo + Inggris</label>
      <input type="radio" name="ocrlang" id="olInd" value="ind">
      <label for="olInd">Indonesia</label>
      <input type="radio" name="ocrlang" id="olEng" value="eng">
      <label for="olEng">Inggris</label>
    </div>
  </div>
  <div class="field">
    <label>Pengaturan</label>
    <label class="row" style="cursor:pointer;gap:8px;font-size:13px;color:var(--muted)">
      <input type="checkbox" id="enhance" checked style="width:16px;height:16px;accent-color:var(--accent)">
      <span>Perjelas otomatis (grayscale + kontras)</span>
    </label>
  </div>
</div>

<div class="actions" id="actions" hidden>
  <div class="summary" id="summary">Siap membaca gambar.</div>
  <div class="row wrap">
    <button id="ocrBtn" class="btn btn-outline btn-lg" type="button"><span class="spinner"></span>Reset Crop</button>
    <button id="runBtn" class="btn btn-primary btn-lg" type="button"><span class="spinner"></span>Baca Teks</button>
  </div>
</div>

<div class="progress" id="progress" hidden><i id="progressBar"></i></div>
<div class="hint-bar" id="ocrStatus" hidden></div>

<div class="panel" id="resultPanel" hidden style="margin-top:14px">
  <div class="row wrap" style="justify-content:space-between;align-items:center">
    <div class="row" style="gap:8px">
      <b>Hasil teks</b>
      <span class="muted small" id="charCount">0 karakter</span>
    </div>
    <div class="row" style="gap:8px">
      <button id="copyBtn" class="btn btn-primary btn-sm" type="button">Salin</button>
      <button id="clearBtn" class="btn btn-outline btn-sm" type="button">Kosongkan</button>
    </div>
  </div>
  <textarea id="ocrResult" rows="9" placeholder="Hasil teks muncul di sini — bisa diedit sebelum disalin." spellcheck="false" style="margin-top:10px;width:100%;resize:vertical"></textarea>
</div>

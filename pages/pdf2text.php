<div class="page-head">
  <h2>PDF ke Teks</h2>
  <p>Ekstrak isi teks dari PDF secara lokal — tanpa unggahan ke layanan mana pun.</p>
</div>

<div class="dropzone" id="drop">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik file PDF di sini, atau klik untuk memilih</div>
  <div class="dz-sub">Hasil teks muncul di bawah setelah selesai diproses</div>
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

<div class="progress" id="progress" hidden><i id="progressBar"></i></div>

<div id="result" hidden>
  <div class="actions" style="margin-top:16px">
    <div class="summary" id="summary"></div>
    <div class="row wrap">
      <button id="copyBtn" class="btn btn-outline" type="button">Salin teks</button>
      <button id="dlBtn" class="btn btn-primary" type="button">Unduh .txt</button>
    </div>
  </div>
  <textarea id="out" class="preview" readonly spellcheck="false" placeholder="Teks hasil ekstraksi…"></textarea>
</div>

<div id="empty" class="empty">Belum ada file. Pilih PDF untuk mengekstrak teksnya.</div>

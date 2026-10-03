(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const chip = document.getElementById('fileChip');
  const toolbar = document.getElementById('toolbar');
  const empty = document.getElementById('empty');
  const actions = document.getElementById('actions');
  const summary = document.getElementById('summary');
  const runBtn = document.getElementById('runBtn');
  const lightBtn = document.getElementById('lightBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const gsStatus = document.getElementById('gsStatus');
  const gsPath = document.getElementById('gsPath');
  const autoRun = document.getElementById('autoRun');

  let currentFile = null;
  let gsFound = null;
  let processing = false;
  let detectP = null;

  function ensureDetect() {
    if (!detectP) detectP = detect();
    return detectP;
  }

  App.initSingleFile({
    drop: drop,
    input: input,
    chip: chip,
    nameEl: document.getElementById('fcName'),
    metaEl: document.getElementById('fcMeta'),
    btnChange: document.getElementById('btnChange'),
    btnClear: document.getElementById('btnClear'),
    onFile: async function (f) {
      currentFile = f;
      toolbar.hidden = false;
      actions.hidden = false;
      empty.hidden = true;
      summary.textContent = App.fmtSize(f.size) + ' → diproses…';
      try {
        const doc = await App.loadPdf(f);
        document.getElementById('fcMeta').textContent = App.fmtSize(f.size) + ' · ' + doc.numPages + ' halaman';
        doc.destroy();
      } catch (e) {
        document.getElementById('fcMeta').textContent = App.fmtSize(f.size);
      }
      if (autoRun.checked && !processing) {
        await ensureDetect();
        process(!gsFound);
      }
    },
    onClear: function () {
      currentFile = null;
      toolbar.hidden = true;
      actions.hidden = true;
      empty.hidden = false;
    }
  });

  function renderStatus() {
    if (gsFound) {
      gsStatus.className = 'notice ok';
      gsStatus.innerHTML = '<div><b>Ghostscript terdeteksi.</b> Kompres kuat siap dipakai.<br><code id="gsCode"></code></div>';
      document.getElementById('gsCode').textContent = gsFound;
      runBtn.disabled = false;
    } else {
      gsStatus.className = 'notice warn';
      gsStatus.innerHTML = '<div><b>Ghostscript belum ada (opsional).</b> Kompres kuat butuh Ghostscript — unduh sekali dari ' +
        '<a href="https://www.ghostscript.com/" target="_blank" rel="noopener">ghostscript.com</a>, instal, lalu klik ' +
        '"Cari". Tanpa itu, gunakan <b>Optimasi Ringan</b> yang tetap berjalan.</div>';
      runBtn.disabled = true;
    }
  }

  async function detect() {
    try {
      const res = await fetch('api/compress.php?detect=1');
      const j = await res.json();
      gsFound = j.gs || null;
      if (j.configured) gsPath.value = j.configured;
    } catch (e) {
      gsFound = null;
    }
    renderStatus();
  }

  document.getElementById('btnGsFind').addEventListener('click', async function () {
    const btn = this;
    App.setLoading(btn, true, 'Mencari…');
    gsPath.value = '';
    try {
      const fd = new FormData();
      fd.append('gs_path', '');
      await fetch('api/compress.php?config=1', { method: 'POST', body: fd });
    } catch (e) {}
    await detect();
    App.setLoading(btn, false);
    App.toast(gsFound ? 'Ghostscript ditemukan.' : 'Belum ditemukan — isi lokasi manual.', gsFound ? 'success' : 'error');
  });

  async function process(light) {
    if (processing) return;
    if (!currentFile) {
      App.toast('Pilih PDF dulu.', 'error');
      return;
    }
    if (!light && !gsFound) {
      App.toast('Ghostscript belum tersedia — gunakan Optimasi Ringan.', 'error');
      return;
    }
    processing = true;
    const fname = App.safeName(document.getElementById('outName').value || 'terkompres', 'pdf');
    const fd = new FormData();
    fd.append('file', currentFile, currentFile.name);
    fd.append('name', fname);
    fd.append('gs_path', gsPath.value.trim());
    if (light) {
      fd.append('light', '1');
    } else {
      fd.append('preset', document.querySelector('input[name="preset"]:checked').value);
    }

    const btn = light ? lightBtn : runBtn;
    App.setLoading(btn, true, light ? 'Mengoptimasi…' : 'Mengompres…');
    progress.hidden = false;
    progressBar.style.width = '45%';
    try {
      const res = await App.postForm('api/compress.php', fd);
      const blob = await res.blob();
      const before = currentFile.size;
      const after = blob.size;
      App.downloadBlob(blob, fname);
      let msg = 'Selesai: ' + fname;
      if (after < before) {
        msg += ' (' + App.fmtSize(before) + ' → ' + App.fmtSize(after) + ', hemat ' +
          Math.round((1 - after / before) * 100) + '%)';
      } else {
        msg += ' (' + App.fmtSize(before) + ' → ' + App.fmtSize(after) + ')';
      }
      App.toast(msg, 'success');
      summary.textContent = App.fmtSize(before) + ' → ' + App.fmtSize(after);
    } catch (e) {
      App.toast(e.message, 'error');
    } finally {
      progressBar.style.width = '100%';
      setTimeout(function () {
        progress.hidden = true;
        progressBar.style.width = '0%';
      }, 350);
      App.setLoading(btn, false);
      processing = false;
      if (light) renderStatus();
    }
  }

  runBtn.addEventListener('click', function () { process(false); });
  lightBtn.addEventListener('click', function () { process(true); });

  ensureDetect();
})();

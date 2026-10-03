(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const chip = document.getElementById('fileChip');
  const toolbar = document.getElementById('toolbar');
  const empty = document.getElementById('empty');
  const actions = document.getElementById('actions');
  const summary = document.getElementById('summary');
  const runBtn = document.getElementById('runBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const progressLabel = document.getElementById('progressLabel');
  const qualityField = document.getElementById('qualityField');
  const quality = document.getElementById('quality');
  const qualityVal = document.getElementById('qualityVal');

  let currentFile = null;
  let doc = null;
  let pageCount = 0;

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
      if (doc) {
        try { doc.destroy(); } catch (e) {}
        doc = null;
      }
      summary.textContent = 'Membaca dokumen…';
      toolbar.hidden = false;
      actions.hidden = false;
      empty.hidden = true;
      try {
        doc = await App.loadPdf(f);
        pageCount = doc.numPages;
        document.getElementById('fcMeta').textContent = App.fmtSize(f.size) + ' · ' + pageCount + ' halaman';
        summary.textContent = pageCount + ' halaman siap dirender';
      } catch (e) {
        App.toast('PDF tidak bisa dibaca — file rusak atau terkunci password.', 'error');
        App.qs('#btnClear').click();
      }
    },
    onClear: function () {
      currentFile = null;
      pageCount = 0;
      if (doc) {
        try { doc.destroy(); } catch (e) {}
        doc = null;
      }
      toolbar.hidden = true;
      actions.hidden = true;
      empty.hidden = false;
    }
  });

  document.querySelectorAll('input[name="fmt"]').forEach(function (r) {
    r.addEventListener('change', function () {
      qualityField.hidden = document.querySelector('input[name="fmt"]:checked').value !== 'jpeg';
    });
  });
  quality.addEventListener('input', function () {
    qualityVal.textContent = quality.value;
  });

  function renderOne(page, scale, format, q) {
    return new Promise(function (resolve, reject) {
      const vp = page.getViewport({ scale: scale });
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(vp.width);
      canvas.height = Math.round(vp.height);
      const ctx = canvas.getContext('2d');
      if (format === 'jpeg') {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
      }
      page.render({ canvasContext: ctx, viewport: vp }).promise.then(function () {
        canvas.toBlob(function (blob) {
          if (blob) resolve(blob);
          else reject(new Error('Gagal membuat gambar halaman ' + page.pageNumber));
        }, format, q);
      }).catch(reject);
    });
  }

  runBtn.addEventListener('click', async function () {
    if (!doc || !pageCount) {
      App.toast('Pilih PDF dulu.', 'error');
      return;
    }
    let pages = App.parseRange(document.getElementById('range').value, pageCount);
    if (!pages.length) {
      for (let i = 1; i <= pageCount; i++) pages.push(i);
    }
    if (pages.length > 400) {
      App.toast('Terlalu banyak halaman sekaligus (' + pages.length + '). Batasi maksimal 400 per render.', 'error');
      return;
    }
    const scale = parseInt(document.querySelector('input[name="scale"]:checked').value, 10) || 2;
    const format = document.querySelector('input[name="fmt"]:checked').value;
    const q = parseInt(quality.value, 10) / 100;
    const ext = format === 'jpeg' ? 'jpg' : 'png';

    App.setLoading(runBtn, true, 'Merender…');
    progress.hidden = false;
    progressLabel.hidden = false;
    progressBar.style.width = '0%';

    try {
      const parts = [];
      for (let i = 0; i < pages.length; i++) {
        const p = pages[i];
        progressLabel.textContent = 'Merender halaman ' + p + ' (' + (i + 1) + '/' + pages.length + ')…';
        progressBar.style.width = Math.round(i / pages.length * 100) + '%';
        const page = await doc.getPage(p);
        const blob = await renderOne(page, scale, format, q);
        parts.push({ name: 'halaman-' + String(p).padStart(3, '0') + '.' + ext, blob: blob });
      }
      progressBar.style.width = '85%';
      progressLabel.textContent = 'Menyiapkan unduhan…';

      const base = App.safeName(document.getElementById('outName').value || 'halaman', 'zip').replace(/\.zip$/i, '');
      if (parts.length === 1) {
        App.downloadBlob(parts[0].blob, parts[0].name.replace('halaman-001', base));
      } else {
        const zip = new JSZip();
        parts.forEach(function (p) {
          zip.file(p.name, p.blob);
        });
        const zblob = await zip.generateAsync({ type: 'blob' });
        App.downloadBlob(zblob, base + '.zip');
      }
      progressBar.style.width = '100%';
      App.toast('Selesai: ' + parts.length + ' halaman dirender (' + ext.toUpperCase() + ', ' + scale + '×).', 'success');
      summary.textContent = parts.length + ' halaman → ' + ext.toUpperCase() + ' ' + scale + '×';
    } catch (e) {
      App.toast(e.message || 'Gagal merender.', 'error');
    } finally {
      setTimeout(function () {
        progress.hidden = true;
        progressLabel.hidden = true;
        progressBar.style.width = '0%';
      }, 450);
      App.setLoading(runBtn, false);
    }
  });
})();

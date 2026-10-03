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

  let currentFile = null;
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
      toolbar.hidden = false;
      actions.hidden = false;
      empty.hidden = true;
      summary.textContent = 'Membaca dokumen…';
      try {
        const doc = await App.loadPdf(f);
        pageCount = doc.numPages;
        doc.destroy();
        document.getElementById('fcMeta').textContent = App.fmtSize(f.size) + ' · ' + pageCount + ' halaman';
        summary.textContent = pageCount + ' halaman akan dinomori';
      } catch (e) {
        pageCount = 0;
        App.toast('PDF tidak bisa dibaca — file rusak atau terkunci password.', 'error');
        App.qs('#btnClear').click();
      }
    },
    onClear: function () {
      currentFile = null;
      pageCount = 0;
      toolbar.hidden = true;
      actions.hidden = true;
      empty.hidden = false;
    }
  });

  runBtn.addEventListener('click', async function () {
    if (!currentFile || !pageCount) {
      App.toast('Pilih PDF dulu.', 'error');
      return;
    }
    const fname = App.safeName(document.getElementById('outName').value || 'bernomor', 'pdf');
    const fd = new FormData();
    fd.append('file', currentFile, currentFile.name);
    fd.append('pos', document.getElementById('pos').value);
    fd.append('format', document.getElementById('format').value);
    fd.append('font', document.getElementById('font').value);
    fd.append('style', document.querySelector('input[name="style"]:checked').value);
    fd.append('size', document.getElementById('size').value || '10');
    fd.append('color', document.getElementById('color').value);
    fd.append('margin', document.getElementById('margin').value || '12');
    fd.append('start', document.getElementById('start').value || '1');
    fd.append('name', fname);

    App.setLoading(runBtn, true, 'Memproses…');
    progress.hidden = false;
    progressBar.style.width = '55%';
    try {
      const res = await App.postForm('api/pagenum.php', fd);
      const blob = await res.blob();
      App.downloadBlob(blob, fname);
      App.toast('Nomor halaman diterapkan: ' + fname, 'success');
    } catch (e) {
      App.toast(e.message, 'error');
    } finally {
      progressBar.style.width = '100%';
      setTimeout(function () {
        progress.hidden = true;
        progressBar.style.width = '0%';
      }, 350);
      App.setLoading(runBtn, false);
    }
  });
})();

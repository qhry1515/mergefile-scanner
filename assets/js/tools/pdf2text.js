(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const chip = document.getElementById('fileChip');
  const empty = document.getElementById('empty');
  const result = document.getElementById('result');
  const out = document.getElementById('out');
  const summary = document.getElementById('summary');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const dlBtn = document.getElementById('dlBtn');
  const copyBtn = document.getElementById('copyBtn');

  let currentFile = null;

  App.initSingleFile({
    drop: drop,
    input: input,
    chip: chip,
    nameEl: document.getElementById('fcName'),
    metaEl: document.getElementById('fcMeta'),
    btnChange: document.getElementById('btnChange'),
    btnClear: document.getElementById('btnClear'),
    onFile: run,
    onClear: function () {
      currentFile = null;
      out.value = '';
      result.hidden = true;
      empty.hidden = false;
    }
  });

  async function run(f) {
    currentFile = f;
    result.hidden = true;
    empty.hidden = true;
    progress.hidden = false;
    progressBar.style.width = '35%';
    summary.textContent = 'Mengekstrak teks…';
    const fd = new FormData();
    fd.append('file', f, f.name);
    try {
      const res = await App.postForm('api/pdf2text.php', fd);
      const j = await res.json();
      out.value = j.text || '';
      result.hidden = false;
      summary.textContent = j.pages + ' halaman · ' + (j.chars || 0).toLocaleString('id') + ' karakter';
      if (!j.text) {
        App.toast('Tidak ada teks terdeteksi (mungkin PDF hasil scan/gambar).', 'info');
      }
    } catch (e) {
      App.toast(e.message, 'error');
      empty.hidden = false;
      App.qs('#btnClear').click();
    } finally {
      progressBar.style.width = '100%';
      setTimeout(function () {
        progress.hidden = true;
        progressBar.style.width = '0%';
      }, 350);
    }
  }

  copyBtn.addEventListener('click', async function () {
    if (!out.value) return;
    try {
      await navigator.clipboard.writeText(out.value);
      App.toast('Teks disalin ke clipboard.', 'success');
    } catch (e) {
      out.select();
      document.execCommand('copy');
      App.toast('Teks disalin.', 'success');
    }
  });

  dlBtn.addEventListener('click', function () {
    if (!out.value) return;
    const base = (currentFile ? currentFile.name : 'hasil').replace(/\.pdf$/i, '');
    const blob = new Blob([out.value], { type: 'text/plain;charset=utf-8' });
    App.downloadBlob(blob, App.safeName(base + '-teks', 'txt'));
  });
})();

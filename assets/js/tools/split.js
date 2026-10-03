(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const chip = document.getElementById('fileChip');
  const toolbar = document.getElementById('toolbar');
  const grid = document.getElementById('grid');
  const empty = document.getElementById('empty');
  const actions = document.getElementById('actions');
  const hintBar = document.getElementById('hintBar');
  const summary = document.getElementById('summary');
  const runBtn = document.getElementById('runBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const MAX_THUMB = 300;

  let items = [];
  let currentFile = null;
  let mode = 'extract';

  const single = App.initSingleFile({
    drop: drop,
    input: input,
    chip: chip,
    nameEl: document.getElementById('fcName'),
    metaEl: document.getElementById('fcMeta'),
    btnChange: document.getElementById('btnChange'),
    btnClear: document.getElementById('btnClear'),
    onFile: openFile,
    onClear: function () {
      items = [];
      currentFile = null;
      render();
    }
  });

  document.querySelectorAll('input[name="smode"]').forEach(function (r) {
    r.addEventListener('change', function () {
      mode = document.querySelector('input[name="smode"]:checked').value;
      document.getElementById('runBtn').lastChild.textContent = mode === 'extract' ? 'Ekstrak & Unduh' : 'Hapus & Unduh';
      updateSummary();
    });
  });

  document.getElementById('btnAll').addEventListener('click', function () {
    items.forEach(function (it) { it.checked = true; });
    render();
  });
  document.getElementById('btnNone').addEventListener('click', function () {
    items.forEach(function (it) { it.checked = false; });
    render();
  });

  async function openFile(f) {
    currentFile = f;
    items = [];
    render();
    let doc;
    try {
      doc = await App.loadPdf(f);
    } catch (e) {
      App.toast('PDF tidak bisa dibaca — file rusak atau terkunci password.', 'error');
      single.clear();
      return;
    }
    const n = doc.numPages;
    document.getElementById('fcMeta').textContent = App.fmtSize(f.size) + ' · ' + n + ' halaman';
    for (let i = 1; i <= n; i++) {
      items.push({ p: i, url: '', checked: true });
    }
    render();
    if (n > MAX_THUMB) {
      App.toast('PDF ' + n + ' halaman — pratinjau hanya untuk halaman pertama ' + MAX_THUMB + '.', 'info');
    }
    startProgress(n);
    try {
      for (let i = 1; i <= Math.min(n, MAX_THUMB); i++) {
        const page = await doc.getPage(i);
        items[i - 1].url = await App.pageToDataUrl(page, 280, 380);
        updateThumb(i - 1);
        setProgress(i / Math.min(n, MAX_THUMB) * 100);
      }
    } catch (e) {}
    stopProgress();
    doc.destroy();
  }

  function startProgress(n) {
    progress.hidden = false;
    progressBar.style.width = '0%';
  }

  function setProgress(pct) {
    progressBar.style.width = Math.round(pct) + '%';
  }

  function stopProgress() {
    progressBar.style.width = '100%';
    setTimeout(function () {
      progress.hidden = true;
      progressBar.style.width = '0%';
    }, 350);
  }

  function selectedPages() {
    if (mode === 'extract') {
      return items.filter(function (it) { return it.checked; }).map(function (it) { return it.p; });
    }
    return items.filter(function (it) { return !it.checked; }).map(function (it) { return it.p; });
  }

  function updateSummary() {
    const total = items.length;
    const picked = items.filter(function (it) { return it.checked; }).length;
    const result = mode === 'extract' ? picked : total - picked;
    if (mode === 'extract') {
      summary.textContent = total + ' halaman · ' + picked + ' dipilih → hasil ' + result + ' halaman';
    } else {
      summary.textContent = total + ' halaman · ' + picked + ' dihapus → hasil ' + result + ' halaman';
    }
    runBtn.disabled = result === 0;
  }

  function updateThumb(idx) {
    const card = grid.querySelector('.card[data-idx="' + idx + '"]');
    if (!card) return;
    const it = items[idx];
    if (!it.url) return;
    const thumb = card.querySelector('.thumb');
    if (thumb.querySelector('img')) return;
    const ph = thumb.querySelector('.ph');
    if (ph) ph.remove();
    const img = document.createElement('img');
    img.src = it.url;
    img.alt = '';
    thumb.insertBefore(img, thumb.firstChild);
  }

  function render() {
    const has = items.length > 0;
    empty.hidden = has;
    grid.hidden = !has;
    toolbar.hidden = !has;
    actions.hidden = !has;
    hintBar.hidden = !has;

    grid.innerHTML = '';
    items.forEach(function (it, idx) {
      const card = App.el('div', 'card' + (it.checked ? ' selected' : ''));
      card.draggable = true;
      card.dataset.idx = String(idx);
      card.title = 'Halaman ' + it.p;

      const thumb = App.el('div', 'thumb');
      if (it.url) {
        const img = document.createElement('img');
        img.src = it.url;
        img.alt = '';
        thumb.appendChild(img);
      } else {
        thumb.appendChild(App.el('div', 'ph', String(it.p)));
      }
      const badge = App.el('span', 'badge', String(it.p));
      thumb.appendChild(badge);

      const cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.className = 'check';
      cb.checked = it.checked;
      cb.title = 'Pilih halaman ini';
      cb.addEventListener('change', function () {
        it.checked = cb.checked;
        card.classList.toggle('selected', it.checked);
        updateSummary();
      });
      thumb.appendChild(cb);

      const meta = App.el('div', 'meta');
      meta.appendChild(App.el('div', 'name', 'Halaman ' + it.p));
      meta.appendChild(App.el('div', 'sub', 'urutan ' + (idx + 1)));

      card.appendChild(thumb);
      card.appendChild(meta);
      grid.appendChild(card);
    });
    updateSummary();
  }

  App.makeSortable(grid, function (from, to) {
    const it = items.splice(from, 1)[0];
    items.splice(to, 0, it);
    render();
  });

  runBtn.addEventListener('click', async function () {
    const pages = selectedPages();
    if (!pages.length) {
      App.toast('Tidak ada halaman yang dipilih.', 'error');
      return;
    }
    const fname = App.safeName(document.getElementById('outName').value || 'hasil-split', 'pdf');
    const fd = new FormData();
    fd.append('file', currentFile, currentFile.name);
    pages.forEach(function (p) { fd.append('pages[]', String(p)); });
    fd.append('name', fname);

    App.setLoading(runBtn, true, 'Memproses…');
    try {
      const res = await App.postForm('api/split.php', fd);
      const blob = await res.blob();
      App.downloadBlob(blob, fname);
      App.toast('Berhasil: ' + pages.length + ' halaman → ' + fname, 'success');
    } catch (e) {
      App.toast(e.message, 'error');
    } finally {
      App.setLoading(runBtn, false);
    }
  });

  render();
})();

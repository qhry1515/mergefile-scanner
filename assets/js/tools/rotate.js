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
  const rotCount = document.getElementById('rotCount');
  const runBtn = document.getElementById('runBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const MAX_THUMB = 300;

  let items = [];
  let currentFile = null;

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
      items.push({ p: i, url: '', rot: 0 });
    }
    render();
    progress.hidden = false;
    progressBar.style.width = '0%';
    try {
      for (let i = 1; i <= Math.min(n, MAX_THUMB); i++) {
        const page = await doc.getPage(i);
        items[i - 1].url = await App.pageToDataUrl(page, 280, 380);
        updateThumb(i - 1);
        progressBar.style.width = Math.round(i / Math.min(n, MAX_THUMB) * 100) + '%';
      }
    } catch (e) {}
    progressBar.style.width = '100%';
    setTimeout(function () {
      progress.hidden = true;
      progressBar.style.width = '0%';
    }, 350);
    doc.destroy();
  }

  document.getElementById('rotAll90').addEventListener('click', function () { applyAll(90); });
  document.getElementById('rotAll180').addEventListener('click', function () { applyAll(180); });
  document.getElementById('rotAll270').addEventListener('click', function () { applyAll(270); });
  document.getElementById('rotReset').addEventListener('click', function () { applyAll(0, true); });

  function applyAll(delta, reset) {
    items.forEach(function (it) {
      it.rot = reset ? 0 : ((it.rot + delta) % 360 + 360) % 360;
    });
    render();
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

  function updateSummary() {
    const rotated = items.filter(function (it) { return it.rot !== 0; }).length;
    summary.textContent = items.length + ' halaman · ' + rotated + ' diputar';
    rotCount.textContent = rotated ? rotated + ' halaman akan diputar' : 'Belum ada rotasi diterapkan';
    runBtn.disabled = rotated === 0;
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
      const card = App.el('div', 'card');
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
      thumb.appendChild(App.el('span', 'badge', String(it.p)));
      if (it.rot) {
        thumb.appendChild(App.el('span', 'badge rot', it.rot + '°'));
      }

      const cardActions = App.el('div', 'card-actions');
      const rotBtn = App.el('button', 'chip-btn', '↻ Putar 90°');
      rotBtn.type = 'button';
      rotBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        it.rot = ((it.rot + 90) % 360 + 360) % 360;
        render();
      });
      cardActions.appendChild(rotBtn);

      const meta = App.el('div', 'meta');
      meta.appendChild(App.el('div', 'name', 'Halaman ' + it.p));
      meta.appendChild(App.el('div', 'sub', it.rot ? 'rotasi ' + it.rot + '°' : 'tanpa rotasi'));

      card.appendChild(thumb);
      card.appendChild(cardActions);
      card.appendChild(meta);
      grid.appendChild(card);
    });
    updateSummary();
  }

  runBtn.addEventListener('click', async function () {
    if (!currentFile) return;
    const fname = App.safeName(document.getElementById('outName').value || 'diputar', 'pdf');
    const fd = new FormData();
    fd.append('file', currentFile, currentFile.name);
    items.forEach(function (it) {
      fd.append('rot[]', String(it.rot));
    });
    fd.append('name', fname);

    App.setLoading(runBtn, true, 'Memproses…');
    try {
      const res = await App.postForm('api/rotate.php', fd);
      const blob = await res.blob();
      App.downloadBlob(blob, fname);
      App.toast('Berhasil diputar & diunduh: ' + fname, 'success');
    } catch (e) {
      App.toast(e.message, 'error');
    } finally {
      App.setLoading(runBtn, false);
    }
  });

  render();
})();

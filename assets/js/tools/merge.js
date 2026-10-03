(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const thumbs = document.getElementById('thumbs');
  const empty = document.getElementById('empty');
  const settings = document.getElementById('settings');
  const actions = document.getElementById('actions');
  const hintBar = document.getElementById('hintBar');
  const hintStats = document.getElementById('hintStats');
  const summary = document.getElementById('summary');
  const mergeBtn = document.getElementById('mergeBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const marginField = document.getElementById('marginField');
  const MAX_FILES = 60;
  const items = [];
  let fakeTimer = null;

  const dzLimit = document.getElementById('dzLimit');
  const dzCount = document.getElementById('dzCount');
  if (dzLimit) dzLimit.textContent = App.maxUpload ? App.fmtSize(App.maxUpload) : '40 MB';
  if (dzCount) dzCount.textContent = String(MAX_FILES);

  App.initDropzone(drop, input, addFiles);

  document.querySelectorAll('input[name="imgmode"]').forEach(function (r) {
    r.addEventListener('change', function () {
      marginField.hidden = document.querySelector('input[name="imgmode"]:checked').value !== 'a4';
    });
  });

  function addFiles(files) {
    let added = 0;
    for (let i = 0; i < files.length; i++) {
      const f = files[i];
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      if (['pdf', 'jpg', 'jpeg', 'png', 'gif'].indexOf(ext) === -1) {
        App.toast('"' + App.fitName(f.name, 40) + '" dilewati — format .' + ext + ' tidak didukung.', 'error');
        continue;
      }
      if (items.length >= MAX_FILES) {
        App.toast('Maksimal ' + MAX_FILES + ' file.', 'error');
        break;
      }
      if (App.maxUpload && f.size > App.maxUpload) {
        App.toast('"' + App.fitName(f.name, 40) + '" terlalu besar (' + App.fmtSize(f.size) + ').', 'error');
        continue;
      }
      const item = {
        file: f,
        name: f.name,
        size: f.size,
        isImage: ext !== 'pdf',
        url: '',
        pages: null
      };
      items.push(item);
      added++;
      fillPreview(item);
    }
    if (added) render();
  }

  async function fillPreview(item) {
    try {
      if (item.isImage) {
        item.url = URL.createObjectURL(item.file);
        render();
        return;
      }
      const doc = await App.loadPdf(item.file);
      item.pages = doc.numPages;
      const page = await doc.getPage(1);
      item.url = await App.pageToDataUrl(page, 300, 400);
      doc.destroy();
      render();
    } catch (e) {
      item.pages = null;
      render();
    }
  }

  function totalPages() {
    let n = 0;
    items.forEach(function (it) {
      if (it.pages) n += it.pages;
      else n += 1;
    });
    return n;
  }

  function removeItem(idx) {
    const it = items[idx];
    if (it && it.url && it.isImage) URL.revokeObjectURL(it.url);
    items.splice(idx, 1);
    render();
  }

  function render() {
    const has = items.length > 0;
    empty.hidden = has;
    thumbs.hidden = !has;
    settings.hidden = !has;
    actions.hidden = !has;
    hintBar.hidden = !has;

    thumbs.innerHTML = '';
    items.forEach(function (it, idx) {
      const card = App.el('div', 'card');
      card.draggable = true;
      card.dataset.idx = String(idx);
      card.title = 'Tarik untuk mengubah urutan';

      const thumb = App.el('div', 'thumb');
      if (it.url) {
        const img = document.createElement('img');
        img.src = it.url;
        img.alt = '';
        thumb.appendChild(img);
      } else {
        thumb.appendChild(App.el('div', 'ph', it.isImage ? '…' : 'PDF'));
      }

      const pageBadge = App.el('span', 'badge');
      pageBadge.textContent = it.pages ? it.pages + ' hlm' : (it.isImage ? 'gambar' : 'PDF');
      thumb.appendChild(pageBadge);

      const cardActions = App.el('div', 'card-actions');
      const del = App.el('button', 'chip-btn', 'Hapus');
      del.type = 'button';
      del.addEventListener('click', function (e) {
        e.stopPropagation();
        removeItem(idx);
      });
      cardActions.appendChild(del);

      const meta = App.el('div', 'meta');
      const nm = App.el('div', 'name', it.name);
      nm.title = it.name;
      const sub = App.el('div', 'sub', App.fmtSize(it.size) + ' · urutan ' + (idx + 1));
      meta.appendChild(nm);
      meta.appendChild(sub);

      card.appendChild(thumb);
      card.appendChild(cardActions);
      card.appendChild(meta);
      thumbs.appendChild(card);
    });

    const pages = totalPages();
    summary.textContent = items.length + ' file · ' + pages + ' halaman · ' +
      App.fmtSize(items.reduce(function (a, b) { return a + b.size; }, 0));
    hintStats.textContent = 'Kartu ke-' + items.length + ' akan jadi halaman terakhir';
  }

  App.makeSortable(thumbs, function (from, to) {
    const it = items.splice(from, 1)[0];
    items.splice(to, 0, it);
    render();
  });

  function startProgress() {
    progress.hidden = false;
    progressBar.style.width = '8%';
    let w = 8;
    fakeTimer = setInterval(function () {
      w = Math.min(92, w + Math.random() * 9);
      progressBar.style.width = w + '%';
    }, 350);
  }

  function stopProgress() {
    if (fakeTimer) clearInterval(fakeTimer);
    fakeTimer = null;
    progressBar.style.width = '100%';
    setTimeout(function () {
      progress.hidden = true;
      progressBar.style.width = '0%';
    }, 350);
  }

  mergeBtn.addEventListener('click', async function () {
    if (!items.length) {
      App.toast('Pilih file dulu.', 'error');
      return;
    }
    const total = items.reduce(function (a, b) { return a + b.size; }, 0);
    if (App.maxUpload && total > App.maxUpload) {
      App.toast('Total ukuran ' + App.fmtSize(total) + ' melebihi batas ' + App.fmtSize(App.maxUpload) + '.', 'error');
      return;
    }
    const outName = document.getElementById('outName').value;
    const fname = App.safeName(outName || 'gabungan', 'pdf');
    const fd = new FormData();
    items.forEach(function (it) {
      fd.append('files[]', it.file, it.file.name);
    });
    fd.append('name', fname);
    fd.append('imgmode', document.querySelector('input[name="imgmode"]:checked').value);
    fd.append('margin', document.getElementById('margin').value || '10');

    App.setLoading(mergeBtn, true, 'Menggabungkan…');
    startProgress();
    try {
      const res = await App.postForm('api/merge.php', fd);
      const blob = await res.blob();
      App.downloadBlob(blob, fname);
      App.toast('Berhasil digabung: ' + fname, 'success');
    } catch (e) {
      App.toast(e.message, 'error');
    } finally {
      stopProgress();
      App.setLoading(mergeBtn, false);
    }
  });

  render();
})();

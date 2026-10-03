(function () {
  const App = (window.App = {});

  App.maxUpload = parseInt(document.body.dataset.maxUpload || '0', 10) || 0;

  const themeBtn = document.getElementById('themeBtn');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      try {
        localStorage.setItem('mf-theme', next);
      } catch (e) {}
    });
  }

  App.toast = function (msg, type) {
    type = type || 'info';
    let box = document.getElementById('toasts');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toasts';
      box.className = 'toasts';
      document.body.appendChild(box);
    }
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    box.appendChild(t);
    const life = type === 'error' ? 6500 : 3800;
    setTimeout(function () {
      t.classList.add('leaving');
      setTimeout(function () {
        t.remove();
      }, 280);
    }, life);
    t.addEventListener('click', function () {
      t.remove();
    });
  };

  App.fmtSize = function (bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  };

  App.qs = function (sel, root) {
    return (root || document).querySelector(sel);
  };

  App.qsa = function (sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  };

  App.el = function (tag, cls, text) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  };

  App.setLoading = function (btn, on, label) {
    if (!btn) return;
    const spin = btn.querySelector('.spinner');
    const setLabel = function (txt) {
      btn.textContent = '';
      if (spin) btn.appendChild(spin);
      btn.appendChild(document.createTextNode(txt));
    };
    if (on) {
      if (!btn.dataset.label) {
        let t = '';
        Array.prototype.forEach.call(btn.childNodes, function (n) {
          if (n.nodeType === 3) t += n.textContent;
        });
        btn.dataset.label = t.trim();
      }
      btn.disabled = true;
      btn.classList.add('loading');
      if (label) setLabel(label);
    } else {
      btn.disabled = false;
      btn.classList.remove('loading');
      setLabel(btn.dataset.label || '');
    }
  };

  App.initDropzone = function (zone, input, onFiles) {
    const stop = function (e) {
      e.preventDefault();
      e.stopPropagation();
    };
    zone.addEventListener('click', function () {
      input.click();
    });
    zone.addEventListener('dragover', function (e) {
      stop(e);
      zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function (e) {
      stop(e);
      zone.classList.remove('dragover');
    });
    zone.addEventListener('drop', function (e) {
      stop(e);
      zone.classList.remove('dragover');
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
        onFiles(Array.prototype.slice.call(e.dataTransfer.files), 'drop');
      }
    });
    input.addEventListener('change', function () {
      if (input.files && input.files.length) {
        onFiles(Array.prototype.slice.call(input.files), 'input');
      }
      input.value = '';
    });
  };

  App.postForm = async function (url, fd) {
    const res = await fetch(url, { method: 'POST', body: fd });
    if (!res.ok) {
      let msg = 'Permintaan gagal (HTTP ' + res.status + ')';
      try {
        const j = await res.json();
        if (j && j.error) msg = j.error;
      } catch (e) {}
      throw new Error(msg);
    }
    return res;
  };

  App.downloadBlob = function (blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () {
      URL.revokeObjectURL(url);
    }, 4000);
  };

  App.loadPdf = async function (file) {
    const data = new Uint8Array(await file.arrayBuffer());
    return await pdfjsLib.getDocument({ data: data }).promise;
  };

  App.pageToDataUrl = async function (page, maxW, maxH) {
    const vp0 = page.getViewport({ scale: 1 });
    const scale = Math.min(maxW / vp0.width, maxH / vp0.height, 2);
    const vp = page.getViewport({ scale: scale });
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(vp.width));
    canvas.height = Math.max(1, Math.round(vp.height));
    const ctx = canvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport: vp }).promise;
    return canvas.toDataURL('image/png');
  };

  App.makeSortable = function (container, onMove) {
    let dragEl = null;
    const clearTargets = function () {
      App.qsa('.drop-target', container).forEach(function (x) {
        x.classList.remove('drop-target');
      });
    };
    container.addEventListener('dragstart', function (e) {
      const c = e.target.closest ? e.target.closest('.card') : null;
      if (!c || !container.contains(c)) return;
      dragEl = c;
      c.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      try {
        e.dataTransfer.setData('text/plain', '');
      } catch (err) {}
    });
    container.addEventListener('dragover', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      const t = e.target.closest ? e.target.closest('.card') : null;
      clearTargets();
      if (t && t !== dragEl && container.contains(t)) t.classList.add('drop-target');
    });
    container.addEventListener('drop', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      const t = e.target.closest ? e.target.closest('.card') : null;
      clearTargets();
      if (t && t !== dragEl && container.contains(t)) {
        const from = parseInt(dragEl.dataset.idx, 10);
        const to = parseInt(t.dataset.idx, 10);
        if (from >= 0 && to >= 0 && from !== to) {
          dragEl.classList.remove('dragging');
          dragEl = null;
          onMove(from, to);
          return;
        }
      }
      dragEl.classList.remove('dragging');
      dragEl = null;
    });
    container.addEventListener('dragend', function () {
      if (dragEl) dragEl.classList.remove('dragging');
      dragEl = null;
      clearTargets();
    });
  };

  App.parseRange = function (str, max) {
    const out = [];
    const seen = {};
    const parts = String(str || '').split(',');
    for (let i = 0; i < parts.length; i++) {
      const p = parts[i].trim();
      if (!p) continue;
      const m = p.match(/^(\d+)\s*-\s*(\d+)$/);
      if (m) {
        let a = parseInt(m[1], 10);
        let b = parseInt(m[2], 10);
        if (a > b) { const tmp = a; a = b; b = tmp; }
        for (let n = a; n <= b && n <= max; n++) {
          if (n >= 1 && !seen[n]) { seen[n] = 1; out.push(n); }
        }
      } else if (/^\d+$/.test(p)) {
        const n = parseInt(p, 10);
        if (n >= 1 && n <= max && !seen[n]) { seen[n] = 1; out.push(n); }
      }
    }
    out.sort(function (a, b) { return a - b; });
    return out;
  };

  App.safeName = function (name, ext) {
    let n = String(name || '').replace(/[\x00-\x1F<>:"/\\|?*]/g, '').replace(/^[\s.]+|[\s.]+$/g, '');
    if (n.length > 100) n = n.slice(0, 100);
    if (!n) n = 'dokumen';
    if (n.toLowerCase().slice(-(ext.length + 1)) !== '.' + ext.toLowerCase()) n += '.' + ext;
    return n;
  };

  App.fitName = function (name, max) {
    max = max || 34;
    if (name.length <= max) return name;
    return name.slice(0, max - 1) + '…';
  };

  App.initSingleFile = function (opts) {
    const drop = opts.drop;
    const input = opts.input;
    const chip = opts.chip;
    let current = null;
    App.initDropzone(drop, input, function (files) {
      const f = files[0];
      if (!f) return;
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      if (ext !== 'pdf') {
        App.toast('Hanya file PDF yang didukung di halaman ini.', 'error');
        return;
      }
      if (App.maxUpload && f.size > App.maxUpload) {
        App.toast('Ukuran ' + App.fmtSize(f.size) + ' melebihi batas ' + App.fmtSize(App.maxUpload) + '.', 'error');
        return;
      }
      current = f;
      opts.nameEl.textContent = f.name;
      opts.nameEl.title = f.name;
      opts.metaEl.textContent = App.fmtSize(f.size);
      drop.hidden = true;
      chip.hidden = false;
      opts.onFile(f);
    });
    if (opts.btnChange) {
      opts.btnChange.addEventListener('click', function () {
        input.click();
      });
    }
    if (opts.btnClear) {
      opts.btnClear.addEventListener('click', function () {
        current = null;
        drop.hidden = false;
        chip.hidden = true;
        if (opts.onClear) opts.onClear();
      });
    }
    return {
      get file() {
        return current;
      },
      clear: function () {
        if (opts.btnClear) opts.btnClear.click();
      }
    };
  };

  if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/vendor/pdfjs/pdf.worker.min.js';
  }
})();

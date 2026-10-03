(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const fileChip = document.getElementById('fileChip');
  const fcName = document.getElementById('fcName');
  const fcMeta = document.getElementById('fcMeta');
  const canvasPanel = document.getElementById('canvasPanel');
  const canvas = document.getElementById('ocrCanvas');
  const toolbar = document.getElementById('toolbar');
  const actions = document.getElementById('actions');
  const summary = document.getElementById('summary');
  const runBtn = document.getElementById('runBtn');
  const resetBtn = document.getElementById('ocrBtn');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const ocrStatus = document.getElementById('ocrStatus');
  const resultPanel = document.getElementById('resultPanel');
  const ocrResult = document.getElementById('ocrResult');
  const charCount = document.getElementById('charCount');
  const copyBtn = document.getElementById('copyBtn');
  const clearBtn = document.getElementById('clearBtn');
  const enhance = document.getElementById('enhance');

  const BASE = new URL('.', document.baseURI).href;

  let file = null;
  let img = null;
  let objUrl = null;
  let sel = null;
  let dragStart = null;
  let curScale = 1;
  let worker = null;
  let workerLang = '';
  let busy = false;

  function setStatus(text) {
    ocrStatus.hidden = false;
    ocrStatus.textContent = text;
  }

  App.initDropzone(drop, input, function (files) {
    const f = files[0];
    if (!f) return;
    if (f.type && f.type.indexOf('image/') !== 0) {
      App.toast('Hanya file gambar yang didukung (JPG/PNG/WEBP/BMP).', 'error');
      return;
    }
    setFile(f);
  });

  document.getElementById('btnChange').addEventListener('click', function () { input.click(); });
  document.getElementById('btnClear').addEventListener('click', clearFile);

  function setFile(f) {
    if (objUrl) URL.revokeObjectURL(objUrl);
    file = f;
    sel = null;
    dragStart = null;
    fcName.textContent = f.name;
    fcName.title = f.name;
    fcMeta.textContent = App.fmtSize(f.size);
    drop.hidden = true;
    fileChip.hidden = false;
    canvasPanel.hidden = false;
    toolbar.hidden = false;
    actions.hidden = false;
    objUrl = URL.createObjectURL(f);
    const im = new Image();
    im.onload = function () {
      img = im;
      summary.textContent = App.fmtSize(f.size) + ' · ' + im.naturalWidth + '×' + im.naturalHeight + ' px';
      setStatus('Pilih area teks dengan menarik kotak (opsional), lalu klik "Baca Teks".');
      draw();
    };
    im.onerror = function () {
      App.toast('Gambar tidak bisa dibuka.', 'error');
      clearFile();
    };
    im.src = objUrl;
  }

  function clearFile() {
    if (objUrl) URL.revokeObjectURL(objUrl);
    objUrl = null;
    file = null;
    img = null;
    sel = null;
    dragStart = null;
    drop.hidden = false;
    fileChip.hidden = true;
    canvasPanel.hidden = true;
    toolbar.hidden = true;
    actions.hidden = true;
    progress.hidden = true;
    resultPanel.hidden = true;
    ocrStatus.hidden = true;
    ocrResult.value = '';
    charCount.textContent = '0 karakter';
  }

  function draw() {
    if (!img) return;
    const panelW = canvas.parentElement && canvas.parentElement.clientWidth || 760;
    const availW = Math.max(220, panelW - 36);
    const maxH = 440;
    curScale = Math.min(availW / img.naturalWidth, maxH / img.naturalHeight, 1);
    canvas.width = Math.max(1, Math.round(img.naturalWidth * curScale));
    canvas.height = Math.max(1, Math.round(img.naturalHeight * curScale));
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    if (sel) {
      const x = sel.x * curScale;
      const y = sel.y * curScale;
      const w = sel.w * curScale;
      const h = sel.h * curScale;
      ctx.save();
      ctx.fillStyle = 'rgba(15,23,42,.5)';
      ctx.fillRect(0, 0, canvas.width, y);
      ctx.fillRect(0, y + h, canvas.width, canvas.height - y - h);
      ctx.fillRect(0, y, x, h);
      ctx.fillRect(x + w, y, canvas.width - x - w, h);
      ctx.strokeStyle = '#3b82f6';
      ctx.lineWidth = 2;
      ctx.strokeRect(x, y, w, h);
      ctx.restore();
    }
  }

  function toNatural(ev) {
    const r = canvas.getBoundingClientRect();
    const nx = (ev.clientX - r.left) / r.width * img.naturalWidth;
    const ny = (ev.clientY - r.top) / r.height * img.naturalHeight;
    return {
      x: Math.max(0, Math.min(img.naturalWidth, nx)),
      y: Math.max(0, Math.min(img.naturalHeight, ny))
    };
  }

  function rectFrom(a, b) {
    const x = Math.min(a.x, b.x);
    const y = Math.min(a.y, b.y);
    return {
      x: x,
      y: y,
      w: Math.abs(a.x - b.x),
      h: Math.abs(a.y - b.y)
    };
  }

  function updateSummary() {
    if (!img) return;
    const w = sel ? Math.round(sel.w) : img.naturalWidth;
    const h = sel ? Math.round(sel.h) : img.naturalHeight;
    summary.textContent = App.fmtSize(file.size) + ' · area dibaca ' + w + '×' + h + ' px';
  }

  canvas.addEventListener('pointerdown', function (e) {
    if (!img || busy) return;
    try { canvas.setPointerCapture(e.pointerId); } catch (err) {}
    dragStart = toNatural(e);
    sel = null;
    draw();
    e.preventDefault();
  });

  canvas.addEventListener('pointermove', function (e) {
    if (!img || !dragStart) return;
    sel = rectFrom(dragStart, toNatural(e));
    draw();
    e.preventDefault();
  });

  canvas.addEventListener('pointerup', function (e) {
    if (!img || !dragStart) return;
    const r = rectFrom(dragStart, toNatural(e));
    dragStart = null;
    if (r.w > 10 && r.h > 10) {
      sel = r;
    } else {
      sel = null;
    }
    draw();
    updateSummary();
    e.preventDefault();
  });

  canvas.addEventListener('pointercancel', function () {
    dragStart = null;
    draw();
  });

  window.addEventListener('resize', function () {
    if (img) draw();
  });

  resetBtn.addEventListener('click', function () {
    if (!img) return;
    sel = null;
    dragStart = null;
    draw();
    updateSummary();
    setStatus('Kotak crop dihapus — seluruh gambar akan dibaca.');
  });

  function applyEnhance(ctx, w, h) {
    const id = ctx.getImageData(0, 0, w, h);
    const px = id.data;
    const gray = new Uint8Array(w * h);
    let min = 255;
    let max = 0;
    for (let i = 0, j = 0; j < gray.length; i += 4, j++) {
      const g = (px[i] * 299 + px[i + 1] * 587 + px[i + 2] * 114) / 1000 | 0;
      gray[j] = g;
      if (g < min) min = g;
      if (g > max) max = g;
    }
    if (max <= min) return;
    const range = max - min;
    for (let i = 0, j = 0; j < gray.length; i += 4, j++) {
      const v = ((gray[j] - min) / range * 255) | 0;
      px[i] = px[i + 1] = px[i + 2] = v;
    }
    ctx.putImageData(id, 0, 0);
  }

  function buildSource() {
    const sx = sel ? sel.x : 0;
    const sy = sel ? sel.y : 0;
    const sw = sel ? sel.w : img.naturalWidth;
    const sh = sel ? sel.h : img.naturalHeight;
    const maxSide = 1600;
    const k = Math.min(1, maxSide / Math.max(sw, sh));
    const ow = Math.max(1, Math.round(sw * k));
    const oh = Math.max(1, Math.round(sh * k));
    const c = document.createElement('canvas');
    c.width = ow;
    c.height = oh;
    const ctx = c.getContext('2d');
    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, ow, oh);
    if (enhance.checked) {
      try { applyEnhance(ctx, ow, oh); } catch (e) {}
    }
    return new Promise(function (resolve) {
      c.toBlob(function (b) { resolve(b); }, 'image/png');
    });
  }

  function currentLang() {
    const el = document.querySelector('input[name="ocrlang"]:checked');
    return el ? el.value : 'ind+eng';
  }

  function onProgress(m) {
    const pct = Math.round((m.progress || 0) * 100);
    progressBar.style.width = Math.max(4, pct) + '%';
    if (m.status) setStatus(m.status + ' · ' + pct + '%');
  }

  async function getWorker(lang) {
    if (worker && workerLang === lang) return worker;
    if (worker) {
      try { await worker.terminate(); } catch (e) {}
      worker = null;
    }
    setStatus('Menyiapkan mesin OCR… (pertama kali memuat data bahasa, ±semenit)');
    worker = await Tesseract.createWorker(lang, 1, {
      langPath: BASE + 'assets/vendor/tesseract/lang',
      corePath: BASE + 'assets/vendor/tesseract',
      workerPath: BASE + 'assets/vendor/tesseract/worker.min.js',
      gzip: false,
      logger: onProgress
    });
    workerLang = lang;
    return worker;
  }

  runBtn.addEventListener('click', async function () {
    if (busy) return;
    if (!img) {
      App.toast('Pilih gambar dulu.', 'error');
      return;
    }
    busy = true;
    App.setLoading(runBtn, true, 'Membaca…');
    progress.hidden = false;
    progressBar.style.width = '6%';
    setStatus('Membaca gambar…');
    try {
      const blob = await buildSource();
      const w = await getWorker(currentLang());
      const res = await w.recognize(blob);
      const text = (res.data && res.data.text) || '';
      ocrResult.value = text;
      charCount.textContent = text.trim().length + ' karakter';
      progressBar.style.width = '100%';
      if (text.trim()) {
        resultPanel.hidden = false;
        setStatus('Selesai — ' + text.trim().length + ' karakter terbaca.');
        App.toast('Teks berhasil dibaca. Klik "Salin" untuk menyalin.', 'success');
        resultPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        setStatus('Tidak ada teks terdeteksi.');
        App.toast('Tidak ada teks terdeteksi — coba crop lebih rapat atau matikan "Perjelas".', 'error');
      }
    } catch (e) {
      const msg = (e && e.message) ? e.message : String(e);
      setStatus('Gagal: ' + msg);
      App.toast('Gagal membaca gambar: ' + msg, 'error');
    } finally {
      busy = false;
      App.setLoading(runBtn, false);
      setTimeout(function () {
        progress.hidden = true;
        progressBar.style.width = '0%';
      }, 500);
    }
  });

  copyBtn.addEventListener('click', async function () {
    const t = ocrResult.value;
    if (!t.trim()) {
      App.toast('Belum ada teks untuk disalin.', 'error');
      return;
    }
    let done = false;
    try {
      await navigator.clipboard.writeText(t);
      done = true;
    } catch (e) {
      ocrResult.focus();
      ocrResult.select();
      try {
        done = document.execCommand('copy');
      } catch (e2) {
        done = false;
      }
    }
    if (done) {
      App.toast('Teks disalin ke clipboard.', 'success');
    } else {
      App.toast('Gagal menyalin otomatis — teks sudah terpilih, tekan Ctrl+C.', 'error');
    }
  });

  clearBtn.addEventListener('click', function () {
    ocrResult.value = '';
    charCount.textContent = '0 karakter';
  });

  ocrResult.addEventListener('input', function () {
    charCount.textContent = ocrResult.value.trim().length + ' karakter';
  });
})();

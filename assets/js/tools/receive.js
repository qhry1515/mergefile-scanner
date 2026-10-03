(function () {
  const drop = document.getElementById('drop');
  const input = document.getElementById('fileInput');
  const progress = document.getElementById('progress');
  const progressBar = document.getElementById('progressBar');
  const uploadStatus = document.getElementById('uploadStatus');
  const fileList = document.getElementById('fileList');
  const listCount = document.getElementById('listCount');
  const autoImg = document.getElementById('autoImg');
  const dzLimit = document.getElementById('dzLimit');
  const qrBox = document.getElementById('qr');
  const qrLabel = document.getElementById('qrLabel');
  const pinGate = document.getElementById('pinGate');
  const pinInput = document.getElementById('pinInput');
  const pinBtn = document.getElementById('pinBtn');
  const pinErr = document.getElementById('pinErr');
  const btnTunnel = document.getElementById('btnTunnel');
  const btnTunnelStop = document.getElementById('btnTunnelStop');
  const netInfo = document.getElementById('netInfo');
  const netUrl = document.getElementById('netUrl');
  const copyNet = document.getElementById('copyNet');
  const netPin = document.getElementById('netPin');
  const panel = document.getElementById('connectPanel');

  let batch = [];
  let queue = [];
  let active = 0;
  let batchTotal = 0;
  let loadedTotal = 0;
  let batchDone = 0;
  let busy = false;

  let urls = [];
  try {
    urls = JSON.parse(panel.dataset.urls || '[]');
  } catch (e) {}
  let tunnelBase = panel.dataset.tunnelBase || '';
  let tunnelKey = panel.dataset.tunnelKey || '';
  const needPin = panel.dataset.needPin === '1';

  if (dzLimit) dzLimit.textContent = App.maxUpload ? App.fmtSize(App.maxUpload) : '40 MB';

  function tunnelPhoneUrl() {
    if (!tunnelBase) return '';
    return tunnelBase + '/?tool=receive' + (tunnelKey ? '&k=' + encodeURIComponent(tunnelKey) : '');
  }

  function activeQrUrl() {
    return tunnelBase ? tunnelPhoneUrl() : urls[0] || '';
  }

  function activeQrLabel() {
    return tunnelBase ? 'Link internet — kunci & PIN sudah otomatis' : 'WiFi lokal — satu jaringan';
  }

  function setQr(text, label) {
    if (!qrBox || !window.QRCode) return;
    qrBox.innerHTML = '';
    qrBox.hidden = !text;
    if (qrLabel) qrLabel.textContent = text ? label : '';
    if (!text) return;
    new QRCode(qrBox, {
      text: text,
      width: 170,
      height: 170,
      colorDark: '#0f172a',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
  }

  const gatedIds = ['drop', 'listPanel'];
  function showGate() {
    if (pinGate) pinGate.hidden = false;
    gatedIds.forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.hidden = true;
    });
    if (pinInput) pinInput.focus();
  }

  function hideGate() {
    if (pinGate) pinGate.hidden = true;
    gatedIds.forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.hidden = false;
    });
  }

  async function submitPin() {
    const pin = (pinInput.value || '').trim();
    if (!/^\d{6}$/.test(pin)) {
      pinErr.hidden = false;
      return;
    }
    try {
      const fd = new FormData();
      fd.append('action', 'verify_pin');
      fd.append('pin', pin);
      const res = await fetch('api/receive.php', { method: 'POST', body: fd });
      const j = await res.json();
      if (res.ok && j.ok) {
        pinErr.hidden = true;
        pinInput.value = '';
        hideGate();
        refreshList();
        loadTunnelStatus();
        App.toast('PIN benar — terkunci terbuka.', 'success');
      } else {
        pinErr.hidden = false;
      }
    } catch (e) {
      pinErr.hidden = false;
    }
  }

  if (pinBtn) pinBtn.addEventListener('click', submitPin);
  if (pinInput) {
    pinInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') submitPin();
    });
  }

  function applyTunnelUi(activeTunnel, url, pin, authorized) {
    tunnelBase = activeTunnel ? url : '';
    if (activeTunnel && authorized && !tunnelKey && typeof lastKey === 'string') {
      tunnelKey = lastKey;
    }
    btnTunnel.hidden = activeTunnel;
    btnTunnelStop.hidden = !(activeTunnel && authorized);
    netInfo.hidden = !activeTunnel;
    if (activeTunnel) {
      const phone = tunnelPhoneUrl();
      netUrl.href = phone;
      netUrl.textContent = phone;
    }
    if (pin) {
      netPin.hidden = false;
      netPin.textContent = 'PIN cadangan (jika QR tidak bisa dipindai): ' + pin;
    } else {
      netPin.hidden = true;
    }
    setQr(activeQrUrl(), activeQrLabel());
  }

  let lastKey = '';

  async function loadTunnelStatus() {
    try {
      const res = await fetch('api/tunnel.php?action=status');
      if (res.status === 401) {
        applyTunnelUi(true, tunnelBase, null, false);
        showGate();
        return;
      }
      const j = await res.json();
      if (j.key) {
        lastKey = j.key;
        tunnelKey = j.key;
      }
      applyTunnelUi(!!j.active, j.active && j.url ? j.url : '', j.pin || null, true);
    } catch (e) {}
  }

  if (btnTunnel) {
    btnTunnel.addEventListener('click', async function () {
      App.setLoading(btnTunnel, true, 'Membuat link… (±10 detik)');
      try {
        const fd = new FormData();
        fd.append('action', 'start');
        const res = await fetch('api/tunnel.php', { method: 'POST', body: fd });
        const j = await res.json();
        if (!res.ok || !j.active) throw new Error((j && j.error) || 'HTTP ' + res.status);
        lastKey = j.key || '';
        tunnelKey = lastKey;
        applyTunnelUi(true, j.url, j.pin, true);
        App.toast('Link internet aktif — scan QR dari HP (bisa data seluler).', 'success');
      } catch (e) {
        App.toast('Gagal: ' + e.message, 'error');
      } finally {
        App.setLoading(btnTunnel, false);
      }
    });
  }

  if (btnTunnelStop) {
    btnTunnelStop.addEventListener('click', async function () {
      if (!confirm('Matikan link internet?')) return;
      App.setLoading(btnTunnelStop, true, 'Mematikan…');
      try {
        const fd = new FormData();
        fd.append('action', 'stop');
        await fetch('api/tunnel.php', { method: 'POST', body: fd });
        applyTunnelUi(false, '', null, true);
        App.toast('Link internet dimatikan.', 'success');
      } catch (e) {
        applyTunnelUi(false, '', null, true);
        App.toast('Link internet dimatikan (koneksi terputus saat dimatikan).', 'info');
      } finally {
        App.setLoading(btnTunnelStop, false);
      }
    });
  }

  if (copyNet) {
    copyNet.addEventListener('click', async function () {
      try {
        await navigator.clipboard.writeText(netUrl.textContent || '');
        App.toast('Link internet disalin.', 'success');
      } catch (e) {
        App.toast(netUrl.textContent || '', 'info');
      }
    });
  }

  const copyBtn = document.getElementById('copyUrl');
  if (copyBtn) {
    copyBtn.addEventListener('click', async function () {
      try {
        await navigator.clipboard.writeText(urls[0] || '');
        App.toast('URL disalin — tempel di Safari HP.', 'success');
      } catch (e) {
        App.toast(urls[0] || '', 'info');
      }
    });
  }

  App.initDropzone(drop, input, function (files) {
    if (busy) {
      App.toast('Masih ada proses kirim yang berjalan.', 'error');
      return;
    }
    startBatch(files);
  });

  function startBatch(files) {
    if (!files.length) return;
    batch = files.slice();
    queue = files.slice();
    loadedTotal = 0;
    batchDone = 0;
    batchTotal = files.reduce(function (a, b) { return a + b.size; }, 0);
    busy = true;
    progress.hidden = false;
    uploadStatus.hidden = false;
    progressBar.style.width = '0%';
    updateStatus('Mengirim 0/' + files.length);
    pump();
  }

  function updateStatus(text) {
    const pct = batchTotal ? Math.min(100, Math.round(loadedTotal / batchTotal * 100)) : 0;
    progressBar.style.width = pct + '%';
    uploadStatus.textContent = text + ' · ' + pct + '%';
  }

  function pump() {
    while (active < 2 && queue.length) {
      const f = queue.shift();
      active++;
      send(f).catch(function (e) {
        App.toast('Gagal mengirim "' + App.fitName(f.name, 40) + '": ' + e.message, 'error');
      }).then(function () {
        active--;
        batchDone++;
        updateStatus('Mengirim ' + batchDone + '/' + batch.length + '…');
        if (!active && !queue.length) finishBatch();
        else pump();
      });
    }
  }

  function finishBatch() {
    busy = false;
    setTimeout(function () {
      progress.hidden = true;
      progressBar.style.width = '0%';
      uploadStatus.hidden = true;
    }, 500);
    App.toast(batchDone + ' file terkirim ke laptop.', 'success');
    refreshList();
  }

  async function send(file) {
    let out = file;
    if (autoImg.checked && isCompressible(file)) {
      out = await maybeCompress(file);
    }
    return new Promise(function (resolve, reject) {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'api/receive.php');
      let last = 0;
      xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
          loadedTotal += e.loaded - last;
          last = e.loaded;
          updateStatus('Mengirim ' + batchDone + '/' + batch.length);
        }
      });
      xhr.addEventListener('load', function () {
        let j = null;
        try { j = JSON.parse(xhr.responseText); } catch (e) {}
        if (xhr.status === 401) {
          showGate();
          reject(new Error('PIN diperlukan.'));
          return;
        }
        if (xhr.status >= 200 && xhr.status < 300 && j && j.ok) {
          loadedTotal += file.size - last;
          resolve(j);
        } else {
          reject(new Error((j && j.error) ? j.error : 'HTTP ' + xhr.status));
        }
      });
      xhr.addEventListener('error', function () { reject(new Error('Koneksi terputus')); });
      const fd = new FormData();
      fd.append('action', 'upload');
      fd.append('files[]', out, out.name);
      xhr.send(fd);
    });
  }

  function isCompressible(file) {
    if (file.size < 700 * 1024) return false;
    if (file.type === 'image/gif') return false;
    if (/^image\//.test(file.type)) return true;
    return /\.(jpe?g|png|heic|heif|webp)$/i.test(file.name);
  }

  async function maybeCompress(file) {
    try {
      const url = URL.createObjectURL(file);
      const img = await new Promise(function (res, rej) {
        const i = new Image();
        i.onload = function () { res(i); };
        i.onerror = function () { rej(new Error('decode')); };
        i.src = url;
      });
      const max = 1920;
      let w = img.naturalWidth || img.width;
      let h = img.naturalHeight || img.height;
      if (!w || !h) { URL.revokeObjectURL(url); return file; }
      const scale = Math.max(w, h) > max ? max / Math.max(w, h) : 1;
      const c = document.createElement('canvas');
      c.width = Math.round(w * scale);
      c.height = Math.round(h * scale);
      const ctx = c.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, c.width, c.height);
      ctx.drawImage(img, 0, 0, c.width, c.height);
      URL.revokeObjectURL(url);
      const blob = await new Promise(function (res) { c.toBlob(res, 'image/jpeg', 0.85); });
      if (!blob || blob.size >= file.size) return file;
      const base = (file.name || 'foto').replace(/\.[^.]+$/, '') || 'foto';
      return new File([blob], base + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
    } catch (e) {
      return file;
    }
  }

  async function refreshList() {
    try {
      const res = await fetch('api/receive.php?action=list');
      if (res.status === 401) {
        showGate();
        return;
      }
      const j = await res.json();
      if (j && j.error && /PIN/.test(j.error)) {
        showGate();
        return;
      }
      renderList(j.files || []);
    } catch (e) {
      App.toast('Gagal memuat daftar file.', 'error');
    }
  }

  function fmtTime(t) {
    try {
      return new Date(t * 1000).toLocaleString('id', { dateStyle: 'medium', timeStyle: 'short' });
    } catch (e) {
      return '';
    }
  }

  function renderList(files) {
    listCount.textContent = files.length + ' file';
    fileList.innerHTML = '';
    if (!files.length) {
      const empty = App.el('div', 'empty', 'Belum ada file masuk.');
      empty.style.marginTop = '0';
      empty.id = 'listEmpty';
      fileList.appendChild(empty);
      return;
    }
    const table = App.el('div');
    table.style.display = 'flex';
    table.style.flexDirection = 'column';
    table.style.gap = '8px';
    files.forEach(function (f) {
      const row = App.el('div');
      row.className = 'filechip';
      row.style.padding = '10px 12px';
      const ic = App.el('div', 'fc-ico');
      ic.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v5h5"/><path d="M6 3h8l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/></svg>';
      const info = App.el('div', 'grow');
      info.style.minWidth = '0';
      const nm = App.el('div', 'name', f.name);
      nm.title = f.name;
      nm.style.fontWeight = '600';
      nm.style.fontSize = '14px';
      nm.style.overflow = 'hidden';
      nm.style.textOverflow = 'ellipsis';
      nm.style.whiteSpace = 'nowrap';
      const sub = App.el('div', 'muted small', App.fmtSize(f.size) + ' · ' + fmtTime(f.time));
      info.appendChild(nm);
      info.appendChild(sub);
      const dl = App.el('a', 'btn btn-primary btn-sm', 'Unduh');
      dl.href = 'api/receive.php?action=download&id=' + f.id;
      dl.setAttribute('download', '');
      const del = App.el('button', 'btn btn-danger-soft btn-sm', 'Hapus');
      del.type = 'button';
      del.addEventListener('click', async function () {
        del.disabled = true;
        try {
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('id', f.id);
          await fetch('api/receive.php', { method: 'POST', body: fd });
          refreshList();
        } catch (e) {
          App.toast('Gagal menghapus.', 'error');
          del.disabled = false;
        }
      });
      row.appendChild(ic);
      row.appendChild(info);
      row.appendChild(dl);
      row.appendChild(del);
      table.appendChild(row);
    });
    fileList.appendChild(table);
  }

  document.getElementById('refreshBtn').addEventListener('click', refreshList);

  document.getElementById('clearBtn').addEventListener('click', async function () {
    if (!confirm('Hapus semua file yang sudah masuk?')) return;
    try {
      const fd = new FormData();
      fd.append('action', 'clear');
      await fetch('api/receive.php', { method: 'POST', body: fd });
      refreshList();
      App.toast('Daftar dikosongkan.', 'success');
    } catch (e) {
      App.toast('Gagal menghapus.', 'error');
    }
  });

  setQr(activeQrUrl(), activeQrLabel());
  if (needPin) showGate();
  refreshList();
  loadTunnelStatus();
})();

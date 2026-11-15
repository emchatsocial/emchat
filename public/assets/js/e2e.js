/* EMChat Media — end-to-end encrypted direct messages (one-to-one chats), on by default.
 *
 * How it works (modelled on WhatsApp's linked devices)
 *  - The first time a user opens EMChat, their browser silently creates an ECDH
 *    P-256 key pair and registers the public key. No setup step.
 *  - A 1:1 chat key is HKDF(ECDH(my private, their public)), bound to the
 *    conversation and both key ids. Messages are AES-256-GCM with a random IV;
 *    conversation, sender and key ids are authenticated as associated data.
 *    Envelope in messages.body: e2e1:<senderKeyId>:<recipientKeyId>:<iv>:<ciphertext>
 *  - Signing in on another device: the new browser makes a one-time key and asks;
 *    an existing browser shows "Allow?" with a 6-digit code derived from that key,
 *    then sends the user's keys encrypted to it. The server only relays ciphertext,
 *    so the full history becomes readable on the new device.
 *  - Optional backup: the keys locked with a PIN (PBKDF2-SHA256, 600k) for when no
 *    other device is available. The server never sees the PIN or a private key.
 *  - Keys stay in this browser's IndexedDB across sign-outs.
 */
(function () {
  'use strict';
  var meta = document.querySelector('meta[name="emc-e2e"]');
  if (!meta || !window.crypto || !crypto.subtle || !window.indexedDB || !window.TextEncoder) return;

  var UID = parseInt(meta.content, 10);
  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var subtle = crypto.subtle, enc = new TextEncoder(), dec = new TextDecoder();
  var ITERATIONS = 600000, MAX_LEN = 4000, MIN_PIN = 6;
  var ECDH = { name: 'ECDH', namedCurve: 'P-256' };

  /* ---------- encoding ---------- */
  function b64(buf) { var b = new Uint8Array(buf), s = ''; for (var i = 0; i < b.length; i++) s += String.fromCharCode(b[i]); return btoa(s); }
  function unb64(s) { var bin = atob(s), b = new Uint8Array(bin.length); for (var i = 0; i < bin.length; i++) b[i] = bin.charCodeAt(i); return b; }
  function b64u(buf) { return b64(buf).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
  function unb64u(s) { s = s.replace(/-/g, '+').replace(/_/g, '/'); while (s.length % 4) s += '='; return unb64(s); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function short(s, n) { s = s.replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1) + '…' : s; }

  /* Same output as the server's rich_text(): escape, then links, @mentions, #hashtags, line breaks. */
  function richText(t) {
    var h = esc(t);
    h = h.replace(/\bhttps?:\/\/[^\s<]+/g, function (u) {
      return '<a href="' + u + '" rel="nofollow noopener" target="_blank">' + u.replace(/^https?:\/\/(www\.)?/, '') + '</a>';
    });
    h = h.replace(/(^|[^a-zA-Z0-9_\/])@([a-zA-Z0-9_]{1,30})/g, '$1<a href="/@$2">@$2</a>');
    h = h.replace(/(^|\s)#([a-zA-Z0-9_]{1,40})/g, '$1<span class="hashtag">#$2</span>');
    return h.replace(/(\r\n|\n)/g, '<br>$1');
  }

  /* ---------- http ---------- */
  function getJSON(url) {
    return fetch(url, { headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).catch(function () { return { ok: false, error: 'Network error' }; });
  }
  function postForm(url, data) {
    var p = new URLSearchParams(data); p.set('_csrf', CSRF);
    return fetch(url, { method: 'POST', credentials: 'same-origin', body: p,
      headers: { 'X-Requested-With': 'fetch', Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (r) { return r.json(); }).catch(function () { return { ok: false, error: 'Network error' }; });
  }

  /* ---------- IndexedDB (non-extractable private keys) ---------- */
  function idb() {
    return new Promise(function (res, rej) {
      var r = indexedDB.open('emchat-e2e', 1);
      r.onupgradeneeded = function () { r.result.createObjectStore('k', { keyPath: 'uid' }); };
      r.onsuccess = function () { res(r.result); };
      r.onerror = function () { rej(r.error); };
    });
  }
  function idbGet() {
    return idb().then(function (db) {
      return new Promise(function (res) {
        var q = db.transaction('k', 'readonly').objectStore('k').get(UID);
        q.onsuccess = function () { res(q.result || null); }; q.onerror = function () { res(null); };
      });
    }).catch(function () { return null; });
  }
  function idbPut(rec) {
    return idb().then(function (db) {
      return new Promise(function (res, rej) {
        var tx = db.transaction('k', 'readwrite'); tx.objectStore('k').put(rec);
        tx.oncomplete = function () { res(); }; tx.onerror = function () { rej(tx.error); };
      });
    });
  }

  /* ---------- state ---------- */
  var server = null;          // { key: {id, public_key} | null, has_backup }
  var privByPub = {};         // public key (b64) -> CryptoKey
  var keyCache = {};          // key id -> { user_id, public_key }
  var convKeys = {};          // "cid:sk:rk" -> AES key
  var peers = {};             // cid -> { key, at }

  async function loadState() {
    var st = await getJSON('/x/e2e/state');
    if (st && st.ok) {
      server = st;
      if (st.key) keyCache[st.key.id] = { user_id: UID, public_key: st.key.public_key };
    }
  }

  var ready = (async function () {
    var local = await idbGet();
    if (local && local.uid === UID) local.keys.forEach(function (k) { privByPub[k.pub] = k.priv; });
    await loadState();
    // Encryption is on by default: the first time, this browser silently creates the user's key.
    if (status() === 'none') { try { await createKeys(); } catch (e) {} }
  })();

  function status() {
    if (!server) return 'offline';
    if (!server.key) return 'none';
    return privByPub[server.key.public_key] ? 'ready' : 'locked';
  }

  /* ---------- keys ---------- */
  async function saveLocal() {
    var keys = [];
    for (var pub in privByPub) keys.push({ pub: pub, priv: privByPub[pub] });
    await idbPut({ uid: UID, keys: keys });
  }

  async function importPriv(pkcs8) { return subtle.importKey('pkcs8', unb64(pkcs8), ECDH, true, ['deriveBits']); }

  /** Add keys from a keyring { keys: { pub: pkcs8 } } (from another device or a backup). */
  async function addRing(ring) {
    for (var pub in (ring.keys || {})) privByPub[pub] = await importPriv(ring.keys[pub]);
    convKeys = {};
    await saveLocal();
  }

  /** All keys on this device, exported, to hand to a new device or put in a backup. */
  async function exportRing() {
    var ring = { v: 1, keys: {} };
    for (var pub in privByPub) {
      try { ring.keys[pub] = b64(await subtle.exportKey('pkcs8', privByPub[pub])); }
      catch (e) { /* keys saved by an earlier version can't be exported */ }
    }
    if (server && server.key && !ring.keys[server.key.public_key]) throw new Error('export');
    return ring;
  }

  /** First time (automatic), or "start fresh" on a device that can't get the old keys. */
  async function createKeys() {
    var kp = await subtle.generateKey(ECDH, true, ['deriveBits']);
    var pub = b64(await subtle.exportKey('spki', kp.publicKey));
    var res = await postForm('/x/e2e/setup', { public_key: pub });
    if (!res.ok) throw new Error(res.error || 'Could not set up encryption.');
    privByPub[pub] = kp.privateKey; convKeys = {};
    await saveLocal();
    var hadBackup = !!(server && server.has_backup);
    var mineIds = (server && server.my_keys ? server.my_keys : []).concat([res.key_id]);
    server = { key: { id: res.key_id, public_key: pub, created_at: null }, has_backup: hadBackup, backups: server ? server.backups : 0, my_keys: mineIds };
    keyCache[res.key_id] = { user_id: UID, public_key: pub };
    if (hadBackup) setStale(true);                 // this new key isn't in any backup yet
  }

  async function pinKey(pin, salt, iterations) {
    var base = await subtle.importKey('raw', enc.encode(pin.normalize('NFKC')), 'PBKDF2', false, ['deriveKey']);
    return subtle.deriveKey({ name: 'PBKDF2', salt: salt, iterations: iterations, hash: 'SHA-256' },
      base, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
  }

  /** Optional backup: the keys, locked with a PIN only the user knows. */
  /** replace: remove older backups (changing the PIN after proving the current one). */
  async function saveBackup(pin, replace) {
    var ring = await exportRing();
    var salt = crypto.getRandomValues(new Uint8Array(16)), iv = crypto.getRandomValues(new Uint8Array(12));
    var ct = await subtle.encrypt({ name: 'AES-GCM', iv: iv }, await pinKey(pin, salt, ITERATIONS), enc.encode(JSON.stringify(ring)));
    var res = await postForm('/x/e2e/backup', { keyring: b64u(iv) + ':' + b64u(ct), salt: b64u(salt), iterations: String(ITERATIONS), replace: replace ? '1' : '0' });
    if (!res.ok) throw new Error(res.error || 'Could not save the backup.');
    server.has_backup = true; server.backups = replace ? 1 : (server.backups || 0) + 1;
    setStale(false);
  }

  /** "Your newest key isn't in your backup yet" (after starting fresh or resetting the key). */
  var STALE_KEY = 'emc-e2e-backup-stale-' + UID;
  function setStale(on) { try { on ? localStorage.setItem(STALE_KEY, '1') : localStorage.removeItem(STALE_KEY); } catch (e) {} }
  function isStale() { try { return !!(server && server.has_backup) && localStorage.getItem(STALE_KEY) === '1'; } catch (e) { return false; } }

  async function deleteBackups() {
    var res = await postForm('/x/e2e/backup/delete', {});
    if (!res.ok) throw new Error(res.error || 'Could not remove the backup.');
    server.has_backup = false; server.backups = 0; setStale(false);
  }

  /** Forget the keys on this browser only (shared computer). */
  async function removeLocalKeys() {
    await new Promise(function (res) { var q = indexedDB.deleteDatabase('emchat-e2e'); q.onsuccess = q.onerror = q.onblocked = function () { res(); }; });
    privByPub = {}; convKeys = {};
  }

  /** Short fingerprint of a public key, to recognise it. */
  async function fingerprint(pubB64) {
    var h = new Uint8Array(await subtle.digest('SHA-256', unb64(pubB64))), out = [];
    for (var i = 0; i < 12; i += 2) out.push(('0000' + ((h[i] << 8) | h[i + 1]) % 100000).slice(-5));
    return out.join(' ');
  }

  /** Try the PIN on every backup the user ever made (old PINs keep working) and keep every key it unlocks. */
  async function restoreBackup(pin) {
    var b = await getJSON('/x/e2e/backup');
    if (!b.ok) throw new Error(b.error || 'Could not load your backup.');
    var list = b.backups || [], unlocked = 0, keys = {};
    for (var i = 0; i < list.length; i++) {
      var parts = String(list[i].keyring).split(':');
      try {
        var k = await pinKey(pin, unb64u(list[i].salt), list[i].iterations);
        var pt = await subtle.decrypt({ name: 'AES-GCM', iv: unb64u(parts[0]) }, k, unb64u(parts[1]));
        var ring = JSON.parse(dec.decode(pt));
        for (var pub in (ring.keys || {})) keys[pub] = ring.keys[pub];
        unlocked++;
      } catch (e) { /* a backup made with a different PIN */ }
    }
    if (!unlocked) throw new Error('That PIN doesn\'t match any of your backups.');
    await addRing({ keys: keys });
  }

  async function fetchKeys(ids) {
    var need = ids.map(String).filter(function (id, i, all) { return id && !keyCache[id] && all.indexOf(id) === i; });
    for (var i = 0; i < need.length; i += 100) {
      var r = await getJSON('/x/e2e/keys?ids=' + need.slice(i, i + 100).join(','));
      if (r.ok) for (var id in r.keys) keyCache[id] = r.keys[id];
    }
  }

  /** Everyone in a chat (1:1 or group) with their current key. Throws when it can't be checked, never "no key". */
  async function members(cid) {
    var r = await getJSON('/x/e2e/members/' + cid);
    if (!r.ok) throw new Error('offline');
    r.members.forEach(function (m) { if (m.key) keyCache[m.key.id] = { user_id: m.user_id, public_key: m.key.public_key }; });
    return r.members;
  }
  function missingKeys(list) { return list.filter(function (m) { return m.user_id !== UID && !m.key; }); }
  function isMyKeyId(id) { return !!(server && server.my_keys && server.my_keys.indexOf(+id) !== -1); }

  /* ----- original 1:1 format (e2e1), still decrypted ----- */
  function aad(cid, sender, sk, rk) { return enc.encode('emchat:' + cid + ':' + sender + ':' + sk + ':' + rk); }
  async function chatKey(cid, sk, rk, mine) {
    var id = cid + ':' + sk + ':' + rk;
    if (convKeys[id]) return convKeys[id];
    var my = keyCache[mine ? sk : rk], their = keyCache[mine ? rk : sk];
    if (!my || !their) throw new Error('nokey');
    if (my.user_id !== UID) throw new Error('nokey');
    var priv = privByPub[my.public_key];
    if (!priv) throw new Error(status() === 'locked' ? 'locked' : 'lost');
    var theirPub = await subtle.importKey('spki', unb64(their.public_key), ECDH, false, []);
    var bits = await subtle.deriveBits({ name: 'ECDH', public: theirPub }, priv, 256);
    var hk = await subtle.importKey('raw', bits, 'HKDF', false, ['deriveKey']);
    var key = await subtle.deriveKey({ name: 'HKDF', hash: 'SHA-256', salt: enc.encode('emchat-e2e-v1:' + cid),
      info: enc.encode(Math.min(sk, rk) + ':' + Math.max(sk, rk)) }, hk, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
    convKeys[id] = key;
    return key;
  }
  var ENV_RE = /^e2e1:(\d+):(\d+):([A-Za-z0-9_-]{16}):([A-Za-z0-9_-]+)$/;

  /* ----- current format (e2e2): one random message key, wrapped for every member's key ----- *
   * e2e2:<b64url(JSON {s: senderKeyId, w: {keyId: iv.wrapped}, iv, ct})>, ct = AES-GCM(JSON {v, t, a}).
   * Attachments: each file has its own random key, listed in "a" inside the encrypted payload.   */
  function aad2(cid, sender, s) { return enc.encode('emchat2:' + cid + ':' + sender + ':' + s); }
  async function wrapKey(cid, s, rid, myPriv, theirPubB64) {
    var id = 'w' + cid + ':' + s + ':' + rid;
    if (convKeys[id]) return convKeys[id];
    var theirPub = await subtle.importKey('spki', unb64(theirPubB64), ECDH, false, []);
    var bits = await subtle.deriveBits({ name: 'ECDH', public: theirPub }, myPriv, 256);
    var hk = await subtle.importKey('raw', bits, 'HKDF', false, ['deriveKey']);
    var key = await subtle.deriveKey({ name: 'HKDF', hash: 'SHA-256', salt: enc.encode('emchat-e2e2:' + cid), info: enc.encode(s + ':' + rid) },
      hk, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
    convKeys[id] = key;
    return key;
  }

  async function encryptV2(cid, payload, recipients) {
    var s = server.key.id, myPriv = privByPub[server.key.public_key];
    if (!myPriv) throw new Error('locked');
    var raw = crypto.getRandomValues(new Uint8Array(32));
    var msgKey = await subtle.importKey('raw', raw, 'AES-GCM', false, ['encrypt']);
    var iv = crypto.getRandomValues(new Uint8Array(12));
    var ct = await subtle.encrypt({ name: 'AES-GCM', iv: iv, additionalData: aad2(cid, UID, s) }, msgKey, enc.encode(JSON.stringify(payload)));
    var w = {}, seen = {};
    recipients = recipients.concat([{ id: s, public_key: server.key.public_key }]);   // always readable by the sender too
    for (var i = 0; i < recipients.length; i++) {
      var r = recipients[i]; if (seen[r.id]) continue; seen[r.id] = 1;
      var wk = await wrapKey(cid, s, r.id, myPriv, r.public_key);
      var wiv = crypto.getRandomValues(new Uint8Array(12));
      w[r.id] = b64u(wiv) + '.' + b64u(await subtle.encrypt({ name: 'AES-GCM', iv: wiv }, wk, raw));
    }
    return 'e2e2:' + b64u(enc.encode(JSON.stringify({ s: s, w: w, iv: b64u(iv), ct: b64u(ct) })));
  }

  function parseV2(env) {
    try { var d = JSON.parse(dec.decode(unb64u(env.slice(5)))); return d && d.w && d.s ? d : null; } catch (e) { return null; }
  }

  /** Key ids a message needs looked up: the sender's, plus any of mine it was encrypted for. */
  function neededIds(env) {
    var m = ENV_RE.exec(env); if (m) return [m[1], m[2]];
    if (env.indexOf('e2e2:') === 0) {
      var d = parseV2(env); if (!d) return [];
      return [String(d.s)].concat(Object.keys(d.w).filter(isMyKeyId));
    }
    return [];
  }

  /** @return {t: text, a: [attachment meta]} */
  async function decryptEl(el) {
    var env = el.getAttribute('data-e2e') || '';
    var cid = +el.getAttribute('data-e2e-cid'), sender = +el.getAttribute('data-e2e-sender');
    var m = ENV_RE.exec(env);
    if (m) {
      var sk = +m[1], rk = +m[2];
      if (!keyCache[sk] || keyCache[sk].user_id !== sender) throw new Error('bad');   // key must belong to the sender
      var key = await chatKey(cid, sk, rk, sender === UID);
      var pt = await subtle.decrypt({ name: 'AES-GCM', iv: unb64u(m[3]), additionalData: aad(cid, sender, sk, rk) }, key, unb64u(m[4]));
      return { t: dec.decode(pt), a: [] };
    }
    var d = env.indexOf('e2e2:') === 0 ? parseV2(env) : null;
    if (!d) throw new Error('bad');
    var sKey = keyCache[d.s];
    if (!sKey || sKey.user_id !== sender) throw new Error('bad');
    var mine = Object.keys(d.w).filter(isMyKeyId);
    if (!mine.length) throw new Error('notforyou');
    var usable = mine.filter(function (id) { return keyCache[id] && privByPub[keyCache[id].public_key]; });
    if (!usable.length) throw new Error(status() === 'locked' ? 'locked' : 'lost');
    var rid = usable[0], parts = d.w[rid].split('.');
    var wk = await wrapKey(cid, d.s, rid, privByPub[keyCache[rid].public_key], sKey.public_key);
    var raw = await subtle.decrypt({ name: 'AES-GCM', iv: unb64u(parts[0]) }, wk, unb64u(parts[1]));
    var msgKey = await subtle.importKey('raw', raw, 'AES-GCM', false, ['decrypt']);
    var p = JSON.parse(dec.decode(await subtle.decrypt({ name: 'AES-GCM', iv: unb64u(d.iv), additionalData: aad2(cid, sender, d.s) }, msgKey, unb64u(d.ct))));
    return { t: String(p.t || ''), a: Array.isArray(p.a) ? p.a : [] };
  }

  /* ---------- attachments ---------- */
  var MAX_FILE = 40 * 1024 * 1024;
  function humanSize(n) { return n < 1024 ? n + ' B' : n < 1048576 ? (n / 1024).toFixed(0) + ' KB' : (n / 1048576).toFixed(1) + ' MB'; }

  /** Photos are re-drawn on a canvas before encryption, which drops EXIF/GPS data (the server can't do it any more). */
  async function prepareFile(f) {
    if (/^image\/(jpeg|png|webp)$/.test(f.type)) {
      try {
        var bmp = await createImageBitmap(f, { imageOrientation: 'from-image' });
        var scale = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
        var w = Math.max(1, Math.round(bmp.width * scale)), h = Math.max(1, Math.round(bmp.height * scale));
        var c = document.createElement('canvas'); c.width = w; c.height = h; c.getContext('2d').drawImage(bmp, 0, 0, w, h);
        var type = f.type === 'image/png' ? 'image/png' : 'image/jpeg';
        var blob = await new Promise(function (res) { c.toBlob(res, type, 0.9); });
        if (blob) return { data: await blob.arrayBuffer(), type: type, name: f.name.replace(/\.[a-z0-9]+$/i, '') + (type === 'image/png' ? '.png' : '.jpg'), size: blob.size, w: w, h: h };
      } catch (e) {}
      throw new Error('This photo couldn\'t be processed. Try a JPG or PNG.');
    }
    if (/^image\//.test(f.type) && f.type !== 'image/gif') throw new Error('This photo format isn\'t supported. Use JPG, PNG, WebP or GIF.');
    return { data: await f.arrayBuffer(), type: f.type || 'application/octet-stream', name: f.name || 'file', size: f.size, w: 0, h: 0 };
  }

  var payloads = new WeakMap();      // .msg element -> decrypted payload (for edits)
  var blobUrls = {};
  async function openAttachment(url, meta) {
    var cacheId = url + '#' + meta.k;
    if (blobUrls[cacheId]) return blobUrls[cacheId];
    var res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('download');
    var key = await subtle.importKey('raw', unb64u(meta.k), 'AES-GCM', false, ['decrypt']);
    var pt = await subtle.decrypt({ name: 'AES-GCM', iv: unb64u(meta.iv) }, key, await res.arrayBuffer());
    return (blobUrls[cacheId] = URL.createObjectURL(new Blob([pt], { type: meta.m || 'application/octet-stream' })));
  }
  function attachmentNode(meta, src) {
    var m = meta.m || '', node;
    if (/^image\//.test(m)) {
      node = document.createElement('a'); node.className = 'msg__e2e-img'; node.href = src; node.target = '_blank'; node.rel = 'noopener';
      var img = document.createElement('img'); img.src = src; img.alt = meta.n || 'Photo'; if (meta.w) { img.width = meta.w; img.height = meta.h; }
      node.appendChild(img);
    } else if (/^video\//.test(m)) {
      node = document.createElement('video'); node.className = 'msg__video'; node.controls = true; node.preload = 'metadata'; node.src = src;
    } else if (/^audio\//.test(m)) {
      node = document.createElement('audio'); node.className = 'msg__audio'; node.controls = true; node.src = src;
    } else {
      node = document.createElement('a'); node.className = 'msg__file'; node.href = src; node.download = meta.n || 'file';
      node.innerHTML = '<span class="msg__file-ico">📎</span><span class="msg__file-meta"><span class="msg__file-name"></span><span class="msg__file-size"></span></span>';
      node.querySelector('.msg__file-name').textContent = meta.n || 'file';
      node.querySelector('.msg__file-size').textContent = humanSize(+meta.s || 0);
    }
    return node;
  }
  async function showAttachments(msg, list) {
    var box = msg && msg.querySelector('[data-e2e-att]');
    if (!box || box.hasAttribute('data-done')) return;
    box.setAttribute('data-done', '1');
    var urls = []; try { urls = JSON.parse(box.getAttribute('data-e2e-att')); } catch (e) {}
    var slots = box.querySelectorAll('.msg__e2e-loading');
    for (var i = 0; i < urls.length; i++) {
      var slot = slots[i], meta = list[i];
      if (!slot) continue;
      if (!meta) { slot.textContent = '🔒 Attachment can\'t be decrypted'; continue; }
      slot.textContent = '🔒 Decrypting ' + (/^image\//.test(meta.m) ? 'photo' : /^video\//.test(meta.m) ? 'video' : 'file') + '…';
      try { slot.replaceWith(attachmentNode(meta, await openAttachment(urls[i].u, meta))); }
      catch (e) { slot.textContent = '🔒 Couldn\'t load this attachment'; }
    }
  }

  /* ---------- decrypting what's on the page ---------- */
  function show(el, p) {
    var text = p.t, label = text || (p.a.length ? (/^image\//.test((p.a[0] || {}).m) ? 'Photo' : 'Attachment') : '');
    el.setAttribute('data-e2e-done', '1');
    if (el.hasAttribute('data-e2e-short')) { el.textContent = short(label, 90); return; }
    el.innerHTML = richText(text);
    el.setAttribute('data-plain', text);
    el.hidden = !text;
    var msg = el.closest('.msg');
    if (!msg) return;
    payloads.set(msg, p);
    var copy = msg.querySelector('[data-e2e-copy]'); if (copy) { copy.setAttribute('data-copy', text); copy.hidden = !text; }
    var rep = msg.querySelector('[data-msg-reply]'); if (rep) rep.setAttribute('data-snippet', short(label, 90));
    var ta = msg.querySelector('textarea[data-e2e-fill]'); if (ta && ta.closest('form').hidden) ta.value = text;
    if (p.a.length) showAttachments(msg, p.a);
  }
  function failed(el, reason) {
    el.setAttribute('data-e2e-done', 'fail');
    el.hidden = false;
    if (reason === 'locked') {
      el.innerHTML = '<span class="e2e-pending">🔒 <button type="button" class="e2e-link" data-e2e-unlock>Unlock to read</button></span>';
    } else if (reason === 'notforyou') {
      el.innerHTML = '<span class="e2e-pending">🔒 Sent before you could read this chat</span>';
    } else if (server && server.has_backup && !el.hasAttribute('data-e2e-short')) {
      el.innerHTML = '<span class="e2e-pending">🔒 Can\'t be decrypted on this device · <button type="button" class="e2e-link" data-e2e-restore>Restore from backup</button></span>';
    } else {
      el.innerHTML = '<span class="e2e-pending">🔒 Can\'t be decrypted on this device</span>';
    }
    var msg = el.closest('.msg'), box = msg && msg.querySelector('[data-e2e-att]:not([data-done])');
    if (box && !el.hasAttribute('data-e2e-short')) box.querySelectorAll('.msg__e2e-loading').forEach(function (s) { s.textContent = '🔒 Encrypted attachment'; });
  }

  var busy = false, again = false;
  async function processAll() {
    if (busy) { again = true; return; }
    busy = true;
    try {
      await ready;
      var els = Array.prototype.slice.call(document.querySelectorAll('[data-e2e]:not([data-e2e-done])'));
      if (els.length) {
        var ids = [];
        els.forEach(function (el) { ids = ids.concat(neededIds(el.getAttribute('data-e2e') || '')); });
        await fetchKeys(ids);
        for (var i = 0; i < els.length; i++) {
          try { show(els[i], await decryptEl(els[i])); }
          catch (e) { failed(els[i], e && e.message); }
        }
      }
    } finally {
      busy = false;
      if (again) { again = false; processAll(); }
    }
  }
  function retryFailed() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-e2e-done="fail"]'), function (el) { el.removeAttribute('data-e2e-done'); });
    Array.prototype.forEach.call(document.querySelectorAll('[data-e2e-att][data-done]'), function (b) {
      if (b.querySelector('.msg__e2e-loading')) b.removeAttribute('data-done');
    });
    convKeys = {};
    processAll();
  }
  new MutationObserver(function () {
    if (document.querySelector('[data-e2e]:not([data-e2e-done])')) processAll();
  }).observe(document.body, { childList: true, subtree: true });

  /* ---------- dialogs ---------- */
  var overlay = null;
  function closeDialog() { if (overlay) { overlay.remove(); overlay = null; } }
  function dialog(html) {
    closeDialog();
    overlay = document.createElement('div'); overlay.className = 'modal-overlay';
    var panel = document.createElement('div'); panel.className = 'modal-panel e2e-dialog'; panel.innerHTML = html;
    overlay.appendChild(panel); document.body.appendChild(overlay);
    requestAnimationFrame(function () { overlay.classList.add('is-in'); });
    var first = panel.querySelector('input'); if (first) setTimeout(function () { first.focus(); }, 50);
    return panel;
  }
  function busyBtn(btn, on, label) { btn.disabled = on; if (label) btn.textContent = label; }
  function done() { closeDialog(); refreshStatus(); retryFailed(); }

  /** This browser doesn't have the user's key (cleared browser data, new browser or device): explain and offer the ways back. */
  function linkDialog() {
    var hasBackup = !!(server && server.has_backup);
    var p = dialog(
      '<h3 class="modal-title">Unlock your messages</h3>' +
      '<p class="modal-sub">Your chats are end-to-end encrypted, and this browser doesn\'t have your key. ' +
      'This happens after clearing your browser data or signing in on a new browser or device.</p>' +
      '<div class="modal-actions modal-actions--stack">' +
      (hasBackup ? '<button class="btn btn--primary btn--block" type="button" data-pin>Enter my backup PIN</button>' : '') +
      '<button class="btn ' + (hasBackup ? 'btn--ghost' : 'btn--primary') + ' btn--block" type="button" data-fresh>Start fresh on this browser</button>' +
      '<button class="btn btn--ghost btn--block e2e-forgot" type="button" data-later>Not now</button></div>' +
      (hasBackup ? '' : '<p class="field__hint">You haven\'t set a backup PIN, so earlier encrypted messages can\'t be restored here. ' +
        'Start fresh to keep chatting: new messages work right away.</p>'));
    var pin = p.querySelector('[data-pin]'); if (pin) pin.addEventListener('click', restoreDialog);
    p.querySelector('[data-fresh]').addEventListener('click', freshDialog);
    p.querySelector('[data-later]').addEventListener('click', closeDialog);
  }

  function restoreDialog() {
    var p = dialog(
      '<h3 class="modal-title">Restore from backup</h3>' +
      '<p class="modal-sub">Enter a backup PIN you\'ve set before. If you\'ve used more than one, try each: every one restores the messages it protected.</p>' +
      '<form class="e2e-form" novalidate>' +
      '<label class="field"><span class="field__label">Backup PIN</span><input type="password" name="pin" autocomplete="current-password" required></label>' +
      '<p class="e2e-error" data-err hidden></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--primary btn--block" type="submit">Restore</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-back>Back</button></div></form>');
    var form = p.querySelector('form'), err = p.querySelector('[data-err]');
    p.querySelector('[data-back]').addEventListener('click', function () { if (status() === 'locked') linkDialog(); else closeDialog(); });
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type=submit]'); err.hidden = true; busyBtn(btn, true, 'Restoring…');
      try { await restoreBackup(form.pin.value); done(); renderSettings(); }
      catch (ex) { err.textContent = ex.message || 'Something went wrong.'; err.hidden = false; busyBtn(btn, false, 'Restore'); }
    });
  }

  function freshDialog() {
    var p = dialog(
      '<h3 class="modal-title">Start fresh on this browser?</h3>' +
      ((server && server.has_backup)
        ? '<p class="modal-sub">You have a backup PIN. Use it instead to keep all your earlier messages. ' +
          'If you start fresh without it, your earlier encrypted messages <strong>won\'t be readable on this device</strong>.</p>'
        : '<p class="modal-sub"><strong>You\'ll permanently lose access to your earlier encrypted messages on this device.</strong> ' +
          'This can\'t be undone, not even by EMChat.</p>') +
      '<p class="field__hint">New messages will work here right away. The people you chat with keep their copy of the conversation.</p>' +
      '<p class="e2e-error" data-err hidden></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      ((server && server.has_backup) ? '<button class="btn btn--primary btn--block" type="button" data-pin>Use my backup PIN</button>' : '') +
      '<button class="btn btn--danger btn--block" type="button" data-go>Start fresh anyway</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-back>Back</button></div>');
    var err = p.querySelector('[data-err]');
    p.querySelector('[data-back]').addEventListener('click', linkDialog);
    var usePin = p.querySelector('[data-pin]'); if (usePin) usePin.addEventListener('click', restoreDialog);
    p.querySelector('[data-go]').addEventListener('click', async function (e) {
      busyBtn(e.target, true, 'Setting up…');
      try { await createKeys(); done(); renderSettings(); }
      catch (ex) { err.textContent = ex.message || 'Something went wrong.'; err.hidden = false; busyBtn(e.target, false, 'Start fresh anyway'); }
    });
  }

  /** Optional: protect history with a backup PIN (for when no other device is available). */
  function backupDialog(reminder) {
    var p = dialog(
      (reminder && isStale()
        ? '<h3 class="modal-title">Update your backup</h3>' +
          '<p class="modal-sub">You have a new encryption key on this browser, so your newest messages aren\'t in your backup yet. ' +
          'Enter a PIN to update it. You can reuse your current PIN.</p>'
        : reminder
        ? '<h3 class="modal-title">Keep your messages safe</h3>' +
          '<p class="modal-sub">Your chats are end-to-end encrypted, so only your devices can read them. Set a backup PIN so you can ' +
          'restore them if you clear your browser or switch devices. We can\'t see or recover your PIN.</p>'
        : isStale()
        ? '<h3 class="modal-title">Update your backup</h3>' +
          '<p class="modal-sub">Add your newest key to your backup. You can reuse your current PIN.</p>'
        : '<h3 class="modal-title">Encrypted backup</h3>' +
          '<p class="modal-sub">A backup PIN lets you restore your encrypted messages if you clear your browser or lose access to all your devices. ' +
          'We can\'t see or recover your PIN.</p>') +
      '<form class="e2e-form" novalidate>' +
      '<label class="field"><span class="field__label">Backup PIN</span><input type="password" name="pin" autocomplete="new-password" required></label>' +
      '<label class="field"><span class="field__label">Repeat PIN</span><input type="password" name="pin2" autocomplete="new-password" required></label>' +
      '<p class="field__hint">At least ' + MIN_PIN + ' characters. A longer PIN with letters is safer.</p>' +
      '<p class="e2e-error" data-err hidden></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--primary btn--block" type="submit">Set up backup</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-later>' + (reminder ? 'Later' : 'Cancel') + '</button></div></form>');
    var form = p.querySelector('form'), err = p.querySelector('[data-err]');
    p.querySelector('[data-later]').addEventListener('click', function () { if (reminder) snoozeReminder(); closeDialog(); });
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var pin = form.pin.value, btn = form.querySelector('[type=submit]'); err.hidden = true;
      if (pin.length < MIN_PIN) { err.textContent = 'Use at least ' + MIN_PIN + ' characters.'; err.hidden = false; return; }
      if (pin !== form.pin2.value) { err.textContent = 'The two PINs don\'t match.'; err.hidden = false; return; }
      busyBtn(btn, true, 'Saving…');
      try { await saveBackup(pin); done(); renderSettings(); }
      catch (ex) {
        err.textContent = ex.message === 'export' ? 'This device can\'t create a backup. Try from another device.' : (ex.message || 'Something went wrong.');
        err.hidden = false; busyBtn(btn, false, 'Set up backup');
      }
    });
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-e2e-unlock]')) { e.preventDefault(); linkDialog(); }
    else if (e.target.closest('[data-e2e-restore]')) { e.preventDefault(); restoreDialog(); }
    else if (e.target.closest('[data-e2e-backup]')) { e.preventDefault(); backupDialog(false); }
  });

  /* ---------- status line above the message box (1:1 and group chats) ---------- */
  var panel = document.querySelector('[data-conversation][data-e2e-chat="1"]');
  function namesOf(list) {
    var n = list.map(function (m) { return '@' + esc(m.username); });
    return n.length > 3 ? n.slice(0, 3).join(', ') + ' and ' + (n.length - 3) + ' more' : n.join(', ');
  }
  async function refreshStatus() {
    if (!panel) return;
    var line = panel.querySelector('[data-e2e-status]');
    if (!line) return;
    await ready;
    var st = status(), html = '';
    if (st === 'locked') {
      html = '🔒 Your encrypted messages are locked on this browser. <button type="button" class="e2e-link" data-e2e-unlock>Unlock</button>';
    } else if (st === 'ready') {
      var mem;
      try { mem = await members(+panel.getAttribute('data-conversation')); } catch (e) { return; }
      var missing = missingKeys(mem);
      html = !missing.length
        ? '🔒 Messages, photos and files are end-to-end encrypted.'
        : '🔓 Not encrypted yet: ' + namesOf(missing) + (missing.length === 1 ? ' needs' : ' need') + ' to open EMChat once to turn it on.';
      if (!missing.length && server && (!server.has_backup || isStale())) html += ' <button type="button" class="e2e-link" data-e2e-backup>' + (isStale() ? 'Update backup' : 'Set up backup') + '</button>';
    }
    line.innerHTML = html; line.hidden = !html;
  }

  /* ---------- hooks used by app.js ---------- */
  function statusError(form, msg) {
    var line = form.querySelector('[data-e2e-status]');
    if (line) { line.innerHTML = '<span class="e2e-error">' + esc(msg) + '</span>'; line.hidden = false; }
    else alert(msg);
  }

  /** Recipients' keys for a chat, or null when someone can't receive encrypted messages yet (then it's sent as before). */
  async function recipientKeys(cid) {
    var mem = await members(cid);
    if (missingKeys(mem).length) return null;
    return mem.filter(function (m) { return m.key; }).map(function (m) { return m.key; });
  }

  async function prepareSend(form, fd) {
    var pnl = form.closest('[data-conversation]');
    if (!pnl || pnl.getAttribute('data-e2e-chat') !== '1') return fd;
    try {
      await ready;
      var text = String(fd.get('body') || '');
      var files = fd.getAll('files[]').filter(function (f) { return f && f.size; });
      if (!text.trim() && !files.length) return fd;
      var st = status();
      if (st === 'locked') { linkDialog(); return null; }
      if (st !== 'ready') { statusError(form, 'Couldn\'t set up encryption. Check your connection and try again.'); return null; }
      var cid = +pnl.getAttribute('data-conversation');
      var recips = await recipientKeys(cid);        // always current: people may have just joined or reset their key
      if (!recips) return fd;                         // someone hasn't opened EMChat since encryption started
      if (text.length > MAX_LEN) { statusError(form, 'Messages can be up to ' + MAX_LEN + ' characters.'); return null; }
      var a = [], blobs = [];
      for (var i = 0; i < files.length; i++) {
        if (files[i].size > MAX_FILE) { statusError(form, '"' + files[i].name + '" is larger than 40 MB.'); return null; }
        var f = await prepareFile(files[i]);
        var k = crypto.getRandomValues(new Uint8Array(32)), iv = crypto.getRandomValues(new Uint8Array(12));
        var fk = await subtle.importKey('raw', k, 'AES-GCM', false, ['encrypt']);
        blobs.push(new Blob([await subtle.encrypt({ name: 'AES-GCM', iv: iv }, fk, f.data)], { type: 'application/octet-stream' }));
        a.push({ k: b64u(k), iv: b64u(iv), n: f.name, m: f.type, s: f.size, w: f.w, h: f.h });
      }
      if (files.length) { fd.delete('files[]'); blobs.forEach(function (b) { fd.append('files[]', b, 'encrypted.bin'); }); }
      fd.set('body', await encryptV2(cid, { v: 2, t: text, a: a }, recips));
      setTimeout(maybeRemindBackup, 2000);
      return fd;
    } catch (e) {
      statusError(form, e && e.message && e.message.length > 12 ? e.message : 'Couldn\'t encrypt this message. Refresh the page and try again.');
      return null;
    }
  }

  async function prepareEdit(form, fd) {
    var ta = form.querySelector('textarea[data-e2e-fill]');
    if (!ta) return fd;                                  // original wasn't encrypted: edit as before
    var m = /\/messages\/(\d+)\/m\//.exec(form.getAttribute('action') || '');
    var old = payloads.get(form.closest('.msg'));
    try {
      await ready;
      if (status() !== 'ready') { linkDialog(); return null; }
      if (!m || !old) { alert('This message can\'t be edited on this device.'); return null; }
      var recips = await recipientKeys(+m[1]);
      if (!recips) { alert('This message can\'t be edited right now.'); return null; }
      var text = String(fd.get('body') || '');
      if (text.length > MAX_LEN) { alert('Messages can be up to ' + MAX_LEN + ' characters.'); return null; }
      fd.set('body', await encryptV2(+m[1], { v: 2, t: text, a: old.a }, recips));   // attachments stay as they were
      return fd;
    } catch (e) {
      alert('Couldn\'t encrypt this message. Refresh the page and try again.');
      return null;
    }
  }

  window.EmcE2E = { prepareSend: prepareSend, prepareEdit: prepareEdit };

  /* ---------- one-time backup reminder ---------- */
  var REMIND_KEY = 'emc-e2e-backup-remind-' + UID;
  function snoozeReminder() { try { localStorage.setItem(REMIND_KEY, String(Date.now() + 7 * 864e5)); } catch (e) {} }
  async function maybeRemindBackup() {
    if (!panel || overlay || status() !== 'ready' || !server || (server.has_backup && !isStale())) return;
    try { if (+localStorage.getItem(REMIND_KEY) > Date.now()) return; } catch (e) { return; }
    if (!document.querySelector('[data-e2e][data-e2e-done="1"]')) return;     // only once they have an encrypted conversation
    snoozeReminder();                                                          // show it at most once a week
    backupDialog(true);
  }


  /* ---------- Settings → Encryption ---------- */
  var settingsBox = document.querySelector('[data-e2e-settings]');

  function changePinDialog() {
    var p = dialog(
      '<h3 class="modal-title">Change backup PIN</h3>' +
      '<p class="modal-sub">Enter your current PIN, then choose a new one. Your old PIN will stop working.</p>' +
      '<form class="e2e-form" novalidate>' +
      '<label class="field"><span class="field__label">Current PIN</span><input type="password" name="cur" autocomplete="current-password" required></label>' +
      '<label class="field"><span class="field__label">New PIN</span><input type="password" name="pin" autocomplete="new-password" required></label>' +
      '<label class="field"><span class="field__label">Repeat new PIN</span><input type="password" name="pin2" autocomplete="new-password" required></label>' +
      '<p class="field__hint">At least ' + MIN_PIN + ' characters. A longer PIN with letters is safer.</p>' +
      '<p class="e2e-error" data-err hidden></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--primary btn--block" type="submit">Change PIN</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-x>Cancel</button>' +
      '<button class="btn btn--ghost btn--block e2e-forgot" type="button" data-forgot>Forgot your current PIN?</button></div></form>');
    var form = p.querySelector('form'), err = p.querySelector('[data-err]');
    p.querySelector('[data-x]').addEventListener('click', closeDialog);
    p.querySelector('[data-forgot]').addEventListener('click', forgotPinDialog);
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var pin = form.pin.value, btn = form.querySelector('[type=submit]'); err.hidden = true;
      if (pin.length < MIN_PIN) { err.textContent = 'Use at least ' + MIN_PIN + ' characters.'; err.hidden = false; return; }
      if (pin !== form.pin2.value) { err.textContent = 'The two new PINs don\'t match.'; err.hidden = false; return; }
      busyBtn(btn, true, 'Changing…');
      try {
        await restoreBackup(form.cur.value);      // proves the current PIN, and gathers every key it protects
        await saveBackup(pin, true);              // one new backup with all keys; older backups removed
        done(); renderSettings();
      } catch (ex) {
        err.textContent = ex.message === 'export' ? 'This browser can\'t create a backup.' : (ex.message || 'Something went wrong.');
        err.hidden = false; busyBtn(btn, false, 'Change PIN');
      }
    });
  }

  function forgotPinDialog() {
    var p = dialog(
      '<h3 class="modal-title">Forgot your PIN?</h3>' +
      '<p class="modal-sub">We can\'t recover it. You can make a new backup with a new PIN: it protects the messages this browser can read now. ' +
      'Your older backup is kept, so if you remember the old PIN later it will still restore older messages.</p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--primary btn--block" type="button" data-new>Make a new backup</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-x>Cancel</button></div>');
    p.querySelector('[data-x]').addEventListener('click', closeDialog);
    p.querySelector('[data-new]').addEventListener('click', function () { backupDialog(false); });
  }

  function confirmDialog(title, text, okLabel, danger, onOk) {
    var p = dialog('<h3 class="modal-title">' + title + '</h3><p class="modal-sub">' + text + '</p>' +
      '<p class="e2e-error" data-err hidden></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn ' + (danger ? 'btn--danger' : 'btn--primary') + ' btn--block" type="button" data-ok>' + okLabel + '</button>' +
      '<button class="btn btn--ghost btn--block" type="button" data-x>Cancel</button></div>');
    var err = p.querySelector('[data-err]');
    p.querySelector('[data-x]').addEventListener('click', closeDialog);
    p.querySelector('[data-ok]').addEventListener('click', async function (e) {
      busyBtn(e.target, true, 'Working…');
      try { await onOk(); closeDialog(); renderSettings(); refreshStatus(); }
      catch (ex) { err.textContent = ex.message || 'Something went wrong.'; err.hidden = false; busyBtn(e.target, false, okLabel); }
    });
  }

  async function renderSettings() {
    if (!settingsBox) return;
    await ready;
    var st = status(), b = server || {}, html = '';
    var date = b.key && b.key.created_at ? new Date(b.key.created_at.replace(' ', 'T') + 'Z').toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';

    html += '<section class="panel e2e-set"><h2 class="panel__title">This browser</h2>';
    if (st === 'ready') {
      html += '<p>🔒 <strong>Encryption is on.</strong> This browser has your key and can read your encrypted messages.</p>';
      if (b.key) html += '<p class="muted e2e-set__meta">Key fingerprint <code>' + esc(await fingerprint(b.key.public_key)) + '</code>' + (date ? ' · created ' + esc(date) : '') + '</p>';
    } else if (st === 'locked') {
      html += '<p>🔒 <strong>Your messages are locked on this browser.</strong> Enter your backup PIN or start fresh.</p>' +
        '<div class="e2e-set__actions"><button class="btn btn--primary btn--sm" type="button" data-e2e-unlock>Unlock</button></div>';
    } else {
      html += '<p class="muted">Couldn\'t load your encryption status. Check your connection and refresh.</p>';
    }
    html += '</section>';

    html += '<section class="panel e2e-set"><h2 class="panel__title">Backup PIN</h2>';
    if (!b.has_backup) {
      html += '<p class="muted">No backup yet. Without one, clearing your browser data or switching browsers means losing access to your earlier encrypted messages.</p>' +
        (st === 'ready' ? '<div class="e2e-set__actions"><button class="btn btn--primary btn--sm" type="button" data-set="create">Set up backup PIN</button></div>' : '');
    } else {
      html += '<p>✅ <strong>Your backup is on.</strong> Use your PIN to restore your messages after clearing your browser or on a new browser.</p>';
      if (isStale()) html += '<p class="e2e-error">Your newest key isn\'t in your backup yet. Update it so recent messages are protected too.</p>';
      html += '<div class="e2e-set__actions">' +
        (st === 'ready' && isStale() ? '<button class="btn btn--primary btn--sm" type="button" data-set="create">Update backup</button>' : '') +
        (st === 'ready' ? '<button class="btn btn--ghost btn--sm" type="button" data-set="change">Change PIN</button>' : '') +
        '<button class="btn btn--ghost btn--sm" type="button" data-e2e-restore>Restore from backup</button>' +
        '<button class="btn btn--ghost btn--sm e2e-danger" type="button" data-set="delete">Remove backup</button></div>';
    }
    html += '</section>';

    html += '<section class="panel e2e-set"><h2 class="panel__title">Manage your key</h2>' +
      '<div class="e2e-set__row"><div><strong>Create a new key</strong><p class="muted">Switch to a new encryption key for new messages, for example if you think someone had access to this browser. This browser keeps the old key so your history stays readable here.</p></div>' +
      '<button class="btn btn--ghost btn--sm e2e-danger" type="button" data-set="reset">New key</button></div>' +
      (st === 'ready' ? '<div class="e2e-set__row"><div><strong>Remove key from this browser</strong><p class="muted">For shared or public computers. Your messages stay encrypted on EMChat. To read them here again you\'ll need your backup PIN.</p></div>' +
        '<button class="btn btn--ghost btn--sm e2e-danger" type="button" data-set="forget">Remove</button></div>' : '') +
      '</section>';

    settingsBox.innerHTML = html;
  }

  if (settingsBox) settingsBox.addEventListener('click', function (e) {
    var t = e.target.closest('[data-set]'); if (!t) return;
    var a = t.getAttribute('data-set');
    if (a === 'create') backupDialog(false);
    else if (a === 'change') changePinDialog();
    else if (a === 'delete') confirmDialog('Remove your backup?',
      'Your backup PIN will stop working. If you clear your browser data or switch browsers later, you won\'t be able to restore your earlier encrypted messages.',
      'Remove backup', true, deleteBackups);
    else if (a === 'reset') confirmDialog('Create a new key?',
      'New messages will use the new key right away. This browser keeps your current key too, so your earlier messages stay readable here' +
      ((server && server.has_backup) ? '. Update your backup afterwards so it includes the new key.' : '. You don\'t have a backup, so on any other browser they\'ll be lost.'),
      'Create new key', true, createKeys);
    else if (a === 'forget') confirmDialog('Remove your key from this browser?',
      (server && server.has_backup)
        ? 'Your encrypted messages will be locked on this browser until you enter your backup PIN.'
        : '<strong>You don\'t have a backup.</strong> Your earlier encrypted messages will be unreadable on this browser for good. Set up a backup PIN first if you want to keep them.',
      'Remove key', true, removeLocalKeys);
  });

  /* ---------- start ---------- */
  ready.then(function () {
    processAll();
    refreshStatus();
    if (status() === 'locked' && /^\/messages/.test(location.pathname)) linkDialog();
    setTimeout(maybeRemindBackup, 2500);
    renderSettings();
  });
})();

/* EMChat Media — progressive enhancement. No dependencies. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var CSRF = (($('meta[name="csrf-token"]') || {}).content) || '';

  /* ============ Toasts ============ */
  var toastHost = $('#toasts') || (function () {
    var d = document.createElement('div'); d.id = 'toasts'; d.className = 'toasts'; document.body.appendChild(d); return d;
  })();
  function toast(msg, type, opts) {
    opts = opts || {};
    var el = document.createElement('div');
    el.className = 'toast toast--' + (type || 'info');
    el.setAttribute('role', 'status');
    el.innerHTML = '<span class="toast__ico"></span><span class="toast__msg"></span>';
    el.querySelector('.toast__msg').textContent = msg;
    toastHost.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-in'); });
    var timer;
    function dismiss() {
      clearTimeout(timer);
      el.classList.remove('is-in'); el.classList.add('is-out');
      setTimeout(function () { el.remove(); }, 260);
    }
    if (type !== 'loading' && !opts.sticky) timer = setTimeout(dismiss, opts.duration || 3400);
    el.addEventListener('click', dismiss);
    return {
      el: el,
      update: function (m, t) {
        el.className = 'toast toast--' + (t || 'info') + ' is-in';
        el.querySelector('.toast__msg').textContent = m;
        clearTimeout(timer);
        if (t !== 'loading') timer = setTimeout(dismiss, 3000);
      },
      close: dismiss
    };
  }
  window.emToast = toast;

  /* ============ Avatar image fallback ============ */
  /* CSP has no 'unsafe-inline' for script-src, so this can't be an
     onerror="" attribute — it has to be a real listener. img error events
     don't bubble, so this must run on the capture phase. */
  document.addEventListener('error', function (e) {
    var img = e.target;
    if (!img || img.tagName !== 'IMG' || !img.classList.contains('avatar')) return;
    var fallback = img.nextElementSibling;
    img.hidden = true;
    if (fallback) fallback.hidden = false;
  }, true);

  // flash -> toast on load
  $$('[data-flash]').forEach(function (bar) {
    toast(bar.textContent.trim(), bar.getAttribute('data-type') || 'success');
    bar.remove();
  });

  /* ============ HTTP ============ */
  function post(url, data, onProgress) {
    return new Promise(function (resolve) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', url);
      xhr.setRequestHeader('X-Requested-With', 'fetch');
      xhr.setRequestHeader('Accept', 'application/json');
      if (onProgress && xhr.upload) xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100));
      });
      xhr.onload = function () {
        var r; try { r = JSON.parse(xhr.responseText); } catch (e) { r = { ok: false }; }
        resolve(r);
      };
      xhr.onerror = function () { resolve({ ok: false, error: 'Network error' }); };
      if (data instanceof FormData) { data.set('_csrf', CSRF); xhr.send(data); }
      else {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        var p = new URLSearchParams(data || {}); p.set('_csrf', CSRF);
        xhr.send(p.toString());
      }
    });
  }
  function getJSON(url) {
    return fetch(url, { headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).catch(function () { return { ok: false }; });
  }

  /* ============ Theme ============ */
  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var root = document.documentElement, cur = root.dataset.theme;
      var sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      var next = cur ? (cur === 'dark' ? 'light' : 'dark') : (sysDark ? 'light' : 'dark');
      root.dataset.theme = next;
      try { localStorage.setItem('emc-theme', next); } catch (e) {}
      toast(next === 'dark' ? 'Dark theme' : 'Light theme', 'info', { duration: 1400 });
    });
  });

  /* ============ Composer modal ============ */
  var modal = $('#composer');
  function openComposer() { if (modal) { modal.hidden = false; var t = $('textarea', modal); if (t) t.focus(); } }
  function closeComposer() { if (modal) modal.hidden = true; }
  $$('[data-open-composer]').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); openComposer(); }); });
  $$('[data-close-composer]').forEach(function (b) { b.addEventListener('click', closeComposer); });
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeComposer(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeComposer(); closeModal(); } });

  /* ============ Autogrow + counters ============ */
  function grow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 360) + 'px'; }
  function bindGrow(el) { if (el.dataset.grown) return; el.dataset.grown = '1'; grow(el); el.addEventListener('input', function () { grow(el); }); }
  $$('textarea[data-autogrow]').forEach(bindGrow);
  function bindCount(form) {
    var ta = $('textarea[name=body]', form), counter = $('[data-count]', form);
    if (ta && counter) { var u = function () { counter.textContent = 2000 - ta.value.length; }; ta.addEventListener('input', u); u(); }
  }
  $$('.composer').forEach(bindCount);

  /* ============ Image previews (composer) ============ */
  $$('[data-image-input]').forEach(function (input) {
    input.addEventListener('change', function () {
      var box = $('[data-previews]', input.closest('.composer'));
      if (!box) return;
      box.innerHTML = ''; box.hidden = !input.files.length;
      Array.prototype.forEach.call(input.files, function (file) {
        var img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.onload = function () { URL.revokeObjectURL(img.src); };
        box.appendChild(img);
      });
    });
  });

  /* ============ Generic modal (report) ============ */
  var overlay;
  function openModal(node) {
    closeModal();
    overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    var panel = document.createElement('div');
    panel.className = 'modal-panel';
    panel.appendChild(node);
    overlay.appendChild(panel);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
    document.body.appendChild(overlay);
    requestAnimationFrame(function () { overlay.classList.add('is-in'); });
  }
  function closeModal() { if (overlay) { overlay.remove(); overlay = null; } }

  function reportDialog(form) {
    var reasons = [['spam', 'Spam or scam'], ['abuse', 'Harassment or hate'], ['nsfw', 'Sensitive content'],
      ['violence', 'Violence or threats'], ['impersonation', 'Impersonation'], ['other', 'Something else']];
    var label = form.getAttribute('data-report-label') || 'post';
    var wrap = document.createElement('div');
    wrap.innerHTML = '<h3 class="modal-title">Report this ' + label + '</h3><p class="modal-sub">What\'s wrong with it?</p>' +
      '<div class="modal-reasons">' + reasons.map(function (r) {
        return '<label class="modal-reason"><input type="radio" name="r" value="' + r[0] + '"' +
          (r[0] === 'spam' ? ' checked' : '') + '><span>' + r[1] + '</span></label>';
      }).join('') + '</div>' +
      '<div class="modal-actions"><button class="btn btn--ghost btn--sm" data-x>Cancel</button>' +
      '<button class="btn btn--danger btn--sm" data-go>Submit report</button></div>';
    wrap.querySelector('[data-x]').addEventListener('click', closeModal);
    wrap.querySelector('[data-go]').addEventListener('click', function () {
      var reason = (wrap.querySelector('input[name=r]:checked') || {}).value || 'other';
      closeModal();
      var t = toast('Submitting report…', 'loading');
      post(form.action, { reason: reason }).then(function (res) {
        t.update(res.ok ? 'Thanks — our team will review it' : (res.error || 'Could not submit'), res.ok ? 'success' : 'error');
        closeMenus();
      });
    });
    openModal(wrap);
  }

  /* ============ Copy link ============ */
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-copy]');
    if (!c) return;
    e.preventDefault();
    var text = c.getAttribute('data-copy');
    var done = function () { toast('Link copied', 'success', { duration: 1600 }); closeMenus(); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, done);
    else { var i = document.createElement('input'); i.value = text; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); done(); }
  });

  /* ============ Edit post ============ */
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-edit-post]')) {
      var post = t.closest('.post');
      post.querySelector('[data-post-body]').hidden = true;
      var f = post.querySelector('.post__edit'); f.hidden = false;
      var ta = f.querySelector('textarea'); ta.dataset.grown = ''; bindGrow(ta); ta.focus();
      closeMenus();
    }
    if (t.closest('[data-cancel-edit]')) {
      var p2 = t.closest('.post');
      p2.querySelector('.post__edit').hidden = true;
      var body = p2.querySelector('[data-post-body]');
      if (body.textContent.trim()) body.hidden = false;
    }
  });

  /* ============ Delegated form submit ============ */
  document.addEventListener('submit', function (e) {
    var form = e.target, kind = form.getAttribute('data-ajax');
    if (!kind) return;
    e.preventDefault();

    var confirmMsg = form.getAttribute('data-confirm');
    if (confirmMsg && !confirm(confirmMsg)) return;

    if (kind === 'like') return handleLike(form);
    if (kind === 'follow') return handleFollow(form);
    if (kind === 'quiet') return handleQuiet(form);
    if (kind === 'report') { reportDialog(form); return; }
    if (kind === 'delete') return handleDelete(form);
    if (kind === 'edit-post') return handleEditPost(form);
    if (kind === 'request') return handleRequest(form);
    if (kind === 'compose') return handleCompose(form);
    if (kind === 'message') return handleMessage(form);
    if (kind === 'settings') return handleSettings(form);
    if (kind === 'remove-avatar') return handleRemoveAvatar(form);
    if (kind === 'onboard') return handleOnboard(form);
    if (kind === 'admin-action') return handleAdminAction(form);
  });

  function handleAdminAction(form) {
    var t = toast('Saving…', 'loading');
    post(form.action, new FormData(form)).then(function (res) {
      if (!res.ok) { t.update(res.error || 'Could not save', 'error'); return; }
      t.update(res.message || 'Done', 'success');
      setTimeout(function () { location.reload(); }, 400);
    });
  }

  function handleOnboard(form) {
    var btn = form.querySelector('[data-finish]');
    if (btn) btn.disabled = true;
    var t = toast('Creating your account…', 'loading');
    post(form.action, new FormData(form), function (p) { if (p < 100) t.update('Uploading photo… ' + p + '%', 'loading'); }).then(function (res) {
      if (btn) btn.disabled = false;
      if (!res.ok) {
        t.update(res.error || 'Something went wrong', 'error');
        if (/handle/i.test(res.error || '')) { window.__onboardGo && window.__onboardGo(0); }
        return;
      }
      t.update('Welcome to EMChat', 'success');
      setTimeout(function () { location.href = res.redirect || '/feed'; }, 350);
    });
  }

  function handleLike(form) {
    var btn = $('button', form);
    var liked = !form.classList.contains('is-liked');
    form.classList.toggle('is-liked', liked);
    post(form.action).then(function (res) {
      if (!res.ok) { form.classList.toggle('is-liked'); return; }
      form.classList.toggle('is-liked', res.liked);
      btn.setAttribute('aria-pressed', res.liked ? 'true' : 'false');
      $('[data-like-count]', btn).textContent = res.count || '';
    });
  }

  function handleFollow(form) {
    post(form.action).then(function (res) {
      if (!res.ok) { toast(res.error || 'Something went wrong', 'error'); return; }
      var s = res.status;
      var handle = (form.action.match(/\/x\/(?:un)?follow\/([a-zA-Z0-9_]+)/) || [])[1] || '';
      $$('[data-follow-widget][data-username="' + handle + '"], .pcard[data-username="' + handle + '"] [data-follow-widget], .urow[data-username="' + handle + '"] [data-follow-widget]').forEach(function () {});
      var b = $('button', form);
      if (b) {
        b.textContent = s === 'accepted' ? 'Following' : s === 'pending' ? 'Requested' : 'Follow';
        b.className = 'btn ' + (s === 'none' ? 'btn--primary' : 'btn--ghost') + (b.classList.contains('btn--sm') ? ' btn--sm' : '');
      }
      form.action = form.action.replace(/\/x\/(follow|unfollow)\//, s === 'none' ? '/x/follow/' : '/x/unfollow/');
      form.setAttribute('data-ajax', 'follow');
      if (s === 'accepted') toast('Following', 'success', { duration: 1600 });
      else if (s === 'pending') toast('Follow request sent', 'success');
      else toast('Unfollowed', 'info', { duration: 1600 });
    });
  }

  function handleQuiet(form) {
    post(form.action).then(function (res) {
      if (!res.ok) { toast(res.error || 'Something went wrong', 'error'); return; }
      var m = form.action.match(/\/x\/(mute|unmute|block|unblock)\/([a-zA-Z0-9_]+)/);
      var verb = { mute: 'Muted', unmute: 'Unmuted', block: 'Blocked', unblock: 'Unblocked' }[m ? m[1] : ''] || 'Done';
      toast(verb + (m ? ' @' + m[2] : ''), 'success');
      closeMenus();
      if (m && (m[1] === 'block')) {
        var p = form.closest('.post'); if (p) p.remove();
      }
    });
  }

  function handleDelete(form) {
    if (!confirm('Delete this post? This cannot be undone.')) return;
    var t = toast('Deleting…', 'loading');
    post(form.action).then(function (res) {
      if (res.ok) {
        t.update('Post deleted', 'success');
        var p = form.closest('.post'); if (p) { p.style.transition = 'opacity .2s'; p.style.opacity = 0; setTimeout(function () { p.remove(); }, 200); }
      } else t.update(res.error || 'Could not delete', 'error');
    });
  }

  function handleEditPost(form) {
    var t = toast('Saving…', 'loading');
    post(form.action, new FormData(form)).then(function (res) {
      if (!res.ok || !res.html) { t.update(res.error || 'Could not update', 'error'); return; }
      t.update('Post updated', 'success');
      var old = form.closest('.post');
      var tmp = document.createElement('div'); tmp.innerHTML = res.html.trim();
      old.replaceWith(tmp.firstElementChild);
      rebind();
    });
  }

  function handleRequest(form) {
    post(form.action).then(function (res) {
      if (res.ok) { var row = form.closest('.notif--request'); if (row) row.remove(); toast('Done', 'success', { duration: 1400 }); }
    });
  }

  function handleCompose(form) {
    var btn = $('button[type=submit]', form);
    var hasFiles = form.querySelector('input[type=file]') && form.querySelector('input[type=file]').files.length;
    if (btn) btn.disabled = true;
    var t = hasFiles ? toast('Uploading…', 'loading') : null;
    post(form.action, new FormData(form), t ? function (pct) { t.update('Uploading… ' + pct + '%', 'loading'); } : null).then(function (res) {
      if (btn) btn.disabled = false;
      if (!res.ok) { if (t) t.update(res.error || 'Could not post', 'error'); else toast(res.error || 'Could not post', 'error'); return; }
      var isReply = !!form.querySelector('[name=reply_to]');
      if (t) t.update(isReply ? 'Reply posted' : 'Posted', 'success');
      else toast(isReply ? 'Reply posted' : 'Posted', 'success', { duration: 1600 });
      var stream = $('[data-stream]');
      if (stream && res.html) {
        var tmp = document.createElement('div'); tmp.innerHTML = res.html.trim();
        var node = tmp.firstElementChild;
        if (isReply) stream.appendChild(node); else stream.insertBefore(node, stream.firstChild);
        rebind();
      }
      if (!isReply) { var emptyEl = $('.empty'); if (emptyEl) emptyEl.remove(); }
      form.reset();
      var pv = $('[data-previews]', form); if (pv) { pv.innerHTML = ''; pv.hidden = true; }
      $$('textarea', form).forEach(grow);
      bindCount(form);
      closeComposer();
    });
  }

  function handleSettings(form) {
    var t = toast('Saving…', 'loading');
    post(form.action, new FormData(form), function (pct) { if (pct < 100) t.update('Saving… ' + pct + '%', 'loading'); }).then(function (res) {
      if (!res.ok) { t.update(res.error || 'Could not save', 'error'); return; }
      t.update(res.message || 'Saved', 'success');
      if (res.avatar) {
        $$('.settings-avatar__preview').forEach(function (p) {
          p.style.backgroundImage = 'url(' + res.avatar + ')'; p.classList.add('has-img'); p.textContent = '';
        });
        $$('.nav .avatar, .tabbar .avatar').forEach(function (a) { if (a.tagName === 'IMG') a.src = res.avatar; });
      }
    });
  }

  function handleRemoveAvatar(form) {
    var t = toast('Removing…', 'loading');
    post(form.action, new FormData(form)).then(function (res) {
      if (!res.ok) { t.update(res.error || 'Could not remove photo', 'error'); return; }
      t.update(res.message || 'Photo removed', 'success');
      // a full reload cleanly swaps every avatar (nav, tabbar, this preview)
      // back to the initials fallback without hand-patching each one.
      setTimeout(function () { location.reload(); }, 500);
    });
  }

  // live preview for settings image pickers
  $$('.settings-avatar input[type=file], .form--settings input[type=file]').forEach(function (input) {
    input.addEventListener('change', function () {
      var f = input.files[0]; if (!f) return;
      var u = URL.createObjectURL(f);
      if (input.name === 'avatar') $$('.settings-avatar__preview').forEach(function (p) {
        p.style.backgroundImage = 'url(' + u + ')'; p.classList.add('has-img'); p.textContent = '';
      });
    });
  });

  /* ============ Messages ============ */
  var mForm = $('.messenger__compose');

  function syncComposer() {
    if (!mForm) return;
    var ta = $('textarea', mForm);
    var has = !!(ta && ta.value.trim()) ||
      !!(mForm.querySelector('input[type=file]') && mForm.querySelector('input[type=file]').files.length);
    mForm.classList.toggle('has-text', has);
  }
  if (mForm) {
    var mta = $('textarea', mForm);
    if (mta) {
      mta.addEventListener('input', syncComposer);
      mta.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
          e.preventDefault();
          if (mForm.classList.contains('has-text')) mForm.requestSubmit();
        }
      });
    }
    syncComposer();
  }

  function setReply(mid, name, snippet) {
    if (!mForm) return;
    $('[data-reply-input]', mForm).value = mid;
    var bar = $('[data-reply-bar]', mForm);
    $('[data-reply-to]', mForm).textContent = 'Replying to ' + name;
    $('[data-reply-text]', mForm).textContent = snippet;
    bar.hidden = false;
    $('textarea', mForm).focus();
  }
  function clearReply() {
    if (!mForm) return;
    $('[data-reply-input]', mForm).value = '';
    var bar = $('[data-reply-bar]', mForm); if (bar) bar.hidden = true;
  }

  function handleMessage(form) {
    var body = $('textarea', form);
    var fileInput = form.querySelector('input[type=file]');
    var hasFiles = fileInput && fileInput.files.length;
    if (!body.value.trim() && !hasFiles) return;
    var scroll = $('[data-messages]');
    var panel = $('[data-conversation]');
    var send = $('button[type=submit]', form);
    if (send) send.disabled = true;
    var t = hasFiles ? toast('Sending…', 'loading') : null;
    post(form.action, new FormData(form), t ? function (p) { t.update('Sending… ' + p + '%', 'loading'); } : null).then(function (res) {
      if (send) send.disabled = false;
      if (!res.ok) { if (t) t.update(res.error || 'Not sent', 'error'); else toast(res.error || 'Message not sent', 'error'); return; }
      if (t) t.close();
      if (scroll && res.html) appendMessages(scroll, [{ id: res.id, html: res.html }]);
      if (panel) panel.setAttribute('data-last-id', res.id);
      var seen = scroll && scroll.querySelector('[data-seen]'); if (seen) seen.remove();
      form.reset(); clearReply(); grow(body); syncComposer();
      var pv = form.querySelector('[data-attach-previews]'); if (pv) { pv.innerHTML = ''; pv.hidden = true; }
      if (scroll) scroll.scrollTop = scroll.scrollHeight;
    });
  }

  function replaceMessage(mid, html) {
    var old = $('[data-mid="' + mid + '"]');
    if (!old) return;
    var tmp = document.createElement('div'); tmp.innerHTML = html.trim();
    old.replaceWith(tmp.firstElementChild);
  }

  // position the per-message menu as a fixed popover so it never clips
  document.addEventListener('toggle', function (e) {
    var det = e.target;
    if (!det.classList || !det.classList.contains('msg__menu') || !det.open) return;
    var menu = det.querySelector('.menu');
    var r = det.querySelector('summary').getBoundingClientRect();
    var mw = menu.offsetWidth || 190, mh = menu.offsetHeight || 180;
    var left = det.closest('.msg--out') ? r.right - mw : r.left;
    left = Math.max(8, Math.min(left, window.innerWidth - mw - 8));
    var top = r.bottom + 4;
    if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 4);
    menu.style.transform = 'translate(' + left + 'px,' + top + 'px)';
    menu.style.visibility = 'visible';
    // iOS Safari can scroll the page horizontally when a <details> opens; snap back.
    requestAnimationFrame(function () {
      window.scrollTo(0, window.scrollY);
      var scroller = document.querySelector('.messenger__scroll');
      if (scroller) scroller.scrollLeft = 0;
      document.documentElement.scrollLeft = 0;
      document.body.scrollLeft = 0;
    });
  }, true);

  // message menu actions
  document.addEventListener('click', function (e) {
    var t = e.target;

    var r = t.closest('[data-msg-reply]');
    if (r) { setReply(r.closest('.msg').getAttribute('data-mid'), r.getAttribute('data-name'), r.getAttribute('data-snippet')); closeMenus(); return; }

    if (t.closest('[data-msg-edit]')) {
      var msg = t.closest('.msg');
      var b = $('[data-msg-body]', msg); if (b) b.hidden = true;
      var f = $('[data-msg-edit-form]', msg); f.hidden = false;
      var ta = $('textarea', f); ta.dataset.grown = ''; bindGrow(ta); ta.focus();
      var end = ta.value.length; ta.setSelectionRange(end, end);
      closeMenus(); return;
    }
    if (t.closest('[data-msg-edit-cancel]')) {
      var m2 = t.closest('.msg');
      $('[data-msg-edit-form]', m2).hidden = true;
      var bb = $('[data-msg-body]', m2); if (bb) bb.hidden = false;
      return;
    }

    var d = t.closest('[data-msg-delete]');
    if (d) {
      closeMenus();
      var url = d.getAttribute('data-url');
      if (d.getAttribute('data-scope') === 'me') {
        if (confirm('Delete this message for you? The other person will still see it.')) {
          post(url, { scope: 'me' }).then(function (res) {
            if (res.ok) { var n = $('[data-mid="' + res.id + '"]'); if (n) n.remove(); toast('Deleted for you', 'success', { duration: 1600 }); }
          });
        }
        return;
      }
      deleteChoiceDialog(url);
      return;
    }
  });

  function deleteChoiceDialog(url) {
    var wrap = document.createElement('div');
    wrap.innerHTML = '<h3 class="modal-title">Delete message</h3>' +
      '<p class="modal-sub">Remove this message from the conversation?</p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--danger btn--block" data-all>Delete for everyone</button>' +
      '<button class="btn btn--ghost btn--block" data-me>Delete for me</button>' +
      '<button class="btn btn--ghost btn--block" data-x>Cancel</button></div>';
    wrap.querySelector('[data-x]').addEventListener('click', closeModal);
    wrap.querySelector('[data-all]').addEventListener('click', function () {
      closeModal();
      post(url, { scope: 'all' }).then(function (res) {
        if (!res.ok) { toast(res.error || 'Could not delete', 'error'); return; }
        if (res.html) replaceMessage(res.id, res.html);
        toast('Deleted for everyone', 'success', { duration: 1600 });
      });
    });
    wrap.querySelector('[data-me]').addEventListener('click', function () {
      closeModal();
      post(url, { scope: 'me' }).then(function (res) {
        if (res.ok) { var n = $('[data-mid="' + res.id + '"]'); if (n) n.remove(); toast('Deleted for you', 'success', { duration: 1600 }); }
      });
    });
    openModal(wrap);
  }

  /* group management (rename, add, role, remove, leave, photo) */
  function confirmDialog(message, onYes) {
    var wrap = document.createElement('div');
    wrap.innerHTML = '<h3 class="modal-title">Please confirm</h3>' +
      '<p class="modal-sub"></p>' +
      '<div class="modal-actions modal-actions--stack">' +
      '<button class="btn btn--danger btn--block" data-yes>Confirm</button>' +
      '<button class="btn btn--ghost btn--block" data-no>Cancel</button></div>';
    wrap.querySelector('.modal-sub').textContent = message;
    wrap.querySelector('[data-no]').addEventListener('click', closeModal);
    wrap.querySelector('[data-yes]').addEventListener('click', function () { closeModal(); onYes(); });
    openModal(wrap);
  }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('[data-group-action]')) return;
    e.preventDefault();
    var go = function () {
      var t = toast('Saving…', 'loading');
      post(f.action, new FormData(f)).then(function (res) {
        if (!res.ok) { t.update(res.error || 'Something went wrong', 'error'); return; }
        t.update(res.message || 'Done', 'success', { duration: 1400 });
        setTimeout(function () { location.href = res.redirect || location.href; }, 320);
      });
    };
    var msg = f.getAttribute('data-confirm');
    if (msg) confirmDialog(msg, go); else go();
  });

  // edit-message submit
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches('[data-msg-edit-form]')) return;
    e.preventDefault();
    var t = toast('Saving…', 'loading');
    post(f.action, new FormData(f)).then(function (res) {
      if (!res.ok || !res.html) { t.update(res.error || 'Could not edit', 'error'); return; }
      t.update('Message updated', 'success', { duration: 1500 });
      replaceMessage(res.id, res.html);
    });
  });

  function dayLabel(iso) {
    var d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' });
  }
  function appendMessages(scroll, items) {
    items.forEach(function (it) {
      if (scroll.querySelector('[data-mid="' + it.id + '"]')) return;
      var tmp = document.createElement('div'); tmp.innerHTML = it.html.trim();
      var node = tmp.firstElementChild;
      var day = node.getAttribute('data-day');
      var lastDay = (function () { var d = $$('.msg-day', scroll).pop(); return d ? d.getAttribute('data-day-label') : null; })();
      if (day && day !== lastDay) {
        var sep = document.createElement('div'); sep.className = 'msg-day'; sep.setAttribute('data-day-label', day);
        sep.textContent = dayLabel(day); scroll.appendChild(sep);
      }
      scroll.appendChild(node);
    });
  }

  // attachment previews in DM composer
  $$('[data-attach-input]').forEach(function (input) {
    input.addEventListener('change', function () {
      var box = input.closest('form').querySelector('[data-attach-previews]');
      if (!box) return;
      box.innerHTML = ''; box.hidden = !input.files.length;
      Array.prototype.forEach.call(input.files, function (f) {
        var chip = document.createElement('span');
        chip.className = 'attach-chip';
        if (f.type.indexOf('image/') === 0) {
          var img = document.createElement('img'); img.src = URL.createObjectURL(f);
          img.onload = function () { URL.revokeObjectURL(img.src); };
          chip.appendChild(img);
        } else {
          var tag = f.type.indexOf('video') === 0 ? 'Video' : f.type.indexOf('audio') === 0 ? 'Audio'
            : f.type.indexOf('pdf') > -1 ? 'PDF' : 'File';
          chip.textContent = tag + ' · ' + f.name;
        }
        box.appendChild(chip);
      });
      if (typeof syncComposer === 'function') syncComposer();
    });
  });

  /* conversation live poll */
  var convPanel = $('[data-conversation]');
  if (convPanel) {
    var cScroll = $('[data-messages]', convPanel);
    if (cScroll) cScroll.scrollTop = cScroll.scrollHeight;
    var pollUrl = convPanel.getAttribute('data-poll-url');
    var lastPoll = Math.floor(Date.now() / 1000);
    setInterval(function () {
      if (document.hidden) return;
      var lastId = convPanel.getAttribute('data-last-id') || 0;
      getJSON(pollUrl + '?after=' + lastId + '&rev=' + lastPoll).then(function (res) {
        if (!res.ok) return;
        if (res.now) lastPoll = res.now;
        (res.revised || []).forEach(function (it) {
          if (!$('[data-mid="' + it.id + '"]')) return;
          if (($('[data-msg-edit-form]', $('[data-mid="' + it.id + '"]')) || {}).hidden === false) return;
          replaceMessage(it.id, it.html);
        });
        if (!res.items || !res.items.length) return;
        var atBottom = cScroll.scrollHeight - cScroll.scrollTop - cScroll.clientHeight < 80;
        appendMessages(cScroll, res.items);
        var maxId = res.items.reduce(function (a, b) { return Math.max(a, b.id); }, +lastId);
        convPanel.setAttribute('data-last-id', maxId);
        if (atBottom) cScroll.scrollTop = cScroll.scrollHeight;
      });
    }, 3500);
  }

  /* recipient search (new message) */
  var recipForm = $('[data-recipient-search]');
  if (recipForm) {
    var recipInput = $('input', recipForm), recipOut = $('[data-recipient-results]');
    var rt;
    recipInput.addEventListener('input', function () {
      clearTimeout(rt);
      rt = setTimeout(function () {
        getJSON('/messages/new?q=' + encodeURIComponent(recipInput.value.trim())).then(function (res) {
          if (res.ok && res.html) recipOut.innerHTML = res.html;
        });
      }, 220);
    });
    recipForm.addEventListener('submit', function (e) { e.preventDefault(); });
  }

  /* new group — live selected count + submit gate */
  var groupForm = $('[data-group-form]');
  if (groupForm) {
    var gCount = $('[data-group-count]', groupForm), gBtn = $('[data-group-submit]', groupForm);
    var syncGroup = function () {
      var n = $$('[data-group-check]:checked', groupForm).length;
      if (gCount) gCount.textContent = n + ' selected';
      if (gBtn) gBtn.disabled = n < 1;
    };
    groupForm.addEventListener('change', function (e) {
      if (e.target && e.target.matches('[data-group-check]')) syncGroup();
    });
    syncGroup();
  }

  /* ============ Landing hero — tracker check ============ */
  (function () {
    var scan = $('[data-scan]');
    if (!scan) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      $$('[data-scan-item]', scan).forEach(function (li) { li.classList.add('on'); });
      var b0 = $('[data-scan-bar]', scan); if (b0) b0.style.width = '100%';
      scan.classList.add('is-done');
      $('[data-scan-label]', scan).textContent = 'Nothing tracking you here';
      return;
    }

    var bar = $('[data-scan-bar]', scan);
    var label = $('[data-scan-label]', scan);
    var items = $$('[data-scan-item]', scan);
    var timers = [];
    function clearTimers() { timers.forEach(clearTimeout); timers = []; }
    function at(ms, fn) { timers.push(setTimeout(fn, ms)); }

    function runScan() {
      clearTimers();
      scan.classList.remove('is-done');
      scan.classList.add('is-scanning');
      label.textContent = 'Checking this page for trackers';
      items.forEach(function (li) { li.classList.remove('on'); });
      bar.style.transition = 'none'; bar.style.width = '0%';
      // force reflow then animate
      void bar.offsetWidth;
      bar.style.transition = 'width 1.9s linear';
      at(60, function () { bar.style.width = '100%'; });
      items.forEach(function (li, i) { at(360 + i * 420, function () { li.classList.add('on'); }); });
      at(2050, function () {
        scan.classList.remove('is-scanning');
        scan.classList.add('is-done');
        label.textContent = 'Nothing tracking you here';
      });
      at(8500, function () { if (!document.hidden) runScan(); });
    }
    setTimeout(runScan, 700);
  })();

  /* ============ Calm scroll-reveal (landing sections) ============ */
  (function () {
    var els = $$('[data-reveal-scroll]');
    if (!els.length) return;
    if (!('IntersectionObserver' in window) || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
      els.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('is-in'); io.unobserve(entry.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function (el) { io.observe(el); });
  })();

  /* ============ Feed load-more ============ */
  var loadBtn = $('[data-load-more]');
  if (loadBtn) loadBtn.addEventListener('click', function () {
    loadBtn.disabled = true; loadBtn.textContent = 'Loading…';
    getJSON(loadBtn.getAttribute('data-url') + '?before=' + loadBtn.getAttribute('data-next')).then(function (res) {
      if (res.html) { $('[data-stream]').insertAdjacentHTML('beforeend', res.html); rebind(); }
      if (res.next) { loadBtn.setAttribute('data-next', res.next); loadBtn.disabled = false; loadBtn.textContent = 'Load older posts'; }
      else loadBtn.remove();
    });
  });

  /* ============ Live nav badges (pulse) ============ */
  var hasBadges = $$('[data-badge]').length;
  if (hasBadges) {
    var prevMsg = -1;
    function paintBadge(key, n) {
      $$('[data-badge="' + key + '"]').forEach(function (link) {
        var b = link.querySelector('.nav__badge') || link.querySelector('.tabbar__badge');
        if (n > 0) {
          if (!b) {
            b = document.createElement('span');
            b.className = link.classList.contains('nav__link') ? 'nav__badge' : 'tabbar__badge';
            link.appendChild(b);
          }
          b.textContent = n > 99 ? '99+' : n;
        } else if (b) b.remove();
      });
    }
    function pulse() {
      if (document.hidden) return;
      getJSON('/x/pulse').then(function (res) {
        if (!res.ok) return;
        paintBadge('notifications', res.notifications);
        paintBadge('messages', res.messages);
        if (prevMsg >= 0 && res.messages > prevMsg && !/^\/messages/.test(location.pathname)) {
          toast('New message', 'info', { duration: 2500 });
        }
        prevMsg = res.messages;
      });
    }
    setInterval(pulse, 12000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) pulse(); });
    pulse();
  }

  /* ============ Autosave (settings toggles) ============ */
  $$('[data-autosave]').forEach(function (form) {
    $$('[data-autosave-hide]', form).forEach(function (b) { b.hidden = true; });
    form.addEventListener('change', function () {
      post(form.action, new FormData(form)).then(function (res) {
        toast(res.ok ? (res.message || 'Saved') : (res.error || 'Could not save'), res.ok ? 'success' : 'error', { duration: 1600 });
      });
    });
  });

  /* ============ Onboarding wizard ============ */
  var onboard = $('[data-onboard]');
  if (onboard) {
    var steps = $$('.onboard__step', onboard);
    var dots = $$('.onboard__progress span', onboard);
    var uInput = $('[data-username-input]', onboard);
    var uStatus = $('[data-username-status]', onboard);
    var uHint = $('[data-username-hint]', onboard);
    var uGate = $('[data-requires-username]', onboard);
    var cur = 0, usernameOk = false;

    function go(n) {
      cur = Math.max(0, Math.min(steps.length - 1, n));
      steps.forEach(function (s, i) { s.hidden = i !== cur; s.classList.toggle('is-active', i === cur); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i <= cur); });
      var f = steps[cur].querySelector('input:not([type=hidden]):not([type=file]), textarea');
      if (f) setTimeout(function () { f.focus(); }, 60);
    }
    window.__onboardGo = go;

    onboard.addEventListener('click', function (e) {
      if (e.target.closest('[data-next]')) {
        var b = e.target.closest('[data-next]');
        if (b.hasAttribute('data-requires-username') && !usernameOk) { checkUsername(true); return; }
        go(cur + 1);
      }
      if (e.target.closest('[data-back]')) go(cur - 1);
    });

    // live username check
    var ut, lastVal = '';
    function setStatus(state, msg) {
      uStatus.className = 'onboard__status is-' + state;
      uStatus.textContent = state === 'ok' ? '✓' : state === 'bad' ? '✕' : state === 'wait' ? '' : '';
      uStatus.classList.toggle('is-spin', state === 'wait');
      if (uHint) { uHint.textContent = msg || ''; uHint.className = 'field__hint field__hint--' + state; }
      usernameOk = state === 'ok';
      if (uGate) uGate.disabled = !usernameOk;
    }
    function checkUsername(force) {
      var v = uInput.value.trim();
      if (v === lastVal && !force) return;
      lastVal = v;
      if (!v) { setStatus('', ''); return; }
      if (!/^[A-Za-z0-9_]{3,30}$/.test(v)) {
        setStatus('bad', v.length < 3 ? 'At least 3 characters' : 'Letters, numbers and underscores only');
        return;
      }
      setStatus('wait', 'Checking…');
      getJSON('/x/username-available?u=' + encodeURIComponent(v)).then(function (res) {
        if (uInput.value.trim() !== v) return;
        if (!res.ok) { setStatus('bad', res.error || 'Try again'); return; }
        setStatus(res.available ? 'ok' : 'bad', res.reason || (res.available ? 'Available' : 'Taken'));
      });
    }
    if (uInput) {
      uInput.addEventListener('input', function () { clearTimeout(ut); setStatus('wait', 'Checking…'); ut = setTimeout(checkUsername, 320); });
      checkUsername(true);
    }

    // avatar preview
    var avInput = $('[data-avatar-input]', onboard);
    var avPrev = $('[data-avatar-preview]', onboard);
    if (avInput) avInput.addEventListener('change', function () {
      var f = avInput.files[0];
      if (!f) return;
      var url = URL.createObjectURL(f);
      avPrev.style.backgroundImage = 'url(' + url + ')';
      avPrev.classList.add('has-img');
      avPrev.textContent = '';
    });

    go(0);
  }

  /* ============ Menus ============ */
  var MENU_SEL = 'details[open].post__menu, details[open].profile-more, details[open].msg__menu, details[open].messenger__menu, details[open].threadrow__menu, details[open].mrow__menu';
  function closeMenus() { $$(MENU_SEL).forEach(function (d) { d.removeAttribute('open'); }); }
  document.addEventListener('click', function (e) {
    $$(MENU_SEL).forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  function rebind() {
    $$('textarea[data-autogrow]').forEach(bindGrow);
  }
})();

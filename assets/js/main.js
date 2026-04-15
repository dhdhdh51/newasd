/**
 * School ERP — Main JavaScript
 * Mobile-first UI interactions, no jQuery required
 */

(function () {
  'use strict';

  /* ============================================================
     Sidebar Toggle (mobile hamburger)
     ============================================================ */
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const toggleBtns = document.querySelectorAll('[data-sidebar-toggle]');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('show');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  toggleBtns.forEach(btn => btn.addEventListener('click', () => {
    sidebar && sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
  }));

  if (overlay) overlay.addEventListener('click', closeSidebar);

  // Close on ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });

  /* ============================================================
     Flash Message Auto-Dismiss (4 s)
     ============================================================ */
  document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(el => {
    const delay = parseInt(el.dataset.autoDismiss) || 4000;
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, delay);
  });

  // Also auto-dismiss any alert that has class alert-success / alert-danger
  document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  /* ============================================================
     CSRF — inject token into every AJAX POST automatically
     ============================================================ */
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : '';

  window.csrfFetch = function (url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-CSRF-Token'] = CSRF_TOKEN;
    if (options.method && options.method.toUpperCase() === 'POST') {
      if (typeof options.body === 'string') {
        options.body += '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
      }
    }
    return fetch(url, options);
  };

  /* ============================================================
     Notification Bell — mark individual as read
     ============================================================ */
  document.addEventListener('click', function (e) {
    const link = e.target.closest('.notif-item-link');
    if (!link) return;
    const id = link.dataset.notifId;
    if (!id) return;
    fetch('/notifications/read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
    }).then(() => {
      const dot = link.querySelector('.notif-unread-dot');
      if (dot) dot.remove();
      updateBadgeCount(-1);
    }).catch(() => {});
  });

  function updateBadgeCount(delta) {
    const badge = document.querySelector('.notif-badge');
    if (!badge) return;
    let count = parseInt(badge.textContent) || 0;
    count = Math.max(0, count + delta);
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
  }

  /* ============================================================
     Dynamic Section Loading (class → section select)
     ============================================================ */
  document.querySelectorAll('[data-load-sections]').forEach(classSelect => {
    const targetId = classSelect.dataset.loadSections;
    const sectionSelect = document.getElementById(targetId);
    if (!sectionSelect) return;

    classSelect.addEventListener('change', function () {
      const classId = this.value;
      sectionSelect.innerHTML = '<option value="">Loading…</option>';
      sectionSelect.disabled = true;

      if (!classId) {
        sectionSelect.innerHTML = '<option value="">— Select Section —</option>';
        sectionSelect.disabled = false;
        return;
      }

      fetch('/admin/ajax/get-sections.php?class_id=' + encodeURIComponent(classId))
        .then(r => r.json())
        .then(sections => {
          sectionSelect.innerHTML = '<option value="">— Select Section —</option>';
          sections.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            sectionSelect.appendChild(opt);
          });
          sectionSelect.disabled = false;
        })
        .catch(() => {
          sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
          sectionSelect.disabled = false;
        });
    });
  });

  /* ============================================================
     Password Toggle
     ============================================================ */
  document.querySelectorAll('[data-password-toggle]').forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.dataset.passwordToggle;
      const input = document.getElementById(targetId);
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      const icon = this.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye',    isText);
        icon.classList.toggle('bi-eye-slash', !isText);
      }
    });
  });

  /* ============================================================
     Print Button shorthand
     ============================================================ */
  document.querySelectorAll('[data-print]').forEach(btn => {
    btn.addEventListener('click', () => window.print());
  });

  /* ============================================================
     Confirm-before-delete / dangerous actions
     ============================================================ */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      const msg = this.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  /* ============================================================
     Real-time grade badge in marks table
     ============================================================ */
  function calcGrade(pct) {
    if (pct >= 90) return ['A+', 'success'];
    if (pct >= 80) return ['A',  'success'];
    if (pct >= 70) return ['B+', 'primary'];
    if (pct >= 60) return ['B',  'primary'];
    if (pct >= 50) return ['C+', 'info'];
    if (pct >= 40) return ['C',  'info'];
    if (pct >= 33) return ['D',  'warning'];
    return ['F', 'danger'];
  }

  document.querySelectorAll('.marks-input').forEach(input => {
    const row   = input.closest('tr');
    if (!row) return;
    const maxEl  = row.querySelector('.max-marks');
    const badgeEl = row.querySelector('.grade-badge');
    const pctEl   = row.querySelector('.pct-cell');

    function refresh() {
      const obtained = parseFloat(input.value) || 0;
      const max      = parseFloat(maxEl ? maxEl.textContent : 100) || 100;
      const pct      = Math.min(100, (obtained / max) * 100);
      const [grade, color] = calcGrade(pct);

      if (badgeEl) {
        badgeEl.textContent = grade;
        badgeEl.className = 'badge grade-badge bg-' + color;
      }
      if (pctEl) pctEl.textContent = pct.toFixed(1) + '%';
    }

    input.addEventListener('input', refresh);
    refresh();
  });

  /* ============================================================
     Photo Preview (file input → img)
     ============================================================ */
  document.querySelectorAll('[data-photo-preview]').forEach(input => {
    const targetId = input.dataset.photoPreview;
    const img = document.getElementById(targetId);
    if (!img) return;

    input.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        this.value = '';
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        alert('Image must be smaller than 2 MB.');
        this.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; };
      reader.readAsDataURL(file);
    });
  });

  /* ============================================================
     Bulk-select checkboxes (select all / individual)
     ============================================================ */
  document.querySelectorAll('[data-select-all]').forEach(master => {
    const targetClass = master.dataset.selectAll;
    const targets = () => document.querySelectorAll('.' + targetClass);

    master.addEventListener('change', function () {
      targets().forEach(cb => { cb.checked = this.checked; });
      updateBulkActions();
    });

    document.addEventListener('change', function (e) {
      if (!e.target.classList.contains(targetClass)) return;
      const all = [...targets()];
      master.checked = all.every(cb => cb.checked);
      master.indeterminate = !master.checked && all.some(cb => cb.checked);
      updateBulkActions();
    });
  });

  function updateBulkActions() {
    const anyChecked = document.querySelectorAll('.bulk-cb:checked').length > 0;
    document.querySelectorAll('.bulk-action-btn').forEach(btn => {
      btn.disabled = !anyChecked;
    });
  }

  /* ============================================================
     DataTable-style live search for small tables
     ============================================================ */
  document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });

  /* ============================================================
     Tooltip initialisation (Bootstrap 5)
     ============================================================ */
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
  }

  /* ============================================================
     PayU — fetch hash then auto-submit
     ============================================================ */
  document.querySelectorAll('.payu-pay-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const feeId  = this.dataset.feeId;
      const formId = 'payuForm_' + feeId;
      const form   = document.getElementById(formId);
      if (!form) return;

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Redirecting…';

      fetch('/payment/payu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'fee_id=' + encodeURIComponent(feeId) + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            btn.disabled = false;
            btn.textContent = 'Pay Now';
            return;
          }
          // populate hidden fields
          Object.keys(data).forEach(k => {
            let hidden = form.querySelector('[name="' + k + '"]');
            if (!hidden) {
              hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = k;
              form.appendChild(hidden);
            }
            hidden.value = data[k];
          });
          form.submit();
        })
        .catch(() => {
          alert('Network error. Please try again.');
          btn.disabled = false;
          btn.textContent = 'Pay Now';
        });
    });
  });

  /* ============================================================
     Admission status tracker — auto-refresh every 60 s
     ============================================================ */
  if (document.getElementById('admissionStatusTracker')) {
    setInterval(() => location.reload(), 60000);
  }

  /* ============================================================
     Active sidebar link highlight (based on current URL)
     ============================================================ */
  (function highlightActiveLink() {
    const current = window.location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('.sidebar-link').forEach(link => {
      const href = (link.getAttribute('href') || '').replace(/\/+$/, '');
      if (!href) return;
      if (current === href || (href.length > 1 && current.startsWith(href))) {
        link.classList.add('active');
      }
    });
  })();

  /* ============================================================
     Settings — tab state persistence via hash
     ============================================================ */
  (function settingsTabs() {
    const tabLinks = document.querySelectorAll('#settingsTabs .nav-link');
    if (!tabLinks.length) return;

    function activateFromHash() {
      const hash = window.location.hash;
      if (!hash) return;
      const tab = document.querySelector('#settingsTabs .nav-link[href="' + hash + '"]');
      if (tab) new bootstrap.Tab(tab).show();
    }

    tabLinks.forEach(tab => {
      tab.addEventListener('shown.bs.tab', e => {
        history.replaceState(null, '', e.target.getAttribute('href'));
      });
    });

    activateFromHash();
  })();

  /* ============================================================
     Attendance — visual button toggle (Present/Absent/Late)
     ============================================================ */
  document.querySelectorAll('.attendance-btn-group').forEach(group => {
    group.querySelectorAll('[data-status]').forEach(btn => {
      btn.addEventListener('click', function () {
        const input = group.querySelector('input[type="hidden"]');
        if (input) input.value = this.dataset.status;
        group.querySelectorAll('[data-status]').forEach(b => {
          b.classList.remove('active', 'btn-success', 'btn-danger', 'btn-warning', 'btn-secondary');
          b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        const colorMap = { present: 'btn-success', absent: 'btn-danger', late: 'btn-warning', holiday: 'btn-secondary' };
        this.classList.add('active', colorMap[this.dataset.status] || 'btn-primary');
      });
    });
  });

  /* ============================================================
     Form — prevent double submit
     ============================================================ */
  document.querySelectorAll('form[data-single-submit]').forEach(form => {
    form.addEventListener('submit', function () {
      const btn = this.querySelector('[type="submit"]');
      if (!btn) return;
      setTimeout(() => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (btn.dataset.loadingText || 'Saving…');
      }, 10);
    });
  });

  /* ============================================================
     Responsive chart resize helper
     ============================================================ */
  if (typeof Chart !== 'undefined') {
    window.addEventListener('resize', () => {
      Chart.instances && Object.values(Chart.instances).forEach(c => c.resize());
    });
  }

})();

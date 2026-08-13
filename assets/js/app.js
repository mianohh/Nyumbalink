/* ============================================================
   Nyumbalink Rental Management System — Dashboard JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  // ---- Sidebar Toggle ----
  const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.querySelector('.topbar-toggle');
  const overlay = document.querySelector('.sidebar-overlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('active');
      document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
    });
  }

  // Close sidebar on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  });

  // ---- Auto-dismiss flash messages ----
  document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
    setTimeout(function () {
      var btn = alert.querySelector('.btn-close');
      if (btn) btn.click();
    }, 5000);
  });

  // ---- Delete confirmations with SweetAlert2 ----
  document.querySelectorAll('a[href*="delete"]').forEach(function (link) {
    if (!link.getAttribute('onclick')) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Delete Record?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
          }).then(function (result) {
            if (result.isConfirmed) window.location.href = href;
          });
        } else {
          if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            window.location.href = href;
          }
        }
      });
    }
  });

  // ---- Print receipt ----
  window.printReceipt = function (url) {
    var w = window.open(url, '_blank');
    if (w) { w.focus(); w.print(); }
  };

  // ---- Chart.js defaults ----
  if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 10;
    Chart.defaults.plugins.legend.labels.padding = 16;
  }

  // ---- Toast notifications for flash messages ----
  if (typeof toastr !== 'undefined') {
    toastr.options = {
      positionClass: 'toast-top-right',
      timeOut: 4000,
      progressBar: true,
      closeButton: true
    };

    var flashEl = document.querySelector('.alert-success, .alert-danger, .alert-warning, .alert-info');
    if (flashEl) {
      var type = flashEl.classList.contains('alert-success') ? 'success'
        : flashEl.classList.contains('alert-danger') ? 'error'
        : flashEl.classList.contains('alert-warning') ? 'warning' : 'info';
      var msg = flashEl.textContent.trim();
      if (msg) toastr[type](msg);
      flashEl.style.display = 'none';
    }
  }

  // ---- File upload preview ----
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var preview = this.closest('.upload-area') || this.parentElement.querySelector('.upload-preview');
      if (preview && this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          preview.innerHTML = '<img src="' + e.target.result + '" style="max-height:120px;border-radius:6px;margin-top:8px;">';
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });

  // ---- Form validation visual feedback ----
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var invalids = form.querySelectorAll(':invalid');
      invalids.forEach(function (el) {
        el.classList.add('is-invalid');
      });
      var valids = form.querySelectorAll(':valid');
      valids.forEach(function (el) {
        if (el.classList.contains('is-invalid')) el.classList.remove('is-invalid');
      });
    });
  });

  // ---- Active nav highlighting ----
  var currentPath = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(function (link) {
    var href = link.getAttribute('href');
    if (href && currentPath.includes(href.replace(/.*modules\//, '/modules/'))) {
      link.classList.add('active');
    }
  });

});

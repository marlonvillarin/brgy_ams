</main>
</div><!-- /.layout -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script>
  // Auto-dismiss flash
  setTimeout(() => { document.querySelectorAll('.flash').forEach(el => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }); }, 5000);

  // Close modal on overlay click
  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function (e) { if (e.target === this) this.classList.remove('show'); });
  });

  // DataTables init (any table with class dtbl)
  $(document).ready(function () {
    if ($('.dtbl').length) {
      $('.dtbl:not([data-server-side])').DataTable({  // ← only change this line
        pageLength: 15,
        language: { search: "🔍 Search:" }
      });
    }
  });
</script>
<?php if (!empty($extra_js))
  echo $extra_js; ?>
</body>

</html>
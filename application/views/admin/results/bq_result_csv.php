<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'BQ Result CSV')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Results</li>
          <li class="breadcrumb-item active">BQ Result CSV</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <h3 class="card-title mb-0">BQ Result CSV</h3>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-primary" href="<?php echo base_url('admin/emarking/reports_bq_csv'); ?>">BQ Result CSV</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form method="get" action="<?php echo base_url('admin/emarking/reports_bq_csv'); ?>" class="mb-3" id="bq-filter-form">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <select name="source_table" class="form-control" required>
                <option value="">Source sheet*</option>
                <?php foreach (($source_tables ?? []) as $table): ?>
                  <option value="<?php echo html_escape((string) $table); ?>" <?php echo (($filters['source_table'] ?? '') === (string) $table) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) $table); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="grade" class="form-control">
                <option value="">Grade (All)</option>
                <option value="4" <?php echo (($filters['grade'] ?? '') === '4') ? 'selected' : ''; ?>>4</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="version" class="form-control">
                <option value="">Version (All)</option>
                <?php foreach (($version_options ?? []) as $version): ?>
                  <option value="<?php echo html_escape((string) $version); ?>" <?php echo (($filters['version'] ?? '') === (string) $version) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) $version); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="district_id" class="form-control">
                <option value="">District (All)</option>
                <?php foreach (($districts ?? []) as $district): ?>
                  <option value="<?php echo (int) $district->district_id; ?>" <?php echo (($filters['district_id'] ?? '') === (string) $district->district_id) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) $district->district_name_en); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="school_query" value="<?php echo htmlspecialchars((string) ($filters['school_query'] ?? '')); ?>" class="form-control" placeholder="School / EMIS Code">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" class="form-control" value="BQ" readonly>
            </div>
          </div>
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
            <div class="col-md-2 mb-2">
              <a href="<?php echo base_url('admin/emarking/reports_bq_csv'); ?>" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
            <div class="col-md-3 mb-2">
              <a href="<?php echo base_url('admin/emarking/export_bq_results_csv?' . http_build_query($filters ?? [])); ?>" class="btn btn-primary btn-block" id="bq-export-link">Export CSV</a>
            </div>
          </div>
        </form>

        <div class="alert alert-light border">
          <strong>CSV columns:</strong>
          <?php echo html_escape(implode(', ', (array) ($csv_headers ?? []))); ?>
        </div>

        <?php if (empty($show_preview)): ?>
          <div class="alert alert-warning mb-0">
            Select a source `sheet_*` table first to load preview rows.
          </div>
        <?php else: ?>
          <div class="mb-2">
            <strong>Preview:</strong> first <?php echo count((array) ($preview_rows ?? [])); ?> rows
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover mb-0">
              <thead>
                <tr>
                  <?php foreach ((array) ($csv_headers ?? []) as $header_text): ?>
                    <th><?php echo html_escape((string) $header_text); ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($preview_rows)): ?>
                  <tr>
                    <td colspan="<?php echo count((array) ($csv_headers ?? [])); ?>" class="text-center text-muted">No BQ result rows found for the selected filters.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($preview_rows as $row): ?>
                    <tr>
                      <?php foreach ((array) ($csv_headers ?? []) as $header_text): ?>
                        <td><?php echo html_escape((string) ($row[$header_text] ?? '')); ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
  (function () {
    var exportLink = document.getElementById('bq-export-link');
    var sourceTable = document.querySelector('select[name="source_table"]');
    var filterForm = document.getElementById('bq-filter-form');
    if (!exportLink || !sourceTable || !filterForm) {
      return;
    }

    exportLink.addEventListener('click', function (event) {
      if (!sourceTable.value) {
        event.preventDefault();
        alert('Please select a source sheet before exporting BQ CSV.');
        return;
      }

      var params = new URLSearchParams(new FormData(filterForm));
      exportLink.href = '<?php echo base_url('admin/emarking/export_bq_results_csv'); ?>?' + params.toString();
    });
  })();
</script>

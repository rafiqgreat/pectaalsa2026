<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$csvHeaders = is_array($csv_headers ?? null) ? $csv_headers : [];
$previewRows = is_array($preview_rows ?? null) ? $preview_rows : [];
$includeMcqStatus = !empty($include_mcq_status);
$tableName = trim((string) ($csv_table_name ?? 'fullbook_complete_result_science'));
$tableExists = !empty($table_exists);
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'Complete Science CSV')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Results</li>
          <li class="breadcrumb-item active">Complete Science CSV</li>
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
          <h3 class="card-title mb-0">Complete Science CSV</h3>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-primary" href="<?php echo base_url('admin/emarking/reports_complete_science_csv'); ?>">Complete Science CSV</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <div><strong>Source table:</strong> <code><?php echo htmlspecialchars($tableName); ?></code></div>
          <div><strong>Preview columns:</strong> <code>mcq_status</code> is excluded.</div>
          <div><strong>Export option:</strong> you can optionally include <code>mcq_status</code> in the downloaded CSV.</div>
        </div>

        <?php if (!$tableExists): ?>
          <div class="alert alert-danger mb-0">Source table not found: <code><?php echo htmlspecialchars($tableName); ?></code></div>
        <?php else: ?>
          <form method="get" action="<?php echo base_url('admin/emarking/reports_complete_science_csv'); ?>" class="mb-3" id="complete-science-export-form">
            <div class="form-row align-items-end">
              <div class="col-md-4 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="include_mcq_status" name="include_mcq_status" <?php echo $includeMcqStatus ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="include_mcq_status">
                    Include <code>mcq_status</code> in CSV download
                  </label>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <a href="<?php echo base_url('admin/emarking/export_complete_science_csv' . ($includeMcqStatus ? '?include_mcq_status=1' : '')); ?>" class="btn btn-primary btn-block" id="complete-science-export-link">Export CSV</a>
              </div>
            </div>
          </form>

          <div class="alert alert-light border">
            <strong>Preview columns:</strong>
            <?php echo html_escape(implode(', ', $csvHeaders)); ?>
          </div>

          <div class="mb-2">
            <strong>Preview:</strong> first <?php echo count($previewRows); ?> rows
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover mb-0">
              <thead>
                <tr>
                  <?php foreach ($csvHeaders as $headerText): ?>
                    <th><?php echo html_escape((string) $headerText); ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($previewRows)): ?>
                  <tr>
                    <td colspan="<?php echo count($csvHeaders); ?>" class="text-center text-muted">No rows found in the complete Science result table.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($previewRows as $row): ?>
                    <tr>
                      <?php foreach ($csvHeaders as $headerText): ?>
                        <td><?php echo html_escape((string) ($row[$headerText] ?? '')); ?></td>
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
    var exportLink = document.getElementById('complete-science-export-link');
    var includeStatus = document.getElementById('include_mcq_status');
    if (!exportLink || !includeStatus) {
      return;
    }

    function updateExportLink() {
      var url = '<?php echo base_url('admin/emarking/export_complete_science_csv'); ?>';
      if (includeStatus.checked) {
        url += '?include_mcq_status=1';
      }
      exportLink.href = url;
    }

    includeStatus.addEventListener('change', updateExportLink);
    updateExportLink();
  })();
</script>

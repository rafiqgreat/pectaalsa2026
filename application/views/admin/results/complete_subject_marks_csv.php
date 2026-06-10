<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$csvHeaders = is_array($csv_headers ?? null) ? $csv_headers : [];
$previewRows = is_array($preview_rows ?? null) ? $preview_rows : [];
$tableName = trim((string) ($csv_table_name ?? ''));
$tableExists = !empty($table_exists);
$reportUrl = trim((string) ($report_url ?? ''));
$exportUrl = trim((string) ($export_url ?? ''));
$pageTitle = trim((string) ($page->title ?? 'Subject Marks CSV'));
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars($pageTitle); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Results</li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
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
          <h3 class="card-title mb-0"><?php echo htmlspecialchars($pageTitle); ?></h3>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($reportUrl); ?>"><?php echo htmlspecialchars($pageTitle); ?></a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <div><strong>Source table:</strong> <code><?php echo htmlspecialchars($tableName); ?></code></div>
          <div><strong>Columns:</strong> preview and export include all marks columns, totals, <code>mcq_status</code>, and <code>created_at</code>.</div>
        </div>

        <?php if (!$tableExists): ?>
          <div class="alert alert-danger mb-0">Source table not found: <code><?php echo htmlspecialchars($tableName); ?></code></div>
        <?php else: ?>
          <div class="mb-3">
            <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="btn btn-primary">Export CSV</a>
          </div>

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
                    <td colspan="<?php echo count($csvHeaders); ?>" class="text-center text-muted">No rows found in the marks result table.</td>
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

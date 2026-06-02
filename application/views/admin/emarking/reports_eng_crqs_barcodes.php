<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$selectedVersion = trim((string) ($selected_version ?? ''));
$versions = isset($barcode_versions) && is_array($barcode_versions) ? $barcode_versions : [];
$rows = isset($barcode_rows) && is_array($barcode_rows) ? $barcode_rows : [];
$totalRows = (int) ($barcode_total ?? count($rows));
$currentPage = max(1, (int) ($barcode_page ?? 1));
$perPage = max(1, (int) ($barcode_per_page ?? 100));
$paginationLinks = (string) ($pagination_links ?? '');
$startSr = (($currentPage - 1) * $perPage) + 1;
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$shownCount = count($rows);
$showImageBarcode = !empty($show_image_barcode);
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'ENG CRQs Barcodes')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/reports_questions'); ?>">Reports</a></li>
          <li class="breadcrumb-item active">ENG CRQs Barcodes</li>
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
        <h3 class="card-title mb-0">Download English CRQ Booklet Barcodes</h3>
      </div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="form-row align-items-end">
            <div class="col-md-4 mb-2">
              <label for="version" class="mb-1">Version</label>
              <select name="version" id="version" class="form-control">
                <option value="">All Versions</option>
                <?php foreach ($versions as $version): ?>
                  <?php $version = trim((string) $version); ?>
                  <option value="<?php echo html_escape($version); ?>" <?php echo ($selectedVersion === $version) ? 'selected' : ''; ?>>
                    <?php echo html_escape($version); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 mb-2">
              <label for="per_page" class="mb-1">Rows Per Page</label>
              <select name="per_page" id="per_page" class="form-control">
                <option value="100" <?php echo ($perPage === 100) ? 'selected' : ''; ?>>100 / page</option>
                <option value="200" <?php echo ($perPage === 200) ? 'selected' : ''; ?>>200 / page</option>
                <option value="500" <?php echo ($perPage === 500) ? 'selected' : ''; ?>>500 / page</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
            <div class="col-md-3 mb-2">
              <a href="<?php echo base_url('admin/emarking/export_eng_crqs_barcodes_csv' . ($selectedVersion !== '' ? '?version=' . urlencode($selectedVersion) : '')); ?>" class="btn btn-primary btn-block">Download CSV</a>
            </div>
          </div>
        </form>

        <div class="alert alert-info mb-3">
          <div><strong>Page <?php echo number_format($currentPage); ?></strong> of <strong><?php echo number_format($totalPages); ?></strong></div>
          <div>Showing <strong><?php echo number_format($shownCount); ?></strong> record(s) of <strong><?php echo number_format($totalRows); ?></strong></div>
          <div>Source: <code>digital_papers_booklets1</code> with <code>paper_generated = 1</code></div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 70px;">Sr</th>
                <th>Grade</th>
                <th>Subject</th>
                <th>Version</th>
                <th>Type</th>
                <th>Barcode</th>
                <th>Status</th>
                <?php if ($showImageBarcode): ?>
                  <th>question_no</th>
                  <th>Image_Barcode</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="<?php echo $showImageBarcode ? 8 : 7; ?>" class="text-center text-muted">No records found</td>
                </tr>
              <?php else: ?>
                <?php $sr = $startSr; ?>
                <?php foreach ($rows as $row): ?>
                  <tr>
                    <td><?php echo (int) $sr++; ?></td>
                    <td><?php echo html_escape((string) ($row->grade ?? '4')); ?></td>
                    <td>ENGLISH</td>
                    <td><?php echo html_escape((string) ($row->version ?? '')); ?></td>
                    <td>CRQ</td>
                    <td><?php echo html_escape((string) ($row->barcode ?? '')); ?></td>
                    <td><?php echo html_escape((string) ($row->status ?? 'Missing')); ?></td>
                    <?php if ($showImageBarcode): ?>
                      <td><?php echo html_escape((string) ($row->question_no ?? 'q1')); ?></td>
                      <td><?php echo html_escape((string) ($row->image_barcode ?? '')); ?></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($paginationLinks !== ''): ?>
          <div class="mt-3">
            <?php echo $paginationLinks; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

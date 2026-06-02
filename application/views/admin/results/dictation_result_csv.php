<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$subjectName = function ($code) {
  $map = [
    '1' => 'English',
    '2' => 'Urdu',
  ];
  $key = trim((string) $code);
  return $map[$key] ?? $key;
};
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'Dictation Result CSV')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Results</li>
          <li class="breadcrumb-item active">Dictation Result CSV</li>
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
          <h3 class="card-title mb-0">Dictation Result CSV</h3>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-primary" href="<?php echo base_url('admin/emarking/reports_dictation_csv'); ?>">Dictation Result CSV</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form method="get" action="<?php echo base_url('admin/emarking/reports_dictation_csv'); ?>" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <select name="grade" class="form-control">
                <option value="">Grade (All)</option>
                <option value="4" <?php echo (($filters['grade'] ?? '') === '4') ? 'selected' : ''; ?>>4</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="subject_code" class="form-control">
                <option value="">Subject (All)</option>
                <?php foreach (($subject_options ?? []) as $code => $label): ?>
                  <option value="<?php echo html_escape((string) $code); ?>" <?php echo (($filters['subject_code'] ?? '') === (string) $code) ? 'selected' : ''; ?>>
                    <?php echo html_escape($subjectName($code)); ?>
                  </option>
                <?php endforeach; ?>
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
              <input type="text" class="form-control" value="DICTATION" readonly>
            </div>
          </div>
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
            <div class="col-md-2 mb-2">
              <a href="<?php echo base_url('admin/emarking/reports_dictation_csv'); ?>" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
            <div class="col-md-3 mb-2">
              <a href="<?php echo base_url('admin/emarking/export_dictation_results_csv?' . http_build_query($filters ?? [])); ?>" class="btn btn-primary btn-block">Export CSV</a>
            </div>
          </div>
        </form>

        <div class="alert alert-light border">
          <strong>CSV columns:</strong>
          <?php echo html_escape(implode(', ', (array) ($csv_headers ?? []))); ?>
        </div>

        <?php if (empty($show_preview)): ?>
          <div class="alert alert-warning mb-0">
            Apply at least one narrowing filter like Subject, Version, District, or School / EMIS Code to load preview rows.
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
                    <td colspan="<?php echo count((array) ($csv_headers ?? [])); ?>" class="text-center text-muted">No dictation result rows found for the selected filters.</td>
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

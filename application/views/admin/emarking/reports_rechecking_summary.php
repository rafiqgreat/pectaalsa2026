<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$role = (int) logged('role');
$subjectName = function ($code) {
  $c = trim((string) $code);
  $map = [
    '1' => 'English',
    '2' => 'Urdu',
    '3' => 'Math',
    '4' => 'Science',
  ];
  return $map[$c] ?? ($c !== '' ? $c : '-');
};
$subjectOptions = isset($subject_options) && is_array($subject_options) && !empty($subject_options)
  ? $subject_options
  : [1 => 'ENGLISH', 2 => 'URDU', 3 => 'MATH', 4 => 'SCIENCE'];
$selectedSubject = $filters['subject_code'] ?? '';
if (is_array($selectedSubject)) $selectedSubject = '';
$selectedEmarker = (string) ($filters['emarker_id'] ?? '');
$selectedGrade = trim((string) ($filters['grade'] ?? ''));
if ($selectedGrade === '') $selectedGrade = '4';
$emarkerLabel = function ($name, $emarkerId) {
  $rawName = trim((string) $name);
  $cleanName = preg_replace('/\s*\([^)]*\)\s*$/', '', $rawName);
  $cleanName = trim((string) $cleanName);
  $id = (int) $emarkerId;
  if ($cleanName === '') {
    return (string) $id;
  }
  return $cleanName . ' (' . $id . ')';
};
$exportParams = [];
foreach (['from', 'to', 'assessment_type', 'grade', 'subject_code', 'emarker_id'] as $filterKey) {
  $filterValue = $filters[$filterKey] ?? '';
  if (is_array($filterValue)) {
    continue;
  }
  $filterValue = trim((string) $filterValue);
  if ($filterValue !== '') {
    $exportParams[] = rawurlencode($filterKey) . '=' . rawurlencode($filterValue);
  }
}
$exportUrl = base_url('admin/emarking/export_rechecking_summary_csv' . (!empty($exportParams) ? ('?' . implode('&', $exportParams)) : ''));
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'Reports')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Reports</li>
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
          <h3 class="card-title mb-0">Reports</h3>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_questions'); ?>">Question-wise</a>
            <?php if ($role !== 18): ?>
              <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_subjects'); ?>">Subject-wise</a>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_emarkers'); ?>">eMarker-wise</a>
            <a class="btn btn-primary" href="<?php echo base_url('admin/emarking/rechecking_summary'); ?>">Rechecking Summary</a>
            <?php if ($role === 1): ?>
              <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_emarkers_payment_summary'); ?>">Payment Summary</a>
              <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_dictation_csv'); ?>">Dictation Result CSV</a>
              <a class="btn btn-outline-secondary" href="<?php echo base_url('admin/emarking/reports_batches'); ?>">Batch-wise</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if ($role === 1): ?>
          <form method="post" class="mb-3" onsubmit="return confirm('Regenerate the full rechecking pool for all subjects?');">
            <button type="submit" class="btn btn-danger btn-sm">Regenerate Pool</button>
          </form>
        <?php endif; ?>

        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <input type="date" name="from" value="<?php echo htmlspecialchars((string) ($filters['from'] ?? '')); ?>" class="form-control" placeholder="From">
            </div>
            <div class="col-md-2 mb-2">
              <input type="date" name="to" value="<?php echo htmlspecialchars((string) ($filters['to'] ?? '')); ?>" class="form-control" placeholder="To">
            </div>
            <div class="col-md-2 mb-2">
              <select name="assessment_type" class="form-control">
                <option value="">Assessment (All)</option>
                <option value="CRQ" <?php echo (($filters['assessment_type'] ?? '') === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo (($filters['assessment_type'] ?? '') === 'DICTATION') ? 'selected' : ''; ?>>Dictation</option>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <select name="grade" class="form-control">
                <option value="">Grade (All)</option>
                <option value="4" <?php echo ($selectedGrade === '4') ? 'selected' : ''; ?>>4</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="subject_code" class="form-control">
                <option value="">Subject (All)</option>
                <?php foreach ($subjectOptions as $code => $name): ?>
                  <?php $codeStr = (string) $code; ?>
                  <option value="<?php echo html_escape($codeStr); ?>" <?php echo ((string) $selectedSubject === $codeStr) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) $subjectName($codeStr)); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="emarker_id" class="form-control">
                <option value="">eMarker (All)</option>
                <?php foreach ((array) ($emarker_options ?? []) as $option): ?>
                  <?php $id = (string) ($option->emarker_id ?? ''); ?>
                  <option value="<?php echo html_escape($id); ?>" <?php echo ($selectedEmarker === $id) ? 'selected' : ''; ?>>
                    <?php echo html_escape($emarkerLabel($option->emarker_name ?? '', $option->emarker_id ?? 0) . ' - ' . trim((string) ($option->emarker_username ?? ''))); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
          </div>
        </form>

        <div class="mb-2">
          <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
            <h5 class="mb-0">eMarker-wise Rechecking Summary</h5>
            <a href="<?php echo $exportUrl; ?>" class="btn btn-primary btn-sm">Download CSV</a>
          </div>
          <?php
          $totalMarked = 0;
          $totalRechecked = 0;
          $totalMaxMarks = 0;
          $totalRecheckedMaxMarks = 0;
          foreach ((array) ($rechecking_summary ?? []) as $summaryRow) {
            $totalMarked += (int) ($summaryRow->marked ?? 0);
            $totalRechecked += (int) ($summaryRow->rechecked ?? 0);
            $totalMaxMarks += (float) ($summaryRow->total_max_marks ?? 0);
            $totalRecheckedMaxMarks += (float) ($summaryRow->rechecked_total_max_marks ?? 0);
          }
          ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead>
                <tr>
                  <th style="width:70px;">Sr No</th>
                  <th>eMarker</th>
                  <th>Username</th>
                  <th>Subject</th>
                  <th>Marked</th>
                  <th>Total Max Marks</th>
                  <th>Rechecked</th>
                  <th>Rechecked Total Max Marks</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rechecking_summary)): ?>
                  <tr><td colspan="8" class="text-center text-muted">No records</td></tr>
                <?php else: ?>
                  <?php $sr = 1; ?>
                  <?php foreach ($rechecking_summary as $row): ?>
                    <tr>
                      <td><?php echo (int) $sr++; ?></td>
                      <td><?php echo htmlspecialchars((string) $emarkerLabel($row->emarker_name ?? '', $row->emarker_id ?? 0)); ?></td>
                      <td><?php echo htmlspecialchars((string) ($row->emarker_username ?? '')); ?></td>
                      <td><?php echo htmlspecialchars((string) $subjectName($row->subject_id ?? '')); ?></td>
                      <td><?php echo (int) ($row->marked ?? 0); ?></td>
                      <td><?php echo number_format((float) ($row->total_max_marks ?? 0), 2); ?></td>
                      <td><?php echo (int) ($row->rechecked ?? 0); ?></td>
                      <td><?php echo (int) round((float) ($row->rechecked_total_max_marks ?? 0)); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <tr class="font-weight-bold">
                    <td colspan="4" class="text-right">Total</td>
                    <td><?php echo (int) $totalMarked; ?></td>
                    <td><?php echo number_format($totalMaxMarks, 2); ?></td>
                    <td><?php echo (int) $totalRechecked; ?></td>
                    <td><?php echo (int) round($totalRecheckedMaxMarks); ?></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$tab = (string) ($reports_tab ?? 'questions');
$allowedTabs = ['questions', 'subjects', 'emarkers', 'batches', 'emarkers_payment'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'questions';
$role = (int) logged('role');
$subjectName = function ($code) {
  $c = trim((string) $code);
  $map = [
    '1' => 'English',
    '2' => 'Urdu',
    '3' => 'Math',
    '4' => 'Science',
  ];
  return $map[$c] ?? ($c !== '' ? $c : '—');
};
$subjectMultiLabel = function ($codes) use ($subjectName) {
  $raw = trim((string) $codes);
  if ($raw === '') return '';
  $parts = array_filter(array_map('trim', explode(',', $raw)), function ($v) { return $v !== ''; });
  $labels = [];
  foreach ($parts as $p) {
    $labels[] = $subjectName($p);
  }
  return implode(', ', array_values(array_unique($labels)));
};
$subjectOptions = isset($subject_options) && is_array($subject_options) && !empty($subject_options)
  ? $subject_options
  : [1 => 'ENGLISH', 2 => 'URDU', 3 => 'MATH', 4 => 'SCIENCE'];
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
            <a class="btn btn-<?php echo ($tab === 'questions') ? 'primary' : 'outline-secondary'; ?>" href="<?php echo base_url('admin/emarking/reports_questions'); ?>">Question-wise</a>
            <a class="btn btn-<?php echo ($tab === 'subjects') ? 'primary' : 'outline-secondary'; ?>" href="<?php echo base_url('admin/emarking/reports_subjects'); ?>">Subject-wise</a>
            <a class="btn btn-<?php echo ($tab === 'emarkers') ? 'primary' : 'outline-secondary'; ?>" href="<?php echo base_url('admin/emarking/reports_emarkers'); ?>">eMarker-wise</a>
            <?php if ($role === 1): ?>
              <a class="btn btn-<?php echo ($tab === 'emarkers_payment') ? 'primary' : 'outline-secondary'; ?>" href="<?php echo base_url('admin/emarking/reports_emarkers_payment_summary'); ?>">Payment Summary</a>
            <?php endif; ?>
            <a class="btn btn-<?php echo ($tab === 'batches') ? 'primary' : 'outline-secondary'; ?>" href="<?php echo base_url('admin/emarking/reports_batches'); ?>">Batch-wise</a>
          </div>
        </div>
      </div>
      <div class="card-body">
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
              <?php $gr = trim((string) ($filters['grade'] ?? '')); ?>
              <?php if ($gr === '') $gr = '4'; ?>
              <select name="grade" class="form-control">
                <option value="">Grade (All)</option>
                <option value="4" <?php echo ($gr === '4') ? 'selected' : ''; ?>>4</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="subject_code" class="form-control">
                <?php $rawSc = $filters['subject_code'] ?? ''; ?>
                <?php $sc = is_array($rawSc) ? '' : trim((string) $rawSc); ?>
                <option value="">Subject (All)</option>
                <?php foreach ($subjectOptions as $code => $name): ?>
                  <?php $codeStr = (string) $code; ?>
                  <option value="<?php echo html_escape($codeStr); ?>" <?php echo ($sc === $codeStr) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) $subjectName($codeStr)); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
          </div>
        </form>

        <?php if ($tab === 'questions'): ?>
          <div class="mb-4">
            <h5 class="mb-2">Overall Summary</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Total Images</th>
                    <th>Uploaded/Pending</th>
                    <th>Assigned</th>
                    <th>Marked</th>
                    <th>Skipped</th>
                    <th>Not Attempted</th>
                    <th>Recheck</th>
                    <th>Finalized</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><?php echo (int) ($overall_summary->total_images ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->uploaded ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->assigned ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->marked ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->skipped ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->not_attempted ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->recheck ?? 0); ?></td>
                    <td><?php echo (int) ($overall_summary->finalized ?? 0); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="mb-2">
            <h5 class="mb-2">Question-wise Summary</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-hover mb-0">
                <thead>
                  <tr>
                    <th>Assessment</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Ver</th>
                    <th>Page</th>
                    <th>Q.No</th>
                    <th>Title</th>
                    <th>Total</th>
                    <th>Uploaded</th>
                    <th>Assigned</th>
                    <th>Marked</th>
                    <th>Skipped</th>
                    <th>Not Attempted</th>
                    <th>Recheck</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($question_summary)): ?>
                    <tr><td colspan="14" class="text-center text-muted">No records</td></tr>
                  <?php else: ?>
                    <?php foreach ($question_summary as $r): ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) $r->assessment_type); ?></td>
                        <td><?php echo (int) $r->grade; ?></td>
                        <td><?php echo htmlspecialchars((string) $subjectName($r->subject_code)); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->version); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->page_no); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->question_no); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->question_title); ?></td>
                        <td><?php echo (int) $r->total_images; ?></td>
                        <td><?php echo (int) $r->uploaded; ?></td>
                        <td><?php echo (int) $r->assigned; ?></td>
                        <td><?php echo (int) $r->marked; ?></td>
                        <td><?php echo (int) $r->skipped; ?></td>
                        <td><?php echo (int) $r->not_attempted; ?></td>
                        <td><?php echo (int) $r->recheck; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php elseif ($tab === 'subjects'): ?>
          <div class="mb-2">
            <h5 class="mb-2">Subject-wise Summary</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Assessment</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Total</th>
                    <th>Uploaded</th>
                    <th>Assigned</th>
                    <th>Marked</th>
                    <th>Skipped</th>
                    <th>Not Attempted</th>
                    <th>Recheck</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($subject_summary)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No records</td></tr>
                  <?php else: ?>
                    <?php
                    $subject_totals = [
                      'total_images' => 0,
                      'uploaded' => 0,
                      'assigned' => 0,
                      'marked' => 0,
                      'skipped' => 0,
                      'not_attempted' => 0,
                      'recheck' => 0,
                    ];
                    ?>
                    <?php foreach ($subject_summary as $r): ?>
                      <?php
                      $subject_totals['total_images'] += (int) ($r->total_images ?? 0);
                      $subject_totals['uploaded'] += (int) ($r->uploaded ?? 0);
                      $subject_totals['assigned'] += (int) ($r->assigned ?? 0);
                      $subject_totals['marked'] += (int) ($r->marked ?? 0);
                      $subject_totals['skipped'] += (int) ($r->skipped ?? 0);
                      $subject_totals['not_attempted'] += (int) ($r->not_attempted ?? 0);
                      $subject_totals['recheck'] += (int) ($r->recheck ?? 0);
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) $r->assessment_type); ?></td>
                        <td><?php echo (int) $r->grade; ?></td>
                        <td><?php echo htmlspecialchars((string) $subjectName($r->subject_code)); ?></td>
                        <td><?php echo (int) $r->total_images; ?></td>
                        <td><?php echo (int) $r->uploaded; ?></td>
                        <td><?php echo (int) $r->assigned; ?></td>
                        <td><?php echo (int) $r->marked; ?></td>
                        <td><?php echo (int) $r->skipped; ?></td>
                        <td><?php echo (int) $r->not_attempted; ?></td>
                        <td><?php echo (int) $r->recheck; ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <tr class="font-weight-bold table-secondary">
                      <td colspan="3" class="text-right">Total</td>
                      <td><?php echo (int) $subject_totals['total_images']; ?></td>
                      <td><?php echo (int) $subject_totals['uploaded']; ?></td>
                      <td><?php echo (int) $subject_totals['assigned']; ?></td>
                      <td><?php echo (int) $subject_totals['marked']; ?></td>
                      <td><?php echo (int) $subject_totals['skipped']; ?></td>
                      <td><?php echo (int) $subject_totals['not_attempted']; ?></td>
                      <td><?php echo (int) $subject_totals['recheck']; ?></td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php elseif ($tab === 'emarkers_payment'): ?>
          <div class="mb-2">
            <h5 class="mb-2">eMarker-wise Payment Summary</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width:70px;">Sr No</th>
                    <th>eMarker</th>
                    <th>Username</th>
                    <th>Subject</th>
                    <th>Total Actions</th>
                    <th>Marked</th>
                    <th>Duration (Hours)</th>
                    <th>Skipped</th>
                    <th>Not Attempted</th>
                    <th>Total Marks</th>
                    <th>Total Max Marks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $rows = isset($emarker_payment_summary) ? $emarker_payment_summary : []; ?>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="11" class="text-center text-muted">No records</td></tr>
                  <?php else: ?>
                    <?php $sr = 1; ?>
                    <?php foreach ($rows as $r): ?>
                      <tr>
                        <td><?php echo (int) $sr++; ?></td>
                        <td><?php echo htmlspecialchars((string) $r->emarker_name); ?> (<?php echo (int) $r->emarker_id; ?>)</td>
                        <td><?php echo htmlspecialchars((string) $r->emarker_username); ?></td>
                        <td><?php echo htmlspecialchars((string) $subjectMultiLabel($r->subjects ?? '')); ?></td>
                        <td><?php echo (int) $r->total_actions; ?></td>
                        <td><?php echo (int) $r->marked; ?></td>
                        <td><?php echo number_format((float) ($r->duration_hours ?? 0), 2); ?></td>
                        <td><?php echo (int) $r->skipped; ?></td>
                        <td><?php echo (int) $r->not_attempted; ?></td>
                        <td><?php echo number_format((float) ($r->total_marks ?? 0), 2); ?></td>
                        <td><?php echo number_format((float) ($r->total_max_marks ?? 0), 2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php elseif ($tab === 'emarkers'): ?>
          <div class="mb-2">
            <h5 class="mb-2">eMarker-wise Summary</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width:70px;">Sr No</th>
                    <th>eMarker</th>
                    <th>Username</th>
                    <th>Subject</th>
                    <th>Marked</th>
                    <?php if ((int) logged('role') !== 18): ?>
                      <th>Total Actions</th>
                      <th>Skipped</th>
                      <th>Not Attempted</th>
                      <th>Total Marks</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($emarker_summary)): ?>
                    <tr><td colspan="<?php echo ((int) logged('role') === 18) ? 5 : 9; ?>" class="text-center text-muted">No records</td></tr>
                  <?php else: ?>
                    <?php $sr = 1; ?>
                    <?php foreach ($emarker_summary as $r): ?>
                      <tr>
                        <td><?php echo (int) $sr++; ?></td>
                        <td><?php echo htmlspecialchars((string) $r->emarker_name); ?> (<?php echo (int) $r->emarker_id; ?>)</td>
                        <td><?php echo htmlspecialchars((string) $r->emarker_username); ?></td>
                        <td><?php echo htmlspecialchars((string) $subjectMultiLabel($r->subjects ?? '')); ?></td>
                        <td><?php echo (int) $r->marked; ?></td>
                        <?php if ((int) logged('role') !== 18): ?>
                          <td><?php echo (int) $r->total_actions; ?></td>
                          <td><?php echo (int) $r->skipped; ?></td>
                          <td><?php echo (int) $r->not_attempted; ?></td>
                          <td><?php echo number_format((float) $r->total_marks, 2); ?></td>
                        <?php endif; ?>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php else: ?>
          <div class="mb-2">
            <h5 class="mb-2">Batch-wise Summary</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Batch</th>
                    <th>Assessment</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>eMarker</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Pending</th>
                    <th>Marked</th>
                    <th>Skipped</th>
                    <th>N.A.</th>
                    <th>Recheck</th>
                    <th>Created</th>
                    <th>Deadline</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($batch_summary)): ?>
                    <tr><td colspan="14" class="text-center text-muted">No records</td></tr>
                  <?php else: ?>
                    <?php foreach ($batch_summary as $r): ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) $r->batch_code); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->assessment_type); ?></td>
                        <td><?php echo (int) $r->grade; ?></td>
                        <td><?php echo htmlspecialchars((string) $subjectName($r->subject_code)); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->emarker_name); ?> (<?php echo (int) $r->assigned_to; ?>)</td>
                        <td><?php echo htmlspecialchars((string) $r->status); ?></td>
                        <td><?php echo (int) $r->total_items; ?></td>
                        <td><?php echo (int) $r->pending_items; ?></td>
                        <td><?php echo (int) $r->marked_items; ?></td>
                        <td><?php echo (int) $r->skipped_items; ?></td>
                        <td><?php echo (int) $r->not_attempted_items; ?></td>
                        <td><?php echo (int) $r->recheck_items; ?></td>
                        <td><?php echo htmlspecialchars((string) $r->created_at); ?></td>
                        <td><?php echo htmlspecialchars((string) $r->deadline); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

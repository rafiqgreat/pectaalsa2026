<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
// Summary for configured (active) rubric steps
$active_count = 0;
$rubric_max_total = 0.0;
if (!empty($rubric_steps)) {
  foreach ($rubric_steps as $s) {
    if ((int) ($s->status ?? 0) !== 1) continue;
    $active_count++;
    $type = (string) ($s->marking_type ?? 'ZERO_ONE');
    if ($type === 'RANGE') {
      $rubric_max_total += (float) ($s->max_marks ?? 0);
    } else {
      $rubric_max_total += (float) ($s->step_marks ?? 0);
    }
  }
}
$question_max = (float) ($question->max_marks ?? 0);
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Rubric Steps</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/questions'); ?>">Questions</a></li>
          <li class="breadcrumb-item active">Rubric</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="alert alert-info">
      <div><strong>Question Max Marks:</strong> <?php echo htmlspecialchars(number_format($question_max, 2)); ?></div>
      <div><strong>Rubric Max Total (Active Steps):</strong> <?php echo htmlspecialchars(number_format($rubric_max_total, 2)); ?> | <strong>Active Steps:</strong> <?php echo (int) $active_count; ?> / 15</div>
      <?php if ($question_max > 0 && abs($rubric_max_total - $question_max) > 0.0001): ?>
        <div class="text-warning mt-1"><strong>Note:</strong> Rubric max total does not match question max marks.</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          Question: <?php echo htmlspecialchars((string) $question->question_no); ?> — <?php echo htmlspecialchars((string) $question->question_title); ?>
        </h3>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo base_url('admin/emarking/save_rubric_step'); ?>" id="rubricForm">
          <input type="hidden" name="id" id="step_id" value="">
          <input type="hidden" name="question_id" value="<?php echo (int) $question->id; ?>">

          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Order</label>
              <input type="number" name="step_order" id="step_order" class="form-control" value="1" required>
            </div>
            <div class="form-group col-md-2">
              <label>Label</label>
              <input type="text" name="step_label" id="step_label" class="form-control" placeholder="e.g. a, i">
            </div>
            <div class="form-group col-md-4">
              <label>Title</label>
              <input type="text" name="step_title" id="step_title" class="form-control" required>
            </div>
            <div class="form-group col-md-2">
              <label>Step Marks</label>
              <input type="number" step="0.01" name="step_marks" id="step_marks" class="form-control" value="1.00">
            </div>
            <div class="form-group col-md-2">
              <label>Status</label>
              <select name="status" id="step_status" class="form-control">
                <option value="1" selected>Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Marking Type</label>
              <select name="marking_type" id="marking_type" class="form-control">
                <option value="ZERO_ONE">ZERO_ONE</option>
                <option value="FIXED">FIXED</option>
                <option value="RANGE">RANGE</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Min Marks (RANGE)</label>
              <input type="number" step="0.01" name="min_marks" id="min_marks" class="form-control" value="0.00">
            </div>
            <div class="form-group col-md-3">
              <label>Max Marks (RANGE)</label>
              <input type="number" step="0.01" name="max_marks" id="max_marks" class="form-control" value="1.00">
            </div>
            <div class="form-group col-md-3">
              <label>Detail (optional)</label>
              <input type="text" name="step_detail" id="step_detail" class="form-control">
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save Step</button>
          <button type="button" class="btn btn-secondary" onclick="resetStepForm()">Clear</button>
          <a href="<?php echo base_url('admin/emarking/edit_question/' . (int) $question->id); ?>" class="btn btn-link">Back to Question</a>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Existing Steps</h3>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Order</th>
              <th>Label</th>
              <th>Title</th>
              <th>Marks</th>
              <th>Type</th>
              <th>Range</th>
              <th>Status</th>
              <th style="width:150px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rubric_steps)): ?>
              <tr><td colspan="9" class="text-center text-muted">No steps yet</td></tr>
            <?php else: ?>
              <?php foreach ($rubric_steps as $s): ?>
                <tr>
                  <td><?php echo (int) $s->id; ?></td>
                  <td><?php echo (int) $s->step_order; ?></td>
                  <td><?php echo htmlspecialchars((string) $s->step_label); ?></td>
                  <td><?php echo htmlspecialchars((string) $s->step_title); ?></td>
                  <td><?php echo htmlspecialchars((string) $s->step_marks); ?></td>
                  <td><?php echo htmlspecialchars((string) $s->marking_type); ?></td>
                  <td><?php echo htmlspecialchars((string) $s->min_marks) . ' - ' . htmlspecialchars((string) $s->max_marks); ?></td>
                  <td><?php echo ((int) $s->status === 1) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                  <td>
                    <button type="button" class="btn btn-info btn-xs"
                      onclick='editStep(<?php echo (int) $s->id; ?>,
                        <?php echo (int) $s->step_order; ?>,
                        <?php echo json_encode((string) $s->step_label); ?>,
                        <?php echo json_encode((string) $s->step_title); ?>,
                        <?php echo json_encode((string) $s->step_detail); ?>,
                        <?php echo json_encode((string) $s->step_marks); ?>,
                        <?php echo json_encode((string) $s->marking_type); ?>,
                        <?php echo json_encode((string) $s->min_marks); ?>,
                        <?php echo json_encode((string) $s->max_marks); ?>,
                        <?php echo (int) $s->status; ?>
                      )'>Edit</button>
                    <a class="btn btn-danger btn-xs"
                      href="<?php echo base_url('admin/emarking/delete_rubric_step/' . (int) $s->id); ?>"
                      onclick="return confirm('Delete this step?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</section>

<script>
function editStep(id, order, label, title, detail, marks, type, minMarks, maxMarks, status) {
  document.getElementById('step_id').value = id;
  document.getElementById('step_order').value = order;
  document.getElementById('step_label').value = label || '';
  document.getElementById('step_title').value = title || '';
  document.getElementById('step_detail').value = detail || '';
  document.getElementById('step_marks').value = marks || '0.00';
  document.getElementById('marking_type').value = type || 'ZERO_ONE';
  document.getElementById('min_marks').value = minMarks || '0.00';
  document.getElementById('max_marks').value = maxMarks || '1.00';
  document.getElementById('step_status').value = (status == 1) ? '1' : '0';
  try {
    window.scrollTo({top: 0, behavior: 'smooth'});
  } catch (e) {
    window.scrollTo(0, 0);
  }
}
function resetStepForm() {
  document.getElementById('step_id').value = '';
  document.getElementById('step_order').value = 1;
  document.getElementById('step_label').value = '';
  document.getElementById('step_title').value = '';
  document.getElementById('step_detail').value = '';
  document.getElementById('step_marks').value = '1.00';
  document.getElementById('marking_type').value = 'ZERO_ONE';
  document.getElementById('min_marks').value = '0.00';
  document.getElementById('max_marks').value = '1.00';
  document.getElementById('step_status').value = '1';
}
</script>

<?php include viewPath('admin/includes/footer'); ?>

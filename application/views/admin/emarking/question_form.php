<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$is_edit = !empty($question) && !empty($question->id);
$action_url = $is_edit ? base_url('admin/emarking/edit_question/' . (int) $question->id) : base_url('admin/emarking/add_question');
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo $is_edit ? 'Edit Question' : 'Add Question'; ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/questions'); ?>">Questions</a></li>
          <li class="breadcrumb-item active"><?php echo $is_edit ? 'Edit' : 'Add'; ?></li>
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
        <div class="d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0">Question Details</h3>
          <?php if ($is_edit): ?>
            <a class="btn btn-warning btn-sm" href="<?php echo base_url('admin/emarking/rubric_steps/' . (int) $question->id); ?>">
              <i class="fas fa-list"></i> Rubric Steps
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo $action_url; ?>" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Assessment Type</label>
              <select name="assessment_type" class="form-control" required>
                <option value="CRQ" <?php echo ($is_edit && (string) $question->assessment_type === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo ($is_edit && (string) $question->assessment_type === 'DICTATION') ? 'selected' : ''; ?>>Dictation</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Grade</label>
              <?php $grade_val = (string) ($question->grade ?? '4'); ?>
              <input type="hidden" name="grade" value="4">
              <select class="form-control" disabled>
                <option value="4" selected>Grade 4</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Subject <span class="text-danger">*</span></label>
              <?php $subject_code_val = (string) ($question->subject_code ?? ''); ?>
              <select name="subject_code" class="form-control" required>
                <option value="" disabled <?php echo ($subject_code_val === '') ? 'selected' : ''; ?>>Select Subject</option>
                <?php
                  $subject_options = isset($subject_options) && is_array($subject_options) ? $subject_options : [];
                  foreach ($subject_options as $code => $name):
                ?>
                  <option value="<?php echo (int) $code; ?>" <?php echo ($subject_code_val === (string) $code) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Version <span class="text-danger">*</span></label>
              <?php $version_val = (string) ($question->version ?? ''); ?>
              <select name="version" class="form-control" required>
                <option value="" disabled <?php echo ($version_val === '' || $version_val === '0') ? 'selected' : ''; ?>>Select Version</option>
                <option value="1" <?php echo ($version_val === '1') ? 'selected' : ''; ?>>V1</option>
                <option value="2" <?php echo ($version_val === '2') ? 'selected' : ''; ?>>V2</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Page No</label>
              <input type="text" name="page_no" class="form-control" required value="<?php echo htmlspecialchars((string) ($question->page_no ?? '')); ?>" placeholder="e.g. 101 / 041">
            </div>
            <div class="form-group col-md-2">
              <label>Question Folder</label>
              <input type="text" name="question_no" class="form-control" required value="<?php echo htmlspecialchars((string) ($question->question_no ?? '')); ?>" placeholder="e.g. q1">
            </div>
          </div>

          <div class="form-group">
            <label>Question Title</label>
            <textarea name="question_title" class="form-control" rows="2" required><?php echo htmlspecialchars((string) ($question->question_title ?? '')); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Question Type</label>
              <select name="question_type" class="form-control">
                <?php
                $types = ['OBJECTIVE_STEPS', 'WRITING', 'PARAGRAPH', 'LIST', 'DICTATION', 'OTHER'];
                $selected_type = (string) ($question->question_type ?? 'OBJECTIVE_STEPS');
                foreach ($types as $t):
                ?>
                  <option value="<?php echo $t; ?>" <?php echo ($selected_type === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Max Marks</label>
              <input type="number" step="0.01" name="max_marks" class="form-control" required value="<?php echo htmlspecialchars((string) ($question->max_marks ?? '5.00')); ?>">
            </div>
            <div class="form-group col-md-3">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="1" <?php echo (!$is_edit || (int) ($question->status ?? 1) === 1) ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo ($is_edit && (int) ($question->status ?? 1) === 0) ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Source Table (optional)</label>
              <input type="text" name="source_table" class="form-control" value="<?php echo htmlspecialchars((string) ($question->source_table ?? '')); ?>" placeholder="auto if empty">
              <small class="text-muted">Auto-filled based on Assessment + Subject Code if left empty.</small>
            </div>
          </div>

          <div class="form-group">
            <label>Rubric Title (optional)</label>
            <input type="text" name="rubric_title" class="form-control" value="<?php echo htmlspecialchars((string) ($question->rubric_title ?? '')); ?>">
          </div>
          <div class="form-group">
            <label>Rubric Detail (optional)</label>
            <textarea name="rubric_detail" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($question->rubric_detail ?? '')); ?></textarea>
          </div>

          <div class="form-group">
            <label>Sample Answer (optional)</label>
            <textarea name="sample_answer" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($question->sample_answer ?? '')); ?></textarea>
          </div>

          <div class="form-group">
            <label>Guide Text (optional)</label>
            <textarea name="guide_text" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($question->guide_text ?? '')); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Sample Answer File (optional)</label>
              <input type="file" name="sample_answer_file" class="form-control">
              <?php if ($is_edit && !empty($question->sample_answer_file)): ?>
                <input type="hidden" name="remove_sample_answer_file" id="remove_sample_answer_file" value="0">
                <div id="sample_answer_file_current">
                  <small class="text-muted d-block">
                    Current:
                    <a href="<?php echo base_url((string) $question->sample_answer_file); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars((string) $question->sample_answer_file); ?>
                    </a>
                  </small>
                </div>
                <div class="mt-1" id="sample_answer_file_controls">
                  <button type="button" class="btn btn-danger btn-xs"
                    onclick="confirmRemoveUpload('sample_answer_file', 'remove_sample_answer_file', 'sample_answer_file_controls')">
                    Remove
                  </button>
                </div>
              <?php endif; ?>
            </div>
            <div class="form-group col-md-4">
              <label>Guide File (optional)</label>
              <input type="file" name="guide_file" class="form-control">
              <?php if ($is_edit && !empty($question->guide_file)): ?>
                <input type="hidden" name="remove_guide_file" id="remove_guide_file" value="0">
                <div id="guide_file_current">
                  <small class="text-muted d-block">
                    Current:
                    <a href="<?php echo base_url((string) $question->guide_file); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars((string) $question->guide_file); ?>
                    </a>
                  </small>
                </div>
                <div class="mt-1" id="guide_file_controls">
                  <button type="button" class="btn btn-danger btn-xs"
                    onclick="confirmRemoveUpload('guide_file', 'remove_guide_file', 'guide_file_controls')">
                    Remove
                  </button>
                </div>
              <?php endif; ?>
            </div>
            <div class="form-group col-md-4">
              <label>Question Paper File (optional)</label>
              <input type="file" name="question_paper_file" class="form-control">
              <?php if ($is_edit && !empty($question->question_paper_file)): ?>
                <input type="hidden" name="remove_question_paper_file" id="remove_question_paper_file" value="0">
                <div id="question_paper_file_current">
                  <small class="text-muted d-block">
                    Current:
                    <a href="<?php echo base_url((string) $question->question_paper_file); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars((string) $question->question_paper_file); ?>
                    </a>
                  </small>
                </div>
                <div class="mt-1" id="question_paper_file_controls">
                  <button type="button" class="btn btn-danger btn-xs"
                    onclick="confirmRemoveUpload('question_paper_file', 'remove_question_paper_file', 'question_paper_file_controls')">
                    Remove
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update' : 'Save'; ?></button>
          <a href="<?php echo base_url('admin/emarking/questions'); ?>" class="btn btn-secondary">Back</a>
        </form>
      </div>
    </div>

    <?php if ($is_edit): ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title mb-0">Rubric Steps (Preview)</h3>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Order</th>
                <th>Label</th>
                <th>Title</th>
                <th>Marks</th>
                <th>Type</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rubric_steps)): ?>
                <tr><td colspan="7" class="text-center text-muted">No steps yet</td></tr>
              <?php else: ?>
                <?php foreach ($rubric_steps as $s): ?>
                  <tr>
                    <td><?php echo (int) $s->id; ?></td>
                    <td><?php echo (int) $s->step_order; ?></td>
                    <td><?php echo htmlspecialchars((string) $s->step_label); ?></td>
                    <td><?php echo htmlspecialchars((string) $s->step_title); ?></td>
                    <td><?php echo htmlspecialchars((string) $s->step_marks); ?></td>
                    <td><?php echo htmlspecialchars((string) $s->marking_type); ?></td>
                    <td><?php echo ((int) $s->status === 1) ? 'Active' : 'Inactive'; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<script>
var emarkingRemoveFileUrl = <?php echo json_encode(base_url('admin/emarking/remove_question_file')); ?>;
var emarkingQuestionId = <?php echo (int) ($question->id ?? 0); ?>;

function confirmRemoveUpload(field, hiddenInputId, controlsId) {
  if (!emarkingQuestionId) return;
  if (!confirm('Delete current ' + field.replace(/_/g, ' ') + '?')) return;

  var controls = document.getElementById(controlsId);
  if (controls) {
    controls.innerHTML = '<span class="text-muted">Removing...</span>';
  }

  fetch(emarkingRemoveFileUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ question_id: String(emarkingQuestionId), field: String(field) })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (!data || data.ok !== true) throw new Error((data && data.message) ? data.message : 'Failed');

    var hidden = document.getElementById(hiddenInputId);
    if (hidden) hidden.value = '1';

    var current = document.getElementById(field + '_current');
    if (current) current.parentNode.removeChild(current);

    if (controls) {
      controls.innerHTML = '<span class="badge badge-success">Removed</span>';
    }
  })
  .catch(function (e) {
    if (controls) {
      controls.innerHTML = '<span class="badge badge-danger">Remove failed</span>';
    }
    alert('Unable to remove file. ' + (e && e.message ? e.message : ''));
  });
}
</script>

<?php include viewPath('admin/includes/footer'); ?>

<script>
function mapSourceTable(assessmentType, subjectCode) {
  assessmentType = (assessmentType || '').toUpperCase();
  subjectCode = parseInt(subjectCode || '0', 10);
  if (assessmentType === 'CRQ') {
    if (subjectCode === 1) return 'digital_papers_booklets1';
    if (subjectCode === 2) return 'digital_papers_booklets2';
    if (subjectCode === 3) return 'digital_papers_booklets3';
    if (subjectCode === 4) return 'digital_papers_booklets4';
  }
  if (assessmentType === 'DICTATION') {
    if (subjectCode === 1) return 'digital_papers_dictation1';
    if (subjectCode === 2) return 'digital_papers_dictation2';
  }
  return '';
}
(function(){
  var at = document.querySelector('select[name="assessment_type"]');
  var sc = document.querySelector('input[name="subject_code"]');
  var st = document.querySelector('input[name="source_table"]');
  if (!at || !sc || !st) return;
  function autoFill(){
    if ((st.value || '').trim() !== '') return;
    var v = mapSourceTable(at.value, sc.value);
    if (v) st.value = v;
  }
  at.addEventListener('change', autoFill);
  sc.addEventListener('blur', autoFill);
  autoFill();
})();
</script>

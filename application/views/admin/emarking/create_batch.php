<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Create Batch</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/batches'); ?>">Batches</a></li>
          <li class="breadcrumb-item active">Create</li>
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
        <h3 class="card-title mb-0">Batch Details</h3>
      </div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Assessment Type</label>
              <select name="assessment_type" class="form-control">
                <option value="">All</option>
                <option value="CRQ" <?php echo (($filters['assessment_type'] ?? '') === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo (($filters['assessment_type'] ?? '') === 'DICTATION') ? 'selected' : ''; ?>>DICTATION</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Grade</label>
              <input type="text" name="grade" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['grade'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-2">
              <label>Subject Code</label>
              <input type="text" name="subject_code" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['subject_code'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-2">
              <label>Version</label>
              <input type="text" name="version" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['version'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-secondary btn-block">Filter Questions</button>
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
              <a href="<?php echo base_url('admin/emarking/create_batch'); ?>" class="btn btn-link btn-block">Reset</a>
            </div>
          </div>
        </form>

        <form method="post" action="<?php echo base_url('admin/emarking/create_batch'); ?>">
          <?php
          $deadline_value = (string) ($this->input->post('deadline', true) ?? '');
          if ($deadline_value === '') {
            $deadline_value = date('Y-m-d\\TH:i', strtotime((string) ($default_deadline_dt ?? '+3 days')));
          }
          ?>
          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Assessment Type</label>
              <select name="assessment_type" class="form-control">
                <option value="">Auto (from question)</option>
                <option value="CRQ">CRQ</option>
                <option value="DICTATION">DICTATION</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Question</label>
              <select name="question_id" class="form-control" required>
                <option value="">Select question</option>
                <?php foreach (($questions ?? []) as $q): ?>
                  <?php
                    $qid = (int) $q->id;
                    $uploadedCnt = (int) (($uploaded_counts[$qid] ?? 0));
                    $totalCnt = (int) (($total_counts[$qid] ?? 0));
                  ?>
                  <option value="<?php echo (int) $q->id; ?>">
                    <?php echo htmlspecialchars((string) $q->assessment_type); ?> | G<?php echo (int) $q->grade; ?> | S<?php echo htmlspecialchars((string) $q->subject_code); ?> | V<?php echo (int) $q->version; ?> | P<?php echo htmlspecialchars((string) $q->page_no); ?> | <?php echo htmlspecialchars((string) $q->question_no); ?> - <?php echo htmlspecialchars((string) $q->question_title); ?> | Images: <?php echo $uploadedCnt; ?> (Total: <?php echo $totalCnt; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Batch will pick images where status is <code>UPLOADED</code> for the selected question.</small>
            </div>
            <div class="form-group col-md-1">
              <label>Grade</label>
              <input type="text" name="grade" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['grade'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-1">
              <label>Subject</label>
              <input type="text" name="subject_code" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['subject_code'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-1">
              <label>Version</label>
              <input type="text" name="version" class="form-control" value="<?php echo htmlspecialchars((string) ($filters['version'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-3">
              <label>Assign To (eMarker)</label>
              <select name="emarker_id" class="form-control select2" required>
                <option value="">Select eMarker</option>
                <?php foreach (($emarkers ?? []) as $u): ?>
                  <?php
                    $name = trim((string) ($u->name ?? ''));
                    $name = $name !== '' ? $name : trim((string) ($u->username ?? ''));
                    $cnic = trim((string) ($u->cnic ?? ''));
                    $cnic = $cnic !== '' ? $cnic : trim((string) ($u->username ?? ''));
                    $label = trim($name . ' - ' . $cnic, " \t\n\r\0\x0B-");
                  ?>
                  <option value="<?php echo (int) $u->id; ?>">
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Batch Size</label>
              <input type="number" name="batch_size" class="form-control" value="100">
            </div>
            <div class="form-group col-md-1">
              <label>Deadline</label>
              <input type="datetime-local" name="deadline" class="form-control" value="<?php echo htmlspecialchars($deadline_value); ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Create</button>
          <a href="<?php echo base_url('admin/emarking/batches'); ?>" class="btn btn-secondary">Back</a>
        </form>
      </div>
    </div>

  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
  $(function () {
    if ($.fn.select2) {
      $('.select2').select2({
        width: '100%',
        placeholder: 'Select eMarker',
        allowClear: true
      });
    }
  });
</script>

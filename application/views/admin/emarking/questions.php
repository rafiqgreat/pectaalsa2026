<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">E-Marking Questions</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Questions</li>
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
          <h3 class="card-title mb-0">Questions</h3>
          <a class="btn btn-primary btn-sm" href="<?php echo base_url('admin/emarking/add_question'); ?>">
            <i class="fas fa-plus"></i> Add Question
          </a>
        </div>
      </div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <select name="assessment_type" class="form-control">
                <option value="">Assessment (All)</option>
                <option value="CRQ" <?php echo (($filters['assessment_type'] ?? '') === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo (($filters['assessment_type'] ?? '') === 'DICTATION') ? 'selected' : ''; ?>>Dictation</option>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <input type="text" name="grade" value="<?php echo html_escape((string) ($filters['grade'] ?? '')); ?>" class="form-control" placeholder="Grade">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="subject_code" value="<?php echo html_escape((string) ($filters['subject_code'] ?? '')); ?>" class="form-control" placeholder="Subject Code">
            </div>
            <div class="col-md-1 mb-2">
              <input type="text" name="version" value="<?php echo html_escape((string) ($filters['version'] ?? '')); ?>" class="form-control" placeholder="Ver">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="page_no" value="<?php echo html_escape((string) ($filters['page_no'] ?? '')); ?>" class="form-control" placeholder="Page No">
            </div>
            <div class="col-md-2 mb-2">
              <select name="status" class="form-control">
                <option value="">Status (All)</option>
                <option value="1" <?php echo (($filters['status'] ?? '') === '1') ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo (($filters['status'] ?? '') === '0') ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
          <a href="<?php echo base_url('admin/emarking/questions'); ?>" class="btn btn-link btn-sm">Reset</a>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>Assessment</th>
                <th>Grade</th>
                <th>Subject</th>
                <th>Version</th>
                <th>Page</th>
                <th>Question No</th>
                <th>Title</th>
                <th>Max Marks</th>
                <th>Status</th>
                <th>Rubric Steps</th>
                <th>Edit</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($questions)): ?>
                <tr><td colspan="11" class="text-center text-muted">No records</td></tr>
              <?php else: ?>
                <?php foreach ($questions as $q): ?>
                  <tr>
                    <td><?php echo html_escape((string) $q->assessment_type); ?></td>
                    <td><?php echo (int) $q->grade; ?></td>
                    <td><?php echo html_escape((string) $q->subject_code); ?></td>
                    <td><?php echo (int) $q->version; ?></td>
                    <td><?php echo html_escape((string) $q->page_no); ?></td>
                    <td><?php echo html_escape((string) $q->question_no); ?></td>
                    <?php $isUrdu = ((string) ($q->subject_code ?? '') === '2'); ?>
                    <td class="<?php echo $isUrdu ? 'urdufont-right' : ''; ?>">
                      <?php echo html_escape((string) $q->question_title); ?>
                    </td>
                    <td><?php echo html_escape((string) $q->max_marks); ?></td>
                    <td>
                      <?php if ((int) $q->status === 1): ?>
                        <span class="badge badge-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a class="btn btn-warning btn-xs" href="<?php echo base_url('admin/emarking/rubric_steps/' . (int) $q->id); ?>">
                        Steps (<?php echo (int) ($q->rubric_steps_count ?? 0); ?>)
                      </a>
                    </td>
                    <td>
                      <a class="btn btn-info btn-xs" href="<?php echo base_url('admin/emarking/edit_question/' . (int) $q->id); ?>">Edit</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

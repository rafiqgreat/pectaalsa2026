<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">E-Marking Batches</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Batches</li>
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
          <h3 class="card-title mb-0">Batches</h3>
          <a class="btn btn-primary btn-sm" href="<?php echo base_url('admin/emarking/create_batch'); ?>">
            <i class="fas fa-plus"></i> Create Batch
          </a>
        </div>
      </div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <select name="status" class="form-control">
                <option value="">Status (All)</option>
                <?php foreach (['PENDING','IN_PROGRESS','COMPLETED','FINALIZED'] as $st): ?>
                  <option value="<?php echo $st; ?>" <?php echo (($filters['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="assessment_type" class="form-control">
                <option value="">Assessment (All)</option>
                <option value="CRQ" <?php echo (($filters['assessment_type'] ?? '') === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo (($filters['assessment_type'] ?? '') === 'DICTATION') ? 'selected' : ''; ?>>Dictation</option>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <input type="text" name="grade" value="<?php echo htmlspecialchars((string) ($filters['grade'] ?? '')); ?>" class="form-control" placeholder="Grade">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="subject_code" value="<?php echo htmlspecialchars((string) ($filters['subject_code'] ?? '')); ?>" class="form-control" placeholder="Subject">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="assigned_to" value="<?php echo htmlspecialchars((string) ($filters['assigned_to'] ?? '')); ?>" class="form-control" placeholder="eMarker ID">
            </div>
            <div class="col-md-2 mb-2">
              <input type="text" name="question_id" value="<?php echo htmlspecialchars((string) ($filters['question_id'] ?? '')); ?>" class="form-control" placeholder="Question ID">
            </div>
            <div class="col-md-1 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Go</button>
            </div>
          </div>
          <a href="<?php echo base_url('admin/emarking/batches'); ?>" class="btn btn-link btn-sm">Reset</a>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Batch Code</th>
                <th>Assessment</th>
                <th>G</th>
                <th>S</th>
                <th>V</th>
                <th>Question</th>
                <th>Assigned To</th>
                <th>Size</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($batches)): ?>
                <tr><td colspan="12" class="text-center text-muted">No records</td></tr>
              <?php else: ?>
                <?php foreach ($batches as $b): ?>
                  <tr>
                    <td><?php echo (int) $b->id; ?></td>
                    <td><?php echo htmlspecialchars((string) $b->batch_code); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->assessment_type); ?></td>
                    <td><?php echo (int) $b->grade; ?></td>
                    <td><?php echo htmlspecialchars((string) $b->subject_code); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->version); ?></td>
                    <td>
                      <div><strong><?php echo htmlspecialchars((string) $b->question_no); ?></strong></div>
                      <div class="text-muted" style="max-width:340px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo htmlspecialchars((string) $b->question_title); ?>
                      </div>
                    </td>
                    <td>
                      <?php echo htmlspecialchars((string) $b->emarker_name); ?>
                      <div class="text-muted"><?php echo htmlspecialchars((string) $b->emarker_username); ?> (<?php echo (int) $b->assigned_to; ?>)</div>
                    </td>
                    <td><?php echo (int) $b->batch_size; ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars((string) $b->status); ?></span></td>
                    <td><?php echo htmlspecialchars((string) $b->deadline); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->created_at); ?></td>
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


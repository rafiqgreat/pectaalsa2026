<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Batch: <?php echo html_escape((string) $batch->batch_code); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('emarker/marking/dashboard'); ?>">E-Marking</a></li>
          <li class="breadcrumb-item active">Batch</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('user/includes/notifications'); ?>

    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0">
            <?php echo html_escape((string) $batch->assessment_type); ?> | G<?php echo (int) $batch->grade; ?> | S<?php echo html_escape((string) $batch->subject_code); ?> | Q<?php echo html_escape((string) $batch->question_no); ?>
          </h3>
          <div>
            <a class="btn btn-secondary btn-sm" href="<?php echo base_url('emarker/marking/dashboard'); ?>">Back</a>
            <a class="btn btn-success btn-sm" href="<?php echo base_url('emarker/marking/start/' . (int) $batch->id); ?>">Start / Continue</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <p class="mb-1"><strong>Status:</strong> <?php echo html_escape((string) $batch->status); ?></p>
            <p class="mb-1"><strong>Deadline:</strong> <?php echo html_escape((string) $batch->deadline); ?></p>
          </div>
          <div class="col-md-8">
            <p class="mb-1"><strong>Question:</strong> <?php echo html_escape((string) $batch->question_title); ?></p>
            <p class="mb-0"><strong>Max Marks:</strong> <?php echo html_escape((string) $batch->max_marks); ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Items</h3>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Barcode</th>
              <th>Roll No</th>
              <th>Item Status</th>
              <th>Marks</th>
              <th>Marked At</th>
              <th style="width:120px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr><td colspan="7" class="text-center text-muted">No items</td></tr>
            <?php else: ?>
              <?php foreach ($items as $idx => $it): ?>
                <tr>
                  <td><?php echo (int) ($idx + 1); ?></td>
                  <td><?php echo html_escape((string) $it->paper_barcode); ?></td>
                  <td><?php echo html_escape((string) $it->roll_no); ?></td>
                  <td>
                    <?php
                    $badge = 'secondary';
                    if ($it->status === 'PENDING') $badge = 'warning';
                    if ($it->status === 'MARKED') $badge = 'success';
                    if ($it->status === 'SKIPPED' || $it->status === 'NOT_ATTEMPTED') $badge = 'info';
                    if ($it->status === 'RECHECK') $badge = 'danger';
                    ?>
                    <span class="badge badge-<?php echo $badge; ?>"><?php echo html_escape((string) $it->status); ?></span>
                  </td>
                  <td><?php echo html_escape((string) ($it->marks_obtained ?? '')); ?></td>
                  <td><?php echo html_escape((string) ($it->marked_at ?? '')); ?></td>
                  <td>
                    <a class="btn btn-primary btn-xs" href="<?php echo base_url('emarker/marking/marking_screen/' . (int) $batch->id . '/' . (int) $it->id); ?>">Open</a>
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

<?php include viewPath('user/includes/footer'); ?>

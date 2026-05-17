<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">E-Marking</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('user/dashboard'); ?>">Home</a></li>
          <li class="breadcrumb-item active">E-Marking</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('user/includes/notifications'); ?>

    <style>
      .batch-meta { color:#6c757d; font-size:12px; }
      .batch-kv { display:flex; gap:6px; flex-wrap:wrap; }
      .batch-kv > div { min-width:140px; }
    </style>

    <div class="row">
      <?php if (empty($batches)): ?>
        <div class="col-12">
          <div class="alert alert-info mb-0">No assigned batches.</div>
        </div>
      <?php else: ?>
        <?php foreach ($batches as $b): ?>
          <?php
          $total = (int) ($b->total_questions ?? 0);
          $checked = (int) ($b->checked_questions ?? 0);
          $pending = (int) ($b->pending_questions ?? 0);
          $skipped = (int) ($b->skipped_questions ?? 0);
          $balance = $pending;
          $pct = $total > 0 ? round(($checked / $total) * 100, 2) : 0.0;

          $deadline_ts = !empty($b->deadline) ? strtotime((string) $b->deadline) : false;
          $now_ts = time();
          $time_left = 'â€”';
          if ($deadline_ts !== false) {
            $diff = $deadline_ts - $now_ts;
            if ($diff <= 0) {
              $time_left = 'Overdue';
            } else {
              $days = floor($diff / 86400);
              $hours = floor(($diff % 86400) / 3600);
              $mins = floor(($diff % 3600) / 60);
              $time_left = ($days > 0 ? $days . 'd ' : '') . $hours . 'h ' . $mins . 'm';
            }
          }
          ?>
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                  <h3 class="card-title mb-0">
                    Subject: <?php echo html_escape((string) $b->subject_code); ?>
                    <span class="text-muted">| <?php echo html_escape((string) $b->assessment_type); ?> | Grade <?php echo (int) $b->grade; ?></span>
                  </h3>
                  <span class="badge badge-info"><?php echo html_escape((string) $b->status); ?></span>
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                  <div class="mb-2">
                    <div class="batch-meta"><strong>Batch Code:</strong> <?php echo html_escape((string) $b->batch_code); ?></div>
                    <div class="batch-meta"><strong>Question:</strong> <?php echo html_escape((string) $b->question_no); ?></div>
                    <div class="batch-meta">
                      <strong>Allotted:</strong> <?php echo html_escape((string) $b->created_at); ?>
                      | <strong>Deadline:</strong> <?php echo html_escape((string) ($b->deadline ?: 'â€”')); ?>
                      | <strong>Time Left:</strong> <?php echo html_escape((string) $time_left); ?>
                    </div>
                  </div>
                  <div class="mb-2">
                    <a class="btn btn-primary btn-sm" href="<?php echo base_url('emarker/marking/view_batch/' . (int) $b->id); ?>">View Batch</a>
                    <?php if ($pending > 0): ?>
                      <a class="btn btn-success btn-sm" href="<?php echo base_url('emarker/marking/start/' . (int) $b->id); ?>">Start Checking</a>
                    <?php else: ?>
                      <button type="button" class="btn btn-secondary btn-sm" disabled>Start Checking</button>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="batch-kv">
                  <div><strong>Allotment:</strong> <?php echo $total; ?></div>
                  <div><strong>Checked:</strong> <?php echo $checked; ?></div>
                  <div><strong>Unchecked:</strong> <?php echo $pending; ?></div>
                  <div><strong>Skipped:</strong> <?php echo $skipped; ?></div>
                  <div><strong>Balance:</strong> <?php echo $balance; ?></div>
                  <div><strong>Progress:</strong> <?php echo number_format($pct, 2); ?>%</div>
                </div>

                <div class="progress mt-2" style="height:10px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo (float) $pct; ?>%;" aria-valuenow="<?php echo (float) $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php include viewPath('user/includes/footer'); ?>


<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Synchronize Eng CRQs</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('admin/settings'); ?>"><?php echo lang('settings') ?></a></li>
          <li class="breadcrumb-item active">Synchronize Eng CRQs</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="row">
    <div class="col-sm-3">
      <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-sm-9">
      <?php $counts = is_array($sync_counts ?? null) ? $sync_counts : ['total' => 0, 'pending' => 0, 'done' => 0]; ?>
      <?php $summary = is_array($run_summary ?? null) ? $run_summary : null; ?>
      <div class="card">
        <div class="card-header with-border">
          <h3 class="card-title">Synchronize Missing English CRQ Images</h3>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <div><strong>Source table:</strong> <code>tbl_missing_barcodes_englishcrq</code></div>
            <div><strong>Source path:</strong> <code>storagebox/crqs/{1st}/{8th}/{9th}/{12th-14th}/q1|q2/</code></div>
            <div><strong>Target path:</strong> <code>storagebox/mcrqs/{1st}/{8th}/{9th}/{12th-14th}/q1|q2/</code></div>
            <div><strong>Filename rule:</strong> source picks any random image from the matched source folder and copies it as the target row's <code>image_barcode</code>.</div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <div class="small-box bg-info">
                <div class="inner">
                  <h3><?php echo number_format((int) ($counts['total'] ?? 0)); ?></h3>
                  <p>Total Rows</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3><?php echo number_format((int) ($counts['pending'] ?? 0)); ?></h3>
                  <p>Pending</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="small-box bg-success">
                <div class="inner">
                  <h3><?php echo number_format((int) ($counts['done'] ?? 0)); ?></h3>
                  <p>Synchronized</p>
                </div>
              </div>
            </div>
          </div>

          <form method="post" action="<?php echo url('admin/settings/sync_eng_crqs'); ?>" class="mb-3">
            <div class="form-row align-items-end">
              <div class="col-md-4">
                <label for="batch_size">Batch Size</label>
                <input type="number" min="1" max="10000" step="1" class="form-control" id="batch_size" name="batch_size" value="<?php echo (int) ($batch_size ?? 100); ?>">
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Run Synchronize</button>
              </div>
            </div>
          </form>

          <?php if (!empty($summary)): ?>
            <?php if (!empty($summary['error'])): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars((string) $summary['error']); ?></div>
            <?php else: ?>
              <div class="alert alert-secondary">
                <div><strong>Processed:</strong> <?php echo number_format((int) ($summary['processed'] ?? 0)); ?></div>
                <div><strong>Copied:</strong> <?php echo number_format((int) ($summary['copied'] ?? 0)); ?></div>
                <div><strong>Failed:</strong> <?php echo number_format((int) ($summary['failed'] ?? 0)); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($summary['items'])): ?>
              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Image Barcode</th>
                      <th>Result</th>
                      <th>Message</th>
                      <th>Source Folder</th>
                      <th>Target File</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($summary['items'] as $item): ?>
                      <tr>
                        <td><?php echo (int) ($item['id'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars((string) ($item['image_barcode'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($item['result'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($item['message'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($item['source'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($item['target'] ?? '')); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include viewPath('admin/includes/footer'); ?>

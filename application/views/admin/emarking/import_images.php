<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'Import Images')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/questions'); ?>">Questions</a></li>
          <li class="breadcrumb-item active">Import</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="alert alert-info">
      <div><strong>Rules enforced:</strong></div>
      <ul class="mb-0">
        <li>Tries to match source records where <code>paper_generated = 1</code> (if source columns exist)</li>
        <li>CRQ uses <code>paper_type_code = 1</code> (booklets tables) when validating source</li>
        <li>Dictation uses <code>paper_type_code = 12</code> (English subject_code 1) and <code>13</code> (Urdu subject_code 2) when validating source</li>
        <li>Folder structure must be: <code>{base}/{grade}/{subject_code}/{version}/{page_no}/{question_no}/{barcode}_1.jpg</code></li>
        <li>If source validation fails, image is still imported with <code>source_paper_id = 0</code> (see Errors list)</li>
      </ul>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title mb-0">Import CRQ Images</h3>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo base_url('admin/emarking/import_crq_images'); ?>">
              <div class="form-group">
                <label>Base Folder</label>
                <input type="text" name="base_folder" class="form-control" value="<?php echo htmlspecialchars((string) ($default_crq_path ?? 'processed_crqs')); ?>">
              </div>
              <div class="form-group">
                <label>Upload Batch No (optional)</label>
                <input type="text" name="upload_batch_no" class="form-control" placeholder="auto if empty">
              </div>
              <button type="submit" class="btn btn-primary">Import CRQ</button>
            </form>

            <?php if (!empty($result_crq)): ?>
              <hr>
              <div><strong>Result</strong></div>
              <div>Inserted: <strong><?php echo (int) ($result_crq['inserted'] ?? 0); ?></strong></div>
              <div>Skipped: <strong><?php echo (int) ($result_crq['skipped'] ?? 0); ?></strong></div>
              <?php if (!empty($result_crq['errors'])): ?>
                <div class="mt-2"><strong>Errors</strong></div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>File</th><th>Reason</th></tr></thead>
                    <tbody>
                      <?php foreach ($result_crq['errors'] as $e): ?>
                        <tr>
                          <td style="max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars((string) ($e['file'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string) ($e['reason'] ?? '')); ?></td>
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

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title mb-0">Import Dictation Images</h3>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo base_url('admin/emarking/import_dictation_images'); ?>">
              <div class="form-group">
                <label>Base Folder</label>
                <input type="text" name="base_folder" class="form-control" value="<?php echo htmlspecialchars((string) ($default_dictation_path ?? 'processed_dictation')); ?>">
              </div>
              <div class="form-group">
                <label>Upload Batch No (optional)</label>
                <input type="text" name="upload_batch_no" class="form-control" placeholder="auto if empty">
              </div>
              <button type="submit" class="btn btn-primary">Import Dictation</button>
            </form>

            <?php if (!empty($result_dict)): ?>
              <hr>
              <div><strong>Result</strong></div>
              <div>Inserted: <strong><?php echo (int) ($result_dict['inserted'] ?? 0); ?></strong></div>
              <div>Skipped: <strong><?php echo (int) ($result_dict['skipped'] ?? 0); ?></strong></div>
              <?php if (!empty($result_dict['errors'])): ?>
                <div class="mt-2"><strong>Errors</strong></div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>File</th><th>Reason</th></tr></thead>
                    <tbody>
                      <?php foreach ($result_dict['errors'] as $e): ?>
                        <tr>
                          <td style="max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars((string) ($e['file'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string) ($e['reason'] ?? '')); ?></td>
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

  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

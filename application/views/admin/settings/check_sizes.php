<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Check Sizes</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/settings') ?>">Settings</a></li>
          <li class="breadcrumb-item active">Check Sizes</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Find images larger than a given size</h3>
          </div>
          <div class="card-body">
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger"><?php echo html_escape((string) $error); ?></div>
            <?php endif; ?>

            <form method="get" action="<?php echo url('admin/settings/check_sizes'); ?>" class="mb-3">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Directory path</label>
                  <input
                    type="text"
                    name="dir"
                    class="form-control"
                    placeholder="<?php echo html_escape(FCPATH . 'storagebox'); ?>"
                    value="<?php echo html_escape((string) ($filters['dir'] ?? '')); ?>"
                    required
                  >
                  <small class="text-muted">Allowed inside this project (or storagebox).</small>
                </div>
                <div class="form-group col-md-2">
                  <label>Min size (MB)</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="min_mb"
                    class="form-control"
                    value="<?php echo html_escape((string) ($filters['min_mb'] ?? '0')); ?>"
                  >
                </div>
                <div class="form-group col-md-2">
                  <label>Per page</label>
                  <?php $pp = (int) ($filters['per_page'] ?? 100); ?>
                  <select class="form-control" name="per_page">
                    <option value="100" <?php echo $pp === 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?php echo $pp === 200 ? 'selected' : ''; ?>>200</option>
                    <option value="500" <?php echo $pp === 500 ? 'selected' : ''; ?>>500</option>
                  </select>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary btn-block">
                    Scan
                  </button>
                </div>
              </div>
            </form>

            <?php if (!empty($scan_meta) && is_array($scan_meta)): ?>
              <div class="mb-3">
                <div><strong>Scanned:</strong> <?php echo html_escape((string) ($scan_meta['dir'] ?? '')); ?></div>
                <div><strong>Min size:</strong> <?php echo html_escape((string) ($scan_meta['min_mb'] ?? '0')); ?> MB</div>
                <div><strong>Found:</strong> <?php echo (int) ($scan_meta['results_count'] ?? 0); ?> image(s)</div>
              </div>
            <?php endif; ?>

            <?php if (!empty($items) && is_array($items)): ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                  <thead>
                    <tr>
                      <th style="width:70px;">Sr</th>
                      <th>Image</th>
                      <th style="width:140px;">Size (MB)</th>
                      <th style="width:120px;">Open</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $page = (int) ($this->input->get('page') ?? 1);
                      if ($page < 1) $page = 1;
                      $sr = (($page - 1) * (int) ($filters['per_page'] ?? 100)) + 1;
                    ?>
                    <?php foreach ($items as $row): ?>
                      <?php
                        $path = (string) ($row['path'] ?? '');
                        $url = (string) ($row['url'] ?? '');
                        $mb = ((int) ($row['size_bytes'] ?? 0)) / (1024 * 1024);
                      ?>
                      <tr>
                        <td><?php echo (int) $sr++; ?></td>
                        <td><?php echo html_escape($path); ?></td>
                        <td><?php echo number_format($mb, 2); ?></td>
                        <td>
                          <?php if ($url !== ''): ?>
                            <a href="<?php echo html_escape($url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-info">Open</a>
                          <?php else: ?>
                            <span class="text-muted">N/A</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if (!empty($pagination_links)) { ?>
                <div class="mt-3">
                  <?php echo $pagination_links; ?>
                </div>
              <?php } ?>
            <?php elseif (!empty($scan_meta)): ?>
              <div class="alert alert-info">No images found above the given size.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>


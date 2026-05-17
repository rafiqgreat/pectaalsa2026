<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">eMarker Timers</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/batches'); ?>">Batches</a></li>
          <li class="breadcrumb-item active">Timers</li>
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
          <h3 class="card-title mb-0">Timer Settings</h3>
          <a class="btn btn-secondary btn-sm" href="<?php echo base_url('admin/emarking/batches'); ?>">Back</a>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          Default is <strong>15 seconds</strong>. Set <code>0</code> to allow instant submit.
        </div>

        <form method="post" action="<?php echo base_url('admin/emarking/emarker_timers'); ?>">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Timer (seconds)</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($emarkers)): ?>
                  <tr><td colspan="4" class="text-center text-muted">No eMarkers found</td></tr>
                <?php else: ?>
                  <?php foreach ($emarkers as $u): ?>
                    <?php
                      $uid = (int) $u->id;
                      $sec = (int) (($timer_map[$uid] ?? 15));
                    ?>
                    <tr>
                      <td><?php echo $uid; ?></td>
                      <td><?php echo html_escape((string) ($u->name ?: '')); ?></td>
                      <td><?php echo html_escape((string) ($u->username ?: '')); ?></td>
                      <td style="max-width:220px;">
                        <input type="number" min="0" step="1" class="form-control" name="timers[<?php echo $uid; ?>]" value="<?php echo $sec; ?>">
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save Timers</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>


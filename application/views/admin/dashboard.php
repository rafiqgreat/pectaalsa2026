<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo lang('dashboard'); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('dashboard'); ?></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?php echo (int) ($dashboard_summary['users_total'] ?? 0); ?></h3>
            <p>Total Users</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3><?php echo (int) ($dashboard_summary['active_users'] ?? 0); ?></h3>
            <p>Active Users</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3><?php echo (int) ($dashboard_summary['roles_total'] ?? 0); ?></h3>
            <p>Roles</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3><?php echo (int) ($dashboard_summary['schools_total'] ?? 0); ?></h3>
            <p>Schools</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Fresh Start Summary</h3>
      </div>
      <div class="card-body">
        <p class="mb-0">Legacy application, challan, scrutiny, tracking, diary, merit, and report modules have been detached from this dashboard. Use this space for the new functionality you plan to build.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Users</h3>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($dashboard_summary['recent_users'])): ?>
              <?php foreach ($dashboard_summary['recent_users'] as $user): ?>
                <tr>
                  <td><?php echo (int) $user['id']; ?></td>
                  <td><?php echo htmlspecialchars((string) $user['name']); ?></td>
                  <td><?php echo htmlspecialchars((string) $user['username']); ?></td>
                  <td><?php echo htmlspecialchars((string) $user['email']); ?></td>
                  <td><?php echo htmlspecialchars((string) ($user['role_title'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars((string) $user['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6">No users found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

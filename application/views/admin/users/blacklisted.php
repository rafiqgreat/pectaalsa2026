<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Blacklisted Users</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item active">Blacklisted Users</li>
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
            <h3 class="card-title">Blacklisted Users</h3>
          </div>
          <div class="card-body">
            <table id="example1" class="table table-bordered table-hover table-striped">
              <thead>
                <tr>
                  <th>SR</th>
                  <th>Name</th>
                  <th>CNIC</th>
                  <th>Application ID</th>
                  <th>Blacklist Type / Reason</th>
                  <th>Application Type</th>
                  <th>District</th>
                  <th>Tehsil</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($blacklisted_users)): ?>
                  <?php $sr = 1; ?>
                  <?php foreach ($blacklisted_users as $row): ?>
                    <?php $action_url = url('admin/users/view/' . $row['user_id']); ?>
                    <tr>
                      <td><?php echo $sr++; ?></td>
                      <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['cnic']); ?></td>
                      <td><?php echo htmlspecialchars((string)$row['application_id']); ?></td>
                      <td><?php echo htmlspecialchars((string)$row['blacklist_reason']); ?></td>
                      <td><?php echo htmlspecialchars($row['application_type']); ?></td>
                      <td><?php echo htmlspecialchars((string)$row['district']); ?></td>
                      <td><?php echo htmlspecialchars((string)$row['tehsil']); ?></td>
                      <td><?php echo htmlspecialchars((string)$row['app_status']); ?></td>
                      <td>
                        <a href="<?php echo $action_url; ?>" class="btn btn-sm btn-info" title="View User" data-toggle="tooltip">
                          <i class="fa fa-eye"></i> View User
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="10">No records found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

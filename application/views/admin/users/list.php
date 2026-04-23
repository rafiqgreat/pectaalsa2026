<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>


<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('users') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('users') ?></li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3"><?php echo lang('users') ?></h3>
            <div class="ml-auto p-2">
              <?php if (hasPermissions('users_add')): ?>
                <a href="<?php echo url('admin/users/add') ?>" class="btn btn-primary btn-sm"><span class="pr-1"><i class="fa fa-plus"></i></span> <?php echo lang('new_user') ?></a>
              <?php endif ?>
            </div>
          </div>

          <!-- /.card-header -->
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-3 col-sm-6 mb-2">
                <input type="text" id="filter-name" class="form-control form-control-sm" placeholder="Name" value="<?php echo htmlspecialchars((string)($filters['name'] ?? '')); ?>">
              </div>
              <div class="col-md-3 col-sm-6 mb-2">
                <input type="text" id="filter-username" class="form-control form-control-sm" placeholder="Username/CNIC" value="<?php echo htmlspecialchars((string)($filters['username'] ?? '')); ?>">
              </div>
              <div class="col-md-3 col-sm-6 mb-2">
                <input type="text" id="filter-email" class="form-control form-control-sm" placeholder="Email" value="<?php echo htmlspecialchars((string)($filters['email'] ?? '')); ?>">
              </div>
              <div class="col-md-3 col-sm-6 mb-2">
                <select id="filter-role" class="form-control form-control-sm">
                  <option value="">All Roles</option>
                  <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?php echo (int)$role->id; ?>" <?php echo ((int)($filters['role_id'] ?? 0) === (int)$role->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$role->title); ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <button type="button" id="filter-search" class="btn btn-sm btn-primary btn-block">Search</button>
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <button type="button" id="filter-reset" class="btn btn-sm btn-outline-secondary btn-block">Reset</button>
              </div>
            </div>
            <table id="example1" class="table table-bordered table-hover table-striped">
              <thead>
                <tr>
                  <th><?php echo lang('id') ?></th>
                  <th><?php echo lang('user_image') ?></th>
                  <th><?php echo lang('user_name') ?></th>
                  <th>Username/CNIC</th>
                  <th><?php echo lang('user_email') ?></th>
                  <th><?php echo lang('user_role') ?></th>
                  <th><?php echo lang('user_last_login') ?></th>
                  <th><?php echo lang('user_status') ?></th>
                  <th><?php echo lang('action') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $row): ?>
                  <tr>
                    <td width="60"><?php echo $row->id ?></td>
                    <td width="50" class="text-center">
                      <img src="<?php echo userProfile($row->id) ?>" width="40" height="40" alt="" class="img-avtar">

                    </td>
                    <td>
                      <?php echo $row->name ?>
                    </td>
                    <td><?php echo $row->username ?></td>
                    <td><?php echo $row->email ?></td>
                    <td><?php echo htmlspecialchars((string)$row->role_title); ?></td>
                    <td><?php echo ($row->last_login != '0000-00-00 00:00:00') ? date(setting('date_format'), strtotime($row->last_login)) : 'No Record' ?></td>
                    <td>
                      <?php if (logged('id') !== $row->id): ?>
                        <input type="checkbox" name="my-checkbox" onchange="updateUserStatus('<?php echo $row->id ?>', $(this).is(':checked') )" <?php echo ($row->status) ? 'checked' : '' ?> data-bootstrap-switch data-off-color="secondary" data-on-color="success" data-off-text="<?php echo lang('user_inactive') ?>" data-on-text="<?php echo lang('user_active') ?>">
                      <?php else: ?>
                        <input type="checkbox" name="my-checkbox" disabled data-bootstrap-switch data-off-color="secondary" data-on-color="success" data-off-text="<?php echo lang('user_inactive') ?>" data-on-text="<?php echo lang('user_active') ?>">
                      <?php endif ?>
                    </td>
                    <td>
                      <?php $is_role_15 = ((int) logged('role') === 15); ?>
                      <?php if (hasPermissions('users_edit') || $is_role_15): ?>
                        <a href="<?php echo url('admin/users/edit/' . $row->id) ?>" class="btn btn-sm btn-primary" title="<?php echo lang('edit_user') ?>" data-toggle="tooltip"><i class="fas fa-edit"></i></a>
                      <?php endif ?>
                      <?php if (hasPermissions('users_view') || $is_role_15): ?>
                        <a href="<?php echo url('admin/users/view/' . $row->id) ?>" class="btn btn-sm btn-info" title="<?php echo lang('view_user') ?>" data-toggle="tooltip"><i class="fa fa-eye"></i></a>
                      <?php endif ?>
                      <?php if (hasPermissions('users_delete') || $is_role_15): ?>
                        <?php if ($row->id != 1 && logged('id') != $row->id): ?>
                          <a href="#" class="btn btn-sm btn-warning" onclick="return archiveDeleteUser('<?php echo $row->id ?>')" title="Delete Archive" data-toggle="tooltip">Delete Archive</a>
                          <?php if (!$is_role_15): ?>
                            <a href="<?php echo url('admin/users/delete/' . $row->id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Do you really want to delete this user ?')" title="<?php echo lang('delete_user') ?>" data-toggle="tooltip"><i class="fa fa-trash"></i></a>
                          <?php endif ?>
                        <?php else: ?>
                          <a href="#" class="btn btn-sm btn-warning" title="Delete Archive" data-toggle="tooltip" disabled>Delete Archive</a>
                          <?php if (!$is_role_15): ?>
                            <a href="#" class="btn btn-sm btn-danger" title="<?php echo lang('delete_user_cannot') ?>" data-toggle="tooltip" disabled><i class="fa fa-trash"></i></a>
                          <?php endif ?>
                        <?php endif ?>
                      <?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <?php if (!empty($pagination_links)) { ?>
          <div class="mt-3">
            <?php echo $pagination_links; ?>
          </div>
        <?php } ?>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</section>
<!-- /.content -->



<?php include viewPath('admin/includes/footer'); ?>

<script>
  $(function() {
    function buildFilterUrl(reset) {
      var baseUrl = '<?php echo url('admin/users'); ?>';
      if (reset) {
        return baseUrl;
      }
      var params = [];
      var name = $('#filter-name').val() || '';
      var username = $('#filter-username').val() || '';
      var email = $('#filter-email').val() || '';
      var roleId = $('#filter-role').val() || '';
      if (name) params.push('name=' + encodeURIComponent(name));
      if (username) params.push('username=' + encodeURIComponent(username));
      if (email) params.push('email=' + encodeURIComponent(email));
      if (roleId) params.push('role_id=' + encodeURIComponent(roleId));
      return params.length ? (baseUrl + '?' + params.join('&')) : baseUrl;
    }

    $('#filter-search').on('click', function() {
      window.location.href = buildFilterUrl(false);
    });

    $('#filter-reset').on('click', function() {
      window.location.href = buildFilterUrl(true);
    });
  });

  window.updateUserStatus = (id, status) => {
    $.get('<?php echo url('admin/users/change_status') ?>/' + id, {
      status: status
    }, (data, status) => {
      if (data == 'done') {
        // code
      } else {
        alert('<?php echo lang('user_unable_change_status') ?>');
      }
    })
  }

  window.archiveDeleteUser = (id) => {
    const reason = prompt('Enter delete reason (required):');
    if (reason === null) {
      return false;
    }
    const trimmed = reason.trim();
    if (!trimmed) {
      alert('Delete reason is required.');
      return false;
    }
    return confirm('Archive delete this user and all related data?') &&
      (window.location.href = `<?php echo url('admin/users/archive_delete/') ?>${id}?reason=${encodeURIComponent(trimmed)}`);
  }
</script>

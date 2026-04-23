<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo lang('dashboard'); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('dashboard'); ?></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Account Overview</h3>
      </div>
      <div class="card-body">
        <p class="mb-3">Legacy application workflows have been removed from this account area. This dashboard is now a clean base for the next phase of development.</p>
        <table class="table table-bordered mb-0">
          <tr>
            <th style="width:220px;">Name</th>
            <td><?php echo htmlspecialchars((string) ($user->name ?? '')); ?></td>
          </tr>
          <tr>
            <th>Username</th>
            <td><?php echo htmlspecialchars((string) ($user->username ?? '')); ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars((string) ($user->email ?? '')); ?></td>
          </tr>
          <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars((string) ($user->phone ?? '')); ?></td>
          </tr>
          <tr>
            <th>Registered</th>
            <td><?php echo htmlspecialchars((string) ($user->created_at ?? '')); ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('user/includes/footer'); ?>

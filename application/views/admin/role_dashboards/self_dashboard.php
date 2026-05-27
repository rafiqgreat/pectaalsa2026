<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo html_escape($page->title ?? 'Dashboard'); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">

		<div class="row">
			<div class="col-md-7">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">My Profile</h3>
					</div>
					<div class="card-body">
						<table class="table table-bordered mb-0">
							<tr><th style="width:220px;">Name</th><td><?php echo html_escape((string) ($user->name ?? '')); ?></td></tr>
							<tr><th>Email</th><td><?php echo html_escape((string) ($user->email ?? '')); ?></td></tr>
							<tr><th>Username</th><td><?php echo html_escape((string) ($user->username ?? '')); ?></td></tr>
							<tr><th>Phone</th><td><?php echo html_escape((string) ($user->phone ?? '')); ?></td></tr>
							<tr><th>Role</th><td><?php echo html_escape((string) (($user->role->title ?? '') ?: '')); ?></td></tr>
							<?php if (!empty($subjects) && is_array($subjects)): ?>
								<tr><th>Subjects</th><td><?php echo html_escape(implode(', ', $subjects)); ?></td></tr>
							<?php endif; ?>
						</table>
						<div class="mt-3">
							<a class="btn btn-outline-primary" href="<?php echo url('admin/profile/index/edit'); ?>">Edit Profile</a>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-5">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Reset Password</h3>
					</div>
					<div class="card-body">
						<?php echo form_open('admin/profile/updatePassword', ['method' => 'POST', 'autocomplete' => 'off']); ?>
							<div class="form-group">
								<label>Old Password</label>
								<input type="password" class="form-control" name="old_password" required>
							</div>
							<div class="form-group">
								<label>New Password</label>
								<input type="password" class="form-control" name="password" minlength="6" required>
							</div>
							<div class="form-group">
								<label>Confirm Password</label>
								<input type="password" class="form-control" name="password_confirm" minlength="6" required>
							</div>
							<button type="submit" class="btn btn-success">Update Password</button>
						<?php echo form_close(); ?>
						<div class="text-muted mt-2" style="font-size:12px;">After changing password you will be asked to login again.</div>
					</div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">E-Markers (Govt Sector) - Subject Summary</h3>
			</div>
			<div class="card-body table-responsive p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead>
						<tr>
							<th style="width:60px;">Sr</th>
							<th>Subject</th>
							<th style="width:140px;">No of eMarkers</th>
							<th style="width:160px;">Accepted eMarkers</th>
							<th style="width:160px;">Rejected eMarkers</th>
							<th style="width:160px;">Pending eMarkers</th>
						</tr>
					</thead>
					<tbody>
						<?php $stats = isset($emarker_subject_stats) && is_array($emarker_subject_stats) ? $emarker_subject_stats : []; ?>
						<?php if (!empty($stats)): ?>
							<?php $sr = 1; foreach ($stats as $r): ?>
								<tr>
									<td><?php echo (int) $sr++; ?></td>
									<td><?php echo html_escape((string) ($r['subject'] ?? '')); ?></td>
									<td><?php echo (int) ($r['total'] ?? 0); ?></td>
									<td><?php echo (int) ($r['accepted'] ?? 0); ?></td>
									<td><?php echo (int) ($r['rejected'] ?? 0); ?></td>
									<td><?php echo (int) ($r['pending'] ?? 0); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="6" class="text-center text-muted py-3">No records found.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>


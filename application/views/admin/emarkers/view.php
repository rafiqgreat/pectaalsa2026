<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<?php include viewPath('admin/includes/header'); ?>
<?php
	$u = isset($user_row) && is_object($user_row) ? $user_row : null;
	$security = isset($security) && is_object($security) ? $security : null;
	$steps = isset($steps_row) && is_object($steps_row) ? $steps_row : null;
	$img = (!empty($u->profile_picture)) ? base_url($u->profile_picture) : base_url('assets/img/avatar5.png');
	$is_active = !empty($u) && (int) ($u->status ?? 0) === 1;
	$is_completed = !empty($steps) && (int) ($steps->registration_completed ?? 0) === 1;
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>E-Marker Profile</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers'); ?>">E-Markers</a></li>
					<li class="breadcrumb-item active">View</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php if (!empty($this->session->flashdata('alert'))): ?>
			<div class="alert alert-<?php echo $this->session->flashdata('alert-type'); ?>">
				<?php echo $this->session->flashdata('alert'); ?>
			</div>
		<?php endif; ?>

		<div class="card">
			<div class="card-header d-flex align-items-center">
				<h3 class="card-title mb-0">Evaluator Detail</h3>
				<div class="ml-auto">
					<?php if (!$is_active): ?>
						<?php echo form_open('admin/emarkers/approve/' . (int) $u->id, ['method' => 'POST', 'style' => 'display:inline']); ?>
							<button type="submit" class="btn btn-success btn-sm js-approve" <?php echo !$is_completed ? 'disabled' : ''; ?>>
								<i class="fas fa-check"></i> Approve & Activate
							</button>
						<?php echo form_close(); ?>
					<?php else: ?>
						<span class="badge badge-success p-2">Active</span>
					<?php endif; ?>
				</div>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-4">
						<img src="<?php echo $img; ?>" alt="Profile" style="width:150px;height:180px;object-fit:cover;border:1px solid #e5e7eb;">
					</div>
					<div class="col-md-8">
						<div class="row">
							<div class="col-md-6">
								<p><strong>Name:</strong> <?php echo htmlspecialchars((string) ($u->name ?? '')); ?></p>
								<p><strong>Father Name:</strong> <?php echo htmlspecialchars((string) ($u->father_name ?? '')); ?></p>
								<p><strong>Email:</strong> <?php echo htmlspecialchars((string) ($u->email ?? '')); ?></p>
								<p><strong>Phone:</strong> <?php echo htmlspecialchars((string) ($u->phone ?? '')); ?></p>
							</div>
							<div class="col-md-6">
								<p><strong>CNIC:</strong> <?php echo htmlspecialchars((string) ($u->cnic ?? '')); ?></p>
								<p><strong>Blood Group:</strong> <?php echo htmlspecialchars((string) ($u->blood_group ?? '')); ?></p>
								<p><strong>Gender:</strong> <?php echo htmlspecialchars((string) ($u->gender ?? '')); ?></p>
								<p><strong>DOB:</strong> <?php echo !empty($u->dob) ? date('M d, Y', strtotime($u->dob)) : ''; ?></p>
							</div>
						</div>
						<hr>
						<p>
							<strong>Registration:</strong>
							<?php if ($is_completed): ?>
								<span class="badge badge-success">Completed</span>
							<?php else: ?>
								<span class="badge badge-secondary">Incomplete</span>
							<?php endif; ?>
						</p>
						<p>
							<strong>Security Document:</strong>
							<?php if (!empty($security) && !empty($security->document_file)): ?>
								<a href="<?php echo base_url($security->document_file); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="far fa-eye"></i> View</a>
							<?php else: ?>
								<span class="text-muted">Missing</span>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header"><h3 class="card-title mb-0">Uploaded Documents</h3></div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<h6 class="font-weight-bold">Education Documents</h6>
						<?php if (!empty($educations)): ?>
							<ul class="mb-0">
								<?php foreach ($educations as $e): ?>
									<li>
										<?php echo htmlspecialchars((string) $e->degree); ?> -
										<?php if (!empty($e->transcript_file)): ?>
											<a href="<?php echo base_url($e->transcript_file); ?>" target="_blank">View</a>
										<?php else: ?>
											<span class="text-muted">Missing</span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<p class="text-muted mb-0">No education records.</p>
						<?php endif; ?>
					</div>
					<div class="col-md-6">
						<h6 class="font-weight-bold">Experience Documents</h6>
						<?php if (!empty($experiences)): ?>
							<ul class="mb-0">
								<?php foreach ($experiences as $x): ?>
									<li>
										<?php echo htmlspecialchars((string) $x->department); ?> -
										<?php if (!empty($x->document_file)): ?>
											<a href="<?php echo base_url($x->document_file); ?>" target="_blank">View</a>
										<?php else: ?>
											<span class="text-muted">Missing</span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<p class="text-muted mb-0">No experience records.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
	$(function () {
		$('.js-approve').on('click', function (e) {
			if (!confirm('Approve and activate this E-Marker account?')) {
				e.preventDefault();
				return false;
			}
		});
	});
</script>


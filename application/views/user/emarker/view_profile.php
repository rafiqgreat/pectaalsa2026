<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>
<?php
	$u = isset($user_row) && is_object($user_row) ? $user_row : null;
	$address = isset($address) && is_object($address) ? $address : null;
	$bank = isset($bank) && is_object($bank) ? $bank : null;
	$spec = isset($specialization) && is_object($specialization) ? $specialization : null;
	$security = isset($security) && is_object($security) ? $security : null;
	$steps = isset($steps_row) && is_object($steps_row) ? $steps_row : null;

	$active = !empty($u) && (int) ($u->status ?? 0) === 1;
	$doc_verified = $active;
	$status_label = (!empty($steps) && (int) ($steps->registration_completed ?? 0) === 1) ? 'Eligible' : 'Incomplete';
	$img = (!empty($u->profile_picture)) ? base_url($u->profile_picture) : base_url('assets/img/avatar5.png');
?>

<style>
	.profile-tabs .nav-link { color:#111827; font-weight:600; padding:14px 18px; border:0; border-bottom:2px solid transparent; }
	.profile-tabs .nav-link.active { border-bottom-color:#111827; background:transparent; }
	.profile-card { border:1px solid rgba(37,99,235,0.35); border-radius:6px; }
	.detail-label { font-weight:700; }
	.switch {
		position: relative; display: inline-block; width: 52px; height: 28px; vertical-align: middle;
	}
	.switch input { opacity: 0; width: 0; height: 0; }
	.slider {
		position: absolute; cursor: default; top: 0; left: 0; right: 0; bottom: 0;
		background-color: #d1d5db; transition: .2s; border-radius: 999px;
	}
	.slider:before {
		position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px;
		background-color: white; transition: .2s; border-radius: 999px;
	}
	input:checked + .slider { background-color: #22c55e; }
	input:checked + .slider:before { transform: translateX(24px); }
</style>

<section class="content-header">
	<div class="container-fluid">
		<div class="d-flex justify-content-between align-items-center">
			<ol class="breadcrumb mb-0">
				<li class="breadcrumb-item"><a href="<?php echo url('/user/'); ?>"><?php echo lang('home'); ?></a></li>
				<li class="breadcrumb-item">Evaluator Profile</li>
				<li class="breadcrumb-item active"><strong>View Profile</strong></li>
			</ol>
			<div>
				<a href="<?php echo url('user/profile'); ?>" class="btn btn-outline-primary mr-2">Edit Profile</a>
				<a href="<?php echo url('user/profile/index/change_password'); ?>" class="btn btn-primary">Change Password</a>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
			<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab_detail" role="tab">Evaluator Detail</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_address" role="tab">Address Details</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_edu" role="tab">Educational Details</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_exp" role="tab">Experience Detail</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_bank" role="tab">Bank Detail</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_spec" role="tab">Area Of Specialization</a></li>
			<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_em" role="tab">E-marking Experience</a></li>
		</ul>

		<div class="tab-content pt-3">
			<div class="tab-pane fade show active" id="tab_detail" role="tabpanel">
				<div class="card profile-card">
					<div class="card-body">
						<div class="row">
							<div class="col-md-4">
								<div class="mb-2"><span class="detail-label">Name:</span> <?php echo html_escape((string) ($u->name ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Phone:</span> <?php echo html_escape((string) ($u->phone ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Email:</span> <?php echo html_escape((string) ($u->email ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Status:</span> <?php echo html_escape($status_label); ?></div>
								<div class="mb-2 d-flex align-items-center">
									<span class="detail-label mr-2">Active Status:</span>
									<label class="switch mb-0">
										<input type="checkbox" <?php echo $active ? 'checked' : ''; ?> disabled>
										<span class="slider"></span>
									</label>
									<span class="ml-2" style="color:#16a34a;font-weight:600;"><?php echo $active ? 'Active' : 'Pending'; ?></span>
								</div>
							</div>

							<div class="col-md-4 text-center">
								<img src="<?php echo $img; ?>" alt="Profile" style="width:120px;height:140px;object-fit:cover;border-radius:2px;border:0;">
							</div>

							<div class="col-md-4">
								<div class="mb-2"><span class="detail-label">Father Name:</span> <?php echo html_escape((string) ($u->father_name ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Date of Birth:</span> <?php echo !empty($u->dob) ? date('M d, Y', strtotime($u->dob)) : ''; ?></div>
								<div class="mb-2"><span class="detail-label">Doc Status:</span> <?php echo $doc_verified ? 'Verified' : 'Pending'; ?></div>
								<div class="mb-2">
									<span class="detail-label">Security Document:</span>
									<?php if (!empty($security) && !empty($security->document_file)): ?>
										<a href="<?php echo base_url($security->document_file); ?>" target="_blank" title="View Document"><i class="far fa-eye"></i></a>
									<?php else: ?>
										<span class="text-muted">-</span>
									<?php endif; ?>
								</div>
								<hr>
								<div class="mb-2"><span class="detail-label">CNIC:</span> <?php echo html_escape((string) ($u->cnic ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Blood Group:</span> <?php echo html_escape((string) ($u->blood_group ?? '')); ?></div>
								<div class="mb-2"><span class="detail-label">Gender:</span> <?php echo html_escape((string) ($u->gender ?? '')); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_address" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($address)): ?>
							<table class="table table-bordered mb-0">
								<tr><th style="width:240px;">Address</th><td><?php echo nl2br(html_escape((string) $address->address)); ?></td></tr>
								<tr><th>District</th><td><?php echo html_escape((string) $address->district); ?></td></tr>
								<tr><th>City</th><td><?php echo html_escape((string) $address->city); ?></td></tr>
								<tr><th>Province</th><td><?php echo html_escape((string) $address->province); ?></td></tr>
								<tr><th>Country</th><td><?php echo html_escape((string) $address->country); ?></td></tr>
							</table>
						<?php else: ?>
							<p class="text-muted mb-0">No address details found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_edu" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($educations)): ?>
							<div class="table-responsive">
								<table class="table table-bordered mb-0">
									<thead>
										<tr>
											<th>Degree</th>
											<th>Institute/University</th>
											<th>Passing Year</th>
											<th>CGPA/Percentage</th>
											<th>Document</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($educations as $e): ?>
											<tr>
												<td><?php echo html_escape((string) $e->degree); ?></td>
												<td><?php echo html_escape((string) $e->institute); ?></td>
												<td><?php echo html_escape((string) $e->passing_year); ?></td>
												<td><?php echo html_escape((string) $e->cgpa_percentage); ?></td>
												<td>
													<?php if (!empty($e->transcript_file)): ?>
														<a href="<?php echo base_url($e->transcript_file); ?>" target="_blank"><i class="far fa-eye"></i></a>
													<?php else: ?>
														-
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<p class="text-muted mb-0">No educational details found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_exp" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($steps) && !empty($steps->no_experience)): ?>
							<p class="mb-0 text-muted">No experience.</p>
						<?php elseif (!empty($experiences)): ?>
							<div class="table-responsive">
								<table class="table table-bordered mb-0">
									<thead>
										<tr>
											<th>Department</th>
											<th>Sector</th>
											<th>Experience Type</th>
											<th>Job Type</th>
											<th>Start</th>
											<th>End</th>
											<th>Currently Working</th>
											<th>Teaching Level</th>
											<th>BPS</th>
											<th>Document</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($experiences as $x): ?>
											<tr>
												<td><?php echo html_escape((string) $x->department); ?></td>
												<td><?php echo html_escape((string) $x->sector); ?></td>
												<td><?php echo html_escape((string) $x->experience_type); ?></td>
												<td><?php echo html_escape((string) $x->job_type); ?></td>
												<td><?php echo html_escape((string) $x->start_date); ?></td>
												<td><?php echo html_escape((string) ($x->end_date ?? '-')); ?></td>
												<td><?php echo !empty($x->currently_working) ? 'Yes' : 'No'; ?></td>
												<td><?php echo html_escape((string) ($x->teaching_level ?? '-')); ?></td>
												<td><?php echo html_escape((string) ($x->bps ?? '-')); ?></td>
												<td>
													<?php if (!empty($x->document_file)): ?>
														<a href="<?php echo base_url($x->document_file); ?>" target="_blank"><i class="far fa-eye"></i></a>
													<?php else: ?>
														-
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<p class="text-muted mb-0">No experience details found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_bank" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($bank)): ?>
							<table class="table table-bordered mb-0">
								<tr><th style="width:240px;">Bank Name</th><td><?php echo html_escape((string) $bank->bank_name); ?></td></tr>
								<tr><th>Branch Name</th><td><?php echo html_escape((string) $bank->branch_name); ?></td></tr>
								<tr><th>Branch Code</th><td><?php echo html_escape((string) $bank->branch_code); ?></td></tr>
								<tr><th>Account Title</th><td><?php echo html_escape((string) $bank->account_title); ?></td></tr>
								<tr><th>Account/IBAN</th><td><?php echo html_escape((string) $bank->iban_account_no); ?></td></tr>
								<tr><th>International User</th><td><?php echo !empty($bank->international_user) ? 'Yes' : 'No'; ?></td></tr>
							</table>
						<?php else: ?>
							<p class="text-muted mb-0">No bank details found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_spec" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($spec)): ?>
							<div><span class="detail-label">Area of Specialization:</span> <?php echo html_escape((string) $spec->specialization); ?></div>
						<?php else: ?>
							<p class="text-muted mb-0">No specialization found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_em" role="tabpanel">
				<div class="card">
					<div class="card-body">
						<?php if (!empty($steps) && !empty($steps->no_emarking_experience)): ?>
							<p class="mb-0 text-muted">No e-marking experience.</p>
						<?php elseif (!empty($emarking)): ?>
							<div class="table-responsive">
								<table class="table table-bordered mb-0">
									<thead>
										<tr>
											<th>Department</th>
											<th>From</th>
											<th>To</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($emarking as $m): ?>
											<tr>
												<td><?php echo html_escape((string) $m->department); ?></td>
												<td><?php echo html_escape((string) $m->from_date); ?></td>
												<td><?php echo html_escape((string) $m->to_date); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<p class="text-muted mb-0">No e-marking experience found.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('user/includes/footer'); ?>


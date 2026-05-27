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
	$review_status = !empty($steps) && !empty($steps->review_status) ? (string) $steps->review_status : ($is_active ? 'approved' : 'pending');
	$doc_verified = $is_active ? 'Verified' : 'Not-Verified';
	$eligible = $is_completed ? 'Eligible' : 'Not-Eligible';
	$sis_status = ((int) ($u->sis_verified ?? 0) === 1) ? 'SIS Verified' : 'Not Verified';
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers'); ?>">Evaluator</a></li>
					<li class="breadcrumb-item active">
						<?php echo ($review_status === 'approved') ? 'Approved Profiles' : (($review_status === 'rejected') ? 'Rejected Profiles' : 'Pending Request'); ?>
					</li>
					<li class="breadcrumb-item active"><?php echo (int) ($u->id ?? 0); ?></li>
				</ol>
			</div>
			<div class="col-sm-6">
				<div class="float-sm-right" style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
					<?php $role = (int) logged('role'); ?>
					<?php if (in_array($role, [1, 18], true) && $review_status === 'pending'): ?>
						<button type="button" class="btn btn-outline-primary js-seek-btn">Seek Information</button>
						<button type="button" class="btn btn-danger js-reject-btn">Reject Request</button>
						<?php echo form_open('admin/emarkers/approve/' . (int) $u->id, ['method' => 'POST', 'style' => 'display:inline']); ?>
							<button type="submit" class="btn btn-success js-approve" <?php echo !$is_completed ? 'disabled' : ''; ?>>Approve Request</button>
						<?php echo form_close(); ?>
					<?php elseif ($role === 1): ?>
						<a href="<?php echo url('admin/emarkers/edit/' . (int) $u->id . '/1'); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">Edit Profile</a>
						<a href="<?php echo url('admin/emarkers/change_password/' . (int) $u->id); ?>" class="btn btn-primary">Change Password</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="row mb-2">
			<div class="col-sm-12">
				<a href="<?php echo url('admin/emarkers/' . (($review_status === 'approved') ? 'approved' : (($review_status === 'rejected') ? 'rejected' : 'pending'))); ?>" style="font-weight:600;">Back to List</a>
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

		<style>
			.profile-card { border:1px solid #d6defa; border-radius:10px; }
			.profile-label strong { font-weight:700; }
			.doc-missing { color:#e11d48; font-weight:600; }
			.section-title { font-size:28px; font-weight:700; margin:26px 0 10px; }
			.section-card { border:1px solid #d6defa; border-radius:10px; padding:18px 22px; background:#fff; }
			.table-clean th { border-top:0; color:#111827; font-weight:700; }
			.table-clean td { color:#6b7280; vertical-align:middle; }
			.eye-link { color:#1e5aa8; font-size:18px; }
		</style>

		<div class="section-card profile-card">
			<div class="text-center mb-3">
				<img src="<?php echo $img; ?>" alt="Profile Photo" style="width:120px;height:150px;object-fit:cover;border:1px solid #e5e7eb;">
				<div style="font-weight:700;margin-top:6px;">Profile Photo</div>
			</div>

			<div class="row">
				<div class="col-md-4 profile-label">
					<p><strong>Name:</strong> <?php echo htmlspecialchars((string) ($u->name ?? '')); ?></p>
					<p><strong>Phone:</strong> <?php echo htmlspecialchars((string) ($u->phone ?? '')); ?></p>
					<p><strong>Email:</strong> <?php echo htmlspecialchars((string) ($u->email ?? '')); ?></p>
					<p><strong>Status:</strong> <?php echo $eligible; ?></p>
					<p class="d-flex align-items-center" style="gap:10px;">
						<strong style="margin:0;">Active Status:</strong>
						<input type="checkbox"
							class="js-status-switch"
							data-user-id="<?php echo (int) ($u->id ?? 0); ?>"
							<?php echo $is_active ? 'checked' : ''; ?>
							<?php echo ((int) logged('role') === 18) ? 'disabled' : ''; ?>
							data-bootstrap-switch
							data-off-color="secondary"
							data-on-color="success"
							data-off-text="Inactive"
							data-on-text="Active"
						>
					</p>
				</div>
				<div class="col-md-4 profile-label">
					<p><strong>Father Name:</strong> <?php echo htmlspecialchars((string) ($u->father_name ?? '')); ?></p>
					<p><strong>Date of Birth:</strong> <?php echo !empty($u->dob) ? date('M j, Y', strtotime($u->dob)) : ''; ?></p>
					<p><strong>Doc Status:</strong> <?php echo $doc_verified; ?></p>
					<p>
						<strong>Security Document:</strong>
						<?php if (!empty($security) && !empty($security->document_file)): ?>
							<a href="javascript:void(0)" class="js-doc-view eye-link" data-title="Security Document" data-url="<?php echo base_url($security->document_file); ?>"><i class="far fa-eye"></i></a>
						<?php else: ?>
							<span class="doc-missing">Not attached</span>
						<?php endif; ?>
					</p>
					<p>
						<strong>Integrity Affidavit:</strong>
						<?php if (!empty($security) && !empty($security->integrity_affidavit_file)): ?>
							<a href="javascript:void(0)" class="js-doc-view eye-link" data-title="Integrity Affidavit" data-url="<?php echo base_url($security->integrity_affidavit_file); ?>"><i class="far fa-eye"></i></a>
						<?php else: ?>
							<span class="doc-missing">Not attached</span>
						<?php endif; ?>
					</p>
				</div>
				<div class="col-md-4 profile-label">
					<p><strong>CNIC:</strong> <?php echo htmlspecialchars((string) ($u->cnic ?? '')); ?></p>
					<p>
						<strong>SIS:</strong>
						<?php if ((int) ($u->sis_verified ?? 0) === 1): ?>
							<span class="text-success font-weight-bold">
								<i class="fas fa-check-circle"></i> SIS Verified
							</span>
						<?php else: ?>
							<span class="text-danger font-weight-bold">
								<i class="fas fa-times-circle"></i> Not Verified
							</span>
						<?php endif; ?>
					</p>
					<p><strong>Gender:</strong> <?php echo htmlspecialchars((string) ($u->gender ?? '')); ?></p>

					<?php if (!$is_active): ?>
						<div style="margin-top:14px;">
							<?php if ($review_status !== 'pending'): ?>
								<?php echo form_open('admin/emarkers/approve/' . (int) $u->id, ['method' => 'POST', 'style' => 'display:inline']); ?>
									<button type="submit" class="btn btn-success js-approve" <?php echo !$is_completed ? 'disabled' : ''; ?>>
										<i class="fas fa-check"></i> Approve & Activate
									</button>
								<?php echo form_close(); ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($review_status === 'rejected' && !empty($steps->rejection_reason)): ?>
				<div class="mt-3">
					<strong>Rejection Reason:</strong> <span class="doc-missing"><?php echo htmlspecialchars((string) $steps->rejection_reason); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<?php $a = isset($address) && is_object($address) ? $address : null; ?>
		<div class="section-title">Address Details:</div>
		<div class="section-card">
			<div class="row table-clean">
				<div class="col-md-6">
					<div style="font-weight:700;">Address</div>
					<div><?php echo htmlspecialchars((string) ($a->address ?? '')); ?></div>
				</div>
				<div class="col-md-2">
					<div style="font-weight:700;">District</div>
					<div><?php echo htmlspecialchars((string) ($a->district ?? '')); ?></div>
				</div>
				<div class="col-md-1">
					<div style="font-weight:700;">City</div>
					<div><?php echo htmlspecialchars((string) ($a->city ?? '')); ?></div>
				</div>
				<div class="col-md-2">
					<div style="font-weight:700;">Province</div>
					<div><?php echo htmlspecialchars((string) ($a->province ?? '')); ?></div>
				</div>
				<div class="col-md-1">
					<div style="font-weight:700;">Country</div>
					<div><?php echo htmlspecialchars((string) ($a->country ?? '')); ?></div>
				</div>
			</div>
		</div>

		<div class="section-title">Educational Details:</div>
		<div class="section-card p-0">
			<table class="table mb-0 table-clean">
				<thead>
					<tr>
						<th>Degree</th>
						<th>Institute</th>
						<th>Passing Year</th>
						<th>CGPA/Percentage</th>
						<th style="width:140px;">View Document</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($educations)): ?>
						<?php foreach ($educations as $e): ?>
							<tr>
								<td><?php echo htmlspecialchars((string) ($e->degree ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($e->institute ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($e->passing_year ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($e->cgpa_percentage ?? '')); ?></td>
								<td>
									<?php if (!empty($e->transcript_file)): ?>
										<a href="javascript:void(0)" class="js-doc-view eye-link" data-title="Education Document" data-url="<?php echo base_url($e->transcript_file); ?>"><i class="far fa-eye"></i></a>
									<?php else: ?>
										<span class="doc-missing">Not attached</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr><td colspan="5" class="text-center text-muted">No education records.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="section-title">Experience Details:</div>
		<div class="section-card p-0">
			<table class="table mb-0 table-clean">
				<thead>
					<tr>
						<th>Department</th>
						<th>Sector</th>
						<th>Experience Type</th>
						<th>Job Type</th>
						<th>Teaching Level</th>
						<th>BPS</th>
						<th>Start Date</th>
						<th>End Date</th>
						<th style="width:140px;">View Document</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($experiences)): ?>
						<?php foreach ($experiences as $x): ?>
							<tr>
								<td><?php echo htmlspecialchars((string) ($x->department ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($x->sector ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($x->experience_type ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($x->job_type ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($x->teaching_level ?? '')); ?></td>
								<td><?php echo htmlspecialchars((string) ($x->bps ?? '')); ?></td>
								<td><?php echo !empty($x->start_date) ? date('M j, Y', strtotime($x->start_date)) : ''; ?></td>
								<td>
									<?php if (!empty($x->currently_working)): ?>
										Currently Working
									<?php else: ?>
										<?php echo !empty($x->end_date) ? date('M j, Y', strtotime($x->end_date)) : ''; ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if (!empty($x->document_file)): ?>
										<a href="javascript:void(0)" class="js-doc-view eye-link" data-title="Experience Document" data-url="<?php echo base_url($x->document_file); ?>"><i class="far fa-eye"></i></a>
									<?php else: ?>
										<span class="doc-missing">Not attached</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr><td colspan="9" class="text-center text-muted">No experience records.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<?php $b = isset($bank) && is_object($bank) ? $bank : null; ?>
		<div class="section-title">Bank Details:</div>
		<div class="section-card p-0">
			<table class="table mb-0 table-clean">
				<thead>
					<tr>
						<th>Bank Name</th>
						<th>Branch Name</th>
						<th>Account/IBAN Number</th>
						<th>Branch Code</th>
						<th>Account Title</th>
						<th>International User</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo htmlspecialchars((string) ($b->bank_name ?? '')); ?></td>
						<td><?php echo htmlspecialchars((string) ($b->branch_name ?? '')); ?></td>
						<td><?php echo htmlspecialchars((string) ($b->iban_account_no ?? '')); ?></td>
						<td><?php echo htmlspecialchars((string) ($b->branch_code ?? '')); ?></td>
						<td><?php echo htmlspecialchars((string) ($b->account_title ?? '')); ?></td>
						<td><?php echo !empty($b->international_user) ? 'Yes' : 'No'; ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php $s = isset($specialization) && is_object($specialization) ? $specialization : null; ?>
		<div class="section-title">Area Of Specialization</div>
		<div class="section-card">
			<?php $specVal = trim((string) ($s->specialization ?? '')); ?>
			<span class="<?php echo (strtoupper($specVal) === 'URDU') ? 'urdufont-right' : ''; ?>">
				<?php echo htmlspecialchars($specVal); ?>
			</span>
		</div>

		<div class="section-title">E-marking Experience:</div>
		<div class="section-card p-0">
			<table class="table mb-0 table-clean">
				<thead>
					<tr>
						<th>Department</th>
						<th>From Date</th>
						<th>To Date</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($emarking)): ?>
						<?php foreach ($emarking as $m): ?>
							<tr>
								<td><?php echo htmlspecialchars((string) ($m->department ?? '')); ?></td>
								<td><?php echo !empty($m->from_date) ? date('M j, Y', strtotime($m->from_date)) : ''; ?></td>
								<td><?php echo !empty($m->to_date) ? date('M j, Y', strtotime($m->to_date)) : ''; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr><td colspan="3" class="text-center text-muted">No e-marking records.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content" style="border-radius:10px;">
			<div class="modal-header">
				<h5 class="modal-title">Reject Request</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<?php echo form_open('admin/emarkers/reject/' . (int) ($u->id ?? 0), ['method' => 'POST']); ?>
				<div class="modal-body">
					<div class="form-group">
						<label>Rejection Reason<span class="text-danger">*</span></label>
						<textarea name="reason" class="form-control" rows="4" placeholder="Enter rejection reason..." required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger">Reject</button>
				</div>
			<?php echo form_close(); ?>
		</div>
	</div>
</div>

<!-- Seek Information Modal -->
<div class="modal fade" id="seekModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content" style="border-radius:10px;">
			<div class="modal-header">
				<h5 class="modal-title">Seek Information</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<?php echo form_open('admin/emarkers/seek_information/' . (int) ($u->id ?? 0), ['method' => 'POST']); ?>
				<div class="modal-body">
					<div class="form-group">
						<label>Message<span class="text-danger">*</span></label>
						<textarea name="note" class="form-control" rows="4" placeholder="Enter message for evaluator..." required><?php echo !empty($steps->review_notes) ? htmlspecialchars((string) $steps->review_notes) : ''; ?></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-outline-primary">Save</button>
				</div>
			<?php echo form_close(); ?>
		</div>
	</div>
</div>

<!-- Document Modal -->
<div class="modal fade" id="docModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		<div class="modal-content" style="border-radius:10px;">
			<div class="modal-header">
				<h5 class="modal-title" id="docModalLabel">View Document</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" style="min-height:70vh;">
				<img id="docModalImg" src="" alt="Document" style="max-width:100%;max-height:70vh;display:none;margin:0 auto;">
				<iframe id="docModalFrame" src="" style="width:100%;height:70vh;border:0;display:none;"></iframe>
			</div>
		</div>
	</div>
</div>

<script>
	$(function () {
		$('.js-approve').on('click', function (e) {
			if (!confirm('Approve and activate this E-Marker account?')) {
				e.preventDefault();
				return false;
			}
		});

		$('.js-reject-btn').on('click', function () {
			$('#rejectModal').modal('show');
		});
		$('.js-seek-btn').on('click', function () {
			$('#seekModal').modal('show');
		});

		$(document).on('click', '.js-doc-view', function (e) {
			e.preventDefault();
			var url = $(this).data('url') || '';
			var title = $(this).data('title') || 'View Document';
			if (!url) return;

			$('#docModalLabel').text(title);
			var lower = url.toLowerCase();
			var isImg = (lower.indexOf('.jpg') > -1 || lower.indexOf('.jpeg') > -1 || lower.indexOf('.png') > -1 || lower.indexOf('.gif') > -1 || lower.indexOf('.webp') > -1);
			if (isImg) {
				$('#docModalFrame').hide().attr('src', '');
				$('#docModalImg').show().attr('src', url);
			} else {
				$('#docModalImg').hide().attr('src', '');
				$('#docModalFrame').show().attr('src', url);
			}
			$('#docModal').modal('show');
		});
		$('#docModal').on('hidden.bs.modal', function () {
			$('#docModalImg').hide().attr('src', '');
			$('#docModalFrame').hide().attr('src', '');
		});

		// Toggle active status
		$('.js-status-switch').on('switchChange.bootstrapSwitch', function (event, state) {
			var $sw = $(this);
			var id = $sw.data('user-id');
			if (!id) return;

			if (!confirm('Change account status?')) {
				$sw.bootstrapSwitch('state', !state, true);
				return;
			}

			$.get('<?php echo url('admin/emarkers/change_status'); ?>/' + id, { status: state }, function (data) {
				if (data !== 'done') {
					alert('Unable to change status.');
					$sw.bootstrapSwitch('state', !state, true);
				}
			}).fail(function () {
				alert('Unable to change status.');
				$sw.bootstrapSwitch('state', !state, true);
			});
		});
	});
</script>

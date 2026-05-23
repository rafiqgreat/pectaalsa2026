<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php include viewPath('admin/includes/header'); ?>
<?php
	$rows = isset($emarkers) && is_array($emarkers) ? $emarkers : [];
	$f = isset($filters) && is_array($filters) ? $filters : [];
	$type = (string) ($f['type'] ?? 'pending');
	$cnic = (string) ($f['cnic'] ?? '');
	$name = (string) ($f['name'] ?? '');
	$spec = (string) ($f['spec'] ?? '');
	$qual = (string) ($f['qual'] ?? '');
	$sort = (string) ($f['sort'] ?? '');
	$dir = (string) ($f['dir'] ?? 'asc');
	$spec_opts = isset($f['spec_opts']) && is_array($f['spec_opts']) ? $f['spec_opts'] : [];
	$qual_opts = isset($f['qual_opts']) && is_array($f['qual_opts']) ? $f['qual_opts'] : [];

	$titleMap = [
		'pending' => 'Pending Request',
		'approved' => 'Approved Profiles',
		'rejected' => 'Rejected Profiles',
	];
	$pageTitle = $titleMap[$type] ?? 'Pending Request';

	$expDir = ($sort === 'exp' && $dir === 'asc') ? 'desc' : 'asc';
	$expUrl = url('admin/emarkers/' . $type) . '?sort=exp&dir=' . $expDir
		. ($cnic !== '' ? ('&cnic=' . urlencode($cnic)) : '')
		. ($name !== '' ? ('&name=' . urlencode($name)) : '')
		. ($spec !== '' ? ('&spec=' . urlencode($spec)) : '')
		. ($qual !== '' ? ('&qual=' . urlencode($qual)) : '');
	$expArrow = ($sort === 'exp') ? ($dir === 'asc' ? '&uarr;' : '&darr;') : '&uarr;';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo htmlspecialchars((string) $pageTitle); ?></h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers/pending'); ?>">Evaluator</a></li>
					<li class="breadcrumb-item active"><?php echo htmlspecialchars((string) $pageTitle); ?></li>
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
						<h3 class="card-title p-3"><?php echo htmlspecialchars((string) $pageTitle); ?></h3>
					</div>

					<div class="card-body">
						<?php if (!empty($this->session->flashdata('alert'))): ?>
							<div class="alert alert-<?php echo $this->session->flashdata('alert-type'); ?>">
								<?php echo $this->session->flashdata('alert'); ?>
							</div>
						<?php endif; ?>

						<?php if ((int) logged('role') === 18): ?>
							<?php
								$ss_subjects_filter = isset($ss_subjects_filter) && is_array($ss_subjects_filter) ? $ss_subjects_filter : [];
							?>
							<div class="alert alert-info">
								<strong>Subject Specialist Filter:</strong>
								<?php echo !empty($ss_subjects_filter) ? htmlspecialchars(implode(', ', $ss_subjects_filter)) : '<em>No subjects assigned</em>'; ?>
							</div>
						<?php endif; ?>

						<div class="row mb-3">
							<div class="col-md-3 col-sm-6 mb-2">
								<input type="text" id="filter-cnic" class="form-control form-control-sm" placeholder="CNIC" value="<?php echo htmlspecialchars((string) $cnic); ?>">
							</div>
							<div class="col-md-3 col-sm-6 mb-2">
								<input type="text" id="filter-name" class="form-control form-control-sm" placeholder="Name" value="<?php echo htmlspecialchars((string) $name); ?>">
							</div>
							<div class="col-md-3 col-sm-6 mb-2">
								<select id="filter-spec" class="form-control form-control-sm">
									<option value="">All Specializations</option>
									<?php foreach ($spec_opts as $opt): ?>
										<option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($spec === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-md-3 col-sm-6 mb-2">
								<select id="filter-qual" class="form-control form-control-sm">
									<option value="">All Qualifications</option>
									<?php foreach ($qual_opts as $opt): ?>
										<option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($qual === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-12 mb-2 d-flex justify-content-end">
								<button type="button" id="filter-search" class="btn btn-sm btn-primary mr-2 px-4" style="min-width: 120px;">Search</button>
								<button type="button" id="filter-reset" class="btn btn-sm btn-outline-secondary px-4" style="min-width: 120px;">Reset</button>
							</div>
						</div>

						<table id="example1" class="table table-bordered table-hover table-striped">
							<thead>
								<tr>
									<th style="width:70px;">ID</th>
									<th>Name</th>
									<th style="width:170px;">CNIC</th>
									<th>Specialization</th>
									<th style="width:140px;">
										<a href="<?php echo $expUrl; ?>" style="color:inherit;text-decoration:none;">
											Experience <?php echo $expArrow; ?>
										</a>
									</th>
									<th>Qualification</th>
									<th style="width:160px;">Teaching Level</th>
									<th style="width:150px;">Active Status</th>
									<?php if ($type === 'rejected'): ?>
										<th>Rejection Reason</th>
									<?php else: ?>
										<th style="width:120px;">Status</th>
									<?php endif; ?>
									<th style="width:90px;">Action</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($rows)): ?>
									<?php foreach ($rows as $r): ?>
										<tr>
											<td><?php echo (int) ($r->id ?? 0); ?></td>
											<td><?php echo htmlspecialchars((string) ($r->name ?? '')); ?></td>
											<td><?php echo htmlspecialchars((string) ($r->cnic ?? '')); ?></td>
											<?php $specVal = trim((string) ($r->specialization ?? '')); ?>
											<td class="<?php echo (strtoupper($specVal) === 'URDU') ? 'urdufont-right' : ''; ?>">
												<?php echo htmlspecialchars($specVal); ?>
											</td>
											<td><?php echo number_format((float) ($r->total_years ?? 0), 1); ?> years</td>
											<td><?php echo htmlspecialchars((string) ($r->highest_degree ?? '')); ?></td>
											<td><?php echo htmlspecialchars((string) ($r->teaching_level ?? '---')); ?></td>
											<td>
												<?php
													$active = ((int) ($r->status ?? 0) === 1);
													// Only Admin can change evaluator active status (SS can view only).
													$disabled = ($type !== 'approved' || (int) logged('role') === 18) ? 'disabled' : '';
												?>
												<input
													type="checkbox"
													name="my-checkbox"
													<?php echo $active ? 'checked' : ''; ?>
													<?php echo $disabled; ?>
													class="js-emarker-status-switch"
													data-user-id="<?php echo (int) ($r->id ?? 0); ?>"
													data-bootstrap-switch
													data-off-color="secondary"
													data-on-color="success"
													data-off-text="Inactive"
													data-on-text="Active"
												>
											</td>
											<?php if ($type === 'rejected'): ?>
												<td><?php echo htmlspecialchars((string) ($r->rejection_reason ?? '')); ?></td>
											<?php else: ?>
												<td><?php echo htmlspecialchars((string) (!empty($r->derived_status) ? $r->derived_status : ($type === 'approved' ? 'Approved' : 'Pending'))); ?></td>
											<?php endif; ?>
											<td>
												<a href="<?php echo url('admin/emarkers/edit/' . (int) $r->id); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary" title="Edit" data-toggle="tooltip">
													<i class="fa fa-edit"></i>
												</a>
												<a href="<?php echo url('admin/emarkers/view/' . (int) $r->id); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-info" title="View" data-toggle="tooltip">
													<i class="fa fa-eye"></i>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr><td colspan="10" class="text-center text-muted py-4">No records found.</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					<!-- /.card-body -->
				</div>
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
			var baseUrl = '<?php echo url('admin/emarkers/' . $type); ?>';
			if (reset) {
				return baseUrl;
			}
			var params = [];
			var cnic = ($('#filter-cnic').val() || '').trim();
			var name = ($('#filter-name').val() || '').trim();
			var spec = $('#filter-spec').val() || '';
			var qual = $('#filter-qual').val() || '';
			if (cnic) params.push('cnic=' + encodeURIComponent(cnic));
			if (name) params.push('name=' + encodeURIComponent(name));
			if (spec) params.push('spec=' + encodeURIComponent(spec));
			if (qual) params.push('qual=' + encodeURIComponent(qual));
			return params.length ? (baseUrl + '?' + params.join('&')) : baseUrl;
		}

		$('#filter-search').on('click', function() {
			window.location.href = buildFilterUrl(false);
		});

		$('#filter-reset').on('click', function() {
			window.location.href = buildFilterUrl(true);
		});

		$('#filter-cnic, #filter-name').on('keyup', function(e) {
			if (e.key === 'Enter') window.location.href = buildFilterUrl(false);
		});
	});

	// Status switch: bind to bootstrapSwitch event to avoid spurious triggers on initial render.
	$(function () {
		$('.js-emarker-status-switch').on('switchChange.bootstrapSwitch', function (event, state) {
			var $sw = $(this);
			var id = parseInt($sw.data('user-id'), 10) || 0;
			if (!id) return;

			$.get('<?php echo url('admin/emarkers/change_status'); ?>/' + id, {
				status: state
			}, function (data) {
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

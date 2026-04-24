<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>Registered E-Markers</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item active">E-Markers</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">E-Marker Listing</h3>
			</div>
			<div class="card-body">
				<?php if (!empty($this->session->flashdata('alert'))): ?>
					<div class="alert alert-<?php echo $this->session->flashdata('alert-type'); ?>">
						<?php echo $this->session->flashdata('alert'); ?>
					</div>
				<?php endif; ?>

				<?php $f = isset($filters) && is_array($filters) ? $filters : ['status' => 'all', 'reg' => 'all', 'q' => '']; ?>
				<div class="row mb-3">
					<div class="col-md-3 col-sm-6 mb-2">
						<input type="text" id="filter-q" class="form-control form-control-sm" placeholder="Search name/email/phone/CNIC" value="<?php echo htmlspecialchars((string) ($f['q'] ?? '')); ?>">
					</div>
					<div class="col-md-3 col-sm-6 mb-2">
						<select id="filter-status" class="form-control form-control-sm">
							<option value="all" <?php echo (($f['status'] ?? 'all') === 'all') ? 'selected' : ''; ?>>All Status</option>
							<option value="pending" <?php echo (($f['status'] ?? 'all') === 'pending') ? 'selected' : ''; ?>>Pending</option>
							<option value="active" <?php echo (($f['status'] ?? 'all') === 'active') ? 'selected' : ''; ?>>Active</option>
						</select>
					</div>
					<div class="col-md-3 col-sm-6 mb-2">
						<select id="filter-reg" class="form-control form-control-sm">
							<option value="all" <?php echo (($f['reg'] ?? 'all') === 'all') ? 'selected' : ''; ?>>All Registrations</option>
							<option value="completed" <?php echo (($f['reg'] ?? 'all') === 'completed') ? 'selected' : ''; ?>>Completed</option>
							<option value="incomplete" <?php echo (($f['reg'] ?? 'all') === 'incomplete') ? 'selected' : ''; ?>>Incomplete</option>
						</select>
					</div>
					<div class="col-md-2 col-sm-6 mb-2">
						<button type="button" id="filter-search" class="btn btn-sm btn-primary btn-block">Search</button>
					</div>
					<div class="col-md-1 col-sm-6 mb-2">
						<button type="button" id="filter-reset" class="btn btn-sm btn-outline-secondary btn-block">Reset</button>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped">
						<thead>
							<tr>
								<th style="width:80px;">ID</th>
								<th>Name</th>
								<th>CNIC</th>
								<th>Email</th>
								<th>Phone</th>
								<th>Registration</th>
								<th>Account</th>
								<th>Docs</th>
								<th style="width:140px;">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($emarkers)): ?>
								<?php foreach ($emarkers as $r): ?>
									<tr>
										<td><?php echo (int) $r->id; ?></td>
										<td><?php echo htmlspecialchars((string) $r->name); ?></td>
										<td><?php echo htmlspecialchars((string) $r->cnic); ?></td>
										<td><?php echo htmlspecialchars((string) $r->email); ?></td>
										<td><?php echo htmlspecialchars((string) $r->phone); ?></td>
										<td>
											<?php if ((int) ($r->registration_completed ?? 0) === 1): ?>
												<span class="badge badge-success">Completed</span>
											<?php else: ?>
												<span class="badge badge-secondary">Incomplete</span>
											<?php endif; ?>
										</td>
										<td>
											<input type="checkbox"
												name="emarker-status"
												data-user-id="<?php echo (int) $r->id; ?>"
												<?php echo ((int) ($r->status ?? 0) === 1) ? 'checked' : ''; ?>
												data-bootstrap-switch
												data-off-color="secondary"
												data-on-color="success"
												data-off-text="Inactive"
												data-on-text="Active"
											>
										</td>
										<td>
											<span class="badge badge-info">Sec: <?php echo !empty($r->has_security_doc) ? 'Yes' : 'No'; ?></span>
											<span class="badge badge-light">Edu: <?php echo (int) ($r->edu_docs ?? 0); ?></span>
											<span class="badge badge-light">Exp: <?php echo (int) ($r->exp_docs ?? 0); ?></span>
										</td>
										<td>
											<a href="<?php echo url('admin/emarkers/view/' . (int) $r->id); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr><td colspan="9" class="text-center text-muted">No e-markers found.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
	$(function () {
		function buildUrl(reset) {
			var base = '<?php echo url('admin/emarkers'); ?>';
			if (reset) return base;
			var params = [];
			var q = ($('#filter-q').val() || '').trim();
			var status = $('#filter-status').val() || 'all';
			var reg = $('#filter-reg').val() || 'all';
			if (q) params.push('q=' + encodeURIComponent(q));
			if (status && status !== 'all') params.push('status=' + encodeURIComponent(status));
			if (reg && reg !== 'all') params.push('reg=' + encodeURIComponent(reg));
			return params.length ? (base + '?' + params.join('&')) : base;
		}
		$('#filter-search').on('click', function () { window.location.href = buildUrl(false); });
		$('#filter-reset').on('click', function () { window.location.href = buildUrl(true); });
	});

	$(function () {
		// Toggle behavior (BootstrapSwitch)
		$("input[name='emarker-status']").on('switchChange.bootstrapSwitch', function (event, state) {
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

<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo htmlspecialchars((string) ($page->title ?? 'QC - My Batches')); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<?php $s = isset($stats) && is_array($stats) ? $stats : []; ?>
		<div class="row">
			<div class="col-md-3 col-6">
				<div class="small-box bg-info">
					<div class="inner">
						<h3><?php echo (int) ($s['batches_total'] ?? 0); ?></h3>
						<p>Total QC Batches</p>
					</div>
				</div>
			</div>
			<div class="col-md-3 col-6">
				<div class="small-box bg-warning">
					<div class="inner">
						<h3><?php echo (int) ($s['items_pending'] ?? 0); ?></h3>
						<p>Pending Items</p>
					</div>
				</div>
			</div>
			<div class="col-md-3 col-6">
				<div class="small-box bg-success">
					<div class="inner">
						<h3><?php echo (int) ($s['items_marked'] ?? 0); ?></h3>
						<p>Marked</p>
					</div>
				</div>
			</div>
			<div class="col-md-3 col-6">
				<div class="small-box bg-secondary">
					<div class="inner">
						<h3><?php echo (int) ($s['items_not_attempted'] ?? 0); ?></h3>
						<p>Not Attempted</p>
					</div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">My QC Batches</h3>
			</div>
			<div class="card-body table-responsive p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead>
						<tr>
							<th>Batch Code</th>
							<th>Assessment</th>
							<th>Grade</th>
							<th>Subject</th>
							<th>Version</th>
							<th>Status</th>
							<th>Total</th>
							<th>Pending</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($batches)): ?>
							<tr><td colspan="9" class="text-center text-muted py-3">No batches assigned.</td></tr>
						<?php else: ?>
							<?php foreach ($batches as $b): ?>
								<tr>
									<td><?php echo htmlspecialchars((string) ($b->batch_code ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->assessment_type ?? '')); ?></td>
									<td><?php echo (int) ($b->grade ?? 0); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->subject_code ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->version ?? 'ALL')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->status ?? '')); ?></td>
									<td><?php echo (int) ($b->total_items ?? 0); ?></td>
									<td><?php echo (int) ($b->pending_items ?? 0); ?></td>
									<td>
										<a class="btn btn-sm btn-info" href="<?php echo url('admin/qc_marking/view_batch/' . (int) $b->id); ?>">View</a>
										<a class="btn btn-sm btn-primary" href="<?php echo url('admin/qc_marking/start/' . (int) $b->id); ?>">Start</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>


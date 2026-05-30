<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo htmlspecialchars((string) ($page->title ?? 'QC - Batches')); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">QC Batches</h3>
				<div class="card-tools">
					<a class="btn btn-sm btn-primary" href="<?php echo url('admin/qc_marking/create_batch'); ?>">Create QC Batch</a>
				</div>
			</div>
			<div class="card-body table-responsive p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead>
						<tr>
							<th>ID</th>
							<th>Batch Code</th>
							<th>Assessment</th>
							<th>Grade</th>
							<th>Subject</th>
							<th>Version</th>
							<th>SS</th>
							<th>Status</th>
							<th>Total</th>
							<th>Pending</th>
							<th>Done</th>
							<th>Created</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($batches)): ?>
							<tr><td colspan="12" class="text-center text-muted py-3">No batches found.</td></tr>
						<?php else: ?>
							<?php foreach ($batches as $b): ?>
								<tr>
									<td><?php echo (int) ($b->id ?? 0); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->batch_code ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->assessment_type ?? '')); ?></td>
									<td><?php echo (int) ($b->grade ?? 0); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->subject_code ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->version ?? 'ALL')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->ss_name ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->status ?? '')); ?></td>
									<td><?php echo (int) ($b->total_items ?? 0); ?></td>
									<td><?php echo (int) ($b->pending_items ?? 0); ?></td>
									<td><?php echo (int) ($b->done_items ?? 0); ?></td>
									<td><?php echo htmlspecialchars((string) ($b->created_at ?? '')); ?></td>
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


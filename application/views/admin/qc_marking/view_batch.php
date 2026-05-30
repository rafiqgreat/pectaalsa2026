<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php $b = $batch ?? null; ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>QC Batch: <?php echo htmlspecialchars((string) ($b->batch_code ?? '')); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="card">
			<div class="card-header d-flex justify-content-between flex-wrap">
				<div>
					<strong>Status:</strong> <?php echo htmlspecialchars((string) ($b->status ?? '')); ?>
				</div>
				<div>
					<a class="btn btn-sm btn-primary" href="<?php echo url('admin/qc_marking/start/' . (int) ($b->id ?? 0)); ?>">Start</a>
				</div>
			</div>
			<div class="card-body table-responsive p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead>
						<tr>
							<th>Item</th>
							<th>Question</th>
							<th>Barcode</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($items)): ?>
							<tr><td colspan="4" class="text-center text-muted py-3">No items.</td></tr>
						<?php else: ?>
							<?php $i = 1; foreach ($items as $r): ?>
								<tr>
									<td><?php echo (int) $i++; ?></td>
									<td><?php echo htmlspecialchars((string) ($r->question_no ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($r->paper_barcode ?? '')); ?></td>
									<td><?php echo htmlspecialchars((string) ($r->status ?? '')); ?></td>
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


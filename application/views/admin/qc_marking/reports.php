<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo htmlspecialchars((string) ($page->title ?? 'QC - SS Summary')); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Subject Specialist QC Summary</h3>
			</div>
			<div class="card-body">
				<form method="get" class="mb-3">
					<div class="form-row">
						<div class="col-md-2 mb-2">
							<input type="date" name="from" value="<?php echo htmlspecialchars((string) ($filters['from'] ?? '')); ?>" class="form-control" placeholder="From">
						</div>
						<div class="col-md-2 mb-2">
							<input type="date" name="to" value="<?php echo htmlspecialchars((string) ($filters['to'] ?? '')); ?>" class="form-control" placeholder="To">
						</div>
						<div class="col-md-2 mb-2">
							<button type="submit" class="btn btn-secondary btn-block">Filter</button>
						</div>
					</div>
				</form>
			</div>
			<div class="card-body table-responsive p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead>
						<tr>
							<th>SS</th>
							<th>Username</th>
							<th>Total Actions</th>
							<th>Marked</th>
							<th>Duration (Hours)</th>
							<th>Skipped</th>
							<th>Not Attempted</th>
							<th>Total Marks</th>
							<th>Total Max Marks</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($rows)): ?>
							<tr><td colspan="9" class="text-center text-muted py-3">No records</td></tr>
						<?php else: ?>
							<?php foreach ($rows as $r): ?>
								<tr>
									<td><?php echo htmlspecialchars((string) ($r->ss_name ?? '')); ?> (<?php echo (int) ($r->ss_id ?? 0); ?>)</td>
									<td><?php echo htmlspecialchars((string) ($r->ss_username ?? '')); ?></td>
									<td><?php echo (int) ($r->total_actions ?? 0); ?></td>
									<td><?php echo (int) ($r->marked ?? 0); ?></td>
									<td><?php echo number_format((float) ($r->duration_hours ?? 0), 2); ?></td>
									<td><?php echo (int) ($r->skipped ?? 0); ?></td>
									<td><?php echo (int) ($r->not_attempted ?? 0); ?></td>
									<td><?php echo number_format((float) ($r->total_marks ?? 0), 2); ?></td>
									<td><?php echo number_format((float) ($r->total_max_marks ?? 0), 2); ?></td>
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


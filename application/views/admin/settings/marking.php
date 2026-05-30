<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>Marking Settings</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/settings'); ?>">Settings</a></li>
					<li class="breadcrumb-item active">Marking</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Enable/Disable Marking</h3>
			</div>
			<div class="card-body">
				<form method="post" action="<?php echo url('admin/settings/markingUpdate'); ?>">
					<div class="form-group">
						<label for="marking_enabled">Enable Marking</label>
						<select name="marking_enabled" id="marking_enabled" class="form-control">
							<option value="1" <?php echo ((string) ($marking_enabled ?? '1') === '1') ? 'selected' : ''; ?>>Enabled</option>
							<option value="0" <?php echo ((string) ($marking_enabled ?? '1') === '0') ? 'selected' : ''; ?>>Disabled</option>
						</select>
						<small class="text-muted">If disabled, eMarkers cannot login and any active sessions will be forced to logout on next request.</small>
					</div>

					<div class="form-group">
						<label for="marking_block_message">Message to show on eMarker login</label>
						<textarea name="marking_block_message" id="marking_block_message" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($marking_block_message ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
					</div>

					<button type="submit" class="btn btn-primary">Save</button>
				</form>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>


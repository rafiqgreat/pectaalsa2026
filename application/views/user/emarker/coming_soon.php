<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark"><?php echo html_escape((string) ($page->title ?? '')); ?></h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/user/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item active"><?php echo html_escape((string) ($page->title ?? '')); ?></li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<div class="card">
			<div class="card-body">
				<p class="mb-0 text-muted">This section will be available soon.</p>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('user/includes/footer'); ?>


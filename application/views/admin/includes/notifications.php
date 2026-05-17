<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?php
$alert = $this->session->flashdata('alert');
$type = (string) $this->session->flashdata('alert-type');

// Backward compatibility: some controllers use message/message_type
if (!$alert) {
	$alert = $this->session->flashdata('message');
}
if ($type === '') {
	$type = (string) $this->session->flashdata('message_type');
}
if ($type === 'error') {
	$type = 'danger';
}
if ($type === '') {
	$type = 'info';
}
?>
<?php if ($alert): $time = time();  ?>

	<section style="padding: 15px;">
		<div class="alert alert-<?php echo htmlspecialchars($type); ?>" id="alert-<?php echo $time ?>">
			<p><?php echo $alert; ?></p>
		</div>
	</section>

	<script>
		setTimeout(function() {
			$('#alert-<?php echo $time ?>').hide().remove();
		}, 5000)
	</script>
	
<?php endif ?>

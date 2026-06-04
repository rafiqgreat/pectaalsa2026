<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>


<!-- jQuery -->
<script src="<?php echo $assets ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo $assets ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $assets ?>/js/adminlte.min.js"></script>


<script>
  $(function () {
    if ($.fn.iCheck) {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' /* optional */
      });
    }
  });
</script>
</body>
</html>

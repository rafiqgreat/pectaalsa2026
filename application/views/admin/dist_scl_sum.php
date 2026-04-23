<style>
  .district {
    font-weight: bold;
    font-size: 24px;
    margin-top: 15px;
  }

  .bdr {
    border-radius: 10px !important;
  }

  .small-box:hover {
    transform: translateY(-10px);
    transition: transform 0.5s ease-in-out;
  }
</style>
<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"> District Wise Summary</h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= base_url(); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item active">District Wise Summary</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<?php //echo '<pre>'; print_r($hardstatics); die();?>
<section class="content">
  <div class="container-fluid">
   <section class="content">
     <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Sr</th>
                    <th>District</th>
                    <!--<th>Hardcopies Received</th>-->
                    <th>Individual</th>
                    <th>Young Entrepreneur</th>
                </tr>
            </thead>
            <tbody>
			<?php if (!empty($district_summary)) : ?>
                <?php $sr = 1; foreach ($district_summary as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= $row['district'] ?></td>
                        <?php /*?><td><?= $row['hardcopies_received'] ?></td><?php */?>
                        <td><?= $row['individual'] ?></td>
                        <td><?= $row['young_entrepreneur'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No data found</td></tr>
            <?php endif; ?>

            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <!--<th></th>-->
                    <th><?= $total_individual; ?></th>
                    <th><?= $total_yep; ?></th>
                </tr>
            </tfoot>
        </table>
        </div>
     </div>
   </section>
	  
	
   
    <!-- /.row (main row) -->
  </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php include viewPath('admin/includes/footer'); ?>

<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="<?php echo $url->assets ?>js/pages/dashboard.js"></script>
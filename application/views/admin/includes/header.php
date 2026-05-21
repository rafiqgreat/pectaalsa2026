<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="<?php echo getUserlang() ?>">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo $page->title ?> | <?php echo $app->site_title ?> </title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf_token_name" content="<?php echo $this->security->get_csrf_token_name(); ?>" />
  <meta name="csrf_token_hash" content="<?php echo $this->security->get_csrf_hash(); ?>" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/toastr/toastr.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/jqvmap/jqvmap.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/summernote/summernote-bs4.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/select2/css/select2.min.css" />
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>css/adminlte.min.css">
  <link rel="stylesheet" href="<?php echo $url->assets ?>css/app.css">
  <!-- jQuery -->
  <script src="<?php echo $url->assets ?>plugins/jquery/jquery.min.js"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="<?php echo $url->assets ?>plugins/jquery-ui/jquery-ui.min.js"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
    if (window.jQuery && $.ui && $.widget && $.widget.bridge) {
      $.widget.bridge('uibutton', $.ui.button);
    }
  </script>
  <!-- Bootstrap 4 -->
  <script src="<?php echo $url->assets ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">

  <style>
    @font-face {
      font-family: "Jameel Noori Nastaleeq";
      src: url("<?= base_url() ?>assets/fonts/Jameel Noori Nastaleeq.ttf");
      font-weight: bold;
    }

    .urdufont-right {
      font-family: 'Jameel Noori Nastaleeq', 'Open Sans', sans-serif;
      direction: rtl;
      font-size: 22px;
      text-align: right;
    }

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
</head>

<body class="hold-transition sidebar-mini <?php echo isset($page->body_classes) ? $page->body_classes : 'layout-fixed' ?>">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <div class="row col-11">
        <div class="col-1"><img src="<?= base_url('assets/images/Logo_1.png'); ?>" alt="Logo 1" style="height:80px" /></div>
        <div class="col-10 mt-2">
          <h4><span style="font-weight:bold">PECTAA LSA 2026 - DIGITAL WEB SOLUTION</span></h4>
          <h5><span>Digital Barcoding, Scanning, Emarking, AI Analysis - A Complete Solution</span></h5>
        </div>
      </div>
      <!-- Left navbar links -->

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown Menu -->

        <!-- User Account: style can be found in dropdown.less -->
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
            <img src="<?php echo userProfile(logged('id')) ?>" class="user-image img-circle elevation-2" alt="User Image">
            <span class="d-none d-md-inline" style="color:#333 !important"><?php echo logged('name') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <!-- User image -->
            <li class="user-header bg-primary">
              <img src="<?php echo userProfile(logged('id')) ?>" class="img-circle elevation-2" alt="User Image">

              <p>
                <?php echo logged('name') ?>
                <small>Member since Nov. 2012</small>
              </p>
            </li>
            <!-- Menu Body -->
            <!-- Menu Footer-->
            <li class="user-footer">
              <a href="<?php echo url('/admin/profile') ?>" class="btn btn-default btn-flat"><?php echo lang('profile') ?></a>
              <a href="<?php echo url('/admin/logout') ?>" class="btn btn-default btn-flat float-right"><?php echo lang('signout') ?></a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar elevation-4" style="background-color: #1a365d;">
      <!-- Brand Logo -->
      <a href="<?php echo url('/admin/') ?>" class="brand-link">
        <img src="<?php echo $url->assets ?>/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light"><?php echo setting('company_name') ?></span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">


        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <?php include 'nav.php' ?>

        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">

      <?php include 'notifications.php'; ?>

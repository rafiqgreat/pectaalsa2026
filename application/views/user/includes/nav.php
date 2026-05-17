<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav nav-pills nav-sidebar flex-column nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
  <li class="nav-item">
    <a href="<?php echo url('user/dashboard'); ?>" class="nav-link <?php echo ($page->menu == 'dashboard') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-tachometer-alt"></i>
      <p><?php echo lang('dashboard'); ?></p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('emarker/marking/dashboard'); ?>" class="nav-link <?php echo ($page->menu == 'emarking') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-pen-square"></i>
      <p>E-Marking</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/invitation'); ?>" class="nav-link <?php echo ($page->menu == 'invitation') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-envelope-open-text"></i>
      <p>Invitation for Paper Checking</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/result'); ?>" class="nav-link <?php echo ($page->menu == 'result') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-poll"></i>
      <p>View Result</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/evaluator_profile'); ?>" class="nav-link <?php echo ($page->menu == 'evaluator_profile') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-user-check"></i>
      <p>View Profile</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/qc_review'); ?>" class="nav-link <?php echo ($page->menu == 'qc_review') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-clipboard-check"></i>
      <p>QC-Review</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/profile/index/change_password'); ?>" class="nav-link <?php echo ($page->menu == 'change_password') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-key"></i>
      <p>Change Password</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/logout'); ?>" class="nav-link">
      <i class="nav-icon fas fa-sign-out-alt"></i>
      <p>Logout</p>
    </a>
  </li>
</ul>

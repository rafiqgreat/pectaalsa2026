<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav nav-pills nav-sidebar flex-column nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
  <li class="nav-item">
    <a href="<?php echo url('admin/dashboard'); ?>" class="nav-link <?php echo ($page->menu == 'dashboard') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-tachometer-alt"></i>
      <p><?php echo lang('dashboard'); ?></p>
    </a>
  </li>

  <?php if (hasPermissions('users_list')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/users'); ?>" class="nav-link <?php echo ($page->menu == 'users') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-users"></i>
        <p>Users</p>
      </a>
    </li>
  <?php endif; ?>

  <?php if ((int) logged('role') === 1): ?>
    <li class="nav-item has-treeview <?php echo ($page->menu == 'emarkers') ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo ($page->menu == 'emarkers') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-user-check"></i>
        <p>
          Evaluator
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="<?php echo url('admin/emarkers/approved'); ?>" class="nav-link <?php echo ($page->menu == 'emarkers' && $page->submenu == 'approved') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Approved Profiles</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarkers/pending'); ?>" class="nav-link <?php echo ($page->menu == 'emarkers' && $page->submenu == 'pending') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Pending Requests</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarkers/rejected'); ?>" class="nav-link <?php echo ($page->menu == 'emarkers' && $page->submenu == 'rejected') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Rejected Profiles</p>
          </a>
        </li>
      </ul>
    </li>
  <?php endif; ?>

  <?php if (in_array((int) logged('role'), [1, 17, 18, 19], true)): ?>
    <li class="nav-item has-treeview <?php echo ($page->menu == 'emarking') ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo ($page->menu == 'emarking') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-pen-square"></i>
        <p>
          E-Marking
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/questions'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'questions') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Questions</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/import_crq_images'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'import' && strpos((string) ($page->title ?? ''), 'CRQ') !== false) ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Import CRQ</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/import_dictation_images'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'import' && strpos((string) ($page->title ?? ''), 'Dictation') !== false) ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Import Dictation</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/create_batch'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'batches' && strpos((string) ($page->title ?? ''), 'Create') !== false) ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Create Batch</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/batches'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'batches' && (string) ($page->title ?? '') === 'Batches') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Batches</p>
          </a>
        </li>
        <li class="nav-item has-treeview <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports') ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>
              Reports
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_questions'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'questions') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>Question-wise Summary</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_subjects'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'subjects') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>Subject-wise Summary</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_emarkers'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'emarkers') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>eMarker-wise Summary</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_batches'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'batches') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>Batch-wise Summary</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/billing'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'billing') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Billing</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/skipped'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'skipped') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Skipped</p>
          </a>
        </li>
      </ul>
    </li>
  <?php endif; ?>

  <?php if (
    hasPermissions('location_management') ||
    hasPermissions('state_list') ||
    hasPermissions('district_list') ||
    hasPermissions('tehsil_list')
  ): ?>
    <li class="nav-item has-treeview <?php echo ($page->menu == 'location') ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo ($page->menu == 'location') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-map-marker-alt"></i>
        <p>
          Locations
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <?php if (hasPermissions('state_list') || hasPermissions('location_management')): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/location'); ?>" class="nav-link <?php echo ($page->submenu == '') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>State</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermissions('district_list') || hasPermissions('location_management')): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/location/district'); ?>" class="nav-link <?php echo ($page->submenu == 'district') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>Districts</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermissions('tehsil_list') || hasPermissions('location_management')): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/location/tehsil'); ?>" class="nav-link <?php echo ($page->submenu == 'tehsil') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>Tehsils</p>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </li>
  <?php endif; ?>

  <?php if (hasPermissions('school_management')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/school'); ?>" class="nav-link <?php echo ($page->menu == 'school') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-school"></i>
        <p>Schools</p>
      </a>
    </li>
  <?php endif; ?>

  <?php if (hasPermissions('activity_logs_list')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/activity_logs'); ?>" class="nav-link <?php echo ($page->menu == 'activity_logs') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-history"></i>
        <p>Activity Logs</p>
      </a>
    </li>
  <?php endif; ?>

  <?php if (
    hasPermissions('general_settings') ||
    hasPermissions('company_settings') ||
    hasPermissions('login_theme') ||
    hasPermissions('email_templates') ||
    hasPermissions('roles_list') ||
    hasPermissions('permissions_list') ||
    (int) logged('role') === 1
  ): ?>
    <li class="nav-item has-treeview <?php echo in_array($page->menu, ['settings', 'roles', 'permissions'], true) ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo in_array($page->menu, ['settings', 'roles', 'permissions'], true) ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-cog"></i>
        <p>
          Settings
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <?php if (
          hasPermissions('general_settings') ||
          hasPermissions('company_settings') ||
          hasPermissions('login_theme') ||
          hasPermissions('email_templates') ||
          (int) logged('role') === 1
        ): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/settings'); ?>" class="nav-link <?php echo ($page->menu == 'settings') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>General Settings</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermissions('roles_list')): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/roles'); ?>" class="nav-link <?php echo ($page->menu == 'roles') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>Roles</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermissions('permissions_list')): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/permissions'); ?>" class="nav-link <?php echo ($page->menu == 'permissions') ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>Permissions</p>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </li>
  <?php endif; ?>

  <?php if (hasPermissions('profile_view')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/profile'); ?>" class="nav-link <?php echo ($page->menu == 'profile') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-user"></i>
        <p>Profile</p>
      </a>
    </li>
  <?php endif; ?>

  <li class="nav-item">
    <a href="<?php echo url('admin/logout'); ?>" class="nav-link">
      <i class="nav-icon fas fa-sign-out-alt"></i>
      <p><?php echo lang('signout'); ?></p>
    </a>
  </li>
</ul>

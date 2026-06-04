<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav nav-pills nav-sidebar flex-column nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
  <li class="nav-item">
    <a href="<?php echo url(((int) logged('role') === 19) ? 'admin/dashboard/head_markers' : 'admin/dashboard'); ?>" class="nav-link <?php echo ($page->menu == 'dashboard') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-tachometer-alt"></i>
      <p><?php echo lang('dashboard'); ?></p>
    </a>
  </li>

  <?php $role = (int) logged('role'); ?>
  <?php if ($role === 19): ?>
    <li class="nav-item has-treeview <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports') ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-chart-bar"></i>
        <p>
          Reports
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_questions'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'questions') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Question-wise Summary</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_emarkers'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'emarkers') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>eMarker-wise Summary</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_subjects'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'subjects') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Subject-wise Summary</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_emarkers_payment_summary'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'emarkers_payment') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>eMarker-wise Payment Summary</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_batches'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'batches') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Batch-wise Summary</p>
          </a>
        </li>
      </ul>
    </li>
  <?php else: ?>

  <?php if ($role !== 18 && hasPermissions('users_list')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/users'); ?>" class="nav-link <?php echo ($page->menu == 'users') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-users"></i>
        <p>Users</p>
      </a>
    </li>
  <?php endif; ?>

  <?php
  // Evaluator menu is available to Admin (role 1/17) and Subject Specialist (role 18).
  // Record-level access for Subject Specialist is restricted in the listing query.
  ?>
  <?php if (in_array($role, [1, 17, 18], true)): ?>
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
        <?php if ($role !== 18): ?>
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
        <?php endif; ?>
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
        <?php if ($role === 1): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/qc_marking/create_batch'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'qc' && strpos((string) ($page->title ?? ''), 'QC - Create') !== false) ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>QC Create Batch</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo url('admin/qc_marking/batches'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'qc' && strpos((string) ($page->title ?? ''), 'QC - Batches') !== false) ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>QC Batches</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo url('admin/qc_marking/reports'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'qc' && strpos((string) ($page->title ?? ''), 'QC - SS') !== false) ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>QC SS Report</p>
            </a>
          </li>
        <?php elseif ($role === 18): ?>
          <li class="nav-item">
            <a href="<?php echo url('admin/qc_marking/my'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'qc' && strpos((string) ($page->title ?? ''), 'QC - My') !== false) ? 'active' : ''; ?>">
              <i class="far fa-circle nav-icon"></i>
              <p>QC Dashboard</p>
            </a>
          </li>
        <?php endif; ?>
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
              <a href="<?php echo url('admin/emarking/reports_emarkers'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'emarkers') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>eMarker-wise Summary</p>
              </a>
            </li>
            <?php if ($role !== 18): ?>
              <li class="nav-item">
                <a href="<?php echo url('admin/emarking/reports_subjects'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'subjects') ? 'active' : ''; ?>">
                  <i class="far fa-dot-circle nav-icon"></i>
                  <p>Subject-wise Summary</p>
                </a>
              </li>
              <?php if ($role === 1): ?>
                <li class="nav-item">
                  <a href="<?php echo url('admin/emarking/reports_emarkers_payment_summary'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'emarkers_payment') ? 'active' : ''; ?>">
                    <i class="far fa-dot-circle nav-icon"></i>
                    <p>eMarker-wise Payment Summary</p>
                  </a>
                </li>
              <?php endif; ?>
              <li class="nav-item">
                <a href="<?php echo url('admin/emarking/reports_batches'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'batches') ? 'active' : ''; ?>">
                  <i class="far fa-dot-circle nav-icon"></i>
                  <p>Batch-wise Summary</p>
                </a>
              </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_eng_crqs_barcodes'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'eng_crqs_barcodes') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>ENG CRQs Barcodes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_urdu_crqs_barcodes'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'urdu_crqs_barcodes') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>URDU CRQs Barcodes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_math_crqs_barcodes'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'math_crqs_barcodes') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>MATH CRQs Barcodes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/emarking/reports_science_crqs_barcodes'); ?>" class="nav-link <?php echo ($page->menu == 'emarking' && $page->submenu == 'reports' && (string) ($reports_tab ?? '') === 'science_crqs_barcodes') ? 'active' : ''; ?>">
                <i class="far fa-dot-circle nav-icon"></i>
                <p>SCIENCE CRQs Barcodes</p>
              </a>
            </li>
          </ul>
        </li>
        <?php if ($role !== 18): ?>
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
        <?php endif; ?>
      </ul>
    </li>
  <?php endif; ?>

  <?php if ($role !== 18 && (
    hasPermissions('location_management') ||
    hasPermissions('state_list') ||
    hasPermissions('district_list') ||
    hasPermissions('tehsil_list')
  )): ?>
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

  <?php if ($role !== 18 && hasPermissions('school_management')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/school'); ?>" class="nav-link <?php echo ($page->menu == 'school') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-school"></i>
        <p>Schools</p>
      </a>
    </li>
  <?php endif; ?>

  <?php if ($role === 1): ?>
    <li class="nav-item has-treeview <?php echo ($page->menu == 'results') ? 'menu-open' : ''; ?>">
      <a href="#" class="nav-link <?php echo ($page->menu == 'results') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-poll"></i>
        <p>
          Results
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_crq_csv'); ?>" class="nav-link <?php echo ($page->menu == 'results' && $page->submenu == 'crq_csv') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>CRQ Result CSV</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_mcq_csv'); ?>" class="nav-link <?php echo ($page->menu == 'results' && $page->submenu == 'mcq_csv') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>MCQ Result CSV</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_bq_csv'); ?>" class="nav-link <?php echo ($page->menu == 'results' && $page->submenu == 'bq_csv') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>BQ Result CSV</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo url('admin/emarking/reports_dictation_csv'); ?>" class="nav-link <?php echo ($page->menu == 'results' && $page->submenu == 'dictation_csv') ? 'active' : ''; ?>">
            <i class="far fa-circle nav-icon"></i>
            <p>Dictation Result CSV</p>
          </a>
        </li>
      </ul>
    </li>
  <?php endif; ?>

  <?php if ($role !== 18 && hasPermissions('activity_logs_list')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/activity_logs'); ?>" class="nav-link <?php echo ($page->menu == 'activity_logs') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-history"></i>
        <p>Activity Logs</p>
      </a>
    </li>
  <?php endif; ?>

  <?php if ($role !== 18 && (
    hasPermissions('general_settings') ||
    hasPermissions('company_settings') ||
    hasPermissions('login_theme') ||
    hasPermissions('email_templates') ||
    hasPermissions('roles_list') ||
    hasPermissions('permissions_list') ||
    (int) logged('role') === 1
  )): ?>
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
          <?php if (hasPermissions('general_settings') || (int) logged('role') === 1): ?>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/marking'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'marking') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Marking</p>
              </a>
            </li>
            <?php if ((int) logged('role') === 1): ?>
              <li class="nav-item">
                <a href="<?php echo url('admin/settings/mark'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'mark') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Mark</p>
                </a>
              </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/check_sizes'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'check_sizes') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Check Sizes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/sync_eng_crqs'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'sync_eng_crqs') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Synchronize Eng CRQs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/sync_urdu_crqs'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'sync_urdu_crqs') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Synchronize Urdu CRQs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/sync_math_crqs'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'sync_math_crqs') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Synchronize Math CRQs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo url('admin/settings/sync_science_crqs'); ?>" class="nav-link <?php echo ($page->menu == 'settings' && $page->submenu == 'sync_science_crqs') ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Synchronize Science CRQs</p>
              </a>
            </li>
          <?php endif; ?>
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

  <?php if ($role === 18 || hasPermissions('profile_view')): ?>
    <li class="nav-item">
      <a href="<?php echo url('admin/profile'); ?>" class="nav-link <?php echo ($page->menu == 'profile') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-user"></i>
        <p>Profile</p>
      </a>
    </li>
  <?php endif; ?>
  <?php endif; ?>

  <li class="nav-item">
    <a href="<?php echo url('admin/logout'); ?>" class="nav-link">
      <i class="nav-icon fas fa-sign-out-alt"></i>
      <p><?php echo lang('signout'); ?></p>
    </a>
  </li>
</ul>

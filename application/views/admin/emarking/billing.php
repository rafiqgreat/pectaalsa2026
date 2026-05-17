<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Billing</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Billing</li>
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
        <div class="d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0">eMarker Billing Summary</h3>
          <div>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('admin/emarking/reports'); ?>">Reports</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('admin/emarking/skipped'); ?>">Skipped</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <input type="date" name="from" value="<?php echo html_escape((string) ($filters['from'] ?? '')); ?>" class="form-control">
            </div>
            <div class="col-md-2 mb-2">
              <input type="date" name="to" value="<?php echo html_escape((string) ($filters['to'] ?? '')); ?>" class="form-control">
            </div>
            <div class="col-md-4 mb-2">
              <select name="emarker_id" class="form-control">
                <option value="">All eMarkers</option>
                <?php foreach (($emarkers ?? []) as $u): ?>
                  <option value="<?php echo (int) $u->id; ?>" <?php echo ((string) ($filters['emarker_id'] ?? '') === (string) $u->id) ? 'selected' : ''; ?>>
                    <?php echo html_escape((string) ($u->name ?: $u->username)); ?> (<?php echo (int) $u->id; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Filter</button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>eMarker</th>
                <th>Assessment</th>
                <th>Subject</th>
                <th>Question</th>
                <th>Checked (Payable)</th>
                <th>Sum Max Marks</th>
                <th>Rate Type</th>
                <th>Rate</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($billing_rows)): ?>
                <tr><td colspan="9" class="text-center text-muted">No records</td></tr>
              <?php else: ?>
                <?php $grand = 0.0; ?>
                <?php foreach ($billing_rows as $r): ?>
                  <tr>
                    <td>
                      <?php echo html_escape((string) $r->emarker_name); ?> (<?php echo (int) $r->emarker_id; ?>)
                      <div class="text-muted"><?php echo html_escape((string) $r->emarker_username); ?></div>
                    </td>
                    <td><?php echo html_escape((string) $r->assessment_type); ?></td>
                    <td><?php echo html_escape((string) $r->subject_code); ?></td>
                    <td>
                      <strong><?php echo html_escape((string) $r->question_no); ?></strong>
                      <div class="text-muted" style="max-width:380px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo html_escape((string) $r->question_title); ?>
                      </div>
                    </td>
                    <td><?php echo (int) $r->checked_count; ?></td>
                    <td><?php echo number_format((float) $r->sum_max_marks, 2); ?></td>
                    <td><?php echo html_escape((string) $r->rate_type); ?></td>
                    <td><?php echo number_format((float) $r->rate, 2); ?></td>
                    <td><?php echo number_format((float) $r->amount, 2); ?></td>
                  </tr>
                  <?php $grand += (float) $r->amount; ?>
                <?php endforeach; ?>
                <tr>
                  <td colspan="8" class="text-right"><strong>Grand Total</strong></td>
                  <td><strong><?php echo number_format((float) $grand, 2); ?></strong></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <small class="text-muted d-block mt-2">
          Payable statuses: <code>MARKED</code>, <code>NOT_ATTEMPTED</code>. <code>SKIPPED</code> is not payable by default.
          Rates are taken from <code>emarking_rates</code> (question-specific preferred; otherwise grade/subject default).
        </small>
      </div>
    </div>

  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

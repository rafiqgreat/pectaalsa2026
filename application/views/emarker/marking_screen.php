<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<?php
$item = $marking['item'];
$steps = $marking['steps'] ?? [];
$mark = $marking['mark'] ?? null;
$mark_steps = $marking['mark_steps'] ?? [];
$image_url = base_url((string) $item->image_path);
$total = (int) ($batch_total_items ?? 0);
$idx = (int) ($batch_current_index ?? 0);
$timer_seconds = (int) ($timer_seconds ?? 15);
if ($timer_seconds < 0) $timer_seconds = 0;
?>

<style>
  .emarking-topbar { background:#0b62d6; color:#fff; padding:10px 0; }
  .emarking-topbar a { color:#fff; }
  .rubric-card { border:1px solid #e9ecef; border-radius:8px; padding:12px; margin-bottom:10px; }
  .rubric-title { font-weight:600; }
  .rubric-meta { color:#6c757d; font-size:12px; }
</style>

<div class="emarking-topbar">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong>Exam Management System</strong> | <strong>PECTAA 2026</strong> |
        <a href="<?php echo base_url('user/dashboard'); ?>">Home</a>
      </div>
      <div>
        eMarker: <strong><?php echo html_escape((string) logged('name')); ?></strong>
      </div>
    </div>
  </div>
</div>

<section class="content" style="padding-top:12px;">
  <div class="container-fluid">

    <?php include viewPath('user/includes/notifications'); ?>

    <div class="row">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div><strong>Question No:</strong> <?php echo htmlspecialchars((string) $item->question_no); ?></div>
                <div class="text-muted">
                  <strong>Image:</strong> <?php echo $idx; ?> / <?php echo $total; ?>
                </div>
              </div>
              <div>
                <span class="badge badge-info"><?php echo html_escape((string) $batch->batch_code); ?></span>
                <a class="btn btn-outline-light btn-sm ml-2" style="border-color:#fff;" href="<?php echo base_url('emarker/marking/view_batch/' . (int) $batch->id); ?>">View Batch</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="mb-1"><strong>Question Statement</strong></div>
              <div style="white-space:pre-wrap;"><?php echo html_escape((string) $item->question_title); ?></div>
            </div>

            <div class="mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <div><strong>Student Cropped Answer Image</strong></div>
              </div>
              <div style="border:1px solid #e5e5e5; padding:8px; background:#fafafa;">
                <img src="<?php echo $image_url; ?>" alt="Answer Image" style="width:100%; height:auto;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <form method="post" action="<?php echo base_url('emarker/marking/save_marks'); ?>">
          <input type="hidden" name="batch_id" value="<?php echo (int) $item->batch_id; ?>">
          <input type="hidden" name="batch_item_id" value="<?php echo (int) $item->id; ?>">

          <div class="card">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Rubric Steps</h3>
                <div>
                  <?php if (!empty($item->sample_answer) || !empty($item->sample_answer_file)): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#sampleAnswerModal">View Sample Answer</button>
                  <?php endif; ?>
                  <?php if (!empty($item->guide_text) || !empty($item->guide_file)): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#guideModal">Guide</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-body" style="max-height:60vh; overflow:auto;">

              <?php if (!empty($steps)): ?>
                <?php foreach ($steps as $s): ?>
                  <?php
                  $existing = $mark_steps[(int) $s->id] ?? null;
                  $existingVal = $existing ? (string) $existing->selected_value : '';
                  $stepType = (string) $s->marking_type;
                  $stepMarks = (float) ($s->step_marks ?? 0);
                  $stepMin = (float) ($s->min_marks ?? 0);
                  $stepMax = (float) ($s->max_marks ?? 0);
                  ?>
                  <div class="rubric-card" data-step-id="<?php echo (int) $s->id; ?>" data-type="<?php echo html_escape($stepType); ?>" data-step-marks="<?php echo htmlspecialchars((string) $stepMarks); ?>" data-min="<?php echo htmlspecialchars((string) $stepMin); ?>" data-max="<?php echo htmlspecialchars((string) $stepMax); ?>">
                    <div class="d-flex justify-content-between">
                      <div class="rubric-title">
                        <?php echo html_escape((string) $s->step_label); ?>
                        <?php echo html_escape((string) $s->step_title); ?>
                      </div>
                      <div class="rubric-meta">Marks: <?php echo html_escape((string) $s->step_marks); ?></div>
                    </div>
                    <?php if (!empty($s->step_detail)): ?>
                      <div class="rubric-meta" style="white-space:pre-wrap;"><?php echo html_escape((string) $s->step_detail); ?></div>
                    <?php endif; ?>

                    <div class="mt-2">
                      <?php if ((string) $s->marking_type === 'ZERO_ONE'): ?>
                        <div class="custom-control custom-radio">
                          <input class="custom-control-input emarking-step-input" type="radio" id="step_<?php echo (int) $s->id; ?>_c" name="steps[<?php echo (int) $s->id; ?>]" value="1" <?php echo ($existingVal === '1') ? 'checked' : ''; ?>>
                          <label class="custom-control-label" for="step_<?php echo (int) $s->id; ?>_c">Correct</label>
                        </div>
                        <div class="custom-control custom-radio">
                          <input class="custom-control-input emarking-step-input" type="radio" id="step_<?php echo (int) $s->id; ?>_w" name="steps[<?php echo (int) $s->id; ?>]" value="0" <?php echo ($existingVal === '0' || $existingVal === '') ? 'checked' : ''; ?>>
                          <label class="custom-control-label" for="step_<?php echo (int) $s->id; ?>_w">Wrong</label>
                        </div>
                        <small class="text-muted d-block mt-1">Correct = <?php echo htmlspecialchars((string) $s->step_marks); ?>, Wrong = 0</small>
                      <?php elseif ((string) $s->marking_type === 'RANGE'): ?>
                        <input type="number"
                          step="0.01"
                          min="<?php echo htmlspecialchars((string) $s->min_marks); ?>"
                          max="<?php echo htmlspecialchars((string) $s->max_marks); ?>"
                          class="form-control emarking-step-input"
                          name="steps[<?php echo (int) $s->id; ?>]"
                          value="<?php echo htmlspecialchars($existingVal !== '' ? $existingVal : (string) $s->min_marks); ?>">
                        <small class="text-muted d-block mt-1">Range: <?php echo html_escape((string) $s->min_marks); ?> - <?php echo html_escape((string) $s->max_marks); ?></small>
                      <?php else: ?>
                        <div class="text-muted">Fixed marks will be applied.</div>
                        <input type="hidden" class="emarking-step-input" name="steps[<?php echo (int) $s->id; ?>]" value="1">
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-warning mb-0">No rubric steps configured for this question.</div>
              <?php endif; ?>

              <div class="form-group mt-3">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="2"><?php echo html_escape((string) ($mark->remarks ?? '')); ?></textarea>
              </div>
            </div>
            <div class="card-footer">
              <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                <div class="text-muted">
                  <strong>Obtained:</strong>
                  <span id="emarkingObtained" data-total="<?php echo htmlspecialchars((string) ((float) ($item->max_marks ?? 0))); ?>">0.00</span>
                  /
                  <span id="emarkingTotal"><?php echo htmlspecialchars((string) ((float) ($item->max_marks ?? 0))); ?></span>
                </div>
                <div class="text-muted">
                  <strong>Timer:</strong>
                  <span id="emarkingTimer" data-seconds="<?php echo (int) $timer_seconds; ?>"><?php echo (int) $timer_seconds; ?>s</span>
                  <span id="emarkingTimerHint" class="ml-1">Submit buttons enable when timer ends.</span>
                </div>
              </div>
              <div class="d-flex justify-content-between flex-wrap">
                <div class="mb-2">
                  <button type="submit" name="action" value="SKIPPED" class="btn btn-outline-info emarking-action-btn" disabled onclick="return confirm('Skip this image?');">Skip</button>
                  <button type="submit" name="action" value="NOT_ATTEMPTED" class="btn btn-outline-secondary emarking-action-btn" disabled onclick="return confirm('Mark as NOT ATTEMPTED?');">Not Attempted</button>
                </div>
                <div class="mb-2">
                  <button type="submit" name="action" value="MARKED" class="btn btn-success emarking-action-btn" disabled>Submit &amp; Next</button>
                </div>
              </div>
              <small class="text-muted d-block">Current item status: <?php echo html_escape((string) $item->status); ?></small>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</section>

<?php if (!empty($item->sample_answer) || !empty($item->sample_answer_file)): ?>
  <div class="modal fade" id="sampleAnswerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Sample Answer</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($item->sample_answer)): ?>
            <div style="white-space:pre-wrap;"><?php echo html_escape((string) $item->sample_answer); ?></div>
          <?php endif; ?>
          <?php if (!empty($item->sample_answer_file)): ?>
            <div class="mt-3"><a href="<?php echo base_url((string) $item->sample_answer_file); ?>" target="_blank">Open sample answer file</a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($item->guide_text) || !empty($item->guide_file)): ?>
  <div class="modal fade" id="guideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Guide</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($item->guide_text)): ?>
            <div style="white-space:pre-wrap;"><?php echo html_escape((string) $item->guide_text); ?></div>
          <?php endif; ?>
          <?php if (!empty($item->guide_file)): ?>
            <div class="mt-3"><a href="<?php echo base_url((string) $item->guide_file); ?>" target="_blank">Open guide file</a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include viewPath('user/includes/footer'); ?>

<script>
(function(){
  var timerEl = document.getElementById('emarkingTimer');
  if (!timerEl) return;
  var seconds = parseInt(timerEl.getAttribute('data-seconds') || '15', 10);
  if (isNaN(seconds) || seconds < 0) seconds = 15;

  var buttons = Array.prototype.slice.call(document.querySelectorAll('.emarking-action-btn'));
  var obtainedEl = document.getElementById('emarkingObtained');

  function clamp(val, min, max) {
    val = parseFloat(val);
    if (isNaN(val)) val = 0;
    if (val < min) return min;
    if (val > max) return max;
    return val;
  }

  function computeObtained() {
    var total = 0;
    var cards = Array.prototype.slice.call(document.querySelectorAll('.rubric-card[data-step-id]'));
    cards.forEach(function(card){
      if (!card) return;
      var type = (card.getAttribute('data-type') || '').toUpperCase();
      var stepMarks = parseFloat(card.getAttribute('data-step-marks') || '0') || 0;
      var min = parseFloat(card.getAttribute('data-min') || '0') || 0;
      var max = parseFloat(card.getAttribute('data-max') || '0') || 0;

      if (type === 'FIXED') {
        total += stepMarks;
        return;
      }
      if (type === 'RANGE') {
        var inp = card.querySelector('input[name^=\"steps[\"]');
        if (!inp) return;
        total += clamp(inp.value, min, max);
        return;
      }
      // ZERO_ONE
      var checked = card.querySelector('input[type=\"radio\"][name^=\"steps[\"]:checked');
      if (!checked) return;
      if ((checked.value || '') === '1') total += stepMarks;
    });

    if (obtainedEl) obtainedEl.textContent = total.toFixed(2);
  }

  function setButtonsEnabled(enabled){
    buttons.forEach(function(b){
      if (!b) return;
      b.disabled = !enabled;
    });
  }

  // Start disabled, enable immediately if 0 seconds
  setButtonsEnabled(false);
  computeObtained();
  document.addEventListener('change', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    if (t.classList && t.classList.contains('emarking-step-input')) computeObtained();
  });
  document.addEventListener('input', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    if (t.classList && t.classList.contains('emarking-step-input')) computeObtained();
  });

  if (seconds <= 0) {
    timerEl.textContent = '0s';
    setButtonsEnabled(true);
    return;
  }

  function render(){
    timerEl.textContent = seconds + 's';
  }
  render();

  var interval = setInterval(function(){
    seconds -= 1;
    if (seconds <= 0) {
      seconds = 0;
      render();
      setButtonsEnabled(true);
      clearInterval(interval);
      return;
    }
    render();
  }, 1000);
})();
</script>

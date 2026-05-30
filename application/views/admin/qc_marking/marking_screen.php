<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

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
$is_urdu_subject = ((string) ($item->subject_code ?? '') === '2');
$rubric_title = trim((string) ($item->rubric_title ?? ''));
$panel_heading = $rubric_title !== '' ? $rubric_title : '';
?>

<style>
  .emarking-topbar { background:#0b62d6; color:#fff; padding:10px 0; }
  .emarking-topbar a { color:#fff; }

  /* Left question header (match reference screenshot) */
  .emarking-qbar { background:#0b62d6; border-radius:12px; padding:10px 12px; color:#fff; box-shadow:0 6px 14px rgba(11, 98, 214, .25); }
  .emarking-qbar-row { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .emarking-qpill { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25); font-weight:700; font-size:12px; }
  .emarking-qpill-muted { opacity:.95; font-weight:700; }
  .emarking-qtitle { margin-top:10px; background:#0b62d6; border-radius:12px; padding:12px 14px; color:#fff; box-shadow:0 6px 14px rgba(11, 98, 214, .25); font-weight:600; }

  /* Right-side marking panel only */
  .emarking-panel { border:1px solid #dfe3ea; border-radius:10px; box-shadow:0 10px 24px rgba(16, 24, 40, .10); overflow:hidden; background:#fff; }
  .emarking-panel .card-header { background:#fff; border-bottom:1px solid #eef2f7; padding:12px 14px; }
  .emarking-panel-title { font-size:14px; font-weight:700; color:#111827; margin:0; }
  /* Urdu font sizing: keep global urdufont-right default, but slightly smaller inside the marking panel */
  .emarking-panel .urdufont-right { font-size: 18px; }
  /* Ensure Urdu heading in the right marking panel is truly right-aligned (don't rely on Bootstrap utility class names). */
  #emarkingPanelTitle.urdufont-right { width:100%; text-align:right; }
  .rubric-detail { margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#f8fafc; color:#111827; font-size:13px; line-height:1.5; }
  .emarking-panel-body { padding:12px 14px; max-height:52vh; overflow:auto; }
  .emarking-panel-body::-webkit-scrollbar { width:8px; }
  .emarking-panel-body::-webkit-scrollbar-thumb { background:#d7dde6; border-radius:8px; }

  .rubric-card { border:0; padding:0; margin-bottom:12px; }
  .rubric-title { font-weight:700; font-size:12px; color:#6b7280; margin-bottom:8px; }

  .emarking-combined-wrap { margin-bottom:12px; }
  .emarking-combined-title { font-weight:700; font-size:14px; color:#111827; margin:0 0 10px 0; }
  .emarking-combined-sub { font-size:12px; color:#6b7280; margin-top:-6px; margin-bottom:10px; }

  .emarking-box-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; }
  @media (max-width: 1199.98px) { .emarking-box-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 575.98px) { .emarking-box-grid { grid-template-columns:1fr; } }

  .emarking-box { border:1px solid #dfe3ea; border-radius:10px; background:#fff; padding:8px 10px; min-height:56px; cursor:pointer; transition:background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
  .emarking-box:hover { box-shadow:0 8px 16px rgba(16, 24, 40, .08); transform:translateY(-1px); }
  .emarking-box.is-selected { border-color:#0d6efd; background:#eef4ff; }
  .emarking-box-label { font-size:11px; font-weight:600; color:#6b7280; line-height:1.2; width:100%; }
  .emarking-box-value { display:flex; align-items:center; justify-content:center; margin-top:8px; width:100%; }
  .emarking-box-dot { width:24px; height:24px; border-radius:999px; border:2px solid #cbd5e1; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#111827; background:#fff; }
  .emarking-box.is-selected .emarking-box-dot { border-color:#0d6efd; background:#eef4ff; }

  .emarking-hide-input { position:absolute !important; left:-99999px !important; width:1px !important; height:1px !important; overflow:hidden !important; }

  .emarking-remarks-label { font-weight:800; font-size:11px; color:#6b7280; letter-spacing:.6px; margin-bottom:6px; }
  .emarking-remarks textarea.form-control { border-radius:10px; }

  .emarking-panel .card-footer { background:#fff; border-top:1px solid #eef2f7; padding:12px 14px; position:sticky; bottom:0; }
  .emarking-summary { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
  .emarking-summary-left { display:flex; flex-direction:column; gap:6px; }
  .emarking-summary-right { display:flex; flex-direction:column; gap:6px; align-items:flex-end; margin-left:auto; }
  .emarking-timer { font-weight:800; color:#111827; }
  .emarking-max { font-weight:800; color:#111827; }
  .emarking-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px; }
  .emarking-actions-left { display:flex; gap:8px; flex-wrap:wrap; }
  .emarking-actions-right { margin-left:auto; }
  .emarking-action-btn { border-radius:10px; padding:8px 14px; min-height:36px; font-weight:600; }
  .emarking-submit { background:#0d6efd; border-color:#0d6efd; color:#fff; }
  .emarking-submit:hover { background:#0b5ed7; border-color:#0b5ed7; color:#fff; }
  .emarking-links { display:flex; gap:14px; flex-wrap:wrap; align-items:center; font-size:12px; margin-top:8px; }
  .emarking-linkbtn { padding:0; border:0; background:transparent; color:#0d6efd; font-weight:600; }
  .emarking-linkbtn:hover { text-decoration:underline; color:#0b5ed7; }

  /* Combined view hides per-step blocks (inputs remain) */
  .emarking-panel.is-combined .rubric-card { display:none; }
</style>

<div class="emarking-topbar">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong>Exam Management System</strong> | <strong>PECTAA 2026</strong> |
        <a href="<?php echo base_url('admin/qc_marking/my'); ?>">QC Dashboard</a>
      </div>
      <div>
        Subject Specialist: <strong><?php echo html_escape((string) logged('name')); ?></strong>
      </div>
    </div>
  </div>
</div>

<section class="content" style="padding-top:12px;">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="row">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <?php
              $left_max_marks_val = (float) ($item->max_marks ?? 0);
              $left_max_marks_txt = (abs($left_max_marks_val - (int) $left_max_marks_val) < 0.000001) ? (string) ((int) $left_max_marks_val) : rtrim(rtrim(number_format($left_max_marks_val, 2, '.', ''), '0'), '.');
              $left_obtained_val = (float) (($mark->marks_obtained ?? 0));
              if ($left_obtained_val < 0) $left_obtained_val = 0;
              $left_obtained_txt = (abs($left_obtained_val - (int) $left_obtained_val) < 0.000001) ? (string) ((int) $left_obtained_val) : rtrim(rtrim(number_format($left_obtained_val, 2, '.', ''), '0'), '.');
            ?>
            <div class="emarking-qbar">
              <div class="emarking-qbar-row">
                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                  <span class="emarking-qpill"><?php echo html_escape((string) $item->question_no); ?></span>
                  <span id="emarkingLeftScore" class="emarking-qpill emarking-qpill-muted" data-max="<?php echo html_escape($left_max_marks_txt); ?>"><?php echo html_escape($left_obtained_txt); ?>/<?php echo html_escape($left_max_marks_txt); ?></span>
                </div>
                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                  <span class="emarking-qpill"><?php echo (int) $idx; ?>/<?php echo (int) $total; ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="emarking-qtitle <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>" style="white-space:pre-wrap;"><?php echo html_escape((string) $item->question_title); ?></div>
            </div>

            <div class="mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="sr-only">Student Cropped Answer Image</div>
              </div>
              <div style="border:1px solid #e5e5e5; padding:8px; background:#fafafa;">
                <img src="<?php echo $image_url; ?>" alt="Answer Image" style="width:100%; height:auto;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <form method="post" action="<?php echo base_url('admin/qc_marking/save_marks'); ?>">
          <input type="hidden" name="batch_id" value="<?php echo (int) $item->batch_id; ?>">
          <input type="hidden" name="batch_item_id" value="<?php echo (int) $item->id; ?>">

          <?php
            $qt = trim((string) ($item->question_type ?? ''));
            $qtLabel = $qt !== '' ? ucwords(strtolower(str_replace(['_', '-'], ' ', $qt))) : 'Rubric';
            if (strtolower($qtLabel) === 'objective steps') $qtLabel = 'Answer Evaluation';
            $maxMarksLabel = htmlspecialchars((string) ((float) ($item->max_marks ?? 0)));
            // Rubric title overrides the panel heading when provided (configured on question edit screen).
            $panelHeading = $rubric_title !== '' ? $rubric_title : $qtLabel;
          ?>
          <?php
            // In combined mode, the option value represents "number of correct steps",
            // not total marks. This supports fractional step marks (e.g. 0.5 each).
            $combined_max = !empty($steps) ? count($steps) : 0;
            $combined_prefix = [0 => 0.0];
            if (!empty($steps)) {
              $running = 0.0;
              $i = 0;
              foreach ($steps as $cs) {
                $i++;
                $running += (float) ($cs->step_marks ?? 0);
                $combined_prefix[$i] = $running;
              }
            }
            $is_combined_supported = true;
            if (!empty($steps)) {
              foreach ($steps as $cs) {
                if ((string) $cs->marking_type !== 'ZERO_ONE') { $is_combined_supported = false; break; }
              }
            } else {
              $is_combined_supported = false;
            }
            if ($combined_max <= 0) $is_combined_supported = false;
          ?>
          <div class="card emarking-panel <?php echo $is_combined_supported ? 'is-combined' : ''; ?>" data-combined-max="<?php echo (int) $combined_max; ?>">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h3
                  class="emarking-panel-title <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>"
                  id="emarkingPanelTitle"
                  data-max="<?php echo $maxMarksLabel; ?>"
                >
                  <?php echo html_escape($panelHeading); ?> (0/<?php echo $maxMarksLabel; ?>)
                </h3>
              </div>
            </div>

            <div class="emarking-panel-body">
              <?php if (!empty($item->rubric_detail)): ?>
                <div class="rubric-detail <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>">
                  <?php echo nl2br(html_escape((string) $item->rubric_detail)); ?>
                </div>
              <?php endif; ?>

              <?php if ($is_combined_supported): ?>
                <div class="emarking-combined-wrap">
                  <div class="emarking-combined-title">Combined Marking</div>
                  <div class="emarking-combined-sub">Select number of correct steps.</div>
                  <div class="emarking-box-grid" id="emarkingCombinedGrid" data-mark-grid data-combined="1">
                    <?php for ($i = 0; $i <= (int) $combined_max; $i++): ?>
                      <?php
                        $score = (float) ($combined_prefix[$i] ?? 0.0);
                        $scoreTxt = (abs($score - (int) $score) < 0.000001) ? (string) ((int) $score) : rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');
                      ?>
                      <button type="button" class="emarking-box" data-mark-option data-value="<?php echo (int) $i; ?>" aria-pressed="false">
                        <div class="emarking-box-label"><?php echo (int) $i; ?> correct</div>
                        <div class="emarking-box-value"><span class="emarking-box-dot"><?php echo html_escape($scoreTxt); ?></span></div>
                      </button>
                    <?php endfor; ?>
                    <input type="radio" class="emarking-hide-input" name="combined_correct_count" value="" checked>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (empty($steps)): ?>
                <div class="text-muted">No rubric steps configured for this question.</div>
              <?php else: ?>
                <?php foreach ($steps as $s): ?>
                  <?php
                    $stype = strtoupper((string) ($s->marking_type ?? 'ZERO_ONE'));
                    $stepMarks = (float) ($s->step_marks ?? 0);
                    $stepMarksTxt = (abs($stepMarks - (int) $stepMarks) < 0.000001) ? (string) ((int) $stepMarks) : rtrim(rtrim(number_format($stepMarks, 2, '.', ''), '0'), '.');
                    $min = (float) ($s->min_marks ?? 0);
                    $max = (float) ($s->max_marks ?? 0);
                    $val = '';
                    foreach ($mark_steps as $ms) {
                      if ((int) ($ms->rubric_step_id ?? 0) === (int) ($s->id ?? 0)) { $val = (string) ($ms->selected_value ?? ''); break; }
                    }
                  ?>
                  <div class="rubric-card" data-step-id="<?php echo (int) ($s->id ?? 0); ?>" data-type="<?php echo html_escape($stype); ?>" data-step-marks="<?php echo html_escape($stepMarksTxt); ?>" data-min="<?php echo html_escape((string) $min); ?>" data-max="<?php echo html_escape((string) $max); ?>">
                    <div class="rubric-title <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>"><?php echo html_escape((string) ($s->step_title ?? '')); ?></div>
                    <?php if ($stype === 'RANGE'): ?>
                      <input type="number" step="0.01" class="form-control emarking-step-input" name="steps[<?php echo (int) ($s->id ?? 0); ?>]" value="<?php echo html_escape($val); ?>" placeholder="Enter marks">
                      <small class="text-muted">Allowed: <?php echo html_escape((string) $min); ?> - <?php echo html_escape((string) $max); ?></small>
                    <?php elseif ($stype === 'FIXED'): ?>
                      <div class="text-muted">Fixed marks: <?php echo html_escape($stepMarksTxt); ?></div>
                      <input type="hidden" class="emarking-step-input" name="steps[<?php echo (int) ($s->id ?? 0); ?>]" value="1">
                    <?php else: ?>
                      <div class="d-flex" style="gap:10px;">
                        <label class="mr-3"><input type="radio" class="emarking-step-input" name="steps[<?php echo (int) ($s->id ?? 0); ?>]" value="1" <?php echo ($val === '1') ? 'checked' : ''; ?>> Correct</label>
                        <label><input type="radio" class="emarking-step-input" name="steps[<?php echo (int) ($s->id ?? 0); ?>]" value="0" <?php echo ($val === '0') ? 'checked' : ''; ?>> Wrong</label>
                      </div>
                      <small class="text-muted">Marks: <?php echo html_escape($stepMarksTxt); ?></small>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <div class="emarking-remarks mt-2">
                <div class="emarking-remarks-label">REMARKS</div>
                <textarea class="form-control" name="remarks" rows="2" placeholder="Optional"><?php echo html_escape((string) ($mark->remarks ?? '')); ?></textarea>
              </div>
            </div>

            <div class="card-footer">
              <div class="emarking-summary">
                <div class="emarking-summary-left">
                  <div class="emarking-max">Max Marks: <span><?php echo html_escape($maxMarksLabel); ?></span></div>
                </div>
                <div class="emarking-summary-right">
                  <div class="emarking-timer">Timer: <span id="emarkingTimer"><?php echo (int) $timer_seconds; ?>s</span></div>
                  <div><strong>Obtained:</strong> <span id="emarkingObtained">0</span></div>
                </div>
              </div>

              <div class="emarking-actions">
                <div class="emarking-actions-left">
                  <button type="submit" name="action" value="MARKED" class="btn btn-primary emarking-action-btn emarking-submit js-marking-btn">Save</button>
                  <button type="submit" name="action" value="SKIPPED" class="btn btn-outline-secondary emarking-action-btn js-marking-btn">Skip</button>
                  <button type="submit" name="action" value="NOT_ATTEMPTED" class="btn btn-outline-secondary emarking-action-btn js-marking-btn">Not Attempted</button>
                  <button type="submit" name="action" value="RECHECK" class="btn btn-outline-secondary emarking-action-btn js-marking-btn">Recheck</button>
                </div>
                <div class="emarking-actions-right">
                  <a class="btn btn-outline-secondary emarking-action-btn" href="<?php echo base_url('admin/qc_marking/view_batch/' . (int) $item->batch_id); ?>">Back</a>
                </div>
              </div>

              <div class="emarking-links">
                <a class="emarking-linkbtn" href="<?php echo base_url('admin/qc_marking/my'); ?>">My QC Batches</a>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
(function(){
  var seconds = parseInt('<?php echo (int) $timer_seconds; ?>', 10) || 0;
  var timerEl = document.getElementById('emarkingTimer');
  var buttons = Array.prototype.slice.call(document.querySelectorAll('.js-marking-btn'));
  var obtainedEl = document.getElementById('emarkingObtained');
  var panelTitleEl = document.getElementById('emarkingPanelTitle');
  var combinedGridEl = document.getElementById('emarkingCombinedGrid');
  var leftScoreEl = document.getElementById('emarkingLeftScore');
  var anyChosen = false;

  function clamp(val, min, max) {
    val = parseFloat(val);
    if (isNaN(val)) val = 0;
    if (val < min) return min;
    if (val > max) return max;
    return val;
  }

  function closest(el, selector) {
    while (el && el.nodeType === 1) {
      if (el.matches(selector)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function syncGrid(gridEl){
    if (!gridEl) return;
    var radios = Array.prototype.slice.call(gridEl.querySelectorAll('input[type="radio"][name^="steps["]'));
    var chosen = 0;
    radios.forEach(function(r){ if (r && r.checked && (r.value||'')==='1') chosen += 1; });
    Array.prototype.slice.call(gridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
      var isSel = String(o.getAttribute('data-value')||'') === String(chosen);
      o.classList.toggle('is-selected', isSel);
      o.setAttribute('aria-pressed', isSel ? 'true' : 'false');
    });
  }

  function computeObtained() {
    var total = 0;
    var correctCount = 0;
    anyChosen = false;
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
        var inp = card.querySelector('input[name^="steps["]');
        if (!inp) return;
        if (String(inp.value || '').trim() !== '') anyChosen = true;
        total += clamp(inp.value, min, max);
        return;
      }
      // ZERO_ONE
      var checked = card.querySelector('input[type="radio"][name^="steps["]:checked');
      if (!checked) return;
      anyChosen = true;
      if ((checked.value || '') === '1') {
        total += stepMarks;
        correctCount += 1;
      }
    });

    if (obtainedEl) {
      var shownObt = total;
      if (Math.abs(shownObt - Math.round(shownObt)) < 1e-6) {
        obtainedEl.textContent = String(Math.round(shownObt));
      } else {
        obtainedEl.textContent = shownObt.toFixed(2);
      }
    }

    if (leftScoreEl) {
      var leftMax = (leftScoreEl.getAttribute('data-max') || '').trim();
      var leftShown = total;
      if (Math.abs(leftShown - Math.round(leftShown)) < 1e-6) leftShown = Math.round(leftShown);
      else leftShown = leftShown.toFixed(2);
      leftScoreEl.textContent = leftShown + '/' + leftMax;
    }

    if (panelTitleEl) {
      var maxMarks = parseFloat(panelTitleEl.getAttribute('data-max') || '0') || 0;
      var shown = total;
      if (Math.abs(shown - Math.round(shown)) < 1e-6) shown = Math.round(shown);
      var maxShown = maxMarks;
      if (Math.abs(maxShown - Math.round(maxShown)) < 1e-6) maxShown = Math.round(maxShown);
      var baseText = (panelTitleEl.textContent || '').replace(/\(\s*.*?\s*\)\s*$/, '').trim();
      panelTitleEl.textContent = baseText + ' (' + shown + '/' + maxShown + ')';
    }

    if (combinedGridEl) {
      if (!anyChosen) {
        Array.prototype.slice.call(combinedGridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
          o.classList.remove('is-selected');
          o.setAttribute('aria-pressed', 'false');
        });
      } else {
        var v = correctCount;
        if (v < 0) v = 0;
        Array.prototype.slice.call(combinedGridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
          var isSel = String(o.getAttribute('data-value') || '') === String(v);
          o.classList.toggle('is-selected', isSel);
          o.setAttribute('aria-pressed', isSel ? 'true' : 'false');
        });
      }
    }
  }

  function setButtonsEnabled(enabled){
    buttons.forEach(function(b){
      if (!b) return;
      b.disabled = !enabled;
    });
  }

  setButtonsEnabled(false);
  computeObtained();
  Array.prototype.slice.call(document.querySelectorAll('[data-mark-grid]')).forEach(function(g){ syncGrid(g); });

  if (combinedGridEl) {
    var formEl = closest(combinedGridEl, 'form');
    if (formEl) {
      formEl.addEventListener('submit', function(){
        if (anyChosen) return;
        var zeroOpt = combinedGridEl.querySelector('[data-mark-option][data-value="0"]');
        if (zeroOpt) zeroOpt.click();
      });
    }
    combinedGridEl.addEventListener('click', function(e){
      var t = e.target;
      while (t && t !== combinedGridEl && !t.hasAttribute('data-mark-option')) t = t.parentElement;
      if (!t || !t.hasAttribute('data-mark-option')) return;
      var val = String(t.getAttribute('data-value') || '');
      Array.prototype.slice.call(combinedGridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
        var isSel = String(o.getAttribute('data-value') || '') === val;
        o.classList.toggle('is-selected', isSel);
        o.setAttribute('aria-pressed', isSel ? 'true' : 'false');
      });
      // In combined mode we just update visual; actual rubric radios still drive computed marks.
    });
  }

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
    if (timerEl) timerEl.textContent = '0s';
    setButtonsEnabled(true);
    return;
  }

  function render(){
    if (timerEl) timerEl.textContent = seconds + 's';
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


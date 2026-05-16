(function ($) {
	'use strict';

	var pendingSubmit = null;

	function toast(type, msg) {
		if (window.toastr) {
			toastr.options = {
				closeButton: true,
				progressBar: true,
				timeOut: 4000
			};
			toastr[type](msg);
			return;
		}
		alert(msg);
	}

	function clearValidation($form) {
		$form.find('.is-invalid').removeClass('is-invalid');
		$form.find('.js-dyn-feedback').remove();
		$form.find('.invalid-feedback').not('.js-dyn-feedback').hide();
		$form.find('.js-upload-box.is-invalid').removeClass('is-invalid');
	}

	function applyValidation($form, errors) {
		if (!errors) return;
		Object.keys(errors).forEach(function (name) {
			var msg = errors[name];
			var $el = $form.find('[name="' + name + '"]');
			if ($el.length === 0) {
				// fall back for array fields
				$el = $form.find('[name="' + name + '[]"]');
			}
			if ($el.length) {
				$el.addClass('is-invalid');
				var $target = $el.last();
				$target.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">' + msg + '</div>');
			}
		});
	}

	function validateRequiredInline($form) {
		var ok = true;
		$form.find(':input[required]').each(function () {
			var el = this;
			var $el = $(el);
			if ($el.is(':disabled')) return;
			// hidden inputs are not visible; only validate visible required fields here
			if (!$el.is(':visible')) return;

			if (typeof el.checkValidity === 'function' && !el.checkValidity()) {
				ok = false;
				$el.addClass('is-invalid');
				var $fb = $el.siblings('.invalid-feedback').first();
				if ($fb.length) {
					$fb.show();
				} else {
					$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">This field is required.</div>');
				}
				return;
			}

			var val = ($el.val() || '').toString().trim();
			if (val === '') {
				ok = false;
				$el.addClass('is-invalid');
				var $fb2 = $el.siblings('.invalid-feedback').first();
				if ($fb2.length) {
					$fb2.show();
				} else {
					$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">This field is required.</div>');
				}
			}
		});

		// Required uploads (hidden path inputs)
		$form.find('.js-required-upload').each(function () {
			var $path = $(this);
			var groupVisible = $path.closest('.form-group').is(':visible');
			if (!groupVisible) return;
			var v = ($path.val() || '').toString().trim();
			if (v === '') {
				ok = false;
				var $group = $path.closest('.form-group');
				$group.find('.js-upload-box').addClass('is-invalid');
				var label = $path.data('label') || 'Upload';
				var $meta = $group.find('.js-upload-meta').first();
				if ($meta.length) {
					$meta.html('<span class="label">File name:</span> <span class="text-danger">Required (' + label + ')</span>');
				}
			}
		});

		return ok;
	}

	function uploadFile($input) {
		var file = $input[0].files && $input[0].files[0] ? $input[0].files[0] : null;
		if (!file) return;

		if (file.size > 5 * 1024 * 1024) {
			toast('error', 'Max upload size is 5MB.');
			$input.val('');
			return;
		}

		var $group = $input.closest('.form-group');
		var $path = $group.find('.js-upload-path').first();
		var $meta = $group.find('.js-upload-meta').first();
		var field = $group.find('.js-upload-box').data('field') || 'file';

		var fd = new FormData();
		fd.append('file', file);
		fd.append('field', field);

		$meta.html('<span class="label">File name:</span> Uploading...');

		$.ajax({
			url: window.SIGNUP_WIZARD && window.SIGNUP_WIZARD.uploadUrl ? window.SIGNUP_WIZARD.uploadUrl : '',
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function (res) {
				if (!res || !res.success) {
					toast('error', (res && res.message) ? res.message : 'Upload failed.');
					$meta.html('<span class="label">File name:</span> -');
					return;
				}
				$path.val(res.file_path || '');
				$group.find('.js-upload-box').removeClass('is-invalid');
				$meta.html('<span class="label">File name:</span> ' + (res.file_name || file.name));
				var $rm = $group.find('.js-remove-upload').first();
				if ($rm.length) $rm.show();
				toast('success', 'File uploaded.');
			},
			error: function (xhr) {
				var msg = 'Upload failed.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
				toast('error', msg);
				$meta.html('<span class="label">File name:</span> -');
			}
		});
	}

	function submitCurrentStep($btn) {
		var $form = $('.signup-step-form').first();
		if ($form.length === 0) return;

		clearValidation($form);

		// Normalize experience currently_working to positional array to avoid index/key mismatch after add/remove.
		var stepNow = window.SIGNUP_WIZARD ? parseInt(window.SIGNUP_WIZARD.step, 10) : 0;
		if (stepNow === 4) {
			$form.find('.js-currently-pos').remove();
			$form.find('.js-repeat-wrap[data-repeat="experience"] .js-repeat-item').each(function () {
				var $item = $(this);
				var checked = $item.find('.js-currently-working').is(':checked') ? '1' : '0';
				$form.append('<input type="hidden" class="js-currently-pos" name="currently_working_pos[]" value="' + checked + '">');
			});
		}

		var action = $btn.data('action');
		var fd = new FormData($form[0]);

		$btn.prop('disabled', true).text('Please wait...');

		$.ajax({
			url: action,
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function (res) {
				$btn.prop('disabled', false);
				$btn.text($btn.data('final') === 1 || $btn.data('final') === '1' ? 'Signup' : 'Save & Next');

				if (!res || !res.success) {
					toast('error', (res && res.message) ? res.message : 'Save failed.');
					if (res && res.errors) applyValidation($form, res.errors);
					return;
				}

				// Final signup success modal
				if (res.completed) {
					var loginUrl = res.login_url || (window.SIGNUP_WIZARD ? window.SIGNUP_WIZARD.loginUrl : '');
					if (window.Swal && typeof Swal.fire === 'function') {
						Swal.fire({
							icon: 'success',
							title: 'Registration Successful',
							text: 'You have been registered successfully!',
							confirmButtonText: 'Go to Login',
							confirmButtonColor: '#16a34a',
							allowOutsideClick: false
						}).then(function () {
							if (loginUrl) window.location.href = loginUrl;
						});
						return;
					}
					if (loginUrl) window.location.href = loginUrl;
					return;
				}

				toast('success', res.message || 'Saved.');

				if (res.next_url) {
					window.location.href = res.next_url;
					return;
				}
			},
			error: function (xhr) {
				$btn.prop('disabled', false);
				$btn.text($btn.data('final') === 1 || $btn.data('final') === '1' ? 'Signup' : 'Save & Next');

				var res = xhr && xhr.responseJSON ? xhr.responseJSON : null;
				var msg = res && res.message ? res.message : 'Save failed.';
				toast('error', msg);
				if (res && res.errors) applyValidation($form, res.errors);
			}
		});
	}

	function validateCnicInline($input) {
		if ($input.length === 0) return true;
		var val = ($input.val() || '').trim();
		var ok = (/^\d{5}-\d{7}-\d$/).test(val);
		var $fb = $input.siblings('.invalid-feedback').first();
		if (!ok) {
			$input.addClass('is-invalid');
			if ($fb.length) $fb.show();
		} else {
			$input.removeClass('is-invalid');
			if ($fb.length) $fb.hide();
		}
		return ok;
	}

	function tryAutoResumeFromStep1() {
		var wiz = window.SIGNUP_WIZARD || {};
		if (parseInt(wiz.step, 10) !== 1) return;
		if (parseInt(wiz.userId, 10) > 0) return; // already in-session

		var $form = $('.signup-step-form').first();
		if ($form.length === 0) return;

		var $cnic = $form.find('.js-cnic').first();
		var $dob = $form.find('[name="dob"]').first();
		if ($cnic.length === 0 || $dob.length === 0) return;

		var cnicVal = ($cnic.val() || '').toString().trim();
		var dobVal = ($dob.val() || '').toString().trim();
		if (!cnicVal || !dobVal) return;

		if (!validateCnicInline($cnic)) return;
		if (!isValidYmd(dobVal)) return;

		var url = wiz.checkResumeUrl || '';
		if (!url) return;

		$.ajax({
			url: url,
			type: 'POST',
			data: { cnic: cnicVal, dob: dobVal },
			success: function (res) {
				if (!res || !res.success) return;
				if (!res.found) return;
				toast('success', res.message || 'Resumed registration.');
				if (res.resume_url) {
					window.location.href = res.resume_url;
				}
			},
			error: function () {}
		});
	}

	function normalizeIban(val) {
		return (val || '').toString().replace(/\s+/g, '').toUpperCase();
	}

	function validateIbanInline($input) {
		if ($input.length === 0) return true;
		var raw = $input.val();
		var norm = normalizeIban(raw);
		if (raw !== norm) $input.val(norm);
		var ok = (/^[A-Z0-9]{24}$/).test(norm);
		var $fb = $input.siblings('.invalid-feedback').first();
		if (!ok && norm !== '') {
			$input.addClass('is-invalid');
			if ($fb.length) $fb.show();
		} else if (ok) {
			$input.removeClass('is-invalid');
			if ($fb.length) $fb.hide();
		}
		return ok;
	}

	function nextIndex($wrap) {
		var max = -1;
		$wrap.find('.js-repeat-item').each(function () {
			var idx = parseInt($(this).attr('data-index'), 10);
			if (!isNaN(idx)) max = Math.max(max, idx);
		});
		return max + 1;
	}

	function addRepeat(type) {
		var tplId = '#tpl-' + type;
		var $tpl = $(tplId);
		if ($tpl.length === 0) return;

		var $wrap = $('.js-repeat-wrap[data-repeat="' + type + '"]').first();
		if ($wrap.length === 0) return;

		var idx = nextIndex($wrap);
		var html = $tpl.html().replace(/__INDEX__/g, String(idx));
		$wrap.append(html);
	}

	function wireExperienceItem($item) {
		var $cw = $item.find('.js-currently-working');
		var $end = $item.find('.js-end-date');
		if ($cw.is(':checked')) {
			$end.val('').prop('disabled', true);
		}
	}

	function markInvalid($el, message) {
		var $el = $($el);
		$el.addClass('is-invalid');
		var $fb = $el.siblings('.invalid-feedback').first();
		if ($fb.length) {
			$fb.text(message).show();
			return;
		}
		$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">' + message + '</div>');
	}

	function isValidYmd(value) {
		if (!value) return false;
		return (/^\d{4}-\d{2}-\d{2}$/).test(value);
	}

	function validateDateRange($start, $end, opts) {
		opts = opts || {};
		var startVal = ($start.val() || '').toString().trim();
		var endVal = ($end.val() || '').toString().trim();
		if (!startVal) return false;
		if (opts.endRequired && !endVal) return false;
		if (endVal && !isValidYmd(endVal)) return false;
		if (!isValidYmd(startVal)) return false;
		if (!endVal) return true;
		return (startVal <= endVal); // Y-m-d lexicographic compare works
	}

	function validateExperienceDates($form) {
		var ok = true;
		$form.find('.js-repeat-wrap[data-repeat="experience"] .js-repeat-item').each(function () {
			var $item = $(this);
			var $start = $item.find('.js-start-date').first();
			var $end = $item.find('.js-end-date').first();
			var isCurrently = $item.find('.js-currently-working').is(':checked');
			if ($start.length === 0 || $end.length === 0) return;
			if (isCurrently) return;
			var startVal = ($start.val() || '').toString().trim();
			var endVal = ($end.val() || '').toString().trim();
			if (!startVal) return;

			// End is required when not currently working
			var endRequired = true;
			if (!endVal) {
				ok = false;
				markInvalid($end, 'End Date is required unless Currently Working is checked.');
				return;
			}
			if (!validateDateRange($start, $end, { endRequired: endRequired })) {
				ok = false;
				markInvalid($end, 'End Date must be greater than or equal to Start Date.');
			}
		});
		return ok;
	}

	function validateEmarkingDates($form) {
		var ok = true;
		$form.find('.js-repeat-wrap[data-repeat="emarking"] .js-repeat-item').each(function () {
			var $item = $(this);
			var $from = $item.find('.js-from-date').first();
			var $to = $item.find('.js-to-date').first();
			if ($from.length === 0 || $to.length === 0) return;
			var fromVal = ($from.val() || '').toString().trim();
			var toVal = ($to.val() || '').toString().trim();
			if (!fromVal || !toVal) return;
			if (!validateDateRange($from, $to, { endRequired: true })) {
				ok = false;
				markInvalid($to, 'To date must be greater than or equal to From date.');
			}
		});
		return ok;
	}

	$(function () {
		// CNIC mask + inline validation (step 1)
		if ($.fn.inputmask) {
			$('.js-cnic').inputmask('99999-9999999-9', { placeholder: '_____-_______-_' });
		}
		$(document).on('keyup blur change', '.js-cnic', function (e) {
			validateCnicInline($(this));
			// Only try auto-resume when leaving CNIC with both valid values present
			if (e && e.type === 'blur') tryAutoResumeFromStep1();
		});
		$(document).on('blur', '.signup-step-form [name="dob"]', function () {
			tryAutoResumeFromStep1();
		});

		// Clear inline validation once corrected
		$(document).on('input change blur', '.signup-step-form :input', function () {
			var $el = $(this);
			if ($el.is(':disabled')) return;
			if (!$el.hasClass('is-invalid')) return;

			// CNIC uses its own validator
			if ($el.hasClass('js-cnic')) return;
			// IBAN uses its own validator
			if ($el.hasClass('js-iban')) {
				validateIbanInline($el);
				return;
			}

			var el = this;
			var valid = true;
			if (typeof el.checkValidity === 'function') {
				valid = el.checkValidity();
			} else {
				var val = ($el.val() || '').toString().trim();
				valid = (val !== '');
			}
			if (!valid) return;

			$el.removeClass('is-invalid');
			$el.siblings('.invalid-feedback').hide();
			$el.siblings('.js-dyn-feedback').remove();
			// Also remove immediate next dynamic feedback if inserted after element
			if ($el.next('.js-dyn-feedback').length) $el.next('.js-dyn-feedback').remove();
		});

		// IBAN normalize + inline validation (step 5)
		$(document).on('input blur change', '.js-iban', function () {
			validateIbanInline($(this));
		});

		// Upload widget
		$(document).on('click', '.js-upload-box', function (e) {
			// Prevent recursion when the file input click bubbles back to the box.
			if ($(e.target).hasClass('js-upload-input')) return;
			e.preventDefault();
			e.stopPropagation();
			var input = $(this).find('.js-upload-input').get(0);
			if (input) input.click();
		});
		$(document).on('change', '.js-upload-input', function () {
			uploadFile($(this));
		});

		// Remove uploaded file
		$(document).on('click', '.js-remove-upload', function (e) {
			e.preventDefault();
			var $group = $(this).closest('.form-group');
			var $path = $group.find('.js-upload-path').first();
			var existing = ($path.val() || '').toString().trim();

			$path.val('');
			$group.find('.js-upload-box').removeClass('is-invalid');
			$group.find('.js-upload-input').val('');
			var $meta = $group.find('.js-upload-meta').first();
			if ($meta.length) {
				$meta.find('.text-danger').remove();
				$meta.html('<span class="label">File name:</span> - <span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>');
			}

			var delUrl = window.SIGNUP_WIZARD && window.SIGNUP_WIZARD.deleteUrl ? window.SIGNUP_WIZARD.deleteUrl : '';
			if (existing && delUrl) {
				$.ajax({
					url: delUrl,
					type: 'POST',
					data: { file_path: existing },
					success: function () {},
					error: function () {}
				});
			}
		});

		// Repeatables
		$(document).on('click', '.js-add-more', function () {
			var type = $(this).data('repeat');
			addRepeat(type);
		});
		$(document).on('click', '.js-remove-item', function () {
			$(this).closest('.js-repeat-item').remove();
		});

		// Experience rules
		$(document).on('change', '.js-no-experience', function () {
			$('.js-experience-wrap').toggle(!$(this).is(':checked'));
		});
		$(document).on('change', '.js-currently-working', function () {
			var $item = $(this).closest('.js-repeat-item');
			var $end = $item.find('.js-end-date');
			if ($(this).is(':checked')) {
				$end.val('').prop('disabled', true);
				$end.removeClass('is-invalid');
				$end.siblings('.invalid-feedback').hide();
				if ($end.next('.js-dyn-feedback').length) $end.next('.js-dyn-feedback').remove();
			} else {
				$end.prop('disabled', false);
			}
		});
		$(document).on('change blur', '.js-start-date, .js-end-date', function () {
			var $item = $(this).closest('.js-repeat-item');
			var $start = $item.find('.js-start-date').first();
			var $end = $item.find('.js-end-date').first();
			if ($start.length && $end.length) {
				var startVal = ($start.val() || '').toString().trim();
				if (startVal) $end.attr('min', startVal);
			}
		});
		$(document).on('change', '.js-sector', function () {
			var $item = $(this).closest('.js-repeat-item');
			var isGov = $(this).val() === 'Government';
			$item.find('.js-bps-wrap').toggle(isGov);
			if (!isGov) $item.find('.js-bps').val('');
		});
		$('.js-repeat-item').each(function () { wireExperienceItem($(this)); });

		// Emarking rules
		$(document).on('change', '.js-no-emarking', function () {
			$('.js-emarking-wrap').toggle(!$(this).is(':checked'));
		});
		$(document).on('change blur', '.js-from-date, .js-to-date', function () {
			var $item = $(this).closest('.js-repeat-item');
			var $from = $item.find('.js-from-date').first();
			var $to = $item.find('.js-to-date').first();
			if ($from.length && $to.length) {
				var fromVal = ($from.val() || '').toString().trim();
				if (fromVal) $to.attr('min', fromVal);
			}
		});

		// Save confirmation modal
		$(document).on('click', '.js-save-next', function () {
			var $form = $('.signup-step-form').first();
			if ($form.length) {
				clearValidation($form);

				// Step-specific toggles affect required rules
				var step = window.SIGNUP_WIZARD ? parseInt(window.SIGNUP_WIZARD.step, 10) : 0;
				if (step === 4 && $('.js-no-experience').is(':checked')) {
					// skip validation when section hidden
				} else if (step === 8 && $('.js-no-emarking').is(':checked')) {
					// skip validation when section hidden
				}

				if (!validateRequiredInline($form)) {
					toast('error', 'Please fill all required fields.');
					return;
				}

				if (step === 4 && !$('.js-no-experience').is(':checked')) {
					if (!validateExperienceDates($form)) {
						toast('error', 'Please correct your experience dates.');
						return;
					}
				}
				if (step === 8 && !$('.js-no-emarking').is(':checked')) {
					if (!validateEmarkingDates($form)) {
						toast('error', 'Please correct your e-marking dates.');
						return;
					}
				}

				if (step === 1) {
					var $cnic = $('.js-cnic').first();
					if ($cnic.length && !validateCnicInline($cnic)) {
						toast('error', 'Please enter valid CNIC.');
						return;
					}
				}
				if (step === 5) {
					var $iban = $('.js-iban').first();
					if ($iban.length && !validateIbanInline($iban)) {
						toast('error', 'Please enter a valid 24-character IBAN.');
						return;
					}
				}
			}

			pendingSubmit = $(this);
			$('#confirmMoveModal').modal('show');
		});
		$(document).on('click', '.js-confirm-yes', function () {
			$('#confirmMoveModal').modal('hide');
			if (pendingSubmit) submitCurrentStep(pendingSubmit);
			pendingSubmit = null;
		});
		$(document).on('click', '.js-confirm-cancel', function () {
			pendingSubmit = null;
		});
	});
})(jQuery);

/**
 * Plugin Template frontend js.
 *
 *  @package WordPress Plugin Template/JS
 */


jQuery(document).ready(
	function ($) {

		$(document).on('click', 'form input[type="submit"].vv-submit', function (e) {

			var $form = $(this).closest("form");
			var eL = $form;
			var error_container = false;
			var valid = eL[0].checkValidity();

			if (valid) {

				eL.submit(function () {
					$form.css('opacity', '.5').css('pointer-events', 'none');
					jQuery('.vv-field-group input', eL).removeClass('vv_inline_error');
					jQuery('.error', eL).remove();

					$.getJSON(voyado_ajax.ajax_url, eL.serialize(), function (data, textStatus) {

						if ('success' === textStatus) {
							if (true === data.success) {
								jQuery('#vv_embed_signup_scroll, #vv_embed_signup .error').fadeOut();
								eL.html('<p class="notification success">' + data.success_message + '</p>');
							} else {
								error_container = jQuery('.error', eL);

								jQuery('#'+data.classContainer, eL).addClass('vv_inline_error');

								if (0 === error_container.length) {
									error_container = jQuery('<p class="notification error"></p>');
									error_container.prependTo(eL);
								}

								if (!data.error_message) {
									jQuery('.error', eL).remove();
								} else {
									error_container.html(data.error_message);
								}
							}
						}

						$form.css('opacity', '1').css('pointer-events', 'all');

						return false;

						/*},
						error: function (xhr) {
							error_container = jQuery('<span class="notification error">' + xhr.statusText + '</span>');
							error_container.prependTo(eL);
							return false;
						}*/
					});


					return false;
				});
			} else {

			}

		});
	}
);
<?php

/**
 * Post type Admin Helper file.
 *
 * @package Voyado_Subscribe/Includes
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin Helper class.
 */
class Voyado_Subscribe_Helper
{

	/**
	 * @var mixed
	 */
	private $_token = 'voyado_subscribe';
	/**
	 * @var string
	 */
	private $secretSalt = '54%flakafn415';
	/**
	 * @var array
	 */
	private $options = array();
	/**
	 * @var mixed
	 */
	private $vyapi = null;

	/**
	 * @param $token
	 */
	public function __construct()
	{
		$this->set_options();
		$this->set_vyapi();
	}

	public function debug()
	{
		// cleanup
	}

	/**
	 * @param $token
	 */
	public function set_options()
	{
		$this->options = array(
			'api_key' => get_option($this->_token . '_api_key'),
		);
	}

	/**
	 * @return mixed
	 */
	public function get_subscribers()
	{
		return $this->vyapi->get_subscriber_count();
	}

	public function check_connection()
	{
		if ($this->vyapi->get_subscriber_count()) :
			return true;
		else :
			return false;
		endif;
	}

	/**
	 * Get Voyado API2
	 *
	 * @return bool|Voyado
	 */
	public function set_vyapi()
	{
		$this->vyapi = new Voyado_API(get_option($this->_token . '_api_key'));
	}

	/**
	 * Get Voyado api Key
	 *
	 * @return bool|mixed
	 */
	private function get_api_key()
	{
		if (is_array($this->options) && !empty($this->options['api-key'])) {
			return $this->options['api-key'];
		} else {
			return false;
		}
	}

	public function vv_voyado_register()
	{
		if (
			!isset($_GET['vv_nonce'])
			|| !wp_verify_nonce($_GET['vv_nonce'], 'vv_validate_nonce')
		) {
			exit('The form is not valid.');
		}

		// no valid API connection
		if (!$this->vyapi) {
			$response['error_message'] = 'Ett fel inträffade, försök igen senare.';

			return json_encode($response);
		}

		$response = array(
			'success' => false
		);

		$email = isset($_GET['vv-email']) ? $_GET['vv-email'] : false;
		$agree = isset($_GET['vv-agree']) ? $_GET['vv-agree'] : false;
		$captcha_answer = isset($_GET['vv-challenge-answer']) ? $_GET['vv-challenge-answer'] : false;
		$captcha_challenge = isset($_GET['vv-challenge-token']) ? $_GET['vv-challenge-token'] : false;

		if (!isset($agree)) {
			$response['error_message'] = __('Du behöver godkänna villkoren innan du fortsätter.', 'voyado-subscribe');
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$response['error_message'] = __('Ange en korrekt e-postaddress.', 'voyado-subscribe');
			$response['classContainer'] = 'vv-email';
		}

		if (md5($captcha_answer . $this->secretSalt) != $captcha_challenge) {
			$response['error_message'] = __('Felaktigt svar på säkerhetsfrågan angivet.', 'voyado-subscribe');
			$response['classContainer'] = 'vv-challenge-answer';
		}

		if (!isset($response['error_message'])) {
			if ($this->vyapi->get_subscriber_by_email($email)) {
				$response['error_message'] = __('E-postaddressen du angav finns redan registrerad.', 'voyado-subscribe');
				$response['classContainer'] = 'vv-email';
			} else {
				if ($this->vyapi->add_new_subscriber($email)) {
					$response['success'] = true;
					$response['success_message'] = __('Tack för att du prenumererar!', 'voyado-subscribe');
				} else {
					$response['error_message'] = __('Ett fel inträffade, försök igen senare.', 'voyado-subscribe');
				}
			}
		}

		echo json_encode($response);
		wp_die();
	}

	/**
	 * Shortcode for displaying form
	 *
	 * @param [type] $atts
	 * @return void
	 */
	public function vv_display_shortcode($atts)
	{
		$a = shortcode_atts(array(
			'filter' => null,
		), $atts);

		$captChallenge = array(
			'first' => rand(1, 15),
			'second' => rand(1, 15),
			'math' => (rand(1, 2) == 1) ? '+' : '-',
		);

		if ($captChallenge['second'] > $captChallenge['first']) {
			$captChallenge['math'] = "+";
		}

		$captAnswer = ($captChallenge['math'] == '+')
			? ($captChallenge['first'] + $captChallenge['second'])
			: ($captChallenge['first'] - $captChallenge['second']);

		$output = '';
		ob_start(); ?>

		<div class="news-letterform">
			<!-- Begin Voyado Signup Form -->
			<div id="vv_embed_signup">
				<form action="#" method="post" name="voyado-embedded-subscribe-form" class="validate">
					<div id="vv_embed_signup_scroll">

						<div class="vv-field-group">
							<label for="vv-email">E-postadress: <span class="asterisk">*</span>
							</label>
							<input type="email" value="" name="vv-email" class="required email" id="vv-email" placeholder="Din e-postadress" aria-required="true" required>
						</div>

						<div class="vv-field-group">
							<label for="vv-challenge-answer">Säkerhetsfråga (<?php echo $captChallenge['first'] . ' ' . $captChallenge['math'] . ' ' . $captChallenge['second']; ?>): <span class="asterisk">*</span>
							</label>
							<input type="text" value="" name="vv-challenge-answer" class="required" id="vv-challenge-answer" placeholder="Vad är <?php echo $captChallenge['first'] . ' ' . $captChallenge['math'] . ' ' . $captChallenge['second']; ?>?" aria-required="true" required>
						</div>

						<div class="vv-field-group input-group">
							<ul>
								<li><input type="checkbox" value="1" name="vv-agree" id="vv-agree" class="required" aria-required="true" required><label for="vv-agree"> Härmed godkänner jag Jägersros <a style="color: #949494; text-decoration:underline" href="/wp-content/uploads/2018/05/Integritetspolicy-Jagersro_se.pdf" target="_blank">personuppgiftspolicy</a></label></li>
							</ul>
						</div>

						<div id="vv-responses" class="clear">
							<div class="response" id="vv-error-response" style="display:none"></div>
							<div class="response" id="vv-success-response" style="display:none"></div>
						</div>
						<input type="hidden" name="action" value="vv_do_register">
						<input type="hidden" name="vv-challenge-token" value="<?php echo md5($captAnswer . $this->secretSalt); ?>">
						<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="" tabindex="-1" value=""></div>
						<?php wp_nonce_field('vv_validate_nonce', 'vv_nonce'); ?>
						<div class="clear"><input type="submit" value="Prenumerera" name="subscribe" class="vv-submit" class="button"></div>
					</div>
				</form>
			</div>
		</div>


<?php

		$output .= ob_get_clean();

		return $output;
	}
}

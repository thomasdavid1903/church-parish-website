<?php
/**
 * Contact form: [parish_contact_form] lets visitors write to the clergy.
 *
 * The visitor picks who the message is for (any priest, or a named one).
 * Every message goes to the address under Settings → Parish → Contact form,
 * with the chosen priest in the subject, so whoever reads that inbox can pass
 * it on. Replies go straight to the visitor (Reply-To).
 *
 * Spam protection without a CAPTCHA: a hidden "website" field that people
 * never fill in, and a minimum of 3 seconds between loading and sending.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_OPTION_CONTACT_EMAIL = 'parish_contact_email';

/**
 * Who a message can be addressed to: key => [ English, Russian ].
 */
function parish_contact_recipients(): array {
	return array(
		'any'      => array( 'Any of the clergy', 'Любому из духовенства' ),
		'vitaly'   => array( 'Archpriest Vitaly Serapinas', 'Протоиерей Виталий Серапинас' ),
		'yaroslav' => array( 'Archpriest Yaroslav Hudymenko', 'Протоиерей Ярослав Гудименко' ),
		'theodore' => array( 'Hieromonk Theodore', 'Иеромонах Феодор' ),
	);
}

function parish_contact_ru(): bool {
	return 'ru' === ( function_exists( 'parish_lang' ) ? parish_lang() : 'en' );
}

function parish_contact_t( string $en, string $ru ): string {
	return parish_contact_ru() ? $ru : $en;
}

add_shortcode(
	'parish_contact_form',
	function () {
		$status = isset( $_GET['contact'] ) ? sanitize_key( $_GET['contact'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$ru     = parish_contact_ru();

		// After an error, refill what the visitor typed. It is kept briefly on the
		// server under a one-off key, never in the URL.
		$old = array( 'to' => 'any', 'name' => '', 'email' => '', 'message' => '' );
		$cf  = isset( $_GET['cf'] ) ? sanitize_key( $_GET['cf'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$kept = $cf ? get_transient( 'parish_cf_' . $cf ) : false;
		if ( is_array( $kept ) ) {
			$old = array_merge( $old, $kept );
			delete_transient( 'parish_cf_' . $cf );
		}

		$notice = '';
		if ( 'sent' === $status ) {
			$notice = '<p class="parish-form__notice is-success" role="status">' . esc_html( parish_contact_t( 'Thank you. Your message has been sent and the clergy will reply by email.', 'Спасибо! Ваше сообщение отправлено, духовенство ответит вам по электронной почте.' ) ) . '</p>';
		} elseif ( 'invalid' === $status ) {
			$notice = '<p class="parish-form__notice is-error" role="alert">' . esc_html( parish_contact_t( 'Please fill in your name, a valid email address and your message.', 'Пожалуйста, укажите имя, правильный адрес электронной почты и текст сообщения.' ) ) . '</p>';
		} elseif ( 'failed' === $status ) {
			$notice = '<p class="parish-form__notice is-error" role="alert">' . esc_html( parish_contact_t( 'Sorry, the message could not be sent. Please telephone one of the clergy instead.', 'К сожалению, сообщение не удалось отправить. Пожалуйста, позвоните одному из священников.' ) ) . '</p>';
		}

		$options = '';
		foreach ( parish_contact_recipients() as $key => $names ) {
			$options .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $old['to'], $key, false ), esc_html( $names[ $ru ? 1 : 0 ] ) );
		}

		ob_start();
		?>
		<form class="parish-form" id="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?>
			<input type="hidden" name="action" value="parish_contact">
			<input type="hidden" name="parish_started" value="<?php echo esc_attr( (string) time() ); ?>">
			<input type="hidden" name="parish_return" value="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( 'parish_contact', 'parish_contact_nonce' ); ?>
			<p class="parish-form__hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<p>
				<label for="parish-contact-to"><?php echo esc_html( parish_contact_t( 'Message for', 'Кому' ) ); ?></label>
				<select id="parish-contact-to" name="to"><?php echo $options; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></select>
			</p>
			<p>
				<label for="parish-contact-name"><?php echo esc_html( parish_contact_t( 'Your name', 'Ваше имя' ) ); ?></label>
				<input id="parish-contact-name" type="text" name="name" value="<?php echo esc_attr( $old['name'] ); ?>" required autocomplete="name">
			</p>
			<p>
				<label for="parish-contact-email"><?php echo esc_html( parish_contact_t( 'Your email', 'Ваш email' ) ); ?></label>
				<input id="parish-contact-email" type="email" name="email" value="<?php echo esc_attr( $old['email'] ); ?>" required autocomplete="email">
			</p>
			<p>
				<label for="parish-contact-message"><?php echo esc_html( parish_contact_t( 'Message', 'Сообщение' ) ); ?></label>
				<textarea id="parish-contact-message" name="message" rows="6" required><?php echo esc_textarea( $old['message'] ); ?></textarea>
			</p>
			<p class="parish-form__small"><?php echo esc_html( parish_contact_t( 'Your details are used only to reply to you. For anything urgent, please telephone.', 'Ваши данные используются только для ответа вам. По срочным вопросам, пожалуйста, звоните.' ) ); ?></p>
			<p><button type="submit"><?php echo esc_html( parish_contact_t( 'Send message', 'Отправить' ) ); ?></button></p>
		</form>
		<?php
		return ob_get_clean();
	}
);

function parish_contact_handle(): void {
	$return = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['parish_return'] ?? '' ) ), home_url( '/' ) );
	$back   = function ( string $status, array $keep = array() ) use ( $return ) {
		$args = array( 'contact' => $status );
		if ( $keep ) {
			$token = strtolower( wp_generate_password( 20, false ) );
			set_transient( 'parish_cf_' . $token, $keep, 10 * MINUTE_IN_SECONDS );
			$args['cf'] = $token;
		}
		wp_safe_redirect( add_query_arg( $args, $return ) . '#contact-form' );
		exit;
	};

	if ( ! isset( $_POST['parish_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['parish_contact_nonce'] ), 'parish_contact' ) ) {
		$back( 'failed' );
	}
	// Bots: pretend it worked so they don't retry.
	if ( ! empty( $_POST['website'] ) || time() - (int) ( $_POST['parish_started'] ?? 0 ) < 3 ) {
		$back( 'sent' );
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	$key     = sanitize_key( $_POST['to'] ?? 'any' );
	$people  = parish_contact_recipients();
	if ( '' === $name || ! is_email( $email ) || '' === trim( $message ) || ! isset( $people[ $key ] ) ) {
		// Refill the email as typed (sanitize_email() empties a mistyped one) so it can be corrected.
		$typed = sanitize_text_field( wp_unslash( $_POST['email'] ?? '' ) );
		$back( 'invalid', array( 'to' => isset( $people[ $key ] ) ? $key : 'any', 'name' => $name, 'email' => $typed, 'message' => $message ) );
	}

	$to      = get_option( PARISH_OPTION_CONTACT_EMAIL ) ?: get_option( 'admin_email' );
	$subject = sprintf( '[Website] Message for %s from %s', $people[ $key ][0], $name );
	$body    = "For: {$people[ $key ][0]}\nFrom: {$name} <{$email}>\n\n{$message}\n";
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	$back( $sent ? 'sent' : 'failed', $sent ? array() : array( 'to' => $key, 'name' => $name, 'email' => $email, 'message' => $message ) );
}
add_action( 'admin_post_nopriv_parish_contact', 'parish_contact_handle' );
add_action( 'admin_post_parish_contact', 'parish_contact_handle' );

add_action(
	'admin_init',
	function () {
		add_settings_section( 'parish_contact', __( 'Contact form', 'parish-core' ), '__return_false', 'parish' );
		register_setting(
			'parish',
			PARISH_OPTION_CONTACT_EMAIL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);
		add_settings_field(
			PARISH_OPTION_CONTACT_EMAIL,
			__( 'Send messages to', 'parish-core' ),
			function () {
				printf(
					'<input type="email" class="regular-text" name="%1$s" value="%2$s" placeholder="%3$s"><p class="description">%4$s</p>',
					esc_attr( PARISH_OPTION_CONTACT_EMAIL ),
					esc_attr( get_option( PARISH_OPTION_CONTACT_EMAIL ) ),
					esc_attr( get_option( 'admin_email' ) ),
					esc_html__( 'Email address that receives messages from the contact form on the Contact page. The chosen priest is shown in the subject. Leave empty to use the site admin email.', 'parish-core' )
				);
			},
			'parish',
			'parish_contact'
		);
	}
);

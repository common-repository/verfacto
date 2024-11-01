<?php

trait Verfacto_Plugin_Helper {

	private function get_setting_by_name( $name ) {
		$verfacto_options = get_option( 'verfacto_options' );
		if ( isset( $verfacto_options[ $name ] ) ) {
			return $verfacto_options[ $name ];
		}
	}

    private function check_auth_keys( $consumer_key, $consumer_secret ) {
        $verfacto_options = get_option( 'verfacto_options' );
        if ( isset( $verfacto_options[ $consumer_key ]) && isset( $verfacto_options[ $consumer_secret ]) ) {
            global $wpdb;
            $oauthKeys = $wpdb->get_results( "SELECT consumer_key, consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE '%Verfacto - API%' ORDER BY key_id DESC LIMIT 1" );
            if (is_array($oauthKeys) && count($oauthKeys) > 0) {
                foreach ($oauthKeys as $keys) {
                    if ($keys->consumer_key == $verfacto_options[ $consumer_key ] && $keys->consumer_secret == $verfacto_options[ $consumer_secret ]) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

	private function authorize_backoffice_access( $email = null, $password = null ) {
		$payload = array(
			'account_name' => $this->verfacto_account_name(),
			'email'        => ( $email ) ? $email : $this->get_setting_by_name( 'verfacto_user_email' ),
			'password'     => ( $password ) ? $password : $this->get_setting_by_name( 'verfacto_login_token' ),
		);

		return $this->call_api_endpoint( 'POST', $payload, '/signin' );
	}

	private function call_api_endpoint( $method, $payload, $path, $authorization_token = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => json_encode( $payload ),
		);

		if ( $authorization_token ) {
			$args['headers']['Authorization'] = $authorization_token;
		}

		$remote_response = wp_remote_request( Verfacto_Plugin::API_BASE_URL . $path, $args );

		if ( is_wp_error( $remote_response ) ) {
			die( 'internal-server-error' );
		}

		$response_code = wp_remote_retrieve_response_code( $remote_response );
		$response_body = json_decode( wp_remote_retrieve_body( $remote_response ), true );

		return array(
			'response_code' => $response_code,
			'response_body' => $response_body,
		);
	}

	private function is_page_refreshed() {
		$is_page_refreshed = ( isset( $_SERVER['HTTP_CACHE_CONTROL'] ) && $_SERVER['HTTP_CACHE_CONTROL'] == 'max-age=0' );

		if ( $is_page_refreshed ) {
			return true;
		} else {
			return false;
		}
	}

	private function verfacto_account_name() {
		$url_parts = parse_url( home_url() );
		return str_replace( '.', '-', preg_replace( '/^www\./i', '', $url_parts['host'] ) );
	}

}


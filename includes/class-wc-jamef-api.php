<?php
/**
 * Jamef API.
 *
 * @package brendon.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 */
class WC_Jamef_API {
	/**
	 * Log.
	 *
	 * @var WC_Logger
	 */
	private $log;

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	private $log_handler;

	/**
	 * Initialize functions.
	 *
	 * @param WC_Logger $log the log instance.
	 * @param string    $endpoint the endpoint.
	 */
	public function __construct( WC_Logger $log = null, string $endpoint ) {
		$this->log         = $log;
		$this->log_handler = 'jamef';
		if ( '1' === $endpoint ) {
			$this->endpoint = 'https://api-sandbox.jamef.com.br/frete/rest/v1/';
		} else {
			$this->endpoint = 'https://www.jamef.com.br/frete/rest/v1/';
		}
	}

	/**
	 * Get shipping cost.
	 *
	 * @param array $data data to shipping.
	 * @return mixed|false
	 */
	public function get_shipping_cost( array $data ) {
		$data['cnpjcpf'] = substr( preg_replace( '/\D/', '', $data['cnpjcpf'] ), 0, 14 );
		$data['munori']  = substr( $data['munori'], 0, 50 );

		if ( $this->log ) {
			$this->log->add( $this->log_handler, wp_json_encode( $data, JSON_UNESCAPED_UNICODE ), WC_Log_Levels::INFO );
		}

		$response = wp_remote_get(
			$this->endpoint . $data['tiptra'] . '/' . $data['cnpjcpf'] . '/' . $data['munori'] . '/'
			. $data['estori'] . '/' . $data['segprod'] . '/' . $data['peso'] . '/' . $data['valmer']
			. '/' . $data['metro3'] . '/' . $data['cepdes'] . '/' . $data['filcot'] . '/'
			. gmdate( 'd' ) . '/' . gmdate( 'm' ) . '/' . gmdate( 'Y' ) . '/' . $data['usuario']
		);

		if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), array( 200, 201 ) ) ) {
			if ( $this->log ) {
				$this->log->add( $this->log_handler, wp_remote_retrieve_body( $response ), WC_Log_Levels::INFO );
			}
			return json_decode( wp_remote_retrieve_body( $response ) );
		}

		if ( $this->log ) {
			if ( is_wp_error( $response ) ) {
				$this->log->add( $this->log_handler, $response->get_error_message(), WC_Log_Levels::ERROR );
			} else {
				$this->log->add( $this->log_handler, json_encode( $response ), WC_Log_Levels::ERROR );
			}
		}

		return false;
	}
}

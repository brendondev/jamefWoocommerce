<?php
/**
 * Jamef Shipping Method.
 *
 * @package brendon.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Jamef_Shipping' ) ) {
	/**
	 * Class definition.
	 */
	class WC_Jamef_Shipping extends WC_Shipping_Method {
		/**
		 * Instance from logger.
		 *
		 * @var WC_Logger
		 */
		private $log;

		/**
		 * Handle log enable.
		 *
		 * @var string
		 */
		private $debug;

		/**
		 * Constructor for shipping class
		 *
		 * @param int $instance_id Shipping zone instance ID.
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			$this->instance_id        = absint( $instance_id );
			$this->id                 = 'brendon_jamef';
			$this->title              = __( 'Jamef', 'brendon-jamef' );
			$this->method_title       = __( 'Jamef', 'brendon-jamef' );
			$this->method_description = __( 'Permite enviar suas mercadorias através da transportadora jamef.', 'brendon-jamef' );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
			);

			// Load the form fields.
			$this->init_form_fields();

			// Define user set variables.
			$this->enabled = $this->get_option( 'enabled' );
			$this->title   = $this->get_option( 'title' );
			$this->debug   = $this->get_option( 'debug' );

			// Active logs.
			if ( 'yes' === $this->debug ) {
				$this->log = new WC_Logger();
			}

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Define settings field for this shipping.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'enabled'    => array(
					'title'       => __( 'Habilitar', 'brendon-jamef' ),
					'type'        => 'checkbox',
					'description' => __( 'Habilita a entrega com Jamef.', 'brendon-jamef' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'title'      => array(
					'title'       => __( 'Título', 'brendon-jamef' ),
					'type'        => 'text',
					'description' => __( 'Título que será exibido na loja.', 'brendon-jamef' ),
					'default'     => __( 'Jamef Transportadora', 'brendon-jamef' ),
					'desc_tip'    => true,
				),
				'tiptra'     => array(
					'title'       => __( 'Tipo de Transporte', 'brendon-jamef' ),
					'type'        => 'select',
					'description' => __( 'Tipo de frete usado para transportar a mercadoria.', 'brendon-jamef' ),
					'default'     => '1',
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_trasnport_type(),
				),
				'cnpjcpf'    => array(
					'title'       => __( 'CNPJ/CPF', 'brendon-jamef' ),
					'type'        => 'text',
					'default'     => '',
					'description' => __( 'CNPJ/CPF do cliente que será responsável pelo	pagamento.', 'brendon-jamef' ),
					'desc_tip'    => true,
				),
				'munori'     => array(
					'title'       => __( 'Município de Origem', 'brendon-jamef' ),
					'type'        => 'text',
					'description' => __( 'Nome do Município de origem da Mercadoria. Mesmo Munícipio do Cliente Responsável.', 'brendon-jamef' ),
					'default'     => 'Aracaju',
					'desc_tip'    => true,
				),
				'estori'     => array(
					'title'       => __( 'Estado de Origem', 'brendon-jamef' ),
					'type'        => 'select',
					'description' => __( 'Estado de origem.', 'brendon-jamef' ),
					'default'     => 'SE',
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_state_origin(),
				),
				'segprod'    => array(
					'title'       => __( 'Tipo de Produto', 'brendon-jamef' ),
					'type'        => 'select',
					'description' => __( 'Tipo de produto a ser transportado. Para transporte rodoviário selecionar apenas "Conforme nota fiscal" ou "Livros".', 'brendon-jamef' ),
					'default'     => '000004',
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_product_type(),
				),
				'filcot'     => array(
					'title'       => __( 'Filial de Coleta', 'brendon-jamef' ),
					'type'        => 'select',
					'description' => __( 'Filial da Jamef que irá efetuar a coleta da mercadoria e emitir o CTRC do cliente.', 'brendon-jamef' ),
					'default'     => '31',
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_jamef_branch(),
				),
				'usuario'    => array(
					'title'       => __( 'Usuário', 'brendon-jamef' ),
					'type'        => 'text',
					'description' => __( 'Login cadastrado na Jamef. O mesmo utilizado para acessar o site da empresa.', 'brendon-jamef' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'enviroment' => array(
					'title'       => __( 'Ambiente', 'brendon-jamef' ),
					'type'        => 'select',
					'description' => __( 'Modo de execução da integração com jamef.', 'brendon-jamef' ),
					'default'     => '1',
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_jamef_enviroment(),
				),
				'debug'      => array(
					'title'       => __( 'Debug', 'brendon-jamef' ),
					'type'        => 'checkbox',
					'label'       => __( 'Registra solicitações no arquivo de log.', 'brendon-jamef' ),
					'default'     => 'yes',
					'description' => __( 'Log de eventos para registrar as interações com o WebService.', 'brendon-jamef' ),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Calculate_shipping function.
		 *
		 * @access public
		 * @param array $package the packacge.
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			// Check if valid to be calculeted.
			if ( '' === $package['destination']['postcode'] || 'BR' !== $package['destination']['country'] ) {
				return;
			}

			$cost   = $package['contents_cost'];
			$weight = $this->get_package_weight( $package );
			$metro3 = $this->get_package_metro3( $package );

			if ( 0 === $cost || ! $weight || ! $metro3 ) {
				if ( $this->log ) {
					$this->log->add( $this->id, 'É necessário possuir peso, preço e dimensões para o cálculo de frete.', WC_Log_Levels::ALERT );
				}
				return;
			}

			require_once 'class-wc-jamef-api.php';
			$api = new WC_Jamef_API(
				$this->log ? $this->log : null,
				$this->get_option( 'enviroment' )
			);

			$data = array(
				'tiptra'  => $this->get_option( 'tiptra' ),
				'cnpjcpf' => $this->get_option( 'cnpjcpf' ),
				'munori'  => $this->normalizeChars( $this->get_option( 'munori' ) ),
				'estori'  => $this->get_option( 'estori' ),
				'segprod' => $this->get_option( 'segprod' ),
				'peso'    => strval( $weight ),
				'valmer'  => strval( $cost ),
				'metro3'  => strval( $metro3 ),
				'cepdes'  => preg_replace( '/\D/', '', $package['destination']['postcode'] ),
				'filcot'  => $this->get_option( 'filcot' ),
				'usuario' => $this->get_option( 'usuario' ),
			);

			$estimate = $api->get_shipping_cost( $data );

			if ( $estimate ) {
				if ( $estimate->previsao_entrega ) {
					$meta_delivery = array(
						'_estimate_delivery' => $estimate->previsao_entrega,
					);
				}

				$rate = apply_filters(
					$this->id . '_rate',
					array(
						'id'        => $this->id . ':' . $this->instance_id,
						'label'     => $this->title,
						'cost'      => (float) $estimate->valor,
						'package'   => $package,
						'meta_data' => $meta_delivery,
					),
					$this->instance_id,
					$package
				);

				// Add rate to WooCommerce.
				$this->add_rate( $rate );
			}
		}

		/**
		 * Get option from transport type.
		 *
		 * @return array
		 */
		private function get_trasnport_type() {
			return array(
				'1' => __( 'Rodoviário', 'brendon-jamef' ),
				'2' => __( 'Aéreo', 'brendon-jamef' ),
			);
		}

		/**
		 * Get state UF.
		 *
		 * @return array
		 */
		private function get_state_origin() {
			return array(
				'AC' => __( 'Acre', 'brendon-jamef' ),
				'AL' => __( 'Alagoas', 'brendon-jamef' ),
				'AP' => __( 'Amapá', 'brendon-jamef' ),
				'AM' => __( 'Amazonas', 'brendon-jamef' ),
				'BA' => __( 'Bahia', 'brendon-jamef' ),
				'CE' => __( 'Ceará', 'brendon-jamef' ),
				'DF' => __( 'Distrito Federal', 'brendon-jamef' ),
				'ES' => __( 'Espírito Santo', 'brendon-jamef' ),
				'GO' => __( 'Goiás', 'brendon-jamef' ),
				'MA' => __( 'Maranhão', 'brendon-jamef' ),
				'MT' => __( 'Mato Grosso', 'brendon-jamef' ),
				'MS' => __( 'Mato Grosso do Sul', 'brendon-jamef' ),
				'MG' => __( 'Minas Gerais', 'brendon-jamef' ),
				'PA' => __( 'Pará', 'brendon-jamef' ),
				'PB' => __( 'Paraíba', 'brendon-jamef' ),
				'PR' => __( 'Paraná', 'brendon-jamef' ),
				'PE' => __( 'Pernambuco', 'brendon-jamef' ),
				'PI' => __( 'Piauí', 'brendon-jamef' ),
				'RJ' => __( 'Rio de Janeiro', 'brendon-jamef' ),
				'RN' => __( 'Rio Grande do Norte', 'brendon-jamef' ),
				'RS' => __( 'Rio Grande do Sul', 'brendon-jamef' ),
				'RO' => __( 'Rondônia', 'brendon-jamef' ),
				'RR' => __( 'Roraima', 'brendon-jamef' ),
				'SC' => __( 'Santa Catarina', 'brendon-jamef' ),
				'SP' => __( 'São Paulo', 'brendon-jamef' ),
				'SE' => __( 'Sergipe', 'brendon-jamef' ),
				'TO' => __( 'Tocantins', 'brendon-jamef' ),
			);
		}

		/**
		 * Get product type.
		 *
		 * @return array
		 */
		private function get_product_type() {
			return array(
				'000004' => __( 'Conforme Nota Fiscal', 'brendon-jamef' ),
				'000010' => __( 'AÉREO - Alimentos Industrializados', 'brendon-jamef' ),
				'000008' => __( 'AÉREO - Confecções', 'brendon-jamef' ),
				'000011' => __( 'AÉREO - Cosméticos / Material Cirúrgico', 'brendon-jamef' ),
				'000006' => __( 'AÉREO - Jornais / Revistas', 'brendon-jamef' ),
				'000005' => __( 'Livros', 'brendon-jamef' ),
				'000013' => __( 'AÉREO - Material Escolar', 'brendon-jamef' ),
			);
		}

		/**
		 * Get product type.
		 *
		 * @return array
		 */
		private function get_jamef_branch() {
			return array(
				'06' => __( 'Rio de Janeiro / RJ ', 'brendon-jamef' ),
				'31' => __( 'Aracaju / SE ', 'brendon-jamef' ),
				'19' => __( 'Barueri / SP ', 'brendon-jamef' ),
				'16' => __( 'Bauru / SP', 'brendon-jamef' ),
				'02' => __( 'Belo Horizonte / MG', 'brendon-jamef' ),
				'09' => __( 'Blumenau / SC', 'brendon-jamef' ),
				'28' => __( 'Brasília / DF', 'brendon-jamef' ),
				'26' => __( 'Criciúma / SC', 'brendon-jamef' ),
				'03' => __( 'Campinas / SP', 'brendon-jamef' ),
				'22' => __( 'Caxias do Sul / RS', 'brendon-jamef' ),
				'04' => __( 'Curitiba / PR', 'brendon-jamef' ),
				'38' => __( 'Divinópolis / MG', 'brendon-jamef' ),
				'34' => __( 'Feira de Santana / BA', 'brendon-jamef' ),
				'11' => __( 'Florianópolis / SC', 'brendon-jamef' ),
				'32' => __( 'Fortaleza / CE', 'brendon-jamef' ),
				'24' => __( 'Goiânia / GO', 'brendon-jamef' ),
				'36' => __( 'João Pessoa / PB', 'brendon-jamef' ),
				'23' => __( 'Juiz de Fora / MG', 'brendon-jamef' ),
				'08' => __( 'Joinville / SC', 'brendon-jamef' ),
				'10' => __( 'Londrina / PR', 'brendon-jamef' ),
				'25' => __( 'Manaus / AM', 'brendon-jamef' ),
				'33' => __( 'Maceió / AL', 'brendon-jamef' ),
				'12' => __( 'Maringá / PR', 'brendon-jamef' ),
				'05' => __( 'Porto Alegre / RS', 'brendon-jamef' ),
				'27' => __( 'Pouso Alegre / MG', 'brendon-jamef' ),
				'18' => __( 'Ribeirão Preto / SP', 'brendon-jamef' ),
				'30' => __( 'Recife / PE', 'brendon-jamef' ),
				'07' => __( 'São Paulo / SP', 'brendon-jamef' ),
				'21' => __( 'São José dos Campos / SP', 'brendon-jamef' ),
				'20' => __( 'São José do Rio Preto / SP', 'brendon-jamef' ),
				'29' => __( 'Salvador / BA', 'brendon-jamef' ),
				'17' => __( 'Uberlândia / MG', 'brendon-jamef' ),
				'39' => __( 'Vitória da Conquista / BA', 'brendon-jamef' ),
				'14' => __( 'Vitória / ES', 'brendon-jamef' ),
			);
		}

		/**
		 * Get enviroment.
		 *
		 * @return array
		 */
		private function get_jamef_enviroment() {
			return array(
				'1' => __( 'Testes', 'brendon-jamef' ),
				'2' => __( 'Produção', 'brendon-jamef' ),
			);
		}

		/**
		 * Get package weight.
		 *
		 * @param  array $package Shipping package.
		 *
		 * @return float|bool
		 */
		private function get_package_weight( $package ) {
			$weight = 0;

			foreach ( $package['contents'] as $value ) {
				$product        = $value['data'];
				$qty            = $value['quantity'];
				$product_weight = 0;

				if ( $qty > 0 && $product->needs_shipping() ) {
					$product_weight = wc_get_weight( (float) $product->get_weight(), 'kg' );

					if ( $qty > 1 ) {
						$product_weight *= $qty;
					}
				}

				$weight += $product_weight;
			}

			return $weight;
		}

		/**
		 * Get package metro3.
		 *
		 * @param  array $package Shipping package.
		 *
		 * @return int|bool
		 */
		private function get_package_metro3( $package ) {
			$metro3 = 0;

			foreach ( $package['contents'] as $value ) {
				$product        = $value['data'];
				$qty            = $value['quantity'];
				$product_metro3 = 0;

				if ( ! $product->has_dimensions() ) {
					continue;
				}

				if ( $qty > 0 && $product->needs_shipping() ) {
					$_height = wc_get_dimension( (float) $product->get_height(), 'm' );
					$_width  = wc_get_dimension( (float) $product->get_width(), 'm' );
					$_length = wc_get_dimension( (float) $product->get_length(), 'm' );

					$product_metro3 = $_height * $_width * $_length;

					if ( $qty > 1 ) {
						$product_metro3 *= $qty;
					}
				}

				$metro3 += $product_metro3;
			}

			return $metro3;
		}

		/**
		 * Remove accents from text.
		 *
		 * @param string $s the string.
		 */
		private function normalizeChars( $s ) {
			$replace = array(
				'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
				'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
				'Þ'=>'B',
				'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
				'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
				'Ğ'=>'G',
				'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
				'Ł'=>'L',
				'Ñ'=>'N', 'Ń'=>'N',
				'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
				'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
				'Ț'=>'T',
				'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
				'Ý'=>'Y',
				'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
				'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
				'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
				'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
				'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
				'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
				'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
				'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
				'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
				'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
				'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
				'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
				'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
				'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
				'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
				'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
				'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
				'ק'=>'q',
				'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
				'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
				'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
				'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
				'в'=>'v', 'ו'=>'v', 'В'=>'v',
				'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
				'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
				'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
			);
			return strtr($s, $replace);
		}
	}
}

<?php
/**
 * Plugin Name: Paymment module for 2CheckOut
 * Plugin URI: http://www.2checkout.com
 * Description: Paymment with credit card from 2CheckOut
 * Author: Jose Sabino
 * Author URI: https://gitlab.com/users/jasabino/projects
 * Version: 1.0
 * Text Domain: wc-gateway-2CO
 *
 * Copyright: (c) 2018 Jose Sabino
 *
 * @package   wc-gateway-2CO
 * @author    Jose Sabino
 * @category  Admin
 * @copyright Copyright (c) 2018 Jose Sabino
 *
 */

defined('ABSPATH') or exit;

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


/**
 * this functions allow to add this payment to the array of available methods of payment
 *
 * @since 1.0.0
 * @param array $gateways
 * @return array $gateways + twocheckout gateway
 */
function wc_twocheckout_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_twocheckout';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_twocheckout_add_to_gateways');


/**
 * this funtion allow to add the link for setting the module
 *
 * @since 1.0.0
 * @param array $links
 * @return array $links + "Configurar"
 */
function wc_twocheckout_gateway_plugin_links($links)
{

    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=twocheckout_gateway') . '">' . __('Configurar', 'wc-gateway-twocheckout') . '</a>'
    );

    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_twocheckout_gateway_plugin_links');


/**
 * 2CheckOut Plugin
 *
 *
 * @class        2CheckOut
 * @extends        WC_Payment_Gateway
 * @version        1.0.0
 * @package        WooCommerce/Classes/Payment
 * @author        Jose Sabino
 */
add_action('plugins_loaded', 'wc_twocheckout_gateway_init', 11);

function wc_twocheckout_gateway_init()
{

    class WC_Gateway_twocheckout extends WC_Payment_Gateway
    {
        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {

            $this->id = 'twocheckout_gateway';
            $this->icon = apply_filters('woocommerce_twocheckout_icon', '');
            $this->has_fields = false;
            $this->method_title = __('2Checkout', 'wc-gateway-twocheckout');
            $this->method_description = __('Permite pagos con tarjeta de crédito para ser procesadas a través de 2CheckOut.', 'wc-gateway-twocheckout');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->publishableKey = $this->get_option('publishableKey');
            $this->privateKey = $this->get_option('privateKey');
            $this->sellerId = $this->get_option('sellerId');
            $this->supports[] = 'default_credit_card_form';

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('wp_enqueue_scripts', 'wp_twocheckout_adding_scripts');

        }


        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields()
        {

            $this->form_fields = apply_filters('wc_offline_form_fields', array(

                'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'wc-gateway-twocheckout'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar Modulo 2CheckOut', 'wc-gateway-twocheckout'),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title' => __('Título', 'wc-gateway-twocheckout'),
                    'type' => 'text',
                    'description' => __('Título asociado al metodo de Pago ', 'wc-gateway-offline'),
                    'default' => __('Pago con Tarjeta de Crédito', 'wc-gateway-twocheckout'),
                    'desc_tip' => true,
                ),

                'description' => array(
                    'title' => __('Descripcion', 'wwc-gateway-twocheckout'),
                    'type' => 'textarea',
                    'description' => __('Descripción del método de pago que el cliente vera al intentar procesar el pago.', 'wc-gateway-offline'),
                    'default' => __('Pagos con Tarjeta de Crédito: Visa, MasterCard o American Express', 'wc-gateway-offline'),
                    'desc_tip' => true,
                ),
                'publishableKey' => array(
                    'title' => __('Publishable Key', 'wc-gateway-twocheckout'),
                    'type' => 'text',
                    'description' => __('Publishable Key 2CheckOut', 'wc-gateway-offline'),
                    'default' => __(''),
                    'desc_tip' => true,
                ),
                'privateKey' => array(
                    'title' => __('Private Key', 'wc-gateway-twocheckout'),
                    'type' => 'text',
                    'description' => __('Private Key 2CheckOut', 'wc-gateway-offline'),
                    'default' => __(''),
                    'desc_tip' => true,
                ),
                'sellerId' => array(
                    'title' => __('Seller Id', 'wc-gateway-twocheckout'),
                    'type' => 'text',
                    'description' => __('Seller Id 2CheckOut', 'wc-gateway-offline'),
                    'default' => __(''),
                    'desc_tip' => true,
                )
            ));
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {

            $order = wc_get_order($order_id);

            if (!isset($_POST['twocheckout_gateway-card-number'] ) || $_POST['twocheckout_gateway-card-number']==''){
                 wc_add_notice('Datos Incompletos: Debe especificar el numero de la tarjeta de crédito', 'error');
            }
            else if (!isset($_POST['twocheckout_gateway-card-expiry'] )|| $_POST['twocheckout_gateway-card-expiry']==''){
                wc_add_notice('Datos Incompletos: Debe especificar la fecha de expiración de la tarjeta de Crédito', 'error');
            }
            else if (!isset($_POST['twocheckout_gateway-card-cvc'] )|| $_POST['twocheckout_gateway-card-cvc']==''){
                wc_add_notice('Datos Incompletos: Debe especificar el código de validación de la tarjeta de crédito', 'error');
            }else{
                $name = $_POST['billing_first_name'].' '.$_POST['billing_last_name'];
                $addr = $_POST['billing_address_1'];
                $city = $_POST['billing_city'];
                $state = $_POST['billing_state'];
                $zip = $_POST['billing_postcode'];
                $email = $_POST['billing_email'];
                $phone = $_POST['billing_phone'];
                $invoiceNumber = str_replace("#", "", $order->get_order_number());
                $ammount = str_replace(array(',', '$', ' '), '', $order->order_total);
                $token = $_POST['twocheckout_gateway-token'];

                try {
                    $resp = $this->call2CO($token, $name, $addr, $city, $state, $zip, $email, $phone, $ammount, $invoiceNumber);

                    if ($resp) {

                        $order->update_status('completed', 'Pago Recibido');

                        // Reduce stock levels
                        $order->reduce_order_stock();

                        // Remove cart
                        WC()->cart->empty_cart();

                        // Return thankyou redirect
                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url($order)
                        );
                    } else {
                        // Transaction was not succesful
                        // Add notice to the cart
                        wc_add_notice($this->msg_error, 'error');
                        // Add note to the order for your reference
                        $order->add_order_note('Error: ' . $this->msg_error);
                    }

                } catch (Exception $e) {
                    // Transaction was not succesful
                    // Add notice to the cart
                    wc_add_notice('Error: ' . $e, 'error');
                    // Add note to the order for your reference
                    $order->add_order_note('Error: ' . $this->msg_error);

                }

            }


        }


        public function call2CO($token, $name, $addr, $city, $state, $zip, $email, $phone, $amount, $id)
        {

            require_once('lib/2COApi/Twocheckout.php');
            Twocheckout::privateKey($this->privateKey);
            Twocheckout::sellerId($this->sellerId);
            Twocheckout::verifySSL(false);
            Twocheckout::sandbox(true);

            try {
                $charge = Twocheckout_Charge::auth(array(
                    "sellerId" => $this->sellerId,
                    "merchantOrderId" => $id,
                    "token" => $token,
                    "currency" => 'USD',
                    "total" => $amount,
                    "billingAddr" => array(
                        "name" => $name,
                        "addrLine1" => $addr,
                        "city" => $city,
                        "state" => $state,
                        "zipCode" => $zip,
                        "country" => 'USA',
                        "email" => $email,
                        "phoneNumber" => $phone
                    ),
                    "shippingAddr" => array(
                        "name" => $name,
                        "addrLine1" => $addr,
                        "city" => $city,
                        "state" => $state,
                        "zipCode" => $zip,
                        "country" => 'USA',
                        "email" => $email,
                        "phoneNumber" => $phone
                    )
                ));
                if ($charge['response'] != null && $charge['response']['responseCode'] == 'APPROVED')
                    return true;
                else{
                    $this->msg_error = $charge['exception']['errorMsg'];
                    return false;
                }
            } catch (Twocheckout_Error $e) {
                $this->msg_error = $e->getMessage();
                return false;
            }
        }

        public function payment_fields() {
            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            if ( $this->supports( 'default_credit_card_form' ) ) {
                $cc_form = $this;
                $cc_form->id       = $this->id;
                $cc_form->supports = $this->supports;
                $cc_form->form();
            }
        }

        /**
         * Remaking the form
         */
        public function form() {
            wp_enqueue_script( 'wc-credit-card-form' );

            $fields = array();

            $cvc_field = '<p class="form-row form-row-last">
			                <label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Card code', 'woocommerce' ) . ' <span class="required">*</span></label>
			                <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
		                  </p>';

            $default_fields = array(
                'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
			</p>',
                'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Expiry (MM/YYYY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM / YYYY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
			</p>',
                'token-field'=>'<input id="' . esc_attr($this->id) . '-token" class="input-text" type="hidden" name="' . esc_attr($this->id) . '-token' . '" />'
            );

            if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
                $default_fields['card-cvc-field'] = $cvc_field;
            }

            $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
            ?>

            <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
                <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
                <?php
                foreach ( $fields as $field ) {
                    echo $field;
                }
                ?>
                <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
                <div class="clear"></div>
            </fieldset>
            <?php

            if ( $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
                echo '<fieldset>' . $cvc_field . '</fieldset>';
            }

            $script = '<script type="text/javascript" src="https://www.2checkout.com/checkout/api/2co.min.js"/>';
            $script .= '<script>';
            $script .= 'jQuery(function() {';
            $script .= '    TCO.loadPubKey("sandbox");';
            $script .= '    jQuery(".woocommerce-checkout").submit(function(e) {';
            $script .= '        tokenRequest();';
            $script .= '        return false;';
            $script .= '    });';
            $script .= '});';
            $script .= 'var tokenRequest = function() {';
            $script .= '    var args = {';
            $script .= '        sellerId: "'.$this->sellerId.'",';
            $script .= '        publishableKey: "'.$this->publishableKey.'",';
            $script .= '        ccNo: jQuery("#twocheckout_gateway-card-number").val().replace(/ /g,""),';
            $script .= '        cvv: jQuery("#twocheckout_gateway-card-cvc").val(),';
            $script .= '        expMonth: jQuery("#twocheckout_gateway-card-expiry").val().slice(0,2),';
            $script .= '        expYear:  jQuery("#twocheckout_gateway-card-expiry").val().slice(5,7),';
            $script .= '    };';
            $script .= '    console.log(args);';
            $script .= '    TCO.requestToken(successCallback, errorCallback, args);';
            $script .= '};';
            $script .= 'var successCallback = function(data) {';
            $script .= '    var myForm = jQuery(".woocommerce-checkout");';
            $script .= '    jQuery("#twocheckout_gateway-token").val(data.response.token.token);';
            $script .= '    console.log(jQuery("#twocheckout_gateway-token").val());';
            $script .= '    myForm.submit();';
            $script .= '};';
            $script .= 'var errorCallback = function(data) {';
            $script .= '    console.log(data);';
            $script .= '};';
            $script .= '';
            $script .= '';
            $script .= '</script>';

            echo $script;
        }

        public function field_name( $name ) {
            return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
        }
    }

    function wp_twocheckout_adding_scripts() {
        wp_deregister_script('jquery-payment');
        wp_register_script('jquery-payment', '/wp-content/plugins/2checkout/jquery.payment.js' , array( 'jquery' ), '3.0.0', TRUE);
        wp_enqueue_script('jquery-payment');
    }

}
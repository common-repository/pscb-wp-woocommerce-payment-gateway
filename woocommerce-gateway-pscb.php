<?php
/**
 *	Plugin Name: Модуль оплаты АО Банк ПСКБ для WooCommerce
 *	Plugin URI: https://ru.wordpress.org/plugins/pscb-wp-woocommerce-payment-gateway/
 *	Description: Модуль приема платежей АО Банк "ПСКБ" позволяет принимать оплату на сайте с использованием банковских карт (интернет-эквайринг), электронных кошельков (QIWI, Яндекс.Деньги, WebMoney) и других популярных способов оплаты.
 *	Version: 1.6.1
 *  Author: pscb
 *	Author URI: https://profiles.wordpress.org/pscb
*/

add_action('plugins_loaded', 'woocommerce_gateway_pscb_init', 0);

function woocommerce_gateway_pscb_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Gateway class
 	 */
	class WC_Gateway_PSCB extends WC_Payment_Gateway {

//        private $customer_info = settings['customer_info'];
//        private $customer_id = settings['customer_id'];
//        private $basket_composition = settings['basket_composition'];

	     /**
         * Make __construct()
         **/	
		public function __construct(){
			
			$this->id 					      = 'pscb_payments';
			$this->title 				      = 'Модуль оплаты АО Банк ПСКБ';
			$this->description			      = 'Модуль оплаты АО Банк ПСКБ';
			$this->has_fields 			      = false;

			$this->init_form_fields();
			$this->init_settings();

			$this->title 			          = $this->settings['title'];
			$this->description 		          = $this->settings['description'];
			
			
			$this->icon                       = plugins_url( 'assets/img/logo_pscb.svg', __FILE__ );
            
            $this->merchant_id 	              = $this->settings['merchant_id'];
            $this->merchant_key		          = $this->settings['merchant_key'];
            $this->companyEmail		          = $this->settings['companyEmail'];
            $this->taxSystem		          = $this->settings['taxSystem'];
            $this->work_mode                  = $this->settings['work_mode'];
			$this->widget                     = $this->settings['widget'];
            $this->accept_all_payments        = $this->settings['accept_all_payments'];
            $this->send_receipt               = $this->settings['send_receipt'];
            $this->default_tax                = $this->settings['default_vat'];
            $this->shipping_tax               = $this->settings['shipping_vat'];
            $this->success_url                = $this->settings['success_url'];
			$this->fail_url 		          = $this->settings['fail_url'];
            $this->payment_method             = $this->settings['payment_method'];
            $this->payment_method_w           = $this->settings['payment_method_w'];
            $this->hold                       = $this->settings['hold'];
            $this->language                   = $this->settings['lang'];
            $this->status_pending             = $this->settings['status_pending'];
            $this->status_success             = (isset($this->settings['status_complete']) ? $this->settings['status_complete'] : $this->settings['status_success']);
            $this->status_fail                = (isset($this->settings['status_failed']) ? $this->settings['status_failed'] : $this->settings['status_fail']);
            $this->default_items_type         = $this->settings['default_items_type'];
            $this->default_items_type_shipp   = $this->settings['default_items_type_shipp'];
            $this->default_items_object       = $this->settings['default_items_object'];
            $this->default_items_unit         = $this->settings['default_items_unit'];
			$this->log_level			      = $this->settings['log_level'];
            $this->customer_info              = $this->settings['customer_info'];
            $this->customer_id                = $this->settings['customer_id'];
            $this->basket_composition         = $this->settings['basket_composition'];
            
            $this->msg['message']	          = '';
            $this->msg['class'] 	          = '';
			
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_oos_response'), 100); //update for woocommerce >2.0

            if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); //update for woocommerce >2.0
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) ); // WC-1.6.6
			}
            
			add_action('woocommerce_receipt_'.$this->id, array(&$this, 'receipt_page'));
            
		}
			
        /**
         * Initiate Form Fields in the Admin Backend
         **/
		function init_form_fields(){
            
            //loading texts for admin fields
            require_once(__DIR__."/oos/namespace.OOS.php");
            \pscb_OOS\Loader::loadClasses();
            
            if(strstr(get_locale(),"en_")){
                $fieldsSettings = new \pscb_OOS\Settings(\pscb_OOS\SETTINGS_LANG_EN,array(__DIR__."/custom_settings.xml"));
            }
            else{
                $fieldsSettings = new \pscb_OOS\Settings(\pscb_OOS\SETTINGS_LANG_RU,array(__DIR__."/custom_settings.xml"));
            }
            
            $this->form_fields = array();
            
            $this->form_fields['enabled'] = array(
                    'title'             => __('Вкл/Выкл:', 'woo_pscb'),
                    'type'                 => 'checkbox',
                    'label'             => __(' ', 'woo_pscb'),
                    'default'             => 'no',
                    'description'         => 'Отображать в списке доступных способов оплаты'
            );
            
            $settings_aliases = $fieldsSettings->getVisibleSettingAliases();
            
            foreach($settings_aliases as $setting_alias){
                $fieldSettings = $fieldsSettings->getSetting($setting_alias);
                $new_line = array(
                    'title'     =>  __($fieldSettings->getTitle(), 'woo_pscb'),
                    'type'      => $fieldSettings->getFieldType(),
                    'default'   => __($fieldSettings->getDefault(), 'woo_pscb'),
                    'description' => __($fieldSettings->getDescription(), 'woo_pscb'),
                    
                );
                if(in_array($setting_alias,array("status_pending","status_failed","status_complete"))){
                    $new_line["options"] = wc_get_order_statuses();
                }else{
					$fieldsettings_options = $fieldSettings->getOptions();
                    $new_line['options'] = (!empty($fieldsettings_options) ? $fieldsettings_options : null);
                }
				$fieldsettings_desciprtion = $fieldSettings->getDescription();
                $new_line['desc_tip'] = !empty($fieldsettings_desciprtion);
				$fieldsettings_required = $fieldSettings->getRequired();
                $new_line['class'] = (!empty($fieldsettings_required) ? 'field-required' : '');
                                
				if($setting_alias == "notification_url"){
					$new_line['default'] = __(get_site_url().'?wc-api=wc_gateway_pscb', 'woo_pscb');
					$new_line['class'] = 'field-readonly';
				}
				
				if($setting_alias == "method_title" || $setting_alias == "method_description"){
					$setting_alias = str_replace("method_","",$setting_alias);
				}
				
                $this->form_fields[$setting_alias] = $new_line;
            }
		}
		
        /**
         * Admin Panel Options
         * - Show info on Admin Backend
         **/
		public function admin_options(){
			global $woocommerce;

			//some wordpress things about redirecting in adminPanel
			if ( $this->redirect_page == '' || $this->redirect_page == 0 ) {
				$return_url = get_permalink( get_option ( 'woocommerce_myaccount_page_id' ) );
			} else {
				$redirect_url_static = get_permalink( $this->redirect_page );
			}
			require_once("assets/templates/adminparams_head.php");
			$this->generate_settings_html();
            $admin_js_url = plugins_url( 'assets/js/admin.js', __FILE__ );
			require_once("assets/templates/adminparams_foot.php");
		}

        /**
         *  There are no payment fields, but we want to show the description if set.
         **/
		function payment_fields(){
			if( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}
		
        /**
         * Receipt Page
         **/
		function receipt_page($order){
			echo $this->generate_pscbpayment_form($order);
		}
    		
        /**
         * Generate button link
         **/
		function generate_pscbpayment_form($order_id){
			global $woocommerce;


            //initial data
            $merchant_id = $this->merchant_id;
            $merchant_key = $this->merchant_key;
            $companyEmail = $this->companyEmail;
            $taxSystem = $this->taxSystem;
            $work_mode = $this->work_mode;
            $send_receipt = $this->send_receipt;
            $default_tax = $this->default_tax;
            $shipping_tax = $this->shipping_tax;
            $payment_method = $this->payment_method;
            $payment_method_w = $this->payment_method_w;
            $hold = $this->hold;
            $lang = (!isset($this->language) ? "" : $this->language);
            $customer_info = $this->customer_info;
            $customer_ident = $this->customer_id;
            $basket_composition = $this->basket_composition;
            $widget = "no";

			$payment_method = ($payment_method == "ac" || $payment_method == "sbp") ? $payment_method : "";
            
			//initializing form creator
            require_once(__DIR__."/oos/namespace.OOS.php");
            \pscb_OOS\Loader::loadClasses("form");
			
			\pscb_OOS\Helper::setLogLevel($this->log_level);

            $formCreator = null;
            try{
                $formCreator = new \pscb_OOS\FormCreator($merchant_id,
                    $merchant_key,
                    $work_mode,
					$send_receipt,
                    $default_tax,
                    $shipping_tax,
                    $payment_method,
                    $hold,
                    $widget,
                    $payment_method_w);
            }catch(\pscb_OOS\GeneralException $e){
                return (new WP_Error('broke',"payment module misconfigured"))->get_error_message();
            }
            			
            $order_data = new \pscb_OOS\Order();
			
            //initial order data
            $order = new WC_Order( $order_id );
            $order_items_info = $order->get_items();
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name = $order->get_billing_last_name();
            $customer_phone = $order->get_billing_phone();
            $customer_email = $order->get_billing_email();
            $customer_comment = $order->get_customer_note();
            \pscb_oos\Helper::doLog("billing first name: ".$billing_first_name,\pscb_OOS\TRACE);
            \pscb_oos\Helper::doLog("billing last name: ".$billing_last_name,\pscb_OOS\TRACE);
            //forming VAT rate - we'll get it from order
            $order_taxes = $order->get_taxes();
            
            $rur_code = 'RUB';
            
            $real_order_currency = $order->get_order_currency();
            $form_data["rub_not_found_error"] = ($real_order_currency != "RUB");
            
            //if it is not RUB - we cannot handle this, throw error
            if($form_data["rub_not_found_error"]){
                $this->id = 'payment';
                return (new WP_Error('broke',"only RUB payments supported"))->get_error_message();
            }
            
            //
            $rur_order_total = $order->get_total();
            $amount = round($rur_order_total,2);
            
            //reading data on order items
            foreach($order_items_info as $order_item_info){
                $item_info = $order_item_info->get_data(); //у товара может быть несколько ставок НДС согласно настройкам WooCommerce
                
                $price_for_one = ($item_info['subtotal'] + $item_info['subtotal_tax'])/($item_info['quantity'] * 1.0);
                
                $price_for_all = $item_info['total'] + $item_info['total_tax'];
                                
                //Здесь выдёргиваем id (первой)ставки НДС
                $rate_array = $item_info['taxes']['total'];
                $tax_id = array_keys($rate_array)[0];
                $tax = (new WC_Tax())->get_rates()[$tax_id]['rate'];

                $name = $item_info['name'];
                
                $order_data->addTS($taxSystem);
                $order_data->addCE($companyEmail);

                $order_item = (new \pscb_OOS\OrderItemBuilder())
                    ->setTitle(strip_tags($name))
                    ->setQuantity($item_info['quantity'])
                    ->setPriceForOne($price_for_one)
                    ->setPriceForAll($price_for_all)
                    ->setTax($formCreator->getItemTax(empty($tax) ? "-1" : $tax))
                    ->setItemType($this->default_items_type)
                    ->setItemObject($this->default_items_object)
                    ->setItemUnit($this->default_items_unit)
                    ->build();
                
                $order_data->addItem($order_item);
            }
            
            //shipping
			$shipping_method = $order->get_shipping_method();
            if(!empty($shipping_method)){
                $shipping_title = $order->get_shipping_method();
                $shipping_cost = $order->get_shipping_total() + $order->get_shipping_tax();
                $shipp_m = array_shift($order->get_shipping_methods());

                //Здесь выдёргиваем id (первой)ставки НДС
                $ship_info = $shipp_m->get_data();
                $rate_array = $ship_info['taxes']['total'];
                $tax_id = array_keys($rate_array)[0];
                $tax = (new WC_Tax())->get_rates()[$tax_id]['rate'];

                $shipping = new \pscb_OOS\OrderShipping($shipping_title,
                                                        $shipping_cost,
                                                        $formCreator->getShippingTax(empty($tax) ? "-1" : $tax),
                                                        $this->default_items_type_shipp);
                $order_data->addShipping($shipping);
                \pscb_OOS\Helper::doLog('shipping title = ' . $shipping_title,\pscb_OOS\TRACE);
		        \pscb_OOS\Helper::doLog('shipping cost = ' . $shipping_cost,\pscb_OOS\TRACE);
		        \pscb_OOS\Helper::doLog('shipping_type = ' .$shipping_order_type,\pscb_OOS\TRACE);
            }
            
            //reading CMS info
            $cms_info = array_combine($formCreator->getCmsInfoKeys(),array_fill(0,count($formCreator->getCmsInfoKeys()),""));
            $cms_info["name"] = "Woocommerce";
            $cms_info["version"] = $woocommerce->version;
            $cms_info["module_version"] = \pscb_OOS\VERSION;

            $customer_phone = ($customer_info == "0") ? "" : $customer_phone;
            $customer_email = ($customer_info == "0") ? "" : $customer_email;
            $customer_comment = ($customer_info == "0") ? "" : $customer_comment;

            $customer_id = ($customer_ident == "0" || $order->get_customer_id() == "0" ) ? "" : $order->get_customer_id();

            
            $params = array("customer_id" => $customer_id,
                            "customer_email" => $customer_email,
                            "customer_phone" => $customer_phone,
                            "customer_comment" => $customer_comment,
                            "order_id" => $order_id,
                            "total" => $amount,
                            "lang" => $lang,
                            "method" => "",
                            "success_url" => $this->success_url,
                            "fail_url" => $this->fail_url,
                            "data_debug" => "",
                            "cms" => $cms_info);

            ($basket_composition == "0") ? $params["details"] =  " " : $params["details"] = "";

			$form_html = "";
			try{
				$form_html = $formCreator->createPaymentForm($params,$order_data);
			}catch(\pscb_OOS\GeneralException $e){
				return (new WP_Error('broke',"cannot create payment form : invalid params accepted"))->get_error_message();
			}
			
			$woocommerce->cart->empty_cart();
			return $form_html;

		}



        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id){
			global $woocommerce;
            $order = new WC_Order($order_id);
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) { // For WC 2.1.0
			  	$checkout_payment_url = $order->get_checkout_payment_url( true );
			} else {
				$checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
			}

			return array(
                        'result' => 'success',
                        'redirect' => add_query_arg(
                            'order',
                            $order->id,
					        add_query_arg(
						        'key',
						        $order->order_key,
						        $checkout_payment_url)));
		}
		
        /**
         * Check for valid gateway server callback
         **/
        function check_oos_response(){
            
            global $woocommerce;
            
            require_once("oos/namespace.OOS.php");
            \pscb_OOS\Loader::loadClasses("notification");
            \pscb_OOS\Helper::setLogLevel($this->log_level);
			
			\pscb_oos\Helper::doLog("Entering notification accepting",\pscb_OOS\TRACE);
			
            if(!\pscb_OOS\NotificationReciever::is_ip_acceptable()){
				\pscb_oos\Helper::doLog("Notification accepting : incorrect IP: ".$_SERVER['REMOTE_ADDR'],\pscb_OOS\ERROR);
                echo "inacceptable ip";
                http_response_code(400);
                die;
            }
            
            $notificationReciever = null;
            try{
                $notificationReciever = new \pscb_OOS\NotificationReciever(
                    $this->merchant_id,
                    $this->merchant_key,
                    preg_replace("/^wc-/","",$this->status_pending),
                    preg_replace("/^wc-/","",$this->status_success),
                    preg_replace("/^wc-/","",$this->status_fail),
                    'pscb_payments',
                    $this->accept_all_payments);
            }catch(\pscb_OOS\GeneralException $e){
                echo "module misonfigured";
                http_response_code(500);
                die;
            }
            
            $encrypted_request = file_get_contents('php://input');
			
            try{
                $ordersArray = $notificationReciever->decodeRequestToArray($encrypted_request);
            }
            catch(\pscb_OOS\GeneralException $e){
                \pscb_oos\Helper::doLog($e->getMessage(),\pscb_OOS\ERROR);
                echo "Decryption methods not available";
                http_response_code(500);
                die;
            }
            catch(Exception $e){
				\pscb_oos\Helper::doLog("Notification accepting : cannot decrypt notification",\pscb_OOS\ERROR);
                http_response_code(400);
                die;
            }
            
            if(empty($ordersArray)){
				\pscb_oos\Helper::doLog("Notification accepting : notification empty",\pscb_OOS\ERROR);
                http_response_code(400);
                die;
            }
            
			\pscb_oos\Helper::doLog("notification decrypted, not empty",\pscb_OOS\TRACE);
			
            foreach($ordersArray as $orderRecord){

                //reading order
                $wcOrderId = $notificationReciever->getOrderIdFromRecord($orderRecord);
				$wcOrder = wc_get_order($wcOrderId);
                if (empty($wcOrder)) {
                    \pscb_oos\Helper::doLog("order not found".$wcOrderId,\pscb_OOS\TRACE);
                    $wcOrder = null;
                }
                
				\pscb_oos\Helper::doLog("accepted info on order #".$wcOrderId,\pscb_OOS\TRACE);
				
                if(!empty($wcOrder)){
                    $converted_total = $wcOrder->get_total();//????
                    $orderInfo = array('total' => round($converted_total,2),
                                       'status' => $wcOrder->get_status());
                }
                else
                    $orderInfo = array();
                
                $localOrder = $notificationReciever->processOrderRecord($orderRecord,$orderInfo);
                
                if(!empty($localOrder['order_status']) && $localOrder['order_status'] != $wcOrder->get_status()){
					\pscb_oos\Helper::doLog("updating order #".$wcOrderId,\pscb_OOS\TRACE);
                    $wcOrder->update_status($localOrder['order_status']);
                }
            }
            
			\pscb_oos\Helper::doLog("notification processed, doing final output",\pscb_OOS\TRACE);
            $notificationReciever->doFinishOutput();
        }

        /**
         * Get Page list from WordPress
         **/
		function pscb_get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
                	$has_parent = $page->post_parent;
                	while($has_parent) {
                    	$prefix .=  ' - ';
                    	$next_page = get_post($has_parent);
                    	$has_parent = $next_page->post_parent;
                	}
            	}
            	// add to page list array array
            	$page_list[$page->ID] = $prefix . $page->post_title;
        	}
        	return $page_list;
		}

	}

	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_gateway_pscb_gateway($methods) {
		$methods[] = 'WC_Gateway_PSCB';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_pscb_gateway' );
	
}

/**
* 'Settings' link on plugin page
**/
add_filter( 'plugin_action_links', 'pscb_add_action_plugin', 10, 5 );
function pscb_add_action_plugin( $actions, $plugin_file ) {
	static $plugin;

	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);
	if ($plugin == $plugin_file) {

			$settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_pscb">' . __('Settings') . '</a>');
    		$actions = array_merge($settings, $actions);
			
	}
	
	return $actions;
}

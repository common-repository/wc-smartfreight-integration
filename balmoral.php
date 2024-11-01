<?php
/* 
Plugin Name: Integration of SmartFreight for WooCommerce
Plugin URI: https://wordpress.org/plugins/wc-smartfreight-integration/	
Description: Integrates your store with your SmartFreight account.
Author: Matrix Internet
Author URI: https://www.matrixinternet.ie/
Version: 1.1.0
WC requires at least: 4.7
WC tested up to: 6.0.0
Text Domain: balmoral
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'WBalmoral_VERSION', '1.1' );
define( 'WBalmoral_PLUGIN', __FILE__ );
define( 'WBalmoral_PLUGIN_BASENAME', plugin_basename( WBalmoral_PLUGIN ) );
define( 'WBalmoral_PLUGIN_NAME', trim( dirname( WBalmoral_PLUGIN_BASENAME ), '/' ) );
define( 'WBalmoral_PLUGIN_DIR', untrailingslashit( dirname( WBalmoral_VERSION ) ) );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'wp_enqueue_scripts', 'add_script' );
    function add_script() {
    	$ajaxurl = admin_url( 'admin-ajax.php' );
        wp_enqueue_style( 'balmoral-css', plugins_url('balmoral.css', __FILE__));
        wp_localize_script( 'balmoral-js', 'coxpress_ajax',
        array( 
            'ajaxurl' => $ajaxurl,
        ));
}
    
    function balmoral_shipping_method() {
        if ( ! class_exists( 'Balmoral_Shipping_Method' ) ) {
            class Balmoral_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'balmoral'; 
                    $this->method_title       = __( 'SmartFreight', 'balmoral' );  
                    $this->method_description = __( 'Custom Shipping Method for Balmoral', 'balmoral' ); 
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Balmoral Shipping', 'tutsplus' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
                        'balmoral_enabled' => array(
                            'title' => __('Enable', 'balmoral'),
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping.', 'balmoral'),
                            'default' => 'yes'
                        ),
                        'balmoral_id' => array(
                            'title' => __('ID', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_password' => array(
                            'title' => __('Password', 'balmoral'),
                            'type' => 'password'
                        ),
                        'balmoral_sendaccno' => array(
                            'title' => __('Sender Account No', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_sendname' => array(
                            'title' => __('Sender Name', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_address1' => array(
                            'title' => __('Address Line 1', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_address2' => array(
                            'title' => __('Address Line 2', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_city' => array(
                            'title' => __('Address City', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_county' => array(
                            'title' => __('County / State', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_postcode' => array(
                            'title' => __('Zip Code', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_country' => array(
                            'title' => __('Country', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_send_eori' => array(
                            'title' => __('Send EORI', 'balmoral'),
                            'type' => 'text'
                        ),
                        'balmoral_rec_eori' => array(
                            'title' => __('Receive EORI', 'balmoral'),
                            'type' => 'text'
                        ),
                    );
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package ) {
                    $woocommerce_balmoral_settings = get_option('woocommerce_balmoral_settings');
                    $enabled = $woocommerce_balmoral_settings['balmoral_enabled'];
                    if ($enabled == 'yes') {
                        $weight_val = 0;
                        $freightlinedetails = "";
                        foreach ($package['contents'] as $values) {
                            if ($values['variation_id'] != "") {
                                $_product = $values['variation_id'];
                            } else {
                                $_product = $values['product_id'];
                            }
                            $quantity = $values['quantity'];
                            $weight = (float)get_post_meta($_product, '_weight', true) * (int)$quantity;
                            //$price = (float)get_post_meta($_product, '_price', true) * (int)$quantity;
                            $price = (int)$quantity;
                            if ($weight == "" || $weight == "0") {
                                $weight_val = $weight_val + 1;
                            }
                            $len = get_post_meta($_product, '_length', true);
                            $hgt = get_post_meta($_product, '_height', true);
                            $wdt = get_post_meta($_product, '_width', true);
                            $cube =  $quantity * (($len * $hgt * $wdt) / 1000000);
                            $freightlinedetails = $freightlinedetails . '<freightlinedetails>
  				<ref>' . get_the_title($_product) . $_product . '</ref>
 				<amt>' . $price . '</amt>
  				<desc>CARTON</desc>
                <len>'.$len.'</len>
                <hgt>'.$hgt.'</hgt>
                <wdt>'.$wdt.'</wdt>
  				<wgt>' . $weight . '</wgt>
  				<cube>'.$cube.'</cube>
 			</freightlinedetails>';
                        }

                        $sendaddr = "";
                        if ($woocommerce_balmoral_settings['enabled'] == 'yes') {
                            if ($woocommerce_balmoral_settings['balmoral_address1']) {
                                $sendaddr = $sendaddr . '<add1>' . $woocommerce_balmoral_settings['balmoral_address1'] . '</add1>';
                            }
                            if ($woocommerce_balmoral_settings['balmoral_address2']) {
                                $sendaddr = $sendaddr . '<add2>' . $woocommerce_balmoral_settings['balmoral_address2'] . '</add2>';
                            }
                            if ($woocommerce_balmoral_settings['balmoral_city']) {
                                $sendaddr = $sendaddr . '<add3>' . $woocommerce_balmoral_settings['balmoral_city'] . '</add3>';
                            }
                            if ($woocommerce_balmoral_settings['balmoral_county']) {
                                $sendaddr = $sendaddr . '<add4>' . $woocommerce_balmoral_settings['balmoral_county'] . '</add4>';
                            }
                            if ($woocommerce_balmoral_settings['balmoral_postcode']) {
                                $sendaddr = $sendaddr . '<add5>' . $woocommerce_balmoral_settings['balmoral_postcode'] . '</add5>';
                            }
                            if ($woocommerce_balmoral_settings['balmoral_country']) {
                                $sendaddr = $sendaddr . '<add6>' . $woocommerce_balmoral_settings['balmoral_country'] . '</add6>';
                            }
                        }
                        $recaddr = "";
                            $country = explode("(",WC()->countries->countries[$package["destination"]["country"]]);
                            if ($package["destination"]["address_1"]) {
                                $recaddr = $recaddr . '<add1>' . $package["destination"]["address_1"] . '</add1>';
                            } else {
                                $recaddr = $recaddr . '<add1>dummy address</add1>';
                            }
                            if ($package["destination"]["address_2"]) {
                                $recaddr = $recaddr . '<add2>' . $package["destination"]["address_2"] . '</add2>';
                            }
                            if ($package["destination"]["city"]) {
                                $recaddr = $recaddr . '<add3>' . $package["destination"]["city"] . '</add3>';
                            }
                            if ($package["destination"]["state"]) {
                                $state_add = WC()->countries->states[$package['destination']['country']][$package['destination']['state']];
                                if(trim($country[0]) == 'Australia'){
                                    $state = $package["destination"]["state"];
                                } else {
                                    if($state_add){
                                        $state = $state_add;
                                    } else {
                                        $state = $package["destination"]["state"];
                                    }
                                }
                                $recaddr = $recaddr . '<add4>' . $state . '</add4>';
                            }
                            if ($package["destination"]["postcode"]) {
                                $recaddr = $recaddr . '<add5>' . $package["destination"]["postcode"] . '</add5>';
                            }
                            if ($package["destination"]["country"]) {
                               
                                $recaddr = $recaddr . '<add6>' .trim($country[0]) . '</add6>';
                            }
                        $calship = 0;
                        if ($weight_val != 0) {
                            $calship = $calship + 1;
                        }
                        if ($sendaddr == "") {
                            $calship = $calship + 1;
                        }
                        if ($recaddr == "") {
                            $calship = $calship + 1;
                        }
                        if ($freightlinedetails == "") {
                            $calship = $calship + 1;
                        }
                        if ($calship == 0) {
                            $xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:onl="http://www.smartfreight.com/online">
   <soapenv:Header/>
   <soapenv:Body>
      <onl:GetDeliveryOptions>
         <!--Optional:-->
         <onl:id>' . $woocommerce_balmoral_settings['balmoral_id'] . '</onl:id>
         <!--Optional:-->
         <onl:passwd>' . $woocommerce_balmoral_settings['balmoral_password'] . '</onl:passwd>
         <!--Optional:-->
         <onl:reference></onl:reference>
         <!--Optional:-->
         <onl:consignment><![CDATA[
		  <connote>
          <sendaddr>' . $sendaddr . '</sendaddr>
          <recaddr>' . $recaddr . '</recaddr>
          <multiplylwh>Yes</multiplylwh>
 			<carriername>[Automatic]</carriername>
 			' . $freightlinedetails . '
	    </connote>]]>
	  </onl:consignment>
      </onl:GetDeliveryOptions>
   </soapenv:Body>
</soapenv:Envelope>';
//echo $xml;
                            try {
                                $wsdl = 'http://api-uk.smartfreight.com/api/soap/classic/online?singleWsdl';
                                $option = array(
                                    "trace" => true,
                                    'Content-Type' => 'text/xml;charset=UTF-8',
                                    'Accept-Encoding' => 'gzip,deflate',
                                    'Content-Length' => strlen($xml),
                                    'Connection' => 'Keep-Alive'
                                );
                                $client = new SoapClient($wsdl, $option);
                                $location_URL = 'http://api-uk.smartfreight.com/api/soap/deliveryoptions';
                                $action_URL = "http://api-uk.smartfreight.com/api/soap/classic/online";
                                $order_return = $client->__doRequest($xml, $location_URL, $action_URL, 1);
                                if ($order_return) {
                                    $order_return1 = xmlstr_to_array(htmlspecialchars_decode($order_return));
                                    $recommended = $order_return1['soap:Body']['onlineResponse']['onlineResult']['deliveryoptionresults']['recommended']['DeliveryOption'];
                                    $otheroptions = $order_return1['soap:Body']['onlineResponse']['onlineResult']['deliveryoptionresults']['otheroptions']['DeliveryOption'];
                                    if ($recommended) {
                                        $this->add_rate(array(
                                            'id' => 'SmartFreight_'.$recommended['accno'] . '_' . $recommended['optionid'],
                                            'label' => $recommended['carriername'] . ' - ' . $recommended['service'],
                                            'cost' => $recommended['primarypricing']
                                        ));
                                    }
                                    if ($otheroptions) {
                                        foreach ($otheroptions as $otheroptions_opt) {
                                            $this->add_rate(array(
                                                'id' => 'SmartFreight_'.$otheroptions_opt['accno'] . '_' . $otheroptions_opt['optionid'],
                                                'label' => $otheroptions_opt['carriername'] . ' - ' . $otheroptions_opt['service'],
                                                'cost' => $otheroptions_opt['primarypricing']
                                            ));
                                        }
                                    }
                                }
                                //echo htmlspecialchars_decode($order_return); exit;
                                $insert_arr = array();
                                $insert_arr['request_name'] = 'deliveryoptions'; 
                                $insert_arr['request'] = $xml; 
                                $insert_arr['response'] = htmlspecialchars_decode($order_return); 
                                $insert_arr['datetimes'] = date("Y-m-d H:i:s"); 
                                add_logs_data($insert_arr);
                                
                            } catch (SoapFault $fault) {
                                $insert_arr = array();
                                $errors =  "SOAP Fault: (faultcode: " . $fault->faultcode . ", faultstring:" . $fault->faultstring . ")";
                                $insert_arr['error'] = $errors; 
                                $insert_arr['datetimes'] = date("Y-m-d H:i:s");
                                add_logs_data($insert_arr);
                            }
                        }
                    }
            }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'balmoral_shipping_method' );
 
    function add_balmoral_shipping_method( $methods ) {
        $methods[] = 'Balmoral_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_balmoral_shipping_method');
}
add_action('woocommerce_thankyou', 'balmoral_thankyou', 10, 1);
function balmoral_thankyou( $order_id ){ 
     if (!$order_id){ return; } 
    $order = wc_get_order($order_id);
    $is_import = get_post_meta($order_id,'is_import',true);
    if($is_import == ""){
    update_post_meta($order_id,'is_import',1);
    $items = $order->get_items();
    $ship_method = $order->get_shipping_method();
    $notes = trim($order->get_customer_note());
    if(strlen($notes) > 90){
        $notes_date = "<Sp1>".substr($notes,0,30)."</Sp1><Sp2>".substr($notes,30,30)."</Sp2><Sp3>".substr($notes,60,30)."</Sp3>";
    } else if(strlen($notes) <= 90 && strlen($notes) >= 61){
        $notes_date = "<Sp1>".substr($notes,0,30)."</Sp1><Sp2>".substr($notes,30,30)."</Sp2><Sp3>".substr($notes,60,30)."</Sp3>";
    } else if(strlen($notes) <= 60 && strlen($notes) >= 31){
        $notes_date = "<Sp1>".substr($notes,0,30)."</Sp1><Sp2>".substr($notes,30,30)."</Sp2>";
    } else {
        $notes_date = "<Sp1>".$notes."</Sp1>";
    }
    $ship_method_id ="";
    foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
        foreach($shipping_item_obj->rates as $key=>$val){
            print_r($val);
        }

        $ship_method_id = $shipping_item_obj->get_method_id();
    }
    if (trim($ship_method_id) == 'balmoral') {
        $woocommerce_balmoral_settings = get_option('woocommerce_balmoral_settings');
        $custom_shipping_method = explode("_",get_post_meta( $order_id, 'custom_shipping_method', true ));
        $order_data = $order->get_data();
        $pass_shipp = explode("-", trim($ship_method));
        $store_arr = array();
        $freightlinedetails = "";
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product_variation_id = $item->get_variation_id();
            if ($product_variation_id != "") {
                $_product = $product_variation_id;
            } else {
                $_product = $product_id;
            }
            $quantity = $item->get_quantity();
            //$weight = get_post_meta($_product, '_weight', true);
            $weight = (float)get_post_meta($_product, '_weight', true) * (int)$quantity;
            $price = get_post_meta($_product, '_price', true);
            $len = get_post_meta($_product, '_length', true);
            $hgt = get_post_meta($_product, '_height', true);
            $wdt = get_post_meta($_product, '_width', true);
            $cube =  $quantity * (($len * $hgt * $wdt) / 1000000);
            $freightlinedetails = $freightlinedetails . '<freightlinedetails>
  				<ref>' . get_the_title($_product) . $_product . '</ref>
 				<amt>' . $quantity . '</amt>
  				<desc>CARTON</desc>
  				<wgt>' . $weight . '</wgt>
                <len>'.$len.'</len>
                <hgt>'.$hgt.'</hgt>
                <wdt>'.$wdt.'</wdt>
  				<cube>'.$cube.'</cube>
 			</freightlinedetails>';
        }
        $recaddr = "";
        $country = explode("(",WC()->countries->countries[$order_data['shipping']['country']]);
        $state_add = WC()->countries->states[$order_data['shipping']['country']][$order_data['shipping']['state']];
        //$state_add = $order_data['shipping']['state'];
        echo "states :: ". $order_data['shipping']['state'].'<br><br>';
        echo "country :: ". trim($country[0]).'<br><br>';
        if(trim($country[0]) == 'Australia'){
            $state = $order_data['shipping']['state'];
        } else {
            if($state_add){
                $state = $state_add;
            } else {
                $state = $order_data['shipping']['state'];
            }
        }
        
        if ($order_data['billing']['company']) {
            $recaddr = $recaddr . '<placename>'.$order_data['billing']['company'].'</placename>';
        }
        if ($order_data['shipping']['address_1']) {
            $recaddr = $recaddr . '<add1>' . $order_data['shipping']['address_1'] . '</add1>';
        }
        if ($order_data['shipping']['address_2']) {
            $recaddr = $recaddr . '<add2>' . $order_data['shipping']['address_2'] . '</add2>';
        }
        if ($order_data['shipping']['city']) {
            $recaddr = $recaddr . '<add3>' . $order_data['shipping']['city'] . '</add3>';
        }
        if ($order_data['shipping']['state']) {
            $recaddr = $recaddr . '<add4>' . $state. '</add4>';
        }
        if ($order_data['shipping']['postcode']) {
            $recaddr = $recaddr . '<add5>' . $order_data['shipping']['postcode'] . '</add5>';
        }
        if ($order_data['shipping']['country']) {
            $recaddr = $recaddr . '<add6>' . trim($country[0]) . '</add6>';
        }
        $sendaddr = "";
        if ($woocommerce_balmoral_settings['enabled'] == 'yes') {
            if ($woocommerce_balmoral_settings['balmoral_address1']) {
                $sendaddr = $sendaddr . '<add1>' . $woocommerce_balmoral_settings['balmoral_address1'] . '</add1>';
            }
            if ($woocommerce_balmoral_settings['balmoral_address2']) {
                $sendaddr = $sendaddr . '<add2>' . $woocommerce_balmoral_settings['balmoral_address2'] . '</add2>';
            }
            if ($woocommerce_balmoral_settings['balmoral_city']) {
                $sendaddr = $sendaddr . '<add3>' . $woocommerce_balmoral_settings['balmoral_city'] . '</add3>';
            }
            if ($woocommerce_balmoral_settings['balmoral_county']) {
                $sendaddr = $sendaddr . '<add4>' . $woocommerce_balmoral_settings['balmoral_county'] . '</add4>';
            }
            if ($woocommerce_balmoral_settings['balmoral_postcode']) {
                $sendaddr = $sendaddr . '<add5>' . $woocommerce_balmoral_settings['balmoral_postcode'] . '</add5>';
            }
            if ($woocommerce_balmoral_settings['balmoral_country']) {
                $sendaddr = $sendaddr . '<add6>' . $woocommerce_balmoral_settings['balmoral_country'] . '</add6>';
            }
        }
        $additionalreferences = "";
        if(strtolower($country[0]) != "ireland"){$additionalreferences = '<additionalreferences>
      <predefinedtype>send_eori</predefinedtype>
      <referenceno>'.$woocommerce_balmoral_settings['balmoral_send_eori'].'</referenceno>
   </additionalreferences>
   <additionalreferences>
      <predefinedtype>rec_eori</predefinedtype>
      <referenceno>'.$woocommerce_balmoral_settings['balmoral_rec_eori'].'</referenceno>
   </additionalreferences>';}
        $xml_import = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:onl="http://www.smartfreight.com/online">
   <soapenv:Header/>
   <soapenv:Body>
      <onl:Import>
         <onl:id>' . $woocommerce_balmoral_settings['balmoral_id'] . '</onl:id>
         <onl:passwd>' . $woocommerce_balmoral_settings['balmoral_password'] . '</onl:passwd>
         <onl:reference>' . $order_id . '</onl:reference>
         <onl:consignmentxml><![CDATA[<connote>
      <condate>' . date("d/m/Y") . '</condate>
      <accno>'.trim($custom_shipping_method[1]).'</accno>
      <recaccno>'.$order_id.'</recaccno>
      <multiplylwh>Yes</multiplylwh>
      <sendname>' . $woocommerce_balmoral_settings['balmoral_sendname'] . '</sendname>
      <sendaddr>' . $sendaddr . '</sendaddr>
      <recname>' . trim($order_data['shipping']['first_name'] . ' ' . $order_data['shipping']['last_name']) . '</recname>
      <recph>' . $order_data['billing']['phone'] . ' </recph>
      <reccontact>' . trim($order_data['shipping']['first_name'] . ' ' . $order_data['shipping']['last_name']) . '</reccontact>
      <recemail>' . $order_data['billing']['email'] . '</recemail>'.$additionalreferences.'
      <recaddr>' . $recaddr . '</recaddr>
      <carriername>' . trim($pass_shipp[0]) . '</carriername>
          <service>' . trim($pass_shipp[1]) . '</service>
          <spins>'.$notes_date.'</spins>' . $freightlinedetails . '</connote>]]></onl:consignmentxml></onl:Import></soapenv:Body></soapenv:Envelope>';
        try {
            $wsdl = 'http://api-uk.smartfreight.com/api/soap/classic/online?singleWsdl';
            $option = array(
                "trace" => true,
                'Content-Type' => 'text/xml;charset=UTF-8',
                'Accept-Encoding' => 'gzip,deflate',
                'Content-Length' => strlen($xml_import),
                'Connection' => 'Keep-Alive'
            );
            $client = new SoapClient($wsdl, $option);
            $location_URL = 'http://api-uk.smartfreight.com/api/soap/classic';
            $action_URL = "http://www.smartfreight.com/online/SFOv1/Import";
            $order_return = $client->__doRequest($xml_import, $location_URL, $action_URL, 1);
            $order_return1 = xmlstr_to_array(htmlspecialchars_decode($order_return));
            $allocatedconid =  $order_return1['soap:Body']['ImportResponse']['ImportResult']['Connote']['allocatedconid'];
            $trackingid =  $order_return1['soap:Body']['ImportResponse']['ImportResult']['Connote']['trackingid'];
            update_post_meta($order_id,'btrackingnumber',$trackingid);
            update_post_meta($order_id,'allocatedconid',$allocatedconid);
            $insert_arr = array();
            $insert_arr['orderid'] = $order_id;
            $insert_arr['request_name'] = 'Import'; 
            $insert_arr['request'] = $xml_import; 
            $insert_arr['response'] = htmlspecialchars_decode($order_return); 
            $insert_arr['datetimes'] = date("Y-m-d H:i:s"); 
            add_logs_data($insert_arr);
        } catch (SoapFault $fault) {
            $insert_arr = array();
            $errors = "SOAP Fault: (faultcode: " . $fault->faultcode . ", faultstring:" . $fault->faultstring . ")";
            $insert_arr['orderid'] = $order_id;
            $insert_arr['error'] = $errors;
            $insert_arr['datetimes'] = date("Y-m-d H:i:s");
            add_logs_data($insert_arr);
        }
    }
    }
}
function xmlstr_to_array($xmlstr){
	$doc = new DOMDocument();
	$doc->loadXML($xmlstr);
	$root = $doc->documentElement;
	$output = domnode_to_array($root);
	return $output;
}
function domnode_to_array($node) {
	$output = array();
	switch ($node->nodeType) {
		case XML_CDATA_SECTION_NODE:
		case XML_TEXT_NODE:
			$output = trim($node->textContent);
			break;
		case XML_ELEMENT_NODE:
			for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
				$child = $node->childNodes->item($i);
				$v = domnode_to_array($child);
				if(isset($child->tagName)) {
					$t = $child->tagName;
					if(!isset($output[$t])) {
						$output[$t] = array();
					}
					$output[$t][] = $v;
				}
				elseif($v || $v === '0') {
					$output = (string) $v;
				}
			}
			if($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
				$output = array('@content'=>$output); //Change output into an array.
			}
			if(is_array($output)) {
				if($node->attributes->length) {
					$a = array();
					foreach($node->attributes as $attrName => $attrNode) {
						$a[$attrName] = (string) $attrNode->value;
					}
					$output['@attributes'] = $a;
				}
				foreach ($output as $t => $v) {
					if(is_array($v) && count($v)==1 && $t!='@attributes') {
						$output[$t] = $v[0];
					}
				}
			}
			break;
	}
	return $output;
}
function add_logs_data($insert_arr){
    global $wpdb;
    $table_logs = $wpdb->prefix . 'balmoral_logs';
    $wpdb->insert($table_logs, $insert_arr);
}
add_action( 'init', 'create_tabels_cpt' );
function create_tabels_cpt() {
global $wpdb;
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
$charset_collate = $wpdb->get_charset_collate();
$table_logs = $wpdb->prefix . 'balmoral_logs';
$sql_airport = "CREATE TABLE $table_logs (
id int(11) NOT NULL AUTO_INCREMENT,
orderid varchar(50) NULL,
request_name varchar(50) NULL,
request text NULL,
response text NULL,
error text NULL,
datetimes datetime NULL,
PRIMARY KEY (id)
) $charset_collate;";
dbDelta( $sql_airport );
}

function balmoral_account_orders_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $name) {
        $new_columns[$key] = $name;
        // add ship-to after order status column
        if ('order-status' === $key) {
            $new_columns['tracking-data'] = __('Tracking Number', 'textdomain');
        }
    }
    return $new_columns;
}

add_filter('woocommerce_my_account_my_orders_columns', 'balmoral_account_orders_column');
function balmoral_new_data_column($order) {
    $btrackingnumber = get_post_meta($order->get_id(), 'btrackingnumber', true);
    $Labelno = "";
    if($btrackingnumber){
        echo!empty($btrackingnumber) ? 'https://www.smartfreight.com/tracking/'.$btrackingnumber : '-';
    } else {
        $new_data = get_post_meta($order->get_id(), 'allocatedconid', true);
        if($new_data){
        $woocommerce_balmoral_settings = get_option('woocommerce_balmoral_settings');
        $xml_con = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:onl="http://www.smartfreight.com/online">
   <soapenv:Header/>
   <soapenv:Body>
      <onl:TrackingEvents>
         <onl:id>'.$woocommerce_balmoral_settings['balmoral_id'].'</onl:id>
         <onl:passwd>'.$woocommerce_balmoral_settings['balmoral_password'].'</onl:passwd>
         <onl:conid>'.$new_data.'</onl:conid>
      </onl:TrackingEvents>
   </soapenv:Body>
</soapenv:Envelope>';
        try {
    $wsdl = 'http://api-uk.smartfreight.com/api/soap/classic/online?singleWsdl';
    $option = array(
        "trace" => true, 
        'Content-Type' => 'text/xml;charset=UTF-8',
        'Accept-Encoding' => 'gzip,deflate', 
        'Content-Length' => strlen($xml_con), 
        'Connection' => 'Keep-Alive'
    );
    $client = new SoapClient($wsdl, $option);
    $location_URL = 'http://api-uk.smartfreight.com/api/soap/classic';
    $action_URL = "http://www.smartfreight.com/online/SFOv1/TrackingEvents";
    $order_return = $client->__doRequest($xml_con, $location_URL, $action_URL,1);
    $order_return1 = xmlstr_to_array(str_replace('<?xml version="1.0" encoding="utf-8" ?>',"",htmlspecialchars_decode($order_return)));
   // echo '<pre>';
    //print_r($order_return1);
    $Labelno =  $order_return1['soap:Body']['TrackingEventsResponse']['TrackingEventsResult']['TrackingEvents']['Events']['Event']['Labelno'];
    if($Labelno){
        update_post_meta($order->get_id(),'btrackingnumber',$Labelno);
            $insert_arr = array();
            $insert_arr['orderid'] = $order->get_id();
            $insert_arr['request_name'] = 'Import'; 
            $insert_arr['request'] = $xml_import; 
            $insert_arr['response'] = htmlspecialchars_decode($order_return); 
            $insert_arr['datetimes'] = date("Y-m-d H:i:s"); 
            add_logs_data($insert_arr);
    } else {
        update_post_meta($order->get_id(),'btrackingnumber','Not Available');
    }
     
    
} catch (SoapFault $fault) {
     $insert_arr = array();
            $errors = "SOAP Fault: (faultcode: " . $fault->faultcode . ", faultstring:" . $fault->faultstring . ")";
            $insert_arr['orderid'] = $order->get_id();
            $insert_arr['error'] = $errors;
            $insert_arr['datetimes'] = date("Y-m-d H:i:s");
            add_logs_data($insert_arr);
}
        }
echo!empty($Labelno) ? $Labelno : '-';
    }
}
add_action('woocommerce_my_account_my_orders_column_tracking-data', 'balmoral_new_data_column');

add_action( 'add_meta_boxes', 'mv_add_meta_boxes_vl' );
function mv_add_meta_boxes_vl(){
    add_meta_box( 'balmoral_fields', __('Tracking','woocommerce'), 'balmoral_packaging', 'shop_order', 'side', 'core' );
}

function balmoral_packaging(){
    global $post;
    $order_id = $post->ID;
    $order = wc_get_order($order_id);
    $ship_method_id ="";
    foreach( $order->get_items( 'shipping' ) as $shipping_item_obj ){
        $ship_method_id = $shipping_item_obj->get_method_id();
    }
    if (trim($ship_method_id) == 'balmoral') {
        $num = get_post_meta($order->get_id(), 'btrackingnumber', true);
        if($num){
            echo 'https://www.smartfreight.com/tracking/'.$num;
        }
    }
}

add_action('woocommerce_checkout_update_order_meta', 'cw_checkout_order_meta');
function cw_checkout_order_meta( $order_id ) {
    if ($_POST['shipping_method']) {
        update_post_meta( $order_id, 'custom_shipping_method', esc_attr(json_encode($_POST['shipping_method'])));
    }
}
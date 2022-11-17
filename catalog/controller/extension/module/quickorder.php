<?php
class ControllerExtensionModuleQuickorder extends Controller {

    private function getCountryDetail($country_id){

        $query=$this->db->query("select * from ".DB_PREFIX."country where country_id='".$country_id."'");

        return $query->row;

    }
    private function getZoneDetail($zone_id){
        $query=$this->db->query("select * from ".DB_PREFIX."zone where zone_id='".$zone_id."'");

        return $query->row;
        
    }
    private function getProductId(){
        $product_query=$this->db->query("select product_id from ".DB_PREFIX."product where model='dynamic_product'");

        if(!$product_query->num_rows){

            $this->db->query("INSERT INTO ".DB_PREFIX."product SET model = 'dynamic_product', sku = '', upc = '', ean = '', jan = '', isbn = '', mpn = '', location = '', quantity = '1', minimum = '1', subtract = '0', stock_status_id = '6', date_available = '2019-10-31', manufacturer_id = '0', shipping = '0', price = '0', points = '0', weight = '0', weight_class_id = '1', length = '0', width = '0', height = '0', length_class_id = '1', status = '1', tax_class_id = '0', sort_order = '1', date_added = NOW()");

            $product_id=$this->db->getLastId();

            $this->db->query("INSERT INTO ".DB_PREFIX."product_description SET product_id = '".$product_id."', language_id = '".$this->config->get('config_language_id')."', name = 'Dynamic', description = '', tag = '', meta_title = 'Dynamic', meta_description = '', meta_keyword = ''");

            $this->db->query("INSERT INTO ".DB_PREFIX."product_to_store SET product_id = '".$product_id."', store_id = '0'");

        }else{
            $product_id=$product_query->row['product_id'];
        }

        return $product_id;
    }
    private function getOptionId(){

        $option_query=$this->db->query("select option_id from ".DB_PREFIX."option_description where name='dynamic_option'");

        if(!$option_query->num_rows){

            $this->db->query("INSERT INTO `".DB_PREFIX."option` SET type = 'radio', sort_order = '1'");

            $option_id=$this->db->getLastId();

            $this->db->query("INSERT INTO ".DB_PREFIX."option_description SET language_id = '".$this->config->get('config_language_id')."', name = 'dynamic_option', option_id = '".$option_id."'");

        }else{
            $option_id=$option_query->row['option_id'];
        }

        return $option_id;


    }
    private function getOptionValueId($option_id,$name){
         $option_query=$this->db->query("select option_value_id from ".DB_PREFIX."option_value_description where name='".$name."'");

        if(!$option_query->num_rows){

        $this->db->query("INSERT INTO ".DB_PREFIX."option_value SET option_id = '".$option_id."', image = '', sort_order = '1'");
        $option_value_id=$this->db->getLastId();
        $this->db->query("INSERT INTO ".DB_PREFIX."option_value_description SET language_id = '".$this->config->get('config_language_id')."', option_id = '".$option_id."', name = '".$name."',option_value_id = '".$option_value_id."'");
        }else{
            $option_value_id=$option_query->row['option_value_id'];
        }

        return $option_value_id;
    }


    
    private function getProductOptionId($product_id,$option_id){

        $option_query=$this->db->query("select product_option_id from ".DB_PREFIX."product_option where option_id=".$option_id);

        if(!$option_query->num_rows){

            $this->db->query("INSERT INTO ".DB_PREFIX."product_option SET product_id = '".$product_id."', option_id = '".$option_id."', required = '1'");

            $product_option_id=$this->db->getLastId();




        }else{
            $product_option_id=$option_query->row['product_option_id'];
        }

        return $product_option_id;


    }


    private function getProductOptionValueId($product_id,$option_id,$option_value_id,$price,$product_option_id){
        
        $option_query=$this->db->query("select product_option_value_id from ".DB_PREFIX."product_option_value where option_value_id=".$option_value_id);

        if(!$option_query->num_rows){

            $this->db->query("INSERT INTO ".DB_PREFIX."product_option_value SET product_option_id = '".$product_option_id."', product_id = '".$product_id."', option_id = '".$option_id."', option_value_id = '".$option_value_id."', quantity = '1', subtract = '0', price = '".$price."', price_prefix = '+', points = '0', points_prefix = '+', weight = '0', weight_prefix = '+'");

            $product_option_value_id=$this->db->getLastId();




        }else{
            $product_option_value_id=$option_query->row['product_option_value_id'];
        }

        return $product_option_value_id;
    }


    private function cartUpdate($price,$name){
        $product_id=$this->getProductId();

        $option_id=$this->getOptionId();
        $option_value_id=$this->getOptionValueId($option_id,$name);
        $product_option_id=$this->getProductOptionId($product_id,$option_id);

        $product_option_value_id=$this->getProductOptionValueId($product_id,$option_id,$option_value_id,$price,$product_option_id);
        $this->db->query("delete from ".DB_PREFIX."cart where session_id='".$this->session->getId()."'");
        $option = [$product_option_id=>$product_option_value_id];
        $this->db->query("insert into ".DB_PREFIX."cart set customer_id = 0, product_id = '" . (int)$product_id . "', recurring_id = 0, `option` = '".$this->db->escape(json_encode($option))."', quantity = 1, date_added = NOW(), session_id = '" . $this->db->escape($this->session->getId()) . "'");
    }
    public function index() {

        $quickorder_id=$this->request->get['q'];
        $data=$this->db->query("select data from ".DB_PREFIX."quickorder where quickorder_id=".(int)$quickorder_id);
        
        if(!$data->num_rows){
            http_response_code(404);
            die;
        }
        $data=json_decode($data->row['data']);


        $this->cartUpdate($data->quickorder_amount,$data->quickorder_product);

        $country=$this->getCountryDetail($data->country_id);
        $zone=$this->getZoneDetail($data->zone_id);

        $shipping_address= Array(
      
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'company' => $data->company,
            'address_1' =>$data->address_1 ,
            'address_2' =>$data->address_2,
            'postcode' => $data->postcode,
            'city' => $data->city ,
            'country_id' => $data->country_id,
            'zone_id' => $data->zone_id,
            'country' => $country['name'],
            'iso_code_2' => $country['iso_code_2'],
            'iso_code_3' => $country['iso_code_3'],
            'address_format' =>$country['address_format'] ,
            'zone' => $zone['name'],
            'zone_code' => $zone['code'],
            'custom_field' => (array)$data->custom_field

        );

        $this->session->data = Array
(
    'language' => $this->session->data['language'],
    'currency' => $this->session->data['currency'],
    'token'=>$this->session->getId(),
    'account' => 'guest',
    'guest' => Array
        (
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'email' => $data->email,
            'telephone' => '',
            'fax' => '',
        ),
                       

    'shipping_address' => Array(),

    'payment_address' => $shipping_address,

    'shipping_address' => $shipping_address,
    'payment_methods' => Array(
        $data->quickorder_payment=>Array(
            'code'=>$data->quickorder_payment,
            'title'=>$data->payment_title,
            'terms'=>'',
            'sort_order'=>1
        )
    ),
    'payment_method' => $data->quickorder_payment
    

);

    $this->response->redirect($this->url->link('extension/checkout/quickorder', '', true));

    }
}
<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Payment;
class Stripe extends \Opencart\System\Engine\Controller {
    public function index(): string { $this->load->language('extension/sixmoments/payment/stripe'); return $this->load->view('extension/sixmoments/payment/stripe',['button_confirm'=>$this->language->get('button_confirm'),'confirm'=>$this->url->link('extension/sixmoments/payment/stripe.confirm','language='.$this->config->get('config_language'))]); }
    public function confirm(): void {
        $this->load->language('extension/sixmoments/payment/stripe');$json=[];$order_id=(int)($this->session->data['order_id']??0);
        if(!$order_id||!isset($this->session->data['payment_method'])||$this->session->data['payment_method']['code']!=='stripe.stripe')$json['error']=$this->language->get('error_order');
        $this->load->model('checkout/order');$order=$order_id?$this->model_checkout_order->getOrder($order_id):[];if(!$order)$json['error']=$this->language->get('error_order');
        if(!$json){$session=$this->createSession($order);if(!empty($session['url'])){$pending=((array)$this->config->get('config_pending_status'))[0]??0;if($pending)$this->model_checkout_order->addHistory($order_id,(int)$pending,'Stripe Checkout session '.$session['id'],false);$json['redirect']=$session['url'];}else{$json['error']=$session['error']['message']??$this->language->get('error_api');}}
        $this->response->addHeader('Content-Type: application/json');$this->response->setOutput(json_encode($json));
    }
    public function success(): void { $this->response->redirect($this->url->link('checkout/success','language='.$this->config->get('config_language'),true)); }
    public function webhook(): void {
        $payload=file_get_contents('php://input')?:'';$signature=$this->request->server['HTTP_STRIPE_SIGNATURE']??'';$secret=(string)$this->config->get('payment_stripe_webhook_secret');
        if(!$secret||!$this->validSignature($payload,$signature,$secret)){http_response_code(400);return;}$event=json_decode($payload,true);
        if(($event['type']??'')==='checkout.session.completed'){$session=$event['data']['object']??[];$order_id=(int)($session['metadata']['order_id']??$session['client_reference_id']??0);if($order_id){$this->load->model('checkout/order');$order=$this->model_checkout_order->getOrder($order_id);if($order){$status=(int)$this->config->get('payment_stripe_order_status_id');if(!$status)$status=(int)(((array)$this->config->get('config_processing_status'))[0]??0);$this->model_checkout_order->addHistory($order_id,$status,'Stripe payment confirmed: '.($session['payment_intent']??$session['id']),true);}}}
        $this->response->addHeader('Content-Type: application/json');$this->response->setOutput(json_encode(['received'=>true]));
    }
    private function createSession(array $order): array {
        $key=(string)$this->config->get('payment_stripe_secret_key');$success=$this->url->link('extension/sixmoments/payment/stripe.success','language='.$this->config->get('config_language').'&session_id={CHECKOUT_SESSION_ID}',true);$cancel=$this->url->link('checkout/checkout','language='.$this->config->get('config_language'),true);
        $body=['mode'=>'payment','client_reference_id'=>(string)$order['order_id'],'customer_email'=>$order['email'],'success_url'=>html_entity_decode($success,ENT_QUOTES,'UTF-8'),'cancel_url'=>html_entity_decode($cancel,ENT_QUOTES,'UTF-8'),'metadata'=>['order_id'=>(string)$order['order_id']],'automatic_payment_methods'=>['enabled'=>'true'],'line_items'=>[['price_data'=>['currency'=>strtolower($order['currency_code']),'unit_amount'=>(int)round((float)$order['total']*100),'product_data'=>['name'=>'6MOMENTS order #'.$order['order_id']]],'quantity'=>1]]];
        $ch=curl_init('https://api.stripe.com/v1/checkout/sessions');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/x-www-form-urlencoded','Idempotency-Key: sixmoments-order-'.$order['order_id']],CURLOPT_POSTFIELDS=>http_build_query($body)]);$response=curl_exec($ch);if($response===false)return ['error'=>['message'=>curl_error($ch)]];$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$json=json_decode($response,true)?:['error'=>['message'=>'Invalid Stripe response']];if($status<200||$status>=300)return $json;return $json;
    }
    private function validSignature(string $payload,string $header,string $secret): bool { $timestamp=0;$signatures=[];foreach(explode(',',$header) as $part){[$key,$value]=array_pad(explode('=',$part,2),2,'');if($key==='t')$timestamp=(int)$value;if($key==='v1')$signatures[]=$value;}if(!$timestamp||abs(time()-$timestamp)>300)return false;$expected=hash_hmac('sha256',$timestamp.'.'.$payload,$secret);foreach($signatures as $signature)if(hash_equals($expected,$signature))return true;return false; }
}

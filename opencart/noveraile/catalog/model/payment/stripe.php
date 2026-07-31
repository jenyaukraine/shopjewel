<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Payment;
class Stripe extends \Opencart\System\Engine\Model {
    public function getMethods(array $address = []): array {
        $this->load->language('extension/noveraile/payment/stripe');
        if (!$this->config->get('payment_stripe_status') || !$this->config->get('payment_stripe_secret_key')) return [];
        return ['code'=>'stripe','name'=>$this->language->get('heading_title'),'option'=>['stripe'=>['code'=>'stripe.stripe','name'=>$this->language->get('heading_title')]],'sort_order'=>(int)$this->config->get('payment_stripe_sort_order')];
    }
}

<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile;

class Coupon extends \Opencart\System\Engine\Controller {
    public function apply(): void {
        $language = 'language=' . $this->config->get('config_language');
        $status = 'error';

        if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
            $coupon = trim((string)($this->request->post['coupon'] ?? ''));
            if ($coupon !== '') {
                $this->load->model('marketing/coupon');
                $info = $this->model_marketing_coupon->getCoupon($coupon);
                if ($info) {
                    $this->session->data['coupon'] = $coupon;
                    $status = 'success';
                }
            }
        }

        $this->response->redirect($this->url->link('checkout/cart', $language . '&coupon_status=' . $status));
    }
}

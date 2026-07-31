<?php
namespace Opencart\Admin\Controller\Extension\Sixmoments\Payment;
class Stripe extends \Opencart\System\Engine\Controller { public function index(): void { $this->response->redirect($this->url->link('extension/sixmoments/module/sixmoments','user_token='.$this->session->data['user_token'])); } }

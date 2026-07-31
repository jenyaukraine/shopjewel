<?php
namespace Opencart\Admin\Controller\Extension\Noveraile\Shipping;
class Dhl extends \Opencart\System\Engine\Controller { public function index(): void { $this->response->redirect($this->url->link('extension/noveraile/module/noveraile','user_token='.$this->session->data['user_token'])); } }

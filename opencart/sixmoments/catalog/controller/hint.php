<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments;
class Hint extends \Opencart\System\Engine\Controller {
    public function send(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];
        $p = [
            'product_id' => (int)($this->request->post['product_id'] ?? 0),
            'sender_name' => trim((string)($this->request->post['sender_name'] ?? '')),
            'sender_email' => trim((string)($this->request->post['sender_email'] ?? '')),
            'recipient_name' => trim((string)($this->request->post['recipient_name'] ?? '')),
            'recipient_email' => trim((string)($this->request->post['recipient_email'] ?? '')),
            'message' => trim((string)($this->request->post['message'] ?? ''))
        ];

        foreach (['sender_name', 'recipient_name'] as $key) {
            if ($p[$key] === '' || mb_strlen($p[$key]) > 96) $json['error'] = $this->language->get('six_error_required');
            $p[$key] = str_replace(["\r", "\n"], ' ', $p[$key]);
        }
        if (!filter_var($p['sender_email'], FILTER_VALIDATE_EMAIL) || !filter_var($p['recipient_email'], FILTER_VALIDATE_EMAIL) || strlen($p['sender_email']) > 190 || strlen($p['recipient_email']) > 190) {
            $json['error'] = $this->language->get('six_error_email');
        }
        if (mb_strlen($p['message']) > 2000) $json['error'] = $this->language->get('six_error_message_length');
        if ((int)($this->session->data['sixmoments_hint_time'] ?? 0) > time() - 60) $json['error'] = $this->language->get('six_error_rate_limit');

        $this->load->model('catalog/product');
        if (!$p['product_id'] || !$this->model_catalog_product->getProduct($p['product_id'])) {
            $json['error'] = $this->language->get('six_error_product');
        }

        if (!$json) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "sixmoments_hint` SET `product_id`='" . $p['product_id'] . "',`sender_name`='" . $this->db->escape($p['sender_name']) . "',`sender_email`='" . $this->db->escape($p['sender_email']) . "',`recipient_name`='" . $this->db->escape($p['recipient_name']) . "',`recipient_email`='" . $this->db->escape($p['recipient_email']) . "',`message`='" . $this->db->escape($p['message']) . "',`language_code`='" . $this->db->escape($this->config->get('config_language')) . "',`date_added`=NOW()");
            $this->session->data['sixmoments_hint_time'] = time();
            $this->sendMail($p);
            $json['success'] = $this->language->get('six_success_hint');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    private function sendMail(array $p): void { if(!$this->config->get('config_mail_engine'))return;$o=['parameter'=>$this->config->get('config_mail_parameter'),'smtp_hostname'=>$this->config->get('config_mail_smtp_hostname'),'smtp_username'=>$this->config->get('config_mail_smtp_username'),'smtp_password'=>html_entity_decode($this->config->get('config_mail_smtp_password'),ENT_QUOTES,'UTF-8'),'smtp_port'=>$this->config->get('config_mail_smtp_port'),'smtp_timeout'=>$this->config->get('config_mail_smtp_timeout')];$mail=new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'),$o);$mail->setTo($p['recipient_email']);$mail->setFrom($this->config->get('config_email'));$mail->setReplyTo($p['sender_email']);$mail->setSender((string)($this->config->get('module_sixmoments_brand_name') ?: $this->config->get('config_name')));$mail->setSubject($p['sender_name'].' thought you might love this');$mail->setText($p['message']."\n\n".$this->url->link('product/product','product_id='.(int)$p['product_id'],true));$mail->send(); }
}

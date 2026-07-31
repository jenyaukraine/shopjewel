<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments;
class Newsletter extends \Opencart\System\Engine\Controller {
    public function subscribe(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];
        $email = trim((string)($this->request->post['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $json['error'] = $this->language->get('six_error_email');
        } elseif (empty($this->request->post['consent'])) {
            $json['error'] = $this->language->get('six_error_consent');
        }

        if (!$json) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "sixmoments_subscriber` SET `email`='" . $this->db->escape($email) . "', `language_code`='" . $this->db->escape($this->config->get('config_language')) . "', `consent`='1', `date_added`=NOW() ON DUPLICATE KEY UPDATE `language_code`=VALUES(`language_code`), `consent`='1'");
            $json['success'] = $this->language->get('six_success_subscribe');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}

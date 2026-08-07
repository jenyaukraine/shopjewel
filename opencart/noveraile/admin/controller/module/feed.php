<?php
namespace Opencart\Admin\Controller\Extension\Noveraile\Module;

/**
 * Supplier feed import endpoints.
 *
 * The feed is roughly 10 MB and every article downloads up to eleven images
 * from the supplier CDN, so a single request can never finish the job. Upload
 * queues the work, and the admin page then drives `process` in a loop until the
 * queue is empty.
 */
class Feed extends \Opencart\System\Engine\Controller {
    private const PERMISSION = 'extension/noveraile/module/noveraile';
    private const MAX_UPLOAD_BYTES = 48 * 1024 * 1024;

    public function upload(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        try {
            $this->assertPermission();

            $upload = $this->request->files['feed_file'] ?? [];
            $path = (string)($upload['tmp_name'] ?? '');
            $name = basename((string)($upload['name'] ?? ''));

            if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($path)) {
                throw new \RuntimeException($this->language->get('error_feed_upload'));
            }
            if (!str_ends_with(strtolower($name), '.csv')) {
                throw new \RuntimeException($this->language->get('error_feed_file'));
            }
            if ((int)($upload['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
                throw new \RuntimeException($this->language->get('error_feed_size'));
            }

            $this->load->model('extension/noveraile/module/feed');
            $json = $this->model_extension_noveraile_module_feed->queue($path, $name);
            $json['success'] = sprintf($this->language->get('text_feed_queued'), (int)$json['total'], (int)$json['rows']);
        } catch (\Throwable $error) {
            $json = ['error' => $error->getMessage()];
        }

        $this->respond($json);
    }

    public function process(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        try {
            $this->assertPermission();
            $run_id = (int)($this->request->post['run_id'] ?? 0);
            $limit = (int)($this->request->post['limit'] ?? 5);

            $this->load->model('extension/noveraile/module/feed');
            $json = $this->model_extension_noveraile_module_feed->process($run_id, $limit);
        } catch (\Throwable $error) {
            $json = ['error' => $error->getMessage()];
        }

        $this->respond($json);
    }

    public function status(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        try {
            $this->assertPermission('access');
            $this->load->model('extension/noveraile/module/feed');
            $run_id = (int)($this->request->get['run_id'] ?? 0);
            $json = $run_id
                ? $this->model_extension_noveraile_module_feed->getRun($run_id)
                : $this->model_extension_noveraile_module_feed->getLatestRun();
        } catch (\Throwable $error) {
            $json = ['error' => $error->getMessage()];
        }

        $this->respond($json);
    }

    public function cancel(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        try {
            $this->assertPermission();
            $this->load->model('extension/noveraile/module/feed');
            $this->model_extension_noveraile_module_feed->cancel((int)($this->request->post['run_id'] ?? 0));
            $json['success'] = $this->language->get('text_feed_cancelled');
        } catch (\Throwable $error) {
            $json = ['error' => $error->getMessage()];
        }

        $this->respond($json);
    }

    /** Report which articles failed, so a partial run can be acted on. */
    public function failures(): void {
        $this->load->language('extension/noveraile/module/noveraile');

        try {
            $this->assertPermission('access');
            $run_id = (int)($this->request->get['run_id'] ?? 0);
            $rows = $this->db->query("SELECT `articul`, `message` FROM `" . DB_PREFIX . "noveraile_feed_item` WHERE `run_id` = '" . $run_id . "' AND `status` = '2' ORDER BY `item_id` LIMIT 500")->rows;
            $this->respond(['failures' => $rows]);
        } catch (\Throwable $error) {
            $this->respond(['error' => $error->getMessage()]);
        }
    }

    private function assertPermission(string $type = 'modify'): void {
        if (!$this->user->hasPermission($type, self::PERMISSION)) {
            throw new \RuntimeException($this->language->get('error_permission'));
        }
    }

    private function respond(array $json): void {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

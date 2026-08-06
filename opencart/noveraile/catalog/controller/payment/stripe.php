<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Payment;

class Stripe extends \Opencart\System\Engine\Controller {
    public function index(): string {
        $this->load->language('extension/noveraile/payment/stripe');

        return $this->load->view('extension/noveraile/payment/stripe', [
            'button_confirm' => $this->language->get('button_confirm'),
            'confirm' => $this->url->link('extension/noveraile/payment/stripe.confirm', 'language=' . $this->config->get('config_language'))
        ]);
    }

    public function confirm(): void {
        $this->load->language('extension/noveraile/payment/stripe');
        $json = [];
        $order_id = (int)($this->session->data['order_id'] ?? 0);

        if (!$order_id || ($this->session->data['payment_method']['code'] ?? '') !== 'stripe.stripe') {
            $json['error'] = $this->language->get('error_order');
        }

        $this->load->model('checkout/order');
        $order = $order_id ? $this->model_checkout_order->getOrder($order_id) : [];
        if (!$order) {
            $json['error'] = $this->language->get('error_order');
        }

        if (!$json) {
            $session = $this->createSession($order);
            if (!empty($session['url'])) {
                $pending = (int)(((array)$this->config->get('config_pending_status'))[0] ?? 0);
                if ($pending && (int)($order['order_status_id'] ?? 0) !== $pending) {
                    $this->model_checkout_order->addHistory($order_id, $pending, 'Stripe Checkout session ' . $session['id'], false);
                }
                $json['redirect'] = $session['url'];
            } else {
                $json['error'] = $session['error']['message'] ?? $this->language->get('error_api');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function success(): void {
        $this->response->redirect($this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true));
    }

    public function webhook(): void {
        $payload = file_get_contents('php://input') ?: '';
        $signature = $this->request->server['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = (string)$this->config->get('payment_stripe_webhook_secret');

        if (!$secret || !$this->validSignature($payload, $signature, $secret)) {
            http_response_code(400);
            return;
        }

        $event = json_decode($payload, true);
        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $order_id = (int)($session['metadata']['order_id'] ?? $session['client_reference_id'] ?? 0);

            if ($order_id && ($session['payment_status'] ?? '') === 'paid') {
                $this->load->model('checkout/order');
                $order = $this->model_checkout_order->getOrder($order_id);
                $expected_amount = $order ? $this->stripeAmount($order) : 0;
                $expected_currency = strtolower((string)($order['currency_code'] ?? ''));

                if ($order && (int)($session['amount_total'] ?? -1) === $expected_amount && strtolower((string)($session['currency'] ?? '')) === $expected_currency) {
                    $status = (int)$this->config->get('payment_stripe_order_status_id');
                    if (!$status) {
                        $status = (int)(((array)$this->config->get('config_processing_status'))[0] ?? 0);
                    }
                    if ($status && (int)($order['order_status_id'] ?? 0) !== $status) {
                        $reference = (string)($session['payment_intent'] ?? $session['id'] ?? '');
                        $this->model_checkout_order->addHistory($order_id, $status, 'Stripe payment confirmed: ' . $reference, true);
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['received' => true]));
    }

    private function createSession(array $order): array {
        $key = (string)$this->config->get('payment_stripe_secret_key');
        $success = $this->url->link('extension/noveraile/payment/stripe.success', 'language=' . $this->config->get('config_language') . '&session_id={CHECKOUT_SESSION_ID}', true);
        $cancel = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'), true);
        $brand = (string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name') ?: '6 Moments');
        $body = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id' => (string)$order['order_id'],
            'customer_email' => $order['email'],
            'success_url' => html_entity_decode($success, ENT_QUOTES, 'UTF-8'),
            'cancel_url' => html_entity_decode($cancel, ENT_QUOTES, 'UTF-8'),
            'metadata' => ['order_id' => (string)$order['order_id']],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower((string)$order['currency_code']),
                    'unit_amount' => $this->stripeAmount($order),
                    'product_data' => ['name' => $brand . ' order #' . $order['order_id']]
                ],
                'quantity' => 1
            ]]
        ];

        $curl = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/x-www-form-urlencoded',
                'Idempotency-Key: noveraile-order-' . $order['order_id']
            ],
            CURLOPT_POSTFIELDS => http_build_query($body)
        ]);
        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            return ['error' => ['message' => $error]];
        }
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $json = json_decode($response, true) ?: ['error' => ['message' => 'Invalid Stripe response']];

        if (($status < 200 || $status >= 300) && empty($json['error'])) {
            return ['error' => ['message' => 'Stripe rejected the Checkout session request']];
        }

        return $json;
    }

    private function stripeAmount(array $order): int {
        $currency_value = (float)($order['currency_value'] ?? 1);
        if ($currency_value <= 0) {
            $currency_value = 1;
        }

        return max(1, (int)round((float)$order['total'] * $currency_value * 100));
    }

    private function validSignature(string $payload, string $header, string $secret): bool {
        $timestamp = 0;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            if ($key === 't') $timestamp = (int)$value;
            if ($key === 'v1') $signatures[] = $value;
        }
        if (!$timestamp || abs(time() - $timestamp) > 300) return false;
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) return true;
        }
        return false;
    }
}

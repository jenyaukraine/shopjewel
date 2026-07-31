<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class Privacy extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $this->document->setTitle($this->language->get('six_privacy_title'));

        $data = $this->language->all();
        $data['page_title'] = $this->language->get('six_privacy_title');
        $data['page_copy'] = $this->copy();
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/noveraile/page/legal', $data));
    }

    private function copy(): string {
        $controller = $this->value('module_noveraile_legal_name', 'Legal company name');
        $address = $this->value('module_noveraile_legal_address', 'Registered office address');
        [$email_href, $email_label] = $this->email();
        $authority = $this->value('module_noveraile_data_authority', 'Name and website of the competent data protection authority');
        $retention = $this->value('module_noveraile_retention_periods', 'Applicable order, account, enquiry and technical-log retention periods');

        return <<<HTML
<p class="legal-intro">This privacy policy explains how NOVERAILE collects and uses personal data when you browse the store, contact the atelier, create an account, subscribe to the newsletter or place an order.</p>
<p class="legal-updated">Last updated: 31 July 2026</p>
<h2>1. Data controller</h2>
<dl class="legal-details">
  <div><dt>Controller</dt><dd>{$controller}</dd></div>
  <div><dt>Address</dt><dd>{$address}</dd></div>
  <div><dt>Privacy contact</dt><dd><a href="{$email_href}">{$email_label}</a></dd></div>
</dl>
<h2>2. Personal data we process</h2>
<ul>
  <li><strong>Account and contact data:</strong> name, email address, telephone number, password in encrypted form, saved addresses and account preferences.</li>
  <li><strong>Order and delivery data:</strong> products, size and personalisation choices, billing and shipping addresses, order history, returns and correspondence.</li>
  <li><strong>Payment data:</strong> payment status and transaction identifiers. Card details are entered directly on Stripe's secure checkout and are not stored by NOVERAILE.</li>
  <li><strong>Atelier, newsletter and gift-hint data:</strong> messages, consent records, email address and, when you use “Send a hint”, the sender's and recipient's details supplied by you.</li>
  <li><strong>Technical data:</strong> IP address, device and browser information, timestamps, security and server logs, session identifiers, language and currency preferences.</li>
</ul>
<h2>3. Purposes and legal bases</h2>
<div class="legal-table" role="table" aria-label="Purposes and legal bases">
  <div class="legal-table-row legal-table-head" role="row"><strong role="columnheader">Purpose</strong><strong role="columnheader">Legal basis under GDPR</strong></div>
  <div class="legal-table-row" role="row"><span role="cell">Operate the cart and account, process payment, fulfil, deliver and support an order</span><span role="cell">Performance of a contract or steps requested before a contract — Article 6(1)(b)</span></div>
  <div class="legal-table-row" role="row"><span role="cell">Invoices, tax records and responding to lawful requests</span><span role="cell">Legal obligation — Article 6(1)(c)</span></div>
  <div class="legal-table-row" role="row"><span role="cell">Store security, fraud prevention, service improvement, customer support and establishment or defence of legal claims</span><span role="cell">Legitimate interests — Article 6(1)(f)</span></div>
  <div class="legal-table-row" role="row"><span role="cell">Newsletter and any non-essential cookies or similar technologies</span><span role="cell">Consent — Article 6(1)(a), which you may withdraw at any time</span></div>
</div>
<p>If information required at checkout is not provided, we may be unable to conclude or fulfil your order. Newsletter subscription is optional.</p>
<h2>4. Recipients</h2>
<p>We disclose only the data necessary for the relevant service to trusted processors and recipients, which may include:</p>
<ul>
  <li>Stripe and the financial institutions involved in payment processing;</li>
  <li>selected delivery carriers, including DHL or DPD, and fulfilment partners;</li>
  <li>hosting, database, security, email and customer-support providers;</li>
  <li>professional advisers, courts, regulators or public authorities where legally required.</li>
</ul>
<p>We do not sell personal data.</p>
<h2>5. International transfers</h2>
<p>Some service providers may process data outside the European Economic Area. Where this occurs, we rely on a valid transfer mechanism, such as an adequacy decision or the European Commission's Standard Contractual Clauses, together with supplementary safeguards where required. You may contact us for information about the applicable safeguards.</p>
<h2>6. Retention</h2>
<p>We keep personal data only for as long as necessary for the purpose for which it was collected and to meet tax, accounting, consumer-protection and legal-claims obligations. Newsletter data is kept until you unsubscribe or withdraw consent, subject to a minimal suppression record needed to respect that choice.</p>
<p>{$retention}</p>
<h2>7. Cookies and local storage</h2>
<p>Strictly necessary technologies maintain the shopping session, cart, checkout, account security and your language or currency choice. Non-essential analytics or marketing technologies, if introduced, may be activated only after consent. You can delete cookies in your browser; blocking necessary cookies may prevent the cart or checkout from working.</p>
<h2>8. Your rights</h2>
<p>Subject to the conditions in the GDPR, you may request access, rectification, erasure, restriction, portability or object to processing. You may withdraw consent at any time without affecting earlier lawful processing. You may also object at any time to direct marketing.</p>
<p>Send requests to <a href="{$email_href}">{$email_label}</a>. We may need to verify your identity before acting on a request.</p>
<h2>9. Complaints</h2>
<p>You have the right to lodge a complaint with the data protection authority in the country where you live, work or where the alleged infringement occurred. Our lead authority is: {$authority}. A list of EEA authorities is available from the <a href="https://www.edpb.europa.eu/about-edpb/about-edpb/members_en" rel="external noopener">European Data Protection Board</a>.</p>
<h2>10. Automated decisions</h2>
<p>We do not use personal data for solely automated decisions that produce legal or similarly significant effects. Any product quiz or recommendation is optional and does not affect your ability to shop.</p>
<h2>11. Security and policy changes</h2>
<p>We use appropriate technical and organisational measures designed to protect personal data. We may update this policy when our services, providers or legal duties change; the date above shows the latest revision.</p>
<p class="legal-note"><strong>Merchant notice:</strong> Entries highlighted as “to be completed” must be configured before launch. The retention schedule and processor list must reflect actual operations and local law, and this policy should be reviewed for every sales market.</p>
HTML;
    }

    private function value(string $key, string $fallback, bool $fallback_is_placeholder = true): string {
        $value = trim((string)$this->config->get($key));

        if ($value === '') {
            if (!$fallback_is_placeholder && trim(strip_tags($fallback)) !== '') {
                return $fallback;
            }

            return '<span class="legal-placeholder">To be completed: ' . htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    private function email(): array {
        $email = trim((string)$this->config->get('module_noveraile_privacy_email'))
            ?: trim((string)$this->config->get('module_noveraile_email'))
            ?: trim((string)$this->config->get('config_email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['#', '<span class="legal-placeholder">To be completed: Privacy contact email</span>'];
        }

        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        return ['mailto:' . $email, $email];
    }
}

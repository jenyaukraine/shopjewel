<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;

class Imprint extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $this->document->setTitle($this->language->get('six_imprint_title'));

        $data = $this->language->all();
        $data['page_title'] = $this->language->get('six_imprint_title');
        $data['page_copy'] = $this->copy();
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/sixmoments/page/legal', $data));
    }

    private function copy(): string {
        $legal_name = $this->value('module_sixmoments_legal_name', 'Legal company name');
        $legal_form = $this->value('module_sixmoments_legal_form', 'Legal form');
        $representative = $this->value('module_sixmoments_legal_representative', 'Authorised representative');
        $address = $this->value('module_sixmoments_legal_address', 'Registered office address');
        $email = $this->value('module_sixmoments_email', (string)$this->config->get('config_email'));
        $phone = $this->value('module_sixmoments_phone', 'Telephone number');
        $register = $this->value('module_sixmoments_legal_register', 'Trade register and registration number');
        $vat = $this->value('module_sixmoments_vat_id', 'VAT identification number, if applicable');
        $supervisory = $this->value('module_sixmoments_supervisory_authority', 'Competent supervisory authority, if applicable');
        $content_responsible = $this->value('module_sixmoments_content_responsible', 'Person responsible for editorial content, if required');

        return <<<HTML
<p class="legal-intro">Information about the provider of this online store in accordance with Article 5 of Directive 2000/31/EC and the applicable national law of the country of establishment.</p>
<h2>Service provider</h2>
<dl class="legal-details">
  <div><dt>Trading name</dt><dd>6MOMENTS Jewelry</dd></div>
  <div><dt>Legal entity</dt><dd>{$legal_name}</dd></div>
  <div><dt>Legal form</dt><dd>{$legal_form}</dd></div>
  <div><dt>Represented by</dt><dd>{$representative}</dd></div>
  <div><dt>Registered office</dt><dd>{$address}</dd></div>
</dl>
<h2>Contact</h2>
<dl class="legal-details">
  <div><dt>Email</dt><dd><a href="mailto:{$email}">{$email}</a></dd></div>
  <div><dt>Telephone</dt><dd>{$phone}</dd></div>
</dl>
<h2>Registration and tax details</h2>
<dl class="legal-details">
  <div><dt>Register</dt><dd>{$register}</dd></div>
  <div><dt>VAT ID</dt><dd>{$vat}</dd></div>
  <div><dt>Supervisory authority</dt><dd>{$supervisory}</dd></div>
</dl>
<h2>Editorial responsibility</h2>
<p>{$content_responsible}</p>
<h2>Consumer dispute resolution</h2>
<p>The trader's participation in consumer alternative dispute resolution, including the competent dispute-resolution body where applicable, must be stated here in accordance with the law of the country of establishment. The former EU Online Dispute Resolution platform was discontinued on 20 July 2025.</p>
<p class="legal-note"><strong>Merchant notice:</strong> Entries highlighted as “to be completed” are mandatory launch information and must be replaced in the 6MOMENTS module settings before accepting orders. Requirements vary by country; have this notice reviewed for the markets in which you sell.</p>
HTML;
    }

    private function value(string $key, string $placeholder): string {
        $value = trim((string)$this->config->get($key));

        if ($value === '') {
            return '<span class="legal-placeholder">To be completed: ' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }
}

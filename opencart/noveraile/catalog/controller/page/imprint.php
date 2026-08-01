<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class Imprint extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $this->document->setTitle($this->language->get('six_imprint_title'));

        $data = $this->language->all();
        $data['six_brand_name'] = $this->brand();
        $data['page_title'] = $this->language->get('six_imprint_title');
        $data['page_copy'] = $this->copy();
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/noveraile/page/legal', $data));
    }

    private function copy(): string {
        $legal_name = $this->value('module_noveraile_legal_name', 'Legal company name');
        $legal_form = $this->value('module_noveraile_legal_form', 'Legal form');
        $representative = $this->value('module_noveraile_legal_representative', 'Authorised representative');
        $address = $this->value('module_noveraile_legal_address', 'Registered office address');
        [$email_href, $email_label] = $this->email();
        $phone = $this->value('module_noveraile_phone', 'Telephone number');
        $register = $this->value('module_noveraile_legal_register', 'Trade register and registration number');
        $vat = $this->value('module_noveraile_vat_id', 'VAT identification number, if applicable');
        $supervisory = $this->value('module_noveraile_supervisory_authority', 'Competent supervisory authority, if applicable');
        $content_responsible = $this->value('module_noveraile_content_responsible', 'Person responsible for editorial content, if required');
        $brand = htmlspecialchars($this->brand(), ENT_QUOTES, 'UTF-8');
        $language = (string)$this->config->get('config_language');
        if ($language !== 'en-gb') {
            return $this->localizedCopy($language, $brand, $legal_name, $legal_form, $representative, $address, $email_href, $email_label, $phone, $register, $vat, $supervisory, $content_responsible);
        }

        return <<<HTML
<p class="legal-intro">Information about the provider of this online store in accordance with Article 5 of Directive 2000/31/EC and the applicable national law of the country of establishment.</p>
<h2>Service provider</h2>
<dl class="legal-details">
  <div><dt>Trading name</dt><dd>{$brand} Jewelry</dd></div>
  <div><dt>Legal entity</dt><dd>{$legal_name}</dd></div>
  <div><dt>Legal form</dt><dd>{$legal_form}</dd></div>
  <div><dt>Represented by</dt><dd>{$representative}</dd></div>
  <div><dt>Registered office</dt><dd>{$address}</dd></div>
</dl>
<h2>Contact</h2>
<dl class="legal-details">
  <div><dt>Email</dt><dd><a href="{$email_href}">{$email_label}</a></dd></div>
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
<p class="legal-note"><strong>Merchant notice:</strong> Entries highlighted as “to be completed” are mandatory launch information and must be replaced in the store module settings before accepting orders. Requirements vary by country; have this notice reviewed for the markets in which you sell.</p>
HTML;
    }

    private function localizedCopy(string $language, string $brand, string $legal_name, string $legal_form, string $representative, string $address, string $email_href, string $email_label, string $phone, string $register, string $vat, string $supervisory, string $content_responsible): string {
        $copy = [
            'de-de'=>['intro'=>'Anbieterinformationen gemäß Artikel 5 der Richtlinie 2000/31/EG und dem Recht des Niederlassungsstaates.','provider'=>'Diensteanbieter','trade'=>'Handelsname','entity'=>'Rechtsträger','form'=>'Rechtsform','represented'=>'Vertreten durch','office'=>'Sitz','contact'=>'Kontakt','email'=>'E-Mail','phone'=>'Telefon','registration'=>'Register- und Steuerangaben','register'=>'Register','vat'=>'USt-IdNr.','authority'=>'Aufsichtsbehörde','editorial'=>'Inhaltlich verantwortlich','dispute'=>'Verbraucherstreitbeilegung','dispute_copy'=>'Die Teilnahme des Händlers an einer alternativen Streitbeilegung und die zuständige Stelle müssen entsprechend dem Recht des Niederlassungsstaates angegeben werden. Die frühere EU-ODR-Plattform wurde am 20. Juli 2025 eingestellt.','notice'=>'Händlerhinweis: Alle als auszufüllen markierten Angaben sind vor Bestellannahme zu ergänzen und für die Verkaufsmärkte rechtlich zu prüfen.'],
            'cs-cz'=>['intro'=>'Informace o provozovateli podle článku 5 směrnice 2000/31/ES a práva země usazení.','provider'=>'Poskytovatel služby','trade'=>'Obchodní název','entity'=>'Právnická osoba','form'=>'Právní forma','represented'=>'Zastoupení','office'=>'Sídlo','contact'=>'Kontakt','email'=>'E-mail','phone'=>'Telefon','registration'=>'Registrační a daňové údaje','register'=>'Rejstřík','vat'=>'DIČ','authority'=>'Dozorový orgán','editorial'=>'Odpovědnost za obsah','dispute'=>'Mimosoudní řešení sporů','dispute_copy'=>'Účast obchodníka na mimosoudním řešení spotřebitelských sporů a příslušný orgán musí být uvedeny podle práva země usazení. Bývalá platforma EU ODR byla ukončena 20. července 2025.','notice'=>'Upozornění pro obchodníka: všechny označené údaje doplňte před přijímáním objednávek a nechte je právně ověřit pro země prodeje.'],
            'ru-ru'=>['intro'=>'Информация о поставщике услуг в соответствии со статьёй 5 Директивы 2000/31/EC и законодательством страны регистрации.','provider'=>'Поставщик услуг','trade'=>'Торговое наименование','entity'=>'Юридическое лицо','form'=>'Организационная форма','represented'=>'Представитель','office'=>'Юридический адрес','contact'=>'Контакты','email'=>'Email','phone'=>'Телефон','registration'=>'Регистрационные и налоговые данные','register'=>'Реестр','vat'=>'Номер НДС','authority'=>'Надзорный орган','editorial'=>'Ответственный за содержание','dispute'=>'Разрешение потребительских споров','dispute_copy'=>'Участие продавца в альтернативном разрешении потребительских споров и компетентный орган указываются по законодательству страны регистрации. Прежняя платформа EU ODR закрыта 20 июля 2025 года.','notice'=>'Примечание продавцу: заполните все отмеченные поля до приёма заказов и проведите юридическую проверку для стран продаж.'],
            'uk-ua'=>['intro'=>'Інформація про постачальника послуг відповідно до статті 5 Директиви 2000/31/EC і законодавства країни реєстрації.','provider'=>'Постачальник послуг','trade'=>'Торговельне найменування','entity'=>'Юридична особа','form'=>'Організаційна форма','represented'=>'Представник','office'=>'Юридична адреса','contact'=>'Контакти','email'=>'Email','phone'=>'Телефон','registration'=>'Реєстраційні й податкові дані','register'=>'Реєстр','vat'=>'Номер ПДВ','authority'=>'Наглядовий орган','editorial'=>'Відповідальний за зміст','dispute'=>'Вирішення споживчих спорів','dispute_copy'=>'Участь продавця в альтернативному вирішенні споживчих спорів і компетентний орган зазначаються за законодавством країни реєстрації. Колишню платформу EU ODR закрито 20 липня 2025 року.','notice'=>'Примітка продавцю: заповніть усі позначені поля до приймання замовлень і проведіть юридичну перевірку для країн продажу.']
        ][$language] ?? [];
        return '<p class="legal-intro">'.$copy['intro'].'</p><h2>'.$copy['provider'].'</h2><dl class="legal-details"><div><dt>'.$copy['trade'].'</dt><dd>'.$brand.' Jewelry</dd></div><div><dt>'.$copy['entity'].'</dt><dd>'.$legal_name.'</dd></div><div><dt>'.$copy['form'].'</dt><dd>'.$legal_form.'</dd></div><div><dt>'.$copy['represented'].'</dt><dd>'.$representative.'</dd></div><div><dt>'.$copy['office'].'</dt><dd>'.$address.'</dd></div></dl><h2>'.$copy['contact'].'</h2><dl class="legal-details"><div><dt>'.$copy['email'].'</dt><dd><a href="'.$email_href.'">'.$email_label.'</a></dd></div><div><dt>'.$copy['phone'].'</dt><dd>'.$phone.'</dd></div></dl><h2>'.$copy['registration'].'</h2><dl class="legal-details"><div><dt>'.$copy['register'].'</dt><dd>'.$register.'</dd></div><div><dt>'.$copy['vat'].'</dt><dd>'.$vat.'</dd></div><div><dt>'.$copy['authority'].'</dt><dd>'.$supervisory.'</dd></div></dl><h2>'.$copy['editorial'].'</h2><p>'.$content_responsible.'</p><h2>'.$copy['dispute'].'</h2><p>'.$copy['dispute_copy'].'</p><p class="legal-note"><strong>'.$copy['notice'].'</strong></p>';
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
    }

    private function value(string $key, string $placeholder): string {
        $value = trim((string)$this->config->get($key));

        if ($value === '') {
            return '<span class="legal-placeholder">To be completed: ' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    private function email(): array {
        $email = trim((string)$this->config->get('module_noveraile_email')) ?: trim((string)$this->config->get('config_email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['#', '<span class="legal-placeholder">To be completed: Contact email address</span>'];
        }

        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        return ['mailto:' . $email, $email];
    }
}

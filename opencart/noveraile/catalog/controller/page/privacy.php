<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class Privacy extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $this->document->setTitle($this->language->get('six_privacy_title'));

        $data = $this->language->all();
        $data['six_brand_name'] = $this->brand();
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
        $brand = htmlspecialchars($this->brand(), ENT_QUOTES, 'UTF-8');
        $language = (string)$this->config->get('config_language');
        if ($language !== 'en-gb') {
            return $this->localizedCopy($language, $brand, $controller, $address, $email_href, $email_label, $authority, $retention);
        }

        return <<<HTML
<p class="legal-intro">This privacy policy explains how {$brand} collects and uses personal data when you browse the store, contact the atelier, create an account, subscribe to the newsletter or place an order.</p>
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
  <li><strong>Payment data:</strong> payment status and transaction identifiers. Card details are entered directly on Stripe's secure checkout and are not stored by {$brand}.</li>
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

    private function localizedCopy(string $language, string $brand, string $controller, string $address, string $email_href, string $email_label, string $authority, string $retention): string {
        $copy = [
            'de-de' => [
                'intro'=>"Diese Datenschutzerklärung erläutert, wie {$brand} personenbezogene Daten beim Besuch des Shops, bei Kontakt, Kontoerstellung, Newsletter-Anmeldung und Bestellung verarbeitet.", 'updated'=>'Zuletzt aktualisiert: 31. Juli 2026',
                'controller'=>'1. Verantwortlicher', 'controller_label'=>'Verantwortlicher', 'address'=>'Anschrift', 'contact'=>'Datenschutzkontakt',
                'data'=>'2. Verarbeitete Daten', 'data_copy'=>'Wir verarbeiten Kontakt- und Kontodaten, Bestell- und Lieferdaten, Zahlungsstatus und Transaktionskennungen, Nachrichten und Einwilligungen sowie technische Sitzungs-, Geräte- und Sicherheitsdaten. Kartendaten werden direkt bei Stripe eingegeben und nicht von uns gespeichert.',
                'purposes'=>'3. Zwecke und Rechtsgrundlagen', 'purposes_copy'=>'Wir verarbeiten Daten zur Vertragsanbahnung und -erfüllung, zur Erfüllung gesetzlicher Pflichten, für Sicherheit und Betrugsprävention auf Grundlage berechtigter Interessen sowie für Newsletter und nicht notwendige Technologien nur mit Einwilligung.',
                'recipients'=>'4. Empfänger und Übermittlungen', 'recipients_copy'=>'Erforderliche Daten können an Stripe, beteiligte Finanzinstitute, DHL, DPD sowie Hosting-, E-Mail-, Sicherheits- und Supportanbieter übermittelt werden. Wir verkaufen keine personenbezogenen Daten. Übermittlungen außerhalb des EWR erfolgen nur auf Grundlage eines gültigen Mechanismus und angemessener Garantien.',
                'retention'=>'5. Speicherdauer', 'rights'=>'6. Ihre Rechte', 'rights_copy'=>'Nach Maßgabe der DSGVO können Sie Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit oder Widerspruch verlangen und Einwilligungen jederzeit widerrufen. Kontaktieren Sie uns unter',
                'complaints'=>'7. Beschwerden und Sicherheit', 'complaints_copy'=>'Sie können sich bei der zuständigen Datenschutzbehörde beschweren. Wir setzen angemessene technische und organisatorische Schutzmaßnahmen ein. Zuständige Stelle:',
                'notice'=>'Händlerhinweis: Platzhalter, Aufbewahrungsfristen und die tatsächliche Liste der Auftragsverarbeiter müssen vor dem Start ergänzt und rechtlich geprüft werden.'
            ],
            'cs-cz' => [
                'intro'=>"Tyto zásady vysvětlují, jak {$brand} zpracovává osobní údaje při návštěvě obchodu, kontaktování ateliéru, vytvoření účtu, odběru newsletteru a objednávce.", 'updated'=>'Poslední aktualizace: 31. července 2026',
                'controller'=>'1. Správce údajů', 'controller_label'=>'Správce', 'address'=>'Adresa', 'contact'=>'Kontakt pro soukromí',
                'data'=>'2. Zpracovávané údaje', 'data_copy'=>'Zpracováváme kontaktní a účtové údaje, údaje o objednávce a doručení, stav platby a identifikátory transakcí, zprávy a souhlasy i technické údaje o relaci, zařízení a zabezpečení. Údaje karty zadáváte přímo u Stripe a my je neukládáme.',
                'purposes'=>'3. Účely a právní základy', 'purposes_copy'=>'Údaje používáme k uzavření a plnění smlouvy, plnění zákonných povinností, zabezpečení a prevenci podvodů na základě oprávněného zájmu. Newsletter a nepovinné technologie používáme pouze se souhlasem.',
                'recipients'=>'4. Příjemci a předávání', 'recipients_copy'=>'Nezbytné údaje mohou obdržet Stripe, zapojené banky, DHL, DPD a poskytovatelé hostingu, e-mailu, zabezpečení či podpory. Osobní údaje neprodáváme. Předání mimo EHP probíhá jen s platným právním mechanismem a odpovídajícími zárukami.',
                'retention'=>'5. Doba uchování', 'rights'=>'6. Vaše práva', 'rights_copy'=>'Za podmínek GDPR můžete žádat přístup, opravu, výmaz, omezení, přenositelnost nebo vznést námitku a souhlas kdykoli odvolat. Kontaktujte nás na',
                'complaints'=>'7. Stížnosti a zabezpečení', 'complaints_copy'=>'Můžete podat stížnost příslušnému dozorovému úřadu. Používáme přiměřená technická a organizační bezpečnostní opatření. Příslušný úřad:',
                'notice'=>'Upozornění pro obchodníka: před spuštěním doplňte zástupné údaje, dobu uchování a skutečný seznam zpracovatelů a nechte text právně zkontrolovat.'
            ],
            'ru-ru' => [
                'intro'=>"Эта политика объясняет, как {$brand} обрабатывает персональные данные при посещении магазина, обращении в ателье, создании аккаунта, подписке и оформлении заказа.", 'updated'=>'Последнее обновление: 31 июля 2026 г.',
                'controller'=>'1. Оператор данных', 'controller_label'=>'Оператор', 'address'=>'Адрес', 'contact'=>'Контакт по вопросам данных',
                'data'=>'2. Какие данные обрабатываются', 'data_copy'=>'Мы обрабатываем контактные данные и данные аккаунта, заказа и доставки, статус платежа и идентификаторы транзакций, сообщения и согласия, а также технические данные сессии, устройства и безопасности. Данные карты вводятся непосредственно в Stripe и нами не хранятся.',
                'purposes'=>'3. Цели и правовые основания', 'purposes_copy'=>'Данные используются для заключения и исполнения договора, выполнения юридических обязанностей, безопасности и предотвращения мошенничества на основании законного интереса. Рассылка и необязательные технологии используются только с согласия.',
                'recipients'=>'4. Получатели и передача', 'recipients_copy'=>'Необходимые данные могут получать Stripe, участвующие банки, DHL, DPD и поставщики хостинга, электронной почты, безопасности и поддержки. Мы не продаём персональные данные. Передача за пределы ЕЭЗ производится только на законном основании с надлежащими гарантиями.',
                'retention'=>'5. Срок хранения', 'rights'=>'6. Ваши права', 'rights_copy'=>'При соблюдении условий GDPR вы можете запросить доступ, исправление, удаление, ограничение, переносимость, возразить против обработки или отозвать согласие. Напишите нам:',
                'complaints'=>'7. Жалобы и безопасность', 'complaints_copy'=>'Вы вправе обратиться в компетентный орган по защите данных. Мы применяем надлежащие технические и организационные меры. Компетентный орган:',
                'notice'=>'Примечание продавцу: до запуска заполните все поля, реальные сроки хранения и список обработчиков, затем проведите юридическую проверку текста.'
            ],
            'uk-ua' => [
                'intro'=>"Ця політика пояснює, як {$brand} обробляє персональні дані під час відвідування магазину, звернення до ательє, створення акаунта, підписки та оформлення замовлення.", 'updated'=>'Останнє оновлення: 31 липня 2026 р.',
                'controller'=>'1. Контролер даних', 'controller_label'=>'Контролер', 'address'=>'Адреса', 'contact'=>'Контакт із питань даних',
                'data'=>'2. Які дані обробляються', 'data_copy'=>'Ми обробляємо контактні дані й дані акаунта, замовлення та доставки, статус платежу й ідентифікатори транзакцій, повідомлення і згоди, а також технічні дані сесії, пристрою та безпеки. Дані картки вводяться безпосередньо у Stripe і нами не зберігаються.',
                'purposes'=>'3. Цілі та правові підстави', 'purposes_copy'=>'Дані використовуються для укладення й виконання договору, виконання юридичних обов’язків, безпеки та запобігання шахрайству на підставі законного інтересу. Розсилка й необов’язкові технології використовуються лише за згодою.',
                'recipients'=>'4. Одержувачі та передавання', 'recipients_copy'=>'Необхідні дані можуть отримувати Stripe, залучені банки, DHL, DPD та постачальники хостингу, електронної пошти, безпеки й підтримки. Ми не продаємо персональні дані. Передавання за межі ЄЕЗ відбувається лише на законній підставі з належними гарантіями.',
                'retention'=>'5. Строк зберігання', 'rights'=>'6. Ваші права', 'rights_copy'=>'За умовами GDPR ви можете запитати доступ, виправлення, видалення, обмеження, перенесення, заперечити проти обробки або відкликати згоду. Напишіть нам:',
                'complaints'=>'7. Скарги та безпека', 'complaints_copy'=>'Ви маєте право звернутися до компетентного органу із захисту даних. Ми застосовуємо належні технічні й організаційні заходи. Компетентний орган:',
                'notice'=>'Примітка продавцю: до запуску заповніть усі поля, фактичні строки зберігання та перелік обробників, а потім проведіть юридичну перевірку тексту.'
            ]
        ][$language] ?? [];
        return '<p class="legal-intro">'.$copy['intro'].'</p><p class="legal-updated">'.$copy['updated'].'</p><h2>'.$copy['controller'].'</h2><dl class="legal-details"><div><dt>'.$copy['controller_label'].'</dt><dd>'.$controller.'</dd></div><div><dt>'.$copy['address'].'</dt><dd>'.$address.'</dd></div><div><dt>'.$copy['contact'].'</dt><dd><a href="'.$email_href.'">'.$email_label.'</a></dd></div></dl><h2>'.$copy['data'].'</h2><p>'.$copy['data_copy'].'</p><h2>'.$copy['purposes'].'</h2><p>'.$copy['purposes_copy'].'</p><h2>'.$copy['recipients'].'</h2><p>'.$copy['recipients_copy'].'</p><h2>'.$copy['retention'].'</h2><p>'.$retention.'</p><h2>'.$copy['rights'].'</h2><p>'.$copy['rights_copy'].' <a href="'.$email_href.'">'.$email_label.'</a>.</p><h2>'.$copy['complaints'].'</h2><p>'.$copy['complaints_copy'].' '.$authority.'</p><p class="legal-note"><strong>'.$copy['notice'].'</strong></p>';
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
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

<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class Terms extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $this->document->setTitle($this->language->get('six_terms_title'));
        $data = $this->language->all();
        $data['six_brand_name'] = $this->brand();
        $data['page_title'] = $this->language->get('six_terms_title');
        $data['page_copy'] = $this->copy();
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/noveraile/page/legal', $data));
    }

    private function copy(): string {
        $copies = [
            'en-gb' => '<h2>Orders and payment</h2><p>An order is accepted after payment confirmation. Product availability, final price, delivery rate and made-to-order lead time are shown before confirmation.</p><h2 id="returns">Returns and exchanges</h2><p>Contact the atelier before returning an item. Engraved, resized or otherwise personalised pieces may not be returnable except where required by law. Your statutory consumer rights remain unaffected.</p><h2 id="warranty">Warranty</h2><p>Manufacturing defects are assessed by our atelier. Normal wear, accidental damage and third-party repairs are excluded.</p>',
            'de-de' => '<h2>Bestellung und Zahlung</h2><p>Eine Bestellung wird nach Zahlungsbestätigung angenommen. Verfügbarkeit, Endpreis, Versandkosten und Lieferzeit werden vor der Bestätigung angezeigt.</p><h2 id="returns">Rückgabe und Umtausch</h2><p>Kontaktieren Sie vor einer Rücksendung das Atelier. Gravierte, geänderte oder personalisierte Stücke können vom Widerruf ausgeschlossen sein, soweit das Gesetz dies erlaubt. Gesetzliche Verbraucherrechte bleiben unberührt.</p><h2 id="warranty">Gewährleistung</h2><p>Fertigungsfehler werden von unserem Atelier geprüft. Normale Abnutzung, Unfallschäden und Reparaturen Dritter sind ausgeschlossen.</p>',
            'cs-cz' => '<h2>Objednávky a platba</h2><p>Objednávka je přijata po potvrzení platby. Dostupnost, konečná cena, doprava a termín výroby se zobrazí před potvrzením.</p><h2 id="returns">Vrácení a výměna</h2><p>Před vrácením kontaktujte ateliér. Gravírované, upravené nebo personalizované šperky nemusí být možné vrátit, s výjimkou případů daných zákonem. Zákonná práva spotřebitele zůstávají zachována.</p><h2 id="warranty">Záruka</h2><p>Výrobní vady posuzuje náš ateliér. Běžné opotřebení, náhodné poškození a opravy třetí stranou jsou vyloučeny.</p>',
            'ru-ru' => '<h2>Заказы и оплата</h2><p>Заказ принимается после подтверждения оплаты. Наличие, итоговая цена, стоимость доставки и срок изготовления показываются до подтверждения.</p><h2 id="returns">Возврат и обмен</h2><p>Перед возвратом свяжитесь с ателье. Изделия с гравировкой, изменённым размером или иной персонализацией могут не подлежать возврату, кроме предусмотренных законом случаев. Законные права потребителя сохраняются.</p><h2 id="warranty">Гарантия</h2><p>Производственные дефекты оценивает наше ателье. Обычный износ, случайные повреждения и сторонний ремонт не покрываются.</p>',
            'uk-ua' => '<h2>Замовлення й оплата</h2><p>Замовлення приймається після підтвердження оплати. Наявність, підсумкова ціна, вартість доставки та строк виготовлення показуються до підтвердження.</p><h2 id="returns">Повернення й обмін</h2><p>Перед поверненням зв’яжіться з ательє. Вироби з гравіюванням, зміненим розміром або іншою персоналізацією можуть не підлягати поверненню, крім передбачених законом випадків. Законні права споживача зберігаються.</p><h2 id="warranty">Гарантія</h2><p>Виробничі дефекти оцінює наше ательє. Звичайне зношення, випадкові пошкодження та сторонній ремонт не покриваються.</p>'
        ];
        return $copies[(string)$this->config->get('config_language')] ?? $copies['en-gb'];
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
    }
}

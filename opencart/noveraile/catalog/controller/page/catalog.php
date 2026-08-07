<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class Catalog extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $brand = $this->brand();
        $this->document->setTitle($this->language->get('six_catalog_title') . ' | ' . $brand);
        $data = $this->language->all();
        $data['six_brand_name'] = $brand;

        $selected_currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $filter = $this->resolveFilter($data);

        $page = max(1, (int)($this->request->get['page'] ?? 1));
        $limit = 12;
        $filter['start'] = ($page - 1) * $limit;
        $filter['limit'] = $limit;
        if (!$filter['sort']) $filter['sort'] = 'popular';

        $this->load->model('catalog/product');
        $data['products'] = [];
        foreach ($this->model_extension_noveraile_catalog->getProductIds($filter) as $product_id) {
            $product = $this->model_catalog_product->getProduct($product_id);
            if ($product) $data['products'][] = $this->productThumb($product);
        }
        $total = $this->model_extension_noveraile_catalog->getTotalProducts($filter);
        $data['total'] = $total;
        $data['filters'] = $filter;
        $data['language'] = $this->config->get('config_language');
        $data['currency_code'] = $selected_currency;
        $data['catalog_action'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language'));
        $data['clear_url'] = $data['catalog_action'];
        $data['ajax_filter_status'] = (bool)$this->config->get('module_noveraile_ajax_filter_status');
        $data['filter_panel'] = $this->load->view('extension/noveraile/page/filters', $data);

        $data['categories'] = [];
        foreach ($this->model_extension_noveraile_catalog->getCategories() as $category) {
            $data['categories'][] = [
                'category_id' => (int)$category['category_id'],
                'name' => $category['name'],
                'href' => $this->filterUrl(['category_id' => (int)$category['category_id'], 'page' => null])
            ];
        }
        $data['types'] = [
            ['value'=>'rings','name'=>$data['six_type_rings']], ['value'=>'earrings','name'=>$data['six_type_earrings']],
            ['value'=>'necklaces','name'=>$data['six_type_necklaces']], ['value'=>'bracelets','name'=>$data['six_type_bracelets']]
        ];
        foreach ($data['types'] as &$type) $type['href'] = $this->filterUrl(['type' => $type['value'], 'category_id' => null, 'page' => null]);
        unset($type);
        $data['moments'] = [];

        $data['sorts'] = [
            ['value'=>'popular','name'=>$data['six_sort_popular']], ['value'=>'price-asc','name'=>$data['six_sort_price_asc']],
            ['value'=>'price-desc','name'=>$data['six_sort_price_desc']], ['value'=>'newest','name'=>$data['six_sort_newest']],
            ['value'=>'carat-asc','name'=>$data['six_sort_carat_asc']], ['value'=>'carat-desc','name'=>$data['six_sort_carat_desc']],
            ['value'=>'weight-asc','name'=>$data['six_sort_weight_asc']], ['value'=>'weight-desc','name'=>$data['six_sort_weight_desc']],
            ['value'=>'name-asc','name'=>$data['six_sort_name_asc']]
        ];
        foreach ($data['sorts'] as &$sort) $sort['href'] = $this->filterUrl(['sort' => $sort['value'], 'page' => null]);

        $url = $this->queryString(['page']);
        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $total, 'page' => $page, 'limit' => $limit,
            'url' => $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language') . $url . '&page={page}')
        ]);
        $data['result_text'] = $total ? sprintf($data['six_catalog_results'], min($total, $filter['start'] + 1), min($total, $filter['start'] + $limit), $total) : $data['six_no_products'];
        if ($data['ajax_filter_status'] && !empty($this->request->get['ajax'])) {
            $this->response->addHeader('Content-Type: text/html; charset=utf-8');
            $this->response->setOutput($this->load->view('extension/noveraile/page/catalog_results', $data));
            return;
        }
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/noveraile/page/catalog', $data));
    }

    /**
     * Render the refinement rail on its own. OpenCart's own category, search
     * and special listings have no filtering of their own, so they borrow the
     * catalog rail and submit it back to this controller.
     */
    public function panel(int $category_id = 0, string $clear_url = ''): string {
        $this->load->language('extension/noveraile/module/noveraile');
        $data = $this->language->all();
        $filter = $this->resolveFilter($data);
        if ($category_id > 0) $filter['category_id'] = $category_id;
        // OpenCart's own search page names the term differently.
        if ($filter['q'] === '') $filter['q'] = trim((string)($this->request->get['search'] ?? ''));
        $data['filters'] = $filter;
        $data['language'] = $this->config->get('config_language');
        $data['currency_code'] = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $data['catalog_action'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $data['language']);
        $data['clear_url'] = $clear_url ?: $data['catalog_action'];
        // Results live in the host listing, so there is no container to swap.
        $data['ajax_filter_status'] = false;

        return $this->load->view('extension/noveraile/page/filters', $data);
    }

    /**
     * Read every supported refinement out of the query string and collect the
     * facet lists the rail needs to render. Values that are not offered by the
     * catalog are discarded rather than passed to the model.
     */
    private function resolveFilter(array &$data): array {
        $allowed = [
            'type' => ['rings','earrings','necklaces','bracelets','wedding'],
            'moment' => ['engagement','wedding','motherhood','career','self-purchase','milestone'],
            'fineness' => ['375','585','750'],
            'stone' => ['natural','lab-grown','no-stones'],
            'availability' => ['ready','preorder'],
            'delivery' => ['delivery-3','delivery-10'],
            'sort' => ['popular','price-asc','price-desc','newest','carat-asc','carat-desc','weight-asc','weight-desc','name-asc']
        ];
        $filter = ['q' => trim((string)($this->request->get['q'] ?? ''))];
        foreach ($allowed as $key => $values) {
            $value = (string)($this->request->get[$key] ?? '');
            $filter[$key] = in_array($value, $values, true) ? $value : '';
        }
        $filter['category_id'] = max(0, (int)($this->request->get['category_id'] ?? 0));
        $filter['sale'] = !empty($this->request->get['sale']);
        $selected_currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        foreach (['price_min', 'price_max'] as $key) {
            $display_value = isset($this->request->get[$key]) && is_numeric($this->request->get[$key]) ? max(0, (float)$this->request->get[$key]) : '';
            $filter[$key] = $display_value === '' ? '' : $this->currency->convert($display_value, $selected_currency, $this->config->get('config_currency'));
            $data[$key] = $display_value;
        }

        foreach (['carat_min', 'carat_max'] as $key) {
            $value = isset($this->request->get[$key]) && is_numeric($this->request->get[$key])
                ? min(20, max(0, (float)$this->request->get[$key]))
                : '';
            $filter[$key] = $value;
            $data[$key] = $value;
        }

        $this->load->model('extension/noveraile/catalog');
        $data['metal_options'] = $this->model_extension_noveraile_catalog->getMetalOptions();
        $metal = trim((string)($this->request->get['metal'] ?? ''));
        $filter['metal'] = in_array($metal, $data['metal_options'], true) ? $metal : '';
        $price_bounds = $this->model_extension_noveraile_catalog->getPriceBounds();
        $data['price_floor'] = max(0, (int)floor($this->currency->convert($price_bounds['min'], $this->config->get('config_currency'), $selected_currency)));
        $data['price_ceiling'] = max($data['price_floor'] + 1, (int)ceil($this->currency->convert($price_bounds['max'], $this->config->get('config_currency'), $selected_currency)));
        $data['price_slider_min'] = $data['price_min'] === '' ? $data['price_floor'] : min($data['price_ceiling'], max($data['price_floor'], (float)$data['price_min']));
        $data['price_slider_max'] = $data['price_max'] === '' ? $data['price_ceiling'] : min($data['price_ceiling'], max($data['price_floor'], (float)$data['price_max']));
        if ($data['price_slider_min'] > $data['price_slider_max']) {
            $data['price_slider_min'] = $data['price_slider_max'];
        }
        $facets = $this->model_extension_noveraile_catalog->getAttributeFacets();
        foreach (['gemstone' => 'gemstones', 'stone_quality' => 'stone_qualities', 'style' => 'styles'] as $key => $data_key) {
            $data[$data_key] = $facets[$key] ?? [];
            $values = array_column($data[$data_key], 'value');
            $value = trim((string)($this->request->get[$key] ?? ''));
            $filter[$key] = in_array($value, $values, true) ? $value : '';
        }
        $shape_facets = $this->model_extension_noveraile_catalog->getStoneShapeFacets();
        $data['stone_shapes'] = [];
        foreach (['round','princess','marquise','baguette','cushion','heart','oval','pear','emerald','radiant','asscher'] as $shape) {
            $localized = (string)($data['six_shape_' . $shape] ?? ucfirst($shape));
            if (($shape_facets[$shape] ?? 0) < 1) continue;
            $data['stone_shapes'][] = [
                'value' => $shape,
                'name' => $localized,
                'total' => $shape_facets[$shape] ?? 0
            ];
        }
        $shape = trim((string)($this->request->get['stone_shape'] ?? ''));
        $filter['stone_shape'] = in_array($shape, array_column($data['stone_shapes'], 'value'), true) ? $shape : '';
        $data['ring_sizes'] = $this->model_extension_noveraile_catalog->getRingSizes();
        $ring_size = trim((string)($this->request->get['ring_size'] ?? ''));
        $filter['ring_size'] = in_array($ring_size, array_column($data['ring_sizes'], 'value'), true) ? $ring_size : '';

        return $filter;
    }

    private function productThumb(array $result): string {
        $this->load->model('tool/image');
        $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $this->load->model('extension/noveraile/pricing');
        $market_price = $this->model_extension_noveraile_pricing->resolve($result, $currency);
        $data = array_merge($result, [
            'thumb' => $this->model_tool_image->resize($image, 700, 700),
            'description' => trim(strip_tags(html_entity_decode((string)$result['description'], ENT_QUOTES, 'UTF-8'))),
            'price' => $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['price'] : $this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']),
            'special' => $market_price['special'] > 0 ? $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['special'] : $this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']) : false,
            'tax' => false, 'minimum' => max(1, (int)$result['minimum']),
            'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$result['product_id']),
            'cart_add' => $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language')),
            'wishlist_add' => $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language')),
            'compare_add' => $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language')),
            'review_status' => false, 'rating' => 0
        ]);
        return $this->load->view('product/thumb', $data);
    }

    private function filterUrl(array $replace): string {
        $query = $this->request->get;
        unset($query['route'], $query['_route_'], $query['language'], $query['ajax']);
        foreach ($replace as $key => $value) {
            if ($value === null || $value === '') unset($query[$key]); else $query[$key] = $value;
        }
        $suffix = $query ? '&' . http_build_query($query) : '';
        return $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language') . $suffix);
    }

    private function queryString(array $exclude = []): string {
        $query = $this->request->get;
        foreach (array_merge(['route','_route_','language','ajax'], $exclude) as $key) unset($query[$key]);
        return $query ? '&' . http_build_query($query) : '';
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
    }
}

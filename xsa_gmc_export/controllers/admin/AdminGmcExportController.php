<?php
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class AdminGmcExportController extends ModuleAdminController
{
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $categories = Category::getSimpleCategories($this->context->language->id);
        $validCategories = array_filter($categories, fn($cat) => (int)$cat['id_category'] > 1);

    }

    public function displayAjaxExportBatch()
    {
        $categoryId = (int) Tools::getValue('category_id');
        $batchSize = max(1, (int) Tools::getValue('batch_size', 50));
        $offset = (int) Tools::getValue('offset', 0);

        $result = $this->exportBatch($categoryId, $batchSize, $offset);
        die(json_encode($result));
    }

    private function exportBatch(int $categoryId, int $batchSize, int $offset): array
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $products = Product::getProducts($idLang, $offset, $batchSize, 'id_product', 'ASC', $categoryId, true);
        $totalProducts = count($products);
        $processed = 0;
        $missingData = [];

        $xmlFile = _PS_MODULE_DIR_ . $this->module->name . '/exports/gmc_feed.xml';
        if (!file_exists($xmlFile)) {
            $xml = new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"><channel></channel></rss>'
            );
            $xml->asXML($xmlFile);
        }

        $xml = simplexml_load_file($xmlFile);
        $channel = $xml->channel;
        $currency = $this->context->currency->iso_code;
        $imageType = ImageType::getFormattedName('large');
        $ns = 'http://base.google.com/ns/1.0';

        foreach ($products as $product) {
            $productObj = new Product((int)$product['id_product'], false, $idLang, $idShop);
            if (!$productObj->active) continue;

            $missing = [];
            if (!$productObj->name) $missing[] = 'name';
            if (!$productObj->description_short) $missing[] = 'description';
            if (!Product::getPriceStatic($productObj->id)) $missing[] = 'price';
            if (empty(Product::getCover($productObj->id))) $missing[] = 'image';
            if ($missing) $missingData[$productObj->id] = $missing;

            $quantity = StockAvailable::getQuantityAvailableByProduct($productObj->id, 0, $idShop);
            $price = Product::getPriceStatic($productObj->id, true, null, 2);

            $item = $channel->addChild('item');
            $item->addChild('g:id', (string)$productObj->id, $ns);
            $this->addCdata($item->addChild('g:title', null, $ns), $productObj->name);
            $this->addCdata($item->addChild('g:description', null, $ns), strip_tags($productObj->description_short));
            $this->addCdata($item->addChild('g:link', null, $ns), $this->context->link->getProductLink($productObj));
            $item->addChild('g:price', $price . ' ' . $currency, $ns);
            $item->addChild('g:availability', $quantity > 0 ? 'in stock' : 'out of stock', $ns);

            $cover = Product::getCover($productObj->id);
            if ($cover && isset($cover['id_image'])) {
                $imageLink = $this->context->link->getImageLink($productObj->link_rewrite, $productObj->id . '-' . $cover['id_image'], $imageType);
                $this->addCdata($item->addChild('g:image_link', null, $ns), $imageLink);
            }

            $processed++;
        }

        $xml->asXML($xmlFile);

        $totalProducts = Product::getProducts($idLang, 0, 0, 'id_product', 'ASC', $categoryId, true);

        return [
            'processed' => $processed,
            'missing_data' => $missingData,
            'offset' => $offset + $batchSize,
            'done' => $offset + $batchSize >= count($totalProducts),
            'total_products'=> count($totalProducts),
            'feed_link'       => $this->module->getPathUri().'exports/gmc_feed.xml',
        ];
    }

    private function addCdata(SimpleXMLElement $node, string $value): void
    {
        $domNode = dom_import_simplexml($node);
        $owner = $domNode->ownerDocument;
        $domNode->appendChild($owner->createCDATASection($value));
    }
}

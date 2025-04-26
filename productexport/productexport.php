<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductExport extends Module
{
    public function __construct()
    {
        $this->name = 'productexport';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Votre Nom';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Export des Produits pour Importation');
        $this->description = $this->l('Module pour exporter les produits avec les détails pour importation dans un autre site PrestaShop.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitExport')) {
            $this->exportProducts();
        }

        return $this->renderForm();
    }

    private function renderForm()
    {
        $html = '<form method="post">';
        $html .= '<input type="submit" name="submitExport" value="'.$this->l('Exporter les produits').'" class="btn btn-primary">';
        $html .= '</form>';
        return $html;
    }

    private function exportProducts()
    {
        $products = $this->getProducts();
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename);

        $output = fopen('php://output', 'w');

        // Définition des colonnes selon le format d'importation de PrestaShop
        $headers = [
            'ID', 'Active (0/1)', 'Name *', 'Categories (x,y,z...)',
            'Price tax excluded or Price tax included', 'Tax rules ID',
            'Wholesale price', 'On sale (0/1)', 'Discount amount',
            'Discount percent', 'Discount from (yyyy-mm-dd)', 'Discount to (yyyy-mm-dd)',
            'Reference #', 'Supplier reference #', 'Supplier',
            'Manufacturer', 'EAN13', 'UPC', 'MPN', 'Ecotax',
            'Width', 'Height', 'Depth', 'Weight', 'Delivery time of in-stock products',
            'Delivery time of out-of-stock products with allowed orders', 'Quantity',
            'Minimal quantity', 'Low stock level', 'Send me an email when the quantity is under this level',
            'Visibility', 'Additional shipping cost', 'Unity', 'Unit price',
            'Short description', 'Description', 'Tags (x,y,z...)', 'Meta title',
            'Meta keywords', 'Meta description', 'URL rewritten', 'Text when in stock',
            'Text when backorder allowed', 'Available for order (0 = No, 1 = Yes)',
            'Product available date', 'Product creation date', 'Show price (0 = No, 1 = Yes)',
            'Image URLs (x,y,z...)', 'Delete existing images (0 = No, 1 = Yes)', 'Feature(Name:Value:Position)',
            'Available online only (0 = No, 1 = Yes)', 'Condition', 'Customizable (0 = No, 1 = Yes)',
            'Uploadable files (0 = No, 1 = Yes)', 'Text fields (0 = No, 1 = Yes)', 'Out of stock action',
            'Virtual product (0 = No, 1 = Yes)', 'File URL', 'Number of allowed downloads',
            'Expiration date', 'Number of days', 'ID / Name of shop'
        ];

        // Écrire l'en-tête du CSV
        fputcsv($output, $headers, ';');

        // Écrire les données des produits
        foreach ($products as $product) {
            fputcsv($output, $product, ';');
        }

        fclose($output);
        exit;
    }

    private function getProducts()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $products = [];

        $sql = 'SELECT p.id_product, p.active, pl.name, p.price, p.id_tax_rules_group, p.wholesale_price,
                p.on_sale, p.reference, p.supplier_reference, s.name as supplier_name,
                m.name as manufacturer_name, p.ean13, p.upc, p.mpn, p.ecotax, p.width, p.height, p.depth,
                p.weight, p.quantity, p.minimal_quantity, p.additional_shipping_cost, pl.description_short,
                pl.description, pl.meta_title, pl.meta_keywords, pl.meta_description, pl.link_rewrite,
                p.available_for_order, p.available_date, p.date_add, p.show_price, p.condition, p.customizable,
                p.uploadable_files, p.text_fields, p.out_of_stock
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = '.$id_lang.')
                LEFT JOIN ' . _DB_PREFIX_ . 'supplier s ON (p.id_supplier = s.id_supplier)
                LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                WHERE p.active = 1';

        $results = Db::getInstance()->executeS($sql);

        foreach ($results as $result) {
            // Obtenir les catégories
            $categories = $this->getProductCategoriesIds($result['id_product']);

            // Obtenir les images
            $image_links = $this->getProductImages($result['id_product']);

            // Obtenir les tags
            $tags = $this->getProductTags($result['id_product'], $id_lang);

            // Construire la ligne du produit selon le format d'importation
            $product = [
                $result['id_product'], // ID
                $result['active'], // Active (0/1)
                $result['name'], // Name *
                implode(',', $categories), // Categories (x,y,z...)
                $result['price'], // Price tax excluded or Price tax included
                $result['id_tax_rules_group'], // Tax rules ID
                $result['wholesale_price'], // Wholesale price
                $result['on_sale'], // On sale (0/1)
                '', // Discount amount
                '', // Discount percent
                '', // Discount from (yyyy-mm-dd)
                '', // Discount to (yyyy-mm-dd)
                $result['reference'], // Reference #
                $result['supplier_reference'], // Supplier reference #
                $result['supplier_name'], // Supplier
                $result['manufacturer_name'], // Manufacturer
                $result['ean13'], // EAN13
                $result['upc'], // UPC
                $result['mpn'], // MPN
                $result['ecotax'], // Ecotax
                $result['width'], // Width
                $result['height'], // Height
                $result['depth'], // Depth
                $result['weight'], // Weight
                '', // Delivery time of in-stock products
                '', // Delivery time of out-of-stock products with allowed orders
                $result['quantity'], // Quantity
                $result['minimal_quantity'], // Minimal quantity
                '', // Low stock level
                '', // Send me an email when the quantity is under this level
                'both', // Visibility
                $result['additional_shipping_cost'], // Additional shipping cost
                '', // Unity
                '', // Unit price
                $result['description_short'], // Short description
                $result['description'], // Description
                implode(',', $tags), // Tags (x,y,z...)
                $result['meta_title'], // Meta title
                $result['meta_keywords'], // Meta keywords
                $result['meta_description'], // Meta description
                $result['link_rewrite'], // URL rewritten
                '', // Text when in stock
                '', // Text when backorder allowed
                $result['available_for_order'], // Available for order (0 = No, 1 = Yes)
                $result['available_date'], // Product available date
                $result['date_add'], // Product creation date
                $result['show_price'], // Show price (0 = No, 1 = Yes)
                implode(',', $image_links), // Image URLs (x,y,z...)
                '0', // Delete existing images (0 = No, 1 = Yes)
                '', // Feature(Name:Value:Position)
                '', // Available online only (0 = No, 1 = Yes)
                $result['condition'], // Condition
                $result['customizable'], // Customizable (0 = No, 1 = Yes)
                $result['uploadable_files'], // Uploadable files (0 = No, 1 = Yes)
                $result['text_fields'], // Text fields (0 = No, 1 = Yes)
                $result['out_of_stock'], // Out of stock action
                '0', // Virtual product (0 = No, 1 = Yes)
                '', // File URL
                '', // Number of allowed downloads
                '', // Expiration date
                '', // Number of days
                '', // ID / Name of shop
            ];

            $products[] = $product;
        }

        return $products;
    }

    private function getProductCategoriesIds($id_product)
    {
        $sql = 'SELECT cp.id_category
                FROM ' . _DB_PREFIX_ . 'category_product cp
                WHERE cp.id_product = ' . (int) $id_product;

        $categories = Db::getInstance()->executeS($sql);

        $category_ids = [];
        foreach ($categories as $category) {
            $category_ids[] = $category['id_category'];
        }

        return $category_ids;
    }

    private function getProductImages($id_product)
    {
        $images = Image::getImages((int) Context::getContext()->language->id, $id_product);

        $image_links = [];
        foreach ($images as $image) {
            $image_obj = new Image($image['id_image']);
            $image_url = $this->context->link->getImageLink(
                $image_obj->getExistingImgPath(),
                $image['id_image'],
                'large_default'
            );
            $image_links[] = $image_url;
        }

        return $image_links;
    }

    private function getProductTags($id_product, $id_lang)
    {
        $tags = Tag::getProductTags($id_product);
        if (isset($tags[$id_lang])) {
            return $tags[$id_lang];
        }
        return [];
    }
}

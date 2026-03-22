<?php
if (!defined('_PS_VERSION_')) exit;

class FenixTraceApi
{
    /**
     * Sync a PrestaShop product to FenixTrace.
     */
    public static function syncProduct(int $id_product): array
    {
        $product = new Product($id_product, true, (int) Configuration::get('PS_LANG_DEFAULT'));
        if (!Validate::isLoadedObject($product)) {
            return array('success' => false, 'error' => 'Product not found');
        }

        $payload = self::buildPayload($product);
        $filename = self::generateFilename($product);
        $kit_url = rtrim(Configuration::get('FENIXTRACE_KIT_URL') ?: 'http://localhost:3005', '/');
        $upload_dir = Configuration::get('FENIXTRACE_UPLOAD_DIR');

        // Write file if upload dir configured
        if ($upload_dir && is_dir($upload_dir) && is_writable($upload_dir)) {
            file_put_contents(
                rtrim($upload_dir, '/') . '/' . $filename,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        // Update state to queued
        self::updateSync($id_product, array('state' => 'queued', 'file_name' => $filename, 'last_error' => ''));

        // POST to Integration Kit
        $ch = curl_init($kit_url . '/process/' . rawurlencode($filename));
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            self::updateSync($id_product, array('state' => 'error', 'last_error' => 'Connection failed: ' . $curlError));
            return array('success' => false, 'error' => $curlError);
        }

        $data = json_decode($response, true);
        if ($httpCode >= 400 || !$data) {
            $error = isset($data['error']) ? $data['error'] : "HTTP $httpCode";
            self::updateSync($id_product, array('state' => 'error', 'last_error' => $error));
            return array('success' => false, 'error' => $error);
        }

        $result = isset($data['result']) && is_array($data['result']) ? $data['result'] : $data;
        $txHash = isset($result['txHash']) ? pSQL($result['txHash']) : '';
        $notarizationTxHash = isset($result['notarizationTxHash']) ? pSQL($result['notarizationTxHash']) : '';
        $ipfsHash = isset($result['ipfsHash']) ? pSQL($result['ipfsHash']) : '';

        self::updateSync($id_product, array(
            'state' => 'synced',
            'tx_hash' => $txHash,
            'notarization_tx_hash' => $notarizationTxHash,
            'ipfs_hash' => $ipfsHash,
            'last_sync_at' => date('Y-m-d H:i:s'),
            'last_error' => '',
        ));

        return array('success' => true, 'txHash' => $txHash, 'notarizationTxHash' => $notarizationTxHash);
    }

    /**
     * Build JSON payload from PrestaShop product.
     */
    public static function buildPayload(Product $product): array
    {
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $categories = Product::getProductCategoriesFull($product->id, $id_lang);
        $cat_names = array_column($categories, 'name');
        $manufacturer = new Manufacturer($product->id_manufacturer, $id_lang);

        return array(
            'name' => $product->name[$id_lang] ?? $product->name[1] ?? 'Product ' . $product->id,
            'company' => Configuration::get('PS_SHOP_NAME') ?: 'PrestaShop Store',
            'template' => Configuration::get('FENIXTRACE_TEMPLATE') ?: 'generic',
            'product' => array(
                'name' => $product->name[$id_lang] ?? '',
                'reference' => $product->reference ?: '',
                'ean13' => $product->ean13 ?: '',
                'price' => (float) $product->price,
                'category' => implode(', ', $cat_names),
                'description_short' => strip_tags($product->description_short[$id_lang] ?? ''),
                'manufacturer' => Validate::isLoadedObject($manufacturer) ? $manufacturer->name : '',
                'weight' => (float) $product->weight,
            ),
            'source' => 'prestashop_plugin',
            'createdAt' => gmdate('c'),
            'prestashop' => array(
                'id_product' => (int) $product->id,
                'product_url' => Context::getContext()->link->getProductLink($product),
                'shop_name' => Configuration::get('PS_SHOP_NAME') ?: '',
                'shop_url' => Tools::getShopDomainSsl(true),
            ),
        );
    }

    /**
     * Generate unique filename.
     */
    public static function generateFilename(Product $product): string
    {
        $slug = Tools::link_rewrite($product->reference ?: ($product->name[1] ?? 'product-' . $product->id));
        if (empty($slug)) $slug = 'product-' . $product->id;
        return $slug . '_' . $product->id . '_' . gmdate('YmdHis') . '.json';
    }

    /**
     * Insert or update sync record.
     */
    public static function updateSync(int $id_product, array $data): void
    {
        $existing = Db::getInstance()->getValue(
            'SELECT id_sync FROM `' . _DB_PREFIX_ . 'fenixtrace_sync` WHERE id_product = ' . $id_product
        );

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            Db::getInstance()->update('fenixtrace_sync', $data, 'id_product = ' . $id_product);
        } else {
            $data['id_product'] = $id_product;
            $data['created_at'] = date('Y-m-d H:i:s');
            Db::getInstance()->insert('fenixtrace_sync', $data);
        }
    }

    /**
     * Check Integration Kit status.
     */
    public static function checkStatus(): array
    {
        $kit_url = rtrim(Configuration::get('FENIXTRACE_KIT_URL') ?: 'http://localhost:3005', '/');
        $ch = curl_init($kit_url . '/status');
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10));
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return array('connected' => false, 'error' => $error);
        return array('connected' => true, 'data' => json_decode($response, true));
    }
}

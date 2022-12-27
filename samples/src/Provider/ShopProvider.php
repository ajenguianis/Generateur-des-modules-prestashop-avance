<?php
namespace EvoGroup\Module\Moduleclass\Provider;

use Context;
use Shop;

/**
 * Class responsible to provide current PrestaShop Shop data
 */
class ShopProvider
{
    /**
     * @return int
     *
     * @throws Exception
     */
    public function getIdentifier()
    {
        /** @var Shop|null $shop */
        $shop = Context::getContext()->shop;

        if ($shop instanceof Shop) {
            return (int) Context::getContext()->shop->id;
        }

        throw new \Exception('Unable to retrieve current shop identifier.');
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    public function getGroupIdentifier()
    {
        /** @var Shop|null $shop */
        $shop = Context::getContext()->shop;

        if ($shop instanceof Shop) {
            return (int) Context::getContext()->shop->id_shop_group;
        }

        throw new \Exception('Unable to retrieve current shop group identifier.');
    }

    /**
     * Get one Shop Url
     *
     * @param int $shopId
     *
     * @return string
     */
    public function getShopUrl($shopId)
    {
        return (new \Shop($shopId))->getBaseURL();
    }

    /**
     * Get all shops Urls
     *
     * @return array
     */
    public function getShopsUrl()
    {
        $shopList = \Shop::getShops();
        $protocol = $this->getShopsProtocolInformations();
        $urlList = [];

        foreach ($shopList as $shop) {
            $urlList[] = [
                'id_shop' => $shop['id_shop'],
                'url' => $protocol['protocol'] . $shop[$protocol['domain_type']] . $shop['uri'],
            ];
        }

        return $urlList;
    }

    /**
     * getShopsProtocol
     *
     * @return array
     */
    protected function getShopsProtocolInformations()
    {
        if (true === \Tools::usingSecureMode()) {
            return [
                'domain_type' => 'domain_ssl',
                'protocol' => 'https://',
            ];
        }

        return [
            'domain_type' => 'domain',
            'protocol' => 'http://',
        ];
    }
    /**
     * @return array
     */
    public function getIdShopList()
    {
        // Get shops for each attributes
        $shopIds = Shop::getContextListShopID();

        $id_shop_list = [];
        if (is_array($shopIds) && count($shopIds)) {
            foreach ($shopIds as $shop) {
                if (!empty($shop) && !is_numeric($shop)) {
                    $id_shop_list[] = Shop::getIdByName($shop);
                } elseif (!empty($shop)) {
                    $id_shop_list[] = $shop;
                }
            }
        }

        return $id_shop_list;
    }
}

<?php


namespace Evogroup\Module\Moduleclass\Configuration;

use Configuration;

/**
 * Class responsible to manage PrestaShop configuration
 */
class PrestaShopConfiguration
{
    /**
     * @var PrestaShopConfigurationOptionsResolver
     */
    private $optionsResolver;

    /**
     * @param PrestaShopConfigurationOptionsResolver $optionsResolver
     */
    public function __construct(PrestaShopConfigurationOptionsResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    /**
     * @param string $key
     * @param array $options Options
     *
     * @return bool
     */
    public function has($key, array $options = [])
    {
        $settings = $this->optionsResolver->resolve($options);

        return (bool) Configuration::hasKey(
            $key,
            $settings['id_lang'],
            $settings['id_shop_group'],
            $settings['id_shop']
        );
    }

    /**
     * @param string $key
     * @param array $options Options
     *
     * @return mixed
     */
    public function get($key, array $options = [])
    {
        $settings = $this->optionsResolver->resolve($options);

        $value = Configuration::get(
            $key,
            $settings['id_lang'],
            $settings['id_shop_group'],
            $settings['id_shop']
        );

        if (empty($value)) {
            return $settings['default'];
        }

        return $value;
    }

    /**
     * Set configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @param array $options Options
     *
     * @return $this
     *
     * @throws Exception
     */
    public function set($key, $value, array $options = [])
    {
        $settings = $this->optionsResolver->resolve($options);

        $success = (bool) Configuration::updateValue(
            $key,
            $value,
            $settings['html'],
            $settings['id_shop_group'],
            $settings['id_shop']
        );

        if (false === $success) {
            throw new \Exception(sprintf('Could not set key %s in PrestaShop configuration', $key));
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     *
     * @throws Exception
     */
    public function remove($key)
    {
        $success = (bool) Configuration::deleteByName($key);

        if (false === $success) {
            throw new \Exception(sprintf('Could not remove key %s from PrestaShop configuration', $key));
        }

        return $this;
    }
}

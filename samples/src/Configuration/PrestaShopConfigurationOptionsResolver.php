<?php

namespace EvoGroup\Module\Moduleclass\Configuration;


use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class responsible to define default value for PrestaShop configuration options
 */
class PrestaShopConfigurationOptionsResolver
{
    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @param int $shopId
     */
    public function __construct($shopId)
    {
        $this->optionsResolver = new OptionsResolver();
        $this->optionsResolver->setDefaults([
            'global' => false,
            'html' => false,
            'default' => false,
            'id_lang' => null,
        ]);
        $this->optionsResolver->setDefault('id_shop', function (Options $options) use ($shopId) {
            if (true === $options['global']) {
                return 0;
            }

            return $shopId;
        });
        $this->optionsResolver->setDefault('id_shop_group', function (Options $options) {
            if (true === $options['global']) {
                return 0;
            }

            return null;
        });
        $this->optionsResolver->setAllowedTypes('global', 'bool');
        $this->optionsResolver->setAllowedTypes('id_lang', ['null', 'int']);
        $this->optionsResolver->setAllowedTypes('id_shop', ['null', 'int']);
        $this->optionsResolver->setAllowedTypes('id_shop_group', ['null', 'int']);
        $this->optionsResolver->setAllowedTypes('html', 'bool');
    }

    public function resolve(array $options)
    {
        return $this->optionsResolver->resolve($options);
    }
}

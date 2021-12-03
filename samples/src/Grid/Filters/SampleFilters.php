<?php declare(strict_types=1);

namespace module_namespace\Grid\Filters;

use module_namespace\Grid\Definition\Factory\QuoteGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

class QuoteFilters extends Filters
{
    protected $filterId = QuoteGridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id_quote',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}

<?php
namespace EvoGroup\Module\Moduleclass\Provider;

use Context;
use ImageManager;
use PrestaShop\PrestaShop\Core\Image\Parser\ImageTagSourceParserInterface;
use Shop;

class ImageProvider
{
    /**
     * @var ImageTagSourceParserInterface
     */
    private $imageTagSourceParser;

    /**
     * @param ImageTagSourceParserInterface $imageTagSourceParser
     */
    public function __construct(ImageTagSourceParserInterface $imageTagSourceParser)
    {
        $this->imageTagSourceParser = $imageTagSourceParser;
    }
    /*add your getter methods*/

}
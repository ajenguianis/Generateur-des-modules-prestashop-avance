<?php
namespace EvoGroup\Module\Moduleclass\Handler;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Category\Command\AddCategoryCommand;
use PrestaShop\PrestaShop\Core\Domain\Category\Command\EditCategoryCommand;
use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\CategoryId;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;
use PrestaShop\PrestaShop\Core\Image\Uploader\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Creates/updates category from data submitted in category form
 */
final class CategoryFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var ImageUploaderInterface
     */
    private $categoryCoverUploader;

    /**
     * @var ImageUploaderInterface
     */
    private $categoryThumbnailUploader;
    /**
     * @var ImageUploaderInterface
     */
    private $bannerHomeUploader;
    /**
     * @var ImageUploaderInterface
     */
    private $categoryMenuThumbnailUploader;
    /*add your images uploader attributes*/

    /**
     * @param CommandBusInterface $commandBus
     * @param ImageUploaderInterface $categoryCoverUploader
     * @param ImageUploaderInterface $categoryThumbnailUploader
     * @param ImageUploaderInterface $categoryMenuThumbnailUploader
     */
    public function __construct(
        CommandBusInterface $commandBus,
        ImageUploaderInterface $categoryCoverUploader,
        ImageUploaderInterface $categoryThumbnailUploader,
        ImageUploaderInterface $categoryMenuThumbnailUploader,
        /*add your images argument uploader*/

    ) {
        $this->commandBus = $commandBus;
        $this->categoryCoverUploader = $categoryCoverUploader;
        $this->categoryThumbnailUploader = $categoryThumbnailUploader;
        $this->categoryMenuThumbnailUploader = $categoryMenuThumbnailUploader;
        /*add your images definition uploader*/
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $command = $this->createAddCategoryCommand($data);

        /** @var CategoryId $categoryId */
        $categoryId = $this->commandBus->handle($command);

        $this->uploadImages(
            $categoryId,
            $data['cover_image'],
            $data['thumbnail_image'],
            $data['menu_thumbnail_images'],
        /*add your arguments uploader*/
        );

        return $categoryId->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($categoryId, array $data)
    {
        $command = $this->createEditCategoryCommand($categoryId, $data);

        $this->commandBus->handle($command);

        $categoryId = new CategoryId((int) $categoryId);

        $this->uploadImages(
            $categoryId,
            $data['cover_image'],
            $data['thumbnail_image'],
            $data['menu_thumbnail_images'],
        /*add your arguments uploader*/
        );
    }

    /**
     * Creates add category command from form data
     *
     * @param array $data
     *
     * @return AddCategoryCommand
     */
    private function createAddCategoryCommand(array $data)
    {
        $command = new AddCategoryCommand(
            $data['name'],
            $data['link_rewrite'],
            (bool) $data['active'],
            (int) $data['id_parent']
        );

        $command->setLocalizedDescriptions($data['description']);
        $command->setLocalizedMetaTitles($data['meta_title']);
        $command->setLocalizedMetaDescriptions($data['meta_description']);
        $command->setLocalizedMetaKeywords($data['meta_keyword']);
        $command->setAssociatedGroupIds($data['group_association']);

        if (isset($data['shop_association'])) {
            $command->setAssociatedShopIds($data['shop_association']);
        }

        return $command;
    }

    /**
     * Creates edit category command from
     *
     * @param int $categoryId
     * @param array $data
     *
     * @return EditCategoryCommand
     */
    private function createEditCategoryCommand($categoryId, array $data)
    {
        $command = new EditCategoryCommand($categoryId);
        $command->setIsActive($data['active']);
        $command->setLocalizedLinkRewrites($data['link_rewrite']);
        $command->setLocalizedNames($data['name']);
        $command->setParentCategoryId($data['id_parent']);
        $command->setLocalizedDescriptions($data['description']);
        $command->setLocalizedMetaTitles($data['meta_title']);
        $command->setLocalizedMetaDescriptions($data['meta_description']);
        $command->setLocalizedMetaKeywords($data['meta_keyword']);
        $command->setAssociatedGroupIds($data['group_association']);

        if (isset($data['shop_association'])) {
            $command->setAssociatedShopIds($data['shop_association']);
        }

        return $command;
    }

    /**
     * @param CategoryId $categoryId
     * @param UploadedFile $coverImage
     * @param UploadedFile $thumbnailImage
     * @param UploadedFile[] $menuThumbnailImages
     */
    private function uploadImages(
        CategoryId $categoryId,
        UploadedFile $coverImage = null,
        UploadedFile $thumbnailImage = null,
        array $menuThumbnailImages = [],
        /*uploaded file arguments*/

    ) {

        if (null !== $coverImage) {
            $this->categoryCoverUploader->upload($categoryId->getValue(), $coverImage);
        }

        if (null !== $thumbnailImage) {
            $this->categoryThumbnailUploader->upload($categoryId->getValue(), $thumbnailImage);
        }

        if (!empty($menuThumbnailImages)) {
            foreach ($menuThumbnailImages as $menuThumbnail) {
                $this->categoryMenuThumbnailUploader->upload($categoryId->getValue(), $menuThumbnail);
            }
        }
        /*add your uploaders conditions*/

    }

}

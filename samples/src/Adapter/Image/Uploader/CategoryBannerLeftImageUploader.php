<?php


namespace EvoGroup\Module\Moduleclass\Adapter\Image\Uploader;

use Category;
use ImageManager;
use ImageType;
use PrestaShop\PrestaShop\Adapter\Image\Uploader\AbstractImageUploader;
use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\CategoryId;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageOptimizationException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageUploadException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\MemoryLimitException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\UploadedImageConstraintException;
use PrestaShop\PrestaShop\Core\Image\Uploader\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CategoryCoverImageUploader.
 *
 * @internal
 */
final class CategoryBannerLeftImageUploader extends AbstractImageUploader implements ImageUploaderInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws MemoryLimitException
     * @throws ImageOptimizationException
     * @throws ImageUploadException
     * @throws UploadedImageConstraintException
     */
    public function upload($id, UploadedFile $uploadedImage)
    {
        $this->checkImageIsAllowedForUpload($uploadedImage);
        $this->uploadImage($id, $uploadedImage);
        $this->getThumbnailImage($id);
    }



    /**
     * @param int $id
     * @param UploadedFile $image
     *
     * @throws ImageOptimizationException
     * @throws ImageUploadException
     * @throws MemoryLimitException
     */
    private function uploadImage($id, UploadedFile $image)
    {
        $temporaryImageName = tempnam(_PS_TMP_IMG_DIR_, 'PS');

        if (!$temporaryImageName) {
            throw new ImageUploadException('Failed to create temporary image file');
        }

        if (!move_uploaded_file($image->getPathname(), $temporaryImageName)) {
            throw new ImageUploadException('Failed to upload image');
        }

        if (!ImageManager::checkImageMemoryLimit($temporaryImageName)) {
            throw new MemoryLimitException('Cannot upload image due to memory restrictions');
        }

        $optimizationSucceeded = ImageManager::resize(
            $temporaryImageName,
            _PS_IMG_DIR_ . 'c' . DIRECTORY_SEPARATOR . $id . '-category_img_banner_left.jpg',
            null,
            null,
            'jpg'
        );

        if (!$optimizationSucceeded) {
            throw new ImageOptimizationException('Failed to optimize image after uploading');
        }

        unlink($temporaryImageName);
    }

    /**
     * @param $categoryId
     * @return bool|null
     */
    private function getThumbnailImage($categoryId)
    {
        $imageTypes = ImageType::getImagesTypes('categories');

        if (count($imageTypes) > 0) {
            $thumb = '';
            $imageTag = '';
            $formattedSmall = ImageType::getFormattedName('small');
            $imageType = new ImageType();
            foreach ($imageTypes as $k => $imageType) {
                if ('category_img_banner_left' == $imageType['name']) {
                    $thumb = _PS_CAT_IMG_DIR_ . $categoryId . '-' . $imageType['name'] . '.jpg';
                    if (is_file($thumb)) {
                        $imageTag = ImageManager::thumbnail(
                            $thumb,
                            'category_' . (int) $categoryId . '-category_img_banner_left.jpg',
                            (int) $imageType['width'],
                            'jpg',
                            true,
                            true
                        );
                    }
                }
            }

         return true;

        }

        return null;
    }
}

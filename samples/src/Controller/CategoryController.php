<?php

namespace EvoGroup\Module\Moduleclass\Controller;
use PrestaShop\PrestaShop\Core\Domain\Category\Command\DeleteCategoryCoverImageCommand;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CannotDeleteImageException;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CategoryException;
use PrestaShop\PrestaShop\Core\Domain\Category\Query\GetCategoryForEditing;
use PrestaShop\PrestaShop\Core\Domain\Category\QueryResult\EditableCategory;
use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\MenuThumbnailId;
use PrestaShop\PrestaShop\Core\Domain\ShowcaseCard\Query\GetShowcaseCardIsClosed;
use PrestaShop\PrestaShop\Core\Domain\ShowcaseCard\ValueObject\ShowcaseCard;
use PrestaShop\PrestaShop\Core\Foundation\Filesystem\FileSystem;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\CategoryGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters\CategoryFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Form\Admin\Sell\Category\DeleteCategoriesType;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CategoryController extends FrameworkBundleAdminController
{

    /**
     * Show & process category editing.
     *
     *
     *
     * @param int $categoryId
     * @param Request $request
     *
     * @return Response
     */
    public function editAction($categoryId, Request $request)
    {
        try {
            /** @var EditableCategory $editableCategory */
            $editableCategory = $this->getQueryBus()->handle(new GetCategoryForEditing((int)$categoryId));
            if ($editableCategory->isRootCategory()) {
                return $this->redirectToRoute('admin_categories_edit_root', ['categoryId' => $categoryId]);
            }
        } catch (CategoryException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));

            return $this->redirectToRoute('admin_categories_index');
        }

        try {
            $categoryFormBuilder = $this->get('prestashop.core.form.identifiable_object.builder.category_form_builder');
            $categoryFormHandler = $this->get('prestashop.core.form.identifiable_object.handler.category_form_handler');

            $categoryFormOptions = [
                'id_category' => (int)$categoryId,
                'subcategories' => $editableCategory->getSubCategories(),
            ];

            $categoryForm = $categoryFormBuilder->getFormFor((int)$categoryId, [], $categoryFormOptions);
            $categoryForm->handleRequest($request);

            $handlerResult = $categoryFormHandler->handleFor((int)$categoryId, $categoryForm);

            if ($handlerResult->isSubmitted() && $handlerResult->isValid()) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_categories_index', [
                    'categoryId' => $categoryForm->getData()['id_parent'],
                ]);
            }
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        $defaultGroups = $this->get('prestashop.adapter.group.provider.default_groups_provider')->getGroups();
        $imageProvider=$this->get('moduleclass.category.image.provider');
        return $this->render(
            '@PrestaShop/Admin/Sell/Catalog/Categories/edit.html.twig',
            [
                'allowMenuThumbnailsUpload' => $editableCategory->canContainMoreMenuThumbnails(),
                'maxMenuThumbnails' => count(MenuThumbnailId::ALLOWED_ID_VALUES),
                'contextLangId' => $this->getContextLangId(),
                'editCategoryForm' => $categoryForm->createView(),
                'editableCategory' => $editableCategory,
                 /*add your custom images*/
                'defaultGroups' => $defaultGroups,
                'categoryUrl' => $this->get('prestashop.adapter.shop.url.category_provider')
                    ->getUrl($categoryId, '{friendy-url}'),
            ]
        );
    }
    /*add your delete methods*/
}
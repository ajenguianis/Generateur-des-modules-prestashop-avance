<?php

namespace App\DataFixtures;

use App\Entity\Hook;
use App\Entity\TableMapping;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $ps_hook = [
            ['id_hook' => '1', 'name' => 'actionValidateOrder', 'title' => 'New orders', 'description' => '', 'position' => '1'],
            ['id_hook' => '2', 'name' => 'displayMaintenance', 'title' => 'Maintenance Page', 'description' => 'This hook displays new elements on the maintenance page', 'position' => '1'],
            ['id_hook' => '3', 'name' => 'displayProductPageDrawer', 'title' => 'Product Page Drawer', 'description' => 'This hook displays content in the right sidebar of the product page', 'position' => '1'],
            ['id_hook' => '4', 'name' => 'actionPaymentConfirmation', 'title' => 'Payment confirmation', 'description' => 'This hook displays new elements after the payment is validated', 'position' => '1'],
            ['id_hook' => '5', 'name' => 'displayPaymentReturn', 'title' => 'Payment return', 'description' => '', 'position' => '1'],
            ['id_hook' => '6', 'name' => 'actionUpdateQuantity', 'title' => 'Quantity update', 'description' => 'Quantity is updated only when a customer effectively places their order', 'position' => '1'],
            ['id_hook' => '7', 'name' => 'displayRightColumn', 'title' => 'Right column blocks', 'description' => 'This hook displays new elements in the right-hand column', 'position' => '1'],
            ['id_hook' => '8', 'name' => 'displayWrapperTop', 'title' => 'Main wrapper section (top)', 'description' => 'This hook displays new elements in the top of the main wrapper', 'position' => '1'],
            ['id_hook' => '9', 'name' => 'displayWrapperBottom', 'title' => 'Main wrapper section (bottom)', 'description' => 'This hook displays new elements in the bottom of the main wrapper', 'position' => '1'],
            ['id_hook' => '10', 'name' => 'displayContentWrapperTop', 'title' => 'Content wrapper section (top)', 'description' => 'This hook displays new elements in the top of the content wrapper', 'position' => '1'],
            ['id_hook' => '11', 'name' => 'displayContentWrapperBottom', 'title' => 'Content wrapper section (bottom)', 'description' => 'This hook displays new elements in the bottom of the content wrapper', 'position' => '1'],
            ['id_hook' => '12', 'name' => 'displayLeftColumn', 'title' => 'Left column blocks', 'description' => 'This hook displays new elements in the left-hand column', 'position' => '1'],
            ['id_hook' => '13', 'name' => 'displayHome', 'title' => 'Homepage content', 'description' => 'This hook displays new elements on the homepage', 'position' => '1'],
            ['id_hook' => '14', 'name' => 'Header', 'title' => 'Pages html head section', 'description' => 'This hook adds additional elements in the head section of your pages (head section of html)', 'position' => '1'],
            ['id_hook' => '15', 'name' => 'actionCartSave', 'title' => 'Cart creation and update', 'description' => 'This hook is displayed when a product is added to the cart or if the cart\'s content is modified', 'position' => '1'],
            ['id_hook' => '16', 'name' => 'actionAuthentication', 'title' => 'Successful customer authentication', 'description' => 'This hook is displayed after a customer successfully signs in', 'position' => '1'],
            ['id_hook' => '17', 'name' => 'actionProductAdd', 'title' => 'Product creation', 'description' => 'This hook is displayed after a product is created', 'position' => '1'],
            ['id_hook' => '18', 'name' => 'actionProductUpdate', 'title' => 'Product update', 'description' => 'This hook is displayed after a product has been updated', 'position' => '1'],
            ['id_hook' => '19', 'name' => 'displayAfterBodyOpeningTag', 'title' => 'Very top of pages', 'description' => 'Use this hook for advertisement or modals you want to load first', 'position' => '1'],
            ['id_hook' => '20', 'name' => 'displayBeforeBodyClosingTag', 'title' => 'Very bottom of pages', 'description' => 'Use this hook for your modals or any content you want to load at the very end', 'position' => '1'],
            ['id_hook' => '21', 'name' => 'displayTop', 'title' => 'Top of pages', 'description' => 'This hook displays additional elements at the top of your pages', 'position' => '1'],
            ['id_hook' => '22', 'name' => 'displayNavFullWidth', 'title' => 'Navigation', 'description' => 'This hook displays full width navigation menu at the top of your pages', 'position' => '1'],
            ['id_hook' => '23', 'name' => 'displayRightColumnProduct', 'title' => 'New elements on the product page (right column)', 'description' => 'This hook displays new elements in the right-hand column of the product page', 'position' => '1'],
            ['id_hook' => '24', 'name' => 'actionProductDelete', 'title' => 'Product deletion', 'description' => 'This hook is called when a product is deleted', 'position' => '1'],
            ['id_hook' => '25', 'name' => 'actionObjectProductInCartDeleteBefore', 'title' => 'Cart product removal', 'description' => 'This hook is called before a product is removed from a cart', 'position' => '1'],
            ['id_hook' => '26', 'name' => 'actionObjectProductInCartDeleteAfter', 'title' => 'Cart product removal', 'description' => 'This hook is called after a product is removed from a cart', 'position' => '1'],
            ['id_hook' => '27', 'name' => 'displayFooterProduct', 'title' => 'Product footer', 'description' => 'This hook adds new blocks under the product\'s description', 'position' => '1'],
            ['id_hook' => '28', 'name' => 'displayInvoice', 'title' => 'Invoice', 'description' => 'This hook displays new blocks on the invoice (order)', 'position' => '1'],
            ['id_hook' => '29', 'name' => 'actionOrderStatusUpdate', 'title' => 'Order status update - Event', 'description' => 'This hook launches modules when the status of an order changes', 'position' => '1'],
            ['id_hook' => '30', 'name' => 'displayAdminOrder', 'title' => 'Display new elements in the Back Office, tab AdminOrder', 'description' => 'This hook launches modules when the AdminOrder tab is displayed in the Back Office', 'position' => '1'],
            ['id_hook' => '31', 'name' => 'displayAdminOrderTabOrder', 'title' => 'Display new elements in Back Office, AdminOrder, panel Order', 'description' => 'This hook launches modules when the AdminOrder tab is displayed in the Back Office and extends / override Order panel tabs', 'position' => '1'],
            ['id_hook' => '32', 'name' => 'displayAdminOrderTabShip', 'title' => 'Display new elements in Back Office, AdminOrder, panel Shipping', 'description' => 'This hook launches modules when the AdminOrder tab is displayed in the Back Office and extends / override Shipping panel tabs', 'position' => '1'],
            ['id_hook' => '33', 'name' => 'displayAdminOrderContentOrder', 'title' => 'Display new elements in Back Office, AdminOrder, panel Order', 'description' => 'This hook launches modules when the AdminOrder tab is displayed in the Back Office and extends / override Order panel content', 'position' => '1'],
            ['id_hook' => '34', 'name' => 'displayAdminOrderContentShip', 'title' => 'Display new elements in Back Office, AdminOrder, panel Shipping', 'description' => 'This hook launches modules when the AdminOrder tab is displayed in the Back Office and extends / override Shipping panel content', 'position' => '1'],
            ['id_hook' => '35', 'name' => 'displayFooter', 'title' => 'Footer', 'description' => 'This hook displays new blocks in the footer', 'position' => '1'],
            ['id_hook' => '36', 'name' => 'displayPDFInvoice', 'title' => 'PDF Invoice', 'description' => 'This hook allows you to display additional information on PDF invoices', 'position' => '1'],
            ['id_hook' => '37', 'name' => 'displayInvoiceLegalFreeText', 'title' => 'PDF Invoice - Legal Free Text', 'description' => 'This hook allows you to modify the legal free text on PDF invoices', 'position' => '1'],
            ['id_hook' => '38', 'name' => 'displayAdminCustomers', 'title' => 'Display new elements in the Back Office, tab AdminCustomers', 'description' => 'This hook launches modules when the AdminCustomers tab is displayed in the Back Office', 'position' => '1'],
            ['id_hook' => '39', 'name' => 'displayAdminCustomersAddressesItemAction', 'title' => 'Display new elements in the Back Office, tab AdminCustomers, Add', 'description' => 'This hook launches modules when the Addresses list into the AdminCustomers tab is displayed in the Back Office', 'position' => '1'],
            ['id_hook' => '40', 'name' => 'displayOrderConfirmation', 'title' => 'Order confirmation page', 'description' => 'This hook is called within an order\'s confirmation page', 'position' => '1'],
            ['id_hook' => '41', 'name' => 'actionCustomerAccountAdd', 'title' => 'Successful customer account creation', 'description' => 'This hook is called when a new customer creates an account successfully', 'position' => '1'],
            ['id_hook' => '42', 'name' => 'actionCustomerAccountUpdate', 'title' => 'Successful customer account update', 'description' => 'This hook is called when a customer updates its account successfully', 'position' => '1'],
            ['id_hook' => '43', 'name' => 'displayCustomerAccount', 'title' => 'Customer account displayed in Front Office', 'description' => 'This hook displays new elements on the customer account page', 'position' => '1'],
            ['id_hook' => '44', 'name' => 'actionOrderSlipAdd', 'title' => 'Order slip creation', 'description' => 'This hook is called when a new credit slip is added regarding client order', 'position' => '1'],
            ['id_hook' => '45', 'name' => 'displayShoppingCartFooter', 'title' => 'Shopping cart footer', 'description' => 'This hook displays some specific information on the shopping cart\'s page', 'position' => '1'],
            ['id_hook' => '46', 'name' => 'displayCreateAccountEmailFormBottom', 'title' => 'Customer authentication form', 'description' => 'This hook displays some information on the bottom of the email form', 'position' => '1'],
            ['id_hook' => '47', 'name' => 'displayAuthenticateFormBottom', 'title' => 'Customer authentication form', 'description' => 'This hook displays some information on the bottom of the authentication form', 'position' => '1'],
            ['id_hook' => '48', 'name' => 'displayCustomerAccountForm', 'title' => 'Customer account creation form', 'description' => 'This hook displays some information on the form to create a customer account', 'position' => '1'],
            ['id_hook' => '49', 'name' => 'displayAdminStatsModules', 'title' => 'Stats - Modules', 'description' => '', 'position' => '1'],
            ['id_hook' => '50', 'name' => 'displayAdminStatsGraphEngine', 'title' => 'Graph engines', 'description' => '', 'position' => '1'],
            ['id_hook' => '51', 'name' => 'actionOrderReturn', 'title' => 'Returned product', 'description' => 'This hook is displayed when a customer returns a product ', 'position' => '1'],
            ['id_hook' => '52', 'name' => 'displayProductAdditionalInfo', 'title' => 'Product page additional info', 'description' => 'This hook adds additional information on the product page', 'position' => '1'],
            ['id_hook' => '53', 'name' => 'displayBackOfficeHome', 'title' => 'Administration panel homepage', 'description' => 'This hook is displayed on the admin panel\'s homepage', 'position' => '1'],
            ['id_hook' => '54', 'name' => 'displayAdminStatsGridEngine', 'title' => 'Grid engines', 'description' => '', 'position' => '1'],
            ['id_hook' => '55', 'name' => 'actionWatermark', 'title' => 'Watermark', 'description' => '', 'position' => '1'],
            ['id_hook' => '56', 'name' => 'actionProductCancel', 'title' => 'Product cancelled', 'description' => 'This hook is called when you cancel a product in an order', 'position' => '1'],
            ['id_hook' => '57', 'name' => 'displayLeftColumnProduct', 'title' => 'New elements on the product page (left column)', 'description' => 'This hook displays new elements in the left-hand column of the product page', 'position' => '1'],
            ['id_hook' => '58', 'name' => 'actionProductOutOfStock', 'title' => 'Out-of-stock product', 'description' => 'This hook displays new action buttons if a product is out of stock', 'position' => '1'],
            ['id_hook' => '59', 'name' => 'actionProductAttributeUpdate', 'title' => 'Product attribute update', 'description' => 'This hook is displayed when a product\'s attribute is updated', 'position' => '1'],
            ['id_hook' => '60', 'name' => 'displayCarrierList', 'title' => 'Extra carrier (module mode)', 'description' => '', 'position' => '1'],
            ['id_hook' => '61', 'name' => 'displayShoppingCart', 'title' => 'Shopping cart - Additional button', 'description' => 'This hook displays new action buttons within the shopping cart', 'position' => '1'],
            ['id_hook' => '62', 'name' => 'actionCarrierUpdate', 'title' => 'Carrier Update', 'description' => 'This hook is called when a carrier is updated', 'position' => '1'],
            ['id_hook' => '63', 'name' => 'actionOrderStatusPostUpdate', 'title' => 'Post update of order status', 'description' => '', 'position' => '1'],
            ['id_hook' => '64', 'name' => 'displayCustomerAccountFormTop', 'title' => 'Block above the form for create an account', 'description' => 'This hook is displayed above the customer\'s account creation form', 'position' => '1'],
            ['id_hook' => '65', 'name' => 'displayBackOfficeHeader', 'title' => 'Administration panel header', 'description' => 'This hook is displayed in the header of the admin panel', 'position' => '1'],
            ['id_hook' => '66', 'name' => 'displayBackOfficeTop', 'title' => 'Administration panel hover the tabs', 'description' => 'This hook is displayed on the roll hover of the tabs within the admin panel', 'position' => '1'],
            ['id_hook' => '67', 'name' => 'displayAdminEndContent', 'title' => 'Administration end of content', 'description' => 'This hook is displayed at the end of the main content, before the footer', 'position' => '1'],
            ['id_hook' => '68', 'name' => 'displayBackOfficeFooter', 'title' => 'Administration panel footer', 'description' => 'This hook is displayed within the admin panel\'s footer', 'position' => '1'],
            ['id_hook' => '69', 'name' => 'actionProductAttributeDelete', 'title' => 'Product attribute deletion', 'description' => 'This hook is displayed when a product\'s attribute is deleted', 'position' => '1'],
            ['id_hook' => '70', 'name' => 'actionCarrierProcess', 'title' => 'Carrier process', 'description' => '', 'position' => '1'],
            ['id_hook' => '71', 'name' => 'displayBeforeCarrier', 'title' => 'Before carriers list', 'description' => 'This hook is displayed before the carrier list in Front Office', 'position' => '1'],
            ['id_hook' => '72', 'name' => 'displayAfterCarrier', 'title' => 'After carriers list', 'description' => 'This hook is displayed after the carrier list in Front Office', 'position' => '1'],
            ['id_hook' => '73', 'name' => 'displayOrderDetail', 'title' => 'Order detail', 'description' => 'This hook is displayed within the order\'s details in Front Office', 'position' => '1'],
            ['id_hook' => '74', 'name' => 'actionPaymentCCAdd', 'title' => 'Payment CC added', 'description' => '', 'position' => '1'],
            ['id_hook' => '75', 'name' => 'actionCategoryAdd', 'title' => 'Category creation', 'description' => 'This hook is displayed when a category is created', 'position' => '1'],
            ['id_hook' => '76', 'name' => 'actionCategoryUpdate', 'title' => 'Category modification', 'description' => 'This hook is displayed when a category is modified', 'position' => '1'],
            ['id_hook' => '77', 'name' => 'actionCategoryDelete', 'title' => 'Category deletion', 'description' => 'This hook is displayed when a category is deleted', 'position' => '1'],
            ['id_hook' => '78', 'name' => 'displayPaymentTop', 'title' => 'Top of payment page', 'description' => 'This hook is displayed at the top of the payment page', 'position' => '1'],
            ['id_hook' => '79', 'name' => 'actionHtaccessCreate', 'title' => 'After htaccess creation', 'description' => 'This hook is displayed after the htaccess creation', 'position' => '1'],
            ['id_hook' => '80', 'name' => 'actionAdminMetaSave', 'title' => 'After saving the configuration in AdminMeta', 'description' => 'This hook is displayed after saving the configuration in AdminMeta', 'position' => '1'],
            ['id_hook' => '81', 'name' => 'displayAttributeGroupForm', 'title' => 'Add fields to the form \'attribute group\'', 'description' => 'This hook adds fields to the form \'attribute group\'', 'position' => '1'],
            ['id_hook' => '82', 'name' => 'actionAttributeGroupSave', 'title' => 'Saving an attribute group', 'description' => 'This hook is called while saving an attributes group', 'position' => '1'],
            ['id_hook' => '83', 'name' => 'actionAttributeGroupDelete', 'title' => 'Deleting attribute group', 'description' => 'This hook is called while deleting an attributes  group', 'position' => '1'],
            ['id_hook' => '84', 'name' => 'displayFeatureForm', 'title' => 'Add fields to the form \'feature\'', 'description' => 'This hook adds fields to the form \'feature\'', 'position' => '1'],
            ['id_hook' => '85', 'name' => 'actionFeatureSave', 'title' => 'Saving attributes\' features', 'description' => 'This hook is called while saving an attributes features', 'position' => '1'],
            ['id_hook' => '86', 'name' => 'actionFeatureDelete', 'title' => 'Deleting attributes\' features', 'description' => 'This hook is called while deleting an attributes features', 'position' => '1'],
            ['id_hook' => '87', 'name' => 'actionProductSave', 'title' => 'Saving products', 'description' => 'This hook is called while saving products', 'position' => '1'],
            ['id_hook' => '88', 'name' => 'displayAttributeGroupPostProcess', 'title' => 'On post-process in admin attribute group', 'description' => 'This hook is called on post-process in admin attribute group', 'position' => '1'],
            ['id_hook' => '89', 'name' => 'displayFeaturePostProcess', 'title' => 'On post-process in admin feature', 'description' => 'This hook is called on post-process in admin feature', 'position' => '1'],
            ['id_hook' => '90', 'name' => 'displayFeatureValueForm', 'title' => 'Add fields to the form \'feature value\'', 'description' => 'This hook adds fields to the form \'feature value\'', 'position' => '1'],
            ['id_hook' => '91', 'name' => 'displayFeatureValuePostProcess', 'title' => 'On post-process in admin feature value', 'description' => 'This hook is called on post-process in admin feature value', 'position' => '1'],
            ['id_hook' => '92', 'name' => 'actionFeatureValueDelete', 'title' => 'Deleting attributes\' features\' values', 'description' => 'This hook is called while deleting an attributes features value', 'position' => '1'],
            ['id_hook' => '93', 'name' => 'actionFeatureValueSave', 'title' => 'Saving an attributes features value', 'description' => 'This hook is called while saving an attributes features value', 'position' => '1'],
            ['id_hook' => '94', 'name' => 'displayAttributeForm', 'title' => 'Add fields to the form \'attribute value\'', 'description' => 'This hook adds fields to the form \'attribute value\'', 'position' => '1'],
            ['id_hook' => '95', 'name' => 'actionAttributePostProcess', 'title' => 'On post-process in admin feature value', 'description' => 'This hook is called on post-process in admin feature value', 'position' => '1'],
            ['id_hook' => '96', 'name' => 'actionAttributeDelete', 'title' => 'Deleting an attributes features value', 'description' => 'This hook is called while deleting an attributes features value', 'position' => '1'],
            ['id_hook' => '97', 'name' => 'actionAttributeSave', 'title' => 'Saving an attributes features value', 'description' => 'This hook is called while saving an attributes features value', 'position' => '1'],
            ['id_hook' => '98', 'name' => 'actionTaxManager', 'title' => 'Tax Manager Factory', 'description' => '', 'position' => '1'],
            ['id_hook' => '99', 'name' => 'displayMyAccountBlock', 'title' => 'My account block', 'description' => 'This hook displays extra information within the \'my account\' block"', 'position' => '1'],
            ['id_hook' => '100', 'name' => 'actionModuleInstallBefore', 'title' => 'actionModuleInstallBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '101', 'name' => 'actionModuleInstallAfter', 'title' => 'actionModuleInstallAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '102', 'name' => 'displayTopColumn', 'title' => 'Top column blocks', 'description' => 'This hook displays new elements in the top of columns', 'position' => '1'],
            ['id_hook' => '103', 'name' => 'displayBackOfficeCategory', 'title' => 'Display new elements in the Back Office, tab AdminCategories', 'description' => 'This hook launches modules when the AdminCategories tab is displayed in the Back Office', 'position' => '1'],
            ['id_hook' => '104', 'name' => 'displayProductListFunctionalButtons', 'title' => 'Display new elements in the Front Office, products list', 'description' => 'This hook launches modules when the products list is displayed in the Front Office', 'position' => '1'],
            ['id_hook' => '105', 'name' => 'displayNav', 'title' => 'Navigation', 'description' => '', 'position' => '1'],
            ['id_hook' => '106', 'name' => 'displayOverrideTemplate', 'title' => 'Change the default template of current controller', 'description' => '', 'position' => '1'],
            ['id_hook' => '107', 'name' => 'actionAdminLoginControllerSetMedia', 'title' => 'Set media on admin login page header', 'description' => 'This hook is called after adding media to admin login page header', 'position' => '1'],
            ['id_hook' => '108', 'name' => 'actionOrderEdited', 'title' => 'Order edited', 'description' => 'This hook is called when an order is edited', 'position' => '1'],
            ['id_hook' => '109', 'name' => 'actionEmailAddBeforeContent', 'title' => 'Add extra content before mail content', 'description' => 'This hook is called just before fetching mail template', 'position' => '1'],
            ['id_hook' => '110', 'name' => 'actionEmailAddAfterContent', 'title' => 'Add extra content after mail content', 'description' => 'This hook is called just after fetching mail template', 'position' => '1'],
            ['id_hook' => '111', 'name' => 'sendMailAlterTemplateVars', 'title' => 'Alter template vars on the fly', 'description' => 'This hook is called when Mail::send() is called', 'position' => '1'],
            ['id_hook' => '112', 'name' => 'displayCartExtraProductActions', 'title' => 'Extra buttons in shopping cart', 'description' => 'This hook adds extra buttons to the product lines, in the shopping cart', 'position' => '1'],
            ['id_hook' => '113', 'name' => 'displayPaymentByBinaries', 'title' => 'Payment form generated by binaries', 'description' => 'This hook displays form generated by binaries during the checkout', 'position' => '1'],
            ['id_hook' => '114', 'name' => 'additionalCustomerFormFields', 'title' => 'Add fields to the Customer form', 'description' => 'This hook returns an array of FormFields to add them to the customer registration form', 'position' => '1'],
            ['id_hook' => '115', 'name' => 'additionalCustomerAddressFields', 'title' => 'Add fields to the Customer address form', 'description' => 'This hook returns an array of FormFields to add them to the customer address registration form', 'position' => '1'],
            ['id_hook' => '116', 'name' => 'addWebserviceResources', 'title' => 'Add extra webservice resource', 'description' => 'This hook is called when webservice resources list in webservice controller', 'position' => '1'],
            ['id_hook' => '117', 'name' => 'displayCustomerLoginFormAfter', 'title' => 'Display elements after login form', 'description' => 'This hook displays new elements after the login form', 'position' => '1'],
            ['id_hook' => '118', 'name' => 'actionClearCache', 'title' => 'Clear smarty cache', 'description' => 'This hook is called when smarty\'s cache is cleared', 'position' => '1'],
            ['id_hook' => '119', 'name' => 'actionClearCompileCache', 'title' => 'Clear smarty compile cache', 'description' => 'This hook is called when smarty\'s compile cache is cleared', 'position' => '1'],
            ['id_hook' => '120', 'name' => 'actionClearSf2Cache', 'title' => 'Clear Sf2 cache', 'description' => 'This hook is called when the Symfony cache is cleared', 'position' => '1'],
            ['id_hook' => '121', 'name' => 'actionValidateCustomerAddressForm', 'title' => 'Customer address form validation', 'description' => 'This hook is called when a customer submit its address form', 'position' => '1'],
            ['id_hook' => '122', 'name' => 'displayCarrierExtraContent', 'title' => 'Display additional content for a carrier (e.g pickup points)', 'description' => 'This hook calls only the module related to the carrier, in order to add options when needed', 'position' => '1'],
            ['id_hook' => '123', 'name' => 'validateCustomerFormFields', 'title' => 'Customer registration form validation', 'description' => 'This hook is called to a module when it has sent additional fields with additionalCustomerFormFields', 'position' => '1'],
            ['id_hook' => '124', 'name' => 'displayProductExtraContent', 'title' => 'Display extra content on the product page', 'description' => 'This hook expects ProductExtraContent instances, which will be properly displayed by the template on the product page', 'position' => '1'],
            ['id_hook' => '125', 'name' => 'filterCmsContent', 'title' => 'Filter the content page', 'description' => 'This hook is called just before fetching content page', 'position' => '1'],
            ['id_hook' => '126', 'name' => 'filterCmsCategoryContent', 'title' => 'Filter the content page category', 'description' => 'This hook is called just before fetching content page category', 'position' => '1'],
            ['id_hook' => '127', 'name' => 'filterProductContent', 'title' => 'Filter the content page product', 'description' => 'This hook is called just before fetching content page product', 'position' => '1'],
            ['id_hook' => '128', 'name' => 'filterCategoryContent', 'title' => 'Filter the content page category', 'description' => 'This hook is called just before fetching content page category', 'position' => '1'],
            ['id_hook' => '129', 'name' => 'filterManufacturerContent', 'title' => 'Filter the content page manufacturer', 'description' => 'This hook is called just before fetching content page manufacturer', 'position' => '1'],
            ['id_hook' => '130', 'name' => 'filterSupplierContent', 'title' => 'Filter the content page supplier', 'description' => 'This hook is called just before fetching content page supplier', 'position' => '1'],
            ['id_hook' => '131', 'name' => 'filterHtmlContent', 'title' => 'Filter HTML field before rending a page', 'description' => 'This hook is called just before fetching a page on HTML field', 'position' => '1'],
            ['id_hook' => '132', 'name' => 'displayDashboardTop', 'title' => 'Dashboard Top', 'description' => 'Displays the content in the dashboard\'s top area', 'position' => '1'],
            ['id_hook' => '133', 'name' => 'actionUpdateLangAfter', 'title' => 'Update "lang" tables', 'description' => 'Update "lang" tables after adding or updating a language', 'position' => '1'],
            ['id_hook' => '134', 'name' => 'actionOutputHTMLBefore', 'title' => 'Before HTML output', 'description' => 'This hook is used to filter the whole HTML page before it is rendered (only front)', 'position' => '1'],
            ['id_hook' => '135', 'name' => 'displayAfterProductThumbs', 'title' => 'Display extra content below product thumbs', 'description' => 'This hook displays new elements below product images ex. additional media', 'position' => '1'],
            ['id_hook' => '136', 'name' => 'actionDispatcherBefore', 'title' => 'Before dispatch', 'description' => 'This hook is called at the beginning of the dispatch method of the Dispatcher', 'position' => '1'],
            ['id_hook' => '137', 'name' => 'actionDispatcherAfter', 'title' => 'After dispatch', 'description' => 'This hook is called at the end of the dispatch method of the Dispatcher', 'position' => '1'],
            ['id_hook' => '138', 'name' => 'filterProductSearch', 'title' => 'Filter search products result', 'description' => 'This hook is called in order to allow to modify search product result', 'position' => '1'],
            ['id_hook' => '139', 'name' => 'actionProductSearchAfter', 'title' => 'Event triggered after search product completed', 'description' => 'This hook is called after the product search. Parameters are already filter', 'position' => '1'],
            ['id_hook' => '140', 'name' => 'actionEmailSendBefore', 'title' => 'Before sending an email', 'description' => 'This hook is used to filter the content or the metadata of an email before sending it or even prevent its sending', 'position' => '1'],
            ['id_hook' => '141', 'name' => 'displayAdminProductsMainStepLeftColumnMiddle', 'title' => 'Display new elements in back office product page, left column of', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '142', 'name' => 'displayAdminProductsMainStepLeftColumnBottom', 'title' => 'Display new elements in back office product page, left column of', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '143', 'name' => 'displayAdminProductsMainStepRightColumnBottom', 'title' => 'Display new elements in back office product page, right column o', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '144', 'name' => 'displayAdminProductsQuantitiesStepBottom', 'title' => 'Display new elements in back office product page, Quantities/Com', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '145', 'name' => 'displayAdminProductsPriceStepBottom', 'title' => 'Display new elements in back office product page, Price tab', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '146', 'name' => 'displayAdminProductsOptionsStepTop', 'title' => 'Display new elements in back office product page, Options tab', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '147', 'name' => 'displayAdminProductsOptionsStepBottom', 'title' => 'Display new elements in back office product page, Options tab', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '148', 'name' => 'displayAdminProductsSeoStepBottom', 'title' => 'Display new elements in back office product page, SEO tab', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '149', 'name' => 'displayAdminProductsShippingStepBottom', 'title' => 'Display new elements in back office product page, Shipping tab', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '150', 'name' => 'displayAdminProductsCombinationBottom', 'title' => 'Display new elements in back office product page, Combination ta', 'description' => 'This hook launches modules when the back office product page is displayed', 'position' => '1'],
            ['id_hook' => '151', 'name' => 'displayDashboardToolbarTopMenu', 'title' => 'Display new elements in back office page with a dashboard, on to', 'description' => 'This hook launches modules when a page with a dashboard is displayed', 'position' => '1'],
            ['id_hook' => '152', 'name' => 'displayDashboardToolbarIcons', 'title' => 'Display new elements in back office page with dashboard, on icon', 'description' => 'This hook launches modules when the back office with dashboard is displayed', 'position' => '1'],
            ['id_hook' => '153', 'name' => 'actionBuildFrontEndObject', 'title' => 'Manage elements added to the "prestashop" javascript object', 'description' => 'This hook allows you to customize the "prestashop" javascript object that is included in all front office pages', 'position' => '1'],
            ['id_hook' => '154', 'name' => 'actionFrontControllerAfterInit', 'title' => 'Perform actions after front office controller initialization', 'description' => 'This hook is launched after the initialization of all front office controllers', 'position' => '1'],
            ['id_hook' => '155', 'name' => 'actionAdministrationPageForm', 'title' => 'Manage Administration Page form fields', 'description' => 'This hook adds, update or remove fields of the Administration Page form', 'position' => '1'],
            ['id_hook' => '156', 'name' => 'actionAdministrationPageFormSave', 'title' => 'Processing Administration page form', 'description' => 'This hook is called when the Administration Page form is processed', 'position' => '1'],
            ['id_hook' => '157', 'name' => 'actionPerformancePageForm', 'title' => 'Manage Performance Page form fields', 'description' => 'This hook adds, update or remove fields of the Performance Page form', 'position' => '1'],
            ['id_hook' => '158', 'name' => 'actionPerformancePageFormSave', 'title' => 'Processing Performance page form', 'description' => 'This hook is called when the Performance Page form is processed', 'position' => '1'],
            ['id_hook' => '159', 'name' => 'actionMaintenancePageForm', 'title' => 'Manage Maintenance Page form fields', 'description' => 'This hook adds, update or remove fields of the Maintenance Page form', 'position' => '1'],
            ['id_hook' => '160', 'name' => 'actionMaintenancePageFormSave', 'title' => 'Processing Maintenance page form', 'description' => 'This hook is called when the Maintenance Page form is processed', 'position' => '1'],
            ['id_hook' => '161', 'name' => 'actionWebserviceKeyGridPresenterModifier', 'title' => 'Modify Webservice grid view data', 'description' => 'This hook allows to alter presented Webservice grid data', 'position' => '1'],
            ['id_hook' => '162', 'name' => 'actionWebserviceKeyGridDefinitionModifier', 'title' => 'Modifying Webservice grid definition', 'description' => 'This hook allows to alter Webservice grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '163', 'name' => 'actionWebserviceKeyGridQueryBuilderModifier', 'title' => 'Modify Webservice grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for Webservice grid', 'position' => '1'],
            ['id_hook' => '164', 'name' => 'actionWebserviceKeyGridFilterFormModifier', 'title' => 'Modify filters form for Webservice grid', 'description' => 'This hook allows to alter filters form used in Webservice', 'position' => '1'],
            ['id_hook' => '165', 'name' => 'actionSqlRequestGridPresenterModifier', 'title' => 'Modify SQL Manager grid view data', 'description' => 'This hook allows to alter presented SQL Manager grid data', 'position' => '1'],
            ['id_hook' => '166', 'name' => 'actionSqlRequestGridDefinitionModifier', 'title' => 'Modifying SQL Manager grid definition', 'description' => 'This hook allows to alter SQL Manager grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '167', 'name' => 'actionSqlRequestGridQueryBuilderModifier', 'title' => 'Modify SQL Manager grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for SQL Manager grid', 'position' => '1'],
            ['id_hook' => '168', 'name' => 'actionSqlRequestGridFilterFormModifier', 'title' => 'Modify filters form for SQL Manager grid', 'description' => 'This hook allows to alter filters form used in SQL Manager', 'position' => '1'],
            ['id_hook' => '169', 'name' => 'actionMetaGridPresenterModifier', 'title' => 'Modify SEO and URLs grid view data', 'description' => 'This hook allows to alter presented SEO and URLs grid data', 'position' => '1'],
            ['id_hook' => '170', 'name' => 'actionMetaGridDefinitionModifier', 'title' => 'Modifying SEO and URLs grid definition', 'description' => 'This hook allows to alter SEO and URLs grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '171', 'name' => 'actionMetaGridQueryBuilderModifier', 'title' => 'Modify SEO and URLs grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for SEO and URLs grid', 'position' => '1'],
            ['id_hook' => '172', 'name' => 'actionMetaGridFilterFormModifier', 'title' => 'Modify filters form for SEO and URLs grid', 'description' => 'This hook allows to alter filters form used in SEO and URLs', 'position' => '1'],
            ['id_hook' => '173', 'name' => 'actionLogsGridPresenterModifier', 'title' => 'Modify Logs grid view data', 'description' => 'This hook allows to alter presented Logs grid data', 'position' => '1'],
            ['id_hook' => '174', 'name' => 'actionLogsGridDefinitionModifier', 'title' => 'Modifying Logs grid definition', 'description' => 'This hook allows to alter Logs grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '175', 'name' => 'actionLogsGridQueryBuilderModifier', 'title' => 'Modify Logs grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for Logs grid', 'position' => '1'],
            ['id_hook' => '176', 'name' => 'actionLogsGridFilterFormModifier', 'title' => 'Modify filters form for Logs grid', 'description' => 'This hook allows to alter filters form used in Logs', 'position' => '1'],
            ['id_hook' => '177', 'name' => 'actionEmailLogsGridPresenterModifier', 'title' => 'Modify E-mail grid view data', 'description' => 'This hook allows to alter presented E-mail grid data', 'position' => '1'],
            ['id_hook' => '178', 'name' => 'actionEmailLogsGridDefinitionModifier', 'title' => 'Modifying E-mail grid definition', 'description' => 'This hook allows to alter E-mail grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '179', 'name' => 'actionEmailLogsGridQueryBuilderModifier', 'title' => 'Modify E-mail grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for E-mail grid', 'position' => '1'],
            ['id_hook' => '180', 'name' => 'actionEmailLogsGridFilterFormModifier', 'title' => 'Modify filters form for E-mail grid', 'description' => 'This hook allows to alter filters form used in E-mail', 'position' => '1'],
            ['id_hook' => '181', 'name' => 'actionBackupGridPresenterModifier', 'title' => 'Modify DB Backup grid view data', 'description' => 'This hook allows to alter presented DB Backup grid data', 'position' => '1'],
            ['id_hook' => '182', 'name' => 'actionBackupGridDefinitionModifier', 'title' => 'Modifying DB Backup grid definition', 'description' => 'This hook allows to alter DB Backup grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '183', 'name' => 'actionBackupGridFilterFormModifier', 'title' => 'Modify filters form for DB Backup grid', 'description' => 'This hook allows to alter filters form used in DB Backup', 'position' => '1'],
            ['id_hook' => '184', 'name' => 'actionProductFlagsModifier', 'title' => 'Customize product labels displayed on the product list on FO', 'description' => 'This hook allows to add and remove product labels displayed on top of product images', 'position' => '1'],
            ['id_hook' => '185', 'name' => 'actionListMailThemes', 'title' => 'List the available email themes and layouts', 'description' => 'This hook allows to add/remove available email themes (ThemeInterface) and/or to add/remove their layouts (LayoutInterface)', 'position' => '1'],
            ['id_hook' => '186', 'name' => 'actionGetMailThemeFolder', 'title' => 'Define the folder of an email theme', 'description' => 'This hook allows to change the folder of an email theme (useful if you theme is in a module for example)', 'position' => '1'],
            ['id_hook' => '187', 'name' => 'actionBuildMailLayoutVariables', 'title' => 'Build the variables used in email layout rendering', 'description' => 'This hook allows to change the variables used when an email layout is rendered', 'position' => '1'],
            ['id_hook' => '188', 'name' => 'actionGetMailLayoutTransformations', 'title' => 'Define the transformation to apply on layout', 'description' => 'This hook allows to add/remove TransformationInterface used to generate an email layout', 'position' => '1'],
            ['id_hook' => '189', 'name' => 'displayProductActions', 'title' => 'Display additional action button on the product page', 'description' => 'This hook allow additional actions to be triggered, near the add to cart button.', 'position' => '1'],
            ['id_hook' => '190', 'name' => 'displayPersonalInformationTop', 'title' => 'Content in the checkout funnel, on top of the personal informati', 'description' => 'Display actions or additional content in the personal details tab of the checkout funnel.', 'position' => '1'],
            ['id_hook' => '191', 'name' => 'actionSqlRequestFormBuilderModifier', 'title' => 'Modify sql request identifiable object form', 'description' => 'This hook allows to modify sql request identifiable object forms content by modifying form
          builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '192', 'name' => 'actionCustomerFormBuilderModifier', 'title' => 'Modify customer identifiable object form', 'description' => 'This hook allows to modify customer identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '193', 'name' => 'actionLanguageFormBuilderModifier', 'title' => 'Modify language identifiable object form', 'description' => 'This hook allows to modify language identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '194', 'name' => 'actionCurrencyFormBuilderModifier', 'title' => 'Modify currency identifiable object form', 'description' => 'This hook allows to modify currency identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '195', 'name' => 'actionWebserviceKeyFormBuilderModifier', 'title' => 'Modify webservice key identifiable object form', 'description' => 'This hook allows to modify webservice key identifiable object forms content by modifying form
          builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '196', 'name' => 'actionMetaFormBuilderModifier', 'title' => 'Modify meta identifiable object form', 'description' => 'This hook allows to modify meta identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '197', 'name' => 'actionCategoryFormBuilderModifier', 'title' => 'Modify category identifiable object form', 'description' => 'This hook allows to modify category identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '198', 'name' => 'actionRootCategoryFormBuilderModifier', 'title' => 'Modify root category identifiable object form', 'description' => 'This hook allows to modify root category identifiable object forms content by modifying form
          builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '199', 'name' => 'actionContactFormBuilderModifier', 'title' => 'Modify contact identifiable object form', 'description' => 'This hook allows to modify contact identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '200', 'name' => 'actionCmsPageCategoryFormBuilderModifier', 'title' => 'Modify cms page category identifiable object form', 'description' => 'This hook allows to modify cms page category identifiable object forms content by modifying
          form builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '201', 'name' => 'actionTaxFormBuilderModifier', 'title' => 'Modify tax identifiable object form', 'description' => 'This hook allows to modify tax identifiable object forms content by modifying form builder data
          or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '202', 'name' => 'actionManufacturerFormBuilderModifier', 'title' => 'Modify manufacturer identifiable object form', 'description' => 'This hook allows to modify manufacturer identifiable object forms content by modifying form
          builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '203', 'name' => 'actionEmployeeFormBuilderModifier', 'title' => 'Modify employee identifiable object form', 'description' => 'This hook allows to modify employee identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '204', 'name' => 'actionProfileFormBuilderModifier', 'title' => 'Modify profile identifiable object form', 'description' => 'This hook allows to modify profile identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '205', 'name' => 'actionCmsPageFormBuilderModifier', 'title' => 'Modify cms page identifiable object form', 'description' => 'This hook allows to modify cms page identifiable object forms content by modifying form builder
          data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '206', 'name' => 'actionManufacturerAddressFormBuilderModifier', 'title' => 'Modify manufacturer address identifiable object form', 'description' => 'This hook allows to modify manufacturer address identifiable object forms content by modifying
          form builder data or FormBuilder itself
      ', 'position' => '1'],
            ['id_hook' => '207', 'name' => 'actionBeforeUpdateSqlRequestFormHandler', 'title' => 'Modify sql request identifiable object data before updating it', 'description' => 'This hook allows to modify sql request identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '208', 'name' => 'actionBeforeUpdateCustomerFormHandler', 'title' => 'Modify customer identifiable object data before updating it', 'description' => 'This hook allows to modify customer identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '209', 'name' => 'actionBeforeUpdateLanguageFormHandler', 'title' => 'Modify language identifiable object data before updating it', 'description' => 'This hook allows to modify language identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '210', 'name' => 'actionBeforeUpdateCurrencyFormHandler', 'title' => 'Modify currency identifiable object data before updating it', 'description' => 'This hook allows to modify currency identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '211', 'name' => 'actionBeforeUpdateWebserviceKeyFormHandler', 'title' => 'Modify webservice key identifiable object data before updating i', 'description' => 'This hook allows to modify webservice key identifiable object forms data before it was
          updated
      ', 'position' => '1'],
            ['id_hook' => '212', 'name' => 'actionBeforeUpdateMetaFormHandler', 'title' => 'Modify meta identifiable object data before updating it', 'description' => 'This hook allows to modify meta identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '213', 'name' => 'actionBeforeUpdateCategoryFormHandler', 'title' => 'Modify category identifiable object data before updating it', 'description' => 'This hook allows to modify category identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '214', 'name' => 'actionBeforeUpdateRootCategoryFormHandler', 'title' => 'Modify root category identifiable object data before updating it', 'description' => 'This hook allows to modify root category identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '215', 'name' => 'actionBeforeUpdateContactFormHandler', 'title' => 'Modify contact identifiable object data before updating it', 'description' => 'This hook allows to modify contact identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '216', 'name' => 'actionBeforeUpdateCmsPageCategoryFormHandler', 'title' => 'Modify cms page category identifiable object data before updatin', 'description' => 'This hook allows to modify cms page category identifiable object forms data before it was
          updated
      ', 'position' => '1'],
            ['id_hook' => '217', 'name' => 'actionBeforeUpdateTaxFormHandler', 'title' => 'Modify tax identifiable object data before updating it', 'description' => 'This hook allows to modify tax identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '218', 'name' => 'actionBeforeUpdateManufacturerFormHandler', 'title' => 'Modify manufacturer identifiable object data before updating it', 'description' => 'This hook allows to modify manufacturer identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '219', 'name' => 'actionBeforeUpdateEmployeeFormHandler', 'title' => 'Modify employee identifiable object data before updating it', 'description' => 'This hook allows to modify employee identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '220', 'name' => 'actionBeforeUpdateProfileFormHandler', 'title' => 'Modify profile identifiable object data before updating it', 'description' => 'This hook allows to modify profile identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '221', 'name' => 'actionBeforeUpdateCmsPageFormHandler', 'title' => 'Modify cms page identifiable object data before updating it', 'description' => 'This hook allows to modify cms page identifiable object forms data before it was updated
      ', 'position' => '1'],
            ['id_hook' => '222', 'name' => 'actionBeforeUpdateManufacturerAddressFormHandler', 'title' => 'Modify manufacturer address identifiable object data before upda', 'description' => 'This hook allows to modify manufacturer address identifiable object forms data before it was
          updated
      ', 'position' => '1'],
            ['id_hook' => '223', 'name' => 'actionAfterUpdateSqlRequestFormHandler', 'title' => 'Modify sql request identifiable object data after updating it', 'description' => 'This hook allows to modify sql request identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '224', 'name' => 'actionAfterUpdateCustomerFormHandler', 'title' => 'Modify customer identifiable object data after updating it', 'description' => 'This hook allows to modify customer identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '225', 'name' => 'actionAfterUpdateLanguageFormHandler', 'title' => 'Modify language identifiable object data after updating it', 'description' => 'This hook allows to modify language identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '226', 'name' => 'actionAfterUpdateCurrencyFormHandler', 'title' => 'Modify currency identifiable object data after updating it', 'description' => 'This hook allows to modify currency identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '227', 'name' => 'actionAfterUpdateWebserviceKeyFormHandler', 'title' => 'Modify webservice key identifiable object data after updating it', 'description' => 'This hook allows to modify webservice key identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '228', 'name' => 'actionAfterUpdateMetaFormHandler', 'title' => 'Modify meta identifiable object data after updating it', 'description' => 'This hook allows to modify meta identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '229', 'name' => 'actionAfterUpdateCategoryFormHandler', 'title' => 'Modify category identifiable object data after updating it', 'description' => 'This hook allows to modify category identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '230', 'name' => 'actionAfterUpdateRootCategoryFormHandler', 'title' => 'Modify root category identifiable object data after updating it', 'description' => 'This hook allows to modify root category identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '231', 'name' => 'actionAfterUpdateContactFormHandler', 'title' => 'Modify contact identifiable object data after updating it', 'description' => 'This hook allows to modify contact identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '232', 'name' => 'actionAfterUpdateCmsPageCategoryFormHandler', 'title' => 'Modify cms page category identifiable object data after updating', 'description' => 'This hook allows to modify cms page category identifiable object forms data after it was
          updated
      ', 'position' => '1'],
            ['id_hook' => '233', 'name' => 'actionAfterUpdateTaxFormHandler', 'title' => 'Modify tax identifiable object data after updating it', 'description' => 'This hook allows to modify tax identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '234', 'name' => 'actionAfterUpdateManufacturerFormHandler', 'title' => 'Modify manufacturer identifiable object data after updating it', 'description' => 'This hook allows to modify manufacturer identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '235', 'name' => 'actionAfterUpdateEmployeeFormHandler', 'title' => 'Modify employee identifiable object data after updating it', 'description' => 'This hook allows to modify employee identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '236', 'name' => 'actionAfterUpdateProfileFormHandler', 'title' => 'Modify profile identifiable object data after updating it', 'description' => 'This hook allows to modify profile identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '237', 'name' => 'actionAfterUpdateCmsPageFormHandler', 'title' => 'Modify cms page identifiable object data after updating it', 'description' => 'This hook allows to modify cms page identifiable object forms data after it was updated
      ', 'position' => '1'],
            ['id_hook' => '238', 'name' => 'actionAfterUpdateManufacturerAddressFormHandler', 'title' => 'Modify manufacturer address identifiable object data after updat', 'description' => 'This hook allows to modify manufacturer address identifiable object forms data after it was
          updated
      ', 'position' => '1'],
            ['id_hook' => '239', 'name' => 'actionBeforeCreateSqlRequestFormHandler', 'title' => 'Modify sql request identifiable object data before creating it', 'description' => 'This hook allows to modify sql request identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '240', 'name' => 'actionBeforeCreateCustomerFormHandler', 'title' => 'Modify customer identifiable object data before creating it', 'description' => 'This hook allows to modify customer identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '241', 'name' => 'actionBeforeCreateLanguageFormHandler', 'title' => 'Modify language identifiable object data before creating it', 'description' => 'This hook allows to modify language identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '242', 'name' => 'actionBeforeCreateCurrencyFormHandler', 'title' => 'Modify currency identifiable object data before creating it', 'description' => 'This hook allows to modify currency identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '243', 'name' => 'actionBeforeCreateWebserviceKeyFormHandler', 'title' => 'Modify webservice key identifiable object data before creating i', 'description' => 'This hook allows to modify webservice key identifiable object forms data before it was
          created
      ', 'position' => '1'],
            ['id_hook' => '244', 'name' => 'actionBeforeCreateMetaFormHandler', 'title' => 'Modify meta identifiable object data before creating it', 'description' => 'This hook allows to modify meta identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '245', 'name' => 'actionBeforeCreateCategoryFormHandler', 'title' => 'Modify category identifiable object data before creating it', 'description' => 'This hook allows to modify category identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '246', 'name' => 'actionBeforeCreateRootCategoryFormHandler', 'title' => 'Modify root category identifiable object data before creating it', 'description' => 'This hook allows to modify root category identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '247', 'name' => 'actionBeforeCreateContactFormHandler', 'title' => 'Modify contact identifiable object data before creating it', 'description' => 'This hook allows to modify contact identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '248', 'name' => 'actionBeforeCreateCmsPageCategoryFormHandler', 'title' => 'Modify cms page category identifiable object data before creatin', 'description' => 'This hook allows to modify cms page category identifiable object forms data before it was
          created
      ', 'position' => '1'],
            ['id_hook' => '249', 'name' => 'actionBeforeCreateTaxFormHandler', 'title' => 'Modify tax identifiable object data before creating it', 'description' => 'This hook allows to modify tax identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '250', 'name' => 'actionBeforeCreateManufacturerFormHandler', 'title' => 'Modify manufacturer identifiable object data before creating it', 'description' => 'This hook allows to modify manufacturer identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '251', 'name' => 'actionBeforeCreateEmployeeFormHandler', 'title' => 'Modify employee identifiable object data before creating it', 'description' => 'This hook allows to modify employee identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '252', 'name' => 'actionBeforeCreateProfileFormHandler', 'title' => 'Modify profile identifiable object data before creating it', 'description' => 'This hook allows to modify profile identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '253', 'name' => 'actionBeforeCreateCmsPageFormHandler', 'title' => 'Modify cms page identifiable object data before creating it', 'description' => 'This hook allows to modify cms page identifiable object forms data before it was created
      ', 'position' => '1'],
            ['id_hook' => '254', 'name' => 'actionBeforeCreateManufacturerAddressFormHandler', 'title' => 'Modify manufacturer address identifiable object data before crea', 'description' => 'This hook allows to modify manufacturer address identifiable object forms data before it was
          created
      ', 'position' => '1'],
            ['id_hook' => '255', 'name' => 'actionAfterCreateSqlRequestFormHandler', 'title' => 'Modify sql request identifiable object data after creating it', 'description' => 'This hook allows to modify sql request identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '256', 'name' => 'actionAfterCreateCustomerFormHandler', 'title' => 'Modify customer identifiable object data after creating it', 'description' => 'This hook allows to modify customer identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '257', 'name' => 'actionAfterCreateLanguageFormHandler', 'title' => 'Modify language identifiable object data after creating it', 'description' => 'This hook allows to modify language identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '258', 'name' => 'actionAfterCreateCurrencyFormHandler', 'title' => 'Modify currency identifiable object data after creating it', 'description' => 'This hook allows to modify currency identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '259', 'name' => 'actionAfterCreateWebserviceKeyFormHandler', 'title' => 'Modify webservice key identifiable object data after creating it', 'description' => 'This hook allows to modify webservice key identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '260', 'name' => 'actionAfterCreateMetaFormHandler', 'title' => 'Modify meta identifiable object data after creating it', 'description' => 'This hook allows to modify meta identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '261', 'name' => 'actionAfterCreateCategoryFormHandler', 'title' => 'Modify category identifiable object data after creating it', 'description' => 'This hook allows to modify category identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '262', 'name' => 'actionAfterCreateRootCategoryFormHandler', 'title' => 'Modify root category identifiable object data after creating it', 'description' => 'This hook allows to modify root category identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '263', 'name' => 'actionAfterCreateContactFormHandler', 'title' => 'Modify contact identifiable object data after creating it', 'description' => 'This hook allows to modify contact identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '264', 'name' => 'actionAfterCreateCmsPageCategoryFormHandler', 'title' => 'Modify cms page category identifiable object data after creating', 'description' => 'This hook allows to modify cms page category identifiable object forms data after it was
          created
      ', 'position' => '1'],
            ['id_hook' => '265', 'name' => 'actionAfterCreateTaxFormHandler', 'title' => 'Modify tax identifiable object data after creating it', 'description' => 'This hook allows to modify tax identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '266', 'name' => 'actionAfterCreateManufacturerFormHandler', 'title' => 'Modify manufacturer identifiable object data after creating it', 'description' => 'This hook allows to modify manufacturer identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '267', 'name' => 'actionAfterCreateEmployeeFormHandler', 'title' => 'Modify employee identifiable object data after creating it', 'description' => 'This hook allows to modify employee identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '268', 'name' => 'actionAfterCreateProfileFormHandler', 'title' => 'Modify profile identifiable object data after creating it', 'description' => 'This hook allows to modify profile identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '269', 'name' => 'actionAfterCreateCmsPageFormHandler', 'title' => 'Modify cms page identifiable object data after creating it', 'description' => 'This hook allows to modify cms page identifiable object forms data after it was created
      ', 'position' => '1'],
            ['id_hook' => '270', 'name' => 'actionAfterCreateManufacturerAddressFormHandler', 'title' => 'Modify manufacturer address identifiable object data after creat', 'description' => 'This hook allows to modify manufacturer address identifiable object forms data after it was
          created
      ', 'position' => '1'],
            ['id_hook' => '271', 'name' => 'actionShippingPreferencesPageForm', 'title' => 'Modify shipping preferences page options form content', 'description' => 'This hook allows to modify shipping preferences page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '272', 'name' => 'actionOrdersInvoicesByDateForm', 'title' => 'Modify orders invoices by date options form content', 'description' => 'This hook allows to modify orders invoices by date options form FormBuilder', 'position' => '1'],
            ['id_hook' => '273', 'name' => 'actionOrdersInvoicesByStatusForm', 'title' => 'Modify orders invoices by status options form content', 'description' => 'This hook allows to modify orders invoices by status options form FormBuilder', 'position' => '1'],
            ['id_hook' => '274', 'name' => 'actionOrdersInvoicesOptionsForm', 'title' => 'Modify orders invoices options options form content', 'description' => 'This hook allows to modify orders invoices options options form FormBuilder', 'position' => '1'],
            ['id_hook' => '275', 'name' => 'actionCustomerPreferencesPageForm', 'title' => 'Modify customer preferences page options form content', 'description' => 'This hook allows to modify customer preferences page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '276', 'name' => 'actionOrderPreferencesPageForm', 'title' => 'Modify order preferences page options form content', 'description' => 'This hook allows to modify order preferences page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '277', 'name' => 'actionProductPreferencesPageForm', 'title' => 'Modify product preferences page options form content', 'description' => 'This hook allows to modify product preferences page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '278', 'name' => 'actionGeneralPageForm', 'title' => 'Modify general page options form content', 'description' => 'This hook allows to modify general page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '279', 'name' => 'actionLogsPageForm', 'title' => 'Modify logs page options form content', 'description' => 'This hook allows to modify logs page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '280', 'name' => 'actionOrderDeliverySlipOptionsForm', 'title' => 'Modify order delivery slip options options form content', 'description' => 'This hook allows to modify order delivery slip options options form FormBuilder', 'position' => '1'],
            ['id_hook' => '281', 'name' => 'actionOrderDeliverySlipPdfForm', 'title' => 'Modify order delivery slip pdf options form content', 'description' => 'This hook allows to modify order delivery slip pdf options form FormBuilder', 'position' => '1'],
            ['id_hook' => '282', 'name' => 'actionGeolocationPageForm', 'title' => 'Modify geolocation page options form content', 'description' => 'This hook allows to modify geolocation page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '283', 'name' => 'actionLocalizationPageForm', 'title' => 'Modify localization page options form content', 'description' => 'This hook allows to modify localization page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '284', 'name' => 'actionPaymentPreferencesForm', 'title' => 'Modify payment preferences options form content', 'description' => 'This hook allows to modify payment preferences options form FormBuilder', 'position' => '1'],
            ['id_hook' => '285', 'name' => 'actionEmailConfigurationForm', 'title' => 'Modify email configuration options form content', 'description' => 'This hook allows to modify email configuration options form FormBuilder', 'position' => '1'],
            ['id_hook' => '286', 'name' => 'actionRequestSqlForm', 'title' => 'Modify request sql options form content', 'description' => 'This hook allows to modify request sql options form FormBuilder', 'position' => '1'],
            ['id_hook' => '287', 'name' => 'actionBackupForm', 'title' => 'Modify backup options form content', 'description' => 'This hook allows to modify backup options form FormBuilder', 'position' => '1'],
            ['id_hook' => '288', 'name' => 'actionWebservicePageForm', 'title' => 'Modify webservice page options form content', 'description' => 'This hook allows to modify webservice page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '289', 'name' => 'actionMetaPageForm', 'title' => 'Modify meta page options form content', 'description' => 'This hook allows to modify meta page options form FormBuilder', 'position' => '1'],
            ['id_hook' => '290', 'name' => 'actionEmployeeForm', 'title' => 'Modify employee options form content', 'description' => 'This hook allows to modify employee options form FormBuilder', 'position' => '1'],
            ['id_hook' => '291', 'name' => 'actionCurrencyForm', 'title' => 'Modify currency options form content', 'description' => 'This hook allows to modify currency options form FormBuilder', 'position' => '1'],
            ['id_hook' => '292', 'name' => 'actionShopLogoForm', 'title' => 'Modify shop logo options form content', 'description' => 'This hook allows to modify shop logo options form FormBuilder', 'position' => '1'],
            ['id_hook' => '293', 'name' => 'actionTaxForm', 'title' => 'Modify tax options form content', 'description' => 'This hook allows to modify tax options form FormBuilder', 'position' => '1'],
            ['id_hook' => '294', 'name' => 'actionMailThemeForm', 'title' => 'Modify mail theme options form content', 'description' => 'This hook allows to modify mail theme options form FormBuilder', 'position' => '1'],
            ['id_hook' => '295', 'name' => 'actionPerformancePageSave', 'title' => 'Modify performance page options form saved data', 'description' => 'This hook allows to modify data of performance page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '296', 'name' => 'actionMaintenancePageSave', 'title' => 'Modify maintenance page options form saved data', 'description' => 'This hook allows to modify data of maintenance page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '297', 'name' => 'actionAdministrationPageSave', 'title' => 'Modify administration page options form saved data', 'description' => 'This hook allows to modify data of administration page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '298', 'name' => 'actionShippingPreferencesPageSave', 'title' => 'Modify shipping preferences page options form saved data', 'description' => 'This hook allows to modify data of shipping preferences page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '299', 'name' => 'actionOrdersInvoicesByDateSave', 'title' => 'Modify orders invoices by date options form saved data', 'description' => 'This hook allows to modify data of orders invoices by date options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '300', 'name' => 'actionOrdersInvoicesByStatusSave', 'title' => 'Modify orders invoices by status options form saved data', 'description' => 'This hook allows to modify data of orders invoices by status options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '301', 'name' => 'actionOrdersInvoicesOptionsSave', 'title' => 'Modify orders invoices options options form saved data', 'description' => 'This hook allows to modify data of orders invoices options options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '302', 'name' => 'actionCustomerPreferencesPageSave', 'title' => 'Modify customer preferences page options form saved data', 'description' => 'This hook allows to modify data of customer preferences page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '303', 'name' => 'actionOrderPreferencesPageSave', 'title' => 'Modify order preferences page options form saved data', 'description' => 'This hook allows to modify data of order preferences page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '304', 'name' => 'actionProductPreferencesPageSave', 'title' => 'Modify product preferences page options form saved data', 'description' => 'This hook allows to modify data of product preferences page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '305', 'name' => 'actionGeneralPageSave', 'title' => 'Modify general page options form saved data', 'description' => 'This hook allows to modify data of general page options form after it was saved', 'position' => '1'],
            ['id_hook' => '306', 'name' => 'actionLogsPageSave', 'title' => 'Modify logs page options form saved data', 'description' => 'This hook allows to modify data of logs page options form after it was saved', 'position' => '1'],
            ['id_hook' => '307', 'name' => 'actionOrderDeliverySlipOptionsSave', 'title' => 'Modify order delivery slip options options form saved data', 'description' => 'This hook allows to modify data of order delivery slip options options form after it was
          saved
      ', 'position' => '1'],
            ['id_hook' => '308', 'name' => 'actionOrderDeliverySlipPdfSave', 'title' => 'Modify order delivery slip pdf options form saved data', 'description' => 'This hook allows to modify data of order delivery slip pdf options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '309', 'name' => 'actionGeolocationPageSave', 'title' => 'Modify geolocation page options form saved data', 'description' => 'This hook allows to modify data of geolocation page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '310', 'name' => 'actionLocalizationPageSave', 'title' => 'Modify localization page options form saved data', 'description' => 'This hook allows to modify data of localization page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '311', 'name' => 'actionPaymentPreferencesSave', 'title' => 'Modify payment preferences options form saved data', 'description' => 'This hook allows to modify data of payment preferences options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '312', 'name' => 'actionEmailConfigurationSave', 'title' => 'Modify email configuration options form saved data', 'description' => 'This hook allows to modify data of email configuration options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '313', 'name' => 'actionRequestSqlSave', 'title' => 'Modify request sql options form saved data', 'description' => 'This hook allows to modify data of request sql options form after it was saved', 'position' => '1'],
            ['id_hook' => '314', 'name' => 'actionBackupSave', 'title' => 'Modify backup options form saved data', 'description' => 'This hook allows to modify data of backup options form after it was saved', 'position' => '1'],
            ['id_hook' => '315', 'name' => 'actionWebservicePageSave', 'title' => 'Modify webservice page options form saved data', 'description' => 'This hook allows to modify data of webservice page options form after it was saved
      ', 'position' => '1'],
            ['id_hook' => '316', 'name' => 'actionMetaPageSave', 'title' => 'Modify meta page options form saved data', 'description' => 'This hook allows to modify data of meta page options form after it was saved', 'position' => '1'],
            ['id_hook' => '317', 'name' => 'actionEmployeeSave', 'title' => 'Modify employee options form saved data', 'description' => 'This hook allows to modify data of employee options form after it was saved', 'position' => '1'],
            ['id_hook' => '318', 'name' => 'actionCurrencySave', 'title' => 'Modify currency options form saved data', 'description' => 'This hook allows to modify data of currency options form after it was saved', 'position' => '1'],
            ['id_hook' => '319', 'name' => 'actionShopLogoSave', 'title' => 'Modify shop logo options form saved data', 'description' => 'This hook allows to modify data of shop logo options form after it was saved', 'position' => '1'],
            ['id_hook' => '320', 'name' => 'actionTaxSave', 'title' => 'Modify tax options form saved data', 'description' => 'This hook allows to modify data of tax options form after it was saved', 'position' => '1'],
            ['id_hook' => '321', 'name' => 'actionMailThemeSave', 'title' => 'Modify mail theme options form saved data', 'description' => 'This hook allows to modify data of mail theme options form after it was saved', 'position' => '1'],
            ['id_hook' => '322', 'name' => 'actionCategoryGridDefinitionModifier', 'title' => 'Modify category grid definition', 'description' => 'This hook allows to alter category grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '323', 'name' => 'actionEmployeeGridDefinitionModifier', 'title' => 'Modify employee grid definition', 'description' => 'This hook allows to alter employee grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '324', 'name' => 'actionContactGridDefinitionModifier', 'title' => 'Modify contact grid definition', 'description' => 'This hook allows to alter contact grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '325', 'name' => 'actionCustomerGridDefinitionModifier', 'title' => 'Modify customer grid definition', 'description' => 'This hook allows to alter customer grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '326', 'name' => 'actionLanguageGridDefinitionModifier', 'title' => 'Modify language grid definition', 'description' => 'This hook allows to alter language grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '327', 'name' => 'actionCurrencyGridDefinitionModifier', 'title' => 'Modify currency grid definition', 'description' => 'This hook allows to alter currency grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '328', 'name' => 'actionSupplierGridDefinitionModifier', 'title' => 'Modify supplier grid definition', 'description' => 'This hook allows to alter supplier grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '329', 'name' => 'actionProfileGridDefinitionModifier', 'title' => 'Modify profile grid definition', 'description' => 'This hook allows to alter profile grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '330', 'name' => 'actionCmsPageCategoryGridDefinitionModifier', 'title' => 'Modify cms page category grid definition', 'description' => 'This hook allows to alter cms page category grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '331', 'name' => 'actionTaxGridDefinitionModifier', 'title' => 'Modify tax grid definition', 'description' => 'This hook allows to alter tax grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '332', 'name' => 'actionManufacturerGridDefinitionModifier', 'title' => 'Modify manufacturer grid definition', 'description' => 'This hook allows to alter manufacturer grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '333', 'name' => 'actionManufacturerAddressGridDefinitionModifier', 'title' => 'Modify manufacturer address grid definition', 'description' => 'This hook allows to alter manufacturer address grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '334', 'name' => 'actionCmsPageGridDefinitionModifier', 'title' => 'Modify cms page grid definition', 'description' => 'This hook allows to alter cms page grid columns, actions and filters', 'position' => '1'],
            ['id_hook' => '335', 'name' => 'actionBackupGridQueryBuilderModifier', 'title' => 'Modify backup grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for backup grid', 'position' => '1'],
            ['id_hook' => '336', 'name' => 'actionCategoryGridQueryBuilderModifier', 'title' => 'Modify category grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for category grid', 'position' => '1'],
            ['id_hook' => '337', 'name' => 'actionEmployeeGridQueryBuilderModifier', 'title' => 'Modify employee grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for employee grid', 'position' => '1'],
            ['id_hook' => '338', 'name' => 'actionContactGridQueryBuilderModifier', 'title' => 'Modify contact grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for contact grid', 'position' => '1'],
            ['id_hook' => '339', 'name' => 'actionCustomerGridQueryBuilderModifier', 'title' => 'Modify customer grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for customer grid', 'position' => '1'],
            ['id_hook' => '340', 'name' => 'actionLanguageGridQueryBuilderModifier', 'title' => 'Modify language grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for language grid', 'position' => '1'],
            ['id_hook' => '341', 'name' => 'actionCurrencyGridQueryBuilderModifier', 'title' => 'Modify currency grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for currency grid', 'position' => '1'],
            ['id_hook' => '342', 'name' => 'actionSupplierGridQueryBuilderModifier', 'title' => 'Modify supplier grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for supplier grid', 'position' => '1'],
            ['id_hook' => '343', 'name' => 'actionProfileGridQueryBuilderModifier', 'title' => 'Modify profile grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for profile grid', 'position' => '1'],
            ['id_hook' => '344', 'name' => 'actionCmsPageCategoryGridQueryBuilderModifier', 'title' => 'Modify cms page category grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for cms page category grid', 'position' => '1'],
            ['id_hook' => '345', 'name' => 'actionTaxGridQueryBuilderModifier', 'title' => 'Modify tax grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for tax grid', 'position' => '1'],
            ['id_hook' => '346', 'name' => 'actionManufacturerGridQueryBuilderModifier', 'title' => 'Modify manufacturer grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for manufacturer grid', 'position' => '1'],
            ['id_hook' => '347', 'name' => 'actionManufacturerAddressGridQueryBuilderModifier', 'title' => 'Modify manufacturer address grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for manufacturer address grid', 'position' => '1'],
            ['id_hook' => '348', 'name' => 'actionCmsPageGridQueryBuilderModifier', 'title' => 'Modify cms page grid query builder', 'description' => 'This hook allows to alter Doctrine query builder for cms page grid', 'position' => '1'],
            ['id_hook' => '349', 'name' => 'actionLogsGridDataModifier', 'title' => 'Modify logs grid data', 'description' => 'This hook allows to modify logs grid data', 'position' => '1'],
            ['id_hook' => '350', 'name' => 'actionEmailLogsGridDataModifier', 'title' => 'Modify email logs grid data', 'description' => 'This hook allows to modify email logs grid data', 'position' => '1'],
            ['id_hook' => '351', 'name' => 'actionSqlRequestGridDataModifier', 'title' => 'Modify sql request grid data', 'description' => 'This hook allows to modify sql request grid data', 'position' => '1'],
            ['id_hook' => '352', 'name' => 'actionBackupGridDataModifier', 'title' => 'Modify backup grid data', 'description' => 'This hook allows to modify backup grid data', 'position' => '1'],
            ['id_hook' => '353', 'name' => 'actionWebserviceKeyGridDataModifier', 'title' => 'Modify webservice key grid data', 'description' => 'This hook allows to modify webservice key grid data', 'position' => '1'],
            ['id_hook' => '354', 'name' => 'actionMetaGridDataModifier', 'title' => 'Modify meta grid data', 'description' => 'This hook allows to modify meta grid data', 'position' => '1'],
            ['id_hook' => '355', 'name' => 'actionCategoryGridDataModifier', 'title' => 'Modify category grid data', 'description' => 'This hook allows to modify category grid data', 'position' => '1'],
            ['id_hook' => '356', 'name' => 'actionEmployeeGridDataModifier', 'title' => 'Modify employee grid data', 'description' => 'This hook allows to modify employee grid data', 'position' => '1'],
            ['id_hook' => '357', 'name' => 'actionContactGridDataModifier', 'title' => 'Modify contact grid data', 'description' => 'This hook allows to modify contact grid data', 'position' => '1'],
            ['id_hook' => '358', 'name' => 'actionCustomerGridDataModifier', 'title' => 'Modify customer grid data', 'description' => 'This hook allows to modify customer grid data', 'position' => '1'],
            ['id_hook' => '359', 'name' => 'actionLanguageGridDataModifier', 'title' => 'Modify language grid data', 'description' => 'This hook allows to modify language grid data', 'position' => '1'],
            ['id_hook' => '360', 'name' => 'actionCurrencyGridDataModifier', 'title' => 'Modify currency grid data', 'description' => 'This hook allows to modify currency grid data', 'position' => '1'],
            ['id_hook' => '361', 'name' => 'actionSupplierGridDataModifier', 'title' => 'Modify supplier grid data', 'description' => 'This hook allows to modify supplier grid data', 'position' => '1'],
            ['id_hook' => '362', 'name' => 'actionProfileGridDataModifier', 'title' => 'Modify profile grid data', 'description' => 'This hook allows to modify profile grid data', 'position' => '1'],
            ['id_hook' => '363', 'name' => 'actionCmsPageCategoryGridDataModifier', 'title' => 'Modify cms page category grid data', 'description' => 'This hook allows to modify cms page category grid data', 'position' => '1'],
            ['id_hook' => '364', 'name' => 'actionTaxGridDataModifier', 'title' => 'Modify tax grid data', 'description' => 'This hook allows to modify tax grid data', 'position' => '1'],
            ['id_hook' => '365', 'name' => 'actionManufacturerGridDataModifier', 'title' => 'Modify manufacturer grid data', 'description' => 'This hook allows to modify manufacturer grid data', 'position' => '1'],
            ['id_hook' => '366', 'name' => 'actionManufacturerAddressGridDataModifier', 'title' => 'Modify manufacturer address grid data', 'description' => 'This hook allows to modify manufacturer address grid data', 'position' => '1'],
            ['id_hook' => '367', 'name' => 'actionCmsPageGridDataModifier', 'title' => 'Modify cms page grid data', 'description' => 'This hook allows to modify cms page grid data', 'position' => '1'],
            ['id_hook' => '368', 'name' => 'actionCategoryGridFilterFormModifier', 'title' => 'Modify category grid filters', 'description' => 'This hook allows to modify filters for category grid', 'position' => '1'],
            ['id_hook' => '369', 'name' => 'actionEmployeeGridFilterFormModifier', 'title' => 'Modify employee grid filters', 'description' => 'This hook allows to modify filters for employee grid', 'position' => '1'],
            ['id_hook' => '370', 'name' => 'actionContactGridFilterFormModifier', 'title' => 'Modify contact grid filters', 'description' => 'This hook allows to modify filters for contact grid', 'position' => '1'],
            ['id_hook' => '371', 'name' => 'actionCustomerGridFilterFormModifier', 'title' => 'Modify customer grid filters', 'description' => 'This hook allows to modify filters for customer grid', 'position' => '1'],
            ['id_hook' => '372', 'name' => 'actionLanguageGridFilterFormModifier', 'title' => 'Modify language grid filters', 'description' => 'This hook allows to modify filters for language grid', 'position' => '1'],
            ['id_hook' => '373', 'name' => 'actionCurrencyGridFilterFormModifier', 'title' => 'Modify currency grid filters', 'description' => 'This hook allows to modify filters for currency grid', 'position' => '1'],
            ['id_hook' => '374', 'name' => 'actionSupplierGridFilterFormModifier', 'title' => 'Modify supplier grid filters', 'description' => 'This hook allows to modify filters for supplier grid', 'position' => '1'],
            ['id_hook' => '375', 'name' => 'actionProfileGridFilterFormModifier', 'title' => 'Modify profile grid filters', 'description' => 'This hook allows to modify filters for profile grid', 'position' => '1'],
            ['id_hook' => '376', 'name' => 'actionCmsPageCategoryGridFilterFormModifier', 'title' => 'Modify cms page category grid filters', 'description' => 'This hook allows to modify filters for cms page category grid', 'position' => '1'],
            ['id_hook' => '377', 'name' => 'actionTaxGridFilterFormModifier', 'title' => 'Modify tax grid filters', 'description' => 'This hook allows to modify filters for tax grid', 'position' => '1'],
            ['id_hook' => '378', 'name' => 'actionManufacturerGridFilterFormModifier', 'title' => 'Modify manufacturer grid filters', 'description' => 'This hook allows to modify filters for manufacturer grid', 'position' => '1'],
            ['id_hook' => '379', 'name' => 'actionManufacturerAddressGridFilterFormModifier', 'title' => 'Modify manufacturer address grid filters', 'description' => 'This hook allows to modify filters for manufacturer address grid', 'position' => '1'],
            ['id_hook' => '380', 'name' => 'actionCmsPageGridFilterFormModifier', 'title' => 'Modify cms page grid filters', 'description' => 'This hook allows to modify filters for cms page grid', 'position' => '1'],
            ['id_hook' => '381', 'name' => 'actionCategoryGridPresenterModifier', 'title' => 'Modify category grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for category grid
      ', 'position' => '1'],
            ['id_hook' => '382', 'name' => 'actionEmployeeGridPresenterModifier', 'title' => 'Modify employee grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for employee grid
      ', 'position' => '1'],
            ['id_hook' => '383', 'name' => 'actionContactGridPresenterModifier', 'title' => 'Modify contact grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for contact grid
      ', 'position' => '1'],
            ['id_hook' => '384', 'name' => 'actionCustomerGridPresenterModifier', 'title' => 'Modify customer grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for customer grid
      ', 'position' => '1'],
            ['id_hook' => '385', 'name' => 'actionLanguageGridPresenterModifier', 'title' => 'Modify language grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for language grid
      ', 'position' => '1'],
            ['id_hook' => '386', 'name' => 'actionCurrencyGridPresenterModifier', 'title' => 'Modify currency grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for currency grid
      ', 'position' => '1'],
            ['id_hook' => '387', 'name' => 'actionSupplierGridPresenterModifier', 'title' => 'Modify supplier grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for supplier grid
      ', 'position' => '1'],
            ['id_hook' => '388', 'name' => 'actionProfileGridPresenterModifier', 'title' => 'Modify profile grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for profile grid
      ', 'position' => '1'],
            ['id_hook' => '389', 'name' => 'actionCmsPageCategoryGridPresenterModifier', 'title' => 'Modify cms page category grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for cms page category
          grid
      ', 'position' => '1'],
            ['id_hook' => '390', 'name' => 'actionTaxGridPresenterModifier', 'title' => 'Modify tax grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for tax grid
      ', 'position' => '1'],
            ['id_hook' => '391', 'name' => 'actionManufacturerGridPresenterModifier', 'title' => 'Modify manufacturer grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for manufacturer grid
      ', 'position' => '1'],
            ['id_hook' => '392', 'name' => 'actionManufacturerAddressGridPresenterModifier', 'title' => 'Modify manufacturer address grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for manufacturer address
          grid
      ', 'position' => '1'],
            ['id_hook' => '393', 'name' => 'actionCmsPageGridPresenterModifier', 'title' => 'Modify cms page grid template data', 'description' => 'This hook allows to modify data which is about to be used in template for cms page grid
      ', 'position' => '1'],
            ['id_hook' => '394', 'name' => 'registerGDPRConsent', 'title' => 'registerGDPRConsent', 'description' => '', 'position' => '1'],
            ['id_hook' => '395', 'name' => 'dashboardZoneOne', 'title' => 'dashboardZoneOne', 'description' => '', 'position' => '1'],
            ['id_hook' => '396', 'name' => 'dashboardData', 'title' => 'dashboardData', 'description' => '', 'position' => '1'],
            ['id_hook' => '397', 'name' => 'actionObjectOrderAddAfter', 'title' => 'actionObjectOrderAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '398', 'name' => 'actionObjectCustomerAddAfter', 'title' => 'actionObjectCustomerAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '399', 'name' => 'actionObjectCustomerMessageAddAfter', 'title' => 'actionObjectCustomerMessageAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '400', 'name' => 'actionObjectCustomerThreadAddAfter', 'title' => 'actionObjectCustomerThreadAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '401', 'name' => 'actionObjectOrderReturnAddAfter', 'title' => 'actionObjectOrderReturnAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '402', 'name' => 'actionAdminControllerSetMedia', 'title' => 'actionAdminControllerSetMedia', 'description' => '', 'position' => '1'],
            ['id_hook' => '403', 'name' => 'dashboardZoneTwo', 'title' => 'dashboardZoneTwo', 'description' => '', 'position' => '1'],
            ['id_hook' => '404', 'name' => 'actionSearch', 'title' => 'actionSearch', 'description' => '', 'position' => '1'],
            ['id_hook' => '405', 'name' => 'gSitemapAppendUrls', 'title' => 'GSitemap Append URLs', 'description' => 'This hook allows a module to add URLs to a generated sitemap', 'position' => '1'],
            ['id_hook' => '406', 'name' => 'actionObjectLanguageAddAfter', 'title' => 'actionObjectLanguageAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '407', 'name' => 'paymentOptions', 'title' => 'paymentOptions', 'description' => '', 'position' => '1'],
            ['id_hook' => '408', 'name' => 'displayNav1', 'title' => 'displayNav1', 'description' => '', 'position' => '1'],
            ['id_hook' => '409', 'name' => 'actionAdminStoresControllerUpdate_optionsAfter', 'title' => 'actionAdminStoresControllerUpdate_optionsAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '410', 'name' => 'actionAdminCurrenciesControllerSaveAfter', 'title' => 'actionAdminCurrenciesControllerSaveAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '411', 'name' => 'actionModuleRegisterHookAfter', 'title' => 'actionModuleRegisterHookAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '412', 'name' => 'actionModuleUnRegisterHookAfter', 'title' => 'actionModuleUnRegisterHookAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '413', 'name' => 'actionShopDataDuplication', 'title' => 'actionShopDataDuplication', 'description' => '', 'position' => '1'],
            ['id_hook' => '414', 'name' => 'actionFrontControllerSetMedia', 'title' => 'actionFrontControllerSetMedia', 'description' => '', 'position' => '1'],
            ['id_hook' => '415', 'name' => 'displayFooterBefore', 'title' => 'displayFooterBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '416', 'name' => 'actionObjectCustomerUpdateBefore', 'title' => 'actionObjectCustomerUpdateBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '417', 'name' => 'displayAdminCustomersForm', 'title' => 'displayAdminCustomersForm', 'description' => '', 'position' => '1'],
            ['id_hook' => '418', 'name' => 'actionDeleteGDPRCustomer', 'title' => 'actionDeleteGDPRCustomer', 'description' => '', 'position' => '1'],
            ['id_hook' => '419', 'name' => 'actionExportGDPRData', 'title' => 'actionExportGDPRData', 'description' => '', 'position' => '1'],
            ['id_hook' => '420', 'name' => 'actionFeatureFormBuilderModifier', 'title' => 'actionFeatureFormBuilderModifier', 'description' => '', 'position' => '1'],
            ['id_hook' => '421', 'name' => 'actionAfterCreateFeatureFormHandler', 'title' => 'actionAfterCreateFeatureFormHandler', 'description' => '', 'position' => '1'],
            ['id_hook' => '422', 'name' => 'actionAfterUpdateFeatureFormHandler', 'title' => 'actionAfterUpdateFeatureFormHandler', 'description' => '', 'position' => '1'],
            ['id_hook' => '423', 'name' => 'productSearchProvider', 'title' => 'productSearchProvider', 'description' => '', 'position' => '1'],
            ['id_hook' => '424', 'name' => 'actionObjectSpecificPriceRuleUpdateBefore', 'title' => 'actionObjectSpecificPriceRuleUpdateBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '425', 'name' => 'actionAdminSpecificPriceRuleControllerSaveAfter', 'title' => 'actionAdminSpecificPriceRuleControllerSaveAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '426', 'name' => 'displayOrderConfirmation2', 'title' => 'displayOrderConfirmation2', 'description' => '', 'position' => '1'],
            ['id_hook' => '427', 'name' => 'displayCrossSellingShoppingCart', 'title' => 'displayCrossSellingShoppingCart', 'description' => '', 'position' => '1'],
            ['id_hook' => '428', 'name' => 'actionAdminGroupsControllerSaveAfter', 'title' => 'actionAdminGroupsControllerSaveAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '429', 'name' => 'actionObjectCategoryUpdateAfter', 'title' => 'actionObjectCategoryUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '430', 'name' => 'actionObjectCategoryDeleteAfter', 'title' => 'actionObjectCategoryDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '431', 'name' => 'actionObjectCategoryAddAfter', 'title' => 'actionObjectCategoryAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '432', 'name' => 'actionObjectCmsUpdateAfter', 'title' => 'actionObjectCmsUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '433', 'name' => 'actionObjectCmsDeleteAfter', 'title' => 'actionObjectCmsDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '434', 'name' => 'actionObjectCmsAddAfter', 'title' => 'actionObjectCmsAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '435', 'name' => 'actionObjectSupplierUpdateAfter', 'title' => 'actionObjectSupplierUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '436', 'name' => 'actionObjectSupplierDeleteAfter', 'title' => 'actionObjectSupplierDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '437', 'name' => 'actionObjectSupplierAddAfter', 'title' => 'actionObjectSupplierAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '438', 'name' => 'actionObjectManufacturerUpdateAfter', 'title' => 'actionObjectManufacturerUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '439', 'name' => 'actionObjectManufacturerDeleteAfter', 'title' => 'actionObjectManufacturerDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '440', 'name' => 'actionObjectManufacturerAddAfter', 'title' => 'actionObjectManufacturerAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '441', 'name' => 'actionObjectProductUpdateAfter', 'title' => 'actionObjectProductUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '442', 'name' => 'actionObjectProductDeleteAfter', 'title' => 'actionObjectProductDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '443', 'name' => 'actionObjectProductAddAfter', 'title' => 'actionObjectProductAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '444', 'name' => 'displaySearch', 'title' => 'displaySearch', 'description' => '', 'position' => '1'],
            ['id_hook' => '445', 'name' => 'displayAdminNavBarBeforeEnd', 'title' => 'displayAdminNavBarBeforeEnd', 'description' => '', 'position' => '1'],
            ['id_hook' => '446', 'name' => 'displayAdminAfterHeader', 'title' => 'displayAdminAfterHeader', 'description' => '', 'position' => '1'],
            ['id_hook' => '447', 'name' => 'displayGDPRConsent', 'title' => 'displayGDPRConsent', 'description' => '', 'position' => '1'],
            ['id_hook' => '448', 'name' => 'displayAdminOrderLeft', 'title' => 'displayAdminOrderLeft', 'description' => '', 'position' => '1'],
            ['id_hook' => '449', 'name' => 'displayAdminOrderMainBottom', 'title' => 'displayAdminOrderMainBottom', 'description' => '', 'position' => '1'],
            ['id_hook' => '450', 'name' => 'actionObjectShopAddAfter', 'title' => 'actionObjectShopAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '451', 'name' => 'displayExpressCheckout', 'title' => 'displayExpressCheckout', 'description' => '', 'position' => '1'],
            ['id_hook' => '452', 'name' => 'actionbeforecartupdateqty', 'title' => 'actionbeforecartupdateqty', 'description' => '', 'position' => '1'],
            ['id_hook' => '453', 'name' => 'displayNav2', 'title' => '', 'description' => '', 'position' => '1'],
            ['id_hook' => '454', 'name' => 'displayReassurance', 'title' => '', 'description' => '', 'position' => '1'],
            ['id_hook' => '455', 'name' => 'displayCatalogFooterDescription', 'title' => 'displayCatalogFooterDescription', 'description' => '', 'position' => '1'],
            ['id_hook' => '456', 'name' => 'displayCatalogue', 'title' => 'displayCatalogue', 'description' => '', 'position' => '1'],
            ['id_hook' => '457', 'name' => 'displayCatalogCategory', 'title' => 'displayCatalogCategory', 'description' => '', 'position' => '1'],
            ['id_hook' => '458', 'name' => 'displayAdvancedSearch4', 'title' => 'displayAdvancedSearch4', 'description' => '', 'position' => '1'],
            ['id_hook' => '459', 'name' => 'moduleRoutes', 'title' => 'moduleRoutes', 'description' => '', 'position' => '1'],
            ['id_hook' => '460', 'name' => 'actionAdminProductsControllerSaveAfter', 'title' => 'actionAdminProductsControllerSaveAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '461', 'name' => 'actionObjectAddAfter', 'title' => 'actionObjectAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '462', 'name' => 'actionObjectUpdateAfter', 'title' => 'actionObjectUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '463', 'name' => 'actionObjectDeleteAfter', 'title' => 'actionObjectDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '464', 'name' => 'actionProductListOverride', 'title' => 'actionProductListOverride', 'description' => '', 'position' => '1'],
            ['id_hook' => '465', 'name' => 'actionAdminProductsControllerCoreSaveAfter', 'title' => 'actionAdminProductsControllerCoreSaveAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '466', 'name' => 'actionObjectShopDeleteAfter', 'title' => 'actionObjectShopDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '467', 'name' => 'hookDisplayAdminProductsPriceStepBottom', 'title' => 'hookDisplayAdminProductsPriceStepBottom', 'description' => '', 'position' => '1'],
            ['id_hook' => '468', 'name' => 'displayAdminProductsExtra', 'title' => 'displayAdminProductsExtra', 'description' => '', 'position' => '1'],
            ['id_hook' => '469', 'name' => 'displayCategoryParent', 'title' => 'displayCategoryParent', 'description' => '', 'position' => '1'],
            ['id_hook' => '470', 'name' => 'displayProductTab', 'title' => 'displayProductTab', 'description' => '', 'position' => '1'],
            ['id_hook' => '471', 'name' => 'displayProductTabContent', 'title' => 'displayProductTabContent', 'description' => '', 'position' => '1'],
            ['id_hook' => '472', 'name' => 'displayProductPriceBlock', 'title' => 'displayProductPriceBlock', 'description' => '', 'position' => '1'],
            ['id_hook' => '473', 'name' => 'displayBanner', 'title' => 'displayBanner', 'description' => '', 'position' => '1'],
            ['id_hook' => '474', 'name' => 'displayQuantityDiscountProCustom1', 'title' => 'displayQuantityDiscountProCustom1', 'description' => '', 'position' => '1'],
            ['id_hook' => '475', 'name' => 'displayQuantityDiscountProCustom2', 'title' => 'displayQuantityDiscountProCustom2', 'description' => '', 'position' => '1'],
            ['id_hook' => '476', 'name' => 'displayQuantityDiscountProCustom3', 'title' => 'displayQuantityDiscountProCustom3', 'description' => '', 'position' => '1'],
            ['id_hook' => '477', 'name' => 'displayQuantityDiscountProCustom4', 'title' => 'displayQuantityDiscountProCustom4', 'description' => '', 'position' => '1'],
            ['id_hook' => '478', 'name' => 'displayQuantityDiscountProCustom5', 'title' => 'displayQuantityDiscountProCustom5', 'description' => '', 'position' => '1'],
            ['id_hook' => '481', 'name' => 'actionObjectQuantityDiscountRuleUpdateBefore', 'title' => 'actionObjectQuantityDiscountRuleUpdateBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '484', 'name' => 'displayAdminProductsMainStepRightTop', 'title' => 'displayAdminProductsMainStepRightTop', 'description' => '', 'position' => '1'],
            ['id_hook' => '487', 'name' => 'displayProductOutOfStock', 'title' => 'displayProductOutOfStock', 'description' => '', 'position' => '1'],
            ['id_hook' => '490', 'name' => 'actionGetProductPropertiesBefore', 'title' => 'actionGetProductPropertiesBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '493', 'name' => 'displayFooterAfter', 'title' => 'displayFooterAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '496', 'name' => 'displayformcagnotte', 'title' => 'displayformcagnotte', 'description' => '', 'position' => '1'],
            ['id_hook' => '499', 'name' => 'displayReductionCagnotte', 'title' => 'displayReductionCagnotte', 'description' => '', 'position' => '1'],
            ['id_hook' => '500', 'name' => 'displayCartModalContent', 'title' => 'displayCartModalContent', 'description' => '', 'position' => '1'],
            ['id_hook' => '503', 'name' => 'displayStockForm', 'title' => 'displayStockForm', 'description' => '', 'position' => '1'],
            ['id_hook' => '509', 'name' => 'displayPopinPayment', 'title' => 'displayPopinPayment', 'description' => '', 'position' => '1'],
            ['id_hook' => '510', 'name' => 'actionObjectSpecificPriceAddAfter', 'title' => 'actionObjectSpecificPriceAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '513', 'name' => 'actionObjectImageAddAfter', 'title' => 'actionObjectImageAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '516', 'name' => 'actionObjectImageDeleteAfter', 'title' => 'actionObjectImageDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '519', 'name' => 'actionObjectSpecificPriceUpdateAfter', 'title' => 'actionObjectSpecificPriceUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '521', 'name' => 'hookBackOfficeHeader', 'title' => 'hookBackOfficeHeader', 'description' => '', 'position' => '1'],
            ['id_hook' => '524', 'name' => 'displayNavFullWidthinfo', 'title' => 'displayNavFullWidthinfo', 'description' => '', 'position' => '1'],
            ['id_hook' => '527', 'name' => 'displayProductCarriers', 'title' => 'displayProductCarriers', 'description' => '', 'position' => '1'],
            ['id_hook' => '530', 'name' => 'displayProductLineCarriers', 'title' => 'displayProductLineCarriers', 'description' => '', 'position' => '1'],
            ['id_hook' => '533', 'name' => 'displayProductMiniatureLineCarriers', 'title' => 'displayProductMiniatureLineCarriers', 'description' => '', 'position' => '1'],
            ['id_hook' => '536', 'name' => 'overrideCartPresenter', 'title' => 'overrideCartPresenter', 'description' => '', 'position' => '1'],
            ['id_hook' => '539', 'name' => 'actionCartUpdateQuantityBefore', 'title' => 'actionCartUpdateQuantityBefore', 'description' => '', 'position' => '1'],
            ['id_hook' => '542', 'name' => 'actionObjectOrderUpdateAfter', 'title' => 'actionObjectOrderUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '545', 'name' => 'actionObjectCombinationDeleteAfter', 'title' => 'actionObjectCombinationDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '548', 'name' => 'actionObjectSpecificPriceDeleteAfter', 'title' => 'actionObjectSpecificPriceDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '551', 'name' => 'actionProductListModifier', 'title' => 'actionProductListModifier', 'description' => '', 'position' => '1'],
            ['id_hook' => '554', 'name' => 'actionAdminProductsListingResultsModifier', 'title' => 'actionAdminProductsListingResultsModifier', 'description' => '', 'position' => '1'],
            ['id_hook' => '557', 'name' => 'actionGetProductPropertiesAfter', 'title' => 'actionGetProductPropertiesAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '560', 'name' => 'displayProductVideos', 'title' => 'displayProductVideos', 'description' => '', 'position' => '1'],
            ['id_hook' => '563', 'name' => 'displayProductDeliveryDate', 'title' => 'displayProductDeliveryDate', 'description' => '', 'position' => '1'],
            ['id_hook' => '566', 'name' => 'displayProductMiniatureDeliveryDate', 'title' => 'displayProductMiniatureDeliveryDate', 'description' => '', 'position' => '1'],
            ['id_hook' => '569', 'name' => 'actionObjectAddressDeleteAfter', 'title' => 'actionObjectAddressDeleteAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '572', 'name' => 'actionSlddatastudioAvailableFields', 'title' => 'actionSlddatastudioAvailableFields', 'description' => '', 'position' => '1'],
            ['id_hook' => '575', 'name' => 'actionSlddatastudioResults', 'title' => 'actionSlddatastudioResults', 'description' => '', 'position' => '1'],
            ['id_hook' => '578', 'name' => 'actionSlddatastudioRequestedField', 'title' => 'actionSlddatastudioRequestedField', 'description' => '', 'position' => '1'],
            ['id_hook' => '581', 'name' => 'actionObjectCartRuleAddAfter', 'title' => 'actionObjectCartRuleAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '584', 'name' => 'actionObjectCartRuleUpdateAfter', 'title' => 'actionObjectCartRuleUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '587', 'name' => 'displayFaqsRightColumn', 'title' => 'displayFaqsRightColumn', 'description' => '', 'position' => '1'],
            ['id_hook' => '590', 'name' => 'displayFaqsLeftColumn', 'title' => 'displayFaqsLeftColumn', 'description' => '', 'position' => '1'],
            ['id_hook' => '593', 'name' => 'displayformreturnproduct', 'title' => 'displayformreturnproduct', 'description' => '', 'position' => '1'],
            ['id_hook' => '596', 'name' => 'actionDispatcher', 'title' => 'actionDispatcher', 'description' => '', 'position' => '1'],
            ['id_hook' => '599', 'name' => 'displayBeforeProductCarriers', 'title' => 'displayBeforeProductCarriers', 'description' => '', 'position' => '1'],
            ['id_hook' => '602', 'name' => 'displayProductCarrierStockAlert', 'title' => 'displayProductCarrierStockAlert', 'description' => '', 'position' => '1'],
            ['id_hook' => '605', 'name' => 'actionAfterProductStockUpdate', 'title' => 'actionAfterProductStockUpdate', 'description' => '', 'position' => '1'],
            ['id_hook' => '606', 'name' => 'displayProductVisitors', 'title' => 'displayProductVisitors', 'description' => '', 'position' => '1'],
            ['id_hook' => '609', 'name' => 'displayLiveIframeCms', 'title' => 'displayLiveIframeCms', 'description' => '', 'position' => '1'],
            ['id_hook' => '612', 'name' => 'actionProductAddAfter', 'title' => 'actionProductAddAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '613', 'name' => 'actionProductUpdateAfter', 'title' => 'actionProductUpdateAfter', 'description' => '', 'position' => '1'],
            ['id_hook' => '614', 'name' => 'hookSendMailAlterTemplateVars', 'title' => 'hookSendMailAlterTemplateVars', 'description' => '', 'position' => '1'],
        ];
        foreach ($ps_hook as $hookData) {
            $hook = new Hook();
            $hook->setName($hookData['name']);
            $hook->setTitle($hookData['title']);
            $manager->persist($hook);
        }

        $datas = [
            'Product' => [
                'table' => 'product',
                'hasShop' => true,
                'hasLang' => true,
            ],
            'Category' => [
                'table' => 'category',
                'hasShop' => true,
                'hasLang' => true,
            ],
            'Customer' => [
                'table' => 'customer',
                'hasShop' => false,
                'hasLang' => false,
            ],
        ];
        foreach ($datas as $class => $data) {
            $tableMapping = new TableMapping();
            $tableMapping->setClass($class);
            $tableMapping->setTableName($data['table']);
            $tableMapping->setHasShopTable($data['hasShop']);
            $tableMapping->setHasLangTable($data['hasLang']);
            $manager->persist($tableMapping);
        }
        $user = new User();
        $user->setEmail('admin@yopmail.com');

        $password = $this->encoder->encodePassword($user, 'password');
        $user->setPassword($password);

        $manager->persist($user);

        $manager->flush();
    }
}

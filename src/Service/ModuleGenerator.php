<?php

namespace App\Service;

use App\Entity\TableMapping;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Nette\PhpGenerator\PsrPrinter;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PhpNamespace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ModuleGenerator
{
    /**
     * List of field types.
     */
    const TYPE_INT = 1;
    const TYPE_BOOL = 2;
    const TYPE_FLOAT = 4;
    const TYPE_DATE = 5;
    const TYPE_HTML = 6;
    const TYPE_NOTHING = 7;
    const TYPE_SQL = 8;
    private $base_dir;
    private $module_dir;
    public $module_data;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var string[]
     */
    private $mapping = [
        'column_name' => 'field_name',
        'column_type' => 'field_type',
        'column_length' => 'field_length',
        'is_column_nullable' => 'is_nullable',
        'is_column_lang' => 'is_lang',
        'is_column_shop' => 'is_shop',
        'default_column_value' => 'default_value',
    ];
    /**
     * @var array
     */
    private $params;
    /**
     * @var mixed
     */
    private $lastField;
    /**
     * @var array
     */
    private $use;
    /**
     * @var array
     */
    private $installSql;
    /**
     * @var array
     */
    private $unInstallSql;
    private $setters;
    private $getters;
    private $langFields;
    private $tabs;

    /**
     * ModuleGenerator constructor.
     * @param $base_dir
     * @param $module_dir
     * @param $module_data
     * @param EntityManagerInterface $em
     */
    public function __construct($base_dir, $module_dir, $module_data, EntityManagerInterface $em)
    {
        $this->base_dir = $base_dir;
        $this->module_dir = $module_dir;
        $this->module_data = $module_data;
        $this->em = $em;
        $this->filesystem = new Filesystem();
        $this->params = $this->getParams();
    }

    /**
     * @param $string
     * @param string $delimiter
     *
     * @return string
     */
    public function slugify($string, $delimiter = '-'): string
    {
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        setlocale(LC_ALL, $oldLocale);

        return $clean;
    }

    /**
     * @return bool
     */
    public function generateComposer(): bool
    {
        $content = file_get_contents($this->base_dir . '/samples/composer.json');
        $content = str_replace(array('$companyNameLower', '$moduleName', '$nameSpace', '$companyName', '$contact_email'), array($this->params['lower']['company_name'], $this->params['lower']['module_name'], $this->params['upper']['module_name'], $this->params['upper']['company_name'], $this->module_data['email']), $content);
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'composer.json', $content);
        return true;
    }

    /**
     * @return bool|\Symfony\Component\HttpFoundation\File\File
     */
    public function setLogo()
    {
        if (!empty($_FILES)) {
            $file = $_FILES['module_logo'];
            if (empty($file['tmp_name'])) {
                return false;
            }
            $uploadedFile = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error'], false);
            $destination = $this->module_dir;
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename . '.' . $uploadedFile->guessExtension();
            return $uploadedFile->move($destination, $newFilename);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function generateConfig()
    {
        $content = file_get_contents($this->base_dir . '/samples/config.xml');
        $params = $this->getParams();
        $content = str_replace(array('$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'), array($params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $params['upper']['company_name']), $content);

        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'config.xml', $content);
        $this->copyDirFiles('config');
        return true;
    }

    /**
     * @return bool
     */
    public function generateReadMe()
    {
        $content = file_get_contents($this->base_dir . '/samples/Readme.md');
        $params = $this->getParams();
        $content = str_replace(array('$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'), array($params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $params['upper']['company_name']), $content);

        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'Readme.md', $content);
        return true;
    }

    /**
     * @param null $dir
     * @return bool
     */
    public function generateIndex($dir = null)
    {
        if (empty($dir) || !is_dir($dir)) {
            $dir = $this->module_dir;
        }
        $content = file_get_contents($this->base_dir . '/samples/index.php');
        $params = $this->getParams();
        $content = str_replace(array('$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'), array($params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $params['upper']['company_name']), $content);

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.php', $content);
        return true;
    }

    private function getParams()
    {
        $module_name = $this->module_data['module_name'];
        $company_name = $this->module_data['company_name'];
        return ['lower' => ['module_name' => $this->slugify($module_name), 'company_name' => $this->slugify($company_name)], 'upper' => ['module_name' => ucfirst($module_name), 'company_name' => ucfirst($company_name)]];
    }

    public function copyStansardDir()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $dirs = [
            'views',
            'sql',
            'upgrade'
        ];
        foreach ($dirs as $dir) {
            $finder->files()->in($this->base_dir . '/samples/' . $dir);

            foreach ($finder as $file) {
                $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
                $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
            }
        }

        return false;
    }

    public function generateModuleClass()
    {

        $fileSystem = new Filesystem();
        $fileSystem->copy($this->base_dir . '/samples/moduleclass.php', $this->module_dir . '/' . $this->module_data['module_name'] . '.php');
        $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
        $params = $this->getParams();
        $content = $this->replaceStandardStrings($content);

        $content = str_replace(array('Moduleclass', 'moduleclass', 'module_author', 'Diplay name', 'module_description', 'MODULECLASS'), array($params['upper']['module_name'], $params['lower']['module_name'], $params['upper']['company_name'], $this->module_data['display_name'], $this->module_data['description'], strtoupper($params['lower']['module_name'])), $content);
        $class = new ClassType('demo');
        file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
        if (isset($this->module_data['hooks']) && !empty($hooks = $this->module_data['hooks'])) {
            $result = array();
            array_walk_recursive($hooks, function ($v) use (&$result) {
                $result[] = $v;
            });
            $register_hooks = '';

            foreach ($result as $hook) {
                $register_hooks .= " && method->registerHook('" . $hook . "')\n";
                $lowerHookName = $hook;
                $hook = ucfirst($hook);
                if (strpos($content, 'hook' . $hook) === false) {
                    $method = $class->addMethod('hook' . $hook);
                    $method->addParameter('params');
                    if (!empty($this->module_data['hooksContents']) && isset($this->module_data['hooksContents'][$lowerHookName]) && !empty($hookContent = $this->module_data['hooksContents'][$lowerHookName])) {
                        $hookContent = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $hookContent);
                        $method->setBody($hookContent);
                    }
                }
            }
            $content = str_replace("registerHook('backOfficeHeader')", "registerHook('backOfficeHeader')\n" . $register_hooks, $content);
        }
            if (!empty($this->module_data['query']) && !empty($query = $this->module_data['query'])) {
                foreach ($query as $ind => $qb) {
                    $method = $class->addMethod('updateExtra' . $ind . 'Field');
                    $method->addParameter(strtolower($ind) . 'Id');
                    $qb = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $qb);
                    $method->setBody($qb);
                }
            }
            if (!empty($this->module_data['presenter']['product']) && !empty($presenters = $this->module_data['presenter']['product'])) {
                foreach ($presenters as $fn => $body) {
                    $method = $class->addMethod($fn);
                    $method->addParameter('value');
                    $body = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $body);
                    $method->setBody($body);
                }
            }
            $installSql = '';

            if (!empty($this->installSql)) {
                foreach ($this->installSql as $sql) {
                    $installSql .= PHP_EOL . $sql;
                }
            }
            $executionLoop = 'foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                    return false;
                }
            }';
            if (!empty($installSql)) {
                $installContent = file($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'install_vg.php');
                $installContent[26] = $installSql;
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', implode("", $installContent));
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $executionLoop, FILE_APPEND);
            }
            $unInstallSql = '';
            if (!empty($this->installSql)) {
                foreach ($this->unInstallSql as $sql) {
                    $unInstallSql .= PHP_EOL . $sql;
                }
            }
            if (!empty($unInstallSql)) {
                //uninstall
                $uninstallContent = file($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'install_vg.php');
                $uninstallContent[26] = $unInstallSql;
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', implode("", $uninstallContent));
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', $executionLoop, FILE_APPEND);
            }


            $content = str_replace("method", '$this', $content);

            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . $this->module_data['module_name'] . '.php', $content);

            $printer = new Printer;
            $code = $printer->printClass($class);

            $arr = explode("\n", $code);
            unset($arr[0], $arr[1]);
            $code = implode("\n", $arr);
            $lines = file($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
            array_pop($lines);

            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', implode('', $lines));
            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $code, FILE_APPEND);


        if (!empty($this->module_data['use']) && !empty($query = $this->module_data['use'])) {
            $useContent = '';

            foreach ($this->module_data['use'] as $objectName => $useData) {
                foreach ($useData as $use) {

                    $useContent .= 'use ' . $use . ';' . PHP_EOL;
                }
            }
            $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
            $content = str_replace('/** add uses */', $useContent, $content);
            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
        }
        if(!empty($this->tabs)){
            foreach ($this->tabs as $key=>$tab){
                if($key==='getContent'){
                    $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
                    $content = str_replace('/** getContent */', $tab, $content);
                    file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
                }
                if($key==='const'){
                    $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
                    $content = str_replace('/** consts */', $tab, $content);
                    file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
                }
                if($key==='tabs'){
                    $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
                    $content = str_replace('/** getModuleTabs */', $tab, $content);
                    $content = str_replace('parent::install() &&', 'parent::install() && $this->installTabs($this) &&', $content);
                    $content = str_replace('parent::uninstall()', 'parent::uninstall() && $this->uninstallTabs($this)', $content);
                    file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function generateLogPachage()
    {
        $dirs = [
            'src/Configuration',
            'src/Logger',
            'src/Provider',
        ];
        foreach ($dirs as $dir) {
            $this->copyDirFiles($dir);
        }
        $content = file_get_contents($this->base_dir . '/samples/log_sys.yml');
        $content = $this->replaceStandardStrings($content);
        $this->addService($content);
        return false;
    }

    /**
     * @param $dir
     * @return bool
     */
    public function copyDirFiles($dir)
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->base_dir . '/samples/' . $dir);

        foreach ($finder as $file) {
            $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
            $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
            $content = file_get_contents($this->module_dir . $path);
            $content = $this->replaceStandardStrings($content);
            file_put_contents($this->module_dir . $path, $content);
        }
        return true;
    }

    /**
     * @param $content
     * @return string|string[]
     */
    private function replaceStandardStrings($content)
    {
        $params = $this->getParams();
        $content = str_replace('Moduleclass', $params['upper']['module_name'], $content);
        $content = str_replace('moduleclass', $params['lower']['module_name'], $content);
        $content = str_replace('module_author', $params['upper']['company_name'], $content);
        $content = str_replace('company_name', $params['lower']['company_name'], $content);
        $content = str_replace('EvoGroup', $params['upper']['company_name'], $content);
        $content = str_replace('module_class', $params['lower']['module_name'], $content);
        $content = str_replace('MODULE_CLASS', strtoupper($params['lower']['module_name']), $content);
        $content = str_replace('MODULECLASS', strtoupper($params['lower']['module_name']), $content);
        $content = str_replace('prestashop.module.demodoctrine', $params['lower']['company_name'] . '.module.' . $params['lower']['module_name'], $content);
        $content = str_replace('PrestaShop\Module\DemoDoctrine', $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'], $content);
        $content = str_replace('ps_demodoctrine', $params['lower']['module_name'], $content);
        $content = str_replace('demodoctrine', $params['lower']['module_name'], $content);
        $content = str_replace('Demodoctrine', ucfirst($params['lower']['module_name']), $content);
        $content = str_replace('demo-doctrine', $params['lower']['module_name'], $content);
        return $content;
    }

    private function addService(string $content, $typo = 'services')
    {
        $fs = new Filesystem();
        $fs->appendToFile($this->module_dir . '/config/' . $typo . '.yml', $content);
        return true;
    }

    public function generateCommands()
    {

        foreach ($this->module_data['commands'] as $key => $commandData) {

            $content = file_get_contents($this->base_dir . '/samples/src/Command/SampleCommand.php');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('SampleCommand', $commandData['class'], $content);
            $callCommand = $commandData['call'] ?? $commandData['class'] . ':execute';
            $content = str_replace('command_call', $callCommand, $content);
            $commandDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Command';
            if (!is_dir($commandDir) && !@mkdir($commandDir, 0777, true) && !is_dir($commandDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $commandDir));
            }
            file_put_contents($commandDir . DIRECTORY_SEPARATOR . $commandData['class'] . '.php', $content);
            $ymlcontent = file_get_contents($this->base_dir . '/samples/command.yml');
            $ymlcontent = $this->replaceStandardStrings($ymlcontent);
            $ymlcontent = str_replace('command_name', strtolower($commandData['class']), $ymlcontent);
            $ymlcontent = str_replace('CommandName', $commandData['class'], $ymlcontent);
            $this->addService($ymlcontent);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function generateHelpers()
    {

        foreach ($this->module_data['helpers'] as $helper_name) {

            $content = file_get_contents($this->base_dir . '/samples/src/Helper/Helper.php');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('SampleHelper', $helper_name, $content);
            $helperDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Helper';
            if (!is_dir($helperDir) && !@mkdir($helperDir, 0777, true) && !is_dir($helperDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $helperDir));
            }
            file_put_contents($helperDir . DIRECTORY_SEPARATOR . $helper_name . '.php', $content);
            $ymlcontent = file_get_contents($this->base_dir . '/samples/helper.yml');
            $ymlcontent = $this->replaceStandardStrings($ymlcontent);
            $ymlcontent = str_replace('helper_name', strtolower($helper_name), $ymlcontent);
            $ymlcontent = str_replace('HelperName', $helper_name, $ymlcontent);
            $this->addService($ymlcontent);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function generateServices()
    {

        foreach ($this->module_data['services'] as $service_name) {

            $content = file_get_contents($this->base_dir . '/samples/src/Service/Service.php');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('ServiceName', $service_name, $content);
            $serviceDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service';
            if (!is_dir($serviceDir) && !@mkdir($serviceDir, 0777, true) && !is_dir($serviceDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $serviceDir));
            }
            file_put_contents($serviceDir . DIRECTORY_SEPARATOR . $service_name . '.php', $content);
            $ymlcontent = file_get_contents($this->base_dir . '/samples/service.yml');
            $ymlcontent = $this->replaceStandardStrings($ymlcontent);
            $ymlcontent = str_replace('service_name', strtolower($service_name), $ymlcontent);
            $ymlcontent = str_replace('ServiceName', $service_name, $ymlcontent);
            $this->addService($ymlcontent);
        }
        return true;
    }

    public function generateModels($withGeneralGetterAndSetter = false)
    {
        $modelDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Model';
        if (!is_dir($modelDir) && !@mkdir($modelDir, 0777, true) && !is_dir($modelDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $modelDir));
        }
        $firstModel = 1;
        $sql = '';
        $sql_uninstal = '';
        $sql_shop = '';
        $sql_lang = '';
        foreach ($this->module_data['models'] as $index => $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }

            $params = $this->getParams();
            $namespace = new PhpNamespace($params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Model');
            $namespace->addUse('ObjectModel');
            $class = $namespace->addClass($modelData['class']);
            $class->addExtend('ObjectModel');

            $fieldsData = $this->renderModel($modelData, $class, $withGeneralGetterAndSetter);
            $fields = $fieldsData['fields'];

            $fieldsDataSql = $fieldsData['sql'];
            $fieldsDataUninstall = $fieldsData['sql_uninstall'];
            $fieldsDataSql_shop = $fieldsData['sql_shop'];
            $fieldsDataSql_lang = $fieldsData['sql_lang'];
            $fieldsDataSql .= 'PRIMARY KEY  (`' . $modelData['primary'] . '`)' . PHP_EOL;
            $fieldsDataSql .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
            $fieldsDataSql = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataSql);
            $sql .= '$sql[]=' . $fieldsDataSql . "';" . PHP_EOL;
            $fieldsDataUninstall = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataUninstall);
            $sql_uninstal .= $fieldsDataUninstall . PHP_EOL;

            if (!empty($fieldsDataSql_shop)) {
                $fieldsDataSql_shop .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_shop = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_shop);
                $sql_shop .= '$sql[]=' . $fieldsDataSql_shop . "';" . PHP_EOL;
            }
            if (!empty($fieldsDataSql_lang)) {
                $fieldsDataSql_lang .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_lang`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_lang = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_lang);
                $sql_lang .= '$sql[]=' . $fieldsDataSql_lang . "';" . PHP_EOL;
            }

            $definition = [
                'table' => $modelData['table'],
                'primary' => $modelData['primary'] ?? 'id_' . $modelData['table']
            ];

            if (!empty($fieldsDataSql_lang)) {
                $definition['multilang'] = true;
            }
            $definition['fields'] = $fields;
            if (!empty($fieldsDataSql_shop)) {
                $method = $class->addMethod('__construct');
                $method->addParameter('id', null);
                $method->addParameter('id_lang', null);
                $method->addParameter('id_shop', null);
                $method->addParameter('translator', null);
                $body = '\Shop::addTableAssociation(self::$definition[\'table\'], [\'type\'=>\'shop\']);' . PHP_EOL;
                $body .= 'Parent::__construct($id, $id_lang, $id_shop, $translator);' . PHP_EOL;
                $method->setBody($body);
            }
            if ($withGeneralGetterAndSetter && !empty($this->module_data['source'][$modelData['class']])) {
                $modelObject = $this->module_data['source'][$modelData['class']];
                $relatedField = 'id_product';

                if ($modelObject == 'Category') {
                    $relatedField = 'id_category';
                }
                if ($modelObject == 'Customer') {
                    $relatedField = 'id_customer';
                }
                //getter
                $method = $class->addMethod('getExtra' . $modelObject . 'FieldsBy' . $modelObject . 'Id')->setStatic();
                $method->addParameter($relatedField, null);
                $getterContent = '$query = new \DbQuery();' . PHP_EOL;
                $getterContent .= '$query->from(\'' . $modelData['table'] . '\', \'ef\');' . PHP_EOL;
                $getterContent .= '$query->select(\'' . $modelData['primary'] . '\');' . PHP_EOL;


                $getterContent .= '$query->where(\'ef.' . $relatedField . '=\' . (int)$' . $relatedField . ');' . PHP_EOL;
                $getterContent .= '$result = \Db::getInstance()->getRow($query);' . PHP_EOL;
                $getterContent .= 'if (is_array($result) && !empty($result[\'' . $modelData['primary'] . '\'])) {' . PHP_EOL;
                $getterContent .= 'return new self((int)$result[\'' . $modelData['primary'] . '\']);' . PHP_EOL;
                $getterContent .= '}' . PHP_EOL;
                $getterContent .= 'return new self();' . PHP_EOL;
                $method->setBody($getterContent);
                //setter
                $method = $class->addMethod('SetExtra' . $modelObject . 'FieldsBy' . $modelObject . 'Id')->setStatic();
                $method->addParameter($relatedField, null)->setType('int');
                $method->addParameter('form_data', null)->setType('array');
                $setterContent = 'if($' . $relatedField . ' > 0){' . PHP_EOL;
                $setterContent .= '$extra' . $modelObject . 'Fields=self::getExtra' . $modelObject . 'FieldsBy' . $modelObject . 'Id($' . $relatedField . ');' . PHP_EOL;
                foreach ($modelData['fields'] as $field) {
                    if (!empty($field['is_auto_increment']) && $field['is_auto_increment'] == 1) {
                        continue;
                    }
                    if ($field['field_name'] == 'id_' . strtolower($modelObject)) {
                        $setterContent .= '$extra' . $modelObject . 'Fields->' . $field['field_name'] . '=$id_' . strtolower($modelObject) . ';' . PHP_EOL;
                    } else {
                        $setterContent .= '$extra' . $modelObject . 'Fields->' . $field['field_name'] . '=$form_data[\'' . $field['field_name'] . '\'];' . PHP_EOL;
                    }
                }
                $setterContent .= '$extra' . $modelObject . 'Fields->save();' . PHP_EOL;
                $setterContent .= '}' . PHP_EOL;
                $method->setBody($setterContent);
            }


            $definitionProp = $class->addProperty('definition');
            $definitionProp->setStatic()->setValue($definition);
            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);
            $code = str_replace(array("'/*", "*/'"), '', $code);
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', '<?php');
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', $code, FILE_APPEND);


        }
        $this->installSql[] = $sql . $sql_lang . $sql_shop;
        $this->unInstallSql[] = $sql_uninstal;

        return true;
    }

    /**
     * @param $modelData
     * @param $class
     * @return array
     */
    public function renderModel($modelData, $class, $withGeneralGetterAndSetter)
    {
        $fields = [];
        $fieldsDef = [];
        $sql = '';
        $sql_uninstall = '';
        $sql_shop = '';
        $sql_lang = '';
        $sql .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '` (' . PHP_EOL;
        $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '`;\';' . PHP_EOL;
        $firstShopIteration = 1;
        $firstLangIteration = 1;

        foreach ($modelData['fields'] as $index => $fieldData) {
            $separator = ',';
            if ($index === array_key_last($modelData['fields'])) {
                $separator = ',';
            }
            $property = $class->addProperty($fieldData['field_name']);

            if (!empty($fieldData['is_auto_increment']) && $fieldData['is_auto_increment'] == 1) {
                $sql .= '`' . $fieldData['field_name'] . '` int(11) NOT NULL AUTO_INCREMENT' . $separator . PHP_EOL;
                continue;
            }
            if ($fieldData['is_nullable'] === '1') {
                $property->setNullable();
                $nullableCondition = ' NULL';
            } else {
                $nullableCondition = ' NOT NULL';
            }
            $default_value = '';
            if ($fieldData['default_value'] != "" && !empty($fieldData['default_value'])) {
                $default_value = ' DEFAULT ' . $fieldData['default_value'];
            }
            $is_shop_fields = !empty($fieldData['is_shop']) && $fieldData['is_shop'] !== '' && $fieldData['is_shop'] !== null;
            if ($is_shop_fields) {
                $fieldsDef[$index]['shop'] = true;

                if ($firstShopIteration == 1) {
                    $sql_shop .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` (' . PHP_EOL;
                    $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop`;\';' . PHP_EOL;
                    $sql_shop .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_shop .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                if (!empty($fieldData['field_name']) && $fieldData['field_type']) {
                    if (($fieldData['field_type'] === 'INT' || $fieldData['field_type'] === 'UnsignedInt')) {
                        if ($fieldData['field_type'] === 'UnsignedInt') {
                            $sql_shop .= '`' . $fieldData['field_name'] . '` INT(11) UNSIGNED ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                        }
                        if ($fieldData['field_type'] === 'INT') {
                            $sql_shop .= '`' . $fieldData['field_name'] . '` INT(11) ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                        }
                    } elseif (($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT')) {
                        $size = $fieldsDef[$index]['size'] ?? 255;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 20.6);
                        }
                        $size = $size ?? 20.6;
                        $size = str_replace('.', ',', $size);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT')) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;

                    } elseif (($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 1);
                        }
                        $size = $size ?? 1;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME')) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '  ' . $separator . PHP_EOL;
                    } else {
                        $fieldData['field_length'] = str_replace('.', ',', $fieldData['field_length']);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')' . $nullableCondition . $default_value . ',' . PHP_EOL;
                    }
                }

                $firstShopIteration++;
            }

            $is_lang_fields = !empty($fieldData['is_lang']) && $fieldData['is_lang'] !== '' && $fieldData['is_lang'] !== null;
            $in_two_table = !$is_lang_fields;
            if ($is_lang_fields) {
                $fieldsDef[$index]['lang'] = true;

                if ($firstLangIteration == 1) {
                    $sql_lang .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` (' . PHP_EOL;
                    $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang`;\';' . PHP_EOL;
                    $sql_lang .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_lang` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                $type = $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')';
                if ($fieldData['field_type'] !== 'VARCHAR' && $fieldData['field_type'] !== 'TEXT' && $fieldData['field_type'] !== 'LONGTEXT') {
                    $type = 'VARCHAR(' . $fieldData['field_length'] . ')';
                }
                if ($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT') {
                    $type = $fieldData['field_type'];
                }
                $sql_lang .= '`' . $fieldData['field_name'] . '` ' . $type . $nullableCondition . $default_value . ',' . PHP_EOL;
                $firstLangIteration++;
            }

            if (($fieldData['field_type'] === 'INT' || $fieldData['field_type'] === 'UnsignedInt') && $in_two_table) {
                $property->addComment('@var int');
                $fieldsDef[$index]['type'] = '/*self::TYPE_INT*/';
                if ($fieldData['field_type'] === 'UnsignedInt') {
                    $fieldsDef[$index]['validate'] = 'isUnsignedInt';
                    $sql .= '`' . $fieldData['field_name'] . '` INT(11) UNSIGNED ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
                if ($fieldData['field_type'] === 'INT') {
                    $sql .= '`' . $fieldData['field_name'] . '` INT(11) ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
            }
            if (($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT')) {
                if ($is_lang_fields) {
                    $property->addComment('@var array of string');
                } else {
                    $property->addComment('@var string');
                }

                $fieldsDef[$index]['type'] = '/*self::TYPE_STRING*/';
                if ($fieldData['field_type'] === 'EMAIL') {
                    $fieldsDef[$index]['validate'] = 'isEmail';
                }
                if ($fieldData['field_type'] === 'HTML') {
                    $fieldsDef[$index]['type'] = '/*self::TYPE_HTML*/';
                    $fieldsDef[$index]['validate'] = 'isCleanHtml';
                }
                if ($fieldData['field_type'] === 'VARCHAR') {
//                    $fieldsDef[$index]['validate'] = 'isGenericName';
                }
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $fieldsDef[$index]['size'] = (int)$fieldData['field_length'];
                }
                $size = $fieldsDef[$index]['size'] ?? 255;
                if ($in_two_table) {
                    $sql .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }

            }
            if (($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT') && $in_two_table) {
                $property->addComment('@var float');
                $fieldsDef[$index]['type'] = '/*self::TYPE_FLOAT*/';
                $fieldsDef[$index]['validate'] = 'isPrice';
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 20.6);
                }
                $size = $size ?? 20.6;
                $size = str_replace('.', ',', $size);
                $sql .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if (($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT')) {
                $property->addComment('@var string');
                $fieldsDef[$index]['type'] = '/*self::TYPE_STRING*/';
                if ($in_two_table) {
                    $sql .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
            }
            if (($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN') && $in_two_table) {
                $property->addComment('@var bool');
                $fieldsDef[$index]['type'] = '/*self::TYPE_BOOL*/';
                $fieldsDef[$index]['validate'] = 'isBool';
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 1);
                }
                $size = $size ?? 1;
                $sql .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if (($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME') && $in_two_table) {
                $fieldsDef[$index]['type'] = '/*self::TYPE_DATE*/';
                $fieldsDef[$index]['validate'] = 'isDate';
                $fieldsDef[$index]['copy_post'] = false;
                $sql .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '  ' . $separator . PHP_EOL;
            }

            $fields[$fieldData['field_name']] = $fieldsDef[$index];
        }
        if (!empty($sql)) {
            $sql = "'" . $sql;
        }

        if (!empty($sql_shop)) {

            $sql_shop = substr($sql_shop, 0, -3);
            $sql_shop = "'" . $sql_shop;
        }
        if (!empty($sql_lang)) {
            $sql_lang = substr($sql_lang, 0, -3);
            $sql_lang = "'" . $sql_lang;
        }

        return ['fields' => $fields, 'sql' => $sql, 'sql_shop' => $sql_shop, 'sql_lang' => $sql_lang, 'sql_uninstall' => $sql_uninstall];
    }

    public function generateModelCustomFields()
    {
        if (empty($this->module_data['objectModels'])) {
            return false;
        }

        $this->module_data['hooksContents'] = [];

        $this->module_data['hooks'] = [];
        foreach ($this->module_data['objectModels'] as $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }
            if (empty($modelData['fields'])) {
                return false;
            }
            $this->module_data['hooks'][strtolower($modelData['class'])] = [];
            $extraModelsData['class'] = 'Extra' . $modelData['class'] . 'Fields';
            $extraModelsData['table'] = strtolower('Extra' . $modelData['class'] . 'Fields');
            $extraModelsData['primary'] = 'id_' . strtolower('Extra' . $modelData['class'] . 'Fields');
            $extraModelsData['fields'] = $modelData['fields'];
            $there_is_a_lang_field = false;
            $extraData = [];
            if (is_array($modelData['fields']) && !empty($modelData['fields'])) {
                foreach ($modelData['fields'] as $index => $fieldData) {

                    foreach ($fieldData as $key => $value) {
                        $key = $this->mapping[$key] ?? $key;
                        if ($key == 'is_lang' && !empty($value)) {
                            $there_is_a_lang_field = true;
                        }
                        $extraData[$index][$key] = $value;
                    }
                }
            }

            $primaryField = [
                "field_name" => $extraModelsData['primary'],
                "field_type" => "INT",
                "field_length" => "11",
                "is_nullable" => null,
                "is_lang" => null,
                "is_shop" => null,
                "default_value" => null,
                "is_auto_increment" => 1,
            ];
            $unsignedField = [
                "field_name" => 'id_' . strtolower($modelData['class']),
                "field_type" => "UnsignedInt",
                "field_length" => "11",
                "is_nullable" => null,
                "is_lang" => null,
                "is_shop" => null,
                "default_value" => null,
                "is_auto_increment" => null,
            ];

            $extraModelsData['fields'] = array_merge([$primaryField, $unsignedField], $extraData);
            $this->module_data['models'][$extraModelsData['class']] = $extraModelsData;
            $this->module_data['source'][$extraModelsData['class']] = $modelData['class'];

            if (!empty($modelData['listing'])) {
                $this->addToListing($modelData['class'], $modelData['listing'], $there_is_a_lang_field);
            }

            $this->setHookContent($modelData['class'], $modelData['fields'], $there_is_a_lang_field);
        }

        $this->generateModels(true);
        return true;
    }

    /**
     * @param array $array
     * @param $index
     * @param $element
     * @return array
     */
    public function arrayInsertAfter(&$array, $index, $element)
    {
        if (!array_key_exists($index, $array)) {
            throw new Exception("Index not found");
        }
        $tmpArray = array();
        $originalIndex = 0;
        foreach ($array as $key => $value) {
            if ($key === $index) {
                $tmpArray[] = $element;
                break;
            }
            $tmpArray[$key] = $value;
            $originalIndex++;
        }
        array_splice($array, 0, $originalIndex, $tmpArray);
        return $array;
    }

    private function setHookContent($classModel, $fields, $there_is_a_lang_field)
    {


        $this->module_data['hooks'][strtolower($classModel)] = !empty($this->module_data['hooks'][strtolower($classModel)]) ? $this->module_data['hooks'][strtolower($classModel)] : [];
        $this->module_data['use'] = !empty($this->module_data['use']) ? $this->module_data['use'] : [];
        $this->module_data['use'][strtolower($classModel)] = !empty($this->module_data['use'][strtolower($classModel)]) ? $this->module_data['use'][strtolower($classModel)] : [];
        if ($classModel == 'Product') {
            $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['actionObjectProductAddAfter', 'actionObjectProductUpdateAfter', 'displayAdminProductsMainStepLeftColumnBottom']);
            if ($there_is_a_lang_field) {
                $this->module_data['hooks'][strtolower($classModel)][] = 'displayAdminProductsMainStepLeftColumnMiddle';
            }
            $contentForTranslatableTemplates = "";
            $contentForNonTranslatableTemplates = "";
            foreach ($this->module_data['hooks'][strtolower($classModel)] as $hook) {
                $common_content = '$idProduct = (int)$params[\'id_product\'];' . PHP_EOL;
                $model = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . '\Module\Egaddextrafields\Model\ExtraProductFields');
                $this->module_data['use'][strtolower($classModel)][$model] = $model;
                $common_content .= '$extraProductFields = ExtraProductFields::getExtraProductFieldsByProductId($idProduct);' . PHP_EOL;
                $this->module_data['use'][strtolower($classModel)]['PrestaShop\PrestaShop\Adapter\SymfonyContainer'] = 'PrestaShop\PrestaShop\Adapter\SymfonyContainer';
                $this->module_data['use'][strtolower($classModel)]['Symfony\Component\Form\Extension\Core\Type\FormType'] = 'Symfony\Component\Form\Extension\Core\Type\FormType';

                if ($hook == 'displayAdminProductsMainStepLeftColumnBottom') {
                    $formData = '[' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if (!empty($item['is_column_lang'])) {
                            continue;
                        }
                        $formData .= "'" . $item['column_name'] . "' => /+extraProductFields-> " . $item['column_name'] . "," . PHP_EOL;
                        $contentForNonTranslatableTemplates .= "<h3>{{ form_label(form." . $item['column_name'] . ") }}</h3>" . PHP_EOL;
                        $contentForNonTranslatableTemplates .= "{{ form_widget(form." . $item['column_name'] . ") }}" . PHP_EOL;
                        $contentForNonTranslatableTemplates .= "{{ form_errors(form." . $item['column_name'] . ") }}" . PHP_EOL;
                        $contentForNonTranslatableTemplates .= "<br>" . PHP_EOL;
                    }
                    $formData .= ']' . PHP_EOL;

                    $extra_non_translatable_content = $common_content;
                    $extra_non_translatable_content .= '$form = SymfonyContainer::getInstance()->get(\'form.factory\')->createNamedBuilder(\'extra_non_translatable_fields_form\', FormType::class, ' . $formData . ')' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if ($item['column_type'] === 'TINYINT') {
                            $this->module_data['use'][strtolower($classModel)]['PrestaShopBundle\Form\Admin\Type\SwitchType'] = 'PrestaShopBundle\Form\Admin\Type\SwitchType';
                            $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', SwitchType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'choices' => [
                            'OFF' => false,
                            'ON' => true,
                            ],
                            ])" . PHP_EOL;
                        } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                            $this->module_data['use'][strtolower($classModel)]['PrestaShopBundle\Form\Admin\Type\DatePickerType'] = 'PrestaShopBundle\Form\Admin\Type\DatePickerType';
                            $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', DatePickerType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'required' => false,
                            ])" . PHP_EOL;
                        } else {
                            $this->module_data['use'][strtolower($classModel)]['Symfony\Component\Validator\Constraints as Assert'] = 'Symfony\Component\Validator\Constraints as Assert';
                            $this->module_data['use'][strtolower($classModel)]['Symfony\Component\Form\Extension\Core\Type\TextType'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';
                            if (!empty($item['column_length'])) {
                                $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', TextType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'required' => false,
                            'attr' => ['placeholder' => /+this->l('" . $item['column_name'] . "')],
                            'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Length(['min' => 1, 'max' => " . $item['column_length'] . "]),
                            ]
                            ])" . PHP_EOL;
                            } else {
                                $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', TextType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'required' => false,
                            'attr' => ['placeholder' => /+this->l('" . $item['column_name'] . "')],
                            'constraints' => [
                            new Assert\NotBlank(),
                            ]
                            ])" . PHP_EOL;
                            }

                        }
                    }
                    $extra_non_translatable_content .= '->getForm();' . PHP_EOL;
                    $extra_non_translatable_content .= 'return SymfonyContainer::getInstance()->get(\'twig\')->render(' . PHP_EOL;
                    $extra_non_translatable_content .= '    \'@PrestaShop/Products/extra_no_translatable_fields.html.twig\', [\'form\' => $form->createView()]' . PHP_EOL;
                    $extra_non_translatable_content .= ');' . PHP_EOL;
                    $this->module_data['hooksContents']['displayAdminProductsMainStepLeftColumnBottom'] = $extra_non_translatable_content;
                }
                if ($hook == 'displayAdminProductsMainStepLeftColumnMiddle') {
                    $formData = '[' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if (empty($item['is_column_lang'])) {
                            continue;
                        }
                        $formData .= "'" . $item['column_name'] . "' => /+extraProductFields-> " . $item['column_name'] . "," . PHP_EOL;
                        $contentForTranslatableTemplates .= "<h3>{{ form_label(form." . $item['column_name'] . ") }}</h3>" . PHP_EOL;
                        $contentForTranslatableTemplates .= "{{ form_widget(form." . $item['column_name'] . ") }}" . PHP_EOL;
                        $contentForTranslatableTemplates .= "{{ form_errors(form." . $item['column_name'] . ") }}" . PHP_EOL;
                        $contentForTranslatableTemplates .= "<br>" . PHP_EOL;
                    }
                    $formData .= ']' . PHP_EOL;
                    $extra_translatable_content = $common_content;
                    $extra_translatable_content .= '$form = SymfonyContainer::getInstance()->get(\'form.factory\')->createNamedBuilder(\'extra_translatable_fields_form\', FormType::class, ' . $formData . ')' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if ($item['column_type'] === 'VARCHAR' || $item['column_type'] === 'HTML') {
                            $this->module_data['use'][strtolower($classModel)]['PrestaShopBundle\Form\Admin\Type\TranslateType'] = 'PrestaShopBundle\Form\Admin\Type\TranslateType';
                            $this->module_data['use'][strtolower($classModel)]['PrestaShopBundle\Form\Admin\Type\FormattedTextareaType'] = 'PrestaShopBundle\Form\Admin\Type\FormattedTextareaType';
                            if ($item['column_type'] === 'HTML') {
                                $this->module_data['use'][strtolower($classModel)]['PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml'] = 'PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml';
                                $extra_translatable_content .= '->add(' . PHP_EOL;
                                $extra_translatable_content .= '\'' . $item['column_name'] . '\',' . PHP_EOL;
                                $extra_translatable_content .= 'TranslateType::class,' . PHP_EOL;
                                $extra_translatable_content .= ' [' . PHP_EOL;
                                $extra_translatable_content .= '       \'type\' => FormattedTextareaType::class,' . PHP_EOL;
                                $extra_translatable_content .= '       \'options\' => [' . PHP_EOL;
                                $extra_translatable_content .= '          \'required\' => false,' . PHP_EOL;
                                $extra_translatable_content .= '          \'constraints\' => [' . PHP_EOL;
                                $extra_translatable_content .= '             new CleanHtml([\'message\' => $this->trans(\'%s is invalid\', [], \'Admin.Notifications.Error\')])' . PHP_EOL;
                                $extra_translatable_content .= '        ]' . PHP_EOL;
                                $extra_translatable_content .= '    ],' . PHP_EOL;
                                $extra_translatable_content .= '    \'locales\' =>SymfonyContainer::getInstance()->get(\'prestashop.adapter.legacy.context\')->getLanguages(),' . PHP_EOL;
                                $extra_translatable_content .= '    \'hideTabs\' => false' . PHP_EOL;
                                $extra_translatable_content .= ']' . PHP_EOL;
                                $extra_translatable_content .= ')' . PHP_EOL;
                            }
                            if ($item['column_type'] === 'VARCHAR') {
                                $this->module_data['use'][strtolower($classModel)]['PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml'] = 'PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml';
                                $this->module_data['use'][strtolower($classModel)]['Symfony\Component\Validator\Constraints as Assert'] = 'Symfony\Component\Validator\Constraints as Assert';
                                $extra_translatable_content .= '->add(' . PHP_EOL;
                                $extra_translatable_content .= '\'' . $item['column_name'] . '\',' . PHP_EOL;
                                $extra_translatable_content .= 'TranslateType::class,' . PHP_EOL;
                                $extra_translatable_content .= ' [' . PHP_EOL;
                                $extra_translatable_content .= '       \'type\' => FormattedTextareaType::class,' . PHP_EOL;
                                $extra_translatable_content .= '       \'options\' => [' . PHP_EOL;
                                $extra_translatable_content .= '          \'required\' => false,' . PHP_EOL;
                                $extra_translatable_content .= '          \'constraints\' => [' . PHP_EOL;
                                $extra_translatable_content .= '             new Assert\NotBlank(),' . PHP_EOL;
                                $extra_translatable_content .= '             new Assert\Length([\'min\' => 1, \'max\' => ' . $item['column_length'] . ']),' . PHP_EOL;
                                $extra_translatable_content .= '        ]' . PHP_EOL;
                                $extra_translatable_content .= '    ],' . PHP_EOL;
                                $extra_translatable_content .= '    \'locales\' =>SymfonyContainer::getInstance()->get(\'prestashop.adapter.legacy.context\')->getLanguages(),' . PHP_EOL;
                                $extra_translatable_content .= '    \'hideTabs\' => false' . PHP_EOL;
                                $extra_translatable_content .= ']' . PHP_EOL;
                                $extra_translatable_content .= ')' . PHP_EOL;
                            }

                        }
                    }
                    $extra_translatable_content .= '->getForm();' . PHP_EOL;
                    $extra_translatable_content .= 'return SymfonyContainer::getInstance()->get(\'twig\')->render(' . PHP_EOL;
                    $extra_translatable_content .= '    \'@PrestaShop/Products/extra_translatable_fields.html.twig\', [\'form\' => $form->createView()]' . PHP_EOL;
                    $extra_translatable_content .= ');' . PHP_EOL;
                    $this->module_data['hooksContents']['displayAdminProductsMainStepLeftColumnMiddle'] = $extra_translatable_content;
                }
                if (in_array($hook, ['actionObjectProductAddAfter', 'actionObjectProductUpdateAfter'])) {
                    $this->module_data['hooksContents'][$hook] = "/+this->updateExtraProductField((int)/+params['object']->id);";
                }
            }
            $codeForUpdateProduct = '$formData = [\'id_product\' => $productId];' . PHP_EOL;

            $codeForUpdateProduct .= 'if (Tools::getIsset(\'extra_non_translatable_fields_form\') && !empty($extra_non_translatable_form = Tools::getValue(\'extra_non_translatable_fields_form\'))) {' . PHP_EOL;
            $codeForUpdateProduct .= '   $formData=array_merge($formData, $extra_non_translatable_form);' . PHP_EOL;
            $codeForUpdateProduct .= '    }' . PHP_EOL;
            $codeForUpdateProduct .= 'if (Tools::getIsset(\'extra_translatable_fields_form\') && !empty($extra_translatable_form = Tools::getValue(\'extra_translatable_fields_form\'))) {' . PHP_EOL;
            $codeForUpdateProduct .= '   $formData=array_merge($formData, $extra_translatable_form);' . PHP_EOL;
            $codeForUpdateProduct .= '    }' . PHP_EOL;
            $codeForUpdateProduct .= 'ExtraProductFields::SetExtraProductFieldsByProductId($productId, $formData);' . PHP_EOL;

            $this->module_data['query'][$classModel] = $codeForUpdateProduct;

            //templates
            $twigPath = $this->module_dir . '/views/PrestaShop/Products';
            if (!is_dir($twigPath) && !@mkdir($twigPath, 0777, true) && !is_dir($twigPath)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $twigPath));
            }
            file_put_contents($this->module_dir . '/views/PrestaShop/Products/extra_no_translatable_fields.html.twig', $contentForNonTranslatableTemplates);

            file_put_contents($this->module_dir . '/views/PrestaShop/Products/extra_translatable_fields.html.twig', $contentForTranslatableTemplates);
        }
        if ($classModel === 'Category' || $classModel === 'Customer') {
            $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['action' . $classModel . 'FormBuilderModifier', 'actionAfterCreate' . $classModel . 'FormHandler', 'actionAfterUpdate' . $classModel . 'FormHandler']);
            $gridDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Grid';
            if (!is_dir($gridDir) && !@mkdir($gridDir, 0777, true) && !is_dir($gridDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $gridDir));
            }

            $use = [];
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid');
            $namespace->addUse('Module');
            $namespace->addUse('Symfony\Component\Form\FormBuilderInterface');
            $namespace->addUse($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Model' . '\\' . 'Extra' . $classModel . 'Fields');

            $class = $namespace->addClass($classModel . 'FormBuilderModifier');
            $class->addProperty('module')
                ->setProtected();
            $class->addProperty('id' . $classModel)
                ->setProtected();
            $class->addProperty('formBuilder')
                ->setProtected();
            $class->addProperty('formData')
                ->setProtected();
            $construct = $class->addMethod('__construct');
            $construct->addParameter('module')->setType('Module');
            $construct->addParameter('id' . $classModel)->setType('Int');
            $construct->addParameter('formBuilder')->setType('FormBuilderInterface');
            $construct->addParameter('formData')->setType('Array');
            $constructBody = '$this->module=$module;' . PHP_EOL;
            $constructBody .= '$this->id' . $classModel . '=$id' . $classModel . ';' . PHP_EOL;
            $constructBody .= '$this->formBuilder=$formBuilder;' . PHP_EOL;
            $constructBody .= '$this->formData=$formData;' . PHP_EOL;
            $construct->setBody($constructBody);
            $addFields = $class->addMethod('addFields');
            $i = 0;

            $addFieldsBody = '$this->formBuilder' . PHP_EOL;
            foreach ($fields as $index => $item) {
                if ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                    $addFieldsBody .= '->add(' . PHP_EOL;
                    $addFieldsBody .= "'" . $item['column_name'] . "'," . PHP_EOL;
                    $namespace->addUse('PrestaShopBundle\Form\Admin\Type\SwitchType');
                    $addFieldsBody .= "SwitchType::class," . PHP_EOL;
                    $addFieldsBody .= "[" . PHP_EOL;
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . $item['column_name'] . '\'),' . PHP_EOL;
                    $addFieldsBody .= '\'required\'=>false,' . PHP_EOL;
                    $addFieldsBody .= "]" . PHP_EOL;
                    $addFieldsBody .= ')' . PHP_EOL;
                } elseif (!empty($item['is_column_lang'])) {
                    $addFieldsBody .= '->add(' . PHP_EOL;
                    $addFieldsBody .= "'" . $item['column_name'] . "'," . PHP_EOL;
                    $namespace->addUse('PrestaShopBundle\Form\Admin\Type\TranslatableType');
                    $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\TextareaType');
                    $namespace->addUse('Symfony\Component\Validator\Constraints\Regex');
                    $namespace->addUse('PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage');
                    $addFieldsBody .= "TranslatableType::class," . PHP_EOL;
                    $addFieldsBody .= "[" . PHP_EOL;
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . $item['column_name'] . '\'),' . PHP_EOL;
                    $addFieldsBody .= '\'type\'=>TextareaType::class,' . PHP_EOL;
                    $addFieldsBody .= '\'required\'=>false,' . PHP_EOL;
                    $addFieldsBody .= '\'constraints\'=>[new DefaultLanguage()],' . PHP_EOL;
                    $addFieldsBody .= '\'options\'=>[' . PHP_EOL;
                    $addFieldsBody .= '\'constraints\'=>[' . PHP_EOL;
                    $addFieldsBody .= 'new Regex([' . PHP_EOL;
                    $addFieldsBody .= '\'pattern\'=>\'/^[^<>;=#{}]*$/u\',' . PHP_EOL;
                    $addFieldsBody .= '\'message\'=>$this->module->l(\'%s id invalid\')' . PHP_EOL;
                    $addFieldsBody .= ']),' . PHP_EOL;
                    $addFieldsBody .= '],' . PHP_EOL;
                    $addFieldsBody .= '],' . PHP_EOL;
                    $addFieldsBody .= "]" . PHP_EOL;
                    $addFieldsBody .= ')' . PHP_EOL;
                } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                    $namespace->addUse('PrestaShopBundle\Form\Admin\Type\DatePickerType');
                    $addFieldsBody .= '->add(' . PHP_EOL;
                    $addFieldsBody .= "'" . $item['column_name'] . "'," . PHP_EOL;
                    $addFieldsBody .= "DatePickerType::class," . PHP_EOL;
                    $addFieldsBody .= "[" . PHP_EOL;
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . $item['column_name'] . '\'),' . PHP_EOL;
                    $addFieldsBody .= '\'required\'=>false,' . PHP_EOL;
                    $addFieldsBody .= "]" . PHP_EOL;
                    $addFieldsBody .= ')' . PHP_EOL;
                } else {
                    $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\TextType');
                    $addFieldsBody .= '->add(' . PHP_EOL;
                    $addFieldsBody .= "'" . $item['column_name'] . "'," . PHP_EOL;
                    $addFieldsBody .= "TextType::class," . PHP_EOL;
                    $addFieldsBody .= "[" . PHP_EOL;
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . $item['column_name'] . '\'),' . PHP_EOL;
                    $addFieldsBody .= '\'required\'=>false,' . PHP_EOL;
                    $addFieldsBody .= "]" . PHP_EOL;
                    $addFieldsBody .= ')' . PHP_EOL;
                }

            }

            $addFieldsBody .= ';' . PHP_EOL;
            $addFieldsBody .= '$extra' . $classModel . '=Extra' . $classModel . 'Fields::getExtra' . $classModel . 'FieldsBy' . $classModel . 'Id($this->id' . $classModel . ');' . PHP_EOL;
            $use[str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . '\Module\Egaddextrafields\Model\Extra' . $classModel . 'Fields')] = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . '\Module\Egaddextrafields\Model\Extra' . $classModel . 'Fields');

            foreach ($fields as $index => $item) {

                $addFieldsBody .= '$this->formData[\'' . $item['column_name'] . '\']=$extra' . $classModel . '->' . $item['column_name'] . ';' . PHP_EOL;


            }
            $addFieldsBody .= '$this->formBuilder->setData($this->formData);' . PHP_EOL;

            $addFields->setBody($addFieldsBody);
            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);


            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', $code, FILE_APPEND);
            $use[$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier';

            $formBuilderContent = '$' . strtolower($classModel) . 'FormBuilderModifier= new ' . $classModel . 'FormBuilderModifier(' . PHP_EOL;
            $classModelFormBuilderModifierNameSpace = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . "\Module\Egaddextrafields\Grid" . '\\' . $classModel . "FormBuilderModifier");

            $use[$classModelFormBuilderModifierNameSpace] = $classModelFormBuilderModifierNameSpace;
            $formBuilderContent .= '$this,' . PHP_EOL;
            $formBuilderContent .= '(int)$params[\'id\'],' . PHP_EOL;
            $formBuilderContent .= '$params[\'form_builder\'],' . PHP_EOL;
            $formBuilderContent .= '$params[\'data\']' . PHP_EOL;
            $formBuilderContent .= ');' . PHP_EOL;
            $formBuilderContent .= '$' . strtolower($classModel) . 'FormBuilderModifier->addFields();' . PHP_EOL;
            $this->module_data['hooksContents']['action' . $classModel . 'FormBuilderModifier'] = $formBuilderContent;
            $formHandlerContent = 'Extra' . $classModel . 'Fields::setExtra' . $classModel . 'FieldsBy' . $classModel . 'Id(' . PHP_EOL;
            $formHandlerContent .= '(int)$params[\'id\'],' . PHP_EOL;
            $formHandlerContent .= '$params[\'form_data\']' . PHP_EOL;
            $formHandlerContent .= ');' . PHP_EOL;
            $this->module_data['hooksContents']['actionAfterCreate' . $classModel . 'FormHandler'] = $formHandlerContent;
            $this->module_data['hooksContents']['actionAfterUpdate' . $classModel . 'FormHandler'] = $formHandlerContent;

            $this->module_data['use'][strtolower($classModel)] = array_merge($this->module_data['use'][strtolower($classModel)], $use);

        }

        return true;
    }

    private function addToListing($classModel, $listingFields, $there_is_a_lang_field)
    {
        if ($classModel === 'Product') {
            $fileSystem = new Filesystem();
            $finder = new Finder();
            $dirs = [
                'templates',
                'PrestaShop'
            ];
            foreach ($dirs as $dir) {
                $finder->files()->in($this->base_dir . '/samples/customFieldViews/' . $dir);
                foreach ($finder as $file) {
                    $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'customFieldViews', 'views', $file->getRealPath());
                    $fileSystem->copy($file->getRealPath(), $this->module_dir . DIRECTORY_SEPARATOR . $path);
                }
            }

            $this->module_data['hooks']['product'] = ['displayAdminCatalogTwigProductHeader', 'displayAdminCatalogTwigProductFilter', 'displayAdminCatalogTwigListingProductFields', 'actionAdminProductsListingFieldsModifier'];

            $header = "return /+this->display(__FILE__,'views/templates/hook/displayAdminCatalogTwigProductHeader.tpl');" . PHP_EOL;
            $this->module_data['hooksContents']['displayAdminCatalogTwigProductHeader'] = $header;
            $filter = "";
            $filter .= "/+vars=[";
            foreach ($listingFields as $field) {
                $filter .= "'" . $field['column_name'] . "'=>Tools::getValue('filter_column_name_" . $field['column_name'] . "',null)," . PHP_EOL;
            }
            $filter .= "];";
            $filter .= "/+this->context->smarty->assign(/+vars);" . PHP_EOL;
            $filter .= "return /+this->display(__FILE__,'views/templates/hook/displayAdminCatalogTwigProductFilter.tpl');";
            $this->module_data['hooksContents']['displayAdminCatalogTwigProductFilter'] = $filter;
            $listing = "/+id_product= /+params['product']['id_product'];" . PHP_EOL;
            $listing .= "/+extraProductFields= ExtraProductFields::getExtraProductFieldsByProductId(/+id_product);" . PHP_EOL;
            $listing .= "/+vars=[";
            foreach ($listingFields as $field) {
                if (!empty($field['is_column_lang'])) {
                    $listing .= "'" . $field['column_name'] . "'=>/+extraProductFields->" . $field['column_name'] . "[Configuration::get('PS_LANG_DEFAULT')]," . PHP_EOL;
                } elseif ($field['column_type'] === 'TINYINT' || $field['column_type'] === 'BOOLEAN') {
                    $this->module_data['presenter']['product'] = ['presentBooleanResponse' => $this->booleanPresenter()];
                    $listing .= "'" . $field['column_name'] . "'=>/+this->presentBooleanResponse(/+extraProductFields->" . $field['column_name'] . ")," . PHP_EOL;
                } else {
                    $listing .= "'" . $field['column_name'] . "'=>/+extraProductFields->" . $field['column_name'] . "," . PHP_EOL;
                }
            }
            $listing .= "];";
            $listing .= "/+this->context->smarty->assign(/+vars);" . PHP_EOL;
            $listing .= "return /+this->display(__FILE__,'views/templates/hook/displayAdminCatalogTwigListingProductFields.tpl');";
            $this->module_data['hooksContents']['displayAdminCatalogTwigListingProductFields'] = $listing;
            $sql = "";
            $customHead = "";
            $customFilter = "";
            $custom_value = "";
            $params = $this->getParams();
            $id_default_lang = "Configuration::get('PS_LANG_DEFAULT')";
            $transCount = 0;
            $count = 0;
            $where = "";
            $lang = false;
            foreach ($listingFields as $field) {
                $customHead .= "<th>{l s='" . $field['column_name'] . "' mod='" . $params['lower']['module_name'] . "'}</th>" . PHP_EOL;

                if ($field['column_type'] == 'TINYINT') {
                    $customFilter .= "<th id=\"product_filter_column_" . $field['column_name'] . "\" class=\"text-center\">
                    <div class=\"form-select\">
                        <select class=\"custom-select\" name=\"filter_column_name_" . $field['column_name'] . "\">
                            <option value=\"\"></option>
                            <option value=\"1\" {if /+" . $field['column_name'] . "==1} selected {/if}>Oui</option>
                            <option value=\"0\" {if /+" . $field['column_name'] . "==='0'} selected {/if}>Non</option>
                        </select>
                    </div>
                </th>" . PHP_EOL;
                } else {
                    $customFilter .= "<th><input type=\"text\" class=\"form-control\"  placeholder=\"{l s='Search " . $field['column_name'] . "' mod='egaddcustomfieldtoproduct'}\" name=\"filter_column_name_" . $field['column_name'] . "\" value=\"{/+" . $field['column_name'] . "}\"></th>" . PHP_EOL;
                }

                $custom_value .= "<td>{/+" . $field['column_name'] . "}</td>" . PHP_EOL;

                if (!empty($field['is_column_lang'])) {
                    $sql .= "/+params['sql_select']['" . $field['column_name'] . "'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extra_lang'," . PHP_EOL;
                    $sql .= "'field' => '" . $field['column_name'] . "'," . PHP_EOL;
                    $sql .= "'filtering' => \PrestaShop\PrestaShop\Adapter\Admin\AbstractAdminQueryBuilder::FILTERING_LIKE_BOTH" . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                    $where .= 'if (Tools::getIsset(\'filter_column_name_' . $field['column_name'] . '\') && !empty(Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\'))) {' . PHP_EOL;
                    $where .= '$params[\'sql_where\'][] .= "extra_lang.' . $field['column_name'] . ' like \'%" . trim(Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\'))."%\'";' . PHP_EOL;
                    $where .= '}' . PHP_EOL;
                    $transCount++;

                } else {
                    $sql .= "/+params['sql_select']['" . $field['column_name'] . "'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extra'," . PHP_EOL;
                    $sql .= "'field' => '" . $field['column_name'] . "'," . PHP_EOL;
                    $sql .= "'filtering' => \PrestaShop\PrestaShop\Adapter\Admin\AbstractAdminQueryBuilder::FILTERING_LIKE_BOTH" . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                    if ($field['column_type'] == 'TINYINT') {
                        $where .= 'if (Tools::getIsset(\'filter_column_name_' . $field['column_name'] . '\') && (!empty(Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\')) || Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\') ==="0")) {' . PHP_EOL;
                    } else {
                        $where .= 'if (Tools::getIsset(\'filter_column_name_' . $field['column_name'] . '\') && !empty(Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\'))) {' . PHP_EOL;
                    }
                    $where .= '$params[\'sql_where\'][] .= "extra.' . $field['column_name'] . ' =" . trim(Tools::getValue(\'filter_column_name_' . $field['column_name'] . '\'));' . PHP_EOL;
                    $where .= '}' . PHP_EOL;
                    $count++;
                }
                if ($count == 1) {
                    $sql .= "/+params['sql_table']['extra'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extraproductfields'," . PHP_EOL;
                    $sql .= "'join' => 'LEFT JOIN'," . PHP_EOL;
                    $sql .= "'on' => 'p.`id_product` = extra.`id_product`'," . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                }
                if ($transCount == 1) {
                    $sql .= "/+params['sql_table']['extra_lang'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extraproductfields_lang'," . PHP_EOL;
                    $sql .= "'join' => 'LEFT JOIN'," . PHP_EOL;
                    $sql .= "'on' => 'extra_lang.`id_extraproductfields` = extra.`id_extraproductfields`'," . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                }

                $sql .= $where . PHP_EOL;
            }
            $this->module_data['hooksContents']['actionAdminProductsListingFieldsModifier'] = $sql;
            $content = file_get_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductHeader.tpl');
            $content = str_replace('custom_head', $customHead, $content);
            if ($content == '') {
                $content = $customHead;
            }
            $content = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $content);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductHeader.tpl', $content);
            $content = file_get_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductFilter.tpl');
            $content = str_replace('custom_filter', $customFilter, $content);
            if ($content == '') {
                $content = $customFilter;
            }
            $content = str_replace('egaddcustomfieldtoproduct', $params['lower']['module_name'], $content);
            $content = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $content);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductFilter.tpl', $content);
            $content = file_get_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigListingProductFields.tpl');
            if ($content == '') {
                $content = $custom_value;
            }
            $content = str_replace('custom_value', $custom_value, $content);
            $content = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $content);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigListingProductFields.tpl', $content);

        }
        if ($classModel === 'Category' || $classModel === 'Customer') {
            $gridDir = $this->module_dir . '/src/Grid';
            if (!is_dir($gridDir) && !@mkdir($gridDir, 0777, true) && !is_dir($gridDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $gridDir));
            }
            $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['action' . $classModel . 'GridDefinitionModifier', 'action' . $classModel . 'GridQueryBuilderModifier']);
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid');
            $namespace->addUse('Module');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Grid\Filter\Filter');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection');

            $class = $namespace->addClass($classModel . 'GridDefinitionModifier');
            $class->addProperty('module')
                ->setProtected();
            $class->addProperty('gridDefinition')
                ->setProtected();
            $construct = $class->addMethod('__construct');
            $construct->addParameter('module')->setType('Module');
            $construct->addParameter('gridDefinition')->setType('GridDefinitionInterface');
            $constructBody = '$this->module=$module;' . PHP_EOL;
            $constructBody .= '$this->gridDefinition=$gridDefinition;' . PHP_EOL;
            $construct->setBody($constructBody);
            $addColumns = $class->addMethod('addColumns');
            $addColumnsBody = '/** @var ColumnCollection */' . PHP_EOL;
            $addColumnsBody .= '$columns=$this->gridDefinition->getColumns();' . PHP_EOL;
            $i = 1;
            foreach ($listingFields as $index => $item) {
                if ($i == 1) {
                    $addColumnsBody .= '$columns->AddAfter(' . PHP_EOL;
                    $addColumnsBody .= '\'active\',' . PHP_EOL;
                    $addColumnsBody .= '(new DataColumn(\'' . $item['column_name'] . '\'))' . PHP_EOL;
                    $addColumnsBody .= '->setName($this->module->l(\'' . $item['column_name'] . '\'))' . PHP_EOL;
                    $addColumnsBody .= '->setOptions([\'field\'=>\'' . $item['column_name'] . '\'])' . PHP_EOL;
                    $addColumnsBody .= ');' . PHP_EOL;
                } else {
                    $addColumnsBody .= '$columns->AddAfter(' . PHP_EOL;
                    $addColumnsBody .= '\'' . $this->lastField . '\',' . PHP_EOL;
                    $addColumnsBody .= '(new DataColumn(\'' . $item['column_name'] . '\'))' . PHP_EOL;
                    $addColumnsBody .= '->setName($this->module->l(\'' . $item['column_name'] . '\'))' . PHP_EOL;
                    $addColumnsBody .= '->setOptions([\'field\'=>\'' . $item['column_name'] . '\'])' . PHP_EOL;
                    $addColumnsBody .= ');' . PHP_EOL;

                }
                $this->lastField = $item['column_name'];
                $i++;
            }
            $addColumns->setBody($addColumnsBody);
            $addFilters = $class->addMethod('addFilters');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection');
            $addFiltersBody = '/** @var FilterCollection $filters*/' . PHP_EOL;
            $addFiltersBody .= '$filters=$this->gridDefinition->getFilters();' . PHP_EOL;

            foreach ($listingFields as $index => $item) {
                if ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                    $namespace->addUse('PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType');
                    $addFiltersBody .= '$filters->add(' . PHP_EOL;
                    $addFiltersBody .= '(new Filter(\'' . $item['column_name'] . '\', YesAndNoChoiceType::class))' . PHP_EOL;
                    $addFiltersBody .= '->setTypeOptions([\'required\'=>false])' . PHP_EOL;
                    $addFiltersBody .= '->setAssociatedColumn(\'' . $item['column_name'] . '\')' . PHP_EOL;
                    $addFiltersBody .= ');' . PHP_EOL;
                } else {
                    $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\TextType');
                    $addFiltersBody .= '$filters->add(' . PHP_EOL;
                    $addFiltersBody .= '(new Filter(\'' . $item['column_name'] . '\', TextType::class))' . PHP_EOL;
                    $addFiltersBody .= '->setTypeOptions([\'required\'=>false])' . PHP_EOL;
                    $addFiltersBody .= '->setAssociatedColumn(\'' . $item['column_name'] . '\')' . PHP_EOL;
                    $addFiltersBody .= ');' . PHP_EOL;
                }
            }
            $addFilters->setBody($addFiltersBody);
            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);

            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridDefinitionModifier.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridDefinitionModifier.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridDefinitionModifier.php', $code, FILE_APPEND);
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid');
            $namespace->addUse('Module');
            $namespace->addUse('Doctrine\DBAL\Query\QueryBuilder');
            $class = $namespace->addClass($classModel . 'GridQueryBuilderModifier');
            $class->addProperty('module')
                ->setProtected();
            $class->addProperty('filters')
                ->setProtected();
            $class->addProperty('idLang')
                ->setProtected();
            $construct = $class->addMethod('__construct');
            $construct->addParameter('module')->setType('Module');
            $construct->addParameter('filters')->setType('Array');
            $construct->addParameter('idLang')->setType('Int');
            $constructBody = '$this->module=$module;' . PHP_EOL;
            $constructBody .= '$this->filters=$filters;' . PHP_EOL;
            $constructBody .= '$this->idLang=$idLang;' . PHP_EOL;
            $construct->setBody($constructBody);
            $updateQueryBuilder = $class->addMethod('updateQueryBuilder');
            $updateQueryBuilder->addParameter('queryBuilder')->setType('QueryBuilder');
            $updateQueryBuilderBody = '$queryBuilder->leftJoin(' . PHP_EOL;
            $updateQueryBuilderBody .= '\'c\',' . PHP_EOL;
            $updateQueryBuilderBody .= '_DB_PREFIX_.\'extra' . strtolower($classModel) . 'fields\',' . PHP_EOL;
            $updateQueryBuilderBody .= '\'extra_' . strtolower($classModel) . '\',' . PHP_EOL;
            $updateQueryBuilderBody .= '\'extra_' . strtolower($classModel) . '.id_' . strtolower($classModel) . '=c.id_' . strtolower($classModel) . '\'' . PHP_EOL;
            $updateQueryBuilderBody .= ');' . PHP_EOL;
            if ($there_is_a_lang_field) {
                $updateQueryBuilderBody .= '$queryBuilder->leftJoin(' . PHP_EOL;
                $updateQueryBuilderBody .= '\'extra_' . strtolower($classModel) . '\',' . PHP_EOL;
                $updateQueryBuilderBody .= '_DB_PREFIX_.\'extra' . strtolower($classModel) . 'fields_lang\',' . PHP_EOL;
                $updateQueryBuilderBody .= '\'extral_' . strtolower($classModel) . '\',' . PHP_EOL;
                $updateQueryBuilderBody .= '\'extra_' . strtolower($classModel) . '.id_extra' . strtolower($classModel) . 'fields=extral_' . strtolower($classModel) . '.id_extra' . strtolower($classModel) . 'fields AND extral_' . strtolower($classModel) . '.id_lang=:context_lang_id\'' . PHP_EOL;
                $updateQueryBuilderBody .= ');' . PHP_EOL;
                $updateQueryBuilderBody .= '$queryBuilder->setParameter(\'context_lang_id\', $this->idLang);' . PHP_EOL;
            }

            foreach ($listingFields as $index => $item) {
                if (!empty($item['is_column_lang'])) {
                    $updateQueryBuilderBody .= '$queryBuilder->addSelect(\'extral_' . strtolower($classModel) . '.' . $item['column_name'] . '\');';
                } else {
                    $updateQueryBuilderBody .= '$queryBuilder->addSelect(\'extra_' . strtolower($classModel) . '.' . $item['column_name'] . '\');';
                }

                $updateQueryBuilderBody .= 'if(isset($this->filters[\'' . $item['column_name'] . '\'])){' . PHP_EOL;
                if (!empty($item['is_column_lang'])) {
                    $updateQueryBuilderBody .= '$queryBuilder->where(\'extral_' . strtolower($classModel) . '.' . $item['column_name'] . ' LIKE :p_estral_' . strtolower($classModel) . '_' . $item['column_name'] . '\');' . PHP_EOL;
                    $updateQueryBuilderBody .= '$queryBuilder->setParameter(\'p_estral_' . strtolower($classModel) . '_' . $item['column_name'] . '\', \'%\'.$this->filters[\'' . $item['column_name'] . '\'].\'%\');' . PHP_EOL;
                } elseif ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                    $updateQueryBuilderBody .= 'if((bool)$this->filters[\'' . $item['column_name'] . '\']){' . PHP_EOL;
                    $updateQueryBuilderBody .= '$queryBuilder->where(\'extra_' . strtolower($classModel) . '.' . $item['column_name'] . ' = 1\');' . PHP_EOL;
                    $updateQueryBuilderBody .= '}else{' . PHP_EOL;
                    $updateQueryBuilderBody .= '$queryBuilder->where(\'extra_' . strtolower($classModel) . '.' . $item['column_name'] . ' = 0 OR ISNULL("extra_' . strtolower($classModel) . '.' . $item['column_name'] . '")\');';
                    $updateQueryBuilderBody .= '}' . PHP_EOL;
                } else {
                    $updateQueryBuilderBody .= '$queryBuilder->where(\'extra_' . strtolower($classModel) . '.' . $item['column_name'] . ' LIKE :p_estra_' . strtolower($classModel) . '_' . $item['column_name'] . '\');' . PHP_EOL;
                    $updateQueryBuilderBody .= '$queryBuilder->setParameter(\'p_estra_' . strtolower($classModel) . '_' . $item['column_name'] . '\', \'%\'.$this->filters[\'' . $item['column_name'] . '\'].\'%\');' . PHP_EOL;
                }
                $updateQueryBuilderBody .= '}' . PHP_EOL;
            }
            $updateQueryBuilder->setBody($updateQueryBuilderBody);
            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);

            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridQueryBuilderModifier.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridQueryBuilderModifier.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'GridQueryBuilderModifier.php', $code, FILE_APPEND);
            $this->module_data['use'][strtolower($classModel)][$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'GridQueryBuilderModifier'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'GridQueryBuilderModifier';
            $this->module_data['use'][strtolower($classModel)][$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'GridDefinitionModifier'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'GridDefinitionModifier';
            $gridDefinitionModifierContent = '$' . strtolower($classModel) . 'GridDefinitionModifier= new ' . $classModel . 'GridDefinitionModifier(' . PHP_EOL;
            $gridDefinitionModifierContent .= '$this,' . PHP_EOL;
            $gridDefinitionModifierContent .= '$params[\'definition\']' . PHP_EOL;
            $gridDefinitionModifierContent .= ');' . PHP_EOL;
            $gridDefinitionModifierContent .= '$' . strtolower($classModel) . 'GridDefinitionModifier->addColumns();' . PHP_EOL;
            $gridDefinitionModifierContent .= '$' . strtolower($classModel) . 'GridDefinitionModifier->addFilters();' . PHP_EOL;
            $this->module_data['hooksContents']['action' . $classModel . 'GridDefinitionModifier'] = $gridDefinitionModifierContent;
            $gridQueryBuilderModifierContent = '$' . strtolower($classModel) . 'QueryBuilderModifier= new ' . $classModel . 'GridQueryBuilderModifier(' . PHP_EOL;
            $gridQueryBuilderModifierContent .= '$this,' . PHP_EOL;
            $gridQueryBuilderModifierContent .= '$params[\'search_criteria\']->getFilters(),' . PHP_EOL;
            $gridQueryBuilderModifierContent .= 'Context::getContext()->language->id' . PHP_EOL;
            $gridQueryBuilderModifierContent .= ');' . PHP_EOL;
            $gridQueryBuilderModifierContent .= '$' . strtolower($classModel) . 'QueryBuilderModifier->updateQueryBuilder($params[\'count_query_builder\']);' . PHP_EOL;
            $gridQueryBuilderModifierContent .= '$' . strtolower($classModel) . 'QueryBuilderModifier->updateQueryBuilder($params[\'search_query_builder\']);' . PHP_EOL;
            $this->module_data['hooksContents']['action' . $classModel . 'GridQueryBuilderModifier'] = $gridQueryBuilderModifierContent;
        }
        return true;
    }

    /**
     * @return string
     */
    public function booleanPresenter()
    {
        $bool = '';
        $bool .= 'if (!is_null(/+value) && /+value == 0) {' . PHP_EOL;
        $bool .= ' return \'<a href="#"><i class="material-icons action-disabled">clear</i></a>\';' . PHP_EOL;
        $bool .= '} elseif(/+value == 1) {' . PHP_EOL;
        $bool .= 'return \'<a href="#"><i class="material-icons enabled">check</i></a>\';' . PHP_EOL;
        $bool .= '}' . PHP_EOL;
        return $bool;
    }

    public function generateSettings()
    {
        $content = file_get_contents($this->base_dir . '/samples/settings.php');
        $content = $this->replaceStandardStrings($content);
        $inputs = '';
        $there_is_carrier = false;
        $there_is_category = false;
        $there_is_product = false;
        $config_form_value = '';
        $categoryTreeName = null;
        foreach ($this->module_data['settings'] as $settingData) {
            if (empty($settingData['name'])) {
                continue;
            }
            $setting_name = explode(' ', $settingData['name']);
            if (!empty($setting_name)) {
                foreach ($setting_name as $index => $part) {
                    $setting_name[$index] = strtoupper($part);
                }
            }
            $settingData['name'] = strtoupper($this->params['upper']['module_name']) . '_' . implode('_', $setting_name);
            $config_form_value .= "'" . $settingData['name'] . "' => Configuration::get('" . $settingData['name'] . "', null)," . PHP_EOL;
            $inputContent = file_get_contents($this->base_dir . '/samples/inputs/' . strtolower($settingData['type']) . '.txt');

            $inputContent = str_replace(array('setting_name', 'setting_label', 'setting_description'), array($settingData['name'], $settingData['label'], $settingData['description']), $inputContent);

            $inputs .= $inputContent . PHP_EOL;

            if (strtolower($settingData['type']) == 'carrier-select') {
                $there_is_carrier = true;
            }
            if (strtolower($settingData['type']) == 'category-tree') {
                $there_is_category = true;
                $categoryTreeName = $settingData['name'];
            }
            if (strtolower($settingData['type']) == 'product-select') {
                $there_is_product = true;
            }

        }

        $content = str_replace("'form_inputs'", $inputs, $content);
        $content = str_replace("'config_form_value'", $config_form_value, $content);
        if ($there_is_carrier) {
            $carrierSelect = file_get_contents($this->base_dir . '/samples/conditionalCodeParts/carrier.php');
            $content .= $carrierSelect . PHP_EOL;
        }
        if ($there_is_category) {
            $categorySelect = file_get_contents($this->base_dir . '/samples/conditionalCodeParts/category.php');
            $content .= $categorySelect . PHP_EOL;
            $content = str_replace('setting_name', $categoryTreeName, $content);
        }
        if ($there_is_product) {
            $productSelect = file_get_contents($this->base_dir . '/samples/conditionalCodeParts/product.php');
            $content .= $productSelect . PHP_EOL;
        }


        $moduleContent = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');

        $moduleContent = str_replace('/** settings */', $content, $moduleContent);
        file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $moduleContent);
        return true;
    }

    public function generateGrid()
    {
        $this->generateGridEntities();
        $this->generateGridDefinition();
        $this->generateGridFilters();
        $this->generateGridQuery();
        $this->generateSQl();
        $this->generateFormTypeAndHandler();
        $this->generateRepository();
        $this->generateRoutesAndServices();
        $this->generateController();
        $this->generateViews();
        $this->generateJsfiles();
        $this->generateCompiledJs();
        $this->generateCompiledCss();
        $this->setGridTab();

        return true;
    }

    /**
     * @param $modelData
     * @param ClassType $class
     * @return string[]
     */
    private function renderModelSql($modelData, ClassType $class)
    {

        $sql = '';
        $sql_uninstall = '';
        $sql_shop = '';
        $sql_lang = '';
        $table = $modelData['table'] ?? strtolower($class);
        $sql .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $table . '` (' . PHP_EOL;
        $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $table . '`;\';' . PHP_EOL;
        $firstShopIteration = 1;
        $firstLangIteration = 1;

        foreach ($modelData['fields'] as $index => $fieldData) {
            $separator = ',';
            if ($index === array_key_last($modelData['fields'])) {
                $separator = ',';
            }

            if (!empty($fieldData['is_auto_increment']) && $fieldData['is_auto_increment'] == 1) {
                $sql .= '`' . $fieldData['field_name'] . '` int(11) NOT NULL AUTO_INCREMENT' . $separator . PHP_EOL;
                continue;
            }
            if ($fieldData['is_nullable'] === '1') {
                $nullableCondition = ' NULL';
            } else {
                $nullableCondition = ' NOT NULL';
            }
            $default_value = '';
            if ($fieldData['default_value'] != "" && !empty($fieldData['default_value'])) {
                $default_value = ' DEFAULT ' . $fieldData['default_value'];
            }
            $is_shop_fields = !empty($fieldData['is_shop']) && $fieldData['is_shop'] !== '' && $fieldData['is_shop'] !== null;
            if ($is_shop_fields) {

                if ($firstShopIteration == 1) {
                    $sql_shop .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $table . '_shop` (' . PHP_EOL;
                    $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $table . '_shop`;\';' . PHP_EOL;
                    $sql_shop .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_shop .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                if (!empty($fieldData['field_name']) && $fieldData['field_type']) {
                    if (($fieldData['field_type'] === 'INT' || $fieldData['field_type'] === 'UnsignedInt')) {
                        if ($fieldData['field_type'] === 'UnsignedInt') {
                            $sql_shop .= '`' . $fieldData['field_name'] . '` INT(11) UNSIGNED ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                        }
                        if ($fieldData['field_type'] === 'INT') {
                            $sql_shop .= '`' . $fieldData['field_name'] . '` INT(11) ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                        }
                    } elseif (($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT')) {
                        $size = $fieldData['field_length'] ?? 255;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 20.6);
                        }
                        $size = $size ?? 20.6;
                        $size = str_replace('.', ',', $size);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT')) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;

                    } elseif (($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 1);
                        }
                        $size = $size ?? 1;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME')) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '  ' . $separator . PHP_EOL;
                    } else {
                        $fieldData['field_length'] = str_replace('.', ',', $fieldData['field_length']);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')' . $nullableCondition . $default_value . ',' . PHP_EOL;
                    }
                }

                $firstShopIteration++;
            }

            $is_lang_fields = !empty($fieldData['is_lang']) && $fieldData['is_lang'] !== '' && $fieldData['is_lang'] !== null;
            $in_two_table = !$is_lang_fields;
            if ($is_lang_fields) {
                if ($firstLangIteration == 1) {
                    $sql_lang .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $table . '_lang` (' . PHP_EOL;
                    $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $table . '_lang`;\';' . PHP_EOL;
                    $sql_lang .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_lang` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                $type = $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')';
                if ($fieldData['field_type'] !== 'VARCHAR' && $fieldData['field_type'] !== 'TEXT' && $fieldData['field_type'] !== 'LONGTEXT') {
                    $type = 'VARCHAR(' . $fieldData['field_length'] . ')';
                }
                if ($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT') {
                    $type = $fieldData['field_type'];
                }
                $sql_lang .= '`' . $fieldData['field_name'] . '` ' . $type . $nullableCondition . $default_value . ',' . PHP_EOL;
                $firstLangIteration++;
            }

            if (($fieldData['field_type'] === 'INT' || $fieldData['field_type'] === 'UnsignedInt') && $in_two_table) {
                if ($fieldData['field_type'] === 'UnsignedInt') {
                    $sql .= '`' . $fieldData['field_name'] . '` INT(11) UNSIGNED ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
                if ($fieldData['field_type'] === 'INT') {
                    $sql .= '`' . $fieldData['field_name'] . '` INT(11) ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
            }
            if (($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT')) {
                $size = $fieldData['field_length'] ?? 255;
                if ($in_two_table) {
                    $sql .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                }

            }
            if (($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT') && $in_two_table) {
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 20.6);
                }
                $size = $size ?? 20.6;
                $size = str_replace('.', ',', $size);
                $sql .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if (($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT')) {
                if ($in_two_table) {
                    $sql .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;
                }
            }
            if (($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN') && $in_two_table) {
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 1);
                }
                $size = $size ?? 1;
                $sql .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if (($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME') && $in_two_table) {
                $sql .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '  ' . $separator . PHP_EOL;
            }
        }
        if (!empty($sql)) {
            $sql = "'" . $sql;
        }

        if (!empty($sql_shop)) {

            $sql_shop = substr($sql_shop, 0, -3);
            $sql_shop = "'" . $sql_shop;
        }
        if (!empty($sql_lang)) {
            $sql_lang = substr($sql_lang, 0, -3);
            $sql_lang = "'" . $sql_lang;
        }

        return ['sql' => $sql, 'sql_shop' => $sql_shop, 'sql_lang' => $sql_lang, 'sql_uninstall' => $sql_uninstall];
    }

    private function makeFields($fields, ClassType $class, $is_lang_entity = false, $is_shop_entity = false)
    {
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $class = $this->addFieldWithGetterAndSetter($field, $class, 'field', $is_lang_entity, $is_shop_entity);
            }
        }

        return $class;
    }

    /**
     * @param $field
     * @param ClassType $class
     * @param string $returnType
     * @param $is_lang_entity
     * @param $is_shop_entity
     * @return ClassType
     */
    private function addFieldWithGetterAndSetter($field, ClassType $class, string $returnType = 'GetterAndSetter', $is_lang_entity, $is_shop_entity)
    {
        $name = $this->refactorFieldName($field['field_name']);

        if ($returnType == 'field') {
            if ($field['is_lang'] === '1') {
                if ($is_lang_entity) {
                    return $this->generateLangEntityField($field, $class);
                }
                if ($is_shop_entity) {
                    return $this->generateShopEntityField($field, $class);
                }
                $this->use[$class->getName()][] = 'Doctrine\Common\Collections\ArrayCollection';
                $class->addMethod('__construct')->setBody('$this->' . strtolower($class->getName()) . 'Langs' . ' = new ArrayCollection();');

                $langField = $class->addProperty(strtolower($class->getName()) . 'Langs')->setPrivate();
                $langField->addComment('one to many relation');
                $langField->addComment('@ORM\OneToMany(targetEntity="' . $class->getNamespace()->getName() . '\\' . $class->getName() . 'Lang", cascade={"persist", "remove"}, mappedBy="' . strtolower($class->getName()) . '")');
                return $class;
            }
            if ($field['is_shop'] === '1') {
                $shopField = $class->addProperty(strtolower($class->getName()) . 'Shops')->setPrivate();
                $shopField->addComment('one to many relation');
                $shopField->addComment('@ORM\OneToMany(targetEntity="' . $class->getNamespace()->getName() . '\\' . $class->getName() . 'Shop", cascade={"persist", "remove"}, mappedBy="' . strtolower($class->getName()) . '")');
                return $class;
            }

            return $this->generateEntityField($field, $class, $is_lang_entity, $is_shop_entity);
        }


        return $this->generateEntityGettersAndSetters($field, $class, $is_lang_entity, $is_shop_entity);
    }

    /**
     * @param $fieldName
     * @param bool $forMethod
     * @return string
     */
    private function refactorFieldName($fieldName, $forMethod = false)
    {
        $segments = explode('_', $fieldName);
        $name = '';
        $i = 0;
        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $i++;
                if ($i == 1 && !$forMethod) {
                    $name .= $segment;
                } else {
                    $name .= ucfirst($segment);
                }
            }
        }
        return $name;
    }

    /**
     * @param $modelData
     * @param ClassType $class
     * @param $is_lang_entity
     * @param $is_shop_entity
     * @return ClassType
     */
    private function makeMethods($fields, ClassType $class, $is_lang_entity = false, $is_shop_entity = false)
    {
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $class = $this->addFieldWithGetterAndSetter($field, $class, 'GetterAndSetter', $is_lang_entity, $is_shop_entity);
            }
        }
        return $class;
    }

    /**
     * @param $modelData
     * @param ClassType $class
     * @return ClassType
     */
    private function makeCallBack($modelData, ClassType $class)
    {
        $has_callBack = false;
        $body = '';
        if ($modelData['fields']) {
            foreach ($modelData['fields'] as $field) {
                if (($field['field_type'] === 'DATE' || $field['field_type'] === 'DATETIME')) {
                    $has_callBack = true;
                    $name = $this->refactorFieldName($field['field_name'], true);
                    $body .= '$this->set' . $name . '(new DateTime());' . PHP_EOL;
                    $body .= 'if ($this->get' . $name . '() == null) {' . PHP_EOL;
                    $body .= '$this->set' . $name . '(new DateTime());' . PHP_EOL;
                    $body .= '}' . PHP_EOL;
                }
            }
        }

        if ($has_callBack) {
            $callBack = $class->addMethod('updatedTimestamps');
            $callBack->addComment('@ORM\PrePersist');
            $callBack->addComment('@ORM\PreUpdate');
            $callBack->setBody($body);
        }
        return $class;
    }

    /**
     * @param $EntityName
     * @param $modelData
     * @param bool $is_lang_entity
     * @param bool $is_shop_entity
     * @return ClassType
     */
    private function makeEntity($EntityName, $modelData, $is_lang_entity = false, $is_shop_entity = false): ClassType
    {
        $params = $this->getParams();
        $namespace = new PhpNamespace($params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Entity');
        $this->use[$EntityName][] = 'Doctrine\ORM\Mapping as ORM';
        $class = $namespace->addClass($EntityName);
        $class->addComment('@ORM\Table()');
        if (!$is_lang_entity && !$is_shop_entity) {
            $class->addComment('@ORM\Entity(repositoryClass="' . $params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Repository' . '\\' . $modelData['class'] . 'Repository")');

            $class->addComment('@ORM\HasLifecycleCallbacks');
        } else {
            $class->addComment('@ORM\Entity()');
        }

        return $class;
    }

    private function generateEntityField($field, $class, $skipForLang = false, $skipForShop = false)
    {
        $name = $this->refactorFieldName($field['field_name']);
        if ($field['is_nullable'] === '1') {
            $nullableCondition = ', nullable=false';
        } else {
            $nullableCondition = '';
        }
        if (!empty($field['is_auto_increment'])) {
            if ($skipForLang) {
                $this->use[$class->getName()][] = 'PrestaShopBundle\Entity\Lang';
                $property = $class->addProperty(str_replace('lang', '', strtolower($class->getName())))->setPrivate();
                $property->addComment('@ORM\ManyToOne(targetEntity="' . $class->getNamespace()->getName() . '\\' . str_replace('Lang', '', $class->getName()) . '", inversedBy="' . lcfirst($class->getName()) . 's")');
                $property->addComment('@ORM\JoinColumn(name="' . $field['field_name'] . '", referencedColumnName="' . $field['field_name'] . '", nullable=false)');
            } elseif ($skipForShop) {
                $this->use[$class->getName()][] = 'PrestaShopBundle\Entity\Shop';
                $property = $class->addProperty(str_replace('shop', '', strtolower($class->getName())))->setPrivate();
                $property->addComment('@ORM\ManyToOne(targetEntity="' . $class->getNamespace()->getName() . '\\' . str_replace('Shop', '', $class->getName()) . '", inversedBy="' . lcfirst($class->getName()) . 's")');
                $property->addComment('@ORM\JoinColumn(name="' . $field['field_name'] . '", referencedColumnName="' . $field['field_name'] . '", nullable=false)');
            } else {
                $property = $class->addProperty('id')->setPrivate();
                $property->addComment('@ORM\GeneratedValue(strategy="AUTO")');
                $property->addComment('@ORM\Id');
                if($field['field_name']!=='id'){
                    $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="integer"' . $nullableCondition . ')');
                }
            }
        } else {
            if ($field['field_name'] == 'id_lang') {
                $property = $class->addProperty('lang')->setPrivate();
                $property->addComment('@var Lang');
                $property->addComment('@ORM\ManyToOne(targetEntity="PrestaShopBundle\Entity\Lang")');
                $property->addComment('@ORM\JoinColumn(name="id_lang", referencedColumnName="id_lang", nullable=false, onDelete="CASCADE")');
            } elseif ($field['field_name'] == 'id_shop') {
                $property = $class->addProperty('shop')->setPrivate();
                $property->addComment('@var Shop');
                $property->addComment('@ORM\ManyToOne(targetEntity="PrestaShopBundle\Entity\Shop")');
                $property->addComment('@ORM\JoinColumn(name="id_shop", referencedColumnName="id_shop", nullable=false, onDelete="CASCADE")');
            } else {
                $property = $class->addProperty($name)->setPrivate();
            }

        }

        if (($field['field_type'] === 'INT' || $field['field_type'] === 'UnsignedInt') && empty($field['is_auto_increment'])) {
            $property->addComment('@var int');
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="integer"' . $nullableCondition . ')');

        }
        if ($field['field_type'] === 'TEXT' || $field['field_type'] === 'LONGTEXT') {
            $property->addComment('@var string');
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="text", length=1000' . $nullableCondition . ')');
        }
        if (($field['field_type'] === 'EMAIL' || $field['field_type'] === 'VARCHAR' || $field['field_type'] === 'HTML' || $field['field_type'] === 'PERCENT')) {

            $property->addComment('@var string');
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="text", length=255' . $nullableCondition . ')');
        }
        if (($field['field_type'] === 'TINYINT' || $field['field_type'] === 'BOOLEAN')) {
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="boolean")')->setValue(false);
        }
        if (($field['field_type'] === 'DECIMAL' || $field['field_type'] === 'FLOAT')) {
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '",type="decimal", scale=6' . $nullableCondition . ')');
        }
        if (($field['field_type'] === 'DATE' || $field['field_type'] === 'DATETIME')) {
            $property->addComment('@var DateTime');
            $property->addComment('@ORM\Column(name="' . $field['field_name'] . '", type="datetime", scale=6' . $nullableCondition . ')');
        }
        return $class;
    }

    private function generateLangEntityField($field, ClassType $class)
    {
        return $this->generateEntityField($field, $class, true, false);
    }

    private function generateShopEntityField($field, ClassType $class)
    {
        return $this->generateEntityField($field, $class, false, true);
    }

    /**
     * @param $fields
     * @param string $type
     * @param $modelData
     * @return array
     */
    private function prepareFields($fields, string $type, $modelData)
    {
        $results = [];
        $results[0] = [
            "field_name" => $modelData['primary'],
            "field_type" => "INT",
            "field_length" => "11",
            "is_auto_increment" => "1",
            "is_nullable" => null,
            "is_lang" => null,
            "is_shop" => null,
            "default_value" => null
        ];
        if ($type == 'lang') {
            $results[] = [
                "field_name" => "id_lang",
                "field_type" => "INT",
                "field_length" => "11",
                "is_auto_increment" => null,
                "is_nullable" => null,
                "is_lang" => null,
                "is_shop" => null,
                "default_value" => null
            ];
        }
        if ($type == 'shop') {
            $results[] = [
                "field_name" => "id_shop",
                "field_type" => "INT",
                "field_length" => "11",
                "is_auto_increment" => null,
                "is_nullable" => null,
                "is_lang" => null,
                "is_shop" => null,
                "default_value" => null
            ];
        }
        foreach ($fields as $field) {
            if ($type == 'lang' && $field['is_lang'] == 1) {
                $results[] = $field;
            }
            if ($type == 'shop' && $field['is_shop'] == 1) {
                $results[] = $field;
            }
        }
        return $results;
    }

    /**
     * @param $field
     * @param $class
     * @param bool $skipForLang
     * @param bool $skipForShop
     * @return mixed
     */
    private function generateEntityGettersAndSetters($field, $class, $skipForLang = false, $skipForShop = false)
    {
        $name = $this->refactorFieldName($field['field_name']);
        $methodName = $this->refactorFieldName($field['field_name'], true);
        if (!empty($field['is_auto_increment'])) {
            if ($skipForLang) {
                $parent = str_replace('Lang', '', $class->getName());
                $method = $class->addMethod('get' . $parent);
                $method->addComment('@return ' . $parent);
                $method->setBody('return $this->' . strtolower($parent) . ';');
                $setter = $class->addMethod('set' . $parent);
                $this->langFields[$field['field_name']] = $class->getName();
                $this->setters[$field['field_name']] = 'set' . $parent;
                $setter->addComment('@param ' . $parent . ' $' . strtolower($parent));
                $setter->addComment('@return $this');
                $setter->addParameter(strtolower($parent))->setType($parent);
                $setter->setBody('$this->' . strtolower($parent) . ' = $' . strtolower($parent) . ';' . PHP_EOL . 'return $this;');
            } elseif ($skipForShop) {
                $parent = str_replace('Shop', '', $class->getName());
                $method = $class->addMethod('get' . $parent);
                $method->addComment('@return ' . $parent);
                $method->setBody('return $this->' . strtolower($parent) . ';');
                $setter = $class->addMethod('set' . $parent);
                $this->setters[$field['field_name']] = 'set' . $parent;
                $this->getters[$field['field_name']] = 'get' . $parent;
                $setter->addComment('@param ' . $parent . ' $' . strtolower($parent));
                $setter->addComment('@return $this');
                $setter->addParameter(strtolower($parent))->setType($parent);
                $setter->setBody('$this->' . strtolower($parent) . ' = $' . strtolower($parent) . ';' . PHP_EOL . 'return $this;');
            } else {
                $method = $class->addMethod('getId');
                $method->addComment('@return int');
                $method->setBody('return $this->id;');
            }
        } else {
            if ($field['field_name'] == 'id_lang') {
                $method = $class->addMethod('getLang');
                $method->addComment('@return Lang');
                $method->setBody('return $this->lang;');
                $setter = $class->addMethod('setLang');
                $this->setters[$field['field_name']] = 'setLang';
                $this->getters[$field['field_name']] = 'getLang';
                $setter->addComment('@param Lang $lang');
                $setter->addComment('@return $this');
                $setter->addParameter('lang')->setType('Lang');
                $setter->setBody('$this->lang = $lang;' . PHP_EOL . 'return $this;');
            } elseif ($field['field_name'] == 'id_shop') {
                $method = $class->addMethod('getShop');
                $method->addComment('@return Shop');
                $method->setBody('return $this->shop;');
                $setter = $class->addMethod('setShop');
                $this->setters[$field['field_name']] = 'setShop';
                $this->getters[$field['field_name']] = 'getShop';
                $setter->addComment('@param Shop $shop');
                $setter->addComment('@return $this');
                $setter->addParameter('shop')->setType('Shop');
                $setter->setBody('$this->shop = $shop;' . PHP_EOL . 'return $this;');
            } else {
                $condition = (empty($field['is_lang']) || (!empty($field['is_lang']) && $skipForLang)) && (empty($field['is_shop']) || (!empty($field['is_shop']) && $skipForShop));
                if (($field['field_type'] === 'INT' || $field['field_type'] === 'UnsignedInt') && $condition) {
                    $getter = $class->addMethod('get' . $methodName);
                    $getter->addComment('@return int');
                    $getter->setBody('return $this->' . $name . ';');
                    $setter = $class->addMethod('set' . $methodName);
                    $this->setters[$field['field_name']] = 'set' . $methodName;
                    $setter->addComment('@param int $' . $name);
                    $setter->addComment('@return $this');
                    $setter->addParameter($name)->setType('int');
                    $setter->setBody('$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;');
                }
                if (($field['field_type'] === 'EMAIL' || $field['field_type'] === 'VARCHAR' || $field['field_type'] === 'HTML' || $field['field_type'] === 'PERCENT' || $field['field_type'] === 'TEXT' || $field['field_type'] === 'LONGTEXT') && $condition) {
                    $getter = $class->addMethod('get' . $methodName);
                    $getter->addComment('@return string');
                    $getter->setBody('return $this->' . $name . ';');
                    $setter = $class->addMethod('set' . $methodName);
                    $setter->addComment('@param string $' . $name);
                    $setter->addComment('@return $this');
                    $setter->addParameter($name)->setType('string');
                    $setter->setBody('$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;');
                }

                if (($field['field_type'] === 'TINYINT' || $field['field_type'] === 'BOOLEAN') && $condition) {
                    $getter = $class->addMethod('get' . $methodName);
                    $getter->addComment('@return bool');
                    $getter->setBody('return $this->' . $name . ';');
                    $setter = $class->addMethod('set' . $methodName);
                    $setter->addComment('@param bool $' . $name);
                    $setter->addComment('@return $this');
                    $setter->addParameter($name)->setType('bool');
                    $setter->setBody('$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;');
                }
                if (($field['field_type'] === 'DECIMAL' || $field['field_type'] === 'FLOAT') && $condition) {
                    $getter = $class->addMethod('get' . $methodName);
                    $getter->addComment('@return string');
                    $getter->setBody('return $this->' . $name . ';');
                    $setter = $class->addMethod('set' . $methodName);
                    $setter->addComment('@param string $' . $name);
                    $setter->addComment('@return $this');
                    $setter->addParameter($name)->setType('string');
                    $setter->setBody('$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;');
                }
                if (($field['field_type'] === 'DATE' || $field['field_type'] === 'DATETIME') && $condition) {
                    $getter = $class->addMethod('get' . $methodName);
                    $getter->addComment('@return DateTime');
                    $getter->setBody('return $this->' . $name . ';');
                    $setter = $class->addMethod('set' . $methodName);
                    $setter->addComment('@param DateTime $' . $name);
                    $setter->addComment('@return $this');
                    $setter->addParameter($name)->setType('DateTime');
                    $setter->setBody('$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;');
                }

                $this->setters[$field['field_name']] = 'set' . $methodName;
                $this->getters[$field['field_name']] = 'get' . $methodName;
            }

        }
        return $class;
    }

    /**
     * @param $dir
     * @return string
     */
    public function createDir($dir)
    {
        $folder = $this->module_dir . '/src/' . $dir;
        if (!is_dir($folder) && !@mkdir($folder, 0777, true) && !is_dir($folder)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $folder));
        }
        return $folder;
    }

    private function generateGridEntities()
    {
        $sql = '';
        $sql_uninstal = '';
        $sql_shop = '';
        $sql_lang = '';
        foreach ($this->module_data['models'] as $index => $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }
            $params = $this->getParams();
            $namespace = new PhpNamespace($params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Entity');
            $class = $this->makeEntity($modelData['class'], $modelData);
            $class = $this->makeFields($modelData['fields'], $class);
            $class = $this->makeMethods($modelData['fields'], $class);
            $class = $this->makeCallBack($modelData, $class);
            $this->createDir('Entity');
            if (!empty($modelData['fields'])) {
                $has_lang_field = (bool)in_array('1', array_column($modelData['fields'], 'is_lang'));
                $has_shop_field = (bool)in_array('1', array_column($modelData['fields'], 'is_shop'));
            }
            if (!empty($has_lang_field)) {
                $langClass = $this->makeEntity($modelData['class'] . 'Lang', $modelData, true);
                $fields = $this->prepareFields($modelData['fields'], 'lang', $modelData);
                $langClass = $this->makeFields($fields, $langClass, $has_lang_field, false);
                $langClass = $this->makeMethods($fields, $langClass, $has_lang_field, false);
                $printer = new Printer;
                $printer->setTypeResolving(false);
                $code = $printer->printClass($langClass);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', '<?php declare(strict_types=1);');
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', 'namespace '.$params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Entity;' , FILE_APPEND);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', PHP_EOL, FILE_APPEND);
                if (!empty($uses = $this->use[$modelData['class'] . 'Lang'])) {
                    foreach ($uses as $use) {
                        file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', 'use ' . $use . ';' . PHP_EOL, FILE_APPEND);
                    }
                }
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Lang.php', $code, FILE_APPEND);
                $method1=$class->addMethod('get'.$modelData['class'].'Langs')->setComment('@return ArrayCollection');
                $method1->setBody('return $this->'.strtolower($modelData['class']).'Langs;');
            }
            if (!empty($has_shop_field)) {
                $shopClass = $this->makeEntity($modelData['class'] . 'Shop', $modelData, false, true);
                $fields = $this->prepareFields($modelData['fields'], 'shop', $modelData);
                $shopClass = $this->makeFields($fields, $shopClass, false, $has_shop_field);
                $shopClass = $this->makeMethods($fields, $shopClass, false, $has_shop_field);
                $printer = new Printer;
                $printer->setTypeResolving(false);
                $code = $printer->printClass($shopClass);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Shop.php', '<?php declare(strict_types=1);');
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
                if (!empty($uses = $this->use[$modelData['class'] . 'Shop'])) {
                    foreach ($uses as $use) {
                        file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Shop.php', 'use ' . $use . ';' . PHP_EOL, FILE_APPEND);
                    }
                }
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Shop.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . 'Shop.php', $code, FILE_APPEND);
            }

            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printClass($class);
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', 'namespace '.$params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Entity;', FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            if (!empty($uses = $this->use[$modelData['class']])) {
                foreach ($uses as $use) {
                    file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', 'use ' . $use . ';' . PHP_EOL, FILE_APPEND);
                }
            }
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Entity/' . $modelData['class'] . '.php', $code, FILE_APPEND);

            $sqlCollection = $this->renderModelSql($modelData, $class);

            $fieldsDataSql = $sqlCollection['sql'];
            $fieldsDataUninstall = $sqlCollection['sql_uninstall'];
            $fieldsDataSql_shop = $sqlCollection['sql_shop'];
            $fieldsDataSql_lang = $sqlCollection['sql_lang'];
            $fieldsDataSql .= 'PRIMARY KEY  (`' . $modelData['primary'] . '`)' . PHP_EOL;
            $fieldsDataSql .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
            $fieldsDataSql = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataSql);
            $sql .= '$sql[]=' . $fieldsDataSql . "';" . PHP_EOL;
            $fieldsDataUninstall = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataUninstall);
            $sql_uninstal .= $fieldsDataUninstall . PHP_EOL;

            if (!empty($fieldsDataSql_shop)) {
                $fieldsDataSql_shop .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_shop = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_shop);
                $sql_shop .= '$sql[]=' . $fieldsDataSql_shop . "';" . PHP_EOL;
            }
            if (!empty($fieldsDataSql_lang)) {
                $fieldsDataSql_lang .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_lang`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_lang = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_lang);
                $sql_lang .= '$sql[]=' . $fieldsDataSql_lang . "';" . PHP_EOL;
            }
        }
        $this->installSql[] = $sql . $sql_lang . $sql_shop;
        $this->unInstallSql[] = $sql_uninstal;
        return true;
    }

    /**
     * @return bool
     */
    private function generateGridDefinition()
    {
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($modelData['class'])) {
                continue;
            }
            $content = file_get_contents($this->base_dir . '/samples/src/Grid/Definition/Factory/SampleGridDefinitionFactory.php');
            $content = str_replace('module_namespace', $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'], $content);

            $content=str_replace('GRID_ID = \'quote\'','GRID_ID = \''.str_replace('id_', '', $modelData['primary']).'\'', $content);
            $columns = $this->makeColumns($modelData);
            $content = str_replace('/** replace with columns */', $columns, $content);
            $filters = $this->makeFilters($modelData);
            $content = str_replace('/** replace with filters */', $filters, $content);
            $useStatements = '';
            if (!empty($uses = $this->use[$modelData['class'] . 'GridDefinitionFactory'])) {

                foreach ($uses as $use) {
                    $useStatements .= 'use ' . $use . ';' . PHP_EOL;
                }
            }

            $content = str_replace('/** replace with uses */', $useStatements, $content);
            $content=str_replace('id_quote', $modelData['primary'], $content);
            $content = str_replace(array('quote', 'Quote', 'SampleGridDefinitionFactory', 'Demodoctrine', 'ps_demodoctrine_quote'), array(strtolower($modelData['class']), $modelData['class'], $modelData['class'] . 'GridDefinitionFactory', $this->params['upper']['module_name'], $this->params['lower']['module_name'] . '_' . strtolower($modelData['class'])), $content);
            $this->createDir('Grid/Definition/Factory');
            $content = $this->replaceStandardStrings($content);
            file_put_contents($this->module_dir . '/src/Grid/Definition/Factory/' . $modelData['class'] . 'GridDefinitionFactory.php', $content);
        }
        return true;
    }

    /**
     * @param $modelData
     * @return string
     */
    private function makeColumns($modelData)
    {
        $column = 'return (new ColumnCollection())' . PHP_EOL;
        foreach ($modelData['fields'] as $field) {
            if ($field['is_auto_increment'] == 1) {
                $column .= '            ->add((new BulkActionColumn(\'bulk\'))
                                        ->setOptions([
                                            \'bulk_field\' => \'' . $field['field_name'] . '\',
                                        ])
                                    )
                                    ->add((new DataColumn(\'' . $field['field_name'] . '\'))
                                        ->setName($this->trans(\'ID\', [], \'Admin.Global\'))
                                        ->setOptions([
                                            \'field\' => \'' . $field['field_name'] . '\',
                                        ])
                                    )
            ' . PHP_EOL;
            } else {
                $column .= '            ->add((new DataColumn(\'' . $field['field_name'] . '\'))
                ->setName($this->trans(\'' . $field['field_name'] . '\', [], \'Modules.Demodoctrine.Admin\'))
                ->setOptions([
                    \'field\' => \'' . $field['field_name'] . '\',
                ])
            )' . PHP_EOL;
            }
        }
        $column .= '            ->add((new ActionColumn(\'actions\'))
                                ->setName($this->trans(\'Actions\', [], \'Admin.Global\'))
                                ->setOptions([
                                    \'actions\' => (new RowActionCollection())
                                        ->add((new LinkRowAction(\'edit\'))
                                            ->setName($this->trans(\'Edit\', [], \'Admin.Actions\'))
                                            ->setIcon(\'edit\')
                                            ->setOptions([
                                                \'route\' => \'ps_demodoctrine_quote_edit\',
                                                \'route_param_name\' => \'quoteId\',
                                                \'route_param_field\' => \'id_quote\',
                                                \'clickable_row\' => true,
                                            ])
                                        )
                                        ->add((new SubmitRowAction(\'delete\'))
                                            ->setName($this->trans(\'Delete\', [], \'Admin.Actions\'))
                                            ->setIcon(\'delete\')
                                            ->setOptions([
                                                \'method\' => \'DELETE\',
                                                \'route\' => \'ps_demodoctrine_quote_delete\',
                                                \'route_param_name\' => \'quoteId\',
                                                \'route_param_field\' => \'id_quote\',
                                                \'confirm_message\' => $this->trans(
                                                    \'Delete selected item?\',
                                                    [],
                                                    \'Admin.Notifications.Warning\'
                                                ),
                                            ])
                                        ),
                                ])
            )
        ;';
        return $column;

    }

    private function makeFilters($modelData)
    {

        $filters = 'return (new FilterCollection())' . PHP_EOL;
        foreach ($modelData['fields'] as $field) {
            if ($field['is_auto_increment'] == 1) {
                $this->use[$modelData['class'] . 'GridDefinitionFactory']['TextType'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';
                $filters .= '            ->add((new Filter(\'id_quote\', TextType::class))
                ->setTypeOptions([
                    \'required\' => false,
                    \'attr\' => [
                        \'placeholder\' => $this->trans(\'ID\', [], \'Admin.Global\'),
                    ],
                ])
                ->setAssociatedColumn(\'id_quote\')
            )' . PHP_EOL;
            } else {
                if ($field['field_type'] === 'TINYINT' || $field['field_type'] === 'BOOLEAN') {
                    $this->use[$modelData['class'] . 'GridDefinitionFactory']['YesAndNoChoiceType'] = 'PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType';
                    $filters .= '            ->add((new Filter(\'' . $field['field_name'] . '\', YesAndNoChoiceType::class))
                ->setTypeOptions([
                    \'required\' => false,
                    \'attr\' => [
                        \'placeholder\' => $this->trans(\'' . $field['field_name'] . '\', [], \'Modules.Demodoctrine.Admin\'),
                    ],
                ])
                ->setAssociatedColumn(\'' . $field['field_name'] . '\')
            )' . PHP_EOL;
                } elseif (($field['field_type'] === 'DATE' || $field['field_type'] === 'DATETIME')) {
                    $this->use[$modelData['class'] . 'GridDefinitionFactory']['DateTimeType'] = 'Symfony\Component\Form\Extension\Core\Type\DateTimeType';
                    $filters .= '            ->add((new Filter(\'' . $field['field_name'] . '\', DateTimeType::class))
                ->setTypeOptions([
                    \'required\' => false,
                    \'attr\' => [
                        \'placeholder\' => $this->trans(\'' . $field['field_name'] . '\', [], \'Modules.Demodoctrine.Admin\'),
                    ],
                ])
                ->setAssociatedColumn(\'' . $field['field_name'] . '\')
            )' . PHP_EOL;
                } else {
                    $this->use[$modelData['class'] . 'GridDefinitionFactory']['TextType'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';
                    $filters .= '            ->add((new Filter(\'' . $field['field_name'] . '\', TextType::class))
                ->setTypeOptions([
                    \'required\' => false,
                    \'attr\' => [
                        \'placeholder\' => $this->trans(\'' . $field['field_name'] . '\', [], \'Modules.Demodoctrine.Admin\'),
                    ],
                ])
                ->setAssociatedColumn(\'' . $field['field_name'] . '\')
            )' . PHP_EOL;
                }

            }

        }
        $filters .= '            ->add((new Filter(\'actions\', SearchAndResetType::class))
                ->setTypeOptions([
                    \'reset_route\' => \'admin_common_reset_search_by_filter_id\',
                    \'reset_route_params\' => [
                        \'filterId\' => self::GRID_ID,
                    ],
                    \'redirect_route\' => \'ps_demodoctrine_quote_index\',
                ])
                ->setAssociatedColumn(\'actions\')
            )
        ;' . PHP_EOL;
        $this->use[$modelData['class'] . 'GridDefinitionFactory']['TextType'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';
        return $filters;
    }

    private function generateGridFilters()
    {
        $content = file_get_contents($this->base_dir . '/samples/src/Grid/Filters/SampleFilters.php');
        $content = str_replace('module_namespace', $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'], $content);
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($modelData['class'])) {
                continue;
            }
            $content=str_replace('id_quote', $modelData['primary'], $content);
            $content = str_replace(array('quote', 'Quote', 'SampleGridDefinitionFactory', 'Demodoctrine', 'ps_demodoctrine_quote'), array(strtolower($modelData['class']), $modelData['class'], $modelData['class'] . 'GridDefinitionFactory', $this->params['upper']['module_name'], $this->params['lower']['module_name'] . '_' . strtolower($modelData['class'])), $content);
            $this->createDir('Grid/Filters');
            file_put_contents($this->module_dir . '/src/Grid/Filters/' . $modelData['class'] . 'Filters.php', $content);
        }
        return true;
    }

    private function generateGridQuery()
    {
        $content = file_get_contents($this->base_dir . '/samples/src/Grid/Query/SampleQueryBuilder.php');
        $content = str_replace('module_namespace', $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'], $content);
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($modelData['class'])) {
                continue;
            }
            $content=str_replace('id_quote', $modelData['primary'], $content);
            $content = str_replace(array('quote', 'Quote', 'SampleGridDefinitionFactory', 'Demodoctrine', 'ps_demodoctrine_quote'), array(strtolower($modelData['class']), $modelData['class'], $modelData['class'] . 'GridDefinitionFactory', $this->params['upper']['module_name'], $this->params['lower']['module_name'] . '_' . strtolower($modelData['class'])), $content);
            $langSelect = '';
            $select = '';
            $auto_increment = '';
            $selectStatement = '';
            $firsPass = false;
            $allowedFilters = '$allowedFilters=[' . PHP_EOL;
            $queryBuilder = '        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . ' . "'" . $modelData['table'] . "'" . ', \'q\')' . PHP_EOL;
            if (!empty($modelData['fields'])) {
                foreach ($modelData['fields'] as $field) {
                    $allowedFilters .= "            '" . $field['field_name'] . "'," . PHP_EOL;
                    if ($field['is_auto_increment'] == 1) {
                        $auto_increment = "q." . $field['field_name'];
                    } else {
                        if ($field['is_lang'] == 1) {
                            $langSelect .= ", ql." . $field['field_name'];
                            if (!$firsPass) {
                                $queryBuilder .= '            ->innerJoin(\'q\', $this->dbPrefix . ' . "'" . $modelData['table'] . "_lang'" . ', \'ql\', \'q.' . strtolower($modelData['primary']) . ' = ql.' . strtolower($modelData['primary']) . '\')
                                    ->andWhere(\'ql.`id_lang`= :language\')
                                    ->setParameter(\'language\', $this->languageId)';
                            }

                        } else {
                            $select .= ', q.' . $field['field_name'];
                        }
                    }
                }
                $selectStatement = "'" . $auto_increment . $select . $langSelect . "'";
            }

            $queryBuilder .= ';';
            $allowedFilters .= '            ];';
            $queryBuilder = ($allowedFilters) . PHP_EOL . $queryBuilder;
            $searchQueryBuilder = '        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb
            ->select(' . $selectStatement . ')
            ->groupBy(' . "'" . $auto_increment . "'" . ');

        $this->searchCriteriaApplicator
            ->applySorting($searchCriteria, $qb)
            ->applyPagination($searchCriteria, $qb)
        ;

        return $qb;';
            $content = str_replace('/** replace with SearchQueryBuilder */', $searchQueryBuilder, $content);
            $content = str_replace('/** replace with queryBuilder */', $queryBuilder, $content);

            $this->createDir('Grid/Query');
            file_put_contents($this->module_dir . '/src/Grid/Query/' . $modelData['class'] . 'QueryBuilder.php', $content);
        }

        return true;
    }

    private function generateFormTypeAndHandler()
    {
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            $use = [];
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Form');
            $namespace->addUse('PrestaShopBundle\Form\Admin\Type\TranslatorAwareType');
            $namespace->addUse('Symfony\Component\Form\FormBuilderInterface');

            $class = $namespace->addClass($classModel . 'Type')->addExtend('TranslatorAwareType')->addComment('{@inheritdoc}');
            $buildForm = $class->addMethod('buildForm');
            $buildForm->addParameter('builder')->setType('FormBuilderInterface');
            $buildForm->addParameter('options' . $classModel)->setType('array');
            $i = 0;

            $addFieldsBody = '        $builder';
            if (!empty($fields = $modelData['fields'])) {
                foreach ($fields as $item) {
                    if ($item['is_auto_increment'] == 1) {
                        continue;
                    }
                    if ($item['field_type'] === 'TINYINT' || $item['field_type'] === 'BOOLEAN') {
                        $addFieldsBody .= '->add(' . PHP_EOL;
                        $addFieldsBody .= "'" . $item['field_name'] . "'," . PHP_EOL;
                        $namespace->addUse('PrestaShopBundle\Form\Admin\Type\SwitchType');
                        $addFieldsBody .= "SwitchType::class," . PHP_EOL;
                        $addFieldsBody .= "[" . PHP_EOL;
                        $addFieldsBody .= '\'label\'=>$this->trans(\'' . $item['field_name'] . '\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\', []),' . PHP_EOL;
                        $addFieldsBody .= '\'required\'=>true,' . PHP_EOL;
                        $addFieldsBody .= "]" . PHP_EOL;
                        $addFieldsBody .= ')' . PHP_EOL;
                    } elseif (!empty($item['is_lang'])) {
                        $addFieldsBody .= '->add(' . PHP_EOL;
                        $addFieldsBody .= "'" . $item['field_name'] . "'," . PHP_EOL;
                        $namespace->addUse('PrestaShopBundle\Form\Admin\Type\TranslatableType');
                        $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\TextareaType');
                        $namespace->addUse('Symfony\Component\Validator\Constraints\Regex');
                        $namespace->addUse('PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage');
                        $addFieldsBody .= "TranslatableType::class," . PHP_EOL;
                        $addFieldsBody .= "[" . PHP_EOL;
                        $addFieldsBody .= '\'label\'=>$this->trans(\'' . $item['field_name'] . '\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\', []),' . PHP_EOL;
                        $addFieldsBody .= '\'type\'=>TextareaType::class,' . PHP_EOL;
                        $addFieldsBody .= '\'required\'=>true,' . PHP_EOL;
                        $addFieldsBody .= '\'constraints\'=>[new DefaultLanguage([
                        \'message\' => $this->trans(
                            \'The field %field_name% is required at least in your default language.\',
                            \'Admin.Notifications.Error\',
                            [
                                \'%field_name%\' => sprintf(
                                    \'"%s"\',
                                    $this->trans(\'Content\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\')
                                ),
                            ]
                        ),
                    ])],' . PHP_EOL;
                        $addFieldsBody .= '\'options\'=>[' . PHP_EOL;
                        $addFieldsBody .= '\'constraints\'=>[' . PHP_EOL;
                        $addFieldsBody .= 'new Regex([' . PHP_EOL;
                        $addFieldsBody .= '\'pattern\'=>\'/^[^<>;=#{}]*$/u\',' . PHP_EOL;
                        $addFieldsBody .= '\'message\'=>$this->trans(\'%s id invalid\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\', [])' . PHP_EOL;
                        $addFieldsBody .= ']),' . PHP_EOL;
                        $addFieldsBody .= '],' . PHP_EOL;
                        $addFieldsBody .= '],' . PHP_EOL;
                        $addFieldsBody .= "]" . PHP_EOL;
                        $addFieldsBody .= ')' . PHP_EOL;
                    } elseif ($item['field_type'] === 'DATETIME' || $item['field_type'] === 'DATE') {
                        $namespace->addUse('PrestaShopBundle\Form\Admin\Type\DatePickerType');
                        $addFieldsBody .= '->add(' . PHP_EOL;
                        $addFieldsBody .= "'" . $item['field_name'] . "'," . PHP_EOL;
                        $addFieldsBody .= "DatePickerType::class," . PHP_EOL;
                        $addFieldsBody .= "[" . PHP_EOL;
                        $addFieldsBody .= '\'label\'=>$this->trans(\'' . $item['field_name'] . '\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\', []),' . PHP_EOL;
                        $addFieldsBody .= '\'required\'=>true,' . PHP_EOL;
                        $addFieldsBody .= "]" . PHP_EOL;
                        $addFieldsBody .= ')' . PHP_EOL;
                    } else {
                        $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\TextType');
                        $addFieldsBody .= '->add(' . PHP_EOL;
                        $addFieldsBody .= "'" . $item['field_name'] . "'," . PHP_EOL;
                        $addFieldsBody .= "TextType::class," . PHP_EOL;
                        $addFieldsBody .= "[" . PHP_EOL;
                        $addFieldsBody .= '\'label\'=>$this->trans(\'' . $item['field_name'] . '\', \'Modules.' . $this->params['upper']['module_name'] . '.Admin\', []),' . PHP_EOL;
                        $addFieldsBody .= '\'required\'=>true,' . PHP_EOL;
                        $addFieldsBody .= "]" . PHP_EOL;
                        $addFieldsBody .= ')' . PHP_EOL;
                    }
                }
            }
            $buildForm->setBody($addFieldsBody . ';');
            $printer = new Printer;
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);
            $this->createDir('Form');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'Type.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'Type.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'Type.php', $code, FILE_APPEND);
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Form');
            $namespace->addUse('Doctrine\ORM\EntityManagerInterface');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface');
            $namespace->addUse($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Entity' . '\\' . $classModel);
            $namespace->addUse($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Repository' . '\\' . $classModel . 'Repository');
            $namespace->addUse('PrestaShopBundle\Entity\Repository\LangRepository');

            $class = $namespace->addClass($classModel . 'FormDataHandler')->addImplement('FormDataHandlerInterface');
            $class->addProperty(strtolower($classModel) . 'Repository')->setComment('@var ' . $classModel . 'Repository')->setPrivate();
            $class->addProperty('langRepository')->setComment('@var LangRepository')->setPrivate();
            $class->addProperty('entityManager')->setComment('@var  EntityManagerInterface')->setPrivate();
            $construct = $class->addMethod('__construct');
            $construct->addParameter(strtolower($classModel) . 'Repository')->setType($classModel . 'Repository');
            $construct->addParameter('langRepository')->setType('LangRepository');
            $construct->addParameter('entityManager')->setType('EntityManagerInterface');
            $constructBody = '        $this->' . strtolower($classModel) . 'Repository = $' . strtolower($classModel) . 'Repository;' . PHP_EOL;
            $constructBody .= '        $this->langRepository = $langRepository;' . PHP_EOL;
            $constructBody .= '        $this->entityManager = $entityManager;' . PHP_EOL;
            $construct->setBody($constructBody);
            $create = $class->addMethod('create');
            $create->addParameter('data')->setType('array');
            $create->addComment('@inheritdoc');
            $createBody = '        $' . strtolower($classModel) . '=new ' . $classModel . '();' . PHP_EOL;
            if (!empty($this->setters)) {
                foreach ($this->setters as $fieldName => $setterMethod) {
                    $createBody .= '$' . strtolower($classModel) . '->' . $setterMethod . '($data["' . $fieldName . '"]);' . PHP_EOL;
                }
                $createBody .= '$this->entityManager->persist($' . strtolower($classModel) . ');' . PHP_EOL;
                $createBody .= '$this->entityManager->flush();' . PHP_EOL;
                $createBody .= 'return $' . strtolower($classModel) . '->getId();' . PHP_EOL;
            }
            $create->setBody($createBody);
            $update = $class->addMethod('update');
            $update->addParameter('id');
            $update->addParameter('data')->setType('array');
            $update->addComment('@inheritdoc');
            $updateBody = '        $' . strtolower($classModel) . '=$this->' . strtolower($classModel) . 'Repository->find($id);' . PHP_EOL;
            if (!empty($this->setters)) {
                foreach ($this->setters as $fieldName => $setterMethod) {
                    $updateBody .= '$' . strtolower($classModel) . '->' . $setterMethod . '($data["' . $fieldName . '"]);' . PHP_EOL;
                }
                $updateBody .= '$this->entityManager->persist($' . strtolower($classModel) . ');' . PHP_EOL;
                $updateBody .= '$this->entityManager->flush();' . PHP_EOL;
                $updateBody .= 'return $' . strtolower($classModel) . '->getId();' . PHP_EOL;
            }
            $update->setBody($updateBody);
            $code = $printer->printNamespace($namespace);
            $this->createDir('Form');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataHandler.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataHandler.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataHandler.php', $code, FILE_APPEND);
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Form');
            $namespace->addUse('PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface');
            $namespace->addUse($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Repository' . '\\' . $classModel . 'Repository');

            $class = $namespace->addClass($classModel . 'FormDataProvider')->addImplement('FormDataProviderInterface');
            $class->addProperty(strtolower($classModel) . 'Repository')->setComment('@var ' . $classModel . 'Repository')->setPrivate();

            $construct = $class->addMethod('__construct');
            $construct->addParameter(strtolower($classModel) . 'Repository')->setType($classModel . 'Repository');
            $constructBody = '        $this->' . strtolower($classModel) . 'Repository = $' . strtolower($classModel) . 'Repository;' . PHP_EOL;
            $construct->setBody($constructBody);
            $getData = $class->addMethod('getData');
            $getData->addParameter(strtolower($classModel) . 'Id');
            $getData->addComment('@inheritdoc');
            $getDataBody = '        $' . strtolower($classModel) . '=$this->' . strtolower($classModel) . 'Repository->find($' . strtolower($classModel) . 'Id)' . ';' . PHP_EOL;
            if (!empty($this->getters)) {
                $getDataBody .= 'return [' . PHP_EOL;
                foreach ($this->getters as $fieldName => $getterMethod) {
                    $getDataBody .= "'" . $fieldName . "'=>$" . strtolower($classModel) . "->" . $getterMethod . "()," . PHP_EOL;
                }
                $getDataBody .= '];' . PHP_EOL;
            }
            $getData->setBody($getDataBody);
            $getDefaultData = $class->addMethod('getDefaultData');
            $getDefaultData->addComment('@inheritdoc');
            $getDefaultDataBody = '        ' . PHP_EOL;
            if (!empty($this->getters)) {
                $getDefaultDataBody .= 'return [' . PHP_EOL;
                foreach ($this->getters as $fieldName => $getterMethod) {
                    $getDefaultDataBody .= "'" . $fieldName . "'=>''," . PHP_EOL;
                }
                $getDefaultDataBody .= '];' . PHP_EOL;
            }
            $getDefaultData->setBody($getDefaultDataBody);
            $code = $printer->printNamespace($namespace);
            $this->createDir('Form');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataProvider.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataProvider.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Form/' . $classModel . 'FormDataProvider.php', $code, FILE_APPEND);
            return true;

        }
    }

    private function generateRepository()
    {
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }

            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Repository');
            $namespace->addUse('Doctrine\ORM\EntityRepository');
            $namespace->addUse('Doctrine\ORM\QueryBuilder');

            $class = $namespace->addClass($classModel . 'Repository')->addExtend(EntityRepository::class);
            $getRandom = $class->addMethod('getRandom');
            $getRandom->addParameter('langId')->setDefaultValue(0);
            $getRandom->addParameter('limit')->setDefaultValue(0);
            $getRandom->addComment('Since RAND() is not available by default in Doctrine and we haven\'t an extension that');
            $getRandom->addComment('adds it we perform the random fetch and sorting programmatically in PHP.');
            $getRandom->addComment('@param int $langId');
            $getRandom->addComment('@param int $limit');
            $getRandom->addComment('@return array');
            $getRandomBody = '        /** @var QueryBuilder $qb */' . PHP_EOL;
            $getRandomBody .= '$qb = $this->createQueryBuilder(\'q\')' . PHP_EOL;
            $getRandomBody .= '    ->addSelect(\'q\')' . PHP_EOL;
            if (!empty($this->langFields)) {
                $getRandomBody .= '    ->addSelect(\'ql\')' . PHP_EOL;
                $getRandomBody .= '    ->leftJoin(\'q.' . strtolower($classModel) . 'Langs\', \'ql\')' . PHP_EOL;
                $getRandomBody .= ';' . PHP_EOL;
                $getRandomBody .= 'if (0 !== $langId) {' . PHP_EOL;
                $getRandomBody .= '    $qb' . PHP_EOL;
                $getRandomBody .= '       ->andWhere(\'ql.lang = :langId\')' . PHP_EOL;
                $getRandomBody .= '       ->setParameter(\'langId\', $langId)' . PHP_EOL;
                $getRandomBody .= '    ;' . PHP_EOL;
                $getRandomBody .= '}' . PHP_EOL;
            } else {
                $getRandomBody .= ';' . PHP_EOL;
            }

            $getRandomBody .= '$ids = $this->getAllIds();' . PHP_EOL;
            $getRandomBody .= 'shuffle($ids);' . PHP_EOL;
            $getRandomBody .= 'if ($limit > 0) {' . PHP_EOL;
            $getRandomBody .= '    $ids = array_slice($ids, 0, $limit);' . PHP_EOL;
            $getRandomBody .= '}' . PHP_EOL;
            $getRandomBody .= '$qb' . PHP_EOL;
            $getRandomBody .= '    ->andWhere(\'q.id in (:ids)\')' . PHP_EOL;
            $getRandomBody .= '    ->setParameter(\'ids\', $ids)' . PHP_EOL;
            $getRandomBody .= ';' . PHP_EOL;
            $getRandomBody .= '$' . strtolower($classModel) . 's = $qb->getQuery()->getResult();' . PHP_EOL;
            $getRandomBody .= 'uasort($' . strtolower($classModel) . 's, function($a, $b) use ($ids) {' . PHP_EOL;
            $getRandomBody .= '    return array_search($a->getId(), $ids) - array_search($b->getId(), $ids);' . PHP_EOL;
            $getRandomBody .= '});' . PHP_EOL;
            $getRandomBody .= 'return $' . strtolower($classModel) . 's;' . PHP_EOL;
            $getRandom->setBody($getRandomBody);
            $getAllIds = $class->addMethod('getAllIds');
            $getAllIdsBody = '        /** @var QueryBuilder $qb */' . PHP_EOL;
            $getAllIdsBody .= '$qb = $this' . PHP_EOL;
            $getAllIdsBody .= '    ->createQueryBuilder(\'q\')' . PHP_EOL;
            $getAllIdsBody .= '    ->select(\'q.id\')' . PHP_EOL;
            $getAllIdsBody .= ';' . PHP_EOL;
            $getAllIdsBody .= '$' . strtolower($classModel) . 's = $qb->getQuery()->getScalarResult();' . PHP_EOL;
            $getAllIdsBody .= 'return array_map(function($' . strtolower($classModel) . ') {' . PHP_EOL;
            $getAllIdsBody .= '    return $' . strtolower($classModel) . '[\'id\'];' . PHP_EOL;
            $getAllIdsBody .= '}, $' . strtolower($classModel) . 's);' . PHP_EOL;
            $getAllIds->setBody($getAllIdsBody);
            $printer = new Printer;
            $code = $printer->printNamespace($namespace);

            $this->createDir('Repository');
            file_put_contents($this->module_dir . '/src/Repository/' . $classModel . 'Repository.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Repository/' . $classModel . 'Repository.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Repository/' . $classModel . 'Repository.php', $code, FILE_APPEND);

        }
        return true;
    }

    private function generateRoutesAndServices()
    {
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            $content = file_get_contents($this->base_dir . '/samples/gridServices.yml');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('quote', strtolower($classModel), $content);
            $content = str_replace('Quote', $classModel, $content);
            $this->addService($content);
            $content = file_get_contents($this->base_dir . '/samples/gridCommon.yml');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('quote', strtolower($classModel), $content);
            $content = str_replace('Quote', $classModel, $content);
            $this->addService($content, 'common');
            $content = file_get_contents($this->base_dir . '/samples/gridRoutes.yml');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('quote', strtolower($classModel), $content);
            $content = str_replace('Quote', $classModel, $content);
            $this->addService($content, 'routes');
        }
        return true;
    }

    private function generateController()
    {
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            $content = file_get_contents($this->base_dir . '/samples/gridController.php');
            if(!empty($this->langFields)){
                $content = str_replace('/*use PrestaShop\Module\DemoDoctrine\Entity\QuoteLang;*/', 'use PrestaShop\Module\DemoDoctrine\Entity\QuoteLang;', $content);
            }else{
                $content = str_replace('/*use PrestaShop\Module\DemoDoctrine\Entity\QuoteLang;*/', '', $content);
            }
            $content = $this->replaceStandardStrings($content);

            $content = str_replace('quote', strtolower($classModel), $content);
            $content = str_replace('Quote', $classModel, $content);

            $fs = new Filesystem();
            $this->createDir('Controller/Admin');
            $fs->appendToFile($this->module_dir . '/src/Controller/Admin/' . $classModel . 'Controller.php', $content);
        }
        return true;
    }

    private function generateViews()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->base_dir . '/samples/views/templates/grid');
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            foreach ($finder as $file) {
                $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
                $path = str_replace('grid', 'admin', $path);
                $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
                $content = file_get_contents($this->module_dir . $path);
                $content = $this->replaceStandardStrings($content);
                $content = str_replace('quote', strtolower($classModel), $content);
                $content = str_replace('Quote', $classModel, $content);
                file_put_contents($this->module_dir . $path, $content);
            }
        }
        return true;
    }

    private function generateJsfiles()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->base_dir . '/samples/js');
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            foreach ($finder as $file) {
                $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
                $path = str_replace('quotes', strtolower($classModel).'s', $path);
                $path = str_replace('.webpack', 'webpack', $path);
                $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
                $content = file_get_contents($this->module_dir . $path);
                $content = $this->replaceStandardStrings($content);
                $content = str_replace('quote', strtolower($classModel), $content);
                $content = str_replace('Quote', $classModel, $content);
                file_put_contents($this->module_dir . $path, $content);
            }
        }

        return true;
    }
    private function generateCompiledJs()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->base_dir . '/samples/src/gridJs');
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            foreach ($finder as $file) {
                $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
                $path = str_replace('quotes', strtolower($classModel).'s', $path);
                $path = str_replace('.webpack', 'webpack', $path);
                $path = str_replace('src\gridJs', 'views\js', $path);

                $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
                $content = file_get_contents($this->module_dir . $path);
                $content = $this->replaceStandardStrings($content);
                $content = str_replace('quote', strtolower($classModel), $content);
                $content = str_replace('Quote', $classModel, $content);
                file_put_contents($this->module_dir . $path, $content);
            }
        }
        return true;
    }
    private function generateCompiledCss()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->base_dir . '/samples/src/gridCss');
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            foreach ($finder as $file) {
                $path = str_replace($this->base_dir . DIRECTORY_SEPARATOR . 'samples', '', $file->getRealPath());
                $path = str_replace('quotes', strtolower($classModel).'s', $path);
                $path = str_replace('.webpack', 'webpack', $path);
                $path = str_replace('src\gridCss', 'views\css', $path);
                $fileSystem->copy($file->getRealPath(), $this->module_dir . $path);
                $content = file_get_contents($this->module_dir . $path);
                $content = $this->replaceStandardStrings($content);
                $content = str_replace('quote', strtolower($classModel), $content);
                $content = str_replace('Quote', $classModel, $content);
                file_put_contents($this->module_dir . $path, $content);
            }
        }
        return true;
    }

    private function setGridTab()
    {
        $const='';
        $tabs="    /**
     * Returns module tabs data
     *
     * @param Module /+module Module object
     *
     * @return array Module tabs data
     */
    public function getModuleTabs(Module /+module)
    {
        return [
            [
                'name' => /+module->l('EVOGROUP', __CLASS__),
                'parent' => 0,
                'class_name' => self::CONTROLLER_EVOGROUP,
                'module_tab' => false,
                'main_tab' => true,
            ],

            [
                'name' => /+module->name,
                'parent' => self::CONTROLLER_EVOGROUP,
                'class_name' => self::CONTROLLER_MODULE,
                'module_tab' => true,
                'icon' => 'repeat',
            ],".PHP_EOL;
        $const.="const CONTROLLER_EVOGROUP = 'AdminEvoGroup';".PHP_EOL;
        $const.="const CONTROLLER_MODULE = 'Admin".$this->params['upper']['module_name']."';".PHP_EOL;
        foreach ($this->module_data['models'] as $index => $modelData) {
            if (empty($classModel = $modelData['class'])) {
                continue;
            }
            $const.="const CONTROLLER_".strtoupper($classModel)." = 'Admin".$classModel."';".PHP_EOL;
            $this->addLegacyController("Admin".$classModel);
            $tabs.="            [
                'name' => /+module->l('".$modelData['table']."', __CLASS__),
                'parent' => self::CONTROLLER_MODULE,
                'class_name' => self::CONTROLLER_".strtoupper($classModel).",
                'module_tab' => false,
                'modules_tab' => true,
                'icon' => 'repeat',
            ],".PHP_EOL;
            $tabs = $this->replaceStandardStrings($tabs);
            $tabs = str_replace('quote', strtolower($classModel), $tabs);
            $tabs = str_replace('Quote', $classModel, $tabs);

        }
        $tabs.="        ];
    }".PHP_EOL;
        $tabs.="    /**
     * Function used to install module tabs
     * Collects error messages if install process is not successful
     *
     * @param Module /+module Module object
     *
     * @return bool Tabs installed successfully or not
     */
    public function installTabs(Module /+module)
    {
        /+tabs = /+this->getModuleTabs(/+module);

        if (empty(/+tabs)) {
            return true;
        }

        foreach (/+tabs as /+tab) {
            if (Tab::getIdFromClassName(/+tab['class_name'])) {
                continue;
            }

            /+id_parent = is_int(/+tab['parent']) ? (int) /+tab['parent'] : (int) Tab::getIdFromClassName(/+tab['parent']);
            /+icon = /+tab['icon'] ?? null;
            if (!/+this->installTab(/+tab['name'], (int) /+id_parent, /+tab['class_name'], /+module->name, /+icon)) {
                /+this->errors[] = sprintf(/+module->l('Could not install %s tab', __CLASS__), /+tab['name']);

                return false;
            }
        }

        return true;
    }

    /**
     * Registers BackOffice tabs
     *
     * @param string /+name Tab name
     * @param int /+id_parent Parent tab ID
     * @param string /+class_name Tab controller class name
     * @param string /+moduleName Module name
     * @param null /+icon
     *
     * @return bool|int Tab could not be installed | Installed tab ID
     */
    private function installTab(/+name, /+id_parent, /+class_name, /+moduleName, /+icon = null)
    {
        if (!Tab::getIdFromClassName(/+class_name)) {
            /+module_tab = new Tab();
            /+languages = Language::getLanguages(true);

            foreach (/+languages as /+language) {
                /+module_tab->name[(int) /+language['id_lang']] = /+name;
            }

            /+module_tab->class_name = /+class_name;
            /+module_tab->id_parent = (int) /+id_parent;
            /+module_tab->module = /+moduleName;
            /+module_tab->icon = /+icon;

            if (/+module_tab->add()) {
                return (int) /+module_tab->id;
            }
        }

        return false;
    }

    /**
     * Removes BackOffice tab registration
     *
     * @param string /+class_name Tab controller class name
     *
     * @return bool Tab uninstalled successfully or not
     */
    private function uninstallTab(/+class_name)
    {
        if (/+id_tab = (int) Tab::getIdFromClassName(/+class_name)) {
            /+tab = new Tab((int) /+id_tab);

            return /+tab->delete();
        }

        return true;
    }

    /**
     * Function used to uninstall module tabs
     * Collects error messages if uninstall process is not successful
     *
     * @param Module /+module Module object
     *
     * @return bool Tabs uninstalled successfully or not
     */
    public function uninstallTabs(Module /+module)
    {
        /+tabs = /+this->getModuleTabs(/+module);

        if (empty(/+tabs)) {
            return true;
        }

        /+parent_tabs = [];
        /+modules_tab = [];

        foreach (/+tabs as /+tab) {
            if (!/+tab['module_tab']) {
                if (isset(/+tab['modules_tab'])) {
                    /+modules_tab = /+tab;
                    /+parent_tabs[] = /+tab;
                } else {
                    /+parent_tabs[] = /+tab;
                }

                continue;
            }

            if (!/+this->uninstallTab(/+tab['class_name'])) {
                /+this->errors[] = sprintf(/+module->l('Could not uninstall %s tab', __CLASS__), /+tab['name']);

                return false;
            }
        }

        if (!empty(/+modules_tab)) {
            if (Tab::getNbTabs(Tab::getIdFromClassName(/+modules_tab['class_name']))) {
                return true; // Invertus modules tab is not empty
            }

            foreach (/+parent_tabs as /+tab) {
                if (!/+this->uninstallTab(/+tab['class_name'])) {
                    /+this->errors[] = sprintf(/+module->l('Could not uninstall %s tab', __CLASS__), /+tab['name']);

                    return false;
                }
            }
        }

        return true;
    }".PHP_EOL;
        $const = str_replace('/*', '$', $const);
        $tabs = str_replace('/+', '$', $tabs);
        $this->tabs['const']=$const;
        $this->tabs['tabs']=$tabs;

        return true;
    }

    private function generateSQl()
    {
        $sql = '';
        $sql_uninstal = '';
        $sql_shop = '';
        $sql_lang = '';
        foreach ($this->module_data['models'] as $index => $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }
            $params = $this->getParams();
            $namespace = new PhpNamespace($params['upper']['company_name'] . '\\' . 'Module' . '\\' . $params['upper']['module_name'] . '\\' . 'Model');
            $namespace->addUse('ObjectModel');
            $class = $namespace->addClass($modelData['class']);
            $class->addExtend('ObjectModel');

            $fieldsData = $this->renderModel($modelData, $class, false);

            $fieldsDataSql = $fieldsData['sql'];
            $fieldsDataUninstall = $fieldsData['sql_uninstall'];
            $fieldsDataSql_shop = $fieldsData['sql_shop'];
            $fieldsDataSql_lang = $fieldsData['sql_lang'];
            $fieldsDataSql .= 'PRIMARY KEY  (`' . $modelData['primary'] . '`)' . PHP_EOL;
            $fieldsDataSql .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
            $fieldsDataSql = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataSql);
            $sql .= '$sql[]=' . $fieldsDataSql . "';" . PHP_EOL;
            $fieldsDataUninstall = str_replace(array("/*", "*/"), array("'.", ".'"), $fieldsDataUninstall);
            $sql_uninstal .= $fieldsDataUninstall . PHP_EOL;

            if (!empty($fieldsDataSql_shop)) {
                $fieldsDataSql_shop .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_shop = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_shop);
                $sql_shop .= '$sql[]=' . $fieldsDataSql_shop . "';" . PHP_EOL;
            }
            if (!empty($fieldsDataSql_lang)) {
                $fieldsDataSql_lang .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_lang`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_lang = str_replace(array("/*", "*/", 'NUL)'), array("'.", ".'", 'NULL)'), $fieldsDataSql_lang);
                $sql_lang .= '$sql[]=' . $fieldsDataSql_lang . "';" . PHP_EOL;
            }
        }
        $this->installSql[] = $sql . $sql_lang . $sql_shop;
        $this->unInstallSql[] = $sql_uninstal;

        return true;
    }

    /**
     * @param string $controllerName
     * @return bool
     */
    private function addLegacyController(string $controllerName)
    {
        $class = new ClassType($controllerName.'Controller');
        $class->setExtends('ModuleAdminController');
        $method=$class->addMethod('__construct');
        $body='     parent::__construct();
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink(\''.str_replace('Admin', 'Admin'.$this->params['upper']['module_name'], $controllerName).'\'));'.PHP_EOL;
        $method->setBody($body);
        $folder = $this->module_dir . '/controllers/admin';
        if (!is_dir($folder) && !@mkdir($folder, 0777, true) && !is_dir($folder)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $folder));
        }
        $printer = new Printer;
        $printer->setTypeResolving(false);
        $code = $printer->printClass($class);
        file_put_contents($this->module_dir . '/controllers/'.$controllerName.'Controller' . '.php', '<?php declare(strict_types=1);');
        file_put_contents($this->module_dir . '/controllers/'.$controllerName.'Controller' . '.php', PHP_EOL, FILE_APPEND);
        file_put_contents($this->module_dir . '/controllers/'.$controllerName.'Controller' . '.php', $code, FILE_APPEND);
        return true;
    }

}

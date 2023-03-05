<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ModuleGenerator
{
    /**
     * List of field types.
     */
    public const TYPE_INT = 1;
    public const TYPE_BOOL = 2;
    public const TYPE_FLOAT = 4;
    public const TYPE_DATE = 5;
    public const TYPE_HTML = 6;
    public const TYPE_NOTHING = 7;
    public const TYPE_SQL = 8;
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
    public function slugify($string, $delimiter = '_'): string
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
        $content = str_replace(['$companyNameLower', '$moduleName', '$nameSpace', '$companyName', '$contact_email'], [$this->params['lower']['company_name'], $this->params['lower']['module_name'], $this->params['upper']['module_name'], $this->params['upper']['company_name'], $this->module_data['email']], $content);
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

        $content = str_replace(['$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'], [$this->params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $this->params['upper']['company_name']], $content);

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

        $content = str_replace(['$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'], [$this->params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $this->params['upper']['company_name']], $content);

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
        $content = str_replace(['$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'], [$this->params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $this->params['upper']['company_name']], $content);

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.php', $content);

        return true;
    }

    private function getParams()
    {
        $module_name = $this->module_data['module_name'];
        $company_name = $this->module_data['company_name'];

        return ['lower' => ['module_name' => $this->slugify($module_name), 'company_name' => $this->slugify($company_name)], 'upper' => ['module_name' => ucfirst(str_replace(' ', '', $module_name)), 'company_name' => ucfirst(trim(str_replace(' ', '', $company_name)))]];
    }

    public function copyStansardDir()
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();
        $dirs = [
            'views',
            'sql',
            'upgrade',
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

        $content = $this->replaceStandardStrings($content);

        $content = str_replace(['Moduleclass', 'moduleclass', 'module_author', 'Diplay name', 'module_description', 'MODULECLASS'], [$this->params['upper']['module_name'], $this->params['lower']['module_name'], $this->params['upper']['company_name'], $this->module_data['display_name'], $this->module_data['description'], strtoupper($this->params['lower']['module_name'])], $content);
        file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
        if (isset($this->module_data['hooks']) && !empty($hooks = $this->module_data['hooks'])) {
            $result = [];
            array_walk_recursive($hooks, function ($v) use (&$result) {
                $result[] = $v;
            });
            $register_hooks = '';
            $class = new ClassType('demo');
            foreach ($result as $hook) {
                $register_hooks .= " && method->registerHook('" . $hook . "')\n";
                $lowerHookName = $hook;
                $hook = ucfirst($hook);
                if (strpos($content, 'hook' . $hook) === false) {
                    $method = $class->addMethod('hook' . $hook);
                    $method->addParameter('params');
                    if (!empty($this->module_data['hooksContents']) && isset($this->module_data['hooksContents'][$lowerHookName]) && !empty($hookContent = $this->module_data['hooksContents'][$lowerHookName])) {
                        $hookContent = str_replace(["/*", "*/", "/+"], ["", "", "$"], $hookContent);
                        $method->setBody($hookContent);
                    }
                }
            }
            if (!empty($this->module_data['query']) && !empty($query = $this->module_data['query'])) {
                foreach ($query as $ind => $qb) {
                    $method = $class->addMethod('updateExtra' . $ind . 'Field');
                    $method->addParameter(strtolower($ind) . 'Id');
                    $qb = str_replace(["/*", "*/", "/+"], ["", "", "$"], $qb);
                    $method->setBody($qb);
                }
            }
            if (!empty($this->module_data['presenter']['product']) && !empty($presenters = $this->module_data['presenter']['product'])) {
                foreach ($presenters as $fn => $body) {
                    $method = $class->addMethod($fn);
                    $method->addParameter('value');
                    $body = str_replace(["/*", "*/", "/+"], ["", "", "$"], $body);
                    $method->setBody($body);
                }
            }
            $content = str_replace("registerHook('backOfficeHeader')", "registerHook('actionAdminControllerSetMedia')\n" . $register_hooks, $content);
            $content = str_replace("registerHook('header')", "registerHook('actionFrontControllerSetMedia')\n" . $register_hooks, $content);
            $content = str_replace("method", '$this', $content);

            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . $this->module_data['module_name'] . '.php', $content);

            $printer = new Printer();
            $code = $printer->printClass($class);

            $arr = explode("\n", $code);
            unset($arr[0], $arr[1]);
            $code = implode("\n", $arr);
            $lines = file($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
            array_pop($lines);

            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', implode('', $lines));
            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $code, FILE_APPEND);
        }

        if (!empty($this->module_data['use']) && !empty($query = $this->module_data['use'])) {
            $useContent = '';

            foreach ($this->module_data['use'] as $objectName => $useData) {
                foreach ($useData as $use) {
                    $useContent .= 'use ' . $use . ';' . PHP_EOL;
                }
            }
            $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
            $content = str_replace('/** add uses */', $useContent, $content);
            $content = str_replace('hookHeader', 'hookActionFrontControllerSetMedia', $content);
            $content = str_replace('hookBackOfficeHeader', 'hookActionAdminControllerSetMedia', $content);
            file_put_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php', $content);
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
        $content = str_replace('Moduleclass', $this->params['upper']['module_name'], $content);
        $content = str_replace('moduleclass', $this->params['lower']['module_name'], $content);
        $content = str_replace('module_author', $this->params['upper']['company_name'], $content);
        $content = str_replace('company_name', $this->params['lower']['company_name'], $content);
        $content = str_replace('EvoGroup', $this->params['upper']['company_name'], $content);
        $content = str_replace('module_class', $this->params['lower']['module_name'], $content);
        $content = str_replace('MODULE_CLASS', strtoupper($this->params['lower']['module_name']), $content);
        $content = str_replace('MODULECLASS', strtoupper($this->params['lower']['module_name']), $content);

        return $content;
    }

    private function addService(string $content)
    {
        $fs = new Filesystem();
        $fs->appendToFile($this->module_dir . '/config/services.yml', $content);

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
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Model');
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
            $fieldsDataSql = str_replace(["/*", "*/"], ["'.", ".'"], $fieldsDataSql);
            $sql .= '$sql[]=' . $fieldsDataSql . "';" . PHP_EOL;
            $fieldsDataUninstall = str_replace(["/*", "*/"], ["'.", ".'"], $fieldsDataUninstall);
            $sql_uninstal .= $fieldsDataUninstall . PHP_EOL;

            if (!empty($fieldsDataSql_shop)) {
                $fieldsDataSql_shop .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_shop = str_replace(["/*", "*/", 'NUL)'], ["'.", ".'", 'NULL)'], $fieldsDataSql_shop);
                $sql_shop .= '$sql[]=' . $fieldsDataSql_shop . "';" . PHP_EOL;
            }
            if (!empty($fieldsDataSql_lang)) {
                $fieldsDataSql_lang .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $fieldsDataSql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_lang`, `id_shop`) USING BTREE;' . PHP_EOL;
                $fieldsDataSql_lang = str_replace(["/*", "*/", 'NUL)'], ["'.", ".'", 'NULL)'], $fieldsDataSql_lang);
                $sql_lang .= '$sql[]=' . $fieldsDataSql_lang . "';" . PHP_EOL;
            }

            $definition = [
                'table' => $modelData['table'],
                'primary' => $modelData['primary'] ?? 'id_' . $modelData['table'],
            ];

            if (!empty($fieldsDataSql_lang)) {
                $definition['multilang'] = true;
            }
            if (!empty($fieldsDataSql_shop)) {
                $definition['multilang_shop'] = true;
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
                if ($modelObject == 'CmsPage') {
                    $relatedField = 'id_cmspage';
                }
                if ($modelObject == 'CmsPageCategory') {
                    $relatedField = 'id_cmspagecategory';
                }
                if ($modelObject == 'CartRule') {
                    $relatedField = 'id_cartrule';
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
            $printer = new Printer();
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);
            $code = str_replace(["'/*", "*/'"], '', $code);
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', '<?php');
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Model/' . $modelData['class'] . '.php', $code, FILE_APPEND);
        }
        $executionLoop = 'foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}';
        //install
        $installContent = file($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'install_vg.php');
        $installContent[26] = $sql . $sql_lang . $sql_shop;
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', implode("", $installContent));
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', PHP_EOL, FILE_APPEND);
        if (isset($modelObject) && $modelObject === 'CartRule') {
            $content = '$orig =  _PS_BO_ALL_THEMES_DIR_.\'default/template/controllers/cart_rules/\';' . PHP_EOL;
            $content .= '$dest = _PS_ROOT_DIR_.\'/override/controllers/admin/templates/cart_rules/\';' . PHP_EOL;
            $content .= 'if(!is_dir($dest)){' . PHP_EOL;
            $content .= chr(9) . 'mkdir ($dest,0777);' . PHP_EOL;
            $content .= '}' . PHP_EOL;
            $content .= '$dir = dir($orig);' . PHP_EOL;
            $content .= 'while ($entry=$dir->read()) {' . PHP_EOL;
            $content .= chr(9) . '$pathOrig = "$orig/$entry";' . PHP_EOL;
            $content .= chr(9) . '$pathDest = "$dest/$entry";' . PHP_EOL;
            $content .= chr(9) . 'if (is_file($pathOrig) and ($pathDest<>\'\') and ($fp=fopen($pathOrig,\'rb\')) and in_array($entry, array(\'form.tpl\', \'index.php\'))) {' . PHP_EOL;
            $content .= chr(9) . chr(9) . '$buf = fread($fp,filesize($pathOrig));' . PHP_EOL;
            $content .= chr(9) . chr(9) . '$cop = fopen($pathDest,\'w\');' . PHP_EOL;
            $content .= chr(9) . chr(9) . 'fputs ($cop,$buf);' . PHP_EOL;
            $content .= chr(9) . chr(9) . 'fclose ($cop);' . PHP_EOL;
            $content .= chr(9) . chr(9) . 'fclose ($fp);' . PHP_EOL;
            $content .= chr(9) . '}' . PHP_EOL;
            $content .= '}' . PHP_EOL;
            $content .= '$dir->close();' . PHP_EOL;
            $content .= '$str = "{include file=\'controllers/cart_rules/informations.tpl\'}";' . PHP_EOL;
            $content .= '$replace = $str;' . PHP_EOL;
            $content .= '$replace.= "{Hook::exec(\'DisplayExtraFieldCarteRule\')}";' . PHP_EOL;
            $content .= '$file = _PS_ROOT_DIR_.\'/override/controllers/admin/templates/cart_rules/form.tpl\';' . PHP_EOL;
            $content .= '$addContentHook = file_get_contents($file);' . PHP_EOL;
            $content .= '$addContentHook = str_replace($str, $replace, $addContentHook);' . PHP_EOL;
            $content .= 'file_put_contents($file, $addContentHook);' . PHP_EOL;
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $content, FILE_APPEND);
        }
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $executionLoop, FILE_APPEND);
        //uninstall
        $uninstallContent = file($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'install_vg.php');
        $uninstallContent[26] = $sql_uninstal;
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', implode("", $uninstallContent));
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', PHP_EOL, FILE_APPEND);
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', $executionLoop, FILE_APPEND);

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
            $is_lang_fields = !empty($fieldData['is_lang']) && $fieldData['is_lang'] !== '' && $fieldData['is_lang'] !== null;
            if ($is_shop_fields && !$is_lang_fields) {
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
                    } elseif (($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT') && !$is_lang_fields) {
                        $size = $fieldsDef[$index]['size'] ?? 255;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 20.6);
                        }
                        $size = $size ?? 20.6;
                        $size = str_replace('.', ',', $size);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT') && !$is_lang_fields) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN')) {
                        if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                            $size = ($fieldData['field_length'] ?? 1);
                        }
                        $size = $size ?? 1;
                        $sql_shop .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
                    } elseif (($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME')) {
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '  ' . $separator . PHP_EOL;
                    } elseif (!$is_lang_fields) {
                        $fieldData['field_length'] = str_replace('.', ',', $fieldData['field_length']);
                        $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')' . $nullableCondition . $default_value . ',' . PHP_EOL;
                    }
                }

                $firstShopIteration++;
            }


            $in_two_table = !$is_lang_fields;
            if ($is_lang_fields) {
                $fieldsDef[$index]['lang'] = true;

                if ($firstLangIteration == 1) {
                    $sql_lang .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` (' . PHP_EOL;
                    $sql_uninstall .= '$sql[]=\'DROP TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang`;\';' . PHP_EOL;
                    $sql_lang .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_lang` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
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

    public function generateModelCustomImages()
    {
        if (empty($this->module_data['imageFormModels'])) {
            return false;
        }
        $this->module_data['hooks'] = [];
        foreach ($this->module_data['imageFormModels'] as $modelData) {
            if (empty($modelData['class'])) {
                return false;
            }
            if (empty($modelData['fields'])) {
                return false;
            }
            $this->module_data['hooks'][strtolower($modelData['class'])] = [];
            if (is_array($modelData['fields']) && !empty($modelData['fields'])) {
                if ($modelData['class'] === 'Category') {
                    $this->modifyQueryBuilder($modelData);
                    $this->addUploader($modelData);
                    $this->addController($modelData);
                    $this->addRoutes($modelData);
                    $this->addHandler($modelData);
                    $this->addProvider($modelData);
                    $this->addTemplates($modelData);
                    $this->addServices($modelData);
                }
                $this->addImageTypes($modelData);
                $this->removeImageTypes($modelData);
            }
        }
        return true;
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
        $tmpArray = [];
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
        if ($classModel == 'CartRule') {
            $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['displayExtraFieldCarteRule', 'actionObjectCartRuleUpdateAfter', 'actionObjectCartRuleAddAfter']);
            $this->module_data['use'][strtolower($classModel)][$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Model' . '\\' . 'ExtraCartRuleFields'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Model' . '\\' . 'ExtraCartRuleFields';
            $contentForTemplate = "";
            foreach ($this->module_data['hooks'][strtolower($classModel)] as $hook) {
                if ($hook == 'displayExtraFieldCarteRule') {
                    $extra_content = '$cartRuleFields = ExtraCartRuleFields::getExtraCartRuleFieldsByCartRuleId((int)Tools::getValue(\'id_cart_rule\'));' . PHP_EOL;
                    $extra_content .= '$this->context->smarty->assign([' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        $extra_content .= chr(9) . '"' . $item['column_name'] . '" => $cartRuleFields->' . $item['column_name'] . ',' . PHP_EOL;
                        $contentForTemplate .= "<div class=\"form-group\">" . PHP_EOL;
                        $contentForTemplate .= chr(9) . "<label class=\"control-label col-lg-3\">{l s='" . $item['column_name'] . "' mod='" . $this->params['lower']['module_name'] . "'}</label>" . PHP_EOL;
                        $contentForTemplate .= chr(9) . "<div class=\"col-lg-8\">" . PHP_EOL;
                        if ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                            $contentForTemplate .= chr(9) . "<span class=\"switch prestashop-switch fixed-width-lg\">" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "<input type=\"radio\" name=\"" . $item['column_name'] . "\" id=\"" . $item['column_name'] . "_on\" value=\"1\"{if \$" . $item['column_name'] . " == 1} checked=\"checked\"{/if}>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "<label class=\"t\" for=\"" . $item['column_name'] . "_on\">{l s='Yes' mod='" . $this->params['lower']['module_name'] . "'}</label>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "<input type=\"radio\" name=\"" . $item['column_name'] . "\" id=\"" . $item['column_name'] . "_off\" value=\"0\"{if \$" . $item['column_name'] . " == 0} checked=\"checked\"{/if}>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "<label class=\"t\" for=\"" . $item['column_name'] . "_off\">{l s='No' mod='" . $this->params['lower']['module_name'] . "'}</label>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "<a class=\"slide-button btn\"></a>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . "</span>" . PHP_EOL;
                        } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                            $contentForTemplate .= chr(9) . chr(9) . "<div class=\"input-group\">" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . chr(9) . "<input type=\"text\" name=\"" . $item['column_name'] . "\" value=\"{\$" . $item['column_name'] . "}\" class=\"datepicker input-medium\">" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . chr(9) . "<span class=\"input-group-addon\"><i class=\"icon-calendar-empty\"></i></span>" . PHP_EOL;
                            $contentForTemplate .= chr(9) . chr(9) . "</div>" . PHP_EOL;
                        } else {
                            $contentForTemplate .= chr(9) . chr(9) . "<input type=\"text\" name=\"" . $item['column_name'] . "\" value=\"{\$" . $item['column_name'] . "}\">" . PHP_EOL;
                        }
                        $contentForTemplate .= chr(9) . "</div>" . PHP_EOL;
                        $contentForTemplate .= "</div>" . PHP_EOL;
                    }
                    $extra_content .= ']);' . PHP_EOL;
                    $extra_content .= 'return $this->display(__FILE__, \'views/templates/admin/extraCartRuleFields.tpl\');' . PHP_EOL;

                    $this->module_data['hooksContents'][$hook] = $extra_content;
                }
                if (in_array($hook, ['actionObjectCartRuleUpdateAfter', 'actionObjectCartRuleAddAfter'])) {
                    $extra_content = "";

                    foreach ($fields as $index => $item) {
                        $extra_content .= '$form_data["' . $item['column_name'] . '"] = Tools::getValue("' . $item['column_name'] . '");' . PHP_EOL;
                    }
                    $extra_content .= 'Extra' . $classModel . 'Fields::SetExtra' . $classModel . 'FieldsByCartRuleId((int)$params["object"]->id, $form_data);' . PHP_EOL;

                    $this->module_data['hooksContents'][$hook] = $extra_content;
                }
            }

            file_put_contents($this->module_dir . '/views/templates/admin/extraCartRuleFields.tpl', $contentForTemplate);
        }
        if (in_array($classModel, ['Category', 'Customer', 'CmsPage', 'CmsPageCategory'])) {
            $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['action' . $classModel . 'FormBuilderModifier', 'actionAfterCreate' . $classModel . 'FormHandler', 'actionAfterUpdate' . $classModel . 'FormHandler']);
            if ($classModel === 'Customer') {
                $this->module_data['hooks'][strtolower($classModel)] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['additional' . $classModel . 'FormFields', 'validate' . $classModel . 'FormFields', 'action' . $classModel . 'AccountUpdate', 'action' . $classModel . 'AccountAdd']);
            }
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
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . str_replace("_", " ", $item['column_name']) . '\'),' . PHP_EOL;
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
                    $addFieldsBody .= '\'label\'=>$this->module->l(\'' . str_replace("_", " ", $item['column_name']) . '\'),' . PHP_EOL;
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
            $printer = new Printer();
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);


            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', $code, FILE_APPEND);
            $use[$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier';

            $formBuilderContent = '$' . strtolower($classModel) . 'FormBuilderModifier= new ' . $classModel . 'FormBuilderModifier(' . PHP_EOL;
            $classModelFormBuilderModifierNameSpace = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . "\Module" . '\\' . $this->params['upper']['module_name'] . '\\' . "Grid" . '\\' . $classModel . "FormBuilderModifier");

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

            if ($classModel === 'Customer') {
                $formFieldsContent = '$extra' . $classModel . 'Fields = Extra' . $classModel . 'Fields::getExtra' . $classModel . 'FieldsBy' . $classModel . 'Id(' . PHP_EOL;
                $formFieldsContent .= chr(9) . '(int)Context::getContext()->' . strtolower($classModel) . '->id' . PHP_EOL;
                $formFieldsContent .= ');' . PHP_EOL;
                $formFieldsContent .= '$extra_fields = array();' . PHP_EOL;
                foreach ($fields as $index => $item) {
                    $formFieldsContent .= '$extra_fields["' . $item['column_name'] . '"] = (new FormField)' . PHP_EOL;
                    $formFieldsContent .= chr(9) . '->setName("' . $item['column_name'] . '")' . PHP_EOL;

                    if ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                        $formFieldsContent .= chr(9) . '->setType(\'radio-buttons\')' . PHP_EOL;
                        $formFieldsContent .= chr(9) . '->addAvailableValue(0, \'No\')' . PHP_EOL;
                        $formFieldsContent .= chr(9) . '->addAvailableValue(1, \'Yes\')' . PHP_EOL;
                    } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                        $formFieldsContent .= chr(9) . '->setType(\'date\')' . PHP_EOL;
                        PHP_EOL;
                    } elseif ($item['column_type'] === 'INT') {
                        $formFieldsContent .= chr(9) . '->setType(\'number\')' . PHP_EOL;
                        PHP_EOL;
                    } else {
                        $formFieldsContent .= chr(9) . '->setType(\'text\')' . PHP_EOL;
                    }

                    $formFieldsContent .= chr(9) . '->setValue($extra' . $classModel . 'Fields->' . $item['column_name'] . ')' . PHP_EOL;
                    $formFieldsContent .= chr(9) . ' ->setLabel($this->l(\'' . $item['column_name'] . '\'));' . PHP_EOL;
                }
                $formFieldsContent .= 'return $extra_fields;' . PHP_EOL;

                $this->module_data['hooksContents']['additional' . $classModel . 'FormFields'] = $formFieldsContent;

                $validateFormFieldsContent = '$module_fields = $params[\'fields\'];' . PHP_EOL;

                foreach ($fields as $index => $item) {
                    $i = $index - 1;
                    if ($item['column_type'] == "INT") {
                        $validateFormFieldsContent .= 'if (!is_numeric($module_fields[' . $i . ']->getValue())) {' . PHP_EOL;
                        $validateFormFieldsContent .= chr(9) . '$module_fields[' . $i . ']->addError(' . PHP_EOL;
                        $validateFormFieldsContent .= chr(9) . chr(9) . '$this->l(\'Numeric value only\')' . PHP_EOL;
                        $validateFormFieldsContent .= chr(9) . ');' . PHP_EOL;
                        $validateFormFieldsContent .= '}' . PHP_EOL;
                    }
                }

                $validateFormFieldsContent .= 'return array(' . PHP_EOL;
                $validateFormFieldsContent .= chr(9) . '$module_fields' . PHP_EOL;
                $validateFormFieldsContent .= ');' . PHP_EOL;

                $this->module_data['hooksContents']['validate' . $classModel . 'FormFields'] = $validateFormFieldsContent;

                $this->module_data['use'][strtolower($classModel)] = array_merge($this->module_data['use'][strtolower($classModel)], $use);

                $accountUpdateBody = 'if (!isset($params[\'customer\']) || !isset($params[\'customer\']->id)) {' . PHP_EOL;

                $accountUpdateBody .= chr(9) . 'return;' . PHP_EOL;

                $accountUpdateBody .= '}' . PHP_EOL;

                $accountUpdateBody .= '$id = (int)$params[\'customer\']->id;' . PHP_EOL;

                $accountAddBody = 'if (!isset($params[\'newCustomer\']) || !isset($params[\'newCustomer\']->id)) {' . PHP_EOL;

                $accountAddBody .= chr(9) . 'return;' . PHP_EOL;

                $accountAddBody .= '}' . PHP_EOL;

                $accountAddBody .= '$id = (int)$params[\'newCustomer\']->id;' . PHP_EOL;

                $accountSaveBody = "";

                foreach ($fields as $index => $item) {
                    $accountSaveBody .= '$form_data[\'' . $item['column_name'] . '\'] = Tools::getValue(\'' . $item['column_name'] . '\');' . PHP_EOL;
                }

                $accountSaveBody .= 'Extra' . $classModel . 'Fields::SetExtra' . $classModel . 'FieldsBy' . $classModel . 'Id((int)$id, $form_data);' . PHP_EOL;

                $this->module_data['hooksContents']['action' . $classModel . 'AccountUpdate'] = $accountUpdateBody . $accountSaveBody;

                $this->module_data['hooksContents']['action' . $classModel . 'AccountAdd'] = $accountAddBody . $accountSaveBody;
            }
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
                'PrestaShop',
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

            $id_default_lang = "Configuration::get('PS_LANG_DEFAULT')";
            $transCount = 0;
            $count = 0;
            $where = "";
            $lang = false;
            foreach ($listingFields as $field) {
                $customHead .= "<th>{l s='" . $field['column_name'] . "' mod='" . $this->params['lower']['module_name'] . "'}</th>" . PHP_EOL;

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
            $content = str_replace(["/*", "*/", "/+"], ["", "", "$"], $content);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductHeader.tpl', $content);
            $content = file_get_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductFilter.tpl');
            $content = str_replace('custom_filter', $customFilter, $content);
            if ($content == '') {
                $content = $customFilter;
            }
            $content = str_replace('egaddcustomfieldtoproduct', $this->params['lower']['module_name'], $content);
            $content = str_replace(["/*", "*/", "/+"], ["", "", "$"], $content);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigProductFilter.tpl', $content);
            $content = file_get_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . 'displayAdminCatalogTwigListingProductFields.tpl');
            if ($content == '') {
                $content = $custom_value;
            }
            $content = str_replace('custom_value', $custom_value, $content);
            $content = str_replace(["/*", "*/", "/+"], ["", "", "$"], $content);
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
            $printer = new Printer();
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
            $printer = new Printer();
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

            $inputContent = str_replace(['setting_name', 'setting_label', 'setting_description'], [$settingData['name'], $settingData['label'], $settingData['description']], $inputContent);

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

    private function modifyQueryBuilder($modelData)
    {
        $classModel = $modelData['class'];
        if (empty($this->module_data['use'])) {
            $this->module_data['use'] = [];
            if (empty($this->module_data['use'][strtolower($classModel)])) {
                $this->module_data['use'][strtolower($classModel)] = [];
            }
        }


        if (in_array($modelData['class'], ['Category', 'Customer', 'CmsPage', 'CmsPageCategory'])) {
            $this->module_data['hooks'][strtolower($modelData['class'])] = array_merge($this->module_data['hooks'][strtolower($classModel)], ['action' . $classModel . 'FormBuilderModifier']);

            $gridDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Grid';
            if (!is_dir($gridDir) && !@mkdir($gridDir, 0777, true) && !is_dir($gridDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $gridDir));
            }

            $use = [];
            $namespace = new PhpNamespace($this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid');
            $namespace->addUse('Module');
            $namespace->addUse('Symfony\Component\Form\FormBuilderInterface');
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
            $fields = $modelData['fields'];
            foreach ($fields as $index => $item) {
                $column_name = $this->slugify($item['column_image_name']);
                $addFieldsBody .= '->add(' . PHP_EOL;
                $addFieldsBody .= "'" . $this->slugify($column_name) . "'," . PHP_EOL;
                $namespace->addUse('Symfony\Component\Form\Extension\Core\Type\FileType');
                $addFieldsBody .= "FileType::class," . PHP_EOL;
                $addFieldsBody .= "[" . PHP_EOL;
                $addFieldsBody .= '\'label\'=>$this->module->l(\'' . $item['column_image_name'] . '\'),' . PHP_EOL;
                $addFieldsBody .= '\'required\'=>false,' . PHP_EOL;
                $addFieldsBody .= "'attr' => [
                                        'accept' => 'gif,jpg,jpeg,jpe,png',
                                    ]," . PHP_EOL;
                $addFieldsBody .= "]" . PHP_EOL;
                $addFieldsBody .= ')' . PHP_EOL;
            }

            $addFieldsBody .= ';' . PHP_EOL;
            $use[str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . '\Module\Egaddextrafields\Model\Extra' . $classModel . 'Fields')] = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . '\Module\Egaddextrafields\Model\Extra' . $classModel . 'Fields');

            $addFieldsBody .= '$this->formBuilder->setData($this->formData);' . PHP_EOL;

            $addFields->setBody($addFieldsBody);
            $printer = new Printer();
            $printer->setTypeResolving(false);
            $code = $printer->printNamespace($namespace);


            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', '<?php declare(strict_types=1);');
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', PHP_EOL, FILE_APPEND);
            file_put_contents($this->module_dir . '/src/Grid/' . $classModel . 'FormBuilderModifier.php', $code, FILE_APPEND);
            $use[$this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier'] = $this->params['upper']['company_name'] . '\\' . 'Module' . '\\' . $this->params['upper']['module_name'] . '\\' . 'Grid' . '\\' . $classModel . 'FormBuilderModifier';

            $formBuilderContent = '$' . strtolower($classModel) . 'FormBuilderModifier= new ' . $classModel . 'FormBuilderModifier(' . PHP_EOL;
            $classModelFormBuilderModifierNameSpace = str_replace('Egaddextrafields', $this->params['upper']['module_name'], $this->params['upper']['company_name'] . "\Module" . '\\' . $this->params['upper']['module_name'] . '\\' . "Grid" . '\\' . $classModel . "FormBuilderModifier");

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
    }

    private function addImageTypes($modelData)
    {

        $imageTypes = '';
        $data = "[";

        foreach ($modelData['fields'] as $item) {
            $data .= PHP_EOL . "
                [
                'name'=>'" . $item['column_image_name'] . "',
                'width'=>" . $item['column_image_width'] . ",
                'height'=>" . $item['column_image_height'] . ",
                'products'=>" . ($modelData['class'] === 'Product' ? '1' : '0') . ",
                'categories'=>" . ($modelData['class'] === 'Category' ? '1' : '0') . ",
                'manufacturers'=>" . ($modelData['class'] === 'Manufacturer' ? '1' : '0') . ",
                'suppliers'=>" . ($modelData['class'] === 'Supplier' ? '1' : '0') . ",
                'stores'=>" . ($modelData['class'] === 'Store' ? '1' : '0') . ",
                ],
            ";

        }
        $data .= "]";
        $imageTypes = PHP_EOL . "\Db::getInstance()->insert(
                                               'image_type',
                                               " . $data . "
                                               );";
        $imageTypes = $imageTypes . PHP_EOL . 'return true;';
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $imageTypes, FILE_APPEND);
        return true;
    }

    private function removeImageTypes($modelData)
    {

        $dropQuery = '';
        $data = "[";

        foreach ($modelData['fields'] as $item) {
            $dropQuery .= PHP_EOL . "\Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'image_type WHERE name=\'" . $item['column_image_name'] . "\'');";

        }

        $dropQuery = $dropQuery . PHP_EOL . 'return true;';
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/uninstall.php', $dropQuery, FILE_APPEND);
        return true;
    }

    private function addUploader($modelData)
    {
        foreach ($modelData['fields'] as $item) {
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));

            $content = file_get_contents($this->base_dir . '/samples/src/Adapter/Image/Uploader/CategoryBannerLeftImageUploader.php');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('BannerLeftImage', $adapterName, $content);
            $content = str_replace('img_banner_left', $item['column_image_name'], $content);
            $adapterDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Adapter' . DIRECTORY_SEPARATOR . 'Image' . DIRECTORY_SEPARATOR . 'Uploader';
            if (!is_dir($adapterDir) && !@mkdir($adapterDir, 0777, true) && !is_dir($adapterDir)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $adapterDir));
            }
            file_put_contents($adapterDir . DIRECTORY_SEPARATOR . 'Category' . $adapterName . 'Uploader.php', $content);
        }
        return true;
    }

    private function addController($modelData)
    {
        $content = file_get_contents($this->base_dir . '/samples/src/Controller/CategoryController.php');
        $content = $this->replaceStandardStrings($content);
        $images = '';
        $deleteMethods = "";

        foreach ($modelData['fields'] as $item) {
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $images .= "'category$adapterName' => /*imageProvider->getCategory".$adapterName."(/*categoryId)," . PHP_EOL;
            $deleteMethod = '    public function deleteBannerImageLeftAction(Request $request, $categoryId)
    {
        if (!$this->isCsrfTokenValid(\'delete-banner-left-image\', $request->request->get(\'_csrf_token\'))) {
            return $this->redirectToRoute(\'admin_security_compromised\', [
                \'uri\' => $this->generateUrl(\'admin_categories_edit\', [
                    \'categoryId\' => $categoryId,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        }
        $thumbnailPath = $this->configuration->get(\'_PS_CAT_IMG_DIR_\') . $categoryId . \'-category_img_banner_left.jpg\';
        $this->filesystem= new \Symfony\Component\Filesystem\Filesystem();
        try {
            if ($this->filesystem->exists($thumbnailPath)) {
                $this->filesystem->remove($thumbnailPath);
            }
            $this->addFlash(
                \'success\',
                $this->trans(\'The image was successfully deleted.\', \'Admin.Notifications.Success\')
            );
        } catch (IOException $e) {
            $this->addFlash(\'error\', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        return $this->redirectToRoute(\'admin_categories_edit\', [
            \'categoryId\' => $categoryId,
        ]);
    }' . PHP_EOL;
            $deleteMethod = str_replace('BannerLeftImage', $adapterName, $deleteMethod);
            $deleteMethod = str_replace('BannerImageLeft', $adapterName, $deleteMethod);
            $deleteMethod = str_replace('img_banner_left', $item['column_image_name'], $deleteMethod);
            $deleteMethod = str_replace('banner-left-image', $item['column_image_name'], $deleteMethod);
            $deleteMethods .= $deleteMethod;
        }

        $images = str_replace('/*', '$', $images);
        $content = str_replace('/*add your custom images*/', $images, $content);
        $content = str_replace('/*add your delete methods*/', $deleteMethods, $content);

        $controllerDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller';
        if (!is_dir($controllerDir) && !@mkdir($controllerDir, 0777, true) && !is_dir($controllerDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $controllerDir));
        }
        file_put_contents($controllerDir . DIRECTORY_SEPARATOR . 'CategoryController.php', $content);
        return true;
    }

    private function addRoutes($modelData)
    {
        $content = file_get_contents($this->base_dir . '/samples/categoryImages/routes/routes.yml');
        $content = $this->replaceStandardStrings($content);
        $routes = "";
        foreach ($modelData['fields'] as $item) {
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $deleteRoute = file_get_contents($this->base_dir . '/samples/categoryImages/routes/deleteRoutes.yml');
            $deleteRoute = $this->replaceStandardStrings($deleteRoute);
            $deleteRoute = str_replace('BannerLeftImage', $adapterName, $deleteRoute);
            $deleteRoute = str_replace('BannerImageLeft', $adapterName, $deleteRoute);
            $deleteRoute = str_replace('img_banner_left', $item['column_image_name'], $deleteRoute);
            $deleteRoute = str_replace('banner-left-image', $item['column_image_name'], $deleteRoute);
            $routes .= PHP_EOL . $deleteRoute;
        }
        $content = str_replace('#images routes', $routes, $content);
        $configDir = $this->module_dir . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($configDir) && !@mkdir($configDir, 0777, true) && !is_dir($configDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $configDir));
        }

        file_put_contents($configDir . DIRECTORY_SEPARATOR . 'routes.yml', $content);
        return true;
    }

    private function addHandler($modelData)
    {
        $content = file_get_contents($this->base_dir . '/samples/categoryImages/Handler/CategoryFormDataHandler.php');
        $content = $this->replaceStandardStrings($content);

        $definitions = '';
        $constructArguments='';
        $constructAssignments='';
        $arguments='';
        $constraints='';
        $uploadedFileArguments='';
        foreach ($modelData['fields'] as $index=>$item) {
            $definition = '    
    /**
     * @var ImageUploaderInterface
     */
    private $categoryLeftBannerImageUploader;';
            $constructArgument='  ImageUploaderInterface $categoryLeftBannerImageUploader'.((int)$index===(count($modelData['fields'])) ? '' : ',');
            $constructAssignment='   $this->categoryLeftBannerImageUploader = $categoryLeftBannerImageUploader;';
            $argument="            /*data['".$item['column_image_name']."']".((int)$index===(count($modelData['fields'])) ? '' : ',');
            $constraint='        if (null !== $category_img_banner_left) {
            $this->categoryLeftBannerImageUploader->upload($categoryId->getValue(), $category_img_banner_left);
        }';
            $uploadedFileArgument='UploadedFile $category_img_banner_left = null'.((int)$index===(count($modelData['fields'])) ? '' : ',');
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $constraint = str_replace('category_img_banner_left', $item['column_image_name'], $constraint);
            $uploadedFileArgument = str_replace('category_img_banner_left', $item['column_image_name'], $uploadedFileArgument);
            $constraint = str_replace('LeftBannerImage', $adapterName, $constraint);
            $definition = str_replace('LeftBannerImage', $adapterName, $definition);
            $constructArgument = str_replace('LeftBannerImage', $adapterName, $constructArgument);
            $constructAssignment = str_replace('LeftBannerImage', $adapterName, $constructAssignment);
            $constructArguments.=$constructArgument.PHP_EOL;
            $constructAssignments.=$constructAssignment.PHP_EOL;
            $constraints.=$constraint.PHP_EOL;
            $uploadedFileArguments.=$uploadedFileArgument.PHP_EOL;
            $argument=str_replace('/*', '$', $argument);
            $arguments.=$argument.PHP_EOL;
            $definitions .= PHP_EOL . $definition;
        }
        $content=str_replace('/*add your images uploader attributes*/', $definitions, $content);
        $content=str_replace('/*add your images argument uploader*/', $constructArguments, $content);
        $content=str_replace('/*add your images definition uploader*/', $constructAssignments, $content);
        $content=str_replace('/*add your arguments uploader*/', $arguments, $content);
        $content=str_replace('/*add your uploaders conditions*/', $constraints, $content);
        $content=str_replace('/*uploaded file arguments*/', $uploadedFileArguments, $content);
        $handlerDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR .'Handler';
        if (!is_dir($handlerDir) && !@mkdir($handlerDir, 0777, true) && !is_dir($handlerDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $handlerDir));
        }

        file_put_contents($handlerDir . DIRECTORY_SEPARATOR . 'CategoryFormDataHandler.php', $content);
        return true;
    }

    private function addProvider($modelData)
    {
        $content = file_get_contents($this->base_dir . '/samples/categoryImages/Provider/ImageProvider.php');
        $content = $this->replaceStandardStrings($content);
        $getters='';
        foreach ($modelData['fields'] as $index=>$item) {
           $getter="    /**
     * @param /+categoryId
     *
     * @return array|null cover image data or null if category does not have cover
     */
    public function getCategoryBannerImageLeft(/+categoryId)
    {
        /+imageType = 'jpg';
        /+image = _PS_CAT_IMG_DIR_ . /+categoryId . '-category_img_banner_left.' . /+imageType;

        /+imageTag = ImageManager::thumbnail(
            /+image,
            'category' . '_' . /+categoryId . '-category_img_banner_left.' . /+imageType,
            350,
            /+imageType,
            true,
            true
        );

        /+imageSize = file_exists(/+image) ? filesize(/+image) / 1000 : '';

        if (empty(/+imageTag) || empty(/+imageSize)) {
            return null;
        }

        return [
            'size' => sprintf('%skB', /+imageSize),
            'path' => /+this->imageTagSourceParser->parse(/+imageTag),
        ];
    }";
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $getter=str_replace('BannerImageLeft', $adapterName, $getter);
            $getter=str_replace('/+', '$', $getter);
            $getter=str_replace('img_banner_left', $item['column_image_name'], $getter);
            $getters.=$getter.PHP_EOL;
        }
        $content=str_replace('/*add your getter methods*/', $getters, $content);
        $providerDir = $this->module_dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR .'Provider';
        if (!is_dir($providerDir) && !@mkdir($providerDir, 0777, true) && !is_dir($providerDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $providerDir));
        }

        file_put_contents($providerDir . DIRECTORY_SEPARATOR . 'ImageProvider.php', $content);
        return true;
    }

    private function addTemplates($modelData)
    {
        $formDir = $this->module_dir . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR .'PrestaShop'. DIRECTORY_SEPARATOR .'Admin'. DIRECTORY_SEPARATOR .'Sell'. DIRECTORY_SEPARATOR .'Catalog'. DIRECTORY_SEPARATOR .'Categories'. DIRECTORY_SEPARATOR .'Blocks'. DIRECTORY_SEPARATOR .'Forms';
        if (!is_dir($formDir) && !@mkdir($formDir, 0777, true) && !is_dir($formDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $formDir));
        }
        $form18 = file_get_contents($this->base_dir . '/samples/categoryImages/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/Forms/form_1.8.html.twig');
        $form17 = file_get_contents($this->base_dir . '/samples/categoryImages/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/Forms/form_1.7.html.twig');

        $imageBlocs='';
        foreach ($modelData['fields'] as $index=>$item) {
         $imageBloc='                {% if categoryForm.category_img_banner_left is defined %}
                    <div class="form-group row">
                        <label class="form-control-label">
                            {{ \'Category left categories Image\'|trans({}, \'Admin.Catalog.Feature\') }}
                        </label>
                        <div class="col-sm">
                            {% include \'@PrestaShop/Admin/Sell/Catalog/Categories/Blocks/category_img_banner_left.html.twig\' %}

                            {{ form_widget(categoryForm.category_img_banner_left) }}
                        </div>
                    </div>
                {% endif %}';
            $imageBloc=str_replace('category_img_banner_left', $item['column_image_name'], $imageBloc);
            $imageBloc=str_replace('left categories Image', $item['column_image_name'], $imageBloc);
            $imageBlocs.=$imageBloc.PHP_EOL;
            $imgForm = file_get_contents($this->base_dir . '/samples/categoryImages/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/category_img_banner_left.html.twig');
            $imgForm=str_replace('category_img_banner_left', $item['column_image_name'], $imgForm);
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $imgForm=str_replace('BannerImageLeft', $adapterName, $imgForm);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR .'PrestaShop'. DIRECTORY_SEPARATOR .'Admin'. DIRECTORY_SEPARATOR .'Sell'. DIRECTORY_SEPARATOR .'Catalog'. DIRECTORY_SEPARATOR .'Categories'. DIRECTORY_SEPARATOR .'Blocks' . DIRECTORY_SEPARATOR . 'category_'.$item['column_image_name'].'.html.twig', $imgForm);

        }
        $form18=str_replace('<!-- add images blocs -->', $imageBlocs, $form18);
        $form17=str_replace('<!-- add images blocs -->', $imageBlocs, $form17);
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR .'PrestaShop'. DIRECTORY_SEPARATOR .'Admin'. DIRECTORY_SEPARATOR .'Sell'. DIRECTORY_SEPARATOR .'Catalog'. DIRECTORY_SEPARATOR .'Categories'. DIRECTORY_SEPARATOR .'Blocks' . DIRECTORY_SEPARATOR .'Forms'. DIRECTORY_SEPARATOR . 'form_1.8.html.twig', $form18);
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR .'PrestaShop'. DIRECTORY_SEPARATOR .'Admin'. DIRECTORY_SEPARATOR .'Sell'. DIRECTORY_SEPARATOR .'Catalog'. DIRECTORY_SEPARATOR .'Categories'. DIRECTORY_SEPARATOR .'Blocks' . DIRECTORY_SEPARATOR .'Forms'. DIRECTORY_SEPARATOR . 'form_1.7.html.twig', $form17);

    }

    private function addServices($modelData)
    {
        $ymlLog = file_get_contents($this->base_dir . '/samples/log_sys.yml');
        $ymlLog = $this->replaceStandardStrings($ymlLog);
        $ymlLog = $this->replaceStandardStrings($ymlLog);
        $this->addService($ymlLog);
        $ymlService=file_get_contents($this->base_dir . '/samples/category_image.yml');
        $uploader='';
        $arguments='';

        foreach ($modelData['fields'] as $index=>$item){
            $adapterName = str_replace('_', '', ucwords($item['column_image_name'], '_'));
            $uploader.=PHP_EOL.
                "  module_class.adapter.image.uploader.category_".$item['column_image_name']."_uploader:
    class: 'EvoGroup\Module\Moduleclass\Adapter\Image\Uploader\Category".$adapterName."Uploader'
                ";
        }
        if($index===1){
            $arguments.="      - '@module_class.adapter.image.uploader.category_".$item['column_image_name']."_uploader'";
        }else{
            $arguments.=PHP_EOL."      - '@module_class.adapter.image.uploader.category_".$item['column_image_name']."_uploader'";
        }

        $uploader = $this->replaceStandardStrings($uploader);
        $ymlService=str_replace('$uploaders', $uploader, $ymlService);
        $ymlService=str_replace('@service', $arguments, $ymlService);
        $ymlService = $this->replaceStandardStrings($ymlService);
        $ymlService=PHP_EOL.$ymlService;
        $this->addService($ymlService);
        return true;
    }

    public function copyForm()
    {
        $content = file_get_contents($this->base_dir . '/downloads/'.$this->params['lower']['module_name'].'/'.$this->params['lower']['module_name'].'.php');

        $versionCompare="        if (version_compare(phpversion(), '7.2', '<')) {
            /+this->_errors[] = sprintf(/+this->l('This module requires at least PHP version 7.2. Your current version is: %s'), phpversion());

            return false;
        }
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            // PrestaShop 1.8.0.0+
            /+file=_PS_MODULE_DIR_./+this->name.'/views/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/Forms/form_1.8.html.twig';
            /+newfile=_PS_MODULE_DIR_./+this->name.'/views/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/form.html.twig';
            if (!Tools::copy(/+file, /+newfile)) {
                /+this->_errors[]=/+this->l('Cannot copy category forms');
                return false;
            }
        } else {
            // PrestaShop < 1.8.0
            /+file=_PS_MODULE_DIR_./+this->name.'/views/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/Forms/form_1.7.html.twig';
            /+newfile=_PS_MODULE_DIR_./+this->name.'/views/PrestaShop/Admin/Sell/Catalog/Categories/Blocks/form.html.twig';
            if (!Tools::copy(/+file, /+newfile)) {
                /+this->_errors[]=/+this->l('Cannot copy category forms');
                return false;
            }
        }";
        $content=str_replace("include dirname(__FILE__) . '/sql/install.php';",
            "include dirname(__FILE__) . '/sql/install.php';".PHP_EOL.$versionCompare,
            $content
        );

        $content=str_replace('/+', '$', $content);
        file_put_contents($this->base_dir . '/downloads/'.$this->params['lower']['module_name'].'/'.$this->params['lower']['module_name'].'.php', $content);
        return true;
    }
}

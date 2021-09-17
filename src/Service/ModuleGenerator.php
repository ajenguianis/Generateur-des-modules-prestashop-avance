<?php

namespace App\Service;

use App\Entity\TableMapping;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PhpNamespace;
use Doctrine\ORM\EntityManagerInterface;

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
        $params = $this->getParams();
        $content = str_replace(array('$companyNameLower', '$moduleName', '$nameSpace', '$companyName', '$contact_email'), array($params['lower']['company_name'], $params['lower']['module_name'], $params['upper']['module_name'], $params['upper']['company_name'], $this->module_data['email']), $content);
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
        $content = str_replace(array('Moduleclass', 'moduleclass', 'module_author', 'Diplay name', 'module_description', 'MODULECLASS'), array($params['upper']['module_name'], $params['lower']['module_name'], $params['upper']['company_name'], $this->module_data['display_name'], $this->module_data['description'], strtoupper($params['lower']['module_name'])), $content);

        if (isset($this->module_data['hooks']) && !empty($hooks = $this->module_data['hooks'])) {
            $result = array();
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
                        $hookContent = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $hookContent);
                        $method->setBody($hookContent);
                    }
                }
            }
            if (!empty($this->module_data['query']) && !empty($query = $this->module_data['query'])) {
                foreach ($query as $ind => $qb) {
                    $method = $class->addMethod('updateExtra' . $ind . 'Field');
                    $method->addParameter('data')->setType('array');
                    $method->addParameter('params');
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
            $content = str_replace("registerHook('backOfficeHeader')", "registerHook('backOfficeHeader')\n" . $register_hooks, $content);
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
        }
        if (!empty($this->module_data['use']) && !empty($query = $this->module_data['use'])) {
            $useContent='';
            foreach ($this->module_data['use'] as $use){
                $useContent.='use '. $use.';'.PHP_EOL;
            }
            $content = file_get_contents($this->module_dir . '/' . $this->module_data['module_name'] . '.php');
            $content=str_replace('/** add uses */', $useContent, $content);
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
        $params = $this->getParams();
        $content = str_replace('Moduleclass', $params['upper']['module_name'], $content);
        $content = str_replace('moduleclass', $params['lower']['module_name'], $content);
        $content = str_replace('module_author', $params['upper']['company_name'], $content);
        $content = str_replace('company_name', $params['lower']['company_name'], $content);
        $content = str_replace('EvoGroup', $params['upper']['company_name'], $content);
        $content = str_replace('module_class', $params['lower']['module_name'], $content);
        $content = str_replace('MODULE_CLASS', strtoupper($params['lower']['module_name']), $content);
        $content = str_replace('MODULECLASS', strtoupper($params['lower']['module_name']), $content);
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
        foreach ($this->module_data['models'] as $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }

            $params = $this->getParams();
            $namespace = new PhpNamespace($params['upper']['company_name'] . DIRECTORY_SEPARATOR . 'Module' . DIRECTORY_SEPARATOR . $params['upper']['module_name'] . DIRECTORY_SEPARATOR . 'Model');
            $namespace->addUse('ObjectModel');
            $class = $namespace->addClass($modelData['class']);
            $class->addExtend('ObjectModel');
            $fieldsData = $this->renderModel($modelData, $class, $withGeneralGetterAndSetter);
            $fields = $fieldsData['fields'];
            $sql = $fieldsData['sql'];
            $sql_shop = $fieldsData['sql_shop'];
            $sql_lang = $fieldsData['sql_lang'];

            $definition = [
                'table' => $modelData['table'],
                'primary' => $modelData['primary'] ?? 'id_' . $modelData['table']
            ];

            if (!empty($sql_lang)) {
                $definition['multilang'] = true;
            }
            $definition['fields'] = $fields;
            if (!empty($sql_shop)) {
                $method = $class->addMethod('__construct');
                $method->addParameter('id', null);
                $method->addParameter('id_lang', null);
                $method->addParameter('id_shop', null);
                $method->addParameter('translator', null);
                $body = 'Shop::addTableAssociation(self::definition[\'table\'], [\'type\'=>\'shop\']);' . PHP_EOL;
                $body .= 'Parent::__construct($id, $id_lang, $id_shop, $translator)' . PHP_EOL;
                $method->setBody($body);
            }
            if ($withGeneralGetterAndSetter) {
                //getter
                $method = $class->addMethod('getExtra' . $modelData['class'] . 'FieldsBy' . $modelData['class'] . 'Id');
                $method->addParameter($modelData['primary'], null);
                $getterContent = '$query = new \DbQuery();' . PHP_EOL;
                $getterContent .= '$query->from(\'' . $modelData['table'] . '\', \'ef\');' . PHP_EOL;
                $getterContent .= '$query->select(\'' . $modelData['primary'] . '\');' . PHP_EOL;
                $relatedField = 'id_product';

                $modelData['class']=$this->module_data['source'];

                if ($modelData['class'] == 'Category') {
                    $relatedField = 'id_category';
                }
                if ($modelData['class'] == 'Customer') {
                    $relatedField = 'id_customer';
                }

                $getterContent .= '$query->where(\'ef.' . $relatedField . '=\' . (int)$' . $relatedField . ');' . PHP_EOL;
                $getterContent .= '$result = \Db::getInstance()->getRow($query);' . PHP_EOL;
                $getterContent .= 'if (is_array($result) && !empty($result[\'' . $modelData['primary'] . '\'])) {' . PHP_EOL;
                $getterContent .= 'return new self((int)$result[\'' . $modelData['primary'] . '\']);' . PHP_EOL;
                $getterContent .= '}' . PHP_EOL;
                $getterContent .= 'return new self();' . PHP_EOL;
                $method->setBody($getterContent);
                //setter
                $method = $class->addMethod('SetExtra' . $modelData['class'] . 'FieldsBy' . $modelData['class'] . 'Id');
                $method->addParameter($modelData['primary'], null)->setType('int');
                $method->addParameter('form_data', null)->setType('array');
                $setterContent = 'if($' . $relatedField . ' > 0){' . PHP_EOL;
                $setterContent .= '$extra' . $modelData['class'] . 'Fields=self::getExtra' . $modelData['class'] . 'FieldsBy' . $modelData['class'] . 'Id($' . $relatedField . ');'.PHP_EOL;
                foreach ($modelData['fields'] as $field) {
                    if (!empty($field['is_auto_increment']) && $field['is_auto_increment'] == 1) {
                        continue;
                    }
                    $setterContent .= '$extra' . $modelData['class'] . 'Fields->' . $field['field_name'] . '=$form_data[\'' . $field['field_name'] . '\'];' . PHP_EOL;
                }
                $setterContent .= '$extra' . $modelData['class'] . 'Fields->save();' . PHP_EOL;
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

            $sql .= 'PRIMARY KEY  (`' . $modelData['primary'] . '`)' . PHP_EOL;
            $sql .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
            $sql = str_replace(array("/*", "*/"), array("'.", ".'"), $sql);


            if (!empty($sql_shop)) {
                $sql_shop .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $sql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`) USING BTREE;' . PHP_EOL;
                $sql_shop = str_replace(array("/*", "*/"), array("'.", ".'"), $sql_shop);
            }
            if (!empty($sql_lang)) {
                $sql_lang .= ') ENGINE=/*_MYSQL_ENGINE_*/ DEFAULT CHARSET=utf8;' . PHP_EOL;
                $sql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`' . $modelData['primary'] . '`, `id_shop`, `id_lang`) USING BTREE;' . PHP_EOL;
                $sql_lang = str_replace(array("/*", "*/"), array("'.", ".'"), $sql_lang);
            }
            $installContent = file($this->base_dir . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'install_vg.php');
            if (!empty($sql)) {
                $sql = '$sql[]=' . $sql . "';" . PHP_EOL;
            }
            if (!empty($sql_shop)) {
                $sql_shop = '$sql[]=' . $sql_shop . "';" . PHP_EOL;
            }
            if (!empty($sql_lang)) {
                $sql_lang = '$sql[]=' . $sql_lang . "';" . PHP_EOL;
            }
            if ($firstModel == 1) {
                $installContent[26] = $sql . $sql_lang . $sql_shop;
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', implode("", $installContent));
            } else {
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', PHP_EOL, FILE_APPEND);
                file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $sql . $sql_lang . $sql_shop, FILE_APPEND);
            }
            $firstModel++;
        }
        $executionLoop = 'foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}';
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', PHP_EOL, FILE_APPEND);
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', $executionLoop, FILE_APPEND);

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
        $sql_shop = '';
        $sql_lang = '';
        $sql .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '` (' . PHP_EOL;
        $firstShopIteration = 1;
        $firstLangIteration = 1;

        foreach ($modelData['fields'] as $index => $fieldData) {
            $separator = ',';
            if ($index === array_key_last($modelData['fields'])) {
                $separator = ',';
            }
            $property = $class->addProperty($fieldData['field_name']);
            if (!empty($fieldData['is_auto_increment']) && $fieldData['is_auto_increment'] === 1) {
                $sql .= '`' . $fieldData['field_name'] . '` int(11) NOT NULL AUTO_INCREMENT' . $separator . PHP_EOL;
                if($withGeneralGetterAndSetter && !empty($this->module_data['source'])){
                    $sql .= '`id_'.strtolower($this->module_data['source']).'` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                continue;
            }
            if ($fieldData['is_nullable'] === '1') {
                $property->setNullable();
                $nullableCondition = ' NULL';
            } else {
                $nullableCondition = ' NOT NULL';
            }
            $default_value = '';
            if ($fieldData['default_value'] != "") {
                $default_value = ' DEFAULT ' . $fieldData['default_value'];
            }
            $is_shop_fields = !empty($fieldData['is_shop']) && $fieldData['is_shop'] !== '' && $fieldData['is_shop'] !== null;
            if ($is_shop_fields) {
                $fieldsDef[$index]['shop'] = true;

                if ($firstShopIteration == 1) {
                    $sql_shop .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_shop` (' . PHP_EOL;
                    $sql_shop .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_shop .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                if (!empty($fieldData['field_name']) && $fieldData['field_type']) {
                    $sql_shop .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')' . $nullableCondition . $default_value . ',' . PHP_EOL;
                }

                $firstShopIteration++;
            }

            $is_lang_fields = !empty($fieldData['is_lang']) && $fieldData['is_lang'] !== '' && $fieldData['is_lang'] !== null;
            $in_two_table = !$is_lang_fields;
            if ($is_lang_fields) {
                $fieldsDef[$index]['lang'] = true;

                if ($firstLangIteration == 1) {
                    $sql_lang .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` (' . PHP_EOL;
                    $sql_lang .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_lang` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                $type=$fieldData['field_type'] . '(' . $fieldData['field_length'] . ')';
                if ($fieldData['field_type'] !== 'VARCHAR' &&  $fieldData['field_type'] !== 'TEXT' && $fieldData['field_type'] !== 'LONGTEXT') {
                  $type='VARCHAR(' . $fieldData['field_length'] . ')';
                }
                if ($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT') {
                    $type=$fieldData['field_type'];
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

        return ['fields' => $fields, 'sql' => $sql, 'sql_shop' => $sql_shop, 'sql_lang' => $sql_lang];
    }

    public function generateModelCustomFields()
    {
        if (empty($this->module_data['objectModels'])) {
            return false;
        }

        $firstModel = 1;
        $this->module_data['hooksContents'] = [];
        $extraData = [];

        foreach ($this->module_data['objectModels'] as $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }
            if (empty($modelData['fields'])) {
                return false;
            }
            $extraModelsData['class'] = 'Extra' . $modelData['class'] . 'Fields';
            $extraModelsData['table'] = strtolower('Extra' . $modelData['class'] . 'Fields');
            $extraModelsData['primary'] = 'id_' . strtolower('Extra' . $modelData['class'] . 'Fields');
            $extraModelsData['fields'] = $modelData['fields'];
            $there_is_a_lang_field = false;
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
            $extraModelsData['fields'] = array_merge([$primaryField], $extraData);
            $this->module_data['models'] = [$extraModelsData];
            $this->module_data['source']=$modelData['class'];
            $this->generateModels(true);

            if (!empty($modelData['listing'])) {
                $this->addToListing($modelData['class'], $modelData['listing']);
            }

            $this->setHookContent($modelData['class'], $modelData['fields'], $there_is_a_lang_field);

        }
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

    private function setHookContent($class, $fields, $there_is_a_lang_field)
    {
        $sql = '';
        $sql_lang = '';
        $sql_shop = '';
        $content = '';
        $use = [];
        if ($class == 'Product') {
            $this->module_data['hooks'][strtolower($class)] = array_merge($this->module_data['hooks'][strtolower($class)], ['actionObjectProductAddAfter', 'actionObjectProductUpdateAfter', 'displayAdminProductsMainStepLeftColumnBottom']);
            if ($there_is_a_lang_field) {
                $this->module_data['hooks'][strtolower($class)][] = 'displayAdminProductsMainStepLeftColumnMiddle';
            }
            $contentForTranslatableTemplates="";
            $contentForNonTranslatableTemplates="";
            foreach ($this->module_data['hooks'][strtolower($class)] as $hook) {
                $common_content = '$idProduct = (int)$params[\'id_product\'];' . PHP_EOL;
                $use['EvoGroup\Module\Egaddextrafields\Model\ExtraProductFields'] = 'EvoGroup\Module\Egaddextrafields\Model\ExtraProductFields';
                $common_content .= '$extraProductFields = ExtraProductFields::getExtraProductFieldsByProductId($idProduct);' . PHP_EOL;
                $use['PrestaShop\PrestaShop\Adapter\SymfonyContainer'] = 'PrestaShop\PrestaShop\Adapter\SymfonyContainer';
                $use['Symfony\Component\Form\Extension\Core\Type\FormType'] = 'Symfony\Component\Form\Extension\Core\Type\FormType';

                if ($hook == 'displayAdminProductsMainStepLeftColumnBottom') {
                    $formData='['. PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if (!empty($item['is_column_lang'])) {
                            continue;
                        }
                        $formData.="'".$item['column_name']."' => /+extraProductFields-> ". $item['column_name']."," .PHP_EOL;
                        $contentForNonTranslatableTemplates.="<h3>{{ form_label(form.".$item['column_name'].") }}</h3>".PHP_EOL;
                        $contentForNonTranslatableTemplates.="{{ form_widget(form.".$item['column_name'].") }}".PHP_EOL;
                        $contentForNonTranslatableTemplates.="{{ form_errors(form.".$item['column_name'].") }}".PHP_EOL;
                        $contentForNonTranslatableTemplates.="<br>".PHP_EOL;
                    }
                    $formData.=']'. PHP_EOL;

                    $extra_non_translatable_content = $common_content;
                    $extra_non_translatable_content .= '$form = SymfonyContainer::getInstance()->get(\'form.factory\')->createNamedBuilder(\'extra_non_translatable_fields_form\', FormType::class, ' . $formData . ')' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if ($item['column_type'] === 'TINYINT') {
                            $use['PrestaShopBundle\Form\Admin\Type\SwitchType'] = 'PrestaShopBundle\Form\Admin\Type\SwitchType';
                            $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', SwitchType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'choices' => [
                            'OFF' => false,
                            'ON' => true,
                            ],
                            ])" . PHP_EOL;
                        } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                            $use['PrestaShopBundle\Form\Admin\Type\DatePickerType'] = 'PrestaShopBundle\Form\Admin\Type\DatePickerType';
                            $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', DatePickerType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'required' => false,
                            ])" . PHP_EOL;
                                                    } else {
                            $use['Symfony\Component\Form\Extension\Core\Type\TextType'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';
                            $extra_non_translatable_content .= "->add('" . $item['column_name'] . "', TextType::class, [
                            'label' => /+this->l('" . $item['column_name'] . "'),
                            'required' => false,
                            'attr' => ['placeholder' => /+this->l('" . $item['column_name'] . "')],
                            'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Length(['min' => 1, 'max' => " . $item['column_length'] . "]),
                            ]
                            ])" . PHP_EOL;
                        }
                    }
                    $extra_non_translatable_content .= '->getForm();' . PHP_EOL;
                    $extra_non_translatable_content .= 'return SymfonyContainer::getInstance()->get(\'twig\')->render(' . PHP_EOL;
                    $extra_non_translatable_content .= '    \'@PrestaShop/Products/extra_no_translatable_fields.html.twig\', [\'form\' => $form->createView()]' . PHP_EOL;
                    $extra_non_translatable_content .= ');' . PHP_EOL;
                    $this->module_data['hooksContents']['displayAdminProductsMainStepLeftColumnBottom'] = $extra_non_translatable_content;
                }
                if ($hook == 'displayAdminProductsMainStepLeftColumnMiddle') {
                    $formData='['. PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if (empty($item['is_column_lang'])) {
                            continue;
                        }

                        $formData.="'".$item['column_name']."' => /+extraProductFields-> ". $item['column_name']."," .PHP_EOL;
                        $contentForTranslatableTemplates.="<h3>{{ form_label(form.".$item['column_name'].") }}</h3>".PHP_EOL;
                        $contentForTranslatableTemplates.="{{ form_widget(form.".$item['column_name'].") }}".PHP_EOL;
                        $contentForTranslatableTemplates.="{{ form_errors(form.".$item['column_name'].") }}".PHP_EOL;
                        $contentForTranslatableTemplates.="<br>".PHP_EOL;
                    }
                    $formData.=']'. PHP_EOL;
                    $extra_translatable_content = $common_content;
                    $extra_translatable_content .= '$form = SymfonyContainer::getInstance()->get(\'form.factory\')->createNamedBuilder(\'extra_translatable_fields_form\', FormType::class, ' . $formData . ')' . PHP_EOL;
                    foreach ($fields as $index => $item) {
                        if ($item['column_type'] === 'VARCHAR' || $item['column_type'] === 'HTML') {
                            $use['PrestaShopBundle\Form\Admin\Type\TranslateType'] = 'PrestaShopBundle\Form\Admin\Type\TranslateType';
                            $use['PrestaShopBundle\Form\Admin\Type\FormattedTextareaType'] = 'PrestaShopBundle\Form\Admin\Type\FormattedTextareaType';
                            if ($item['column_type'] === 'HTML') {
                                $use['PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml'] = 'PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml';
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
                                $use['PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml'] = 'PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml';
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
                    $this->module_data['hooksContents'][$hook] = "/+this->updateExtraProductField(/+params);";
                }
            }
            $codeForUpdateProduct='$extraProductFields = ExtraProductFields::getExtraProductFieldsByProductId($productId);'.PHP_EOL;

            $codeForUpdateProduct.='if (Tools::getIsset(\'extra_non_translatable_fields_form\') && !empty($extra_non_translatable_form = Tools::getValue(\'extra_non_translatable_fields_form\'))) {'.PHP_EOL;
            $codeForUpdateProduct.='   foreach ($extra_non_translatable_form as $extraFieldKey => $extraFieldValue) {'.PHP_EOL;
            $codeForUpdateProduct.='       $extraProductFields->$extraFieldKey = $extraFieldValue;'.PHP_EOL;
            $codeForUpdateProduct.='    }'.PHP_EOL;
            $codeForUpdateProduct.='    }'.PHP_EOL;
            $codeForUpdateProduct.='if (Tools::getIsset(\'extra_translatable_fields_form\') && !empty($extra_translatable_form = Tools::getValue(\'extra_translatable_fields_form\'))) {'.PHP_EOL;
            $codeForUpdateProduct.='   foreach ($extra_non_translatable_form as $extraFieldKey => $extraFieldValue) {'.PHP_EOL;
            $codeForUpdateProduct.='       $languages = Language::getLanguages();'.PHP_EOL;
            $codeForUpdateProduct.='          foreach ($languages as $lang) {'.PHP_EOL;
            $codeForUpdateProduct.='       $extraProductFields->$extraFieldKey[$lang[\'id_lang\']] = !empty($extraFieldValue[$lang[\'id_lang\']]) ? $extraFieldValue[$lang[\'id_lang\']] : null;'.PHP_EOL;
            $codeForUpdateProduct.='       }'.PHP_EOL;
            $codeForUpdateProduct.='      }'.PHP_EOL;
            $codeForUpdateProduct.='    }'.PHP_EOL;
            $codeForUpdateProduct.='$extraProductFields->save();'.PHP_EOL;

            $this->module_data['query'][$class] = $codeForUpdateProduct;
            $this->module_data['use']=$use;

            //templates
            $twigPath=$this->module_dir.'/views/PrestaShop/Products';
            if (!is_dir($twigPath) && !@mkdir($twigPath, 0777, true) && !is_dir($twigPath)) {
                throw new \RuntimeException(sprintf('Cannot create directory "%s"', $twigPath));
            }
            file_put_contents($this->module_dir.'/views/PrestaShop/Products/extra_no_translatable_fields.html.twig', $contentForNonTranslatableTemplates);

            file_put_contents($this->module_dir.'/views/PrestaShop/Products/extra_translatable_fields.html.twig', $contentForTranslatableTemplates);

        }
//        if ($class == 'Category') {
//            $this->module_data['hooks']['category'] = ['actionCategoryFormBuilderModifier', 'actionAfterCreateCategoryFormHandler', 'actionAfterUpdateCategoryFormHandler'];
//            $content .= "/+formBuilder = /+params['form_builder'];" . PHP_EOL;
//            if ($there_is_a_lang_field) {
//                $content .= "/+languages = Language::getLanguages();" . PHP_EOL;
//            }
//            $formBuilder = "/+category=new Category((int) /+params['id']);" . PHP_EOL;
//            $codeForUpdate = "/+id_category = /+params['id'];" . PHP_EOL;
//            $codeForUpdate .= "/+languages = Language::getLanguages();" . PHP_EOL;
//            foreach ($fields as $index => $item) {
//                if (!empty($item['is_column_lang'])) {
//                    $type = "PrestaShopBundle\Form\Admin\Type\TranslateType::class";
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', " . $type . ", [
//                         'type' => Symfony\Component\Form\Extension\Core\Type\TextType::class,
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'required' => false,
//                         'locales' => /+languages,
//                     ]);" . PHP_EOL;
//                    $formBuilder .= "foreach (/+languages as /+lang) {" . PHP_EOL;
//                    $formBuilder .= "/+id_lang = /+lang['id_lang'];" . PHP_EOL;
//                    $formBuilder .= "/+params['data']['" . $item['column_name'] . "'][/+id_lang] = /+category->" . $item['column_name'] . "[/+id_lang];" . PHP_EOL;
//                    $formBuilder .= "}" . PHP_EOL;
//                } elseif ($item['column_type'] === 'TINYINT') {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\SwitchType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'choices' => [
//                             'OFF' => false,
//                             'ON' => true,
//                         ],
//                     ]);" . PHP_EOL;
//                } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\DatePickerType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'required' => false,
//                     ]);" . PHP_EOL;
//                } else {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', Symfony\Component\Form\Extension\Core\Type\TextType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'required' => false,
//                     ]);" . PHP_EOL;
//                }
//                if (empty($item['is_column_lang'])) {
//                    $formBuilder .= "/+params['data']['" . $item['column_name'] . "'] = /+category->" . $item['column_name'] . ";" . PHP_EOL;
//                }
//                $alterLangTable = !empty($item['is_column_lang']) && $tableMapping->getHasLangTable() == true;
//
//                if (!$alterLangTable) {
//                    $sql .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "', ['" . $item['column_name'] . "' => /+params['form_data']['" . $item['column_name'] . "']], 'id_category=' . (int) /+id_category);" . PHP_EOL;
//                } else {
//                    $sql_lang .= "foreach (/+languages as /+lang) {" . PHP_EOL;
//                    $sql_lang .= "/+id_lang = /+lang['id_lang'];" . PHP_EOL;
//                    $sql_lang .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_lang', ['" . $item['column_name'] . "' => /+params['form_data']['" . $item['column_name'] . "'][/+id_lang]], 'id_category=' . (int) /+id_category);" . PHP_EOL;
//                    $sql_lang .= "}" . PHP_EOL;
//                }
//                $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;
//
//                if ($alterShopTable) {
//                    $sql_shop .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_shop', ['" . $item['column_name'] . "' => /+params['form_data']['" . $item['column_name'] . "']], 'id_category=' . (int) /+id_category);" . PHP_EOL;
//                }
//            }
//            $content .= $formBuilder . PHP_EOL;
//            $content .= "/+formBuilder->setData(/+params['data']);" . PHP_EOL;
//            $this->module_data['hooksContents']['actionCategoryFormBuilderModifier'] = $content;
//            $this->module_data['hooksContents']['actionAfterCreateCategoryFormHandler'] = "/+this->updateCategoryData(/+params['form_data'], /+params);";
//            $this->module_data['hooksContents']['actionAfterUpdateCategoryFormHandler'] = "/+this->updateCategoryData(/+params['form_data'], /+params);";
//            $this->module_data['query']['Category'] = $codeForUpdate . $sql . PHP_EOL . $sql_lang . PHP_EOL . $sql_shop;
//        }
//        if ($class == 'Customer') {
//            $this->module_data['hooks']['customer'] = ['actionCustomerFormBuilderModifier', 'actionAfterCreateCustomerFormHandler', 'actionAfterUpdateCustomerFormHandler'];
//            $content .= "/+formBuilder = /+params['form_builder'];" . PHP_EOL;
//            $formBuilder = "/+customer=new Customer((int) /+params['id']);" . PHP_EOL;
//            $codeForUpdate = "/+id_customer = /+params['id'];" . PHP_EOL;
//            foreach ($fields as $index => $item) {
//                if ($item['column_type'] === 'TINYINT') {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\SwitchType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'choices' => [
//                             'OFF' => false,
//                             'ON' => true,
//                         ],
//                     ]);" . PHP_EOL;
//                } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\DatePickerType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'required' => false,
//                     ]);" . PHP_EOL;
//                } else {
//                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', Symfony\Component\Form\Extension\Core\Type\TextType::class, [
//                         'label' => /+this->l('" . $item['column_name'] . "'),
//                         'required' => false,
//                     ]);" . PHP_EOL;
//                }
//                $formBuilder .= "/+params['data']['" . $item['column_name'] . "'] = /+customer->" . $item['column_name'] . ";" . PHP_EOL;
//
//                $sql .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "', ['" . $item['column_name'] . "' => /+params['form_data']['" . $item['column_name'] . "']], 'id_customer=' . (int) /+id_customer);" . PHP_EOL;
//
//                $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;
//
//                if ($alterShopTable) {
//                    $sql_shop .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_shop', ['" . $item['column_name'] . "' => /+params['form_data']['" . $item['column_name'] . "']], 'id_customer=' . (int) /+id_customer);" . PHP_EOL;
//                }
//            }
//            $content .= $formBuilder . PHP_EOL;
//            $content .= "/+formBuilder->setData(/+params['data']);" . PHP_EOL;
//            $this->module_data['hooksContents']['actionCustomerFormBuilderModifier'] = $content;
//            $this->module_data['hooksContents']['actionAfterCreateCustomerFormHandler'] = "/+this->updateCustomerData(/+params['form_data'], /+params);";
//            $this->module_data['hooksContents']['actionAfterUpdateCustomerFormHandler'] = "/+this->updateCustomerData(/+params['form_data'], /+params);";
//            $this->module_data['query']['Customer'] = $codeForUpdate . $sql . PHP_EOL . $sql_lang . PHP_EOL . $sql_shop;
//        }

        return true;
    }

    private function addToListing($class, $listingFields)
    {
        if ($class === 'Product') {
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
            $listing .= "/+product= new Product(/+id_product);" . PHP_EOL;
            $listing .= "/+vars=[";
            foreach ($listingFields as $field) {
                if (!empty($field['is_column_lang'])) {
                    $listing .= "'" . $field['column_name'] . "'=>/+product->" . $field['column_name'] . "[Configuration::get('PS_LANG_DEFAULT')]," . PHP_EOL;
                } elseif ($field['column_type'] === 'TINYINT' || $field['column_type'] === 'BOOLEAN') {
                    $this->module_data['presenter']['product'] = ['presentBooleanResponse' => $this->booleanPresenter()];
                    $listing .= "'" . $field['column_name'] . "'=>/+this->presentBooleanResponse(Db::getInstance()->getValue('SELECT " . $field['column_name'] . " FROM '._DB_PREFIX_.'product where id_product='.(int)/+product->id))," . PHP_EOL;
                } else {
                    $listing .= "'" . $field['column_name'] . "'=>/+product->" . $field['column_name'] . "," . PHP_EOL;
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
            $transCount=0;
            $count=0;
            $where="";
            foreach ($listingFields as $field) {
                $customHead .= "<th>{l s='" . $field['column_name'] . "' mod='" . $params['lower']['module_name'] . "'}</th>" . PHP_EOL;
                $customFilter .= "<th><input type=\"text\" class=\"form-control\"  placeholder=\"{l s='Search " . $field['column_name'] . "' mod='egaddcustomfieldtoproduct'}\" name=\"filter_column_name_" . $field['column_name'] . "\" value=\"{/+" . $field['column_name'] . "}\"></th>" . PHP_EOL;
                $custom_value .= "<td>{/+" . $field['column_name'] . "}</td>" . PHP_EOL;

                if (!empty($field['is_column_lang'])) {
                    $sql .= "/+params['sql_select']['" . $field['column_name'] . "'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extra_lang'," . PHP_EOL;
                    $sql .= "'field' => '" . $field['column_name'] . "'," . PHP_EOL;
                    $sql .= "'filtering' => \PrestaShop\PrestaShop\Adapter\Admin\AbstractAdminQueryBuilder::FILTERING_LIKE_BOTH" . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                    $where.='if (Tools::getIsset(\'filter_column_name_'.$field['column_name'].'\') && !empty(Tools::getValue(\'filter_column_name_'.$field['column_name'].'\'))) {'.PHP_EOL;
                    $where.='$params[\'sql_where\'][] .= "extra_lang.extra_reference =" . Tools::getValue(\'filter_column_name_'.$field['column_name'].'\');'.PHP_EOL;
                    $where.='}'.PHP_EOL;
                } else {
                    $sql .= "/+params['sql_select']['" . $field['column_name'] . "'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extra'," . PHP_EOL;
                    $sql .= "'field' => '" . $field['column_name'] . "'," . PHP_EOL;
                    $sql .= "'filtering' => \PrestaShop\PrestaShop\Adapter\Admin\AbstractAdminQueryBuilder::FILTERING_LIKE_BOTH" . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                    $where.='if (Tools::getIsset(\'filter_column_name_'.$field['column_name'].'\') && !empty(Tools::getValue(\'filter_column_name_'.$field['column_name'].'\'))) {'.PHP_EOL;
                    $where.='$params[\'sql_where\'][] .= "extra.extra_reference =" . Tools::getValue(\'filter_column_name_'.$field['column_name'].'\');'.PHP_EOL;
                    $where.='}'.PHP_EOL;
                    $count++;
                }
                if($count==1){
                    $sql .= "/+params['sql_table']['extra'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extraproductfields'," . PHP_EOL;
                    $sql .= "'join' => 'INNER JOIN'," . PHP_EOL;
                    $sql .= "'on' => 'p.`id_product` = extra.`id_product`'," . PHP_EOL;
                    $sql .= "];" . PHP_EOL;
                }
                if($transCount==1){
                    $sql .= "/+params['sql_table']['extra_lang'] = [" . PHP_EOL;
                    $sql .= "'table' => 'extraproductfields_lang'," . PHP_EOL;
                    $sql .= "'join' => 'INNER JOIN'," . PHP_EOL;
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
        if ($class === 'Category') {
        }
        return true;
    }

    /**
     * @return string
     */
    public function booleanPresenter()
    {
        $bool = '';
        $bool .= 'if ((bool)/+value == false) {' . PHP_EOL;
        $bool .= ' return \'<a href="#"><i class="material-icons action-disabled">clear</i></a>\';' . PHP_EOL;
        $bool .= '} else {' . PHP_EOL;
        $bool .= 'return \'<a href="#"><i class="material-icons enabled">check</i></a>\';' . PHP_EOL;
        $bool .= '}' . PHP_EOL;
        return $bool;
    }

}

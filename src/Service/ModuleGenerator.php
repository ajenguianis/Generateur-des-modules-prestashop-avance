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
            array_walk_recursive($hooks,function($v) use (&$result){ $result[] = $v; });
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
                foreach ($query as $ind=>$qb){
                    $method = $class->addMethod('update'.$ind.'Data');
                    $method->addParameter('data')->setType('array');
                    $method->addParameter('params');
                    $qb = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $qb);
                    $method->setBody($qb);
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

            return true;
        }
        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . $this->module_data['module_name'] . '.php', $content);
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

    public function generateModels()
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
            $fielsData = $this->renderModel($modelData, $class);
            $fields = $fielsData['fields'];
            $sql = $fielsData['sql'];
            $sql_shop = $fielsData['sql_shop'];
            $sql_lang = $fielsData['sql_lang'];

            $definition = [
                'table' => $modelData['table'],
                'primary' => $modelData['primary'] ?? 'id_' . $modelData['table'],
                'fields' => $fields
            ];

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
    public function renderModel($modelData, $class)
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
            if ($fieldData['is_auto_increment'] === '1') {
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
            if ($fieldData['default_value'] != "") {
                $default_value = ' DEFAULT ' . $fieldData['default_value'];
            }

            if (!empty($fieldData['is_shop']) && $fieldData['is_shop'] !== '' && $fieldData['is_shop'] !== null) {
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


            if (!empty($fieldData['is_lang']) && $fieldData['is_lang'] !== '' && $fieldData['is_lang'] !== null) {
                $fieldsDef[$index]['lang'] = true;

                if ($firstLangIteration == 1) {
                    $sql_lang .= 'CREATE TABLE IF NOT EXISTS `/*_DB_PREFIX_*/' . $modelData['table'] . '_lang` (' . PHP_EOL;
                    $sql_lang .= '`' . $modelData['primary'] . '` int(11) NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_shop` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                    $sql_lang .= '`id_lang` int(11) UNSIGNED NOT NULL,' . PHP_EOL;
                }
                $sql_lang .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . '(' . $fieldData['field_length'] . ')' . $nullableCondition . $default_value . ',' . PHP_EOL;
                $firstLangIteration++;
            }

            if ($fieldData['field_type'] === 'INT' || $fieldData['field_type'] === 'UnsignedInt') {
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
            if ($fieldData['field_type'] === 'EMAIL' || $fieldData['field_type'] === 'VARCHAR' || $fieldData['field_type'] === 'HTML' || $fieldData['field_type'] === 'PERCENT') {
                $property->addComment('@var string');
                $fieldsDef[$index]['type'] = '/*self::TYPE_STRING*/';
                if ($fieldData['field_type'] === 'EMAIL') {
                    $fieldsDef[$index]['validate'] = 'isEmail';
                }
                if ($fieldData['field_type'] === 'HTML') {
                    $fieldsDef[$index]['type'] = '/*self::TYPE_HTML*/';
                    $fieldsDef[$index]['validate'] = 'isCleanHtml';
                }
                if ($fieldData['field_type'] === 'VARCHAR') {
                    $fieldsDef[$index]['validate'] = 'isGenericName';
                }
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $fieldsDef[$index]['size'] = (int)$fieldData['field_length'];
                }
                $size = $fieldsDef[$index]['size'] ?? 255;
                $sql .= '`' . $fieldData['field_name'] . '` VARCHAR(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if ($fieldData['field_type'] === 'DECIMAL' || $fieldData['field_type'] === 'FLOAT') {
                $property->addComment('@var float');
                $fieldsDef[$index]['type'] = '/*self::TYPE_FLOAT*/';
                $fieldsDef[$index]['validate'] = 'isPrice';
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 20.6);
                }
                $size = $size ?? 20.6;
                $sql .= '`' . $fieldData['field_name'] . '` DECIMAL(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if ($fieldData['field_type'] === 'TEXT' || $fieldData['field_type'] === 'LONGTEXT') {
                $property->addComment('@var string');
                $fieldsDef[$index]['type'] = '/*self::TYPE_STRING*/';
                $sql .= '`' . $fieldData['field_name'] . '` ' . $fieldData['field_type'] . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if ($fieldData['field_type'] === 'TINYINT' || $fieldData['field_type'] === 'BOOLEAN') {
                $property->addComment('@var bool');
                $fieldsDef[$index]['type'] = '/*self::TYPE_BOOL*/';
                $fieldsDef[$index]['validate'] = 'isBool';
                if (!empty($fieldData['field_length']) && $fieldData['field_length'] !== '') {
                    $size = ($fieldData['field_length'] ?? 1);
                }
                $size = $size ?? 1;
                $sql .= '`' . $fieldData['field_name'] . '` TINYINT(' . $size . ')  ' . $nullableCondition . $default_value . $separator . PHP_EOL;
            }
            if ($fieldData['field_type'] === 'DATE' || $fieldData['field_type'] === 'DATETIME') {
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
        $overrideDir = $this->module_dir . DIRECTORY_SEPARATOR . 'override';
        if (!is_dir($overrideDir) && !@mkdir($overrideDir, 0777, true) && !is_dir($overrideDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $overrideDir));
        }
        $this->generateIndex($overrideDir);
        $overrideClassDir = $overrideDir . DIRECTORY_SEPARATOR . 'classes';
        if (!is_dir($overrideClassDir) && !@mkdir($overrideClassDir, 0777, true) && !is_dir($overrideClassDir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $overrideClassDir));
        }
        $this->generateIndex($overrideClassDir);
        $firstModel = 1;
        $this->module_data['hooksContents'] = [];

        foreach ($this->module_data['objectModels'] as $modelData) {

            if (empty($modelData['class'])) {
                return false;
            }
            if (empty($modelData['fields'])) {
                return false;
            }
            $sql = '';
            $sql_shop = '';
            $sql_lang = '';
            $tableMapping = $this->em->getRepository(TableMapping::class)->findOneBy(['class' => $modelData['class']]);

            $class = new ClassType($modelData['class']);
            $class->addExtend(__CLASS__);
            $method = $class->addMethod('__construct');
            if ($modelData['class'] === 'Product') {
                $method->addParameter('id_product', null);
                $method->addParameter('full', false);
                $method->addParameter('id_lang', null);
                $method->addParameter('id_shop', null);
                $method->addParameter('context', null)->setType('Context');
            }
            if ($modelData['class'] === 'Category') {
                $method->addParameter('idCategory', null);
                $method->addParameter('idLang', null);
                $method->addParameter('idShop', null);
            }
            if ($modelData['class'] === 'Customer') {
                $method->addParameter('id', null);
            }
            $constructBody = '';
            $fieldsDef = [];

            $there_is_lang_field = false;
            foreach ($modelData['fields'] as $index => $item) {
                $property = $class->addProperty($item['column_name']);
                $nullable = '';
                if (!empty($item['is_column_nullable']) && $item['is_column_nullable'] == 1) {
                    $nullable = ' NULL';
                }
                $default_value = '';

                if (isset($item['default_column_value']) && $item['default_column_value'] !== "") {
                    $default_value = ' DEFAULT ' . $item['default_column_value'];
                }

                $alterLangTable = !empty($item['is_column_lang']) && $tableMapping->getHasLangTable() == true;
                $length='(' . $item['column_length'] . ')';
                if ($item['column_type'] == 'DATETIME' || $item['column_type'] == 'DATE') {
                    $length = '';
                }
                if (!$alterLangTable) {
                    $sql .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $tableMapping->getTableName() . '` ADD COLUMN IF NOT EXISTS `' . $item['column_name'] . '` ' . $item['column_type'] . $length . $nullable . $default_value . ';' . PHP_EOL;
                } else {
                    $column_type = $item['column_type'];
                    if ($item['column_type'] == 'HTML') {
                        $column_type = 'VARCHAR';
                    }
                    $sql_lang .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $tableMapping->getTableName() . '_lang` ADD COLUMN IF NOT EXISTS `' . $item['column_name'] . '` ' . $column_type . $length . $nullable . $default_value . ';' . PHP_EOL;
                }
                $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;

                if ($alterShopTable) {
                    $sql_shop .= 'ALTER TABLE `/*_DB_PREFIX_*/' . $tableMapping->getTableName() . '_shop` ADD COLUMN IF NOT EXISTS `' . $item['column_name'] . '` ' . $item['column_type'] . $length . $nullable . $default_value . ';' . PHP_EOL;
                }
                if (!empty($item['column_length']) && $item['column_length'] !== '') {
                    $size = (int)$item['column_length'];
                }
                if ($item['column_type'] === 'INT' || $item['column_type'] === 'UnsignedInt') {
                    $type = '/*self::TYPE_INT*/';
                    $fieldsDef[$index] = "['type'=>" . $type;
                    if ($item['column_type'] === 'UnsignedInt') {
                        $validate = "'isUnsignedInt'";
                        $fieldsDef[$index] = "['type'=>" . $type . ", 'validate'=>" . $validate;
                    }

                }

                if ($item['column_type'] === 'EMAIL' || $item['column_type'] === 'VARCHAR' || $item['column_type'] === 'HTML' || $item['column_type'] === 'PERCENT') {
                    $type = '/*self::TYPE_STRING*/';
                    if ($item['column_type'] === 'EMAIL') {
                        $validate = "'isEmail'";
                    }
                    if ($item['column_type'] === 'HTML') {
                        $type = '/*self::TYPE_HTML*/';
                        $validate = "'isCleanHtml'";
                    }
                    if ($item['column_type'] === 'VARCHAR') {
                        $validate = "'isGenericName'";
                    }
                    $fieldsDef[$index] = "['type'=>" . $type . ", 'validate'=>" . $validate;
                }
                if ($item['column_type'] === 'DECIMAL' || $item['column_type'] === 'FLOAT') {
                    $type = '/*self::TYPE_FLOAT*/';
                    $validate = "'isPrice'";
                    $fieldsDef[$index] = "['type'=>" . $type . ", 'validate'=>" . $validate;
                }
                if ($item['column_type'] === 'TEXT' || $item['column_type'] === 'LONGTEXT') {
                    $type = '/*self::TYPE_STRING*/';
                    $fieldsDef[$index] = "['type'=>" . $type;
                }
                if ($item['column_type'] === 'TINYINT' || $item['column_type'] === 'BOOLEAN') {
                    $type = '/*self::TYPE_BOOL*/';
                    $validate = "'isBool'";
                    $fieldsDef[$index] = "['type'=>" . $type . ", 'validate'=>" . $validate;
                }

                if ($id_date=($item['column_type'] === 'DATE' || $item['column_type'] === 'DATETIME')) {
                    $fieldsDef[$index] = "['type'=>/*self::TYPE_DATE*/, 'validate'=>'isDate', 'copy_post'=> false";
                }
                $id_date=($item['column_type'] === 'DATE' || $item['column_type'] === 'DATETIME');
                if (!empty($size) && !$id_date) {
                    $fieldsDef[$index] = $fieldsDef[$index] . ", 'size'=>" . $size;
                }
                if (!empty($item['is_column_lang']) && $item['is_column_lang'] == 1) {
                    $fieldsDef[$index] = $fieldsDef[$index] . ", 'lang'=>true";
                    $there_is_lang_field = true;
                }
                if (!empty($item['is_column_shop']) && $item['is_column_shop'] == 1) {
                    $fieldsDef[$index] = $fieldsDef[$index] . ", 'shop'=>true";
                }
                $fieldsDef[$index] .= "];";

                $constructBody .= "self::/+definition['fields']['" . $item['column_name'] . "'] =" . $fieldsDef[$index] . PHP_EOL;

            }
            $this->setHookContent($modelData['class'], $modelData['fields'], $tableMapping, $there_is_lang_field);
            if ($modelData['class'] === 'Product') {
                $constructBody .= "parent::__construct(/+id_product, /+full, /+id_lang,/+id_shop, /+context);" . PHP_EOL;
            }
            if ($modelData['class'] === 'Category') {
                $constructBody .= "parent::__construct(/+idCategory, /+idLang, /+idShop);" . PHP_EOL;
            }
            if ($modelData['class'] === 'Customer') {
                $constructBody .= "parent::__construct(/+id);" . PHP_EOL;
            }
            $constructBody = str_replace(array("/*", "*/", "/+"), array("", "", "$"), $constructBody);
            $installContent = file($this->module_dir . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'install.php');
            $key = 28;

            if (is_array($installContent) && !empty($installContent)) {
                foreach ($installContent as $index => $row) {
                    if (md5($row) == 'd5bdf236268edd2d11313fc7b7ce4a0b') {
                        $key = $index;
                    }
                }
            }

            if (!empty($sql)) {
                $sql = str_replace(array("/*", "*/"), array("'.", ".'"), $sql);
                $sql = '$sql[]=' . "'" . $sql . "';" . PHP_EOL;
            }
            if (!empty($sql_shop)) {
                $sql_shop = str_replace(array("/*", "*/"), array("'.", ".'"), $sql_shop);
                $sql_shop = '$sql[]=' . "'" . $sql_shop . "';" . PHP_EOL;
            }
            if (!empty($sql_lang)) {
                $sql_lang = str_replace(array("/*", "*/"), array("'.", ".'"), $sql_lang);
                $sql_lang = '$sql[]=' . "'" . $sql_lang . "';" . PHP_EOL;
            }
            $alterSql = $sql . $sql_lang . $sql_shop;
            $installContent = $this->arrayInsertAfter($installContent, (int)$key, $alterSql);
            file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'sql/install.php', implode("", $installContent));
            $method->setBody($constructBody);
            $printer = new Printer;
            $code = $printer->printClass($class);
            $code = str_replace('App\Service\ModuleGenerator', $modelData['class'] . 'Core', $code);
            file_put_contents($overrideClassDir . DIRECTORY_SEPARATOR . $modelData['class'] . '.php', '<?php');
            file_put_contents($overrideClassDir . DIRECTORY_SEPARATOR . $modelData['class'] . '.php', PHP_EOL, FILE_APPEND);
            file_put_contents($overrideClassDir . DIRECTORY_SEPARATOR . $modelData['class'] . '.php', $code, FILE_APPEND);

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

    private function setHookContent($class, $fields, $tableMapping, $there_is_lang_field)
    {
        $sql = '';
        $sql_lang = '';
        $sql_shop = '';
        $content = '';
        /* if ($class == 'Product') {
             $this->module_data['hooks'][] = 'actionProductSave';
             $content .= "/+product = new Product(/+params['id_product']);" . PHP_EOL;
             $content .= "if (Validate::isLoadedObject(/+product)) {" . PHP_EOL;
             foreach ($fields as $index => $item) {
                 $alterLangTable = !empty($item['is_column_lang']) && $tableMapping->getHasLangTable() == true;
                 if (!$alterLangTable) {
                     $sql .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "', ['" . $item['column_name'] . "' => Tools::getValue('" . $item['column_name'] . "')], 'id_product=' . (int) /+product->id);" . PHP_EOL;
                 } else {
                     $sql_lang .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_lang', ['" . $item['column_name'] . "' => Tools::getValue('" . $item['column_name'] . "')], 'id_product=' . (int) /+product->id);" . PHP_EOL;
                 }
                 $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;

                 if ($alterShopTable) {
                     $sql_shop .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_shop', ['" . $item['column_name'] . "' => Tools::getValue('" . $item['column_name'] . "')], 'id_product=' . (int) /+product->id);" . PHP_EOL;
                 }
             }
             $content .= $sql . $sql_lang . $sql_shop;
             $content .= "}";
             $this->module_data['hooksContents']['actionProductSave'] = $content;
         }*/
        if ($class == 'Category') {
            $this->module_data['hooks']['category'] = ['actionCategoryFormBuilderModifier', 'actionAfterCreateCategoryFormHandler', 'actionAfterUpdateCategoryFormHandler'];
            $content .= "/+formBuilder = /+params['form_builder'];" . PHP_EOL;
            if ($there_is_lang_field) {
                $content .= "/+languages = Language::getLanguages();" . PHP_EOL;
            }
            $formBuilder = "/+category=new Category((int) /+params['id']);" . PHP_EOL;
            $codeForUpdate = "/+id_category = /+params['id'];" . PHP_EOL;
            $codeForUpdate .= "/+languages = Language::getLanguages();" . PHP_EOL;
            foreach ($fields as $index => $item) {
                if (!empty($item['is_column_lang'])) {
                    $type = "PrestaShopBundle\Form\Admin\Type\TranslateType::class";
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', " . $type . ", [
                         'type' => Symfony\Component\Form\Extension\Core\Type\TextType::class,
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'required' => false,
                         'locales' => /+languages,
                     ]);" . PHP_EOL;
                    $formBuilder.="foreach (/+languages as /+lang) {". PHP_EOL;
                    $formBuilder.="/+id_lang = /+lang['id_lang'];". PHP_EOL;
                    $formBuilder .= "/+params['data']['" . $item['column_name'] . "'][/+id_lang] = /+category->" . $item['column_name'] . "[/+id_lang];" . PHP_EOL;
                    $formBuilder.="}". PHP_EOL;
                } elseif ($item['column_type'] === 'TINYINT') {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\SwitchType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'choices' => [
                             'OFF' => false,
                             'ON' => true,
                         ],
                     ]);" . PHP_EOL;
                } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\DatePickerType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'required' => false,
                     ]);" . PHP_EOL;
                } else {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'required' => false,
                     ]);" . PHP_EOL;
                }
                if (empty($item['is_column_lang'])) {
                    $formBuilder .= "/+params['data']['" . $item['column_name'] . "'] = /+category->" . $item['column_name'] . ";" . PHP_EOL;
                }
                $alterLangTable = !empty($item['is_column_lang']) && $tableMapping->getHasLangTable() == true;

                if (!$alterLangTable) {
                    $sql .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "', ['" . $item['column_name'] . "' => /+params['form_data']['".$item['column_name']."']], 'id_category=' . (int) /+id_category);" . PHP_EOL;
                } else {
                    $sql_lang.="foreach (/+languages as /+lang) {". PHP_EOL;
                    $sql_lang.="/+id_lang = /+lang['id_lang'];". PHP_EOL;
                    $sql_lang .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_lang', ['" . $item['column_name'] . "' => /+params['form_data']['".$item['column_name']."'][/+id_lang]], 'id_category=' . (int) /+id_category);" . PHP_EOL;
                    $sql_lang.="}". PHP_EOL;
                }
                $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;

                if ($alterShopTable) {
                    $sql_shop .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_shop', ['" . $item['column_name'] . "' => /+params['form_data']['".$item['column_name']."']], 'id_category=' . (int) /+id_category);" . PHP_EOL;
                }
            }
            $content .= $formBuilder . PHP_EOL;
            $content .= "/+formBuilder->setData(/+params['data']);" . PHP_EOL;
            $this->module_data['hooksContents']['actionCategoryFormBuilderModifier'] = $content;
            $this->module_data['hooksContents']['actionAfterCreateCategoryFormHandler'] = "/+this->updateCategoryData(/+params['form_data'], /+params);";
            $this->module_data['hooksContents']['actionAfterUpdateCategoryFormHandler'] = "/+this->updateCategoryData(/+params['form_data'], /+params);";
            $this->module_data['query']['Category'] = $codeForUpdate.$sql . PHP_EOL . $sql_lang . PHP_EOL . $sql_shop;
        }
        if ($class == 'Customer') {
            $this->module_data['hooks']['customer'] = ['actionCustomerFormBuilderModifier', 'actionAfterCreateCustomerFormHandler', 'actionAfterUpdateCustomerFormHandler'];
            $content .= "/+formBuilder = /+params['form_builder'];" . PHP_EOL;
            $formBuilder = "/+customer=new Customer((int) /+params['id']);" . PHP_EOL;
            $codeForUpdate = "/+id_customer = /+params['id'];" . PHP_EOL;
            foreach ($fields as $index => $item) {
                if ($item['column_type'] === 'TINYINT') {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\SwitchType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'choices' => [
                             'OFF' => false,
                             'ON' => true,
                         ],
                     ]);" . PHP_EOL;
                } elseif ($item['column_type'] === 'DATETIME' || $item['column_type'] === 'DATE') {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', PrestaShopBundle\Form\Admin\Type\DatePickerType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'required' => false,
                     ]);" . PHP_EOL;
                } else {
                    $content .= "/+formBuilder->add('" . $item['column_name'] . "', Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                         'label' => /+this->l('" . $item['column_name'] . "'),
                         'required' => false,
                     ]);" . PHP_EOL;
                }
                $formBuilder .= "/+params['data']['" . $item['column_name'] . "'] = /+customer->" . $item['column_name'] . ";" . PHP_EOL;

                    $sql .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "', ['" . $item['column_name'] . "' => /+params['form_data']['".$item['column_name']."']], 'id_customer=' . (int) /+id_customer);" . PHP_EOL;

                $alterShopTable = !empty($item['is_column_shop']) && $tableMapping->getHasShopTable() == true;

                if ($alterShopTable) {
                    $sql_shop .= "Db::getInstance()->update('" . $tableMapping->getTableName() . "_shop', ['" . $item['column_name'] . "' => /+params['form_data']['".$item['column_name']."']], 'id_customer=' . (int) /+id_customer);" . PHP_EOL;
                }
            }
            $content .= $formBuilder . PHP_EOL;
            $content .= "/+formBuilder->setData(/+params['data']);" . PHP_EOL;
            $this->module_data['hooksContents']['actionCustomerFormBuilderModifier'] = $content;
            $this->module_data['hooksContents']['actionAfterCreateCustomerFormHandler'] = "/+this->updateCustomerData(/+params['form_data'], /+params);";
            $this->module_data['hooksContents']['actionAfterUpdateCustomerFormHandler'] = "/+this->updateCustomerData(/+params['form_data'], /+params);";
            $this->module_data['query']['Customer'] = $codeForUpdate.$sql . PHP_EOL . $sql_lang . PHP_EOL . $sql_shop;
        }

        return true;
    }


}

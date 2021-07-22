<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;

class ModuleGenerator
{
    private $base_dir;
    private $module_dir;
    public $module_data;

    public function __construct($base_dir, $module_dir, $module_data)
    {
        $this->base_dir = $base_dir;
        $this->module_dir = $module_dir;
        $this->module_data = $module_data;
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
            if(empty($file['tmp_name'])){
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
     * @return bool
     */
    public function generateIndex()
    {
        $content = file_get_contents($this->base_dir . '/samples/index.php');
        $params = $this->getParams();
        $content = str_replace(array('$moduleName', '$moduleDisplayName', '$moduleDescription', '$company_name'), array($params['lower']['module_name'], $this->module_data['display_name'], $this->module_data['description'], $params['upper']['company_name']), $content);

        file_put_contents($this->module_dir . DIRECTORY_SEPARATOR . 'index.php', $content);
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
            $register_hooks = '';
            $class = new ClassType('demo');
            foreach ($hooks as $hook) {
                $register_hooks .= " && method->registerHook('" . $hook . "')\n";
                $hook = ucfirst($hook);
                if (strpos($content, 'hook' . $hook) === false) {
                    $class->addMethod('hook' . $hook)->addParameter('params');
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

        foreach ($this->module_data['commands'] as $key=>$commandData) {

            $content = file_get_contents($this->base_dir . '/samples/src/Command/SampleCommand.php');
            $content = $this->replaceStandardStrings($content);
            $content = str_replace('SampleCommand', $commandData['class'], $content);
            $callCommand = $commandData['call'] ?? $commandData['class'] . ':execute';
            $content = str_replace('command_call', $callCommand, $content);
            $commandDir=$this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Command';
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
            $helperDir=$this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Helper';
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
            $content = str_replace('Service', $service_name, $content);
            $serviceDir=$this->module_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service';
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

}

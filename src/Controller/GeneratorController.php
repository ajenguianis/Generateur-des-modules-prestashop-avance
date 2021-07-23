<?php

namespace App\Controller;


use App\Service\ModuleGenerator;
use HZip;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;


class GeneratorController extends AbstractController
{

    private $urlGenerator;
    /**
     * @var string
     */
    private $dir;
    /**
     * @var ModuleGenerator
     */
    private $_codeGen;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/", name="app_home")
     */
    public function index(Request $request)
    {

        $user = $this->getUser();
        if (!$user) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $connection = $this->getDoctrine()->getConnection();
        $statement = $connection->prepare("
				SELECT `name` FROM ps_hook s");

        $statement->execute();
        $hooks = $statement->fetchAll();

        return $this->render('views/index.html.twig', ['hooks' => $hooks]);
    }


    /**
     * @Route("/generate", name="generate_module")
     */
    public function generateAction(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $base_dir = $this->getParameter('kernel.project_dir');
        $this->dir = $base_dir . DIRECTORY_SEPARATOR . 'downloads';
        $module_name = $request->request->get('module_name');

        $module_dir = $this->dir . '/' . $module_name;
        if (!is_dir($module_dir) && !@mkdir($module_dir, 0777, true) && !is_dir($module_dir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $module_dir));
        }
        $this->_codeGen = $this->buildGenerator($request, $base_dir, $module_dir);

        $this->_codeGen->generateComposer();

        if (!empty($_FILES['module_logo'])) {
            $this->_codeGen->setLogo();
        }

        $this->_codeGen->generateConfig();
        $this->_codeGen->generateReadMe();
        $this->_codeGen->generateIndex();
        $this->_codeGen->copyStansardDir();
        $this->_codeGen->generateModuleClass();
        if ($request->request->get('log_sys')) {
            $this->_codeGen->generateLogPachage();
        }
        if (isset($this->_codeGen->module_data['commands']) && !empty($this->_codeGen->module_data['commands'])) {
            $this->_codeGen->generateCommands();
        }
        if (isset($this->_codeGen->module_data['helpers']) && !empty($this->_codeGen->module_data['helpers'])) {
            $this->_codeGen->generateHelpers();
        }
        if (isset($this->_codeGen->module_data['services']) && !empty($this->_codeGen->module_data['services'])) {
            if(!$request->request->get('log_sys')){
                $this->_codeGen->generateLogPachage();
            }
            $this->_codeGen->generateServices();
        }

        $zip_path = $base_dir.'/downloads/'.$module_name.'.zip';
        HZip::zipDir($module_dir, $zip_path);
        $response = new Response();

        if (file_exists($zip_path))
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$module_name.'.zip');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zip_path));
            ob_clean();
            flush();
            readfile($zip_path);
            exit;
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param string $base_dir
     * @param string $module_dir
     * @return ModuleGenerator
     */
    private function buildGenerator(Request $request, string $base_dir, string $module_dir)
    {
        $commands = [];
        $helpers = [];
        $services = [];
        if (!empty($data = $request->request->all())) {
            foreach ($request->request->all() as $key => $item) {

                if (strpos($key, 'command_name') !== false) {
                    $commands[$key]['class'] = $item;
                    $callKey = str_replace('command_name', 'command_call', $key);
                    if (isset($data[$callKey])) {
                        $commands[$key]['call'] = $data[$callKey];
                    }
                }
                if (strpos($key, 'helper_name') !== false) {
                    $helpers[] = $item;

                }
                if (strpos($key, 'service_name') !== false) {
                    $services[] = $item;
                }
            }

        }

        $data = $request->request->all();
        if (!empty($commands)) {
            $data = array_merge(['commands' => $commands], $data);
        }
        if (!empty($helpers)) {
            $data = array_merge(['helpers' => $helpers], $data);
        }
        if (!empty($services)) {
            $data = array_merge(['services' => $services], $data);
        }
        return new ModuleGenerator($base_dir, $module_dir, $data);
    }


}

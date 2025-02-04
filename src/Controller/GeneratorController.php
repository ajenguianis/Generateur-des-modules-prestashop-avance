<?php

namespace App\Controller;

use App\Service\Hzip;
use App\Service\ModuleGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    /**
     * @var EntityManagerInterface
     */
    private $_em;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->_em = $entityManager;
    }

    /**
     * @Route("/", name="app_home")
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        if (! $user) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $connection = $this->getDoctrine()->getConnection();
        $statement = $connection->prepare("
				SELECT `name` FROM hook s");

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
        $filesystem = new Filesystem();

        if(is_dir($module_dir)) {
            $filesystem->remove($module_dir);
        }

        if (file_exists($this->dir . '/' . $module_name . '.zip')) {
            $filesystem->remove($this->dir . '/' . $module_name . '.zip');
        }

        if (! is_dir($module_dir) && ! @mkdir($module_dir, 0777, true) && ! is_dir($module_dir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $module_dir));
        }
        $this->_codeGen = $this->buildGenerator($request, $base_dir, $module_dir);

        $this->_codeGen->generateComposer();

        if (! empty($_FILES['module_logo'])) {
            $this->_codeGen->setLogo();
        }

        $this->_codeGen->generateConfig();
        $this->_codeGen->generateReadMe();
        $this->_codeGen->generateIndex();
        $this->_codeGen->copyStansardDir();
        if ($request->request->get('log_sys')) {
            $this->_codeGen->generateLogPachage();
        }

        if (isset($this->_codeGen->module_data['commands']) && ! empty($this->_codeGen->module_data['commands'])) {
            $this->_codeGen->generateCommands();
        }
        if (isset($this->_codeGen->module_data['helpers']) && ! empty($this->_codeGen->module_data['helpers'])) {
            $this->_codeGen->generateHelpers();
        }
        if (isset($this->_codeGen->module_data['services']) && ! empty($this->_codeGen->module_data['services'])) {
            if (! $request->request->get('log_sys')) {
                $this->_codeGen->generateLogPachage();
            }
            $this->_codeGen->generateServices();
        }

        if (isset($this->_codeGen->module_data['models']) && ! empty($this->_codeGen->module_data['models'])) {
            $this->_codeGen->generateModels();
        }
        if (isset($this->_codeGen->module_data['objectModels']) && ! empty($this->_codeGen->module_data['objectModels'])) {
            $this->_codeGen->generateModelCustomFields();
        }
        if (isset($this->_codeGen->module_data['imageFormModels']) && ! empty($this->_codeGen->module_data['imageFormModels'])) {
            $this->_codeGen->generateModelCustomImages();
        }
        $this->_codeGen->generateModuleClass();
        if (isset($this->_codeGen->module_data['settings']) && ! empty($this->_codeGen->module_data['settings'])) {
            $this->_codeGen->generateSettings();
        }
        if (isset($this->_codeGen->module_data['imageFormModels']) && ! empty($this->_codeGen->module_data['imageFormModels'])) {
            $this->_codeGen->copyForm();
        }

        $zip_path = $base_dir . '/downloads/' . $module_name . '.zip';

        Hzip::zipDir($module_dir, $zip_path);
        $response = new Response();

        if (file_exists($zip_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . $module_name . '.zip');
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
        $models = [];
        $objectModels = [];
        $ImageFormModels = [];
        $hooks = [];
        if (! empty($data = $request->request->all())) {
            $i = 0;
            foreach ($request->request->all() as $key => $item) {
                if (! empty($item) && strpos($key, 'command_name') !== false) {
                    $commands[$key]['class'] = $item;
                    $callKey = str_replace('command_name', 'command_call', $key);
                    if (isset($data[$callKey])) {
                        $commands[$key]['call'] = $data[$callKey];
                    }
                }
                if (! empty($item) && strpos($key, 'setting_name') !== false) {
                    $settings[$i]['name'] = $item;
                    $labelKey = str_replace('setting_name', 'setting_label', $key);
                    if (isset($data[$labelKey])) {
                        $settings[$i]['label'] = $data[$labelKey];
                    }
                    $typeKey = str_replace('setting_name', 'setting_type', $key);
                    if (isset($data[$typeKey])) {
                        $settings[$i]['type'] = $data[$typeKey];
                    }
                    $descriptionKey = str_replace('setting_name', 'setting_description', $key);
                    if (isset($data[$descriptionKey])) {
                        $settings[$i]['description'] = $data[$descriptionKey];
                    }
                    $i++;
                }
                if (! empty($item) && strpos($key, 'helper_name') !== false) {
                    $helpers[] = $item;
                }
                if (! empty($item) && strpos($key, 'service_name') !== false) {
                    $services[] = $item;
                }
                if (! empty($item) && strpos($key, 'new_hook') !== false) {
                    $hooks[$item] = $item;
                }
                if (! empty($item) && strpos($key, 'object_name') !== false) {
                    $object_name_key = explode('_', $key);
                    $objectIteration = $object_name_key[2];
                    $models[$objectIteration]['class'] = $item;
                    $models[$objectIteration]['table'] = $data['table_name_' . $objectIteration];
                }
                if (! empty($item) && strpos($key, 'field_name') !== false) {
                    $field_name_key = explode('_', $key);
                    $objectIteration = $field_name_key[3];
                    $fieldIteration = $field_name_key[2];
                    $combinIteration = $fieldIteration . '_' . $objectIteration;
                    if (empty($item) || ! isset($data['field_type_' . $combinIteration]) || empty($data['field_type_' . $combinIteration])) {
                        continue;
                    }
                    if (! empty($data['is_auto_increment_' . $combinIteration])) {
                        $models[$objectIteration]['primary'] = $item;
                    }
                    $models[$objectIteration]['fields'][$fieldIteration]['field_name'] = $item;
                    $models[$objectIteration]['fields'][$fieldIteration]['field_type'] = $data['field_type_' . $combinIteration];
                    $models[$objectIteration]['fields'][$fieldIteration]['field_length'] = $data['field_length_' . $combinIteration] ?? null;
                    $models[$objectIteration]['fields'][$fieldIteration]['is_auto_increment'] = $data['is_auto_increment_' . $combinIteration] ?? null;
                    $models[$objectIteration]['fields'][$fieldIteration]['is_nullable'] = $data['is_nullable_' . $combinIteration] ?? null;
                    $models[$objectIteration]['fields'][$fieldIteration]['is_lang'] = $data['is_lang_' . $combinIteration] ?? null;
                    $models[$objectIteration]['fields'][$fieldIteration]['is_shop'] = $data['is_shop_' . $combinIteration] ?? null;
                    $models[$objectIteration]['fields'][$fieldIteration]['default_value'] = $data['default_value_' . $combinIteration] ?? null;
                }
                if (! empty($item) && strpos($key, 'object_model_name') !== false) {
                    $object_name_key = explode('_', $key);
                    if (! isset($object_name_key[3])) {
                        continue;
                    }
                    $objectIteration = $object_name_key[3];
                    $objectModels[$objectIteration]['class'] = $item;
                }
                if (! empty($item) && strpos($key, 'column_name') !== false) {
                    $field_name_key = explode('_', $key);

                    $objectIteration = $field_name_key[3];
                    $fieldIteration = $field_name_key[2];
                    $associationIteration = $fieldIteration . '_' . $objectIteration;

                    if (empty($item) || ! isset($data['column_type_' . $associationIteration]) || empty($data['column_type_' . $associationIteration])) {
                        continue;
                    }

                    $objectModels[$objectIteration]['fields'][$fieldIteration]['column_name'] = $item;
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['column_type'] = $data['column_type_' . $associationIteration];
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['column_length'] = $data['column_length_' . $associationIteration] ?? null;
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['is_column_nullable'] = $data['is_column_nullable_' . $associationIteration] ?? null;
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['is_column_lang'] = $data['is_column_lang_' . $associationIteration] ?? null;
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['is_column_shop'] = $data['is_column_shop_' . $associationIteration] ?? null;
                    $objectModels[$objectIteration]['fields'][$fieldIteration]['default_column_value'] = $data['default_column_value_' . $associationIteration] ?? null;
                    if (! empty($data['add_to_listing_' . $associationIteration])) {
                        $objectModels[$objectIteration]['listing'][$fieldIteration] = $objectModels[$objectIteration]['fields'][$fieldIteration];
                    }
                }
                if (! empty($item) && strpos($key, 'object_model_image') !== false) {
                    $object_name_key = explode('_', $key);

                    if (! isset($object_name_key[3])) {
                        continue;
                    }
                    $objectIteration = $object_name_key[3];
                    $ImageFormModels[$objectIteration]['class'] = $item;
                }
                if (! empty($item) && strpos($key, 'column_image_name') !== false) {
                    $field_name_key = explode('_', $key);

                    $objectIteration = $field_name_key[4];
                    $fieldIteration = $field_name_key[3];
                    $associationIteration = $fieldIteration . '_' . $objectIteration;

                    $ImageFormModels[$objectIteration]['fields'][$fieldIteration]['column_image_name'] = $item;
                    $ImageFormModels[$objectIteration]['fields'][$fieldIteration]['column_image_width'] = $data['column_image_width_' . $associationIteration];
                    $ImageFormModels[$objectIteration]['fields'][$fieldIteration]['column_image_height'] = $data['column_image_height_' . $associationIteration] ?? null;
                }
            }
        }

        $data = $request->request->all();

        if (! empty($commands)) {
            $data = array_merge(['commands' => $commands], $data);
        }
        if (! empty($helpers)) {
            $data = array_merge(['helpers' => $helpers], $data);
        }
        if (! empty($services)) {
            $data = array_merge(['services' => $services], $data);
        }
        if (! empty($models)) {
            $data = array_merge(['models' => $models], $data);
        }
        if (! empty($objectModels)) {
            $data = array_merge(['objectModels' => $objectModels], $data);
        }
        if (! empty($ImageFormModels)) {
            $data = array_merge(['imageFormModels' => $ImageFormModels], $data);
        }
        if (! empty($settings)) {
            $data = array_merge(['settings' => $settings], $data);
        }
        if (! empty($hooks)) {
            if (empty($data['hooks'])) {
                $data['hooks'] = [];
            }
            $data['hooks'] = array_merge($data['hooks'], array_values($hooks));
        }

        return new ModuleGenerator($base_dir, $module_dir, $data, $this->_em);
    }
}

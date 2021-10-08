
/**
 * Load the configuration form
 */
public function getContent()
{
    /**
     * If values have been submitted in the form, process.
     */
    if (((bool)Tools::isSubmit('submitModuleclassModule')) == true) {
        $this->postProcess();
    }

    $this->context->smarty->assign('module_dir', $this->_path);

    $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

    return $this->renderForm();
}

/**
 * Create the form that will be displayed in the configuration of your module.
 */
protected function renderForm()
{
    $helper = new HelperForm();

    $helper->show_toolbar = false;
    $helper->table = $this->table;
    $helper->module = $this;
    $helper->default_form_language = $this->context->language->id;
    $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

    $helper->identifier = $this->identifier;
    $helper->submit_action = 'submitModuleclassModule';
    $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');

    $helper->tpl_vars = array(
        'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
        'languages' => $this->context->controller->getLanguages(),
        'id_language' => $this->context->language->id,
    );

    return $helper->generateForm(array($this->getConfigForm()));
}

/**
 * Create the structure of your form.
 */
protected function getConfigForm()
{
    return array(
        'form' => array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => 'form_inputs',
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ),
    );
}

/**
 * Set values for the inputs.
 */
protected function getConfigFormValues()
{
    return 'config_form_value';
}

/**
 * Save form data.
 */
protected function postProcess()
{
    $form_values = $this->getConfigFormValues();

    foreach (array_keys($form_values) as $key) {
        Configuration::updateValue($key, Tools::getValue($key));
    }
}
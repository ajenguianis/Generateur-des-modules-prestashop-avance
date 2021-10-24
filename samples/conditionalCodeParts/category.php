
public function getCategoryChoices($inputName)
{
    $root = Category::getRootCategory();

//Generating the tree
    $tree = new HelperTreeCategories('categories_1'); //The string in param is the ID used by the generated tree
    $tree->setUseCheckBox(false)
        ->setAttribute('is_category_filter', $root->id)
        ->setRootCategory($root->id)
        ->setSelectedCategories(array((int)Configuration::get('setting_name'))) //if you wanted to be pre-carged
        ->setInputName($inputName); //Set the name of input. The option "name" of $fields_form doesn't seem to work with "categories_select" type
    return $tree->render();
}
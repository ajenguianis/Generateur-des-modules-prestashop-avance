
/**
 * @return array
 */
public function getProductChoices()
{
    $qb = new DbQuery();
    $qb
        ->select('pl.name, p.id_product')
        ->from('product', 'p')
        ->innerJoin('product_lang', 'pl', 'pl.id_product= p.id_product')
        ->where('p.active = 1')
        ->orderBy('pl.name');
    $products = Db::getInstance()->executeS($qb);
    $choices = [];
    if (!empty($products)) {
        foreach ($products as $product) {
            $choices[] = ['id_product' => $product['id_product'], 'name' => $product['name']];
        }
    }
    return $choices;
}
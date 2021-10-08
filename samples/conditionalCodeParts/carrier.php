
/**
 * @return array
 */
public function getCarrierChoices()
{
    $qb = new DbQuery();
    $qb
        ->select('cl.delay, c.id_carrier, c.id_reference, c.name')
        ->from('carrier', 'c')
        ->innerJoin('carrier_lang', 'cl', 'c.id_carrier= cl.id_carrier')
        ->where('c.active = 1')
        ->where('c.deleted = 0')
        ->orderBy('c.name');
    $carriers = Db::getInstance()->executeS($qb);
    $choices = [];
    if (!empty($carriers)) {
        foreach ($carriers as $carrier) {
            $choices[] = ['id_reference'=>$carrier['id_reference'], 'name'=>$carrier['id_carrier'] . ' - ' . $carrier['name'] . ' - ' . $carrier['delay']];
        }
    }
    return $choices;
}
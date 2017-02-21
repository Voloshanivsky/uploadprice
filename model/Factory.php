<?php
  /* (c) 2010 Sanya Vol., shv@ukr.net */
class FactoryTable extends RecordpageTable {
  function __construct() {
    parent::__construct();
    $this->addFields(array(
        'material' => 'text',
        'delivery' => 'text',
        'pict' => 'varchar(4)',
        'availtext' => 'varchar(255)',
    ));
    $this->addField(new Field('soft', 'tinyint', false, 1));
    $this->addField(new Field('order', 'int', false, 9999));
    $this->addField(new Field('waiting', 'int', false, 0)); // время ожидания
    $this->addField(new Field('country', 'int', false, 0)); // страна производитель
    $this->addField(new Field('filter', 'tinyint', false, 1));
    $this->addField(new Field('iddivani', 'int', false, 0)); // id factory from divani.kiev.ua
    $this->addField(new Field('noactual', 'tinyint', false, 0));
    $this->addField(new Field('currency', 'tinyint(1)', false, 0));//цены в валюте
    $this->addField(new Field('exchange', 'float', false, 0));//курс валюты
    $this->addField(new Field('door', 'tinyint(1)', false, 0));//цены в валюте
    $this->addField(new Field('classname', 'varchar(20)', false, ''));// имя класса для загрузки прайсов
    $this->addManyToMany('admin', false);
    $this->addIndex('order');
  }
}

class Factory extends Recordpage {

  static function getByOrder($order) {
    return Record::getSingle('order',$order,'factory');
  }
  static function getByClassName($order) {
    return Record::getSingle('classname',$order,'factory');
  }
  function getArrayIds($entity) {
    $colEnt = ucfirst($entity) . 'Collection';
    $col = new $colEnt();
    return $col->getArrayIds(array($this->Name() => $this->getId()));
  }
//  function
  function getDoorparPrice($dpar) {
    $fhasd = factoryHasdoorpar::getByIds('factoryhasdoorpar', array('doorpar'=>$dpar, 'factory'=>$this->getId()));
    if ($fhasd) {
      return $fhasd->getPrice();
    } else {
      return 0;
    }
  }
  function getDiffPercent($admin_id) {
    $ahf = Entity::getByIds('adminHasfactory', array('admin' => $admin_id, 'factory' => $this->getId()));

    if($ahf) {
      return $ahf->getDiffPercent();
    } else {
      return 0;
    }
  }
  function getClassName() {
    return $this->getField('classname');
  }
  function getPriceupload($filename, $dir) {
    if ($this->getClassName() == '') {
      throw new Exception("Error created priceupload", 1);
    }
    $classname = 'Priceupload'.ucfirst($this->getClassName());
    $object = new $classname($filename, $this->getId(), $dir);
    return $object;
  }
  function getDoor() {
    return $this->getField('door');
  }
  function getCurrency() {
    return $this->getField('currency');
  }
  function getExchange() {
    return $this->getField('exchange');
  }
  function getNoActual() {
    return $this->getField('noactual');
  }
  function getIdDivani() {
    return $this->getField('iddivani');
  }
  function getFilter() {
    return $this->getField('filter');
  }
  function getSoft() {
    return $this->getField('soft');
  }
  function getOrder() {
    return $this->getField('order');
  }
  function getMaterial() {
    return $this->getField('material');
  }
  function getDelivery() {
    return $this->getField('delivery');
  }
  function getAvailtext() {
    return $this->getField('availtext');
  }
  function getWaitingId() {
    return $this->getField('waiting');
  }
  function getWaiting() {
    if ($this->getWaitingId() == 0) {
      return false;
    }
    return new Waiting($this->getWaitingId());
  }
  function getWaitingName() {
    if ($this->getWaitingId() == 0) {
      return '';
    }
    return $this->getWaiting()->getName();
  }
  function getCountryId() {
    return $this->getField('country');
  }
  function getCountry() {
    if ($this->getCountryId() == 0) {
      return false;
    }
    return new Country($this->getCountryId());
  }
  function getCountryName() {
    if ($this->getCountryId() == 0) {
      return '';
    }
    return $this->getCountry()->getName();
  }
  function getRKindFacade($active = null) {
    $col = new RkindfacadeCollection();
    return $col->getRecordsByFields(array('factory' => $this->getId()), 'order', $active);
  }
  function getRKindCorp($active = null) {
    $col = new RkindcorpCollection();
    return $col->getRecordsByFields(array('factory' => $this->getId()), 'order', $active);
  }
  function getRKind($kind = 'rkindfacade', $active = null) {
    $colname = ucfirst($kind).'Collection';
    $col = new $colname();
    return $col->getRecordsByFields(array('factory' => $this->getId()), 'order', $active);
  }
  function getCountRKindFacade($active = null) {
    return $this->getRKindFacade($active)->count();
  }
  function getCountRKindCorp($active = null) {
    return $this->getRKindCorp($active)->count();
  }
  function getCountRKind($kind = 'rkindfacade', $active = null) {
    return $this->getRKind($kind, $active)->count();
  }
  function getCategories($active = null) {
    $col = new CategoryCollection();
    return $col->getRecordsByFields(array('factory' => $this->getId()), 'order', $active);
  }
  function getCategoriesByKind($kind = null, $active = null) {
    if ($kind) {
      $col = new CategoryCollection();
      return $col->getRecordsByFields(array('factory' => $this->getId(), 'kind' => $kind), 'order', $active);
    } else {
      return $this->getCategories($active);
    }
  }
  function getGoodscatsByKind($kind = null, $active = null) {
//    if ($kind) {
//      $col = new goodsHascategoryCollection();
//      return $col->getRecordsByFields(array('factory' => $this->getId(), 'kind' => $kind), 'order', $active);
//    } else {
//      return $this->getCategories($active);
//    }
  }

// PICT  , FILE
  function getPict() {
    return $this->getField('pict');
  }
  function getDir() {
    return "content/{$this->Name()}/{$this->getId()}";
  }
  function getRealPathPict($p = null) {
    if ($p) { // list, page, big
      return "{$this->getDir()}/{$this->getId()}_{$p}.{$this->getPict()}";
    }
    return "{$this->getDir()}/{$this->getId()}_pict.{$this->getPict()}";
  }

  function getPicture() {
    $arr = $this->getCategories(1)->toArray();
    if (count($arr) == 0) {
      return '';
    }
    srand((float)microtime() * 1000000);
    shuffle($arr);
    return $arr[0]->getRealPathPict();
  }
  function getCountCategories($active = null) {
    return $this->getCategories($active)->count();
  }
//  function getCountProducts($active = null) {
//    $col = new DivanCollection();
//    return $col->getRecordsByFields(array('factory' => $this->getId()),'',$active)->count();
//    return 0;
//  }
  function getProducts($active = null) {
    $col = new ProductCollection();
    return $col->getRecordsByFields(array('factory' => $this->getId()),'',$active);
  }

  function getGoods($active = null) {
    $col = new GoodsCollection();
    $params['factory'] = $this->getId();
    if ($active) {
      $params['active'] = 1;
    }
    return $col->getByParams($params, 'name');
  }

  function getCountProducts($active = null) {
    $col = new ProductCollection();
    return $col->getRecordsByFields(array('factory' => $this->getId()),'',$active)->count();
  }

  function produceDoors() {
    $col = new GoodsCollection();
    $count = $col->getByParams(array('factory'=>$this->getId(), 'goodskind'=>81))->count();
    return $count;
  }

  function setNoActual($value) {
    return $this->setField('noactual', $value);
  }
  function setPict($value) {
    return $this->setField('pict', $value);
  }
  function setOrder($value) {
    return $this->setField('order', $value);
  }
  function setCountry($value) {
    return $this->setField('country', $value);
  }
  function setIdDivani($value) {
    return $this->setField('iddivani', $value);
  }

  function moveUp() {
    $col = new FactoryCollection();
    $upper = Factory::getByOrder($this->getOrder() - 1);
    if ($upper) {
      $v = $upper->getOrder();
      $upper->setOrder($this->getOrder());
      $this->setOrder($v);
      return true;
    }
    return false;
  }
  function moveDown() {
    $col = new FactoryCollection();
    $upper = Factory::getByOrder($this->getOrder() + 1);
    if ($upper) {
      $v = $upper->getOrder();
      $upper->setOrder($this->getOrder());
      $this->setOrder($v);
      return true;
    }
    return false;
  }


  function delete() {
    $this->deleteConnections('Category');
//    $this->deleteConnections('Product');
    if (file_exists($this->getRealPathPict())) {
      unlink($this->getRealPathPict());
    }
    rmdir($this->getDir());
    return parent::delete();
  }
}

class FactoryCollection extends RecordpageCollection {

  function add($data) {
    $db = DB::getInstance();
    $order = $db->fetchSingle("SELECT MAX(factory_order) FROM factory");
    $data['order'] = $order + 1;
    $item = parent::add($data);
    mkdir($item->getDir(),0777);
    return $item;
  }

  function getFactories($order = 'order') {
    return $this->getRecordsByFields(array(), $order);
  }
  function getParsingFabrics($order = 'order') {
    $this->addJoin('goods');
    $this->addJoin('parsing');
    $this->addFilter("1 GROUP BY factory.factory_id");
    
    return $this->getCustomIterator('', $order);
  }
  function getActiveFactories($order = 'order') {
    return $this->getRecordsByFields(array('active'=>1), $order);
  }
  function getPublicFactories() {
    return $this->getRecordsByFields(array('soft'=>1), 'order', 1);
  }

  function getByTcharter($tch, $order = "name") {
    $this->addJoin("goods");
    $this->addJoin("goodshastcharter");
    $this->addFilter("tcharter_id=$tch");
//    $this->add
    return $this->getCustomIterator("1 GROUP BY factory.factory_id", $order);
  }

  function getFactoriesByParams($params, $order = 'factory_name') {
//    $db = DB::getInstance();
//    $db->debug = true;
    unset($params['factory']);
//    $this->dumptable = 'factory LEFT JOIN goods ON (factory.factory_id=goods.factory_id)';
    $this->dumptable = 'factory NATURAL JOIN goods';
    $query = '1';
    if (isset($params['filter'])) {
      $query .= " AND factory_filter=1";
    }
    if (isset($params['goods_ids'])) {
      if (count($params['goods_ids']) > 0) {
        $query .= " AND goods_id IN (".join(",", $params['goods_ids']).")";
      }
    }
    if (isset($params['tcharter'])) {
      $this->dumptable .= " NATURAL JOIN goodshastcharter";
      $query .= " AND tcharter_id=".$params['tcharter'];
    }
    return new RowIterator("SELECT factory.factory_id AS id, factory_name AS name, COUNT(DISTINCT goods.goods_id) AS goods_count
                            FROM {$this->dumptable}
                            WHERE {$query}
                            GROUP BY factory.factory_id ORDER BY $order", new RowFetchAssoc());
  }

  function getByParams($params, $order = 'name') {
    if (isset($params['active'])) {
      $this->addFilter("factory_active=1");
    }
    if (isset($params['tcharter'])) {
      $this->addJoin("goods");
      $this->addJoin("goodshastcharter");
      $this->addFilter("tcharter_id=".$params['tcharter']);
    }
    return $this->getCustomIterator("", $order);
  }

  function getByIdDivanNotNull() {
    return new RowIterator("SELECT factory.* FROM factory WHERE NOT(factory_iddivan=0) ORDER BY factory_name", new RowFetchEntity($this));
  }

  function getFactoryIdsForCloth($cloth) {
    $from = 'factory';
    $where = '1';
    $from .= " NATURAL JOIN category NATURAL JOIN clothhascategory";
    $where .= " AND cloth_id=$cloth";
    $iteration = new RowIterator("SELECT factory_id FROM $from WHERE $where GROUP BY factory_id ORDER BY factory_id", new RowFetchValue());
    return $iteration->toArray();
  }

  function getFactoriesProduceDoors($order = 'name') {
//    DB::getInstance()->debug = true;
//    $this->addJoin('goods',' LEFT JOIN ',' ON(goods.factory_id=factory.factory_id)');
//    $this->addFilter("(goodskind_id=81 OR factory_door=1)");
    $this->addFilter("factory_door=1");
    $this->addFilter("1 GROUP BY factory.factory_id");
    return $this->getCustomIterator('', $order);
  }

}
?>

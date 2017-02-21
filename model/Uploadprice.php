<?php
/* (c) 2016 Voloshanivsky,  voloshanivsky@gmail.com */
class UploadpriceTable extends EntityTable{
  function __construct() {
    parent::__construct();
    $this->addManyToOne('factory');
    $this->addField(new Field('created', 'datetime', false, '2016-05-01 00:00:00'));
    $this->addField(new Field('rowstart', 'int', false, 0));
    $this->addField(new Field('colname', 'varchar(255)', false, ''));
    $this->addField(new Field('colprice', 'varchar(255)', false, ''));
    $this->addField(new Field('priceincurr', 'tinyint(1)', false, 0));// цены в валюте
    $this->addField(new Field('success', 'tinyint(1)', false, 0));
    $this->addField(new Field('rollback', 'tinyint(1)', false, 0));//был ли откат измений
    $this->addField(new Field('lastchange', 'datetime', false, '2016-05-01 00:00:00'));
    $this->addField(new Field('filename', 'varchar(255)', false, ''));
    $this->addField(new Field('ext', 'varchar(4)', false, ''));
  }
}

class Uploadprice extends Entity {

  function getFactoryDir() {
    return "content/priceupload/".$this->getFactoryId();
  }
  function getDir() {
    return $this->getFactoryDir()."/".$this->getId();
  }
  function getFilename() {
    return $this->getField('filename');
  }  
  function getExt() {
    return $this->getField('ext');
  }  
  function getRealFilename() {
    return $this->getFilename().'_'.$this->getId().'.'.$this->getExt();
  }
  function getRealPath() {
    return $this->getDir()."/".$this->getRealFilename();
  }
  function getFactoryId() {
    return $this->getField('factory');
  }
  function getFactory() {
    return new Factory($this->getFactoryId());
  }
  function getCreated($date = null) {
    return $this->getDateTime('created', $date);
  }
  function getRowstart() {
    return $this->getField('rowstart');
  }
  function getColname() {
    return $this->getField('colname');
  }
  function getColprice() {
    return $this->getField('colprice');
  }
  function getPriceInCurr() {
    return $this->getField('priceincurr');
  }
  function getSuccess() {
    return $this->getField('success');
  }
  function getRollback() {
    return $this->getField('rollback');
  }
  function getLastchange($date = null) {
    return $this->getDateTime('lastchange', $date);
  }

  function setExt($value) {
    return $this->setField('ext', $value);
  }  
  function setFilename($value) {
    return $this->setField('filename', $value);
  }
}

class UploadpriceCollection extends Collection {

  function add($data) {
    $item = parent::add($data);
    if (!file_exists($item->getFactoryDir())) {
      mkdir($item->getFactoryDir());
    }
    if (!file_exists($item->getDir())) {
      mkdir($item->getDir());
    }
    return $item;
  }
  
  function getByParams($params, $order = 'order') {
    return parent::getByParams($params, $order);
  }

}
?>
<?php
/* (c) 2014 Voloshanivsky, voloshanivsky@gmail.com */
class UploadpriceController extends Myadminv2Controller {

  private $entityName;
  private $factory;
  function __construct($params) {
    parent::__construct($params);
    $this->entityName = 'Uploadprice';
    $this->view->ctrl['name'] = strtolower($this->entityName);

    $this->view->assign('breadcrumbs', $this->breadcrumbs);
    if (!$this->section) {
      $this->view->ctrl['title'] = 'Фабрики';
    }
    $this->view->current_url = $this->view->ctrl['name'];
    if (isset($this->params['cid'])) {
      $this->factory = new Factory($this->params['cid']);
      $this->view->assign('factory', $this->factory);
      $this->view->assign('cid', $this->factory->getId());
    } else {
      $this->displayError('Error: factory not found');
      return;
    }
    $this->view->ctrl['title'] = "Загрузка прайсов. ".$this->factory->getName();
  }

  function ListAction() {
    $colName = $this->entityName.'Collection';
    $col = new $colName();
    $col = $col->getByParams(array('factory'=>$this->factory->getId()), 'created DESC');
    $this->list_action($this->entityName, $col);
  }
  function NewAction() {

    $this->breadcrumbs[2]['name'] = 'Добавление';
    $this->view->assign('breadcrumbs', $this->breadcrumbs);
    $colName = $this->entityName.'Collection';
    $col = new $colName();
    $col = $col->getByParams(array('factory'=>$this->factory->getId()), 'created DESC LIMIT 1')->toArray();
    if (count($col)) {
      $data = array(
          'rowstart' => $col[0]->getRowstart(),
          'colname' => $col[0]->getColname(),
          'colprice' => $col[0]->getColprice(),
        );
    } else {
      $data = array(
          'rowstart' => 1,
          'colname' => 1,
          'colprice' => 2,
        );
    }
    $this->view->assign('data', $data);
    $this->new_action($this->entityName);
  }

  function EditAction() {
    $this->view->assign('editor', true);

    $item = new $this->entityName($this->params['id']);
    $this->view->action['name'] = 'edit';
    $this->view->action['title'] = 'Редактирование';
    $this->view->current_url .= '_edit';
    // $this->breadcrumbs[2]['name'] = $item->getName();
    $this->view->assign('breadcrumbs', $this->breadcrumbs);

    $this->view->assign('item', $item);

    $this->view->assign('fileTpl', $this->dir_templ . strtolower($this->entityName).'/edit.tpl');
    $this->view->display($this->dir_templ . 'admin/default.tpl');
  }

}

class UploadpriceAjaxController extends Myadminv2AjaxController {

  private $entityName;
  private $colName;
  function __construct($params) {
    parent::__construct($params);
    $this->entityName = 'Uploadprice';
    $this->colName = $this->entityName.'Collection';
  }

  function ActiveAction() {
    return $this->active($this->entityName, $this->params);
  }

  function checkValidate($item = null) {

    $this->validateFields(array('rowstart', 'colname', 'colprice'), $item);

    $fileName = $_FILES['ext']['name'];
    if (empty($fileName)) {
      $this->setErrorStatus('ext', 'Выберите файл');
      return $this->result;
    }
    $pos = strrpos($fileName,'.');
    $this->params['ext'] = substr($fileName,$pos+1);
    $this->params['filename'] = $this->translit( substr($fileName,0,$pos) );

    return $this->result;

  }

  function AddAction() {
    $this->checkValidate();
    if ($this->result['error']) {
      return $this->result;
    }

    $col = new $this->colName();
    DB::getInstance()->begin();
    
    $this->params['created'] = date("Y-m-d H:i:s");
    $this->params['lastchange'] = date("Y-m-d H:i:s");
    $item = $col->add($this->params);
    
    if ( !move_uploaded_file($_FILES['ext']['tmp_name'],$item->getRealPath())){
      throw new Exception('File upload failed');
    }    
/* 
*  тут будет загрузка прайса с подключением модулей Ивана 
*  $object - объект загрузки прайсов для этой фабрики
*/
    $priceupload = $item->getFactory()->getPriceupload($item->getRealPath());
    $result_parse = $priceupload->parse_price();
    if (!$result_parse) {
      $this->setErrorStatus(false, $priceupload->error_message);
      return $this->result;
    }
    $result_add = $priceupload->add_db();
    $priceupload->logWrite($item->getDir());
    // $this->setErrorStatus(false, $priceupload->success_message);
    // return $this->result;

    DB::getInstance()->commit();

    $this->result['c'] = strtolower($this->entityName);
    $this->result['id'] = $item->getId();
    $this->result['cid'] = $item->getFactoryId();
    ActionController::addMessage('add', "<br>".$priceupload->success_message."<br>Errors:<br>".$priceupload->error_message);
    // ActionController::addError($priceupload->error_message);
    return $this->result;

  }
  function DeleteAction() {
    $item = new $this->entityName($this->params);
    return $this->delete($item);
  }
  function UpdateAction() {

    $item = new $this->entityName($this->params['id']);
    $this->checkValidate($item);
    if ($this->result['error']) {
      return $this->result;
    }
    DB::getInstance()->begin();
    $this->params['lastchange'] = date("Y-m-d H:i:s");
    $item->update($this->params);

    DB::getInstance()->commit();

    $this->result['alert'] = 'success';
    $this->result['status'] = 'Изменения сохранены';
    $_SESSION['status'] = 'Изменения сохранены';
    return $this->result;

  }


}

?>
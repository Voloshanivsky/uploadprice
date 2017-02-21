<?php
/* (c) 2007-2009 Denis Shushkin, me@den.kiev.ua */

require 'class/DB.php';
require 'class/Collection.php';
require 'class/Table.php';
require 'class/libmail.php';
require 'securimage/securimage.php';

function __autoload($class_name) {
  if ( preg_match('/^(.*?)(Ajax|Remote)?(Front|Action)?Controller$/', $class_name, $matches) ) {
    if ( isset($matches[3]) ) {
      require_once "class/Controller.php";
    } elseif ( $matches[1] ) {
      $filename = ucfirst(strtolower($matches[1]));
      $filename = "controller/{$filename}Controller.php";
      if ( file_exists($filename) ) {
        require_once $filename;
      } else {
        throw new NotFoundException("'$class_name' not found");
      }
    }
  } elseif ( substr($class_name, -4) == 'View' ) {
    require_once "class/View.php";
  } elseif ( in_array($class_name, array('Email', 'Model', 'Entity', 'Collection', 'Table', 'Field', 'Settings', 'JsHttpRequest', 'Foto', 'LiqPay')) ) {
    require_once "class/$class_name.php";
  } elseif ( preg_match('/^Priceupload([A-Z][a-z]+)$/', $class_name, $matches) ) {
    require_once "model/priceupload/{$matches[1]}.php";
  } elseif ( preg_match('/^Dveri(Collection|DB)?$/', $class_name, $matches) ) {
    require_once "class/DBDveri.php";
  } elseif ( preg_match('/^Divani(Collection|DB)?$/', $class_name, $matches) ) {
    require_once "class/DBDivani.php";
  } elseif ( preg_match('/^([A-Z][a-z]+)([A-Za-z])*?(Collection|Table)?$/', $class_name, $matches) ) {
    require_once "model/{$matches[1]}.php";
  } elseif ( preg_match('/^([a-z]+)Has([a-z]+)?(Collection|Table)?$/', $class_name, $matches) ) {
    require_once "model/{$matches[1]}Has{$matches[2]}.php";
  } else {
    smartyAutoload($class_name);
  }
}

?>

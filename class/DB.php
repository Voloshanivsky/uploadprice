<?php

class DB extends mysqli {
  var $debug = false;
  protected $validators, $messages, $settings;
  private static $instance = NULL;

  /**
  * @desc Get instance of class
  * @return DB
  */
  final static function getInstance() {
    if (self::$instance == NULL) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  final private function __clone() {}

  protected function __construct() {
//    $sett = new Settings();
//    $conf = $sett->get('DB');
    $conf = Settings::get('DB');
    parent::__construct(
      $conf['host'],
      $conf['user'],
      $conf['pass'],
      $conf['database']
    );
    if ( mysqli_connect_errno() ) {
      throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    parent::query("SET NAMES 'utf8'");
//    $tz = $this->settings->Get('time_zone');
//    $this->query("SET time_zone = '$tz'");
  }

  public function begin() {
    if ( ++$this->transaction == 1 ) {
      $this->query('START TRANSACTION');
    }
  }

  public function commit() {
    if ( --$this->transaction == 0 ) {
      if ( $this->debug ) {
        echo "<small>COMMIT</small><br />";
      }
      parent::commit();
    }
  }

  public function rollback() {
      if ( $this->debug ) {
        echo "<small>ROLLBACK</small><br />";
      }
    parent::rollback();
    $this->transaction = 0;
  }

  /**
  * Perform a database query
  * @return mysqli_result
  */
  public function query($query) {
    if ( $this->debug ) {
      echo "<small>$query</small><br />";
    }
    if ( !$res = parent::query($query) ) {
      $error = $this->error;
      /*if ( $this->transaction ) {
        $this->Rollback();
      }*/
//      if ( DB_DEBUG ) {
        throw new Exception("Database query failed: $error; Query: $query");
/*      } else {
        throw new Exception("Database query failed");
      }*/
    }
    return $res;
  }

  function prepareData(array &$data) {
    foreach ( $data as &$value ) {
      if ( is_null($value) ) {
        $value = "NULL";
      } else {
        if (is_string($value)) {
          $value = trim(stripslashes($value));
          $value = $this->real_escape_string( $value );
        }
        $value = "'" . $value . "'";
      }
    }
  }

  function prepareDataForUA(array &$data) {
    foreach ( $data as $key => &$value ) {
      $suffix = substr($key, -2, 2);
      if ($suffix == 'ua') {
        $data[ substr($key, 0, -2) ] = $data[ $key ];
        //если передали данные все в куче для двух языков, то нужно для рус удалить
        // и заменить на укр
//        unset($data[ substr($key, 0, -2) ]);
//        $key = substr($key, 0, -2);
      }
    }
  }

  public function update($tables, $where, array $data, array $extra = NULL) {
    $this->prepareData($data, $validators);
    if ( is_array($extra) ) {
      $data = array_merge($data, $extra);
    }
    $update = '';
    foreach ( $data as $field => $value ) {
      $update .= ", $field=$value";
    }
    $update = substr($update, 2);
    $this->query("UPDATE $tables SET $update WHERE $where");
    return $this->affected_rows;
  }

  /**
  * @desc Fetch single value
  * @return mixed
  */
  public function fetchSingle($query) {
    $res = $this->query($query);
    if ( $res->num_rows == 0 ) {
      return false;
      throw new BlankResultException('Database returned blank result');
    }
    list($val) = $res->fetch_row();
    $res->free();
    if ( $this->debug ) {
      echo "<small>FetchSingle: $val</small><br />";
    }
    return $val;
  }

  /**
  * @desc Fetch single row as associative array
  * @return array
  */
  function fetchRowAssoc($query) {
    $res = $this->query($query);
    if ( !$row = $res->fetch_assoc() ) {
      throw new BlankResultException();
    }
    $res->free();
    return $row;
  }

  /**
  * @desc Fetch single-column result as iterator
  */
  public function fetchValueList($query) {
    return new RowIterator($query, new RowFetchValue());
  }

  /**
  * @desc Fetch 2-column result as associative iterator
  */
  public function fetchKeyValueList($query) {
    return new RowIterator($query, new RowFetchKeyValue());
  }

  /**
  * @desc Fetch result as iterator of associative row-arrays
  */
  public function fetchAssocList($query, IRowProcessStrategy $processStrategy = NULL) {
    return new RowIterator($query, new RowFetchAssoc(), $processStrategy);
  }

  /**
  * @desc Fetch result as iterator of enumerated row-arrays
  */
  public function fetchEnumList($query, IRowProcessStrategy $processStrategy = NULL) {
    return new RowIterator($query, new RowFetchEnum(), $processStrategy);
  }
}

class BlankResultException extends Exception {}



interface IRowFetchStrategy {
  function fetch(mysqli_result $result, &$key, &$item);
}

interface IRowProcessStrategy {
  function process(&$row);
}



class RowFetchAssoc implements IRowFetchStrategy {
  function fetch(mysqli_result $result, &$key, &$item) {
    ++$key;
    $item = $result->fetch_assoc();
  }
}

class RowFetchEnum implements IRowFetchStrategy {
  function fetch(mysqli_result $result, &$key, &$item) {
    ++$key;
    $item = $result->fetch_row();
  }
}

class RowFetchKeyValue implements IRowFetchStrategy {
  function fetch(mysqli_result $result, &$key, &$item) {
    list($key, $item) = $result->fetch_row();
  }
}

class RowFetchValue implements IRowFetchStrategy {
  function fetch(mysqli_result $result, &$key, &$item) {
    ++$key;
    list($item) = $result->fetch_row();
  }
}

class RowFetchEntity extends Entity implements IRowFetchStrategy {
  private $col;

  function __construct(Collection $col) {
    $this->col = $col;
  }

  function fetch(mysqli_result $result, &$key, &$item) {
    ++$key;
    $class = $this->col->name;
    if ( $data = $result->fetch_assoc() ) {
      $item = new $class($data[$this->col->table->getPk()]);
      /**
      * @var Entity
      */
      $item->data = $data;
      $item->loaded = true;
    } else {
      $item = NULL;
    }
  }
}

class RowIterator implements Iterator {
    /**
    * @var mysqli_result
    */
  protected $result;
  protected $key, $item, $fetchStrategy, $processStrategy;

  function __construct($sql, IRowFetchStrategy $fetchStrategy, IRowProcessStrategy $processStrategy = NULL) {
    $this->result = DB::getInstance()->query($sql);
    $this->fetchStrategy = $fetchStrategy;
    $this->processStrategy = $processStrategy;
    $this->item = NULL;
    $this->key = -1;
    $this->next();
//    echo 'construct';
  }

  function __destruct() {
    $this->result->free();
  }

  public function setFetchStrategy(IRowFetchStrategy $fetchStrategy) {
    $this->fetchStrategy = $fetchStrategy;
  }

  public function count() {
    return $this->result->num_rows;
  }

  public function rewind() {
    $this->result->data_seek(0);
    $this->key = -1;
    $this->next();
  }

  public function current() {
    return $this->item;
  }

  public function key() {
    return $this->key;
  }

  public function next() {
    $this->fetchStrategy->fetch($this->result, $this->key, $this->item);
    if ( $this->valid() && $this->processStrategy ) {
//      echo 1;
      $this->processStrategy->process($this->item);
    }
  }

  public function valid() {
    return !is_null($this->item);
  }

  public function toArray() {
//    echo "<br>toArray<br>";
    $array = array();
    foreach ( $this as $key => $value ) {
      $array[$key] = $value;
//      print "$key => $value<br>";
    }
    return $array;
  }
}

?>

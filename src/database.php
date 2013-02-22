<?PHP

class ShadowDB {

    public $dbConfig;
    private $db;
    private $tables;

    function __construct($dbConfig) {
        $this->dbConfig = $dbConfig;
        $this->db_connect();
        return $this;
    }

    private function db_query($qry, $params = array()) {
        try {
            $pdo = $this->db->prepare($qry);
            $i = 1;
            foreach ($params as $param) {
                $pdo->bindParam($i, $param);
                $i++;
            }
            $pdo->execute($params);
            return $pdo->fetchAll(PDO::FETCH_OBJ);
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }

    private function db_execute($qry, $params = array()) {
        try {
            $pdo = $this->db->prepare($qry);
            $i = 1;
            foreach ($params as $param) {
                $pdo->bindParam($i, $param);
                $i++;
            }
            $pdo->execute($params);
            return $pdo->rowCount();
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }

    private function db_connect() {

        try {
            $host = $this->dbConfig['connection']['host'];
            $db = $this->dbConfig['connection']['db'];
            $user = $this->dbConfig['connection']['user'];
            $pass = $this->dbConfig['connection']['pass'];

            $this->db = new PDO("mysql:host=" . $host . ";dbname=" . $db, $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo $e->getMessage();
        }

    }

    public function query($q, $p = array()) {
        return $this->db_query($q, $p);
    }

    public function execute($q, $p) {
        return $this->db_execute($q, $p);
    }

    public function lastID() {
        return $this->db->lastInsertId();
    }

    public function get($what, $from, $where) {

        $tempWhere = array();
        foreach ($where as $k => $v) {
            $tempWhere[] = $k . '=:' . $k;
        }

        $whereString = implode(' AND ', $tempWhere);

        $sql = 'select ' . $what . ' FROM `' . $from . '` WHERE ' . $whereString;

        return $this->db_query($sql, $where);
    }

    public function create($table, $params) {

        $temp = array('keys' => array(), 'vals' => array());
        foreach ($params as $k => $v) {
            $temp['keys'][] = $k;
            $temp['vals'][] = ':' . $k;
        }

        $keys = implode(', ', $temp['keys']);
        $vals = implode(', ', $temp['vals']);

        $sql = 'INSERT INTO `' . $table . '` (' . $keys . ') VALUES (' . $vals . ')';
        
        return $this->db_execute($sql, $params);
    }

    public function update($what, $table, $where, $additionalParams=false) {
        $temp = array();

        foreach ($where as $k => $v) {
            $temp[] = $k . ' = :' . $k;
        }

        if($additionalParams){
            $where = array_merge($where, $additionalParams);
        }

        $whereString = implode(' AND ', $temp);

        $sql = 'UPDATE `' . $table . '` SET ' . $what . ' WHERE ' . $whereString;

        return $this->db_execute($sql, $where);
    }

    public function remove($table, $where) {
        $temp = array();

        foreach ($where as $k => $v) {
            $temp[] = $k . ' = :' . $k;
        }

        $whereString = implode(' AND ', $temp);

        $sql = 'DELETE FROM `' . $table . '` WHERE ' . $whereString;

        $this->db_execute($sql, $where);
    }

}

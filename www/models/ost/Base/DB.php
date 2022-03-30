<?php

    namespace OsT\Base;

    use PDO;
    use PDOException;
    use PDOStatement;

    /**
     * Работа с базой данных
     * Стандартизация ST-3
     * Class DB
     * @package OsT\Base
     * @version 2022.03.11
     *
     * __construct          DB constructor
     * prepare              Подготовить запрос
     * connect              Получить объект подкления к базе данных (PDO)
     * getConnection        Получить объект подключения к базе данных (PDO)
     * query                Выполнить запрос
     * _query               Выполнить запрос
     * _update              Обновить данные в БД
     * update               Обновить данные в БД
     * updateArrById        Обновить массив данных в таблице
     * _insert              Добавить данные в БД
     * insert               Добавить данные в БД
     * _delete              Удалить данные из БД
     * delete               Удалить данные из БД
     * _deleteArray         Удалить данные из таблицы $table, атрибут $key которых совпадает со значением в массиве $items
     * deleteArray          Удалить данные из таблицы $table, атрибут $key которых совпадает со значением в массиве $items
     *
     */
    class DB
    {
        public $connection;
        public $workability = false;

        /**
         * DB constructor.
         */
        public function __construct ()
        {
            $this->connection = self::connect();
            if ($this->connection !== false)
                $this->workability = true;
        }

        /**
         * Подготовить запрос
         * @param $query - запрос
         * @return false|PDOStatement
         */
        public function prepare ($query)
        {
            return $this->connection->prepare($query);
        }

        /**
         * Получить объект подкления к базе данных (PDO)
         * @return bool|PDO
         */
        public static function connect ()
        {
            try {
                $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
                $opt = NULL;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
            } catch (PDOException $e) {
                $pdo = false;
            }
            return $pdo;
        }

        /**
         * Получить объект подключения к базе данных (PDO)
         * @return bool|PDO
         */
        public function getConnection ()
        {
            return $this->connection;
        }

        /**
         * Выполнить запрос
         * @param $prepare - строка запроса
         * @param array $execute - массив параметров
         * @return bool|PDOStatement - результат выполнения запроса
         */
        public function query ($prepare, $execute = [])
        {
            return self::_query($prepare, $execute, $this->connection);
        }

        /**
         * Выполнить запрос
         * @param $prepare - строка запроса
         * @param array $execute - массив параметров
         * @param array $db - объект подключения к базе данных (PDO)
         * @return bool|PDOStatement - результат выполнения запроса
         */
        public static function _query ($prepare, $execute = [], &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            $q = $db->prepare($prepare);
            if ($q->execute($execute))
                return $q;
            else return false;
        }

        /**
         * Обновить данные в БД
         * @param $table - наименование таблицы
         * @param $data - массив данных типа [название_атрибута] = значение_атрибута
         * @param null $where - массив условий типа [№пп][0] - условие (id = ); [№пп][1] - переменная (1)
         * @return bool
         */
        public function _update ($table, $data, $where = null)
        {
            return self::update($table, $data, $where, $this->connection);
        }

        /**
         * Обновить данные в БД
         * @param $table - наименование таблицы
         * @param $data - массив данных типа [название_атрибута] = значение_атрибута
         * @param null $where - массив условий типа [№пп][0] - условие (id = ); [№пп][1] - переменная (1)
         * @param null $db - объект подключения к базе данных (PDO)
         * @return bool
         */
        public static function update ($table, $data, $where = null, &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            foreach ($data as $key => $val) {
                if (!isset($SQLset))
                    $SQLset = $key.' = ?';
                else $SQLset .= ', '.$key.' = ?';
                $matches[] = $val;
            }

            if ($where !== null) {
                foreach ($where as $key => $val) {
                    if (!isset($SQLwhere))
                        $SQLwhere = ' WHERE ' . $val[0] . ' ?';
                    else $SQLwhere .= ' AND ' . $val[0] . ' ?';
                    $matches[] = $val[1];
                }
            } else $SQLwhere = '';

            $SQL = 'UPDATE '.$table.' SET '.$SQLset.$SQLwhere;
            $q = $db->prepare($SQL);
            if ($q->execute($matches))
                return true;
            else return false;
        }

        /**
         * Обновить массив данных в таблице
         * @param $table - наименование таблицы
         * @param $field - наименование столбца, который необходимо обновить
         * @param $data - массив данных типа [$data_key][значение]
         * @param string $data_key - ключ, указывающий на то, какие записи обновлять (id)
         * @param null $db - объект подключения к базе данных (PDO)
         * @return bool
         */
        public static function updateArrById ($table, $field, $data, $data_key = 'id', &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            $arr = [];
            $arr2 = [];
            foreach ($data as $key=>$val) {
                $arr[$key] = 'val' . $key;
                $arr2['val' . $key] = $val;
            }

            $sql = 'UPDATE `' . $table . '` SET `' . $field . '` = CASE `' . $data_key . '` ';
            foreach ($arr as $key=>$val)
                $sql .= 'WHEN ' . $key . ' THEN :' . $val . ' ';
            $sql .= ' ELSE `' . $field . '` END';

            return self::_query($sql, $arr2, $db);
        }

        /**
         * Добавить данные в БД
         * @param $table - наименование таблицы
         * @param $data - массив данных типа [название_атрибута] = значение_атрибута
         * @return bool
         */
        public function _insert ($table, $data)
        {
            return self::insert($table, $data, $this->connection);
        }

        /**
         * Добавить данные в БД
         * @param $table - наименование таблицы
         * @param $data - массив данных типа [название_атрибута] = значение_атрибута
         * @param null $db - объект подключения к базе данных (PDO)
         * @return bool
         */
        public static function insert ($table, $data, &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            foreach ($data as $key => $val) {
                if (!isset($SQLkeys) && !isset($SQLvals)) {
                    $SQLkeys = $key;
                    $SQLvals = ' ?';
                } else {
                    $SQLkeys .= ', ' . $key;
                    $SQLvals .= ', ?';
                }
                $matches[] = $val;
            }

            $SQL = 'INSERT INTO ' . $table . ' (' . $SQLkeys . ') VALUES (' . $SQLvals . ')';

            return self::_query($SQL, $matches, $db);
        }

        /**
         * Удалить данные из БД
         * @param $table - наименование таблицы
         * @param null $where - массив условий типа [№пп][0] - условие (id = ); [№пп][1] - переменная (1)
         * @return bool
         */
        public function _delete ($table, $where = null)
        {
            return self::delete($table, $where, $this->connection);
        }

        /**
         * Удалить данные из БД
         * @param $table - наименование таблицы
         * @param null $where - массив условий типа [№пп][0] - условие (id = ); [№пп][1] - переменная (1)
         * @param null $db - объект подключения к базе данных (PDO)
         * @return bool
         */
        public static function delete ($table, $where = null, &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            foreach ($where as $key => $val) {
                if (!isset($SQLwhere))
                    $SQLwhere = ' WHERE '.$val[0].' ?';
                else $SQLwhere .= ' AND '.$val[0].' ?';
                $matches[] = $val[1];
            }

            $SQL = 'DELETE FROM '.$table.$SQLwhere;

            return self::_query($SQL, $matches, $db);
        }

        /**
         * Удалить данные из таблицы $table, атрибут $key которых совпадает со значением в массиве $items
         * @param $table - наименование таблицы
         * @param $items - массив возможных (искомых) значений $key
         * @param string $key - атрибут (название) столбца в таблице $table
         */
        public function _deleteArray ($table, $items, $key = 'id')
        {
            self::deleteArray($table, $items, $key, $this->connection);
        }

        /**
         * Удалить данные из таблицы $table, атрибут $key которых совпадает со значением в массиве $items
         * @param $table - наименование таблицы
         * @param $items - массив возможных (искомых) значений $key
         * @param string $key - атрибут (название) столбца в таблице $table
         * @param null $db - объект подключения к базе данных (PDO)
         */
        public static function deleteArray ($table, $items, $key = 'id', &$db = null)
        {
            if ($db === null)
                $db = self::connect();

            $db->query('
            DELETE FROM   ' . $table . '
            WHERE ' . $key . ' IN (' . System::convertArrToSqlStr($items) . ')');
        }

    }
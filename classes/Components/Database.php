<?php
/**
 * Created by PhpStorm.
 * User: Rhilip
 * Date: 2/7/2020
 * Time: 2020
 */

namespace NexusPHP\Components;


class Database
{
    /**
     * @var array
     */
    private static $query_history = [];

    /**
     * @var \mysqli
     */
    protected static $_mysqli;

    private static function getMysqli()
    {
        global $mysql_host, $mysql_user, $mysql_pass, $mysql_db;

        if (self::$_mysqli === null) {
            self::$_mysqli = mysqli_connect($mysql_host, $mysql_user, $mysql_pass, $mysql_db);

            if (!self::$_mysqli) {
                die('dbconn: mysql_connect: ' . self::$_mysqli->connect_errno);
            }
            self::$_mysqli->autocommit(true);
            self::$_mysqli->set_charset('utf8');
            self::$_mysqli->query("SET collation_connection = 'utf8_general_ci', sql_mode=''");
        }

        return self::$_mysqli;
    }

    public static function getQueryHistory(): array
    {
        return self::$query_history;
    }

    private static function addQueryHistory(string $sql, $values = [])
    {
        if (!$values) {
            self::$query_history[] = $sql;
        } else {
            $values = self::escape($values);
            self::$query_history[] = vsprintf(str_replace('?', '%s', $sql), $values);
        }
    }

    public static function whereIn($array)
    {
        return implode(',', array_fill(0, count($array), '?')); //create question marks
    }

    public static function escape($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::escape($v);
            }
            return $value;
        }

        return "'" . self::real_escape_string($value) . "'";
    }

    public static function insert_id()
    {
        return self::getMysqli()->insert_id;
    }

    public static function error()
    {
        return self::getMysqli()->error;
    }

    public static function errno()
    {
        return self::getMysqli()->errno;
    }

    public static function affected_rows()
    {
        return self::getMysqli()->affected_rows;
    }

    public static function real_escape_string($value)
    {
        return self::getMysqli()->real_escape_string($value);
    }

    /**
     * @param string $sql
     * @param array $values
     * @param string $types
     * @return false|\mysqli_stmt
     */
    public static function exec(string $sql, $values = [], $types = '')
    {
        self::addQueryHistory($sql, $values);
        $stmt = self::getMysqli()->prepare($sql);

        if (!is_array($values)) {
            $values = [$values];
        }

        if (!$types || strlen($types) !== count($values)) {
            $types = str_repeat('s', count($values));
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt;
    }

    /**
     * @param $sql
     * @return bool|\mysqli_result
     */
    public static function raw_query($sql)
    {
        self::addQueryHistory($sql);
        return self::getMysqli()->query($sql);
    }

    /**
     * 如果使用STMT形式，请只在SELECT情况下使用，其他情况（DELETE，UPDATE，INSERT）请用 exec()
     *
     * @param string $sql
     * @param array $values
     * @param string $types
     * @return bool|false|\mysqli_result
     */
    public static function query(string $sql, $values = [], $types = '')
    {
        if (!$values) {
            return self::raw_query($sql);
        } else {
            $stmt = self::exec($sql, $values, $types);
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
    }

    public static function result($result, $number, $field = 0)
    {
        mysqli_data_seek($result, $number);
        $row = mysqli_fetch_array($result);
        return $row[$field];
    }

    public static function one(string $sql, $values = [], $types = '')
    {
        $r = self::query($sql, $values, $types);
        return mysqli_fetch_array($r);
    }

    public static function scalar(string $sql, $default = false, $values = [], $types = '')
    {
        $a = self::one($sql, $values, $types);
        return $a ? $a[0] : $default;
    }

    public static function single($table, $field, $suffix = "", $default = false, $values = [], $types = '')
    {
        return self::scalar("SELECT $field FROM $table $suffix LIMIT 1", $default, $values, $types);
    }

    public static function count($table, $suffix = "", $values = [], $types = '')
    {
        return self::scalar("SELECT COUNT(*) FROM $table $suffix", 0, $values, $types);
    }

    public static function sum($table, $field, $suffix = "", $values = [], $types = '')
    {
        return self::scalar("SELECT SUM($field) FROM $table $suffix", 0, $values, $types);
    }
}

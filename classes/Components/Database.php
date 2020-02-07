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

        return is_string($value) ? "'" . self::real_escape_string($value) . "'" : $value;
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

    public static function query(string $sql, $values = [], $types = '')
    {
        if (!$values) {
            self::addQueryHistory($sql);
            return self::getMysqli()->query($sql);
        } else {
            $stmt = self::getMysqli()->prepare($sql);

            if (!is_array($values)) {
                $values = [$values];
            }

            if (!$types) {
                $types = str_repeat('s', count($values));
            }

            $stmt->bind_param($types, ...$values);
            $stmt->execute();

            self::addQueryHistory($sql, $values);
            return $stmt->get_result();
        }
    }

    public static function result($result, $number, $field = 0)
    {
        mysqli_data_seek($result, $number);
        $row = mysqli_fetch_array($result);
        return $row[$field];
    }

    public static function scalar(string $sql, $default = false, $values = [], $types = '')
    {
        $r = self::query($sql, $values, $types) or sqlerr(__FILE__, __LINE__);
        $a = mysqli_fetch_row($r) or die(self::error());
        return $a ? $a[0] : $default;
    }

    public static function single($table, $field, $suffix = "", $default = false, $values = [], $types = '')
    {
        return self::scalar("SELECT $field FROM $table $suffix LIMIT 1", $default, $values, $types);
    }

    public static function count($table, $suffix = "", $values = [], $types = '')
    {
        return self::scalar("SELECT COUNT(*) FROM $table $suffix", $values, $types);
    }

    public static function sum($table, $field, $suffix = "", $values = [], $types = '')
    {
        return self::scalar("SELECT SUM($field) FROM $table $suffix", $values, $types);
    }
}

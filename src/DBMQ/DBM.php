<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

use Mileena\Web\WebApp;
use mysqli;

/**
 * Abstract class providing database connection and basic query execution methods.
 */
abstract class DBM
{
    private static ?mysqli $connection = null;
    private static string $lastQuery = '';
    private static bool $showQuery = false;

    /**
     * Reconnects to the database by closing the current connection.
     */
    public static function reconnect(): void
    {
        self::closeConnection();
    }

    /**
     * Closes the database connection.
     */
    public static function closeConnection(): void
    {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }

    /**
     * Executes a prepared statement with bound parameters.
     * Note: This implementation is basic and assumes all parameters are strings.
     *
     * @param string $q The SQL query with '?' placeholders.
     * @param list<scalar> $valueList The list of values to bind.
     */
    public static function queryBind(string $q, array $valueList): void
    {
        $con = self::getConnection();
        $stmt = $con->prepare($q);

        if (!empty($valueList)) {
            $types = str_repeat('s', count($valueList));
            $stmt->bind_param($types, ...$valueList);
        }

        $stmt->execute();
        $stmt->close();

        self::$lastQuery = $q;
    }

    /**
     * Enables echoing the next query and exiting. For debugging only.
     */
    public static function showQuery(): void
    {
        self::$showQuery = true;
    }

    /**
     * Gets the last executed SQL query.
     */
    public static function getLastQuery(): string
    {
        return self::$lastQuery;
    }

    /**
     * Gets the table name for the database operation.
     */
    abstract protected static function getTableName(): string;

    /**
     * Must be implemented by child classes to specify the DTO class they operate with.
     *
     * @return class-string<T>
     */
    abstract protected static function getDtoClass(): string;

    /**
     * Fetches a single row from a query.
     * **WARNING:** Unsafe if query contains user input. Use prepared statements instead.
     *
     * @param string|\Mileena\DBMQ\QB $q The raw SQL query string or QB object.
     * @return T|array|null The record data or null if not found.
     */
    protected static function makeOne(QB|string $q, array $params = [], ?string $dtoClass = null): mixed
    {
        $queryStr = $q instanceof QB ? $q->getQuery() : $q;
        $result = self::query($queryStr, $params);

        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_assoc();
            $result->free();

            if ($dtoClass) {
                return $dtoClass::fromArray($row);
            }

            if (empty($row)) {
                return [];
            }

            return $row;
        }

        if ($dtoClass) {
            return null;
        }

        return [];
    }

    /**
     * Executes a raw SQL query.
     * **WARNING:** Inherently unsafe if the query contains any user input.
     *
     * @param string $q The raw SQL query string.
     * @return \mysqli_result|bool
     */
    public static function query(string $q, array|string $params = []): bool|\mysqli_result
    {
        $con = self::getConnection();

        $finalParams = [];

        // Если есть параметры, проверяем их на наличие массивов
        if (!empty($params)) {
            foreach ($params as $val) {
                if (is_array($val)) {
                    // Если массив пустой, SQL упадет на IN (), поэтому ставим NULL или обрабатываем
                    if (empty($val)) {
                        $placeholders = 'NULL';
                    } else {
                        $placeholders = implode(',', array_fill(0, count($val), '?'));
                        $finalParams = array_merge($finalParams, array_values($val));
                    }

                    // Заменяем ПЕРВЫЙ встречный '?' на сгенерированные плейсхолдеры
                    $pos = strpos($q, '?');

                    if ($pos !== false) {
                        $q = substr_replace($q, $placeholders, $pos, 1);
                    }
                } else {
                    $finalParams[] = $val;
                }
            }
        } else {
            $finalParams = $params;
        }

        self::$lastQuery = self::getDebugSql($q, $finalParams);
        ;

        if (self::$showQuery) {
            echo self::$lastQuery;
            exit;
        }

        $stmt = $con->prepare($q);

        if (!$stmt) {
            throw new \Exception("Prepare failed: " . $con->error);
        }

        // 2. Явная привязка параметров с контролем типов
        if (!empty($finalParams)) {
            $types = "";

            foreach ($finalParams as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_double($param)) {
                    $types .= "d";
                } else {
                    $types .= "s"; // Все остальное, включая '3.2', улетит как string
                }
            }
            $stmt->bind_param($types, ...$finalParams);
        }

        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Establishes and returns a database connection.
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            // Enable exception mode for mysqli for better error handling
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $app = WebApp::getInstance();
            [$dbHost, $dbUser, $dbPass, $dbName, $dbPort] = $app->config->getDBCredential();

            try {
                self::$connection = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
                // Use set_charset for modern compatibility
                self::$connection->set_charset('utf8mb4');
            } catch (\mysqli_sql_exception $e) {
                // It's good practice to log the error and re-throw a more generic exception
                // error_log('Database connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Could not connect to the database.', 0, $e);
            }
        }

        return self::$connection;
    }

    private static function getDebugSql(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        $con = self::getConnection();

        foreach ($params as $value) {
            if (is_null($value)) {
                $formatted = "NULL";
            } elseif (is_numeric($value)) {
                $formatted = $value;
            } else {
                $formatted = "'" . $con->real_escape_string((string) $value) . "'";
            }

            $pos = strpos($sql, '?');

            if ($pos !== false) {
                $sql = substr_replace($sql, (string) $formatted, $pos, 1);
            }
        }

        return $sql . ";";
    }

    /**
     * Fetches a list of rows from a query.
     * **WARNING:** Unsafe if query contains user input. Use prepared statements instead.
     *
     * @param string|\Mileena\DBMQ\QB $q The raw SQL query string or QB object.
     * @param string|null $key The column to use as the array key.
     * @return array<int|string, T|array<string, mixed>>
     */
    protected static function makeList(
        QB|string $q,
        ?string   $key = null,
        array     $params = [],
        ?string   $dtoClass = null,
    ): array {
        $list = [];
        $queryStr = $q instanceof QB ? $q->getQuery() : $q;
        $result = self::query($queryStr, $params);

        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $keyVal = null;

                if ($key) {
                    $keyVal = $row[$key];
                }

                if ($dtoClass) {
                    $row = $dtoClass::fromArray($row);
                }

                if ($key !== null) {
                    $list[$keyVal] = $row;
                } else {
                    $list[] = $row;
                }
            }
            $result->free();
        }

        return $list;
    }

    /**
     * Fetches a nested list of rows from a query.
     * **WARNING:** Unsafe if query contains user input. Use prepared statements instead.
     *
     * @param string|QB $q The raw SQL query string or QB object.
     * @param string $key The first level key.
     * @param string|null $key2 The second level key.
     * @return array<string, mixed>
     */
    protected static function makeDoubleList(QB|string $q, string $key, ?string $key2 = null, array $params = []): array
    {
        $list = [];
        $queryStr = $q instanceof QB ? $q->getQuery() : $q;
        $result = self::query($queryStr, $params);

        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                if ($key2) {
                    $list[$row[$key]][$row[$key2]] = $row;
                } else {
                    $list[$row[$key]][] = $row;
                }
            }
            $result->free();
        }

        return $list;
    }

    /**
     * Creates a key-value map from a query result.
     * **WARNING:** Unsafe if query contains user input. Use prepared statements instead.
     *
     * @param string|\Mileena\DBMQ\QB $q The raw SQL query string or QB object.
     * @param string $key The column to use as the map key.
     * @param string $value The column to use as the map value.
     * @return array<string, mixed>
     */
    protected static function makeMap(QB|string $q, string $key, string $value, array $params = []): array
    {
        $map = [];
        $queryStr = $q instanceof QB ? $q->getQuery() : $q;
        $result = self::query($queryStr, $params);

        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $map[$row[$key]] = $row[$value];
            }
            $result->free();
        }

        return $map;
    }

    /**
     * Creates a nested key-value map from a query result.
     * **WARNING:** Unsafe if query contains user input. Use prepared statements instead.
     *
     * @param string|QB $q The raw SQL query string or QB object.
     * @param string $key The first level key.
     * @param string $key2 The second level key.
     * @param string $value The value column.
     * @return array<string, mixed>
     */
    protected static function makeDoubleMap(
        QB|string $q,
        string    $key,
        string    $key2,
        string    $value,
        array     $params = [],
    ): array {
        $map = [];
        $queryStr = $q instanceof QB ? $q->getQuery() : $q;
        $result = self::query($queryStr, $params);

        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $map[$row[$key]][$row[$key2]] = $row[$value];
            }
            $result->free();
        }

        return $map;
    }
}

<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

/**
 * Abstract class for simple CRUD database operations.
 * It uses a Data Transfer Object (DTO) for type-safe data handling.
 *
 * @template T of DTO
 */
abstract class Crud extends DBM
{
    /**
     * Prepares the SET clause for an INSERT or UPDATE statement.
     *
     * @param array<string, mixed> $fieldsAndValues Key-value pairs of data.
     * @return array{0: string, 1: string} Returns a tuple: [bind types, SQL SET clause].
     */
    protected static function makeBindUpdate(array $fieldsAndValues): array
    {
        $setClause = '`' . implode('` = ?, `', array_keys($fieldsAndValues)) . '` = ?';
        $bindTypes = str_repeat('s', count($fieldsAndValues));

        return [$bindTypes, $setClause];
    }

    /**
     * Saves a record. Inserts a new record if pkId is null, otherwise updates the existing record.
     *
     * @param array<string, mixed>|DTO $data The record data as an associative array or a DTO object.
     * @param int|string|null $pkId The primary key value. If null, an INSERT is performed.
     * @param string|null $table The table name. Defaults to the name provided by getTableName().
     * @param string $key The primary key column name.
     * @return int|string The primary key of the saved record.
     */
    public static function save(
        array|DTO $data,
        int|string|null $pkId = null,
        ?string $table = null,
        string $key = 'id',
    ): int|string {
        $table ??= static::getTableName();
        $con = self::getConnection();

        if ($data instanceof DTO) {
            $data = $data->toArray();
        }

        // handling special data types
        foreach ($data as $k => $v) {
            if ($v instanceof \DateTimeInterface) {
                $data[$k] = $v->format('Y-m-d H:i:s');
            } elseif (is_bool($v)) {
                $data[$k] = (int) $v;
            }
        }

        [$bindTypes, $setClause] = self::makeBindUpdate($data);
        $values = array_values($data);

        if ($pkId === null || $pkId === 0 || $pkId === '') {
            $stmt = $con->prepare("INSERT INTO `{$table}` SET {$setClause}");
        } else {
            $stmt = $con->prepare("UPDATE `{$table}` SET {$setClause} WHERE `{$key}` = ?");
            $bindTypes .= 's'; // Assuming PK is a string or int, 's' works for both.
            $values[] = $pkId;
        }

        if (!$stmt) {
            // In a real application, this should throw an exception or be logged.
            return 0;
        }

        $stmt->bind_param($bindTypes, ...$values);
        $stmt->execute();

        if ($pkId === null || $pkId === 0 || $pkId === '') {
            return $con->insert_id;
        }

        return $pkId;
    }

    /**
     * Retrieves a single record by its primary key and maps it to a DTO.
     *
     * @param int|string|null $pkId The primary key value.
     * @return T|array|null The DTO object or null if not found.
     */
    public static function getById(int|string|null $pkId): array|object|null
    {
        if ($pkId === null) {
            if (static::getDtoClass()) {
                return null;
            }

            return [];
        }

        return self::makeOne(
            "SELECT * FROM " . static::getTableName() . " WHERE id = ?",
            [$pkId],
            static::getDtoClass(),
        );
    }

    /**
     * Retrieves multiple records by their primary keys and maps them to DTOs.
     *
     * @param array<int|string> $pkIds An array of primary key values.
     * @return array<int|string, T> An associative array of DTOs, keyed by their primary key.
     */
    public static function getByIds(array $pkIds): array
    {
        if (empty($pkIds)) {
            return [];
        }

        $result = self::query(
            "SELECT * FROM " . static::getTableName() . " WHERE id IN (?)",
            [$pkIds],
        );

        $dtoClass = static::getDtoClass();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[$row['id']] = $dtoClass::fromArray($row);
        }

        return $data;
    }

    /**
     * Retrieves specific columns for a record by its primary key.
     *
     * @param int|string $pkId The primary key value.
     * @param string[] $columns The list of columns to retrieve.
     * @return array<string, mixed>|null The record data as an associative array, or null if not found.
     */
    public static function getColumnsById(int|string $pkId, array $columns): ?array
    {
        $allColumns = static::getColumnNameMap();
        $safeColumns = array_intersect($columns, array_keys($allColumns));

        if (empty($safeColumns)) {
            return null;
        }
        $columnList = 'id, ' . implode(', ', array_map(fn($col) => "`{$col}`", $safeColumns));
        $result = self::query("SELECT {$columnList} FROM " . static::getTableName() . " WHERE id = ?", [$pkId]);

        return $result->fetch_assoc();
    }

    /**
     * Retrieves all records from the table and maps them to DTOs.
     *
     * @param string|null $key The column to use as the key for the result array.
     * @param string $orderBy The ORDER BY clause. WARNING: Not sanitized, do not use with user input.
     * @return array<int|string, T>
     */
    public static function getList(?string $key = null, string $orderBy = 'id asc'): array
    {
        $sql = "SELECT * FROM " . static::getTableName() . " ORDER BY {$orderBy}";

        return self::makeList($sql, $key, [], static::getDtoClass());
    }

    /**
     * Creates a key-value map from two columns.
     *
     * @param string $keyColumn The column to use as the map key.
     * @param string $valueColumn The column to use as the map value.
     * @param string $orderBy The ORDER BY clause. WARNING: Not sanitized, do not use with user input.
     * @return array<string, mixed>
     */
    public static function getMap(string $keyColumn, string $valueColumn, string $orderBy = 'id asc'): array
    {
        $sql = "SELECT `{$keyColumn}`, `{$valueColumn}` FROM " . static::getTableName() . " ORDER BY {$orderBy}";

        return self::makeMap($sql, $keyColumn, $valueColumn);
    }

    /**
     * Deletes a record by a specific field and value.
     *
     * @param int|string $value The value to match.
     * @param string $field The column to match against.
     * @param string|null $table The table name.
     */
    public static function delete(int|string $value, string $field = 'id', ?string $table = null): void
    {
        $table ??= static::getTableName();
        self::query("DELETE FROM `{$table}` WHERE `{$field}` = ?", [$value]);
    }

    /**
     * Retrieves a map of all column names for the table from the database schema.
     *
     * @return array<string, string>
     */
    public static function getColumnNameMap(): array
    {
        $tableName = static::getTableName();
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
        $result = self::query($sql, [$tableName]);

        $map = [];

        while ($row = $result->fetch_assoc()) {
            $map[$row['COLUMN_NAME']] = $row['COLUMN_NAME'];
        }

        return $map;
    }

    /**
     * Copies a row or set of rows within the same table.
     *
     * @param array<string, string> $columnMap A map of target_column => source_column_or_expression.
     * @param string $where An optional WHERE clause. WARNING: This part is not sanitized. Use with extreme caution.
     */
    public static function copyRow(array $columnMap, string $where = ''): void
    {
        if (empty($columnMap)) {
            return;
        }
        $insertCols = '`' . implode('`, `', array_keys($columnMap)) . '`';
        $selectCols = implode(', ', array_values($columnMap));
        $whereClause = $where ? " WHERE {$where}" : '';

        $sql = "INSERT INTO " . static::getTableName(
        ) . " ({$insertCols}) SELECT {$selectCols} FROM " . static::getTableName() . $whereClause;
        self::query($sql);
    }

    /**
     * Saves a sequential sort order for a list of IDs.
     * This operation is performed within a transaction.
     *
     * @param array<int|string> $ids The ordered list of primary key IDs.
     * @param string $orderingField The name of the ordering column.
     * @param string $key The name of the primary key column.
     */
    public static function saveSort(array $ids, string $orderingField = 'ordering', string $key = 'id'): void
    {
        if (empty($ids)) {
            return;
        }
        $con = self::getConnection();
        $table = static::getTableName();

        $con->begin_transaction();

        try {
            $stmt = $con->prepare("UPDATE `{$table}` SET `{$orderingField}` = ? WHERE `{$key}` = ?");

            if (!$stmt) {
                throw new \Exception("Prepare statement failed: " . $con->error);
            }

            $order = 1;

            foreach ($ids as $id) {
                $stmt->bind_param('is', $order, $id);
                $stmt->execute();
                $order++;
            }
            $con->commit();
        } catch (\Throwable $e) {
            $con->rollback();
            // In a real application, this should be logged.
            // error_log('SaveSort failed: ' . $e->getMessage());
        }
    }
}

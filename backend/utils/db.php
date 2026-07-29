<?php
/**
 * utils/db.php
 * 极简 DB 助手类（基于 PDO）。所有方法均为静态方法。
 */

namespace Utils;

class DB
{
    /**
     * 执行 SQL，返回 PDOStatement。
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * 查询单条记录，无结果返回 null。
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * 查询多条记录。
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * 插入记录，返回 lastInsertId。
     */
    public static function insert(string $table, array $data): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('insert data is empty');
        }
        $cols = array_keys($data);
        $placeholders = array_map(static fn($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $cols),
            implode(',', $placeholders)
        );
        $stmt = getPDO()->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        return (int) getPDO()->lastInsertId();
    }

    /**
     * 更新记录，返回受影响行数。
     */
    public static function update(string $table, array $data, array $where): int
    {
        if (empty($data)) {
            return 0;
        }
        $set = implode(', ', array_map(static fn($c) => "`$c` = :set_$c", array_keys($data)));
        $whereStr = implode(' AND ', array_map(static fn($c) => "`$c` = :where_$c", array_keys($where)));
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, $set, $whereStr);
        $stmt = getPDO()->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(':set_' . $k, $v);
        }
        foreach ($where as $k => $v) {
            $stmt->bindValue(':where_' . $k, $v);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * 删除记录，返回受影响行数。
     */
    public static function delete(string $table, array $where): int
    {
        $whereStr = implode(' AND ', array_map(static fn($c) => "`$c` = :where_$c", array_keys($where)));
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $whereStr);
        $stmt = getPDO()->prepare($sql);
        foreach ($where as $k => $v) {
            $stmt->bindValue(':where_' . $k, $v);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * 统计记录数。
     */
    public static function count(string $table, array $where = []): int
    {
        $whereStr = '';
        if (!empty($where)) {
            $whereStr = ' WHERE ' . implode(' AND ', array_map(static fn($c) => "`$c` = :where_$c", array_keys($where)));
        }
        $sql = sprintf('SELECT COUNT(*) AS c FROM `%s`%s', $table, $whereStr);
        $stmt = getPDO()->prepare($sql);
        foreach ($where as $k => $v) {
            $stmt->bindValue(':where_' . $k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }
}

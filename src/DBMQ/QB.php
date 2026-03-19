<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

class QB
{
    private string $base = '';
    private string $groupBy = '';
    private string $orderBy = '';
    private string $limit = '';
    private array $whereItems = [];

    public function __construct(string $base, string $table = '')
    {
        $this->setBase($base, $table);
    }

    public function startBraces(): self
    {
        $this->whereItems[] = ['type' => 'raw', 'val' => '('];

        return $this;
    }

    public function endBraces(): self
    {
        $this->whereItems[] = ['type' => 'raw', 'val' => ')'];

        return $this;
    }

    public function isNull(string $f): self
    {
        $this->whereItems[] = [
            'type' => 'field',
            'field' => $f,
            'sql' => $f . ' is null',
        ];

        return $this;
    }

    public function _and(?string $f = null, float|int|string|null $v = null): self
    {
        if ($f !== null && $v === null) {
            return $this;
        }

        if ($f === null && $v === null) {
            $this->addLogicOperator(' && ');

            return $this;
        }

        $op = $this->setOperand($f);
        $this->addLogicOperator(' && ');

        $this->whereItems[] = [
            'type' => 'field',
            'field' => $this->cleanFieldName($f),
            'sql' => $op . "'" . self::_e((string) $v) . "'",
        ];

        return $this;
    }

    public function _or(?string $f = null, float|int|string|null $v = null): self
    {
        if ($f !== null && $v === null) {
            return $this;
        }

        if ($f === null && $v === null) {
            $this->addLogicOperator(' || ');

            return $this;
        }

        $op = $this->setOperand($f);
        $this->addLogicOperator(' || ');

        $this->whereItems[] = [
            'type' => 'field',
            'field' => $this->cleanFieldName($f),
            'sql' => $op . "'" . self::_e((string) $v) . "'",
        ];

        return $this;
    }

    public function in(string $f, array|float|int|string|null $v, string $e = "'", string $not = ''): self
    {
        if (!$f || $v === null) {
            return $this;
        }

        $sql = '';

        if (is_array($v) && !empty($v)) {
            $escapedValues = array_map([$this, '_e'], array_map('strval', $v));
            $sql = $f . " $not in (" . $e . implode("$e,$e", $escapedValues) . "$e)";
        } elseif (is_string($v) && $v !== '') {
            $sql = $f . " $not in ($e" . self::_e($v) . "$e)";
        } elseif (is_int($v) || is_float($v)) {
            $sql = $f . " $not in (" . $v . ")";
        }

        if ($sql) {
            $this->whereItems[] = ['type' => 'field', 'field' => $f, 'sql' => $sql];
        }

        return $this;
    }

    public function notIn(string $f, array|float|int|string|null $v, string $e = "'"): self
    {
        return $this->in($f, $v, $e, 'not');
    }

    public function andIn(string $f, array|float|int|string|null $v, string $e = "'"): self
    {
        if (empty($v)) {
            return $this;
        }

        return $this->_and()->in($f, $v, $e);
    }

    public function andNotIn(string $f, array|float|int|string|null $v, string $e = "'"): self
    {
        if (empty($v)) {
            return $this;
        }

        return $this->_and()->in($f, $v, $e, 'not');
    }

    public function orIn(string $f, array|float|int|string|null $v, string $e = "'"): self
    {
        if (empty($v)) {
            return $this;
        }

        return $this->_or()->in($f, $v, $e);
    }

    public function orNotIn(string $f, array|float|int|string|null $v, string $e = "'"): self
    {
        if (empty($v)) {
            return $this;
        }

        return $this->_or()->in($f, $v, $e, 'not');
    }

    public function raw(string $v): self
    {
        $this->whereItems[] = ['type' => 'raw', 'val' => $v];

        return $this;
    }

    public function like(string $f, string $s = '', ?string $v = null, string $e = ''): self
    {
        if ($f && $v !== null) {
            $sql = $f . ' like ' . "'" . $s . self::_e($v) . $e . "'";
            $this->whereItems[] = ['type' => 'field', 'field' => $f, 'sql' => $sql];
        }

        return $this;
    }

    public function andLike(string $f, string $s = '', ?string $v = null, string $e = ''): self
    {
        if ($v === null) {
            return $this;
        }

        return $this->_and()->like($f, $s, $v, $e);
    }

    public function groupBy(string $groupBy): self
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function orderBy(?string $orderBy): self
    {
        if ($orderBy !== null) {
            $this->orderBy = $orderBy;
        }

        return $this;
    }

    public function hasOrderBy(): bool
    {
        return $this->orderBy !== '';
    }

    public function removeFieldFromWhere(string $f): void
    {
        $this->whereItems = array_filter($this->whereItems, function ($item) use ($f) {
            return !($item['type'] === 'field' && $item['field'] === $f);
        });
        $this->whereItems = array_values($this->whereItems);
    }

    public function getQuery(): string
    {
        $whereStr = $this->getWhere();

        return $this->base
            . ($whereStr ? ' where ' . $whereStr : '')
            . ($this->groupBy ? ' group by ' . $this->groupBy : '')
            . ($this->orderBy ? ' order by ' . $this->orderBy : '')
            . ($this->limit ? ' limit ' . $this->limit : '');
    }

    public function setBase(string $base, string $table = ''): void
    {
        $this->base = trim($base) . ($table ? ' ' . $table : '');
    }

    public function setWhere(string $where): void
    {
        $this->whereItems = [];
        $this->raw($where);
    }

    public function setLimit(string $limit): void
    {
        $this->limit = $limit;
    }

    public function setLimitFromPager(PagerPaginate $pager): void
    {
        $limit = $pager->getPage($pager->getCurrentPage()) . ', ' . $pager->getRowsPerPage();
        $this->setLimit($limit);
    }

    public function getWhere(): string
    {
        if (empty($this->whereItems)) {
            return '';
        }
        $whereStr = '';

        foreach ($this->whereItems as $item) {
            $whereStr .= $item['sql'] ?? $item['val'];
        }

        $whereStr = preg_replace('/(\&\&|\|\|)\s*(\&\&|\|\|)/', '$1', trim($whereStr));
        $whereStr = trim($whereStr, ' &|');

        $whereStr = preg_replace('/(\&\&|\|\|)\s*(\&\&|\|\|)/', '$1', trim($whereStr));
        $whereStr = preg_replace('/(?<![a-zA-Z0-9_])\(\s*\)/', '', $whereStr);
        $whereStr = trim($whereStr, ' &|');

        $whereStr = preg_replace('/ {2,}/', ' ', trim($whereStr));

        return $whereStr;
    }

    public static function nullOrVal(float|int|string|null $v, string $eq = '='): string
    {
        if ($v === null) {
            return ' is null';
        } else {
            return $eq . "'" . self::_e((string) $v) . "'";
        }
    }

    public function __toString(): string
    {
        return $this->getQuery();
    }

    private function addLogicOperator(string $op): void
    {
        $last = end($this->whereItems);

        if ($last && $last['val'] !== '(' && $this->whereItems !== []) {
            $this->whereItems[] = ['type' => 'op', 'val' => $op];
        }
    }

    private function cleanFieldName(string $f): string
    {
        return trim(str_replace(['=', '>', '<', '!', ' '], '', $f));
    }

    private static function _e(string $v): string
    {
        return str_replace("'", "\'", $v);
    }

    private function setOperand(?string $f): ?string
    {
        if ($f) {
            $trimmedF = trim($f);

            if (in_array($trimmedF, ['>=', '<=', '>', '<', '!='])) {
                return $f;
            }

            if (!preg_match('/[=><!]/', $trimmedF)) {
                return $trimmedF . ' = ';
            }
        }

        return $f;
    }
}

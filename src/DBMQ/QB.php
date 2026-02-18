<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

// Assuming PagerPaginate is in this namespace or imported.
// If not, you'll need to add a `use` statement for it.
// use Path\To\PagerPaginate;

class QB
{
    private string $base = '';
    private string $where = '';
    private string $groupBy = '';
    private string $orderBy = '';
    private string $limit = '';

    public function __construct(string $base, string $table = '')
    {
        $this->base = trim($base) . ' ' . $table;
    }

    public function startBraces(): self
    {
        $this->where .= '(';

        return $this;
    }

    public function endBraces(): self
    {
        $this->where .= ')';

        return $this;
    }

    public function isNull(string $f): self
    {
        $this->where .= $f . ' is null';

        return $this;
    }

    public function _and(?string $f = null, float|int|string|null $v = null): self
    {
        if ($f !== null && $v === null) {
            return $this;
        }

        $f = $this->setOperand($f);

        if ($f === null && $v === null) {
            if (str_ends_with($this->where, ')') === false && $this->where !== '') {
                $this->where .= ' && ';
            }
        } elseif ($f !== null && $v !== null) {
            if (str_ends_with($this->where, ')') === false && $this->where !== '') {
                $this->where .= ' && ';
            }
            $this->where .= $f . "'" . $this->_e((string) $v) . "'";
        }

        return $this;
    }

    public function _or(?string $f = null, float|int|string|null $v = null): self
    {
        if ($f !== null && $v === null) {
            return $this;
        }

        $f = $this->setOperand($f);

        if ($f === null && $v === null) {
            if (str_ends_with($this->where, ')') === false && $this->where !== '') {
                $this->where .= ' || ';
            }
        } elseif ($f && $v) {
            if (str_ends_with($this->where, ')') === false && $this->where !== '') {
                $this->where .= ' || ';
            }
            $this->where .= $f . "'" . $this->_e((string) $v) . "'";
        }

        return $this;
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     * @param string $not
     */
    public function in(string $f, array|string $v, string $e = "'", string $not = ''): self
    {
        if ($f && is_array($v) && !empty($v)) {
            $escapedValues = array_map([$this, '_e'], array_map('strval', $v));
            $this->where .= $f . " $not in (" . $e . implode("$e,$e", $escapedValues) . "$e)";
        } elseif ($f && is_string($v) && $v !== '') {
            $this->where .= $f . " $not in ($e" . $this->_e($v) . "$e)";
        }

        return $this;
    }

    public function raw(string $v): self
    {
        $this->where .= ' ' . $v;

        return $this;
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     */
    public function notIn(string $f, array|string $v, string $e = "'"): self
    {
        return $this->in($f, $v, $e, 'not');
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     */
    public function andIn(string $f, array|string $v, string $e = "'"): ?self
    {
        if ($f && ((is_array($v) && !empty($v)) || (is_string($v) && $v !== ''))) {
            $this->_and();

            return $this->in($f, $v, $e, '');
        }

        return $this;
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     */
    public function andNotIn(string $f, array|string $v, string $e = "'"): ?self
    {
        if ($f && ((is_array($v) && !empty($v)) || (is_string($v) && $v !== ''))) {
            $this->_and();

            return $this->in($f, $v, $e, 'not');
        }

        return $this;
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     */
    public function orIn(string $f, array|string $v, string $e = "'"): ?self
    {
        if ($f && ((is_array($v) && !empty($v)) || (is_string($v) && $v !== ''))) {
            $this->_or();

            return $this->in($f, $v, $e, '');
        }

        return $this;
    }

    /**
     * @param string $f
     * @param array<scalar>|string $v
     * @param string $e
     */
    public function orNotIn(string $f, array|string $v, string $e = "'"): ?self
    {
        if ($f && ((is_array($v) && !empty($v)) || (is_string($v) && $v !== ''))) {
            $this->_or();

            return $this->in($f, $v, $e, 'not');
        }

        return $this;
    }

    public function like(string $f, string $s = '', ?string $v = null, string $e = ''): self
    {
        if ($f && $v !== null) {
            $this->where .= $f . ' like ' . "'" . $s . $this->_e($v) . $e . "'";
        }

        return $this;
    }

    public function andLike(string $f, string $s = '', ?string $v = null, string $e = ''): self
    {
        if ($f && $v) {
            $this->_and()->like($f, $s, $v, $e);
        }

        return $this;
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

    public function getQuery(): string
    {
        return $this->base
            . ($this->where ? ' where ' . $this->where : '')
            . ($this->groupBy ? ' group by ' . $this->groupBy : '')
            . ($this->orderBy ? ' order by ' . $this->orderBy : '')
            . ($this->limit ? ' limit ' . $this->limit : '');
    }

    public static function nullOrVal(int|string|null $value, string $eq = '='): string
    {
        if ($value === null) {
            return ' is null';
        } else {
            return $eq . $value;
        }
    }

    public function setBase(string $base): void
    {
        $this->base = $base;
    }

    public function setWhere(string $where): void
    {
        $this->where = $where;
    }

    public function setLimit(string $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @param object $pager An object with getPage, getCurrentPage, and getRowsPerPage methods.
     */
    public function setLimitFromPager(object $pager): void
    {
        $limit = $pager->getPage($pager->getCurrentPage()) . ', ' . $pager->getRowsPerPage();
        $this->setLimit($limit);
    }

    public function getWhere(): string
    {
        return $this->where;
    }

    public function removeFieldFromWhere(string $field): void
    {
        if (str_contains($this->where, $field)) {
            $this->where = preg_replace("/$field.*?= '.*?'/", '', $this->where);
            $this->where = preg_replace("/( && ){2,}/", ' && ', $this->where);
            $this->where = preg_replace("/( \|\| ){2,}/", ' || ', $this->where);
        }
    }

    private function _e(string $v): string
    {
        // This is not a secure way to escape SQL. Use prepared statements instead.
        return str_replace("'", "\'", $v);
    }

    public function __toString(): string
    {
        return $this->getQuery();
    }

    private function setOperand(?string $f): ?string
    {
        if ($f) {
            $trimmedF = trim($f);

            if (in_array($trimmedF, ['>=', '<=', '>', '<', '!='])) {
                return $f; // Return original if it's just an operator
            }

            if (!preg_match('/[=><!]/', $trimmedF)) {
                return $trimmedF . ' = ';
            }
        }

        return $f;
    }
}

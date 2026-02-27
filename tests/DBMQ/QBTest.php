<?php

declare(strict_types=1);

namespace Tests\DBMQ;

use Mileena\DBMQ\QB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QBTest extends TestCase
{
    private QB $qb;

    protected function setUp(): void
    {
        $this->qb = new QB("select * from users");
    }

    public function testBasicQuery(): void
    {
        $this->qb->_and('status', 1);
        $this->assertEquals(
            "select * from users where status = '1'",
            $this->qb->getQuery(),
        );
    }

    public function testComplexOrWithBraces(): void
    {
        $this->qb->setWhere('');
        $this->qb->startBraces()
            ->_and('id', 10)
            ->_or('id', 20)
            ->endBraces()
            ->_and('active', 'yes');

        $this->assertEquals(
            "select * from users where (id = '10' || id = '20') && active = 'yes'",
            $this->qb->getQuery(),
        );
    }

    public function testRemoveFieldInTheMiddle(): void
    {
        $this->qb->_and('id', 1)
            ->_and('status', 'active')
            ->_and('type', 'admin');

        $this->qb->removeFieldFromWhere('status');

        $this->assertEquals(
            "select * from users where id = '1' && type = 'admin'",
            $this->qb->getQuery(),
        );
    }

    public function testInAndNotIn(): void
    {
        $this->qb->in('role_id', [1, 2, 3])
            ->andNotIn('status', 'deleted');

        $this->assertEquals(
            "select * from users where role_id in ('1','2','3') && status not in ('deleted')",
            $this->qb->getQuery(),
        );
    }

    public function testRemoveFieldWithDuplicates(): void
    {
        $this->qb->_and('status', 1)
            ->_or('status', 2);

        $this->qb->removeFieldFromWhere('status');

        $this->assertEquals(
            "select * from users",
            $this->qb->getQuery(),
        );
    }

    public function testRemoveFieldFromDeepNestedBraces(): void
    {
        $this->qb->startBraces()
            ->_and('status', 'active')
            ->_and()
            ->startBraces()
            ->_and('id', 1)
            ->_or('id', 2)
            ->endBraces()
            ->endBraces();

        $this->qb->removeFieldFromWhere('id');

        $query = $this->qb->getQuery();
        $this->assertStringContainsString("status = 'active'", $query);
        $this->assertStringNotContainsString("id =", $query);
    }

    public function testDeepNestedBraces(): void
    {
        $this->qb->_and('a', 1)
            ->_and()
            ->startBraces()
            ->_and('b', 2)
            ->_or()
            ->startBraces()
            ->_and('c', 3)
            ->_and('d', 4)
            ->endBraces()
            ->endBraces();

        $this->assertEquals(
            "select * from users where a = '1' && (b = '2' || (c = '3' && d = '4'))",
            $this->qb->getQuery(),
        );
    }

    public function testAllComparisonOperators(): void
    {
        $this->qb->_and('age > ', 18)
            ->_and('price <= ', 100.50)
            ->_and('total != ', 0);

        $this->assertEquals(
            "select * from users where age > '18' && price <= '100.5' && total != '0'",
            $this->qb->getQuery(),
        );
    }

    public function testComplexLikeAndNulls(): void
    {
        $this->qb->andLike('name', '%', 'username', '%')
            ->_and()
            ->isNull('deleted_at')
            ->_or()
            ->isNull('archived_at');

        $this->assertEquals(
            "select * from users where name like '%username%' && deleted_at is null || archived_at is null",
            $this->qb->getQuery(),
        );
    }

    public function testSqlInjectionProtection(): void
    {
        $maliciousValue = "admin' OR '1'='1";
        $this->qb->_and('login', $maliciousValue);

        $this->assertStringContainsString("login = 'admin\' OR \'1\'=\'1'", $this->qb->getQuery());
    }

    public function testEmptyValuesHandling(): void
    {
        $this->qb->_and('deleted', 0)
            ->_and('description', '');

        $this->assertEquals(
            "select * from users where deleted = '0' && description = ''",
            $this->qb->getQuery(),
        );
    }

    public function testMassiveInWithManyValues(): void
    {
        $ids = range(1, 100);
        $this->qb->in('id', $ids);

        $query = $this->qb->getQuery();
        $this->assertStringContainsString("id in ('1','2','3'", $query);
        $this->assertStringContainsString("'100')", $query);
    }

    public function testRemoveFieldThatDoesNotExist(): void
    {
        $this->qb->_and('status', 'active');
        $this->qb->removeFieldFromWhere('non_existent_field');

        $this->assertEquals(
            "select * from users where status = 'active'",
            $this->qb->getQuery(),
        );
    }

    public function testRawExpressionsAndCleanup(): void
    {
        $this->qb->raw("AND (1=1)")
            ->_and('id', 5)
            ->raw("OR EXISTS (select 1 from logs)");

        $this->qb->removeFieldFromWhere('id');

        $query = $this->qb->getQuery();

        $this->assertStringContainsString("1=1", $query);
        $this->assertStringContainsString("EXISTS", $query);
    }

    public function testRawExpressions(): void
    {
        $this->qb->_and()->raw("o.edate >= UNIX_TIMESTAMP(curdate())");
        $this->assertEquals(
            "select * from users where o.edate >= UNIX_TIMESTAMP(curdate())",
            $this->qb->getQuery(),
        );
    }

    public function testDuplicateFieldRemovalWithDifferentOperators(): void
    {
        $this->qb->_and('age >', 18)
            ->_and('age <', 60)
            ->in('age', [20, 30]);

        $this->qb->removeFieldFromWhere('age');

        $this->assertEquals(
            "select * from users",
            $this->qb->getQuery(),
        );
    }

    public function testMassiveRemoval(): void
    {
        $this->qb->_and('a', 1)->_and('b', 2)->_and('c', 3);
        $this->qb->removeFieldFromWhere('a');
        $this->qb->removeFieldFromWhere('b');
        $this->qb->removeFieldFromWhere('c');

        $this->assertEquals(
            "select * from users",
            $this->qb->getQuery(),
        );
    }

    public function testNullValues(): void
    {
        $this->qb->_and('id', null);
        $this->qb->_or('id', null);
        $this->qb->in('id', null);

        $this->qb->andIn('id', null);
        $this->qb->andNotIn('id', null);
        $this->qb->orIn('id', null);
        $this->qb->orNotIn('id', null);

        $this->qb->andLike('id', '', null);
        $this->qb->like('id', '', null);

        $this->assertEquals(
            "select * from users",
            $this->qb->getQuery(),
        );
    }

    public function testEmptyValues(): void
    {
        $this->qb->_and('id', '');
        $this->qb->_or('id', '');
        $this->qb->in('id', '');
        $this->qb->in('id', []);
        //
        $this->qb->andIn('id', '');
        $this->qb->andIn('id', []);
        $this->qb->andNotIn('id', '');
        $this->qb->andNotIn('id', []);
        $this->qb->orIn('id', '');
        $this->qb->orIn('id', []);
        $this->qb->orNotIn('id', '');
        $this->qb->orNotIn('id', []);

        $this->qb->andLike('id', '%', '', '%');
        $this->qb->_or();
        $this->qb->like('id', '%', '');

        $this->assertEquals(
            "select * from users where id = '' || id = '' && id like '%%' || id like '%'",
            $this->qb->getQuery(),
        );
    }

    public function testSetWhere(): void
    {
        $this->qb->_and('id', 1);

        $this->assertEquals(
            "select * from users where id = '1'",
            $this->qb->getQuery(),
        );

        $this->qb->setWhere('');
        $this->assertEquals(
            "select * from users",
            $this->qb->getQuery(),
        );

        $this->qb->setWhere("id in (select user_id from orders where status = 'close' || items > 2) && status = 'active'");

        $this->assertEquals(
            "select * from users where id in (select user_id from orders where status = 'close' || items > 2) && status = 'active'",
            $this->qb->getQuery(),
        );
    }

    public function testNullOrVal(): void
    {
        $this->assertEquals(' is null', QB::nullOrVal(null));
        $this->assertEquals(">'0'", QB::nullOrVal(0, '>'));
    }

    #[DataProvider('qbStepProvider')]
    public function testQueryBuilding(string $base, array $steps, string $expectedSql): void
    {
        $qb = new QB($base);

        foreach ($steps as $step) {
            $method = $step[0];
            $args = $step[1] ?? [];
            $qb->$method(...$args);
        }

        $this->assertEquals($expectedSql, $qb->getQuery());
    }

    public static function qbStepProvider(): array
    {
        return [
            'Basic single condition' => [
                'select * from users',
                [['_and', ['status', 1]]],
                "select * from users where status = '1'",
            ],
            'Complex OR logic with braces' => [
                'select * from users',
                [
                    ['startBraces'],
                    ['_and', ['id', 10]],
                    ['_or', ['id', 20]],
                    ['endBraces'],
                    ['_and', ['active', 'yes']],
                ],
                "select * from users where (id = '10' || id = '20') && active = 'yes'",
            ],
            'Deeply nested braces A AND (B OR (C AND D))' => [
                'select * from users',
                [
                    ['_and', ['a', 1]],
                    ['_and'],
                    ['startBraces'],
                    ['_and', ['b', 2]],
                    ['_or'],
                    ['startBraces'],
                    ['_and', ['c', 3]],
                    ['_and', ['d', 4]],
                    ['endBraces'],
                    ['endBraces'],
                ],
                "select * from users where a = '1' && (b = '2' || (c = '3' && d = '4'))",
            ],
            'Multiple comparison operators' => [
                'select * from users',
                [
                    ['_and', ['age > ', 18]],
                    ['_and', ['price <= ', 100.50]],
                    ['_and', ['total != ', 0]],
                ],
                "select * from users where age > '18' && price <= '100.5' && total != '0'",
            ],
            'LIKE and NULL conditions combination' => [
                'select * from users',
                [
                    ['andLike', ['name', '%', 'username', '%']],
                    ['_and'],
                    ['isNull', ['deleted_at']],
                    ['_or'],
                    ['isNull', ['archived_at']],
                ],
                "select * from users where name like '%username%' && deleted_at is null || archived_at is null",
            ],
            'SQL Injection prevention with escaped quotes' => [
                'select * from users',
                [['_and', ['login', "admin' OR '1'='1"]]],
                "select * from users where login = 'admin\' OR \'1\'=\'1'",
            ],
            'Handling integer zero and empty string' => [
                'select * from users',
                [
                    ['_and', ['deleted', 0]],
                    ['_and', ['description', '']],
                ],
                "select * from users where deleted = '0' && description = ''",
            ],
            'Large IN clause with array' => [
                'select * from users',
                [['in', ['id', [1, 2, 3]]]],
                "select * from users where id in ('1','2','3')",
            ],
            'Logical NOT IN clause' => [
                'select * from users',
                [['andNotIn', ['role', ['guest', 'banned']]]],
                "select * from users where role not in ('guest','banned')",
            ],
            'Empty braces cleanup' => [
                'select * from users',
                [
                    ['startBraces'],
                    ['endBraces'],
                    ['_and', ['active', 1]],
                ],
                "select * from users where active = '1'",
            ],
        ];
    }
}

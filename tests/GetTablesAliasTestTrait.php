<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Tests;

use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\Db\Querys\Query;
use Yiisoft\Db\Exceptions\InvalidArgumentException;
use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Db\Expressions\Expression;

trait GetTablesAliasTestTrait
{
    /**
     * @return Query|ActiveQuery
     */
    abstract protected function createQuery();

    public function testGetTableNamesIsFromArrayWithAlias()
    {
        $query = $this->createQuery($this->db);

        $query->from = [
            'prf'     => 'profile',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
            'post AS p',
        ];

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
            '{{p}}'   => '{{post}}',
        ], $tables);
    }

    public function testGetTableNamesIsFromArrayWithoutAlias()
    {
        $query = $this->createQuery($this->db);
        $query->from = [
            '{{profile}}',
            'user',
        ];

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{profile}}' => '{{profile}}',
            '{{user}}'    => '{{user}}',
        ], $tables);
    }

    public function testGetTableNamesIsFromString()
    {
        $query = $this->createQuery($this->db);
        $query->from = 'profile AS \'prf\', user "usr", `order`, "customer", "a b" as "c d"';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{prf}}'      => '{{profile}}',
            '{{usr}}'      => '{{user}}',
            '{{order}}'    => '{{order}}',
            '{{customer}}' => '{{customer}}',
            '{{c d}}'      => '{{a b}}',
        ], $tables);
    }

    public function testGetTableNamesIsFromObjectgenerateException()
    {
        $query = $this->createQuery($this->db);

        $query->from = new \stdClass();

        $this->expectException(InvalidConfigException::class);

        $query->getTablesUsedInFrom();
    }

    public function testGetTablesAliasisFromString()
    {
        $query = $this->createQuery($this->db);

        $query->from = 'profile AS \'prf\', user "usr", service srv, order, [a b] [c d], {{something}} AS myalias';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{prf}}'     => '{{profile}}',
            '{{usr}}'     => '{{user}}',
            '{{srv}}'     => '{{service}}',
            '{{order}}'   => '{{order}}',
            '{{c d}}'     => '{{a b}}',
            '{{myalias}}' => '{{something}}',
        ], $tables);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14150
     */
    public function testGetTableNamesIsFromPrefixedTableName()
    {
        $query = $this->createQuery($this->db);

        $query->from = '{{%order_item}}';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{%order_item}}' => '{{%order_item}}',
        ], $tables);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14211
     */
    public function testGetTableNamesIsFromTableNameWithDatabase()
    {
        $query = $this->createQuery($this->db);

        $query->from = 'tickets.workflows';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{tickets.workflows}}' => '{{tickets.workflows}}',
        ], $tables);
    }

    public function testGetTableNamesIsFromAliasedExpression()
    {
        $query = $this->createQuery($this->db);

        $expression = new Expression('(SELECT id FROM user)');

        $query->from = $expression;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');
        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{x}}' => $expression], $tables);
    }

    public function testGetTableNamesIsFromAliasedArrayWithExpression()
    {
        $query = $this->createQuery($this->db);

        $query->from = ['x' => new Expression('(SELECT id FROM user)')];

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{x}}' => '(SELECT id FROM user)',
        ], $tables);
    }

    public function testGetTableNamesIsFromAliasedSubquery()
    {
        $query = $this->createQuery($this->db);

        $subQuery = $this->createQuery($this->db);

        $subQuery->from('user');
        $query->from(['x' => $subQuery]);
        $expected = ['{{x}}' => $subQuery];

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals($expected, $tables);
    }
}

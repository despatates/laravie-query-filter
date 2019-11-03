<?php

namespace Laravie\QueryFilter\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Laravie\QueryFilter\Searchable;
use Illuminate\Database\Query\Expression;

class SearchableTest extends TestCase
{
    /** @test */
    public function it_can_build_search_query()
    {
        $query = m::mock('Illuminate\Database\Query\Builder');

        $query->shouldReceive('getConnection->getDriverName')->andReturn('mysql');
        $query->shouldReceive('orWhere')->once()->with(m::type('Closure'))
                ->andReturnUsing(static function ($c) use ($query) {
                    $c($query);
                })
            ->shouldReceive('orWhere')->once()->with('name', 'like', 'hello')
            ->shouldReceive('orWhere')->once()->with('name', 'like', 'hello%')
            ->shouldReceive('orWhere')->once()->with('name', 'like', '%hello')
            ->shouldReceive('orWhere')->once()->with('name', 'like', '%hello%');

        $stub = new Searchable(
            'hello', ['name']
        );

        $this->assertEquals($query, $stub->apply($query));
    }

    /** @test */
    public function it_can_build_search_query_with_expression_value()
    {
        $query = m::mock('Illuminate\Database\Query\Builder');

        $query->shouldReceive('getConnection->getDriverName')->andReturn('mysql');
        $query->shouldReceive('orWhere')->once()->with(m::type('Closure'))
                ->andReturnUsing(static function ($c) use ($query) {
                    $c($query);
                })
            ->shouldReceive('orWhere')->once()->with('users.name', 'like', 'hello')
            ->shouldReceive('orWhere')->once()->with('users.name', 'like', 'hello%')
            ->shouldReceive('orWhere')->once()->with('users.name', 'like', '%hello')
            ->shouldReceive('orWhere')->once()->with('users.name', 'like', '%hello%');

        $stub = new Searchable(
            'hello', [new Expression('users.name')]
        );

        $this->assertEquals($query, $stub->apply($query));
    }

    /** @test */
    public function it_can_build_search_query_with_relation_field()
    {
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $relationQuery = m::mock('Illuminate\Database\Database\Builder');

        $query->shouldReceive('getModel->getConnection->getDriverName')->andReturn('mysql');
        $query->shouldReceive('orWhereHas')->once()->with('users', m::type('Closure'))
            ->andReturnUsing(static function ($r, $c) use ($relationQuery) {
                $c($relationQuery);
            });

        $relationQuery->shouldReceive('where')->once()->with(m::type('Closure'))
                ->andReturnUsing(static function ($c) use ($relationQuery) {
                    $c($relationQuery);
                })
            ->shouldReceive('orWhere')->once()->with('name', 'like', 'hello')
            ->shouldReceive('orWhere')->once()->with('name', 'like', 'hello%')
            ->shouldReceive('orWhere')->once()->with('name', 'like', '%hello')
            ->shouldReceive('orWhere')->once()->with('name', 'like', '%hello%');

        $stub = new Searchable(
            'hello', ['users.name']
        );

        $this->assertEquals($query, $stub->apply($query));
    }

    /** @test */
    public function it_can_build_search_query_with_json_selector()
    {
        $query = m::mock('Illuminate\Database\Database\Builder');

        $query->shouldReceive('getConnection->getDriverName')->andReturn('mysql');
        $query->shouldReceive('orWhere')->once()->with(m::type('Closure'))
                ->andReturnUsing(static function ($c) use ($query) {
                    $c($query);
                })
            ->shouldReceive('orWhereRaw')->once()->with('lower(address->\'$.postcode\') like ?', ['hello'])
            ->shouldReceive('orWhereRaw')->once()->with('lower(address->\'$.postcode\') like ?', ['hello%'])
            ->shouldReceive('orWhereRaw')->once()->with('lower(address->\'$.postcode\') like ?', ['%hello'])
            ->shouldReceive('orWhereRaw')->once()->with('lower(address->\'$.postcode\') like ?', ['%hello%']);

        $stub = new Searchable(
            'hello', ['address->postcode']
        );

        $this->assertEquals($query, $stub->apply($query));
    }
}
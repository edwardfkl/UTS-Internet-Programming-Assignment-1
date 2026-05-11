<?php

namespace Tests\Unit;

use App\Support\AdminListRequest;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AdminListRequestTest extends TestCase
{
    public function test_sort_defaults_when_query_missing(): void
    {
        $request = Request::create('/admin/users');

        [$column, $dir] = AdminListRequest::sort($request, ['id', 'name', 'created_at'], 'created_at');

        $this->assertSame('created_at', $column);
        $this->assertSame('desc', $dir);
    }

    public function test_sort_uses_explicit_query_when_allowed(): void
    {
        $request = Request::create('/admin/users?sort=name&dir=asc');

        [$column, $dir] = AdminListRequest::sort($request, ['id', 'name', 'created_at'], 'created_at');

        $this->assertSame('name', $column);
        $this->assertSame('asc', $dir);
    }

    public function test_sort_falls_back_to_default_for_disallowed_column(): void
    {
        $request = Request::create('/admin/users?sort=password&dir=desc');

        [$column, $dir] = AdminListRequest::sort($request, ['id', 'name'], 'id');

        $this->assertSame('id', $column);
        $this->assertSame('desc', $dir);
    }

    public function test_sort_normalises_invalid_direction_to_desc(): void
    {
        $request = Request::create('/admin/users?sort=name&dir=sideways');

        [, $dir] = AdminListRequest::sort($request, ['name'], 'name', 'asc');

        $this->assertSame('desc', $dir);
    }

    public function test_search_returns_null_for_empty_or_whitespace_query(): void
    {
        $this->assertNull(AdminListRequest::search(Request::create('/admin/users')));
        $this->assertNull(AdminListRequest::search(Request::create('/admin/users?q=')));
        $this->assertNull(AdminListRequest::search(Request::create('/admin/users?q=%20%20')));
    }

    public function test_search_trims_surrounding_whitespace(): void
    {
        $this->assertSame(
            'alice',
            AdminListRequest::search(Request::create('/admin/users?q=%20alice%20')),
        );
    }
}

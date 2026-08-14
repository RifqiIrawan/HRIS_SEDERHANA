<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Translates a jQuery DataTables server-side request into the query parameters
 * this application already speaks.
 *
 * DataTables sends `draw`, `start`, `length`, `search[value]` and
 * `order[0][column]`; every index endpoint here reads `page`, `per_page`,
 * `search`, `sort` and `dir`. Doing the translation once in middleware keeps
 * all fifteen controllers free of DataTables vocabulary — and keeps them
 * callable from a plain `?page=2` request, which is what the test suite uses.
 */
class DataTableRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->has('draw')) {
            return $next($request);
        }

        $length = (int) $request->input('length', 10);
        $start = max(0, (int) $request->input('start', 0));

        // Clamped to exactly the range Controller::perPage() honours. If the two
        // disagreed, the page number computed here would address a different
        // slice than the one the controller returns, and the table would quietly
        // skip or repeat rows. "Semua" arrives as -1 and asks for the maximum.
        $perPage = $length < 1
            ? Controller::MAX_PER_PAGE
            : max(Controller::MIN_PER_PAGE, min(Controller::MAX_PER_PAGE, $length));

        $merged = [
            'per_page' => $perPage,
            'page' => intdiv($start, $perPage) + 1,
        ];

        // DataTables sends `search` as {value, regex}; a controller calling
        // ->string('search') on that array would fail, so it is flattened.
        //
        // Only when it *is* an array, though: each module's own filter bar posts
        // a plain `search=...` over the top of it, and overwriting that with the
        // (absent) nested value would silently discard what the user typed.
        $search = $request->input('search');

        if (is_array($search)) {
            $merged['search'] = trim((string) ($search['value'] ?? ''));
        }

        $merged += $this->sort($request);

        $request->merge($merged);

        return $next($request);
    }

    /**
     * Resolve `order[0]` against the column definitions the client sent.
     *
     * @return array<string, string>
     */
    private function sort(Request $request): array
    {
        $index = $request->input('order.0.column');

        if ($index === null) {
            return [];
        }

        $column = $request->input("columns.{$index}.data");
        $orderable = filter_var(
            $request->input("columns.{$index}.orderable", true),
            FILTER_VALIDATE_BOOLEAN,
        );

        if (! is_string($column) || $column === '' || ! $orderable) {
            return [];
        }

        return [
            'sort' => $column,
            'dir' => strtolower((string) $request->input('order.0.dir')) === 'desc' ? 'desc' : 'asc',
        ];
    }
}

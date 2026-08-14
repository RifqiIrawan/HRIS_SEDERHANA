<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Page-size bounds every listing obeys. Public because DataTableRequest
     * must clamp identically — a client asking for a size outside this range
     * has to be told the same number the controller will actually use, or the
     * page it computes would address a different slice of rows.
     */
    public const MIN_PER_PAGE = 5;

    public const MAX_PER_PAGE = 100;

    /**
     * Whether this request wants data rather than a page.
     *
     * Index routes serve both (spec §47), and the decision has to cover more
     * than jQuery's X-Requested-With header — an `Accept: application/json`
     * caller is asking for data just as clearly.
     */
    protected function wantsData(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }

    /**
     * Wraps a paginator in whichever envelope the caller is asking for.
     *
     * A DataTables request (identified by `draw`, normalised upstream by
     * DataTableRequest middleware) gets the envelope its client reads; every
     * other caller keeps the items/meta shape.
     */
    protected function paginated(LengthAwarePaginator $paginator, callable $map): JsonResponse
    {
        $rows = collect($paginator->items())->map($map)->values();
        $request = request();

        if ($request->has('draw')) {
            return response()->json([
                // Echoed back so DataTables can discard a stale response that
                // overtook a newer one.
                'draw' => (int) $request->input('draw'),
                // The paginator only ever sees the already-filtered query, so
                // both counts are the same number. DataTables then omits its
                // "(filtered from N total)" note rather than quoting a figure
                // we would have had to invent.
                'recordsTotal' => $paginator->total(),
                'recordsFiltered' => $paginator->total(),
                'data' => $rows,
            ]);
        }

        return $this->ok([
            'items' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Attaches module-specific extras (a summary block, a date header) to a
     * paginated response without disturbing either envelope.
     *
     * In the DataTables envelope `data` must stay a plain list of rows, so the
     * extras ride alongside it at the top level — DataTables hands the whole
     * JSON body to its callbacks and ignores keys it does not know.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function withExtra(JsonResponse $response, array $extra): JsonResponse
    {
        $payload = $response->getData(true);

        if (array_key_exists('draw', $payload)) {
            return response()->json($payload + $extra);
        }

        $payload['data'] = array_merge($payload['data'], $extra);

        return response()->json($payload);
    }

    /**
     * Applies a client-requested sort, restricted to an explicit allow-list.
     *
     * The key is the column name the frontend sends; the value is the real
     * column (or columns, for a tie-break) to order by. Anything not on the
     * list falls back to the module's default order, so a crafted
     * `?sort=password` can never reach the query.
     *
     * @param  array<string, string|string[]>  $allowed
     */
    protected function applySort(
        mixed $query,
        Request $request,
        array $allowed,
        string $default,
        string $defaultDirection = 'asc',
    ): mixed {
        $requested = (string) $request->string('sort');
        $direction = strtolower((string) $request->string('dir')) === 'desc' ? 'desc' : 'asc';

        if (! array_key_exists($requested, $allowed)) {
            $requested = $default;
            $direction = $defaultDirection;
        }

        foreach ((array) ($allowed[$requested] ?? $default) as $column) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Page size from the query string, clamped so a crafted ?per_page=100000
     * cannot ask the database for every row.
     */
    protected function perPage(Request $request, int $default = 15): int
    {
        return max(
            self::MIN_PER_PAGE,
            min(self::MAX_PER_PAGE, (int) $request->integer('per_page', $default) ?: $default),
        );
    }

    /**
     * The envelope every AJAX endpoint returns (spec §29): the frontend always
     * reads `success` first and falls back to `message` on failure.
     */
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], fn ($value) => $value !== null), $status);
    }

    protected function fail(string $message, int $status = 422, ?string $code = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], fn ($value) => $value !== null), $status);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Transactions
 * List and categorize bank transactions (single or bulk).
 */
class BankTransactionsController extends Controller
{
    public function index(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @queryParam from date The start date (YYYY-MM-DD).
        // @queryParam to date The end date (YYYY-MM-DD).
        // @queryParam status string Filter by status (imported|posted|locked).
        // @queryParam search string Full-text search on description/counterparty.
        // @queryParam tax_tag string Filter by tax tag.
        // @queryParam category_account_id integer Filter by category account id.
        // @queryParam per_page integer Number of rows per page. Example: 50
        // @queryParam sort string asc|desc Sort by posted_at. Example: desc
        $tenant = Tenancy::current();
        $q = BankTransaction::query()
            ->with(['bankAccount', 'journal'])
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id));

        if ($request->filled('from')) $q->where('posted_at', '>=', $request->query('from'));
        if ($request->filled('to'))   $q->where('posted_at', '<=', $request->query('to'));
        if ($request->filled('status')) $q->where('status', $request->query('status'));
        if ($request->filled('source')) $q->whereJsonContains('raw->source', $request->query('source'));
        if ($request->filled('search')) {
            $term = '%'.$request->query('search').'%';
            $q->where(function ($w) use ($term) {
                $w->where('description', 'like', $term)
                  ->orWhere('counterparty', 'like', $term);
            });
        }
        if ($request->filled('category_account_id')) $q->where('category_account_id', (int)$request->query('category_account_id'));
        if ($request->filled('tax_tag')) $q->where('tax_tag', $request->query('tax_tag'));

        $sort = $request->query('sort', 'desc');
        $q->orderBy('posted_at', $sort === 'asc' ? 'asc' : 'desc');

        $per = min(100, (int) $request->query('per_page', 50));
        return $this->showAll($q->paginate($per));
    }

    public function update(Request $request, int $id)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam category_account_id integer The category account id to assign. Example: 5000
        // @bodyParam tax_tag string A tax tag to apply (eg. vatable|exempt). Example: vatable
        // @bodyParam lock boolean Lock this transaction from edits. Example: true
        $data = $request->validate([
            'category_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_tag' => ['nullable', 'string', 'max:50'],
            'lock' => ['sometimes', 'boolean'],
        ]);

        $tenant = Tenancy::current();
        $tx = BankTransaction::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->findOrFail($id);

        $updates = [];
        if (array_key_exists('category_account_id', $data)) {
            $updates['category_account_id'] = $data['category_account_id'];
            $updates['categorized_at'] = now();
        }
        if (array_key_exists('tax_tag', $data)) $updates['tax_tag'] = $data['tax_tag'];
        if (($data['lock'] ?? false) === true) $updates['status'] = 'locked';

        $tx->update($updates);
        return $this->showOne($tx->fresh());
    }

    public function bulk(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam ids array required The list of transaction ids to update.
        // @bodyParam ids.* integer required Transaction id.
        // @bodyParam category_account_id integer The category account id to assign. Example: 5000
        // @bodyParam tax_tag string A tax tag to apply (eg. vatable|exempt). Example: vatable
        // @bodyParam lock boolean Lock these transactions from edits. Example: true
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'category_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_tag' => ['nullable', 'string', 'max:50'],
            'lock' => ['sometimes', 'boolean'],
        ]);

        $tenant = Tenancy::current();
        $q = BankTransaction::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->whereIn('id', $payload['ids']);

        $updates = [];
        if (array_key_exists('category_account_id', $payload)) {
            $updates['category_account_id'] = $payload['category_account_id'];
            $updates['categorized_at'] = now();
        }
        if (array_key_exists('tax_tag', $payload)) $updates['tax_tag'] = $payload['tax_tag'];
        if (($payload['lock'] ?? false) === true) $updates['status'] = 'locked';

        if (!empty($updates)) $q->update($updates);

        return $this->respondSuccess(['message' => 'Bulk update applied.', 'count' => $q->count()]);
    }
}

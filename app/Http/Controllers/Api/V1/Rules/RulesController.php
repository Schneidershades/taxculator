<?php

namespace App\Http\Controllers\Api\V1\Rules;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Rule;
use App\Support\Tenancy;
use Illuminate\Http\Request;

/**
 * @group Rules
 * Create and manage categorization rules; apply to selection.
 */
class RulesController extends Controller
{
    public function index()
    {
        $tenant = Tenancy::current();
        $q = Rule::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id));
        return $this->showAll($q->orderBy('priority')->get());
    }

    public function store(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam name string required Rule name. Example: MTN to Telecoms
        // @bodyParam matcher_type string required One of contains|equals|regex|amount_range. Example: contains
        // @bodyParam field string required One of description|counterparty. Example: description
        // @bodyParam value string Value used for contains|equals|regex matchers. Example: MTN
        // @bodyParam min_amount number Minimum amount (for amount_range). Example: 1000
        // @bodyParam max_amount number Maximum amount (for amount_range). Example: 100000
        // @bodyParam target_account_id integer Category account to assign on match. Example: 5010
        // @bodyParam tax_tag string Tax tag to apply on match. Example: vatable
        // @bodyParam active boolean Whether the rule is active. Example: true
        // @bodyParam priority integer Order (lower runs first). Example: 50
        $tenant = Tenancy::current();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'matcher_type' => ['required', 'in:contains,equals,regex,amount_range'],
            'field' => ['required', 'in:description,counterparty'],
            'value' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric'],
            'max_amount' => ['nullable', 'numeric'],
            'target_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_tag' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:1'],
        ]);

        $rule = Rule::create(array_merge($data, ['tenant_id' => $tenant?->id]));
        return $this->respondSuccess(['message' => 'Rule created.', 'data' => $rule], 201);
    }

    public function update(Request $request, int $id)
    {
        $tenant = Tenancy::current();
        $rule = Rule::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'matcher_type' => ['sometimes', 'in:contains,equals,regex,amount_range'],
            'field' => ['sometimes', 'in:description,counterparty'],
            'value' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric'],
            'max_amount' => ['nullable', 'numeric'],
            'target_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_tag' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:1'],
        ]);
        $rule->update($data);
        return $this->respondSuccess(['message' => 'Rule updated.', 'data' => $rule]);
    }

    public function destroy(int $id)
    {
        $tenant = Tenancy::current();
        $rule = Rule::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->findOrFail($id);
        $rule->delete();
        return $this->respondSuccess(['message' => 'Rule deleted.']);
    }

    public function apply(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam ids array required IDs of transactions to evaluate.
        // @bodyParam ids.* integer required Transaction id.
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $tenant = Tenancy::current();
        $rules = Rule::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->where('active', true)
            ->orderBy('priority')
            ->get();

        $txs = BankTransaction::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->whereIn('id', $payload['ids'])->get();

        $applied = 0;
        foreach ($txs as $t) {
            foreach ($rules as $r) {
                if ($this->matches($r, $t)) {
                    $updates = [];
                    if ($r->target_account_id) {
                        $updates['category_account_id'] = $r->target_account_id;
                        $updates['categorized_at'] = now();
                    }
                    if ($r->tax_tag) $updates['tax_tag'] = $r->tax_tag;
                    if (!empty($updates)) {
                        $t->update($updates);
                        $applied++;
                    }
                    break; // stop after first matching rule
                }
            }
        }

        return $this->respondSuccess(['message' => 'Rules applied.', 'applied' => $applied]);
    }

    private function matches(Rule $r, BankTransaction $t): bool
    {
        $fieldVal = (string) ($r->field === 'counterparty' ? ($t->counterparty ?? '') : ($t->description ?? ''));
        $amt = (float) $t->amount;

        return match ($r->matcher_type) {
            'contains' => $r->value !== null && stripos($fieldVal, (string)$r->value) !== false,
            'equals' => $r->value !== null && strcasecmp($fieldVal, (string)$r->value) === 0,
            'regex' => $r->value !== null && @preg_match($r->value, $fieldVal) === 1,
            'amount_range' => ($r->min_amount === null || $amt >= (float)$r->min_amount) && ($r->max_amount === null || $amt <= (float)$r->max_amount),
            default => false,
        };
    }
}

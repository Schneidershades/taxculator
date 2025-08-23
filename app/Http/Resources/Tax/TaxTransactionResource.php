<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $rels = $this->relationLoaded('relations') ? $this->relations : collect();

        $classes     = $rels->where('description', 'taxClass');
        $deductions  = $rels->where('description', 'deduction');
        $reliefs     = $rels->where('description', 'relief');
        $tariffs     = $rels->where('description', 'taxedIncomeByTariff');

        $grossIncome   = (float) optional($rels->firstWhere('description', 'grossIncome'))->value
            ?: (float) $classes->sum('value');

        $taxableIncome = (float) optional($rels->firstWhere('description', 'taxableIncome'))->value
            ?: max(0, $grossIncome - (float)$deductions->sum('value') - (float)$reliefs->sum('value'));

        $countryTax = (float) $rels->where('description', 'countryTax')->sum('value');
        $stateTax   = (float) $rels->where('description', 'stateTax')->sum('value');
        $localTax   = (float) $rels->where('description', 'localTax')->sum('value');
        $totalTax   = (float) optional($rels->firstWhere('description', 'totalTax'))->value
            ?: ($countryTax + $stateTax + $localTax);

        $credits = $rels->where('description', 'withholdingCreditApplied');

        $creditsApplied = (float) $credits->sum('value');
        $netTaxDue = (float) optional($rels->firstWhere('description', 'netTaxDue'))->value
            ?: max(0, $totalTax - $creditsApplied);

        $empContribs = $rels->where('description', 'employeeContribution');
        $erContribs  = $rels->where('description', 'employerContribution');
        $employeeContrib = (float) $empContribs->sum('value');
        $employerContrib = (float) $erContribs->sum('value');



        return [
            'id'         => $this->id,
            'identifier' => $this->identifier,
            'user_id'    => $this->user_id,

            'amounts' => [
                'gross_income'   => $grossIncome,
                'taxable_income' => $taxableIncome,
                'country_tax'    => $countryTax,
                'state_tax'      => $stateTax,
                'local_tax'      => $localTax,
                'total_tax'      => $totalTax,
                'credits_applied'  => $creditsApplied, // NEW
                'net_tax_due'      => $netTaxDue,      // NEW
                'employee_contrib' => $employeeContrib,
                'employer_contrib' => $employerContrib,

            ],

            'breakdown' => [
                'classes'    => TaxTransactionRelationResource::collection($classes),
                'deductions' => TaxTransactionRelationResource::collection($deductions),
                'reliefs'    => TaxTransactionRelationResource::collection($reliefs),
                'tariffs'    => TaxTransactionRelationResource::collection($tariffs),
                'credits'    => TaxTransactionRelationResource::collection($credits),
                'employee_contributions' => TaxTransactionRelationResource::collection($empContribs),
                'employer_contributions' => TaxTransactionRelationResource::collection($erContribs),

            ],

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

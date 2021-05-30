<?php

namespace App\Services\Tax;

use App\Services\Tax\TaxTransactionClass;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;
use App\Models\TaxClass;
use App\Models\TaxTransaction;
use App\Models\TaxTransactionRelative;
use App\Models\TaxDeductionClass;
use App\Models\CountryTaxDeductionClass;
use App\Models\CountryClassDeduction;
use App\Models\CountryTaxReliefClass;
use App\Models\CountryTaxTarrif;

class TaxTransactionService
{
	public function register($request)
    {
        // $taxTransaction = TaxTransaction::where('id', 1)->first();

    	$taxTransaction = new TaxTransaction;
    	$taxTransaction->save();
    	foreach ($request['taxClasses'][0] as $key => $value) {
    		if($value){
    			$taxClass = TaxClass::where('short_name', $key)->first();
                $this->newTransactionRelative($taxTransaction->id, 'taxClass', $value, 'amount', $taxClass->id, 'taxClass');
    		}
    	}

    	foreach ($request['taxDeductions'][0] as $key => $value) {

    		$taxDeduction = TaxDeductionClass::where('short_name', $key)->first();

    		$countryTaxDeductionClass = CountryTaxDeductionClass::where('tax_deduction_class_id', $taxDeduction->id)->first();

    		if($countryTaxDeductionClass->deduction_type == 'amount'){
    			$nhf = $countryTaxDeductionClass->value;
    		}

    		if($countryTaxDeductionClass->deduction_type == 'percentage'){
    			$divide = $countryTaxDeductionClass->value / 100;

    			$countryClassDeduction = CountryClassDeduction::where('country_tax_deduction_class_id', $countryTaxDeductionClass->id)
    										->pluck('country_tax_class_id')
    										->toArray();

    			$deductClasses = TaxClass::whereIn('id', $countryClassDeduction)
    								->pluck('short_name')
    								->toArray();

    			$items = array_intersect_key( $request['taxClasses'][0], array_flip($deductClasses));

    			$sum = array_sum($items) * $divide;

                $this->newTransactionRelative($taxTransaction->id, 'countryTaxDeductionClass', $sum, 'percentage', $countryTaxDeductionClass->id, 'countryTaxDeductionClass');

    		}
    	}

    	$grossIncome = (int)$taxTransaction->taxTranactionRelatives->sum('value');

        $this->newTransactionRelative($taxTransaction->id, 'grossIncome', $grossIncome, 'amount', null, null);

    	$reliefs = CountryTaxReliefClass::where('country_id', $request['country_id'])->get();

    	$reliefAmount = 0;

    	foreach ($reliefs as $relief) {

            $applied_by = $relief['relief_type'];
            $model_id = $relief->id;
            $model_type = 'countryTaxReliefClass';

            if($relief['minimum_status'] == 'unlimited' && $relief['maximum_status'] == 'unlimited'){
                $value = $grossIncome * $relief->value / 100;
                $reliefAmount += $value;
                $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);
            }

    		if($grossIncome > $relief['minimum_amount'] && $grossIncome > $relief['maximum_amount'] && $relief->taxReliefClass->code == 'fixed'){

                $value = $grossIncome * $relief->value / 100;
                $reliefAmount += $value;

                $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);

    		}elseif($grossIncome > $relief['minimum_amount'] && $grossIncome < $relief['maximum_amount'] && $relief['maximum_status'] == 'static' && $relief['maximum_status'] == 'static'){
                $value = $relief->value;
                $reliefAmount += $relief->value;

                $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);
    		}
    	}

    	$taxableIncome = $grossIncome - $reliefAmount;

        $this->newTransactionRelative($taxTransaction->id, 'taxableIncome', $taxableIncome, 'amount', null, null);

        $tarrifsOrderedByPercentage = CountryTaxTarrif::where('country_id', $request['country_id'])->orderBy('ordering_id', 'ASC')->get();

        $this->processTaxTarrif($taxTransaction, $taxableIncome, $tarrifsOrderedByPercentage);

        return $taxTransaction;

    }

    private function newTransactionRelative ($id, $description, $value, $applied_by, $model_id=null, $model_type=null)
    {
        $taxRelation = new TaxTransactionRelative;
        $taxRelation->tax_transaction_id = $id;
        $taxRelation->tax_relationable_id = $model_id;
        $taxRelation->tax_relationable_type = $model_type;
        $taxRelation->description = $description;
        $taxRelation->value = $value;
        $taxRelation->applied_by = $applied_by;
        $taxRelation->save();
    }


    private function processTaxTarrif($taxTransaction, $amount, $tarrifsOrderedByPercentage, $tarrifsCollective = null){

        $tarrifsCollective = [];
        $taxCollective = [];

        $remainingAmount = $amount;

        if($remainingAmount >= 0){

            foreach($tarrifsOrderedByPercentage as $tarrif){

                if(!in_array($tarrif['ordering_id'], $tarrifsCollective)){

                    $amountRendered = $remainingAmount - $tarrif['fixed_amount'];

                    if($amountRendered <= 0){
                        $amountByPercentage = $remainingAmount * $tarrif['fixed_percentage']/100;
                    }elseif($amountRendered > 0){
                        $amountByPercentage = $tarrif['fixed_amount'] * $tarrif['fixed_percentage']/100;
                    }

                    if ($remainingAmount > 0){
                        $tarrifsCollective[] = $tarrif['ordering_id'];
                        $taxCollective[] = $amountByPercentage;

                        $this->newTransactionRelative($taxTransaction->id, 'taxedIncomeByTarrif', $amountByPercentage, 'percentage', null, null);
                    }

                    $remainingAmount = $amountRendered;
                }
            }
        }
    }
}
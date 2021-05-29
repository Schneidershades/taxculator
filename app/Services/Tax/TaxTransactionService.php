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
    	// $taxTransaction = new TaxTransaction;
    	// $taxTransaction->save();
    	// foreach ($request['taxClasses'][0] as $key => $value) {
    	// 	if($value){
    	// 		$taxClass = TaxClass::where('short_name', $key)->first();
     //            $this->newTransactionRelative($taxTransaction->id, 'taxClass', $value, 'amount', $taxClass->id, 'taxClass');
    	// 	}
    	// }

    	// foreach ($request['taxDeductions'][0] as $key => $value) {

    	// 	$taxDeduction = TaxDeductionClass::where('short_name', $key)->first();

    	// 	$countryTaxDeductionClass = CountryTaxDeductionClass::where('tax_deduction_class_id', $taxDeduction->id)->first();

    	// 	if($countryTaxDeductionClass->deduction_type == 'amount'){
    	// 		$nhf = $countryTaxDeductionClass->value;
    	// 	}

    	// 	if($countryTaxDeductionClass->deduction_type == 'percentage'){
    	// 		$divide = $countryTaxDeductionClass->value / 100;

    	// 		$countryClassDeduction = CountryClassDeduction::where('country_tax_deduction_class_id', $countryTaxDeductionClass->id)
    	// 									->pluck('country_tax_class_id')
    	// 									->toArray();

    	// 		$deductClasses = TaxClass::whereIn('id', $countryClassDeduction)
    	// 							->pluck('short_name')
    	// 							->toArray();

    	// 		$items = array_intersect_key( $request['taxClasses'][0], array_flip($deductClasses));

    	// 		$sum = array_sum($items) * $divide;

     //            $this->newTransactionRelative($taxTransaction->id, 'countryTaxDeductionClass', $sum, 'percentage', $countryTaxDeductionClass->id, 'countryTaxDeductionClass');

    	// 	}
    	// }

     //    // $taxTransaction = TaxTransaction::where('id', 1)->first();

    	// $grossIncome = (int)$taxTransaction->taxRelativeClasses->sum('value');

     //    $this->newTransactionRelative($taxTransaction->id, 'grossIncome', 0, 'amount', null, null);

    	// $reliefs = CountryTaxReliefClass::where('country_id', $request['country_id'])->get();

    	// $reliefAmount = 0;

    	// foreach ($reliefs as $relief) {

     //        $applied_by = $relief['relief_type'];
     //        $model_id = $relief->id;
     //        $model_type = 'countryTaxReliefClass';

     //        if($relief['minimum_status'] == 'unlimited' && $relief['maximum_status'] == 'unlimited'){
     //            $value = $grossIncome * $relief->value / 100;
     //            $reliefAmount += $value;
     //            $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);
     //        }

    	// 	if($grossIncome > $relief['minimum_amount'] && $grossIncome > $relief['maximum_amount'] && $relief->taxReliefClass->code == 'fixed'){

     //            $value = $grossIncome * $relief->value / 100;
     //            $reliefAmount += $value;

     //            $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);

    	// 	}elseif($grossIncome > $relief['minimum_amount'] && $grossIncome < $relief['maximum_amount'] && $relief['maximum_status'] == 'static' && $relief['maximum_status'] == 'static'){
     //            $value = $relief->value;
     //            $reliefAmount += $relief->value;

     //            $this->newTransactionRelative($taxTransaction->id, $model_type, $value, $applied_by, $model_id, $model_type);
    	// 	}
    	// }

    	// $taxableIncome = $grossIncome - $reliefAmount;

     //    $this->newTransactionRelative($taxTransaction->id, 'taxableIncome', 0, 'amount', null, null);

        return $tarrifsOrderedByPercentage = CountryTaxTarrif::where('country_id', $request['country_id'])->orderBy('fixed_percentage', 'ASC')->get();

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


    private function processTaxTarrif($taxTransaction, $amount, $tarrifsOrderedByPercentage){
        $tarrifsCollective = [];

        $remainingAmount = $amount;

        if($amount > 0){
            foreach($tarrifsOrderedByPercentage as $tarrif){
                if(!in_array($tarrif['percentage'], $tarrifsCollective) && $tarrif['above_percentage_max_range'] == false ){

                    $amountRendered = $amount - $tarrif['amount'];
                    $amountByPercentage = $tarrif['amount'] * $tarrif['amount'] / 100;
                    $remainingAmount -= $amount;
                    $this->newTransactionRelative($taxTransaction->id, 'taxableIncome', 0, 'amount', null, null);

                }elseif($tarrif['above_percentage_max_range'] == false){

                    $amountRendered = $amount - $tarrif['amount'];
                    $amountByPercentage = $tarrif['amount'] * $tarrif['amount'] / 100;
                    $this->newTransactionRelative($taxTransaction->id, 'taxableIncome', 0, 'amount', null, null);

                }
            }
        }
    }

}
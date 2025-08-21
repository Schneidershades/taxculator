<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxTariffResource extends JsonResource
{
    public function toArray($request)
    {
        // _level is set in controller (country/state/local). fall back if missing.
        $level = $this->_level ?? optional($this->version->jurisdiction)->level;

        return [
            'id'           => $this->id,
            'tax_version_id' => $this->tax_version_id,
            'level'        => $level,
            'ordering'     => (int) $this->ordering,
            'bracket_min'  => (float) $this->bracket_min,
            'bracket_max'  => $this->bracket_max === null ? null : (float) $this->bracket_max,
            'rate_type'    => $this->rate_type,
            'rate_value'   => (float) $this->rate_value,
        ];
    }
}

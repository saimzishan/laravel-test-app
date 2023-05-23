<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionShiftRequest extends Model
{
    protected $fillable = [
        "promotion_id", "firm_id", "subscription_from", "subscription_to", "end_date" ];
}

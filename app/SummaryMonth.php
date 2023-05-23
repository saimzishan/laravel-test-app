<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryMonth extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "month", "revenue", "expense", "collection", "credits", "refunds",
        "available_time", "worked_time", "billed_time", "collected_time", "billable_hours",
        "billed_hours", "new_clients", "clients_mom", "new_matters", "matters_mom",
        "revenue_percentage_growth", "expense_percentage_growth", "collection_percentage_growth",
        "revenue_avg_rate", "revenue_forecast", "revenue_actual_vs_forecast", "revenue_avg_rate_forecast",
        "revenue_avg_rate_actual_vs_forecast", "billed_vs_collected", "billed_time_forecast",
        "billed_actual_vs_forecast", "revenue_mom", "expense_mom", "collection_mom", "overall_gross_profit_margin",
        "utilization_rate", "realization_rate", "collection_rate"
    ];
}

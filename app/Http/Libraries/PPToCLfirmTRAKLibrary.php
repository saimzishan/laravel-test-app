<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries;

use App\CLTask;
use App\CLUser;
use App\PPTask;
use App\PPUser;
use App\CLMatter;
use App\PPMatter;
use App\CLContact;
use App\CLInvoice;
use App\PPAccount;
use App\PPContact;
use App\PPExpense;
use App\PPInvoice;
use Carbon\Carbon;
use App\PPTaskUser;
use App\CLTimeEntry;
use App\PPTimeEntry;
use App\CLMatterUser;
use App\PPMatterUser;
use App\CLPracticeArea;
use App\CLTaskAssignee;
use App\CLInvoiceMatter;
use App\CLMatterContact;
use App\FirmIntegration;
use App\CLMatterMatterType;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\DB;

class PPToCLfirmTRAKLibrary
{
    private $integration = null;
    private $firm_id;

    public function __construct($firm_id) {
        $this->firm_id = $firm_id;
        $this->integration = FirmIntegration::where("firm_id", $this->firm_id)->first();
    }



    public function syncUsers()
    {
        $success = false;
       
            $response = PPUser::where("firm_id", $this->firm_id)->get();
            foreach($response as $a)
            {
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = CLUser::where("firm_id",$this->firm_id)->where("ref_id", $a->id);
                if ($row->count() == 1) {
                    $row = $row->first();
                } else {
                    $row = new CLUser;
                }
                $row->firm_id = $this->firm_id;
                $row->ref_id = $a->id;
                $row->etag = $a->ref_id;
                $row->enabled = true;
                $row->name = $a->display_name;
                $row->first_name = $a->first_name;
                $row->last_name = $a->last_name;
                $row->phone_number = null;
                $row->email = $a->email;
                $row->type = $a->type;
                $row->date_of_joining = $a->date_of_joining;
                $row->hours_per_week = $a->hours_per_week;
                $row->rate_per_hour = $a->rate_per_hour;
                $row->cost_per_hour = $a->cost_per_hour;
                $row->fte_hours_per_month = $a->fte_hours_per_month;
                $row->fte_equivalence = $a->fte_equivalence;
                $row->monthly_billable_target = $a->monthly_billable_target;
                $row->can_be_calculated = $a->can_be_calculated;
                $row->subscription_type = null;
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
            }
          
       
        
        return $success;
    }

    // public function syncAccounts()
    // {
    //     $success = false;
    //     $response = Curl::to('https://app.practicepanther.com/api/v2/accounts')
    //         ->withHeader("Authorization: BEARER {$this->integration->access_token}")
    //         ->get();
    //     PPAccount::where("firm_id", $this->firm_id)->delete();
    //     foreach(json_decode($response) as $a)
    //     {
    //         $row = new PPAccount;
    //         $row->firm_id = $this->firm_id;
    //         $row->ref_id = $a->id;
    //         $row->display_name = $a->display_name;
    //         $row->company_name = $a->company_name;
    //         $row->created_at = (new Carbon($a->created_at))->format("Y-m-d H:i:s");
    //         $row->updated_at = (new Carbon($a->updated_at))->format("Y-m-d H:i:s");
    //         $success = $row->save();
    //     }
    //     return $success;
    // }

    public function syncContacts()
    {
        $success = false;
        $responseA = PPAccount::where("firm_id", $this->firm_id)->get();
        $responseC = PPContact::where("firm_id", $this->firm_id)->get();
        CLContact::where("firm_id",$this->firm_id)->delete();
            foreach($responseA as $a)
            {
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = new CLContact;
                $row->firm_id =$this->firm_id;
                $row->ref_id = $a->id;
                $row->etag = $a->ref_id;
                $row->first_name = $a->display_name;
                $row->last_name =null;
                $row->middle_name = null;
                $row->type = "Company";
                $row->prefix = null;
                $row->title = null;
                $row->initials = "C";
                $row->primary_email_address = null;
                $row->primary_phone_no = null;
                $row->is_client = false;
                $row->company_id = null;            
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
            }
            foreach($responseC as $a)
            {
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = new CLContact;
                $row->firm_id =$this->firm_id;
                $row->ref_id = $a->id;
                $row->etag = $a->ref_id;
                $row->first_name = $a->first_name;
                $row->last_name = $a->last_name;
                $row->middle_name = null;
                $row->type = "Person";
                $row->prefix = null;
                $row->title = null;
                $row->initials = "P";
                $row->primary_email_address = $a->email;
                $row->primary_phone_no = $a->phone_no;
                $row->is_client = true;
                $row->company_id = CLContact::getIdfromRefID($a->id,$this->firm_id);            
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
            }
        
        return $success;
    }

    public function syncMatters()
    {
        $success = false;

        CLMatter::where("firm_id", $this->firm_id)->delete();
        CLMatterUser::where("firm_id", $this->firm_id)->delete();
        CLMatterContact::where("firm_id", $this->firm_id)->delete();
            $response = PPMatter::where("firm_id", $this->firm_id)->get();
            foreach($response as $a)
            {
                $open_date = new Carbon(optional($a)->open_date);
                $close_date = new Carbon(optional($a)->close_date);
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = new CLMatter;
                $row->firm_id = $this->firm_id;
                $row->ref_id = $a->id;
                $row->etag = $a->ref_id;
                $row->number = optional($a)->number;
                $row->display_number = optional($a)->display_name;
                $row->custom_number = null;
                $row->description = null;
                $row->status = optional($a)->status;
                $row->location = null;
                $row->matter_type = $a->matter_type;
                $row->open_date = $a->open_date == null ? null : $open_date->format("Y-m-d");
                $row->close_date = $a->close_date == null ? null : $close_date->format("Y-m-d");
                $row->pending_date = null;
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
                $matterUser = PPMatterUser::where("firm_id", $this->firm_id)->where("pp_matter_id",$a->id)->get();
                foreach($matterUser as $user)
                {
                    $pivot = new CLMatterUser;
                    $pivot->firm_id = $this->firm_id;
                    $pivot->clio_matter_id = CLMatter::getIDfromRefID($a->id,$this->firm_id);
                    $pivot->clio_user_id = CLUser::getIDfromRefID($user->pp_user_id,$this->firm_id);
                    $pivot->save();
                    unset($pivot);
                }
                $matterContact = PPAccount::where("firm_id", $this->firm_id);
                $pivot = new CLMatterContact;
                $pivot->firm_id = $this->firm_id;
                $pivot->clio_matter_id = CLMatter::getIDfromRefID($a->id,$this->firm_id);
                $pivot->clio_contact_id = CLContact::getIDfromRefID($a->pp_account_id,$this->firm_id);
                $pivot->save();
                unset($pivot);
            
            }
           
        return $success;
    }

    public function syncTasks()
    {
        $success = false;
        CLTask::where("firm_id", $this->firm_id)->delete();
        CLTaskAssignee::where("firm_id", $this->firm_id)->delete();
        $response =   PPTask::where("firm_id", $this->firm_id)->get();
        foreach($response as $a)
        {
            $row = new CLTask;
            $due_at = new Carbon(optional($a)->due_date);
            $created_at = new Carbon($a->created_at);
            $updated_at = new Carbon($a->updated_at);
            $row->firm_id = $this->firm_id;
            $row->ref_id = $a->id;
            $row->etag = $a->ref_id;
            if (strlen($a->subject) > 20) {
                $str = substr($a->subject, 0, 20) . '...';
            } else {
                $str = $a->subject;
            }
            $row->name = htmlspecialchars_decode($str);
            $row->status = optional($a)->status;
            $row->description = null;
            $row->priority = null;
            $row->statute_of_limitation = null;
            $row->task_type = null; 
            $row->clio_matter_id = $a->pp_matter_id == 0 ? 0 : CLMatter::getIDfromRefID($a->pp_matter_id,$this->firm_id);
            $row->assigner =    CLUser::where("firm_id", $this->firm_id)->first()->id;
            $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
            $row->completed_at = null;
            $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
            $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");

            $success = $row->save();
            unset($row);
            $taskUser = PPTaskUser::where("firm_id", $this->firm_id)->where("pp_task_id",$a->id)->get();
            foreach($taskUser as $user)
            {
                $pivot = new CLTaskAssignee;
                $pivot->firm_id = $this->firm_id;
                $pivot->clio_task_id = CLTask::getIDfromRefID($a->id,$this->firm_id);
                $pivot->ref_id = $user->pp_user_id;
                $pivot->type = null;
                $pivot->identifier = null;
                $pivot->name = null;
                $pivot->enabled = true;
                $pivot->save();
                unset($pivot);
                
            }
        }
        return $success;
    }

    public function syncTimeEntries()
    {
        $success = false;
      
        CLTimeEntry::where("firm_id", $this->firm_id)->delete();
        $responseT = PPTimeEntry::where("firm_id", $this->firm_id)->get();
        $responseE = PPExpense::where("firm_id", $this->firm_id)->get();
        foreach($responseT as $a)
        {
            $created_at = new Carbon($a->created_at);
            $updated_at = new Carbon($a->updated_at);
            $row = new CLTimeEntry();
            $row->firm_id = $this->firm_id;
            $row->ref_id = $a->id;
            $row->etag = $a->ref_id;
            $row->type = "TimeEntry";
            $row->quantity_in_hours = optional($a)->hours;
            $row->quantity = optional($a)->hours;
            $row->price = optional($a)->rate;
            $row->note = null;
            $row->flat_rate = null;
            $row->billed = optional($a)->is_billed;
            $row->on_bill = $a->is_billed == true ? false : true;
            $row->total = null;
            $row->contingency_fee = null;
            $row->clio_matter_id = CLMatter::getIDfromRefID($a->pp_matter_id,$this->firm_id);
            $row->clio_user_id = CLUser::getIDfromRefID($a->billed_by_user_id,$this->firm_id);
            $row->date = null;
            $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
            $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
            $success = $row->save();
            unset($row);
        }
        foreach($responseE as $a)
        {
            $date = new Carbon(optional($a)->date);
            $created_at = new Carbon($a->created_at);
            $updated_at = new Carbon($a->updated_at);
            $row = new CLTimeEntry();
            $row->firm_id = $this->firm_id;
            $row->ref_id = $a->id;
            $row->etag = $a->ref_id;
            $row->type = "ExpenseEntry";
            $row->quantity_in_hours = 1;
            $row->quantity = 1;
            $row->price = optional($a)->amount;
            $row->note = null;
            $row->flat_rate = null;
            $row->billed = optional($a)->is_billed;
            $row->on_bill = $a->is_billed == true ? false : true;
            $row->total = optional($a)->amount;
            $row->contingency_fee =null;
            $row->clio_matter_id = CLMatter::getIDfromRefID($a->pp_matter_id,$this->firm_id);
            $row->clio_user_id = CLUser::getIDfromRefID($a->billed_by_user_id,$this->firm_id);
            $row->date = $a->date == null ? null : $date->format("Y-m-d");
            $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
            $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
            $success = $row->save();
            unset($row);
        }
        return $success;
    }

    public function syncInvoices()
    {
        $success = false;
        CLInvoice::where("firm_id", $this->firm_id)->delete();
        CLInvoiceMatter::where("firm_id", $this->firm_id)->delete();
        $response = PPInvoice::where("firm_id", $this->firm_id)->get();
        foreach($response as $a)
        {
         
            $created_at = new Carbon($a->created_at);
            $updated_at = new Carbon($a->updated_at);
            $issued_at = new Carbon(optional($a)->issued_date);
            $due_at = new Carbon(optional($a)->due_date);
            $start_at = new Carbon(optional($a)->issued_Date);
            $row = new CLInvoice;
            $row->firm_id = $this->firm_id;
            $row->ref_id = $a->id;
            $row->etag = $a->ref_id;
            $row->number = null;
            $row->subject = null;
            $row->purchase_order = null;
            $row->type = optional($a)->invoice_type;
            $row->balance = optional($a)->total_outstanding;
            $row->config = null;
            $row->state = null;
            $row->kind = optional($a)->invoice_type;
            $row->total = optional($a)->total;
            $row->paid = optional($a)->total_paid;
            $row->pending = optional($a)->total_outstanding;
            $row->due = optional($a)->total;
            $row->sub_total = optional($a)->sub_total == null ? optional($a)->total: optional($a)->sub_total;
            $row->clio_user_id = null;
            $row->clio_contact_id = CLContact::getIDfromRefID($a->pp_account_id,$this->firm_id);
            $row->issued_at = $a->issued_at == null ? null : $issued_at->format("Y-m-d");
            $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
            $row->start_at = $a->start_at == null ? null : $start_at->format("Y-m-d");
            $row->end_at = null;
            $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
            $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
            $success = $row->save();
            $pivot = new CLInvoiceMatter;
            $pivot->firm_id = $this->firm_id;
            $pivot->clio_invoice_id = CLInvoice::getIDfromRefID($a->id, $this->firm_id);
            $pivot->clio_matter_id = CLMatter::getIDfromRefID($a->pp_matter_id,$this->firm_id);
            $pivot->save();
            unset($pivot);
    
            unset($row);
        }
             
        return $success;
    }

    // public function syncExpenses()
    // {
    //     $success = false;
    //     $response = Curl::to('https://app.practicepanther.com/api/v2/expenses')
    //         ->withHeader("Authorization: BEARER {$this->integration->access_token}")
    //         ->get();
    //     PPExpense::where("firm_id", $this->firm_id)->delete();
    //     foreach(json_decode($response) as $a)
    //     {
    //         $created_at = new Carbon($a->created_at);
    //         $updated_at = new Carbon($a->updated_at);
    //         $date = new Carbon($a->date);
    //         $row = new PPExpense();
    //         $row->firm_id = $this->firm_id;
    //         $row->ref_id = $a->id;
    //         $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id);
    //         $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id);
    //         $row->billed_by_user_id = PPUser::getIDfromRefID(optional($a->billed_by_user_ref)->id);
    //         $row->category = optional($a->expense_category_ref)->name;
    //         $row->is_billable = $a->is_billable;
    //         $row->is_billed = $a->is_billed;
    //         $row->date = $a->date == null ? null : $date->format("Y-m-d H:i:s");
    //         $row->amount = $a->amount;
    //         $row->description = $a->description;
    //         $row->private_notes = $a->private_notes;
    //         $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
    //         $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
    //         $success = $row->save();
    //     }
    //     return $success;
    // }

    public function syncPracticeAreas()
    {
        $success = false;
        $response = Curl::to('https://app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at')
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->get();
        CLPracticeArea::where("firm_id", $this->firm_id)->delete();
        do
        {
            $response = json_decode($response);
            foreach($response->data as $a)
            {
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = new CLPracticeArea;
                $row->firm_id = $this->firm_id;
                $row->ref_id = $a->id;
                $row->etag = $a->etag;
                $row->name = optional($a)->name;
                $row->code = optional($a)->code;
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
            }
            $next = null;    
            if($response->meta->paging != null || $response->meta->paging != "")
            {
                $next =   optional($response->meta->paging)->next;
            }
            $response = Curl::to($next)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->get(); 
        }while ($next != null);  
        return $success;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries;

use App\FirmIntegration;
use App\IntegrationHistory;
use App\PPAccount;
use App\PPContact;
use App\PPContactTask;
use App\PPExpense;
use App\PPInvoice;
use App\PPInvoiceLineItem;
use App\PPMatter;
use App\PPMatterUser;
use App\PPTask;
use App\PPTaskUser;
use App\PPTimeEntry;
use App\PPUser;
use App\Setting;
use Carbon\Carbon;
use Curl;

class PracticePantherAPILibrary
{
    private $integration = null;
    private $app = null;
    private $debuging = false;
    private $firm_id;
    private $update_date = null;

    public function __construct($firm_id, $debuging=false) {
        $this->firm_id = $firm_id;
        $this->debuging = $debuging;
        $this->integration = FirmIntegration::where("firm_id", $this->firm_id)->first();
        $this->app = HelperLibrary::getSettings(["pp_client_id", "pp_client_secret"]);
        if($this->integration->last_sync == NULL) {
            $temp = Carbon::now();
        } else {
            $temp = Carbon::createFromFormat("Y-m-d H:i:s",$this->integration->last_sync);
        }
        $temp->subDays( 1);
        $temp1 = $temp->toAtomString();
        $this->update_date = substr($temp1,0,19);
    }

    public function generateToken()
    {
        if (isset($this->integration->code)) {
            $response = Curl::to('https://app.practicepanther.com/oauth/token')
                ->withData([
                    "grant_type" => "authorization_code",
                    "code" => $this->integration->code,
                    "client_id" => $this->app->pp_client_id,
                    "client_secret" => $this->app->pp_client_secret,
                    "redirect_uri" => secure_url('/oauth/pp') . '/'
                ])
                ->withContentType('application/x-www-form-urlencoded')
                ->post();
            $data = json_decode($response);
            if (isset($data->access_token)) {
                $history = new IntegrationHistory;
                $history->firm_id = $this->firm_id;
                $history->code = $this->integration->code;
                $history->access_token = $this->integration->access_token;
                $history->refresh_token = $this->integration->refresh_token;
                $this->integration->access_token = $data->access_token;
                $this->integration->refresh_token = $data->refresh_token;
                $history->save();
                return $this->integration->save();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function refreshToken()
    {
        if (isset($this->integration->refresh_token)) {
            $response = Curl::to('https://app.practicepanther.com/oauth/token')
                ->withData([
                    "grant_type"=>"refresh_token",
                    "refresh_token"=> $this->integration->refresh_token,
                    "client_id"=>$this->app->pp_client_id,
                    "client_secret"=>$this->app->pp_client_secret
                ])
                ->post();
            $data = json_decode($response);
            if (isset($data->access_token)) {
                $this->integration->access_token = $data->access_token;
                $this->integration->refresh_token = $data->refresh_token;
                $history = new IntegrationHistory;
                $history->firm_id = $this->firm_id;
                $history->code = $this->integration->code;
                $history->access_token = $data->access_token;
                $history->refresh_token = $data->refresh_token;
                $history->save();
                return $this->integration->save();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function syncUsers()
    {
        if ($this->debuging) {
            dump("Users Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/users')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                dump("Processing\n");
                foreach($response as $a)
                {
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = PPUser::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new PPUser;
                        $row->hours_per_week = 40;
                        $row->date_of_joining = $a->created_at == null ? "1970-01-01" : $created_at->format("Y-m-d");
                        $row->can_be_calculated = true;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->is_active = $a->is_active;
                    $row->display_name = $a->display_name;
                    $row->first_name = $a->first_name;
                    $row->last_name = $a->last_name;
                    $row->middle_name = $a->middle_name;
                    $row->email = $a->email;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $row->save();
                    unset($row);
                }
                $success = true;
            }
        }
        return $success;
    }

    public function syncAccounts()
    {
        if ($this->debuging) {
            dump("Accounts Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/accounts')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPAccount::where("firm_id", $this->firm_id)->delete();
                }
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($response as $a)
                {
                    $row = new PPAccount;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->display_name = $a->display_name;
                    $row->company_name = $a->company_name;
                    $row->created_at = (new Carbon($a->created_at))->format("Y-m-d H:i:s");
                    $row->updated_at = (new Carbon($a->updated_at))->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if($counter == 500)
                    {
                        $success = PPAccount::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = PPAccount::insert($bulk_data);
                    unset($bulk_data);
                }
            }
        }
        return $success;
    }

    public function syncContacts()
    {
        if ($this->debuging) {
            dump("Contacts Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/contacts')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPContact::where("firm_id", $this->firm_id)->delete();
                    $bulk_data = [];
                    $counter = 0;
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $row = new PPContact;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->account_display_name = optional($a->account_ref)->display_name;
                        $row->is_primary = $a->is_primary_contact;
                        $row->display_name = $a->display_name;
                        $row->first_name = $a->first_name;
                        $row->last_name = $a->last_name;
                        $row->phone_no = $a->phone_mobile;
                        $row->phone_home = $a->phone_home;
                        $row->phone_fax = $a->phone_fax;
                        $row->phone_work = $a->phone_work;
                        $row->email = $a->email;
                        $bulk_data[] = $row->attributesToArray();
                        unset($row);
                        if($counter == 500)
                        {
                            $success = PPContact::insert($bulk_data);
                            unset($bulk_data);
                            $bulk_data = [];
                            $counter = 0;
                        } else {
                            $counter = $counter + 1;
                        }
                    }
                    if($bulk_data != null ){
                        $success = PPContact::insert($bulk_data);
                        unset($bulk_data);
                    }
                }
            }
        }
        return $success;
    }

    public function syncMatters()
    {
        if ($this->debuging) {
            dump("Matters Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/matters')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPMatter::where("firm_id", $this->firm_id)->delete();
                    PPMatterUser::where("firm_id", $this->firm_id)->delete();
                    $bulk_data = [];
                    $bulk_user = [];
                    $temp_user = [];
                    $counter = 0;
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $open_date = new Carbon($a->open_date);
                        $close_date = new Carbon($a->close_date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row = new PPMatter;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->account_display_name = optional($a->account_ref)->display_name == null ? null : optional($a->account_ref)->display_name;
                        $row->number = preg_replace('/[,]+/', '-', trim($a->number));
                        $row->display_name = preg_replace('/[,]+/', '-', trim($a->display_name));
                        $row->name =  preg_replace('/[,]+/', '-', trim($a->name));
                        $row->open_date = $a->open_date == null ? null : $open_date->format("Y-m-d H:i:s");
                        $row->close_date = $a->close_date == null ? null : $close_date->format("Y-m-d H:i:s");
                        $row->status = $a->status;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        foreach($a->custom_field_values as $field)
                        {
                            if ($field->custom_field_ref->label == "Matter Type") {
                                $row->matter_type =  $field->value_string == null ? null : $field->value_string;
                            }
                        }
                        $row->matter_type = $row->matter_type == null ? null : $row->matter_type;
                        $bulk_data[] = $row->attributesToArray();
                        unset($row);
                        foreach($a->assigned_to_users as $user)
                        {
                            $pivot = new PPMatterUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->pp_matter_id = $a->id;
                            $pivot->pp_user_id = $user->id;
                            $temp_user[] = $pivot;
                            unset($pivot);
                        }
                       if($counter == 500) {
                           $success = PPMatter::insert($bulk_data);
                           foreach($temp_user as $user)
                           {
                               $user->pp_matter_id = PPMatter::getIDfromRefID($user->pp_matter_id, $this->firm_id);
                               $user->pp_user_id = PPUser::getIDfromRefID($user->pp_user_id, $this->firm_id);
                               $bulk_user[] = $user->attributesToArray();
                           }
                           $success = PPMatterUser::insert($bulk_user);
                           unset($bulk_data);
                           unset($bulk_user);
                           unset($temp_user);
                           $bulk_data = [];
                           $bulk_user = [];
                           $temp_user = [];
                           $counter = 0;
                       } else {
                           $counter = $counter + 1;
                       }

                    }
                    if ($bulk_data != null) {
                        $success = PPMatter::insert($bulk_data);
                        foreach($temp_user as $user)
                        {
                            $user->pp_matter_id = PPMatter::getIDfromRefID($user->pp_matter_id, $this->firm_id);
                            $user->pp_user_id = PPUser::getIDfromRefID($user->pp_user_id, $this->firm_id);
                            $bulk_user[] = $user->attributesToArray();
                        }
                        $success = PPMatterUser::insert($bulk_user);
                        unset($bulk_data);
                        unset($bulk_user);
                        unset($temp_user);
                    }
                }

            }
        }
        return $success;
    }

    public function syncTasks()
    {
        if ($this->debuging) {
            dump("Tasks Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/tasks')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPTask::where("firm_id", $this->firm_id)->delete();
                    PPTaskUser::where("firm_id", $this->firm_id)->delete();
                    PPContactTask::where("firm_id", $this->firm_id)->delete();
                    $bulk_data = [];
                    $bulk_user = [];
                    $bulk_contact = [];
                    $temp_user = [];
                    $temp_contact = [];
                    $counter = 0;
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $row = new PPTask;
                        $due_date = new Carbon($a->due_date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->subject= $a->subject;
                        //$row->notes = $a->notes;
                        $row->status = $a->status;
                        $row->due_date = $a->due_date == null ? null : $due_date->format("Y-m-d H:i:s");
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $bulk_data[] = $row->attributesToArray();
                        unset($row);
                        foreach($a->assigned_to_users as $user)
                        {
                            $pivot = new PPTaskUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->pp_task_id = $a->id;
                            $pivot->pp_user_id = $user->id;
                            $temp_user[] = $pivot;
                            unset($pivot);
                        }
                        foreach($a->assigned_to_contacts as $contact)
                        {
                            $pivot1 = new PPContactTask;
                            $pivot1->firm_id = $this->firm_id;
                            $pivot1->pp_task_id = $a->id;
                            $pivot1->pp_contact_id = $contact->id;
                            $temp_contact[] = $pivot1;
                            unset($pivot1);
                        }
                       if ($counter == 500) {
                           $success = PPTask::insert($bulk_data);
                           foreach($temp_user as $user)
                           {
                               $user->pp_task_id = PPTask::getIDfromRefID($user->pp_task_id, $this->firm_id);
                               $user->pp_user_id = PPUser::getIDfromRefID($user->pp_user_id, $this->firm_id);
                               $bulk_user[] = $user->attributesToArray();
                           }
                           foreach($temp_contact  as $contact)
                           {
                               $contact->pp_task_id = PPTask::getIDfromRefID($contact->pp_task_id, $this->firm_id);
                               $contact->pp_contact_id = PPContact::getIDfromRefID($contact->pp_contact_id, $this->firm_id);
                               $bulk_contact[] = $contact->attributesToArray();
                           }
                           $success = PPTaskUser::insert($bulk_user);
                           $success = PPContactTask::insert($bulk_contact);
                           unset($bulk_data);
                           unset($bulk_user);
                           unset($bulk_contact);
                           unset($temp_user);
                           unset($temp_contact);
                           $bulk_data = [];
                           $bulk_user = [];
                           $bulk_contact = [];
                           $temp_user = [];
                           $temp_contact = [];
                           $counter = 0;
                       } else {
                           $counter = $counter + 1;
                       }
                    }
                    if ($bulk_data != null) {
                        $success = PPTask::insert($bulk_data);
                        foreach($temp_user as $user)
                        {
                            $user->pp_task_id = PPTask::getIDfromRefID($user->pp_task_id, $this->firm_id);
                            $user->pp_user_id = PPUser::getIDfromRefID($user->pp_user_id, $this->firm_id);
                            $bulk_user[] = $user->attributesToArray();
                        }
                        foreach($temp_contact as $contact)
                        {

                            $contact->pp_task_id = PPTask::getIDfromRefID($contact->pp_task_id, $this->firm_id);
                            $contact->pp_contact_id = PPContact::getIDfromRefID($contact->pp_contact_id, $this->firm_id);
                            $bulk_contact[] = $contact->attributesToArray();
                        }
                        $success = PPTaskUser::insert($bulk_user);
                        $success = PPContactTask::insert($bulk_contact);
                        unset($bulk_data);
                        unset($bulk_user);
                        unset($bulk_contact);
                        unset($temp_user);
                        unset($temp_contact);
                    }

                }

            }
        }
        return $success;
    }

    public function syncTimeEntries()
    {
        if ($this->debuging) {
            dump("Time Entries Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/timeentries')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPTimeEntry::where("firm_id", $this->firm_id)->delete();
                    $bulk_data = [];
                    $counter = 0;
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $date = new Carbon($a->date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row = new PPTimeEntry;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->billed_by_user_id = PPUser::getIDfromRefID(optional($a->billed_by_user_ref)->id, $this->firm_id);
                        $row->is_billable = $a->is_billable;
                        $row->is_billed = $a->is_billed;
                        $row->date = $a->date == null ? null : $date->format("Y-m-d H:i:s");
                        $row->hours = $a->hours;
                        $row->rate = $a->rate;
                        $row->description = $a->description;
                        $row->private_notes = $a->private_notes;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $bulk_data[] = $row->attributesToArray();
                        unset($row);
                        if ($counter == 500) {
                            $success = PPTimeEntry::insert($bulk_data);
                            unset($bulk_data);
                            $bulk_data = [];
                            $counter = 0;
                        } else {
                            $counter = $counter + 1;
                        }
                    }
                    if($bulk_data != null) {
                        $success = PPTimeEntry::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    }
                }
            }
        }
        return $success;
    }

    public function syncInvoices()
    {
        if ($this->debuging) {
            dump("Invoices Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/invoices')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPInvoice::where("firm_id", $this->firm_id)->delete();
                    PPInvoiceLineItem::where("firm_id", $this->firm_id)->delete();
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $issue_date = new Carbon($a->issue_date);
                        $due_date = new Carbon($a->due_date);
                        $row = new PPInvoice;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->issue_date = $a->issue_date == null ? null : $issue_date->format("Y-m-d H:i:s");
                        $row->due_date = $a->due_date == null ? null : $due_date->format("Y-m-d H:i:s");
                        $row->subtotal = $a->subtotal;
                        $row->tax = $a->tax;
                        $row->discount = $a->discount;
                        $row->total = $a->total;
                        $row->total_paid = $a->total_paid;
                        $row->total_outstanding = $a->total_outstanding;
                        $row->invoice_type = $a->invoice_type;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $success =  $row->save();
                        foreach ($a->items_time_entries as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "time_entries";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        foreach ($a->items_expenses as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "expenses";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        foreach ($a->items_flat_fees as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "flat_fees";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        unset($row);
                    }
                }


            }
        }
        return $success;
    }

    public function syncExpenses()
    {
        if ($this->debuging) {
            dump("Expenses Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/expenses')
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    PPExpense::where("firm_id", $this->firm_id)->delete();
                    $bulk_data = [];
                    $counter = 0;
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $date = new Carbon($a->date);
                        $row = new PPExpense;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->billed_by_user_id = PPUser::getIDfromRefID(optional($a->billed_by_user_ref)->id, $this->firm_id);
                        $row->category = optional($a->expense_category_ref)->name;
                        $row->is_billable = $a->is_billable;
                        $row->is_billed = $a->is_billed;
                        $row->date = $a->date == null ? null : $date->format("Y-m-d H:i:s");
                        $row->amount = $a->amount;
                        $row->description = $a->description;
                        $row->private_notes = $a->private_notes;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $bulk_data[] = $row->attributesToArray();
                        unset($row);
                        if ($counter == 500) {
                            $success = PPExpense::insert($bulk_data);
                            unset($bulk_data);
                            $bulk_data = [];
                            $counter = 0;
                        } else {
                            $counter = $counter + 1;
                        }
                    }
                    if($bulk_data !=null) {
                        $success = PPExpense::insert($bulk_data);
                        unset($bulk_data);
                    }
                }

            }
        }
        return $success;
    }

    //Sync Update Functions Down Below

    public function updateUsers()
    {
        if ($this->debuging) {
            dump("Users Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/users?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                dump("Processing\n");
                foreach($response as $a)
                {
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = PPUser::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new PPUser;
                        $row->hours_per_week = 40;
                        $row->date_of_joining = $a->created_at == null ? "1970-01-01" : $created_at->format("Y-m-d");
                        $row->can_be_calculated = true;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->is_active = $a->is_active;
                    $row->display_name = $a->display_name;
                    $row->first_name = $a->first_name;
                    $row->last_name = $a->last_name;
                    $row->middle_name = $a->middle_name;
                    $row->email = $a->email;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);
                }
            }
        }
        return $success;
    }

    public function updateAccounts()
    {
        if ($this->debuging) {
            dump("Accounts Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/accounts?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $row = PPAccount::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                        } else {
                            $row = new PPAccount;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->display_name = $a->display_name;
                        $row->company_name = $a->company_name;
                        $row->created_at = (new Carbon($a->created_at))->format("Y-m-d H:i:s");
                        $row->updated_at = (new Carbon($a->updated_at))->format("Y-m-d H:i:s");
                        $success = $row->save();
                        unset($row);
                    }
                }
            }
        }
        return $success;
    }

    public function updateContacts()
    {
        if ($this->debuging) {
            dump("Contacts Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/contacts?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $row = PPContact::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                        } else {
                            $row = new PPContact;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->account_display_name = optional($a->account_ref)->display_name;
                        $row->is_primary = $a->is_primary_contact;
                        $row->display_name = $a->display_name;
                        $row->first_name = $a->first_name;
                        $row->last_name = $a->last_name;
                        $row->phone_no = $a->phone_mobile;
                        $row->phone_home = $a->phone_home;
                        $row->phone_fax = $a->phone_fax;
                        $row->phone_work = $a->phone_work;
                        $row->email = $a->email;
                        $row->save();
                        unset($row);
                    }
                }
            }
        }
        return $success;
    }

    public function updateMatters()
    {
        if ($this->debuging) {
            dump("Matters Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/matters?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $open_date = new Carbon($a->open_date);
                        $close_date = new Carbon($a->close_date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row = PPMatter::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                            PPMatterUser::where("firm_id", $this->firm_id)->where("pp_matter_id",$row->id)->delete();
                        } else {
                            $row = new PPMatter;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->account_display_name = optional($a->account_ref)->display_name == null ? null : optional($a->account_ref)->display_name;
                        $row->number = preg_replace('/[,]+/', '-', trim($a->number));
                        $row->display_name = preg_replace('/[,]+/', '-', trim($a->display_name));
                        $row->name =  preg_replace('/[,]+/', '-', trim($a->name));
                        $row->open_date = $a->open_date == null ? null : $open_date->format("Y-m-d H:i:s");
                        $row->close_date = $a->close_date == null ? null : $close_date->format("Y-m-d H:i:s");
                        $row->status = $a->status;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        foreach($a->custom_field_values as $field)
                        {
                            if ($field->custom_field_ref->label == "Matter Type") {
                                $row->matter_type =  $field->value_string == null ? null : $field->value_string;
                            }
                        }
                        $row->matter_type = $row->matter_type == null ? null : $row->matter_type;
                        $success =  $row->save();
                        foreach($a->assigned_to_users as $user)
                        {
                            $pivot = new PPMatterUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->pp_matter_id = $row->id;
                            $pivot->pp_user_id = PPUser::getIDfromRefID($user->id, $this->firm_id);
                            $pivot->save();
                            unset($pivot);
                        }
                        unset($row);
                    }
                }

            }
        }
        return $success;
    }

    public function updateTasks()
    {
        if ($this->debuging) {
            dump("Tasks Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/tasks?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $due_date = new Carbon($a->due_date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row = PPTask::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                            PPTaskUser::where("firm_id", $this->firm_id)->where("pp_task_id",$row->id)->delete();
                            PPContactTask::where("firm_id", $this->firm_id)->where("pp_task_id",$row->id)->delete();
                        } else {
                            $row = new PPTask;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->subject= $a->subject;
                        //$row->notes = $a->notes;
                        $row->status = $a->status;
                        $row->due_date = $a->due_date == null ? null : $due_date->format("Y-m-d H:i:s");
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $success = $row->save();
                        foreach($a->assigned_to_users as $user)
                        {
                            $pivot = new PPTaskUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->pp_task_id = $row->id;
                            $pivot->pp_user_id = PPUser::getIDfromRefID($user->id, $this->firm_id);
                            $pivot->save();
                            unset($pivot);
                        }
                        foreach($a->assigned_to_contacts as $contact)
                        {
                            $pivot1 = new PPContactTask;
                            $pivot1->firm_id = $this->firm_id;
                            $pivot1->pp_task_id = $row->id;
                            $pivot1->pp_contact_id = PPContact::getIDfromRefID($contact->id, $this->firm_id);
                            $pivot1->save();
                            unset($pivot1);
                        }
                        unset($row);
                    }
                }

            }
        }
        return $success;
    }

    public function updateTimeEntries()
    {
        if ($this->debuging) {
            dump("Time Entries Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/timeentries?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $date = new Carbon($a->date);
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $row = PPTimeEntry::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                        } else {
                            $row = new PPTimeEntry;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->billed_by_user_id = PPUser::getIDfromRefID(optional($a->billed_by_user_ref)->id, $this->firm_id);
                        $row->is_billable = $a->is_billable;
                        $row->is_billed = $a->is_billed;
                        $row->date = $a->date == null ? null : $date->format("Y-m-d H:i:s");
                        $row->hours = $a->hours;
                        $row->rate = $a->rate;
                        $row->description = $a->description;
                        $row->private_notes = $a->private_notes;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $success =  $row->Save();
                        unset($row);
                    }
                }
            }
        }
        return $success;
    }

    public function updateInvoices()
    {
        if ($this->debuging) {
            dump("Invoices Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/invoices?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $issue_date = new Carbon($a->issue_date);
                        $due_date = new Carbon($a->due_date);
                        $row = PPInvoice::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                            PPInvoiceLineItem::where("firm_id", $this->firm_id)->where("pp_invoice_id",$row->id)->delete();
                        } else {
                            $row = new PPInvoice;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->issue_date = $a->issue_date == null ? null : $issue_date->format("Y-m-d H:i:s");
                        $row->due_date = $a->due_date == null ? null : $due_date->format("Y-m-d H:i:s");
                        $row->subtotal = $a->subtotal;
                        $row->tax = $a->tax;
                        $row->discount = $a->discount;
                        $row->total = $a->total;
                        $row->total_paid = $a->total_paid;
                        $row->total_outstanding = $a->total_outstanding;
                        $row->invoice_type = $a->invoice_type;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $success =  $row->save();
                        foreach ($a->items_time_entries as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "time_entries";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        foreach ($a->items_expenses as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "expenses";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        foreach ($a->items_flat_fees as $val) {
                            $entry_date = new Carbon($val->date);
                            $entry = new PPInvoiceLineItem;
                            $entry->firm_id = $this->firm_id;
                            $entry->pp_invoice_id = $row->id;
                            $entry->type = "flat_fees";
                            $entry->quantity = $val->quantity;
                            $entry->rate = $val->rate;
                            $entry->discount = $val->discount;
                            $entry->subtotal = $val->subtotal;
                            $entry->total = $val->total;
                            $entry->date = $val->date == null ? null : $entry_date->format("Y-m-d H:i:s");
                            $entry->billed_by = $val->billed_by;
                            $entry->item_name = $val->item_name;
                            $entry->item_description = $val->item_description;
                            $entry->save();
                            unset($entry);
                        }
                        unset($row);
                    }
                }
            }
        }
        return $success;
    }

    public function updateExpenses()
    {
        if ($this->debuging) {
            dump("Expenses Started");
        }
        $success = false;
        if (isset($this->integration->access_token)) {
            $response = Curl::to('https://app.practicepanther.com/api/v2/expenses?updated_since='.$this->update_date)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->get();
            $response = json_decode($response);
            if (empty($response->message) && is_array($response)) {
                if (!empty($response)) {
                    dump("Processing\n");
                    foreach($response as $a)
                    {
                        $created_at = new Carbon($a->created_at);
                        $updated_at = new Carbon($a->updated_at);
                        $date = new Carbon($a->date);
                        $row = PPExpense::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                        if ($row->count() == 1) {
                            $row = $row->first();
                        } else {
                            $row = new PPExpense;
                            $row->firm_id = $this->firm_id;
                            $row->ref_id = $a->id;
                        }
                        $row->pp_account_id = PPAccount::getIDfromRefID(optional($a->account_ref)->id, $this->firm_id);
                        $row->pp_matter_id = PPMatter::getIDfromRefID(optional($a->matter_ref)->id, $this->firm_id);
                        $row->billed_by_user_id = PPUser::getIDfromRefID(optional($a->billed_by_user_ref)->id, $this->firm_id);
                        $row->category = optional($a->expense_category_ref)->name;
                        $row->is_billable = $a->is_billable;
                        $row->is_billed = $a->is_billed;
                        $row->date = $a->date == null ? null : $date->format("Y-m-d H:i:s");
                        $row->amount = $a->amount;
                        $row->description = $a->description;
                        $row->private_notes = $a->private_notes;
                        $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                        $success = $row->save();
                        unset($row);
                    }
                }
            }
        }
        return $success;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries;

use App\CLInvoiceLineItem;
use App\CLTask;
use App\CLUser;
use App\CLMatter;
use App\CLContact;
use App\CLInvoice;
use App\CLCredit;
use App\Firm;
use App\IntegrationHistory;
use App\Setting;
use App\TempMatters;
use Carbon\Carbon;
use App\CLTimeEntry;
use App\CLMatterUser;
use App\CLPracticeArea;
use App\CLTaskAssignee;
use App\CLInvoiceMatter;
use App\CLMatterContact;
use App\FirmIntegration;
use App\CLMatterMatterType;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\DB;

class ClioAPILibrary
{
    private $integration = null;
    private $app = null;
    private $debuging = false;
    private $firm_id;
    private $update_date = null;
    private $location_url = null;

    public function __construct($firm_id, $debuging=false) {
        $this->firm_id = $firm_id;
        $this->debuging = $debuging;
        $this->loadFirmInfo();
        $this->location_url = Setting::where("key","location")->select("value")->first();

    }

    public function loadFirmInfo() {
        $this->integration = FirmIntegration::where("firm_id", $this->firm_id)->first();
        if($this->integration->last_sync == NULL) {
            $temp = Carbon::now();
        } else {
            $temp = Carbon::createFromFormat("Y-m-d H:i:s",$this->integration->last_sync);
        }
        $temp->subDays( 1);
        $temp1 = $temp->toAtomString();
        $this->update_date = substr($temp1,0,19);
        $this->app = HelperLibrary::getSettings(["cl_app_id", "cl_app_secret"]);
    }

    public function generateToken()
    {
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/oauth/token';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/oauth/token';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/oauth/token';
        } else {
            $url = 'https://app.clio.com/oauth/token';
        }
        $data = Curl::to($url)
            ->withData([
                "client_id" => $this->app->cl_app_id,
                "client_secret" => $this->app->cl_app_secret,
                "grant_type" => "authorization_code",
                "code" => $this->integration->code,
                "redirect_uri" => secure_url('/oauth/clio')."/"
            ])
            ->withContentType('application/x-www-form-urlencoded')
            ->post();
        $data = json_decode($data);
        $history = new IntegrationHistory;
        $history->firm_id = $this->firm_id;
        $history->code = $this->integration->code;
        $history->access_token = $this->integration->access_token;
        $history->refresh_token = $this->integration->refresh_token;
        $this->integration->access_token = $data->access_token;
        $this->integration->refresh_token = $data->refresh_token;
        $history->save();
        return $this->integration->save();

    }

    public function refreshToken()
    {
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/oauth/token';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/oauth/token';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/oauth/token';
        } else {
            $url = 'https://app.clio.com/oauth/token';
        }
        $response = Curl::to($url)
            ->withData([
                "grant_type"=>"refresh_token",
                "refresh_token"=> $this->integration->refresh_token,
                "client_id"=>$this->app->cl_app_id,
                "client_secret"=>$this->app->cl_app_secret
            ])
            ->post();
        $data = json_decode($response);
        $history = new IntegrationHistory;
        $history->firm_id = $this->firm_id;
        $history->code = $this->integration->code;
        $history->access_token = $this->integration->access_token;
        $history->refresh_token = $this->integration->refresh_token;
        $this->integration->access_token = $data->access_token;
        $history->save();
        return $this->integration->save();
    }

    public function syncUsers()
    {
        if ($this->debuging) {
            dump("Users Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at';
        } else {
            $url = 'https://app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at';
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach ($data->content['data'][0]['data'] as $a) {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = CLUser::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLUser;
                        $row->hours_per_week = 40;
                        $row->date_of_joining = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        if( $a->subscription_type == "Attorney" && $a->enabled == true) {
                            $row->can_be_calculated = true;
                        } else {
                            $row->can_be_calculated = false;
                        }
                        if($a->subscription_type == "Attorney") {
                            $row->type = "Attorney";
                        }
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->enabled = $a->enabled;
                    $row->name = $a->name;
                    $row->first_name = $a->first_name;
                    $row->last_name = $a->last_name;
                    $row->phone_number = $a->phone_number;
                    $row->email = $a->email;
                    $row->rate_per_hour = $a->rate;
                    $row->subscription_type = $a->subscription_type;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);

                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncContacts()
    {
        if ($this->debuging) {
            dump("Contacts Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number';
        } else {
            $url = 'https://app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number';
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLContact::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = new CLContact;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->name = optional($a)->name;
                    $row->first_name = optional($a)->first_name;
                    $row->last_name = optional($a)->last_name;
                    $row->middle_name = optional($a)->middle_name;
                    $row->type = optional($a)->type;
                    $row->prefix = optional($a)->prefix;
                    $row->title = optional($a)->title;
                    $row->initials = optional($a)->initials;
                    $row->primary_email_address = optional($a)->primary_email_address;
                    $row->primary_phone_no = optional($a)->primary_phone_number;
                    $row->is_client = optional($a)->is_client;
                    $row->company_id = optional($a)->company == null ? null : optional($a->company)->id;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if($counter == 500)
                    {
                        $success = CLContact::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null){
                    $success = CLContact::insert($bulk_data);
                    unset($bulk_data);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncMatters()
    {
        $success = false;
        $this->syncPracticeAreas();
        $this->refreshToken();
        $this->loadFirmInfo();
        if ($this->debuging) {
            dump("Matters Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } else {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLMatter::where("firm_id", $this->firm_id)->delete();
                CLMatterUser::where("firm_id", $this->firm_id)->delete();
                CLMatterContact::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $bulk_user = [];
                $bulk_contact = [];
                $temp_user = [];
                $temp_contact = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $open_date = new Carbon(optional($a)->open_date);
                    $close_date = new Carbon(optional($a)->close_date);
                    $pending_date = new Carbon(optional($a)->pending_date);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = new CLMatter;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->number = optional($a)->number;
                    $row->display_number = optional($a)->display_number;
                    $row->custom_number = optional($a)->custom_number;
                    $row->description = optional($a)->description;
                    $row->status = optional($a)->status;
                    $row->location = optional($a)->location;
                    $row->billable = optional($a)->billable;
                    $row->billing_method = optional($a)->billing_method;
                    $row->open_date = $a->open_date == null ? null : $open_date->format("Y-m-d");
                    $row->close_date = $a->close_date == null ? null : $close_date->format("Y-m-d");
                    $row->pending_date = $a->pending_date == null ? null : $pending_date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    if ($a->practice_area != null) {
                        $row->matter_type = CLPracticeArea::getNamefromRefID($a->practice_area['id'], $this->firm_id);
                    } else {
                        $row->matter_type = null;
                    }
                    if ($a->originating_attorney != null) {
                        $attorney = (object) $a->originating_attorney;
                        $row->clio_originating_attorney_id = CLUser::getIdfromRefID($attorney->id, $this->firm_id);
                    } else {
                        $row->clio_originating_attorney_id = null;
                    }
                    if ($a->custom_rate != null && $a->billing_method == 'flat') {
                        $custom = (object) $a->custom_rate;
                        foreach($custom->rates as $r) {
                            $r = (object) $r;
                            $ruser = (object) $r->user;
                            if($ruser != null) {
                                $row->clio_flat_rate_user_id = CLUser::getIdfromRefID($ruser->id, $this->firm_id);
                            }
                            $row->flat_rate = $r->rate;
                        }
                    } else {
                        $row->clio_flat_rate_user_id = null;
                        $row->flat_rate = null;
                    }
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if ($a->user != null) {
                        if (count($a->user) == count($a->user, COUNT_RECURSIVE)) {
                            $a->user = (object)$a->user;
                            $pivot = new CLMatterUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->clio_matter_id = $a->id;
                            $pivot->clio_user_id = $a->user->id;
                            $temp_user[] = $pivot;
                            unset($pivot);
                        } else {
                            foreach ($a->user as $user) {
                                $user = (object)$user;
                                $pivot = new CLMatterUser;
                                $pivot->firm_id = $this->firm_id;
                                $pivot->clio_matter_id = $a->id;
                                $pivot->clio_user_id = $user->id;
                                $temp_user[] = $pivot;
                                unset($pivot);
                            }
                        }

                    }
                    if ($a->client != null) {
                        if (count($a->client) == count($a->client, COUNT_RECURSIVE)) {
                            $a->client = (object)$a->client;
                            $pivot = new CLMatterContact;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->clio_matter_id = $a->id;
                            $pivot->clio_contact_id = $a->client->id;
                            $temp_contact[] = $pivot;
                            unset($pivot);
                        } else {
                            foreach ($a->client as $client) {
                                $client = (object)$client;
                                $pivot = new CLMatterContact;
                                $pivot->firm_id = $this->firm_id;
                                $pivot->clio_matter_id = $a->id;
                                $pivot->clio_contact_id = $client->id;
                                $temp_contact[] = $pivot;
                                unset($pivot);
                            }
                        }

                    }

                    if($counter == 500)
                    {
                        $success = CLMatter::insert($bulk_data);
                        foreach ($temp_user as $user) {
                            $user->clio_matter_id = CLMatter::getIDfromRefID($user->clio_matter_id, $this->firm_id);
                            $user->clio_user_id = CLUser::getIDfromRefID($user->clio_user_id, $this->firm_id);
                            $bulk_user[] = $user->attributesToArray();
                        }
                        foreach ($temp_contact as $client) {
                            $client->clio_matter_id = CLMatter::getIDfromRefID($client->clio_matter_id, $this->firm_id);
                            $client->clio_contact_id = CLContact::getIDfromRefID($client->clio_contact_id, $this->firm_id);
                            $bulk_contact[] = $client->attributesToArray();
                        }
                        $success = CLMatterUser::insert($bulk_user);
                        $success = CLMatterContact::insert($bulk_contact);
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
                if($bulk_data != null){
                    $success = CLMatter::insert($bulk_data);
                    foreach ($temp_user as $user) {
                        $user->clio_matter_id = CLMatter::getIDfromRefID($user->clio_matter_id, $this->firm_id);
                        $user->clio_user_id = CLUser::getIDfromRefID($user->clio_user_id, $this->firm_id);
                        $bulk_user[] = $user->attributesToArray();
                    }
                    foreach ($temp_contact as $client) {
                        $client->clio_matter_id = CLMatter::getIDfromRefID($client->clio_matter_id, $this->firm_id);
                        $client->clio_contact_id = CLContact::getIDfromRefID($client->clio_contact_id, $this->firm_id);
                        $bulk_contact[] = $client->attributesToArray();
                    }
                    $success = CLMatterUser::insert($bulk_user);
                    $success = CLMatterContact::insert($bulk_contact);
                    unset($bulk_data);
                    unset($bulk_user);
                    unset($bulk_contact);
                    unset($temp_user);
                    unset($temp_contact);

                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncTasks()
    {
        if ($this->debuging) {
            dump("Tasks Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter';
        } else {
            $url = 'https://app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLTask::where("firm_id", $this->firm_id)->delete();
                CLTaskAssignee::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $bulk_asignee = [];
                $temp_asignee = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $row = new CLTask;
                    $due_at = new Carbon(optional($a)->due_at);
                    $completed_at = new Carbon(optional($a)->completed_at);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->name = optional($a)->name;
                    $row->status = optional($a)->status;
                    $row->description = optional($a)->description;
                    $row->priority = optional($a)->priority;
                    $row->statute_of_limitation = optional($a)->statute_of_limitation;
                    $row->task_type = optional($a)->task_type == null ? null : optional($a->task_type)->name;
                    if($a->matter != null)
                    {
                        $row->clio_matter_id = CLMatter::getIDfromRefID($a->matter['id'], $this->firm_id);
                    } else {
                        $row->clio_matter_id = 0;
                    }
                    if ($a->assigner != null) {
                        $row->assigner = CLUser::getIDfromRefID( $a->assigner['id'], $this->firm_id);
                    } else {
                        $row->assigner = 0;
                    }
                    $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
                    $row->completed_at = $a->completed_at == null ? null : $completed_at->format("Y-m-d H:i:s");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if ($a->assignee != null || $a->assignee != "") {
                        $a->assignee = (object) $a->assignee;
                        $pivot = new CLTaskAssignee;
                        $pivot->firm_id = $this->firm_id;
                        $pivot->clio_task_id = $a->id;
                        $pivot->ref_id = optional($a->assignee)->id;
                        $pivot->type = optional($a->assignee)->type;
                        $pivot->identifier = optional($a->assignee)->identifier;
                        $pivot->name = optional($a->assignee)->name;
                        $pivot->enabled = optional($a->assignee)->enabled;
                        $temp_asignee[] = $pivot;
                        unset($pivot);
                    }
                    if($counter == 500)
                    {
                        $success = CLTask::insert($bulk_data);
                        foreach($temp_asignee as $t)
                        {
                            $t->clio_task_id = CLTask::getIDfromRefID($t->clio_task_id,$this->firm_id);
                            $bulk_asignee[] = $t->attributesToArray();
                        }
                        $success = CLTaskAssignee::insert($bulk_asignee);
                        unset($bulk_data);
                        unset($bulk_asignee);
                        unset($temp_asignee);
                        $bulk_data = [];
                        $bulk_asignee = [];
                        $temp_asignee = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = CLTask::insert($bulk_data);
                    foreach($temp_asignee as $t)
                    {
                        $t->clio_task_id = CLTask::getIDfromRefID($t->clio_task_id,$this->firm_id);
                        $bulk_asignee[] = $t->attributesToArray();
                    }
                    $success = CLTaskAssignee::insert($bulk_asignee);
                    unset($bulk_data);
                    unset($bulk_asignee);
                    unset($temp_asignee);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncTimeEntries()
    {
        if ($this->debuging) {
            dump("TimeEntries Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter';
        } else {
            $url = 'https://app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {

                CLTimeEntry::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $date = new Carbon(optional($a)->date);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = new CLTimeEntry();
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->type = optional($a)->type;
                    $row->quantity_in_hours = optional($a)->quantity_in_hours;
                    $row->quantity = optional($a)->quantity;
                    $row->price = optional($a)->price;
                    $row->note = optional($a)->note;
                    $row->flat_rate = optional($a)->flat_rate;
                    $row->billed = optional($a)->billed;
                    $row->on_bill = optional($a)->on_bill;
                    $row->total = optional($a)->total;
                    $row->contingency_fee = optional($a)->contingency_fee;
                    if($a->matter != null){
                        $row->clio_matter_id = CLMatter::getIDfromRefID($a->matter['id'], $this->firm_id);
                    } else {
                        $row->clio_matter_id = 0;
                    }
                    if($a->user != null){
                        $row->clio_user_id = CLUser::getIDfromRefID($a->user['id'], $this->firm_id);
                    } else {
                        $row->clio_user_id = 0;
                    }
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if($counter == 500)
                    {
                        $success = CLTimeEntry::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = CLTimeEntry::insert($bulk_data);
                    unset($bulk_data);
                }

                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncInvoices()
    {
        if ($this->debuging) {
            dump("invoices Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client';
        } else {
            $url = 'https://app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLInvoice::where("firm_id", $this->firm_id)->delete();
                CLInvoiceMatter::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $bulk_matter = [];
                $temp_matter = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $issued_at = new Carbon(optional($a)->issued_at);
                    $due_at = new Carbon(optional($a)->due_at);
                    $start_at = new Carbon(optional($a)->start_at);
                    $end_at = new Carbon(optional($a)->end_at);
                    $row = new CLInvoice;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->number = optional($a)->number;
                    $row->subject = optional($a)->subject;
                    $row->purchase_order = optional($a)->purchase_order;
                    $row->type = optional($a)->type;
                    $row->balance = optional($a)->balance;
                    $row->config = null;
                    $row->state = optional($a)->state;
                    $row->kind = optional($a)->kind;
                    $row->total = optional($a)->total;
                    $row->paid = optional($a)->paid;
                    $row->pending = optional($a)->pending;
                    $row->due = optional($a)->due;
                    $row->sub_total = optional($a)->sub_total;
                    if($a->user != null) {
                        $row->clio_user_id = CLUser::getIDfromRefID($a->user['id'], $this->firm_id);
                    } else {
                        $row->clio_user_id = 0;
                    }
                    if ($a->client != null) {
                        $row->clio_contact_id = CLContact::getIDfromRefID($a->client['id'], $this->firm_id);
                    } else {
                        $row->clio_contact_id = 0;
                    }
                    $row->issued_at = $a->issued_at == null ? null : $issued_at->format("Y-m-d");
                    $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
                    $row->start_at = $a->start_at == null ? null : $start_at->format("Y-m-d");
                    $row->end_at = $a->start_at == null ? null : $end_at->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    foreach($a->matters as $matter)
                    {
                        $matter = (object) $matter;
                        $pivot = new CLInvoiceMatter;
                        $pivot->firm_id = $this->firm_id;
                        $pivot->clio_invoice_id = $a->id;
                        $pivot->clio_matter_id = $matter->id;
                        $temp_matter[] = $pivot;
                        unset($pivot);
                    }
                    if($counter == 500)
                    {
                        $success = CLInvoice::insert($bulk_data);
                        foreach($temp_matter as $m)
                        {
                            $m->clio_invoice_id = CLInvoice::getIDfromRefID($m->clio_invoice_id, $this->firm_id);
                            $m->clio_matter_id = CLMatter::getIDfromRefID($m->clio_matter_id, $this->firm_id);
                            $bulk_matter[] = $m->attributesToArray();
                        }
                        $success = CLInvoiceMatter::insert($bulk_matter);
                        unset($bulk_data);
                        unset($bulk_matter);
                        unset($temp_matter);
                        $bulk_data = [];
                        $bulk_matter = [];
                        $temp_matter = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = CLInvoice::insert($bulk_data);
                    foreach($temp_matter as $m)
                    {
                        $m->clio_invoice_id = CLInvoice::getIDfromRefID($m->clio_invoice_id, $this->firm_id);
                        $m->clio_matter_id = CLMatter::getIDfromRefID($m->clio_matter_id, $this->firm_id);
                        $bulk_matter[] = $m->attributesToArray();
                    }
                    $success = CLInvoiceMatter::insert($bulk_matter);
                    unset($bulk_data);
                    unset($bulk_matter);
                    unset($temp_matter);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncInvoiceLineItems()
    {
        if ($this->debuging) {
            dump("Invoice Line Items Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } else {
            $url = 'https://app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLInvoiceLineItem::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $date = new Carbon(optional($a)->date);
                    $row = new CLInvoiceLineItem;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->type = optional($a)->type;
                    $row->description = optional($a)->description;
                    $row->kind = optional($a)->kind;
                    $row->note = optional($a)->note;
                    $row->total = optional($a)->total;
                    $row->price = optional($a)->price;
                    $row->quantity = optional($a)->quantity;
                    $row->sub_total = optional($a)->sub_total;
                    if($a->user != null) {
                        $row->clio_user_id = $a->user['id'];
                    } else {
                        $row->clio_user_id = 0;
                    }
                    if ($a->matter != null) {
                        $row->clio_matter_id = $a->matter['id'];
                    } else {
                        $row->clio_matter_id = 0;
                    }
                    if ($a->bill != null) {
                        $row->clio_invoice_id = $a->bill['id'];
                    } else {
                        $row->clio_invoice_id = 0;
                    }
                    if ($a->activity != null) {
                        $row->clio_time_entry_id = $a->activity['id'];
                    } else {
                        $row->clio_time_entry_id  = 0;
                    }
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if($counter == 500)
                    {
                        $success = CLInvoiceLineItem::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = CLInvoiceLineItem::insert($bulk_data);
                    unset($bulk_data);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncPracticeAreas()
    {
        if ($this->debuging) {
            dump("Practice Areas Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at';
        } else {
            $url = 'https://app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        CLPracticeArea::where("firm_id", $this->firm_id)->delete();
        do
        {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $content = json_decode($response->content);
            $bulk_data = [];
            dump("Processing\n");
            foreach($content->data as $a)
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
                $bulk_data[] = $row->attributesToArray();
                unset($row);
            }
            $success = CLPracticeArea::insert($bulk_data);
            if ($response->headers['X-RateLimit-Remaining'] <= 1) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            $next = null;
            if ($content->meta->paging != null || $content->meta->paging != "") {
                $next = optional($content->meta->paging)->next;
            }
            $response = Curl::to($next)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->get();
        } while ($next != null);
        unset($response);
        return $success;
    }
    public function syncCredits()
    {
        if ($this->debuging) {
            dump("Credits Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at';
        } else {
            $url = 'https://app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLCredit::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $date = new Carbon($a->date);
                    $voided_at = new Carbon($a->voided_at);
                    $row = new CLCredit;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->amount = optional($a)->amount;
                    $row->description = optional($a)->description;
                    $row->discount = optional($a)->discount;
                    $row->voided_at = $a->voided_at == null ? null : $voided_at->format("Y-m-d H:i:s");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);
                    if($counter == 500)
                    {
                        $success = CLCredit::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null ){
                    $success = CLCredit::insert($bulk_data);
                    unset($bulk_data);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    //Sync Update Functions Down Below

    public function updateUsers()
    {
        if ($this->debuging) {
            dump("Users Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/users?fields=id,etag,enabled,name,first_name,last_name,phone_number,email,rate,subscription_type,created_at,updated_at&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach ($data->content['data'][0]['data'] as $a) {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = CLUser::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLUser;
                        $row->hours_per_week = 40;
                        $row->date_of_joining = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                        $row->can_be_calculated = true;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->enabled = $a->enabled;
                    $row->name = $a->name;
                    $row->first_name = $a->first_name;
                    $row->last_name = $a->last_name;
                    $row->phone_number = $a->phone_number;
                    $row->email = $a->email;
                    $row->rate_per_hour = $a->rate;
                    $row->subscription_type = $a->subscription_type;
                    $row->can_be_calculated = $a->subscription_type == "Attorney" ? true : false;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function updateContacts()
    {
        if ($this->debuging) {
            dump("Contacts Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/contacts?fields=id,etag,name,first_name,last_name,middle_name,type,prefix,title,initials,is_client,company,created_at,updated_at,primary_email_address,primary_phone_number&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = CLContact::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLContact;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->name = optional($a)->name;
                    $row->first_name = optional($a)->first_name;
                    $row->last_name = optional($a)->last_name;
                    $row->middle_name = optional($a)->middle_name;
                    $row->type = optional($a)->type;
                    $row->prefix = optional($a)->prefix;
                    $row->title = optional($a)->title;
                    $row->initials = optional($a)->initials;
                    $row->primary_email_address = optional($a)->primary_email_address;
                    $row->primary_phone_no = optional($a)->primary_phone_number;
                    $row->is_client = optional($a)->is_client;
                    $row->company_id = optional($a)->company == null ? null : optional($a->company)->id;
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function updateMatters()
    {
        $success = false;
        $this->syncPracticeAreas();
        $this->refreshToken();
        $this->loadFirmInfo();
        if ($this->debuging) {
            dump("Matters Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $open_date = new Carbon(optional($a)->open_date);
                    $close_date = new Carbon(optional($a)->close_date);
                    $pending_date = new Carbon(optional($a)->pending_date);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = CLMatter::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                        CLMatterUser::where("firm_id", $this->firm_id)->where("clio_matter_id", $row->id)->delete();
                        CLMatterContact::where("firm_id", $this->firm_id)->where("clio_matter_id", $row->id)->delete();
                    } else {
                        $row = new CLMatter;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->number = optional($a)->number;
                    $row->display_number = optional($a)->display_number;
                    $row->custom_number = optional($a)->custom_number;
                    $row->description = optional($a)->description;
                    $row->status = optional($a)->status;
                    $row->location = optional($a)->location;
                    $row->billable = optional($a)->billable;
                    $row->billing_method = optional($a)->billing_method;
                    $row->open_date = $a->open_date == null ? null : $open_date->format("Y-m-d");
                    $row->close_date = $a->close_date == null ? null : $close_date->format("Y-m-d");
                    $row->pending_date = $a->pending_date == null ? null : $pending_date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    if ($a->practice_area != null) {
                        $row->matter_type = CLPracticeArea::getNamefromRefID($a->practice_area['id'], $this->firm_id);
                    } else {
                        $row->matter_type = null;
                    }
                    if ($a->originating_attorney != null) {
                        $originating_attorney = (object) $a->originating_attorney;
                        $row->clio_originating_attorney_id = CLUser::getIdfromRefID($originating_attorney->id, $this->firm_id);
                    } else {
                        $row->clio_originating_attorney_id = null;
                    }
                    if ($a->custom_rate != null && $a->billing_method == 'flat') {
                        $custom = (object) $a->custom_rate;
                        foreach($custom->rates as $r) {
                            $r = (object) $r;
                            $ruser = (object) $r->user;
                            if($ruser != null) {
                                $row->clio_flat_rate_user_id = CLUser::getIdfromRefID($ruser->id, $this->firm_id);
                            }
                            $row->flat_rate = $r->rate;
                        }
                    } else {
                        $row->clio_flat_rate_user_id = null;
                        $row->flat_rate = null;
                    }
                    $success = $row->save();
                    if ($a->user != null) {
                        if (count($a->user) == count($a->user, COUNT_RECURSIVE)) {
                            $a->user = (object)$a->user;
                            $pivot = new CLMatterUser;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->clio_matter_id = $row->id;
                            $pivot->clio_user_id = CLUser::getIDfromRefID($a->user->id, $this->firm_id);
                            $pivot->save();
                            unset($pivot);
                        } else {
                            foreach ($a->user as $user) {
                                $user = (object)$user;
                                $pivot = new CLMatterUser;
                                $pivot->firm_id = $this->firm_id;
                                $pivot->clio_matter_id = $row->id;
                                $pivot->clio_user_id = CLUser::getIDfromRefID($user->id, $this->firm_id);
                                $pivot->save();
                                unset($pivot);
                            }
                        }
                    }
                    if ($a->client != null) {
                        if (count($a->client) == count($a->client, COUNT_RECURSIVE)) {
                            $a->client = (object)$a->client;
                            $pivot = new CLMatterContact;
                            $pivot->firm_id = $this->firm_id;
                            $pivot->clio_matter_id = $row->id;
                            $pivot->clio_contact_id = CLContact::getIDfromRefID($a->client->id, $this->firm_id);
                            $pivot->save();
                            unset($pivot);
                        } else {
                            foreach ($a->client as $client) {
                                $client = (object)$client;
                                $pivot = new CLMatterContact;
                                $pivot->firm_id = $this->firm_id;
                                $pivot->clio_matter_id = $row->id;
                                $pivot->clio_contact_id = CLContact::getIDfromRefID($client->id, $this->firm_id);
                                $pivot->save();
                                unset($pivot);
                            }
                        }
                    }
                    unset($row);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function updateTasks()
    {
        if ($this->debuging) {
            dump("Tasks Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/tasks?fields=due_at,completed_at,created_at,updated_at,id,etag,name,status,description,priority,statute_of_limitations,assignee,assigner,task_type,matter&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $row = CLTask::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                        CLTaskAssignee::where("firm_id", $this->firm_id)->where("clio_task_id", $row->id)->delete();
                    } else {
                        $row = new CLTask;
                    }
                    $due_at = new Carbon(optional($a)->due_at);
                    $completed_at = new Carbon(optional($a)->completed_at);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->name = optional($a)->name;
                    $row->status = optional($a)->status;
                    $row->description = optional($a)->description;
                    $row->priority = optional($a)->priority;
                    $row->statute_of_limitation = optional($a)->statute_of_limitation;
                    $row->task_type = optional($a)->task_type == null ? null : optional($a->task_type)->name;
                    $row->clio_matter_id = CLMatter::getIDfromRefID(($a->matter != null ? $a->matter['id'] : null), $this->firm_id);
                    $row->assigner = CLUser::getIDfromRefID(($a->assigner != null ? $a->assigner['id'] : null), $this->firm_id);
                    $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
                    $row->completed_at = $a->completed_at == null ? null : $completed_at->format("Y-m-d H:i:s");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    if ($a->assignee != null || $a->assignee != "") {
                        $a->assignee = (object) $a->assignee;
                        $pivot = new CLTaskAssignee;
                        $pivot->firm_id = $this->firm_id;
                        $pivot->clio_task_id =$row->id;
                        $pivot->ref_id = optional($a->assignee)->id;
                        $pivot->type = optional($a->assignee)->type;
                        $pivot->identifier = optional($a->assignee)->identifier;
                        $pivot->name = optional($a->assignee)->name;
                        $pivot->enabled = optional($a->assignee)->enabled;
                        $pivot->save();
                        unset($pivot);
                    }
                    unset($row);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function updateTimeEntries()
    {
        if ($this->debuging) {
            dump("TimeEntries Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/activities?fields=id,etag,type,date,quantity_in_hours,quantity,user,price,note,flat_rate,billed,on_bill,total,contingency_fee,created_at,updated_at,matter&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $date = new Carbon(optional($a)->date);
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $row = CLTimeEntry::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLTimeEntry;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->type = optional($a)->type;
                    $row->quantity_in_hours = optional($a)->quantity_in_hours;
                    $row->quantity = optional($a)->quantity;
                    $row->price = optional($a)->price;
                    $row->note = optional($a)->note;
                    $row->flat_rate = optional($a)->flat_rate;
                    $row->billed = optional($a)->billed;
                    $row->on_bill = optional($a)->on_bill;
                    $row->total = optional($a)->total;
                    $row->contingency_fee = optional($a)->contingency_fee;
                    $row->clio_matter_id = CLMatter::getIDfromRefID(($a->matter != null ? $a->matter['id'] : null), $this->firm_id);
                    $row->clio_user_id = CLUser::getIDfromRefID(($a->user != null ? $a->user['id'] : null), $this->firm_id);
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function updateInvoices()
    {
        if ($this->debuging) {
            dump("invoices Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/bills?fields=created_at,updated_at,issued_at,due_at,start_at,end_at,id,etag,number,subject,purchase_order,type,balance,state,config,kind,total,paid,pending,due,sub_total,matters,user,client&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $issued_at = new Carbon(optional($a)->issued_at);
                    $due_at = new Carbon(optional($a)->due_at);
                    $start_at = new Carbon(optional($a)->start_at);
                    $end_at = new Carbon(optional($a)->end_at);
                    $row = CLInvoice::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                        CLInvoiceMatter::where("firm_id", $this->firm_id)->where("clio_invoice_id",$row->id)->delete();
                    } else {
                        $row = new CLInvoice;
                    }
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    $row->etag = $a->etag;
                    $row->number = optional($a)->number;
                    $row->subject = optional($a)->subject;
                    $row->purchase_order = optional($a)->purchase_order;
                    $row->type = optional($a)->type;
                    $row->balance = optional($a)->balance;
                    $row->config = null;
                    $row->state = optional($a)->state;
                    $row->kind = optional($a)->kind;
                    $row->total = optional($a)->total;
                    $row->paid = optional($a)->paid;
                    $row->pending = optional($a)->pending;
                    $row->due = optional($a)->due;
                    $row->sub_total = optional($a)->sub_total;
                    $row->clio_user_id = CLUser::getIDfromRefID(($a->user != null ? $a->user['id'] : null), $this->firm_id);
                    $row->clio_contact_id = CLContact::getIDfromRefID(($a->client != null ? $a->client['id'] : null), $this->firm_id);
                    $row->issued_at = $a->issued_at == null ? null : $issued_at->format("Y-m-d");
                    $row->due_at = $a->due_at == null ? null : $due_at->format("Y-m-d H:i:s");
                    $row->start_at = $a->start_at == null ? null : $start_at->format("Y-m-d");
                    $row->end_at = $a->start_at == null ? null : $end_at->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    foreach($a->matters as $matter)
                    {
                        $matter = (object) $matter;
                        $pivot = new CLInvoiceMatter;
                        $pivot->firm_id = $this->firm_id;
                        $pivot->clio_invoice_id = $row->id;
                        $pivot->clio_matter_id = CLMatter::getIDfromRefID($matter->id, $this->firm_id);
                        $pivot->save();
                        unset($pivot);
                    }
                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }
    public function updateInvoiceLineItems()
    {
        if ($this->debuging) {
            dump("Invoice Line Items Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        } else {
            $url = 'https://app.clio.com/api/v4/line_items?fields=created_at,updated_at,date,id,etag,type,description,kind,note,total,price,sub_total,quantity,matter,user,activity,bill';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                CLInvoiceLineItem::where("firm_id", $this->firm_id)->delete();
                $bulk_data = [];
                $counter = 0;
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $date = new Carbon(optional($a)->date);
                    $row = CLInvoiceLineItem::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLInvoiceLineItem;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                    }
                    $row->etag = $a->etag;
                    $row->type = optional($a)->type;
                    $row->description = optional($a)->description;
                    $row->kind = optional($a)->kind;
                    $row->note = optional($a)->note;
                    $row->total = optional($a)->total;
                    $row->price = optional($a)->price;
                    $row->quantity = optional($a)->quantity;
                    $row->sub_total = optional($a)->sub_total;
                    if($a->user != null) {
                        $row->clio_user_id = $a->user['id'];
                    } else {
                        $row->clio_user_id = 0;
                    }
                    if ($a->matter != null) {
                        $row->clio_matter_id = $a->matter['id'];
                    } else {
                        $row->clio_matter_id = 0;
                    }
                    if ($a->bill != null) {
                        $row->clio_invoice_id = $a->bill['id'];
                    } else {
                        $row->clio_invoice_id = 0;
                    }
                    if ($a->activity != null) {
                        $row->clio_time_entry_id = $a->activity['id'];
                    } else {
                        $row->clio_time_entry_id  = 0;
                    }
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $success = $row->save();
                    unset($row);
                }
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }
    public function updatePracticeAreas()
    {
        if ($this->debuging) {
            dump("Practice Areas Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/practice_areas?fields=id,etag,name,code,created_at,updated_at&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        do
        {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $content = json_decode($response->content);
            dump("Processing\n");
            foreach($content->data as $a)
            {
                $created_at = new Carbon($a->created_at);
                $updated_at = new Carbon($a->updated_at);
                $row = CLPracticeArea::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                if ($row->count() == 1) {
                    $row = $row->first();
                } else {
                    $row = new CLPracticeArea;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                }
                $row->etag = $a->etag;
                $row->name = optional($a)->name;
                $row->code = optional($a)->code;
                $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                $success = $row->save();
                unset($row);
            }
            if ($response->headers['X-RateLimit-Remaining'] <= 1) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            $next = null;
            if ($content->meta->paging != null || $content->meta->paging != "") {
                $next = optional($content->meta->paging)->next;
            }
            $response = Curl::to($next)
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->get();
        } while ($next != null);
        unset($response);
        return $success;
    }
    public function updateCredits()
    {
        if ($this->debuging) {
            dump("Credits Started\n");
        }
        $this->refreshToken();
        $this->loadFirmInfo();
        $success = false;
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at&updated_since='.$this->update_date;
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at&updated_since='.$this->update_date;
        } else {
            $url = 'https://app.clio.com/api/v4/credit_memos?fields=id,etag,date,amount,description,discount,voided_at,user,contact,created_at,updated_at&updated_since='.$this->update_date;
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $created_at = new Carbon($a->created_at);
                    $updated_at = new Carbon($a->updated_at);
                    $date = new Carbon($a->date);
                    $voided_at = new Carbon($a->voided_at);
                    $row = CLCredit::where("firm_id", $this->firm_id)->where("ref_id", $a->id);
                    if ($row->count() == 1) {
                        $row = $row->first();
                    } else {
                        $row = new CLCredit;
                        $row->firm_id = $this->firm_id;
                        $row->ref_id = $a->id;
                    }
                    $row->etag = $a->etag;
                    $row->date = $a->date == null ? null : $date->format("Y-m-d");
                    $row->amount = optional($a)->amount;
                    $row->description = optional($a)->description;
                    $row->discount = optional($a)->discount;
                    $row->voided_at = $a->voided_at == null ? null : $voided_at->format("Y-m-d H:i:s");
                    $row->created_at = $a->created_at == null ? null : $created_at->format("Y-m-d H:i:s");
                    $row->updated_at = $a->updated_at == null ? null : $updated_at->format("Y-m-d H:i:s");
                    $row->Save();
                    unset($row);

                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        return $success;
    }

    public function syncMattersPracticeAres()
    {
        $success = false;
        $this->refreshToken();
        $this->loadFirmInfo();
        if ($this->debuging) {
            dump("Matters Started\n");
        }
        if($this->location_url->value == "us") {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } elseif ($this->location_url->value == "ca") {
            $url = 'https://ca.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } elseif ($this->location_url->value == "eu") {
            $url = 'https://eu.app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        } else {
            $url = 'https://app.clio.com/api/v4/matters?fields=open_date,close_date,pending_date,created_at,updated_at,id,etag,number,display_number,custom_number,description,status,location,practice_area,user,client,originating_attorney,custom_rate,billable,billing_method';
        }
        $response = Curl::to($url)
            ->withHeader("Authorization: BEARER {$this->integration->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        do {
            if(isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError" ||isset($response->content["error"]) and $response->content["error"]["type"]=="UnauthorizedError")
            {
                $this->setStatus();
                break;
            }
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->integration->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress") || (isset($data->content['data']['status']) && $data->content['data']['status'] == "queued")) {
                $run = true;
            } else {
                $bulk_data = [];
                $counter = 0;
                TempMatters::where('firm_id',$this->firm_id)->delete();
                dump("Processing\n");
                foreach($data->content['data'][0]['data'] as $a)
                {
                    $a = (object) $a;
                    $row = new TempMatters;
                    $row->firm_id = $this->firm_id;
                    $row->ref_id = $a->id;
                    if ($a->practice_area != null) {
                        $row->matter_type = CLPracticeArea::getNamefromRefID($a->practice_area['id'], $this->firm_id);
                    } else {
                        $row->matter_type = null;
                    }
                    $bulk_data[] = $row->attributesToArray();
                    unset($row);

                    if($counter == 5000)
                    {
                        $success = TempMatters::insert($bulk_data);
                        unset($bulk_data);
                        $bulk_data = [];
                        $counter = 0;
                    } else {
                        $counter = $counter + 1;
                    }
                }
                if($bulk_data != null){
                    $success = TempMatters::insert($bulk_data);
                    unset($bulk_data);

                }
                break;
            }
            sleep(2);
        } while ($run);
        unset($run);
        unset($response);
        $d = TempMatters::where('firm_id',$this->firm_id)->get();
        foreach($d as $c=>$a) {
            $matter = CLMatter::where('firm_id',$this->firm_id)->where('ref_id',$a->ref_id)->first();
            if($matter != null and $a->matter_type != null) {
                $matter->matter_type = $a->matter_type;
                $matter->Save();
            }
            unset($matter);
        }
        $firm = Firm::where('id',$this->firm_id)->first();
        $sum = new SummaryLibrary($firm);
        $sum->allAOP();
        unset($sum);

        return $success;
    }
    public function setStatus()
    {
         FirmIntegration::where("firm_id",$this->firm_id)->update(["status"=>"Re-Authorize"]);
    }
}
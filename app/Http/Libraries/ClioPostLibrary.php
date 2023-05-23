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
use App\CLMatter;
use App\CLContact;
use App\CLInvoice;
use Carbon\Carbon;
use App\CLTimeEntry;
use App\CLMatterUser;
use App\CLPracticeArea;
use App\CLTaskAssignee;
use App\CLInvoiceMatter;
use App\CLMatterContact;
use App\FirmIntegration;
use App\CLMatterMatterType;
use http\Env\Response;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Object_;
use function MongoDB\BSON\toJSON;

class ClioPostLibrary
{
  
    private $firm_id;
    private $integration = null;
    private $app = null;
    private $access_token ="";

    public function __construct($firm_id) {
        $this->firm_id = $firm_id;
        $this->loadFirmInfo();
    }
    public function loadFirmInfo() {
        $this->integration = FirmIntegration::where("firm_id", $this->firm_id)->first();
        $this->app = HelperLibrary::getSettings(["cl_app_id", "cl_app_secret"]);
    }

    public function generateToken()
    {
        $data = Curl::to('https://app.clio.com/oauth/token')
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
        return $this->integration->save();

    }

    public function refreshToken()
    {
        $response = Curl::to('https://app.clio.com/oauth/token')
            ->withData([
                "grant_type"=>"refresh_token",
                "refresh_token"=> $this->integration->refresh_token,
                "client_id"=>$this->app->cl_app_id,
                "client_secret"=>$this->app->cl_app_secret
            ])
            ->post();
        $data = json_decode($response);
        $this->access_token = $data->access_token;
//        echo $this->access_token;
    }

 

    
    public function syncUsers()
    {
        $this->refreshToken();
        $data = CLUser::where("firm_id",$this->firm_id)->get();
        foreach ($data as $c=>$a) {
           $row = new CLUser;
           $row->enabled = $a->enabled;
           $row->name = "DummyName".$c;//$a->name;
           $row->first_name = "DummyFirstName".$c;//$a->first_name;
           $row->last_name = "DummyLastName".$c;//$a->last_name;
           $row->phone_number = "DummyPhoneNumber".$c;//$a->phone_number;
           $row->email = "DummyEmail".$c;//$a->email;
           $row->type = $a->type;
           $row->subscription_type = $a->subscription_type;
           $row->created_at = $a->created_at ;
           $row->updated_at = $a->updated_at ;
           $row = json_encode($row);
            $response = Curl::to('https://app.clio.com/api/v4/users')
                ->withHeader("Authorization: BEARER {$this->access_token}")
                ->returnResponseObject()
                ->withContentType('application/json')
                ->post();
            unset($row);
            echo $response->status;
        }
        return true;
    }

    public function syncContacts()
    {
        $this->refreshToken();
        $values = [];
        $email = null;
        $temp_email = [];
        $temp_phone_no = [];
        $array =null;
        $data = CLContact::where("firm_id", $this->firm_id)->get();
        foreach($data as $c=>$a)
        {
            $row = new CLContact;
            $row->name = "DUmmy Name ".$c ;
            $row->middle_name = "DummyMiddleName".$c;//$a->middle_name;
            $row->type = $a->type;
            $row->prefix = $a->prefix;
            $row->title = "DummyTitle".$c;//$a->title;
            $row->initials = $a->initials;
            $temp_email[$c] = array("name"=>"Other","address"=>"DummyEmailPrimary".$c,"default_email"=>true);//$c;//$a->primary_email_address;
            $row->email_addresses = $temp_email;
            $temp_phone_no[$c] = array("name"=>"Other","number"=>"DummyContact".$c,"default_number"=>true);//$c;//$a->primary_email_address;
            $row->phone_numbers =$temp_phone_no;// $c;//$a->primary_phone_number;
            $row->is_client = $a->is_client;
            $row->company_id = $a->company_id;
            $row->created_at = $a->created_at ;
            $row->updated_at = $a->updated_at;
            $values[$c] = json_decode($row);
            unset($row);
            break;

        }
        echo sizeof($values);
        for($i=0;$i<sizeof($values);$i++)
        {
            $array[] = (object) array("data"=>$values[$i]);
        }
        echo json_encode($array);
        for($i=0;$i<sizeof($values);$i++)
        {
            $response = Curl::to('https://app.clio.com/api/v4/contacts')
                ->withHeader("Authorization: BEARER {$this->access_token}")
//            ->withHeader("X-BULK: true")
                ->withData(json_encode($array[$i]))
                ->withContentType('application/json')
                ->post();
            echo $response;
        }

        echo $response;


        return true;
    }

    public function syncMatters()
    {
        $this->refreshToken();
        $success = false;
        $client_id = null;
        $response = Curl::to('https://app.clio.com/api/v4/contacts?fields=id,name')
            ->withHeader("Authorization: BEARER {$this->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        while(true)
        {
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress")) {
                $run = true;
            } else {
                for($i=0;$i<sizeof($data->content['data'][0]['data']);$i++)
                {
                    if($data->content['data'][0]['data'][$i]["name"]=="Dummy Name 0")
                    {
                        echo "found";
                        $client_id = $data->content['data'][0]['data'][$i]["id"];
                    }

                }
                break;

            }
        }
                $this->refreshToken();
                $values = [];
                $array = null;
                 $data = CLMatter::where("firm_id", $this->firm_id)->get();
                 $row = new CLMatter;
                 foreach($data as $c=>$a)
                {

                    $row->display_number = "Dummy Display Number ".$c;//$a->display_number;
                    $row->description = "Dummy Description ".$c;//->description;
                    $row->status = $a->status;
                    $row->location = "Dummy Location ".$c;//location;
                    $row->open_date = $a->open_date;
                    $row->close_date = $a->close_date ;
                    $row->pending_date = $a->pending_date;
                    $row->client = array("id"=> $client_id);
                    $values[$c] = json_decode($row);
                     unset($row);
                     break;
                }
        for($i=0;$i<sizeof($values);$i++)
        {
            $array[] = (object) array("data"=>$values[$i]);
        }
            for($i=0;$i<sizeof($values);$i++)
            {
                $response = Curl::to('https://app.clio.com/api/v4/matters')
                    ->withHeader("Authorization: BEARER {$this->access_token}")
    //            ->withHeader("X-BULK: true")
                    ->withData(json_encode($array[$i]))
                    ->withContentType('application/json')
                    ->post();
                echo $response;
            }
                return true;
    }
    public function syncTasks()
    {
            $values = [];
            $array = null;
            $matter_id = null;
            $user_id = null;
        $this->refreshToken();
        $response = Curl::to('https://app.clio.com/api/v4/matters?fields=id,description')
            ->withHeader("Authorization: BEARER {$this->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        while(true)
        {
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress")) {
                $run = true;
            } else {
                for($i=0;$i<sizeof($data->content['data'][0]['data']);$i++)
                {
                    if($data->content['data'][0]['data'][$i]["description"]=="Dummy Description 0")
                    {
//                        echo "found";
                        $matter_id = $data->content['data'][0]['data'][$i]["id"];
                    }
                }
                break;
            }
        }

// for user_id
        $this->refreshToken();
        $response = Curl::to('https://app.clio.com/api/v4/users?fields=id,name')
            ->withHeader("Authorization: BEARER {$this->access_token}")
            ->withHeader("X-BULK: true")
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        $run = true;
        while(true)
        {
            $data = Curl::to($response->headers['Location'])
                ->withHeader("Authorization: BEARER {$this->access_token}")
                ->withResponseHeaders()
                ->returnResponseObject()
                ->asJsonResponse(true)
                ->allowRedirect(true)
                ->get();
            if ($data->headers['X-RateLimit-Remaining'] < 5) {
                $this->refreshToken();
                $this->loadFirmInfo();
            }
            if ((isset($data->content['data']['status']) && $data->content['data']['status'] == "in_progress") || (isset($data->content['data'][0]['status']) && $data->content['data'][0]['status'] == "in_progress")) {
                $run = true;
            } else {
                for($i=0;$i<sizeof($data->content['data'][0]['data']);$i++)
                {
                    if($data->content['data'][0]['data'][$i]["name"]=="Arsalan Zafar")
                    {
//                        echo "found";
                        $user_id = $data->content['data'][0]['data'][$i]["id"];
                    }

                }
                break;

            }
        }
        $data = CLTask::where("firm_id", $this->firm_id)->get();
        foreach($data as $c=>$a)
        {
            $row = new CLTask;
            $row->name = 'Dummy Name'.$c;//$a->name;
            $row->status = $a->status;
            $row->description = 'Dummy description'.$c;//$a->description;
            $row->priority = $a->priority;
            $row->statute_of_limitation =$a->statute_of_limitation;
//            $row->task_type = array('id'=>"0"); // array;
            $row->matter = array("id"=>$matter_id);
            $row->assignee = array("id"=>$user_id,"type"=>"User");
            $row->due_at = $a->due_at;
            $row->completed_at = $a->completed_at;
            $values[$c]=json_decode($row);
            unset($row);
            break;
        }
        for($i=0;$i<sizeof($values);$i++)
        {
            $array[] = (object) array("data"=>$values[$i]);
        }
        echo json_encode($array);
        for($i=0;$i<sizeof($values);$i++) {
            $response = Curl::to('https://app.clio.com/api/v4/tasks')
                ->withHeader("Authorization: BEARER {$this->access_token}")
//            ->withHeader("X-BULK: true")
                ->withData(json_encode($array[$i]))
                ->withContentType('application/json')
                ->post();
            echo $response;
        }
    }

    public function syncTimeEntries()
    {
              $array = null;
              $values = [];
              $this->refreshToken();
              $data = CLTimeEntry::where("firm_id", $this->firm_id)->get();
                foreach($data as $c=>$a)
                {
                    $row = new CLTimeEntry();
                    $row->type = $a->type;
                    $row->quantity = $a->quantity;
                    $row->price = $a->price;
                    $row->note = "DummyNote".$c;
                    $row->date = $a->date ;

                    $values[$c]= json_decode($row);
                    break;
                    unset($row);
                }
        echo sizeof($values);
        for($i=0;$i<sizeof($values);$i++)
        {
            $array[] = (object) array("data"=>$values[$i]);
        }
//         echo json_encode($array);
        for($i=0;$i<sizeof($values);$i++) {
            $response = Curl::to('https://app.clio.com/api/v4/activities')
                ->withHeader("Authorization: BEARER {$this->access_token}")
////            ->withHeader("X-BULK: true")
                ->withData(json_encode($array[$i]))
                ->withContentType('application/json')
                ->post();
            echo $response;
        }
        return true;
    }

    public function syncInvoices()
    {
        
                CLInvoice::where("firm_id", 25)->delete();
                CLInvoiceMatter::where("firm_id", 25)->delete();
                $data = CLInvoice::where("firm_id", $this->firm_id)->get();
                $invoicematter = CLInvoiceMatter::where("firm_id", $this->firm_id)->get();
                foreach($data as $c=>$a)
                {
                    $row = new CLInvoice;
                    $row->firm_id =25;
                    $row->ref_id = $a->ref_id;
                    $row->etag = $a->etag;
                    $row->number = $a->number;
                    $row->subject = $a->subject;
                    $row->purchase_order = $a->purchase_order;
                    $row->type = $a->type;
                    $row->balance = $a->balance;
                    $row->config = null;
                    $row->state = $a->state;
                    $row->kind = $a->kind;
                    $row->total = $a->total;
                    $row->paid = $a->paid;
                    $row->pending = $a->pending;
                    $row->due = $a->due;
                    $row->sub_total = $a->sub_total;
                    if($a->clio_user_id ==0) {
                        $row->clio_user_id = 0;
                    } else {
                        $row->clio_user_id = CLUser::getIDfromRefID(CLUser::where("id",$a->clio_user_id)->select("ref_id")->first()->ref_id, 25);
                    }
                    if($a->clio_contact_id ==0){
                        $row->clio_contact_id = 0;
                    } else {
                        $row->clio_contact_id = CLContact::getIDfromRefID(CLContact::where("id",$a->clio_contact_id)->select("ref_id")->first()->ref_id, 25);
                    }
                    $row->issued_at = $a->issued_at;
                    $row->due_at = $a->due_at;
                    $row->start_at = $a->start_at;
                    $row->end_at = $a->start_at;
                    $row->created_at = $a->created_at;
                    $row->updated_at = $a->updated_at;
                    $success = $row->save();
                }
               
                    foreach($invoicematter as $c=>$matter)
                    {
                        $pivot = new CLInvoiceMatter;
                        $pivot->firm_id = 25;
                        if($matter->clio_invoice_id ==0)
                        {
                            $pivot->clio_invoice_id = 0;
                        } else {
                            $pivot->clio_invoice_id = CLInvoice::getIDfromRefID(CLInvoice::where("id",$matter->clio_invoice_id)->select("ref_id")->first()->ref_id, 25);
                        } 
                        if($matter->clio_matter_id == 0)
                        {
                            $pivot->clio_matter_id=0;
                        } else {

                            $pivot->clio_matter_id = CLMatter::getIDfromRefID(CLMatter::where("id",$matter->clio_matter_id)->select("ref_id")->first()->ref_id, 25);
                        }
                        $pivot->save();
                        unset($pivot);
                    }
                
               
        return true;
    }

    public function syncPracticeAreas()
    {
        $values = [];
        $array = null;
        $this->refreshToken();

            $data = CLPracticeArea::where("firm_id", $this->firm_id)->get();
            foreach($data as $c=>$a)
            {
                $row = new CLPracticeArea;
                $row->name = "Dummy Area Of Practice".$c;//->name;
                $row->code = $a->code;
                $values[$c] = json_decode($row);
                unset($row);
//                break;
            }
        for($i=0;$i<sizeof($values);$i++)
        {
            $array[] = (object) array("data"=>$values[$i]);
        }
//         echo json_encode($array);
        for($i=0;$i<sizeof($values);$i++) {
            $response = Curl::to('https://app.clio.com/api/v4/practice_areas')
                ->withHeader("Authorization: BEARER {$this->access_token}")
////            ->withHeader("X-BULK: true")
                ->withData(json_encode($array[$i]))
                ->withContentType('application/json')
                ->post();
            echo $response;
        }


        return true;
    }
    public function dummyAccount()
    {
        $cnt = CLContact::where("firm_id", 25)->get();
        foreach($cnt as $a=>$c)
        {
            $c->name = "Contact".$a;
            $c->first_name = "Contact".$a;
            $c->primary_email_address = "Contact".$a."@dummy.com";
            $c->save();
        }

     
        $matt = CLMatter::where("firm_id", 25)->get();
        foreach($matt as $a=>$c)
        {
            $c->display_number = $a;
            $c->number = "Matter".$a;
            $c->save();
        }
        $user = CLUser::where("firm_id", 25)->get();
        foreach($user as $a=>$c)
        {
            $c->name = "User".$a;
            $c->first_name = "User".$a;
            $c->last_name = " ";
            $c->email = "User".$a."@dummy.com";
            $c->save();
        }
        return "All Good";
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries;

use App\PPUser;
use App\PPMatter;
use App\PPAccount;
use App\PPContact;
use App\FirmIntegration;
use Ixudra\Curl\Facades\Curl;
use App\Http\Libraries\ModelForDataSend\Account;
use App\Http\Libraries\ModelForDataSend\Contact;

class PracticePantherPostLibrary {

    private $integration = null;
    private $firm_id;

    public function __construct($firm_id) {
        $this->firm_id = $firm_id;
        $this->integration = FirmIntegration::where("firm_id", $this->firm_id)->first();
    }

    public function sendContact()
    {
        $mts = PPContact::where("firm_id", 1)->where("is_primary",1)->get();
        $data = "";
        foreach ($mts as $k=>$v) {
            $t = "Account".$k;
            $t1 = "Company".$k;
            $t2 = "Contact".$k;
            $row = new Account("",$t,"",$t1,"Chicago","Chicago","Chicago","Illinois","United States","60007","",$t2,"c","","","",$t2."@xyzzz.com");
            $row = json_encode($row);
           // dd($row);
            $response = Curl::to('https://app.practicepanther.com/api/v2/accounts')
            ->withHeader("Authorization: BEARER 3pKhZOMyhVeC2KthjkShCoriSVAgWcKh23rl57ozMxk9X0MRLx8tvEXYuEq6nxatsyaOeQvmTz4ESEw_WcpUn9u2Ax6LduZn6_MwlWrW_lCEI3_-7wkhTLx6vMXfVUO9R3T0YJ6S1UnShQDq55ucUKn5PxGB0TCfqIvk1UaUjjD_IPH--kvVYks6KkUFS6c6stmAxI3kD0KrK1LhUg_3WGBP3pvbmbqCslJ0TgqYoqD7eIo2qYHrACLsSIfIzLHPK9prUlucPZiYQjsLaltOI8FB8wwujb-RmbSsi5pGkrb58T4lb_JzfdLecB9M45TGonp01aSywibNJsVJPuLtWZBzqUjRwQdPN52LLIKC9ncGbSlDvmNmv0KEIHz-Y9JRfkR9BSJEu72uf2-V9Iq84a8fCxUrV_Rjtkc5dtQbeE-lJZLWdkLIuYyjB5nzZN9vJQOL3MA0dV7Tl03GfsjhRiUaZ9KRAi2QxNWhsvz2WwDfQNcRi3HlO9xiU3kN4qGvXsXzlM-lJdTvPBeGwK_8vToAP8khoSxLTcugqk51qtZVoYSn2jKdW3DpVs7n_8ag")
            ->withData($row)
            ->withContentType('application/json')
            ->post();
            //$data = $data.;
            //dd($response);
        }
        
    return $data;
    }
    public function dummyAccount()
    {
        $cnt = PPContact::where("firm_id", $this->firm_id)->get();
        foreach($cnt as $a=>$c)
        {
            $c->display_name = "Contact".$a;
            $c->first_name = "Contact".$a;
            $c->email = "Contact".$a."@dummy.com";
            $c->save();
        }

        $acnt = PPAccount::where("firm_id", $this->firm_id)->get();
        foreach($acnt as $a=>$c)
        {
            $c->display_name = "Account".$a;
            $c->company_name = "Account".$a;
            $c->save();
        }
        $matt = PPMatter::where("firm_id", $this->firm_id)->get();
        foreach($matt as $a=>$c)
        {
            $c->display_name = "Matter".$a;
            $c->name = "Matter".$a;
            $c->save();
        }
        $user = PPUser::where("firm_id", $this->firm_id)->get();
        foreach($user as $a=>$c)
        {
            $c->display_name = "User".$a;
            $c->first_name = "User".$a;
            $c->middle_name = " ";
            $c->last_name = " ";
            $c->email = "User".$a."@dummy.com";
            $c->save();
        }
        return "All Good";
    }

}

?>
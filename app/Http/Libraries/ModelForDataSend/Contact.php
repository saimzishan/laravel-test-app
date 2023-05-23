<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries\ModelForDataSend;

use JsonSerializable;

class Contact implements JsonSerializable
{ 
    
    protected $id =  '';
    protected $first_name = '';  
    protected $last_name =  '';
    protected $phone_mobile =  '';
    protected $phone_home =  '';
    protected $phone_work =  '';
    protected $email =  '';
   

    public function __construct($id,$fn,$ln,$pm,$ph,$pw,$em) {
        $this->id = $id;
        $this->first_name = $fn;
        $this->last_name = $ln;
        $this->phone_mobile = $pm;
        $this->phone_home = $ph;
        $this->phone_work = $pw;
        $this->email = $em;
        
    }
    public function jsonSerialize()
    {
        return 
        [
            'id'   => $this->id,
            'display_name' =>  $this->first_name,
            'first_name' =>  $this->first_name,
            'last_name' => $this->last_name,
            'phone_mobile' => $this->phone_mobile,
            'phone_home' => $this->phone_home,
            'phone_work' => $this->phone_work,
            'email' => $this->email,
            
        ];
    }
   


}

?>
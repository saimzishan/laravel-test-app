<?php
/**
 * Created by PhpStorm.
 * User: Sabeeh Murtaza Mirza
 * Date: 3/1/2019
 * Time: 8:39 PM
 */

namespace App\Http\Libraries\ModelForDataSend;

use JsonSerializable;
use App\Http\Libraries\ModelForDataSend\Contact;

class Account implements JsonSerializable
{ 

    protected $id =  '';
    protected $display_name = '';  
    protected $number =  '';
    protected $company_name =  '';
    protected $address_street_1 =  '';
    protected $address_street_2 =  '';
    protected $address_city =  '';
    protected $address_state =  '';
    protected $address_country =  '';
    protected $address_zip_code = '';
    protected $contact ;

    public function __construct($id,$dp,$n,$cn,$as1,$as2,$ac,$as,$acu,$azc,$cid,$cfn,$cln,$cpm,$cph,$cpw,$cem) {
        $this->id = $id;
        $this->display_name = $dp;
        $this->number = $n;
        $this->company_name = $cn;
        $this->address_street_1 = $as1;
        $this->address_street_2 = $as2;
        $this->address_city = $ac;
        $this->address_state = $as;
        $this->address_country = $acu;
        $this->address_zip_code = $azc;
        $this->contact = new Contact($cid,$cfn,$cln,$cpm,$cph,$cpw,$cem);
    }
    public function getId() 
    {
        return $this->id;
    }
    
    public function getDsiplayName() 
    {
        return $this->display_name;
    }
    public function getNumber() 
    {
        return $this->number;
    }
    public function getCompanyName() 
    {
        return $this->company_name;
    }
    public function jsonSerialize()
    {
        return 
        [
            'id'   => $this->getID(),
            'display_name' =>  $this->getDsiplayName(),
            'number' => $this->getNumber(),
            'complany_name' => $this->getCompanyName(),
            'address_street_1' => $this->address_street_1,
            'address_street_2' => $this->address_street_2,
            'address_city' => $this->address_city,
            'address_state' => $this->address_state,
            'address_country' => $this->address_country,
            'address_zip_code' => $this->address_zip_code,
            'primary_contact' => $this->contact
        ];
    }

   


}

?>
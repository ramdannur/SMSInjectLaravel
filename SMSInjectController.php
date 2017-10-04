<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class SMSInjectController extends Controller
{
    private $error, $msg, $creator_id, $dest, $udh, $msg_part; //msg_part array of couple udh + msg
     
    /**
     * sms_inject::__construct()
     * @usage object constructor 
     * @return void
     */
    function __construct() 
    {
        $this->udh=array(
        'udh_length'=>'05', //sms udh lenth 05 for 8bit udh, 06 for 16 bit udh
        'identifier'=>'00', //use 00 for 8bit udh, use 08 for 16bit udh
        'header_length'=>'03', //length of header including udh_length & identifier
        'reference'=>'00', //use 2bit 00-ff if 8bit udh, use 4bit 0000-ffff if 16bit udh
        'msg_count'=>1, //sms count
        'msg_part'=>1 //sms part number
        );
        $this->msg_part=array();
        $this->error=array();
    }
     
    function tes(){
    	return "hallo, inject success";
    }

    /**
     * sms_inject::mass_sms()
     * @usage tell gammu-smsd to send one sms to many recipient
     * @param string $msg
     * @param string $creator_id
     * @param array $dest
     * @param string $sender
     * @return void
     */
    function mass_sms($msg,$dest,$creator_id,$sender='')
    {
        $this->msg=$msg;
        $this->creator_id=$creator_id;
        $this->create_msg();
        if(!is_array($dest))
        {
            $this->send_sms($msg,$dest,$creator_id,$sender);
        }
        else
        {
            foreach($dest as $dst)
            {
                $this->send_sms($msg,$dst,$creator_id,$sender);
            }
        }
    }
     
     
    /**
     * sms_inject::send_sms()
     * @usage tell gammu-smsd to send sms to sepcified phone number
     * @param string $msg
     * @param string $creator_id
     * @param string $dest
     * @param string $sender
     * @return false if error
     */
    function send_sms($msg,$dest,$creator_id,$sender='')
    {
        if(!$dest)
        {
            $this->error[]='No destination number defined';
            return false;
        }
        $this->msg=$msg;
        $this->dest=$dest;
        $this->creator_id=$creator_id;
        $this->create_msg();
        //uncomment to get preview
        //echo "<pre>Destination : $this->dest\nSender : $sender\nMessage :\n";print_r($this->msg_part);
        $this->inject($sender);
    }
     
     
    /**
     * sms_inject::inject()
     * @usage insert previously created sms part to database
     * @param string $sender
     * @return void
     */
    private function inject($sender='')
    {
        $multipart=(count($this->msg_part) > 1)?'true':'false';
        $id='';
        foreach($this->msg_part as $number => $sms)
        {
            if($number==1)
            {
                // $id=mysql_fetch_assoc(mysql_query("select last_insert_id() as id",$this->res));
                // $id=$id['id'];

                $id = DB::table('outbox')->insertGetId(
                    ['UDH' => $sms['udh'], 
                    'DestinationNumber' => trim($this->dest, " "), 
                    'TextDecoded' => $sms['msg'], 
                    'MultiPart' => $multipart, 
                    'SenderID' => $sender, 
                    'CreatorID' => $this->creator_id]
                );
            }
            else
            {
            
                DB::table('outbox_multipart')->insert(
                    ['UDH' => $sms['udh'], 
                    'SequencePosition' => $number, 
                    'TextDecoded' => $sms['msg'], 
                    'ID' => $id]
                );
            }
        }
    }
     
     
    /**
     * sms_inject::create_msg()
     * @usage create sms message (and create udh if sms is multipart)
     * @return void
     */
    private function create_msg()
    {
        $x=1;
        if(strlen($this->msg)<=160) //if single sms, send without udh
        {
            $this->msg_part[$x]['udh']='';
            $this->msg_part[$x]['msg']=$this->msg;
        }
        else //if multipart sms, split into 153 character each part
        {
            $msg=str_split($this->msg,153);
            $ref=mt_rand(1,255);
            $this->udh['msg_count']=$this->dechex_str(count($msg));
            $this->udh['reference']=$this->dechex_str($ref);
            foreach($msg as $part)
            {
                $this->udh['msg_part']=$this->dechex_str($x);
                $this->msg_part[$x]['udh']=implode('',$this->udh);
                $this->msg_part[$x]['msg']=$part;
                $x++;
            }
        }
    }
     
     
    /**
     * sms_inject::dechex_str()
     * @usage convert decimal to zerofilled hexadecimal
     * @param integer $ref
     * @return 2 digit hexa-decimal in string format
     */
    private function dechex_str($ref)
    {
        return ($ref <= 15 )?'0'.dechex($ref):dechex($ref);
    }
}

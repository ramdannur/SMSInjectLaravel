<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function send_sms(Request $request)
    {

            $msdn = '081234123412';

        	$msdns = array('081234123412', '081234123413', '081234123414');
        	
            $text_pesan = $request->input('text_pesan');

        	$creator_id = $request->user()->creator_id;

        	$inject = new SMSInjectController();

            // send sms
			$inject->send_sms($text_pesan,$msdn, $creator_id,$creator_id);

            // blast sms
			$inject->mass_sms($text_pesan,$msdns,$creator_id, $creator_id); 

    }

}

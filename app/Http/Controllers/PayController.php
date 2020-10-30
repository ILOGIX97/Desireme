<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Paysafe\Environment;
use Paysafe\PaysafeApiClient;
use Paysafe\ThreeDSecureV2\Authentications;
class PayController extends Controller
{
    public function index()
    {
        return view('pay.index');
    }

    public function store(Request $request)
    {
        $paysafeApiKeyId = config('app.paysafeApiKeyId');
        $paysafeApiKeySecret = config('app.paysafeApiKeySecret');
        $paysafeAccountNumber = config('app.paysafeAccountNumber');
        $client = new PaysafeApiClient($paysafeApiKeyId, $paysafeApiKeySecret, Environment::TEST, $paysafeAccountNumber);
        $auth = $client->threeDSecureV2Service()->authentications(new Authentications(array(
            'merchantRefNum' => $request->merchant_ref_num,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'deviceFingerprintingId' => $request->deviceFingerprinting_Id,
            'merchantUrl' => $request->merchant_Url,
            'authenticationPurpose' => $request->authentication_Purpose,
            'deviceChannel' => $request->device_Channel,
            'messageCategory' => $request->message_Category,
            'card' => array(
                'holderName' => $request->holder_Name,
                'cardNum' => $request->card_number,
                'cardExpiry' => array(
                    'month' => $request->card_exp_month,
                    'year' => $request->card_exp_year
                )
            ),
        )
        ));
        print_r($auth->id);
    }
}

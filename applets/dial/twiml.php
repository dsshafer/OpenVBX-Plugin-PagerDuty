<?php
$CI =& get_instance();

$response = new TwimlResponse; // start a new Twiml response

$pd_api_key = AppletInstance::getValue('pd-api-key');
$pd_hostname = AppletInstance::getValue('pd-hostname');
$pd_schedule = AppletInstance::getValue('pd-schedule');

if ((!empty($pd_api_key)) && (!empty($pd_hostname)) && (!empty($pd_schedule)))
{
  // all required configuration is present

  $baseUrl = "https://" . $pd_hostname . ".pagerduty.com/api/v1/";
  $headers = array(
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Token token=' . $pd_api_key
  );
  $handle = curl_init();
  curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

  // get id of currently on-call user
  $url = $baseUrl . "/schedules/$pd_schedule/entries?overflow=true&since=" . gmdate("c") . "&until=" . gmdate("c");
  curl_setopt($handle, CURLOPT_URL, $url);
  if (! $curlResponse = curl_exec($handle))
  {
    $response->say("An unexpected error has occurred accessing the on-call information.");
  }
  else
  {
    $scheddata = json_decode($curlResponse);
    $user_id = $scheddata->entries['0']->user->id;

    // get preferred phone number for on-call user
    $url = $baseUrl . "/users/$user_id/contact_methods";
    curl_setopt($handle, CURLOPT_URL, $url);
    if (! $curlResponse = curl_exec($handle)) {
      $response->say("Error accessing contact methods.");
    }
    curl_close($handle);
    $contactdata = json_decode($curlResponse);
    $num_methods = $contactdata->total;

    $phone_numbers = array();

    $i = 0;
    while ($i < $num_methods)
    {
      if (($contactdata->contact_methods[$i]->type == "phone")
        && ($contactdata->contact_methods[$i]->blacklisted == false))
      {
        $phone_numbers[] = $contactdata->contact_methods[$i]->phone_number;
      }
      $i++;
    }


    $dial = $response->dial(null, array(
      'timeout' => $CI->vbx_settings->get('dial_timeout', $CI->tenant->id),
      'timeLimit' => 14400,
      'callerId' => "+1 563-607-5771"
    ));
    //$dial->number($phone_numbers);

    // dial all numbers
    $i = 0;
    while ($i < count($phone_numbers))
    {
      $dial->number($phone_numbers[$i]);
      $i++;
    }
  }
}
else
{
  // configuration error
  $response->say("An unexpected error has occurred.");
}

$response->respond(); // send response

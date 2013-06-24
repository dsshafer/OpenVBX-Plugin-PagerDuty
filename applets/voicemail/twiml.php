<?php
$CI =& get_instance();
$transcribe = (bool) $CI->vbx_settings->get('transcriptions', $CI->tenant->id);

$response = new TwimlResponse; // start a new Twiml response
if(!empty($_REQUEST['RecordingUrl'])) // if we've got a transcription
{
  $pd_service_api_key = AppletInstance::getValue('pd-service-api-key');

  // add a voice message 
  OpenVBX::addVoiceMessage(
    AppletInstance::getUserGroupPickerValue('permissions'),
    $CI->input->get_post('CallSid'),
    $CI->input->get_post('From'),
    $CI->input->get_post('To'), 
    $CI->input->get_post('RecordingUrl'),
    $CI->input->get_post('RecordingDuration'),
    ($transcribe == false) // if not transcribing then notify immediately
  );

  if(!empty($pd_service_api_key))
  {
    $url = "https://events.pagerduty.com/generic/2010-04-15/create_event.json";
    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json'
    );

    $callDetails = array(
      'CallSid'           => $CI->input->get_post('CallSid'),
      'AccountSid'        => $CI->input->get_post('AccountSid'),
      'From'              => $CI->input->get_post('From'),
      'To'                => $CI->input->get_post('To'),
      'RecordingUrl'      => $CI->input->get_post('RecordingUrl'),
      'RecordingDuration' => $CI->input->get_post('RecordingDuration')
    );

    $optionalKeys = array(
      'CallerName',
      'FromCity',
      'FromState',
      'FromZip',
      'FromCountry',
      'ToCity',
      'ToState',
      'ToZip',
      'ToCountry'
    );
    foreach ($optionalKeys as $key) {
      if ($CI->input->get_post($key))
      {
        $callDetails[$key] = $CI->input->get_post($key);
      }
    }

    $data = json_encode(array(
      'service_key' => $pd_service_api_key,
      'event_type'  => "trigger",
      'description' => "voicemail from " . $CI->input->get_post('From'),
      //'incident_key' => "", ## auto-generated if not present
      'details'     => $callDetails
    ));

    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
 
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
 
    //if ( ! $curlResponse = curl_exec($handle))
    //{
    //  error_log(curl_error($handle));
    //}
    $curlResponse = curl_exec($handle);
    $returnCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    //syslog(LOG_DEBUG, "json request to $url : $data");
    //syslog(LOG_INFO, "curl HTTP return code $returnCode");
    //return $curlResponse;

    // say message recorded; say error
  }
}
else
{
  $permissions = AppletInstance::getUserGroupPickerValue('permissions'); // get the prompt that the user configured
  $isUser = $permissions instanceOf VBX_User? true : false;

  if($isUser)
  {
    $prompt = $permissions->voicemail;
  }
  else
  {
    $prompt = AppletInstance::getAudioSpeechPickerValue('prompt');
  }

  if (!AudioSpeechPickerWidget::setVerbForValue($prompt, $response)) 
  {
    // fallback to default voicemail message
    $response->say('Please leave a message. Press the pound key when you are finished.', array(
      'voice' => $CI->vbx_settings->get('voice', $CI->tenant->id),
      'language' => $CI->vbx_settings->get('voice_language', $CI->tenant->id)
    ));
  }

  // add a <Record>, and use VBX's default transcription handler
  $record_params = array(
    'transcribe' => 'false'
  );
  if ($transcribe) {
    $record_params['transcribe'] = 'true';
    $record_params['transcribeCallback'] = site_url('/twiml/transcribe');
  }

  $response->record($record_params);
}

$response->respond(); // send response

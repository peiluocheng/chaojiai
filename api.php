<?php
header('Access-Control-Allow-Credentials:true');
header("Access-Control-Allow-Methods:PUT,POST,GET,DELETE,OPTIONS");
header("Access-Control-Allow-Headers:*");
$ORIGIN = $_SERVER['HTTP_ORIGIN'];
if ($ORIGIN) {
    header('Access-Control-Allow-Origin: '. $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *');
}

if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS') {
//浏览器的option试探请求，要exit掉，不然你的业务会被执行两次
    exit();
}

$params = file_get_contents("php://input");
$params = json_decode($params, true) ?: $_POST;

$data['title'] = $params['title'];
$data['title'] = str_replace("\\n", " ", $params['title']);
$data['sk'] = $params['sk'];
$ch = curl_init();
$max_tokens = $params['max_tokens'] ?: 2048;
$temperature = $params['temperature'] ?: 1;
$top_p = $params['top_p'] ?: 1;
$frequency_penalty = $params['frequency_penalty'] ?: 0;
$presence_penalty = $params['presence_penalty'] ?: 0;
$model = $params['textmodel'] ?: "text-davinci-003";
if ($model == 'createimg') {
    $data['size'] = $params['size'];
    // $data['size'] = '1024x1024';
    $postData = '{"n":'.$params['imgnum'].',"size": "'.$data['size'].'","prompt":"' . $data['title'] . '"}';
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $data['sk'] . ''
    ];
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    $response_data = json_decode($response);
    if (isset($response_data->data[0]->url)) {
        $text = $response_data->data;
        // $text = trim(str_replace("\\n", "\n", $response_data->data[0]->url), "\n");
    } elseif (isset($response_data->error->message)) {
        $text = "现在使用人数过多，请过段时间再试2：" . $response_data->error->message;
    } else {
        $text = "服务器超时或返回异常消息。";
    }
}elseif ($model == 'chatsonic') {
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.writesonic.com/v2/business/content/chatsonic?engine=premium&language=zh&num_copies=1",
         CURLOPT_SSL_VERIFYPEER=> false,
          CURLOPT_SSL_VERIFYHOST=> false,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\"input_text\":\"".$data['title']."\",\"enable_memory\":true,\"enable_google_results\":false}",
          CURLOPT_HTTPHEADER => [
            "X-API-KEY: 49ba1a5c-009f-4127-891d-8e17c07801c5",
            "accept: application/json",
            "content-type: application/json"
          ],
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    if ($err) {
      $text = "现在使用人数过多，请过段时间再试2："  . $err;
    } else {
      $response_data = json_decode($response, true);
      $text =  $response_data['message'];
    }
}else{
    if(strstr($data['title'],'{"role":"user"')){
        $data['title'] = str_replace("\n","\\n",$data['title']);
        $prompt = $data['title'];
    }else{
       $prompt = '{"role":"user","content":"' . $data['title'] . '"}'; 
    } 
    $model = 'gpt-3.5-turbo';
    $postData = '{"model":"'.$model.'","messages":[' . $prompt . ']}';
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $data['sk'] . ''
    ];
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    $response_data = json_decode($response);
    if (isset($response_data->choices[0]->message->content)) {
        $text = trim(str_replace("\\n", "\n", $response_data->choices[0]->message->content), "\n");
    } elseif (isset($response_data->error->message)) {
        $text = "服务器超时或返回异常消息，请过段时间再试1：" . $response_data->error->message;
    } else {
        $text = "服务器超时或返回异常消息。";
    }
} 
$result = array(
    'code' => 200,
    'msg' => "获取成功",
    'data' => $text,
);
curl_close($ch);
echo json_encode($result, 320);
die;

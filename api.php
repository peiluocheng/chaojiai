<?php
$params = file_get_contents("php://input");
$params = json_decode($params, true);
$data['title'] = $params['title'];
$data['title'] = str_replace("******", "\\n\\n", $params['title']);
$data['sk'] = $params['sk'];
$ch = curl_init();
$max_tokens = $params['max_tokens'] ?: 2048;
$temperature = $params['temperature'] ?: 1;
$top_p = $params['top_p'] ?: 1;
$frequency_penalty = $params['frequency_penalty'] ?: 0;
$presence_penalty = $params['presence_penalty'] ?: 0;
$model = $params['textmodel'] ?: "text-davinci-003";
if ($model == 'gpt-3.5-turbo') {
    $prompt = '{"role":"user","content":"' . $data['title'] . '"}';
    $postData = '{"model":"gpt-3.5-turbo","messages":[' . $prompt . ']}';
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
        $text = "现在使用人数过多，请过段时间再试1：" . $response_data->error->message;
    } else {
        $text = "服务器超时或返回异常消息。";
    }
}elseif ($model == 'createimg') {
    $data['size'] = $params['size'];
    // $data['size'] = '1024x1024';
    $postData = '{"n":1,"size": "'.$data['size'].'","prompt":"' . $data['title'] . '"}';
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
        $text = trim(str_replace("\\n", "\n", $response_data->data[0]->url), "\n");
    } elseif (isset($response_data->error->message)) {
        $text = "现在使用人数过多，请过段时间再试2：" . $response_data->error->message;
    } else {
        $text = "服务器超时或返回异常消息。";
    }
} else {
    $headers = array();
    $headers[] = "Content-Type: application/json";
    $headers[] = "Authorization: Bearer " . $data['sk'];
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/completions");
    $postData = '{
            "prompt": "' . $data['title'] . '",
            "max_tokens": ' . $max_tokens . ',
            "temperature": ' . $temperature . ',
            "top_p": ' . $top_p . ',
            "frequency_penalty": ' . $frequency_penalty . ',
            "presence_penalty": ' . $presence_penalty . ',
            "model": "' . $model . '"
        }';
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $response_data = json_decode($response, true);
    if (isset($response_data['choices'][0]['text'])) {
        $text = trim(str_replace("\\n", "\n", $response_data['choices'][0]['text']), "\n");
    } elseif (curl_errno($ch)) {
        $text = "服务器返回错误信息：" . curl_errno($ch);
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

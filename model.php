<?php
function Template($file, $params = array()) {
	foreach($params as $k => $v) {
		$$k = $v;
	}

	ob_start();
	include $file;
	return ob_get_clean();
}

function auth($user, $subdomain, $domain) {
    $link = 'https://'.$subdomain.'.'.$domain.'/private/api/auth.php?type=json';

    $response = send_request($link, $user, 'CURLOPT_POST');
    $response = json_decode($response["response_str"], TRUE);

    return isset($response["response"]["auth"]);
}

function send_request($link, $post_data = [], $type = FALSE, $log = FALSE, $headers = []) {
    $curl = curl_init(); #Сохраняем дескриптор сеанса cURL

    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    if($type == 'CURLOPT_POST') {
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    } elseif($type == 'CURLOPT_CUSTOMREQUEST') {
        if(is_array($post_data)) {
	        $post_data = json_encode($post_data);
        }
	    if(!in_array('Content-Type: application/json', $headers)) {
		    $headers[] = 'Content-Type: application/json';
	    };
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    }
	if(!empty($headers)) {
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	}
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    sleep(1); #Ждем секунду

    $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
    curl_close($curl); #Заверашем сеанс cURL

    if($log === TRUE) {
        $request_str = "Время: ". date("d-m-Y h:i:s", time()) ."; Time(".time().")".PHP_EOL;
        $request_str .= "Ссылка: ".$link.PHP_EOL;
        $request_str .= "IP: ".$_SERVER["REMOTE_ADDR"].PHP_EOL;
        $request_str .= "Request: ".PHP_EOL.json_encode($post_data).PHP_EOL.PHP_EOL;

        $response_str = "Время: ". date("d-m-Y h:i:s", time()) ."; Time(".time().")".PHP_EOL;
        $response_str .= "Информация: ".$code.PHP_EOL;
        $response_str .= "Response: ".$out.PHP_EOL.PHP_EOL;

        file_put_contents("request.log", $request_str, FILE_APPEND);
        file_put_contents("response.log", $response_str, FILE_APPEND);
    }

    return ["response_str" => $out, "code" => $code];
}

function get_entity_ids($entity, $link, $post_data = [], $type = FALSE, $log = FALSE, $headers = []) {

	$response = send_request($link, $post_data = [], $type = FALSE, $log = FALSE, $headers = []);
	$entities = json_decode($response['response_str'], TRUE)['response'][$entity];

	$entities_ids_str = '[';
	foreach($entities as $entity) {
		$entities_ids_str .= $entity['id'] . ',';
	}
	$entities_ids_str .= ']';

	return ["response_str" => $entities_ids_str, "code" => $response['code']];
}
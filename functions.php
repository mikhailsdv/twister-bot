<?php
	function vkapi($method, $parameters) {
		if (!isset($parameters["v"])) {
			$parameters["v"] = V;
		}
		if (!isset($parameters["access_token"])) {
			$parameters["access_token"] = ACCESS_TOKEN;
		}

        $response = file_get_contents("https://api.vk.com/method/" . $method . "?" . http_build_query($parameters));
		return json_decode($response, true);
	}

	function download_file($url, $file_name) {
		$file_path = $_SERVER["DOCUMENT_ROOT"] . "/twister/temp/" . $file_name;
		file_put_contents($file_path, file_get_contents($url));
		return $file_path;
	}

	function upload_file($url, $file_path) {
		$headers = array();
		$headers[] = "Content-Type:multipart/form-data";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => new CurlFile($file_path)));
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		$result = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $result;
	}

	function generate_token($length = 15) {
		$token = "";
		$code_alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$code_alphabet .= "abcdefghijklmnopqrstuvwxyz";
		$code_alphabet .= "0123456789";
		$max = strlen($code_alphabet);
		for ($i = 0;$i < $length;$i++) {
			$token .= $code_alphabet[random_int(0, ($max - 1))];
		}
		return $token;
	}

	function input_to_post() {
		$_POST = json_decode(file_get_contents("php://input"), true);
	};
<?php
	require_once("./config.php");

	ignore_user_abort(true);
	set_time_limit(0);
	ob_start();
	header("HTTP/1.1 200 OK");
	echo "ok";
	header("Connection: close");
	header("Content-Length: " . ob_get_length());
	ob_end_flush();
	ob_flush();
	flush();

	input_to_post();

	if (
		isset($_POST["secret"]) && $_POST["secret"] === SECRET &&
		$_POST["type"] == "message_new"
	) {
		vkapi("messages.markAsRead", array(
			"peer_id" => $_POST["object"]["message"]["from_id"],
			"start_message_id" => $_POST["object"]["message"]["id"],
		));
		
		$parameters = array(
			"peer_id" => $_POST["object"]["message"]["from_id"],
			"random_id" => 0,
		);
		if (
			isset($_POST["object"]["message"]["text"]) &&
			!empty($_POST["object"]["message"]["text"])
		){
			$parameters["message"] = $_POST["object"]["message"]["text"];
		}
		if (
			isset($_POST["object"]["message"]["attachments"]) &&
			!empty($_POST["object"]["message"]["attachments"])
		) {
			$parameters["attachment"] = implode(",", array_map(function($item) {
				if ($item["type"] === "doc") {
					$file_path = download_file($item["doc"]["url"], $item["doc"]["title"]);
					$doc_upload_server = vkapi("docs.getMessagesUploadServer", array( //ссылка
						"peer_id" => $_POST["object"]["message"]["from_id"],
						"type" => "doc"
					));
					$uploaded_file_info = upload_file($doc_upload_server["response"]["upload_url"], $file_path);
					unlink($file_path);
					$saved_file_info = vkapi("docs.save", array(
						"file" => $uploaded_file_info["file"],
						"title" => $item["doc"]["title"],
					));
					return "doc" . $saved_file_info["response"]["doc"]["owner_id"] . "_" . $saved_file_info["response"]["doc"]["id"];
				}
				if ($item["type"] === "photo") {
					$file_path = download_file(end($item["photo"]["sizes"])["url"], generate_token() . ".jpg");
					/*$photo_upload_server = vkapi("photos.getMessagesUploadServer", array( //ссылка
						"peer_id" => $_POST["object"]["message"]["from_id"],
					));*/
					$photo_upload_server = vkapi("photos.getUploadServer", array( //ссылка
						"album_id" => MAIN_ALBUM_ID,/*
						"group_id" => GROUP_ID,*/
						"access_token" => ADMIN_ACCESS_TOKEN
					));
					$uploaded_file_info = upload_file($photo_upload_server["response"]["upload_url"], $file_path);
					unlink($file_path);
					/*$saved_file_info = vkapi("photos.saveMessagesPhoto", array(
						"photo" => $uploaded_file_info["photo"],
						"server" => $uploaded_file_info["server"],
						"hash" => $uploaded_file_info["hash"],
					));*/
					$saved_file_info = vkapi("photos.save", array(
						"album_id" => MAIN_ALBUM_ID,/*
						"group_id" => GROUP_ID,*/
						"photos_list" => $uploaded_file_info["photos_list"],
						"server" => $uploaded_file_info["server"],
						"hash" => $uploaded_file_info["hash"],
						"access_token" => ADMIN_ACCESS_TOKEN
					));
					return "photo" . $saved_file_info["response"][0]["owner_id"] . "_" . $saved_file_info["response"][0]["id"];
				}
				else if ($item["type"] === "graffiti") {
					$file_path = download_file($item["graffiti"]["url"], generate_token() . ".png");
					$doc_upload_server = vkapi("docs.getMessagesUploadServer", array( //ссылка
						"peer_id" => $_POST["object"]["message"]["from_id"],
						"type" => "doc"
					));
					$uploaded_file_info = upload_file($doc_upload_server["response"]["upload_url"], $file_path);
					unlink($file_path);
					$saved_file_info = vkapi("docs.save", array(
						"file" => $uploaded_file_info["file"],
						"title" => "graffiti.png",
					));
					return "doc" . $saved_file_info["response"]["graffiti"]["owner_id"] . "_" . $saved_file_info["response"]["graffiti"]["id"];
				}
				else if ($item["type"] === "sticker") {
					global $parameters;
					$parameters["sticker_id"] = $item["sticker"]["sticker_id"];
				}
				else if ($item["type"] === "audio_message") {
					$file_path = download_file($item["audio_message"]["link_mp3"], generate_token() . ".mp3");
					$doc_upload_server = vkapi("docs.getMessagesUploadServer", array( //ссылка
						"peer_id" => $_POST["object"]["message"]["from_id"],
						"type" => "audio_message"
					));
					$uploaded_file_info = upload_file($doc_upload_server["response"]["upload_url"], $file_path);
					unlink($file_path);
					$saved_file_info = vkapi("docs.save", array(
						"file" => $uploaded_file_info["file"],
					));
					return "doc" . $saved_file_info["response"]["audio_message"]["owner_id"] . "_" . $saved_file_info["response"]["audio_message"]["id"];
				}
				else {
					$attachment = $item["type"] . $item[$item["type"]]["owner_id"] . "_" . $item[$item["type"]]["id"];
					if (isset($item[$item["type"]]["access_key"])) {
						$attachment .= "_" . $item[$item["type"]]["access_key"];
					}
					return $attachment;
				}
			}, $_POST["object"]["message"]["attachments"]));
		}
		if (isset($_POST["object"]["message"]["geo"])) {
			$parameters["lat"] = $_POST["object"]["message"]["geo"]["coordinates"]["latitude"];
			$parameters["long"] = $_POST["object"]["message"]["geo"]["coordinates"]["longitude"];
		}
		if (
			isset($_POST["object"]["message"]["fwd_messages"]) &&
			!empty($_POST["object"]["message"]["fwd_messages"])
		) {
			$parameters["forward_messages"] = $_POST["object"]["message"]["id"];
			/*$parameters["forward_messages"] = implode(",", array_map(function($item) {
				return $item["conversation_message_id"];
			}, $_POST["object"]["message"]["fwd_messages"]));*/
		}

		vkapi("messages.send", $parameters);
	}
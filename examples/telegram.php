//Not very ready to use....
//For beginners: https://github.com/schmidtflo/telegram_bot_blog
		if ($command == "/dsb") {
					$timetableurl = DSB::getTopicChildUrl(0, 0, "DSB-username", "DSB-password"); 
					file_put_contents("/var/www/html/bot/tmp.png", fopen($timetableurl, 'r'));


      		sendmessage('photo', $message["message"]["from"]["id"], 'tmp.png');
					unlink('tmp.png');


    }

<?php
/*
Plugin Name: WP BlipBot
Plugin URI: http://xn--wicek-k0a.pl/projekty/wp-blipbot
Description: WP BlipBot jest rozbudowaną wtyczką pozwalającą stworzyć w serwisie Blip.pl bota informującego o nowych postach publikowanych na blogu. Wtyczka może też wysyłać Ci za pośrednictwem Blipa powiadomienia o nowych komentarzach pojawiających się na blogu.
Version: 3.0.9
Author: Łukasz Więcek
Author URI: http://xn--wicek-k0a.pl/
*/

$wersja = "3.0.9";

require_once("blipbot.ajax.php");

function file_get_contents_curl($url)
	{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);

	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
	}

function BlipBotUstawienia()
	{
	// KLUCZE DOMYŚLNE
	$blipbot_default_consumer 							= 'Ccu3uCBvHp1xYJi5Bg1W';
	$blipbot_default_consumer_secret 					= 'Xekc9lQbRajQUhW0wjuaKPgc61yl6YO5CKZcR3Qx';
	
	// USTAWIENIE ZMIENNYCH Z KLUCZAMI DOMYSLNYMI
	$blipbot_consumer 								= $blipbot_default_consumer;
	$blipbot_consumer_secret 							= $blipbot_default_consumer_secret;
	
	// ZMIANA KLUCZY W ZMIENNYCH, JEŻELI SĄ ZAPISANE W BAZIE
	if(get_option('blipbot_consumer'))					{$blipbot_consumer 		= get_option('blipbot_consumer');}
	if(get_option('blipbot_consumer_secret'))			{$blipbot_consumer_secret 	= get_option('blipbot_consumer_secret');}
	
	// ZMIANA KLUCZY W ZMIENNYCH, JEŻELI SĄ PODANE W FORMULARZU
	if(strlen($_POST['blipbot_consumer'])==20)			{$blipbot_consumer 		= $_POST['blipbot_consumer'];}
	if(strlen($_POST['blipbot_consumer_secret'])==40)		{$blipbot_consumer_secret 	= $_POST['blipbot_consumer_secret'];}
	
	if($_GET['oauth'] == 'start')
		{
		if(function_exists('current_user_can') && !current_user_can('manage_options'))
			{
			die('Brak uprawnień');
			}
		else
			{
			$oauth_consumer = new OAuthConsumer ($blipbot_consumer, $blipbot_consumer_secret);
			$oauth_method   = new OAuthSignatureMethod_HMAC_SHA1 ();

			$oauth_request = OAuthRequest::from_consumer_and_token ($oauth_consumer, null, 'GET', 'http://blip.pl/oauth/request_token', array ('oauth_callback' => 'oob'));
			$oauth_request->sign_request ($oauth_method, $oauth_consumer, null);

			$resp = @file_get_contents_curl($oauth_request->to_url());
			parse_str($resp, $resp);

			if(!$resp)
				{
				echo '<center>
				<h2 style="margin-top: 30px;">Informacja</h2>
				<h3>Wystąpił błąd w połączeniu.</h3>
				<p>Jeśli nie zmieniałeś kluczy oznacza to, że Blip jest przeciążony, w przeciwnym wypadku klucze są niepoprawne.</p></center>';
				die;
				}

			if(get_option('blipbot_tempt'))
				{
				update_option('blipbot_tempt', $resp['oauth_token']);
				update_option('blipbot_tempts', $resp['oauth_token_secret']);
				}
			else
				{
				add_option('blipbot_tempt', $resp['oauth_token']);
				add_option('blipbot_tempts', $resp['oauth_token_secret']);
				}

			$oauth_token    = new OAuthToken ($resp['oauth_token'], $resp['oauth_token_secret']);

			$oauth_request  = new OAuthRequest ('GET', 'http://blip.pl/oauth/authorize', array ('oauth_token' => $oauth_token->key));
			$link = $oauth_request->to_url();
			echo "<center><h2 style='margin-top: 30px;'>Informacja</h2>
			
			<p>Na dole strony wyświetlana jest ramka Blipa, w której musisz zaakceptować dostęp. Po zaakceptowaniu uzyskasz swój numer PIN.</p>
			
			<p>Zamiast ramki możesz otworzyć Blipa w <a href='{$link}' target='_blank'>nowym oknie</a>.</p>
			
			<h3>Wpisz otrzymany PIN: </h3>
			
			<form action='options-general.php?page=wp-blipbot/wp-blipbot.php&oauth=callback'><input type='hidden' name='page' value='wp-blipbot/wp-blipbot.php'><input type='hidden' name='oauth' value='callback'><input name='oauth_verifier'><p class='submit'><input type='submit' value='Kontynuuj'></p></form>
			
			<iframe scrolling='no' width='820' height='370' style='' src='{$link}'><h2>Aby dokończyć kliknij: <a href='{$link}' target='_blank'>{$link}</a></h2></iframe>";
			die;
			}
		}
		
	if($_GET['oauth'] == 'callback')
		{
		if(function_exists('current_user_can') && !current_user_can('manage_options'))
			{
			die('Brak uprawnień');
			}
		else
			{
			$oauth_verifier = $_GET['oauth_verifier'];

			if(empty($oauth_verifier))
				{
				die('<center><h2 style="margin-top: 30px;">Wystąpił błąd</h2>
				
				<h3>Musisz podać PIN!</h3>
				</center>');
				}

			$oauth_consumer = new OAuthConsumer ($blipbot_consumer, $blipbot_consumer_secret);
			$oauth_method   = new OAuthSignatureMethod_HMAC_SHA1 ();
			$oauth_token    = new OAuthToken (get_option('blipbot_tempt'), get_option('blipbot_tempts'));

			$oauth_request  = OAuthRequest::from_consumer_and_token ($oauth_consumer, $oauth_token, 'GET', 'http://blip.pl/oauth/access_token', array ('oauth_verifier' => $oauth_verifier));
			$oauth_request->sign_request ($oauth_method, $oauth_consumer, $oauth_token);

			$resp = @file_get_contents_curl($oauth_request->to_url());
			parse_str($resp, $resp_parsed);
			
			if(!$resp)
				{
				echo '<center><h2 style="margin-top: 30px;">Informacja</h2>
				
				<p>Wystąpił błąd. Podałeś niepoprawny PIN lub Blip jest przeciążony. Spróbuj jeszcze raz.</p></center>';
				die;
				}

			delete_option('blipbot_tempt');
			delete_option('blipbot_tempts');

			if(get_option('blipbot_token')) {update_option('blipbot_token', $resp_parsed['oauth_token']);}
			else {add_option('blipbot_token', $resp_parsed['oauth_token'], ' ', 'no');}

			if(get_option('blipbot_token_secret')) {update_option('blipbot_token_secret', $resp_parsed['oauth_token_secret']);}
			else {add_option('blipbot_token_secret', $resp_parsed['oauth_token_secret'], ' ', 'no');}

			$oauth_token    = new OAuthToken ($resp_parsed['oauth_token'], $resp_parsed['oauth_token_secret']);
			$blipapi = new BlipApi ($oauth_consumer, $oauth_token);
			$b_read = new BlipApi_User ();
			$response = $blipapi->read ($b_read);
			$body = $response['body'];
			$nick = $body->login;

			if(get_option('blipbot_login')) {update_option('blipbot_login', $nick);}
			else {add_option('blipbot_login', $nick, ' ', 'no');}

			echo '<div id="message" class="updated fade"><p><strong>Token oraz login zostały wypełnione oraz zapisane.</strong></p></div>'; 
			if(get_option('blipbot_password')) { delete_option('blipbot_password'); echo '<div id="message" class="updated fade"><p><strong>Twoje hasło do Blipa (zapisane przez wcześniejszą wersję wtyczki) zostało usunięte z bazy.</strong></p></div>'; }	
			}
		}

	if(isset($_POST['save']))
		{
		if(function_exists('current_user_can') && !current_user_can('manage_options'))
			{
			die('Brak uprawnień');
			}
		else
			{
			if((!empty($_POST['blipbot_login']) && !empty($_POST['blipbot_token'])) || (!empty($_POST['blipbot_consumer']) && !empty($_POST['blipbot_consumer_secret'])) && !empty($_POST['blipbot_wiadomosc']) && !empty($_POST['blipbot_znak']))
				{
				if(get_option('blipbot_password')) {delete_option('blipbot_password'); echo '<div id="message" class="updated fade"><p><strong>Aktualizacja zakończona pomyslnie. Wyrzuciłem Twoje hasło z bazy.</strong></p></div>';}	
					
				if(!empty($_POST['blipbot_consumer']))
					{if(get_option('blipbot_consumer')) {update_option('blipbot_consumer', $_POST['blipbot_consumer']);}
					else{add_option('blipbot_consumer', $_POST['blipbot_consumer'], ' ', 'no');}}
					
				if(!empty($_POST['blipbot_consumer_secret']))
					{if(get_option('blipbot_consumer_secret')) {update_option('blipbot_consumer_secret', $_POST['blipbot_consumer_secret']);}
					else{add_option('blipbot_consumer_secret', $_POST['blipbot_consumer_secret'], ' ', 'no');}}
					
				if($_POST['blipbot_auto']=='on')
					{if(get_option('blipbot_auto')) {update_option('blipbot_auto', 'on');}
					else{add_option('blipbot_auto', 'on', ' ', 'no');}}
				else{if(get_option('blipbot_auto')) {update_option('blipbot_auto', 'off');}
					else{add_option('blipbot_auto', 'off', ' ', 'no');}}
				
				if($_POST['blipbot_zrodlo']=='wewnetrzne')
					{if(get_option('blipbot_zrodlo')) {update_option('blipbot_zrodlo', 'wewnetrzne');}
					else{add_option('blipbot_zrodlo', 'wewnetrzne', ' ', 'no');}}
				else{if(get_option('blipbot_zrodlo')) {update_option('blipbot_zrodlo', 'zewnetrzne');}
					else{add_option('blipbot_zrodlo', 'zewnetrzne', ' ', 'no');}}

				if(isset($_POST['blipbot_wiadomosc']))
					{if(get_option('blipbot_wiadomosc')) {update_option('blipbot_wiadomosc', stripslashes($_POST['blipbot_wiadomosc']));}
					else{add_option('blipbot_wiadomosc', stripslashes($_POST['blipbot_wiadomosc']), ' ', 'no');}}

				if(isset($_POST['blipbot_kom_msg0']))
					{if(get_option('blipbot_kom_msg0')) {update_option('blipbot_kom_msg0', stripslashes($_POST['blipbot_kom_msg0']));}
					else{add_option('blipbot_kom_msg0', stripslashes($_POST['blipbot_kom_msg0']), ' ', 'no');}}

				if(isset($_POST['blipbot_kom_msg1']))
					{if(get_option('blipbot_kom_msg1')) {update_option('blipbot_kom_msg1', stripslashes($_POST['blipbot_kom_msg1']));}
					else{add_option('blipbot_kom_msg1', stripslashes($_POST['blipbot_kom_msg1']), ' ', 'no');}}

				if(isset($_POST['blipbot_kom_msgs']))
					{if(get_option('blipbot_kom_msgs')) {update_option('blipbot_kom_msgs', stripslashes($_POST['blipbot_kom_msgs']));}
					else{add_option('blipbot_kom_msgs', stripslashes($_POST['blipbot_kom_msgs']), ' ', 'no');}}

				if(isset($_POST['blipbot_znak']))
					{if(get_option('blipbot_znak')) {update_option('blipbot_znak', stripslashes($_POST['blipbot_znak']));}
					else{add_option('blipbot_znak', stripslashes($_POST['blipbot_znak']), ' ', 'no');}}

				if(!empty($_POST['blipbot_nick']))
					{if(get_option('blipbot_nick')) {update_option('blipbot_nick', $_POST['blipbot_nick']);}
					else{add_option('blipbot_nick', $_POST['blipbot_nick'], ' ', 'no');}}

				if($_POST['blipbot_kom_0']=='on')
					{if(get_option('blipbot_kom_0')) {update_option('blipbot_kom_0', 'on');}
					else{add_option('blipbot_kom_0', 'on', ' ', 'no');}}
				else{if(get_option('blipbot_kom_0')) {update_option('blipbot_kom_0', 'off');}
					else{add_option('blipbot_kom_0', 'off', ' ', 'no');}}

				if($_POST['blipbot_kom_1']=='on')
					{if(get_option('blipbot_kom_1')) {update_option('blipbot_kom_1', 'on');}
					else{add_option('blipbot_kom_1', 'on', ' ', 'no');}}
				else{if(get_option('blipbot_kom_1')) {update_option('blipbot_kom_1', 'off');}
					else{add_option('blipbot_kom_1', 'off', ' ', 'no');}}
				
				if($_POST['blipbot_kom_s']=='on')
					{if(get_option('blipbot_kom_s')) {update_option('blipbot_kom_s', 'on');}
					else{add_option('blipbot_kom_s', 'on', ' ', 'no');}}
				else{if(get_option('blipbot_kom_s')) {update_option('blipbot_kom_s', 'off');}
					else{add_option('blipbot_kom_s', 'off', ' ', 'no');}}
				
				if($_POST['blipbot_idn']=='on')
					{if(get_option('blipbot_idn')) {update_option('blipbot_idn', 'on');}
					else{add_option('blipbot_idn', 'on', ' ', 'no');}}
				else{if(get_option('blipbot_idn')) {update_option('blipbot_idn', 'off');}
					else{add_option('blipbot_idn', 'off', ' ', 'no');}}
				
				?><div id="message" class="updated fade"><p><strong>Ustawienia zostały zapisane.</strong></p></div><?php 
				}
			else
				{
				?><div id="message" class="updated fade"><p><strong>Musisz wypełnić wszystkie pola (w przypadku loginu oraz tokenu skorzystaj z przycisku autowypełnienia).</strong></p></div><?php 
				}
			}
		}
	
	if(isset($_POST['delete']))
		{
		delete_option('blipbot_login');
		delete_option('blipbot_password');
		delete_option('blipbot_wiadomosc');
		delete_option('blipbot_znak');
		delete_option('blipbot_consumer');
		delete_option('blipbot_consumer_secret');
		delete_option('blipbot_token');
		delete_option('blipbot_token_secret');
		delete_option('blipbot_tempt');
		delete_option('blipbot_tempts');
		delete_option('blipbot_auto');
		delete_option('blipbot_kom_msg0');
		delete_option('blipbot_kom_msg1');
		delete_option('blipbot_kom_msgs');
		delete_option('blipbot_nick');
		delete_option('blipbot_kom_0');
		delete_option('blipbot_kom_1');
		delete_option('blipbot_kom_s');
		delete_option('blipbot_idn');
		delete_option('blipbot_tnij');
		delete_option('blipbot_url');
		delete_option('blipbot_zrodlo');
		
		$blipbot_consumer 			= $blipbot_default_consumer;
		$blipbot_consumer_secret 	= $blipbot_default_consumer_secret;
		
		?><div id="message" class="updated fade"><p><strong>Dane zostały usunięte.</strong></p></div><?php 
		}
	?>
	<div class="wrap">
		<div id="blipbot">
			<h2>Konfiguracja WP BlipBot</h2>

			<form action="options-general.php?page=wp-blipbot/wp-blipbot.php" method="post" id="blipbot_config"> 

			<h3>Informacje o blogu</h3>

			<p style="margin-left: 10px;"><input type='checkbox' name='blipbot_idn' id='blipbot_idn' value='on'<?php if(get_option('blipbot_idn')=='on') echo ' CHECKED'; ?> /> Zaznacz to pole, jeżeli Twój blog jest postawiony na domenie IDN (z Polskimi znakami narodowymi). Więcej o domenach IDN możesz poczytać <a href="http://xn--wicek-k0a.pl/kategoria/domeny-idn" title="IT z ogonkiem - domeny IDN">tutaj</a>.</p>

			<h3>Konto na Blipie</h3>

			<p>Bezpieczne logowanie do Twojego konta na <strong>Blipie</strong> przez <strong>OAuth</strong> (dzięki <a href="http://blip.pl/users/kubofonista/dashboard" style="text-decoration: none;">^Kubofonista</a> [<a style="text-decoration: none;" href="http://kubofonista.net" target="_blank">www</a>]):</p>
			<p><i>Dlaczego jest to bezpieczne? Bo nie podajesz swojego hasła. Token nie umożliwia wejścia na Twoje konto i możesz go w każdej chwili unieważnić usuwając aplikację z <strong>Blipa</strong> (Profil->Aplikacje)</i></p>
			
			<table class="form-table"> 
				<tbody> 
					<tr valign='top'> 
					<th scope='row'>Login:</th> 
					<td><input name='blipbot_login' type='text' id='blipbot_login' value='<?php echo get_option('blipbot_login'); ?>' size='25' readonly/></td> 
				</tr>	

					<tr valign='top'> 
					<th scope='row'>Token:</th> 
					<td><input name='blipbot_token' type='password' id='blipbot_token' value='<?php echo get_option('blipbot_token'); ?>' size='25' readonly/></td> 
				
			</table>
			<p class="submit"><input type="button" value="Kliknij aby wypełnić automatycznie token oraz login" onClick="window.location.href='options-general.php?page=wp-blipbot/wp-blipbot.php&oauth=start';; return false;;"/></p>
			<h3>Dla zaawansowanych (jeśli nie wiesz co to - nie ruszaj)</h3>
			<p>Jeśli nigdy nie zakładałeś własnej aplikacji na <strong>Blipie</strong>, nie zmieniaj poniższych pol.</p>
		
		<table class="form-table"> 
				<tbody> 
					<tr valign='top'> 
					<th scope='row'>Klucz aplikacji:</th> 
					<td><input name='blipbot_consumer' type='text' id='blipbot_consumer' value='<?php echo $blipbot_consumer; ?>' size='20' maxlength='20' style="font-family: monospace;" /></td> 
				</tr>	
					<tr valign='top'> 
					<th scope='row'>Haslo aplikacji:</th> 
					<td><input name='blipbot_consumer_secret' type='password' id='blipbot_consumer_secret' value='<?php echo $blipbot_consumer_secret; ?>' size='40'  maxlength='40' style="font-family: monospace;" /></td> 
				</tr>	
				</tbody>
				</table>
				<p>Jeśli chcesz zmienić (np. aby mieć własne <i>"przez"</i>) to aplikacje załóż według własnego uznania, jednakże ustaw ją jako <i>"poza przeglądarką"</i>.</p>
				<p>Pamiętaj, że zmiana powyższych pól niesie konieczność uaktualnienia tokenu (po wcześniejszym zapisaniu zmiany)!</p>
		
		<h3>Powiadamianie o nowych postach</h3>		
			<p>Zdefiniuj format wysyłanej wiadomości korzystając z następujących tagów:</p>

			<ul style='margin-left: 40px; list-style-type: disc;'>
				<li><b>%tytul%</b> - tytuł posta</li>
				<li><b>%adres%</b> - adres posta</li>
				<li><b>%autor%</b> - autor posta</li>
				<li><b>%tagi%</b> - tagi przypisane do posta</li>
				<li><b>%kategorie%</b> - kategorie, do których został przypisany post</li>
			</ul>

			<table class="form-table"> 
				<tbody>
					<tr valign='top'> 
					<th scope='row'>Treść wiadomości:</th> 
					<td><input name='blipbot_wiadomosc' type='text' id='blipbot_wiadomosc' value='<?php if(get_option('blipbot_wiadomosc')!=''){echo get_option('blipbot_wiadomosc');} else {echo '%tytul% %adres% [tagi: %tagi%]';} ?>' size='60' /></td> 
				</tr>
				<tbody> 
					<tr valign='top'> 
					<th scope='row'>Znak rozdzielający tagi i kat.:</th> 
					<td><input name='blipbot_znak' type='text' id='blipbot_znak' value='<?php if(get_option('blipbot_znak')!=''){echo get_option('blipbot_znak');} else {echo ', ';} ?>' size='2' maxlength ='2' /></td> 
				</tr>
			</table>

			<?php
			global $wpdb;
			$last_post = $wpdb->get_row("SELECT ID FROM $wpdb->posts WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'post' ORDER BY post_date DESC LIMIT 1");
			$last_post_ID = $last_post->ID;
			?>
			<p style='margin: 10px 0 30px 10px;'><input type='checkbox' name='blipbot_auto' id='blipbot_auto' value='on'<?php if(get_option('blipbot_auto')=='on') echo ' CHECKED'; ?> /> Wysyłaj statusy automatycznie po opublikowaniu posta (lub ustawiaj statusy <a href="/wp-admin/edit.php">ręcznie</a>)</p>

			<p>Przykładowa wiadomość będzie wyglądała następująco (zmiany będą widoczne po zapisaniu ustawień):</p>
			<div class="blipek"><a href="http://www.blip.pl/users/<?php echo get_option('blipbot_login'); ?>/dashboard" class="autor"><?php echo get_option('blipbot_login'); ?>:</a> <?php echo BlipBotGen(0); ?></div>

			<h3>Grafiki dołączane do postów</h3>
			<p style="margin-left: 10px;"><input type="radio" name="blipbot_zrodlo" value="wewnetrzne"<?php if(get_option('blipbot_zrodlo')=='wewnetrzne' OR !get_option('blipbot_zrodlo')) echo ' checked'; ?>> Wybieraj grafiki z obrazków dołączonych do posta</p>
			<p style="margin: 0 0 10px 10px;"><input type="radio" name="blipbot_zrodlo" value="zewnetrzne"<?php if(get_option('blipbot_zrodlo')=='zewnetrzne') echo ' checked'; ?>> Ręcznie wpisuj pełny adres URL obrazka (mogą to być obrazki z zewnętrznych serwerów). Funkcja w fazie testów!</p>
			
			<p>Aby było możliwe dodawanie do statusów obrazków z zewnętrznych serwerów, musisz utworzyć pod następującą lokalizacją katalog z prawami dostępu do zapisu:</p>
			
			<?php $temp_dir = $_SERVER[DOCUMENT_ROOT]."/wp-content/wp-blipbot"; ?>
			
			<p style="margin-left: 10px;"><input type="text" readonly value="<?php echo $temp_dir ?>" size="120"/></p>
			<p style="margin-left: 10px;">Status katalogu tymczasowego: <?php if(is_writable($temp_dir)) {echo "<span style='color: darkgreen;'>OK</span>";} else {echo "<span style='color: darkred;'>Brak praw zapisu (lub katalog nie istnieje)</span>";} ?></p>

			<h3>Powiadamianie o nowych komentarzach</h3>

			<p>Dodatkowo <strong>WP BlipBot</strong> może powiadamiać Cię za pośrednictwem prywatnych wiadomości o nowych komentarzach do postów na Twoim blogu. Wystarczy, że wpiszesz swój blipowy nick, oraz zaznaczysz o jakiego rodzaju komentarzach chcesz być powiadamiany.</p>

			<table class="form-table"> 
				<tbody> 
					<tr valign='top'> 
					<th scope='row'>Twój nick:</th> 
					<td><input name='blipbot_nick' type='text' id='blipbot_nick' value='<?php echo get_option('blipbot_nick'); ?>' size='20' /></td> 
				</tr>	
			</table>

			<p>Podobnie jak w przypadku powiadamiania o nowych postach, i tutaj możesz sam zdefiniować treść wiadomości powiadamiających o nowych komentarzach. W treści wiadomości możesz wykorzystać następujące tagi:</p>

			<ul style='margin-left: 40px; list-style-type: disc;'>
				<li><b>%tytul%</b> - tytuł komentowanego postu</li>
				<li><b>%autor%</b> - autor komentarza</li>
				<li><b>%autor_url%</b> - adres strony autora (o ile podał)</li>
				<li><b>%autor_ip%</b> - adres IP autora komentarza</li>
				<li><b>%autor_email%</b> - adres e-mail autora</li>
				<li><b>%url%</b> - bezposredni adres do komentarza w panelu administracyjnym</li>
				<li><b>%url_blog%</b> - bezpośredni adres do komentarza na blogu</li>
			</ul>

			<h4>Komentarze oczekujące na zatwierdzenie</h4>
			<p style='margin: 12px 0 5px 10px;'><input type='checkbox' name='blipbot_kom_0' id='blipbot_kom_0' value='on'<?php if(get_option('blipbot_kom_0')=='on') echo ' CHECKED'; ?> /> powiadamiaj o komentarzach oczekujących na zatwierdzenie</p>

			<table class="form-table"> 
				<tbody>
					<tr valign='top'> 
					<th scope='row'>Treść powiadomienia:</th> 
					<td><input name='blipbot_kom_msg0' type='text' id='blipbot_kom_msg0' value='<?php if(get_option('blipbot_kom_msg0')!=''){echo get_option('blipbot_kom_msg0');} else {echo 'Użytkownik %autor% dodał do wpisu "%tytul%" komentarz, który wymaga Twojego zatwierdzenia %url%';} ?>' size='60' /></td> 
				</tr>
			</table>

			<h4>Komentarze oznaczone jako spam</h4>
			<p style='margin: 12px 0 5px 10px;'><input type='checkbox' name='blipbot_kom_s' id='blipbot_kom_s' value='on'<?php if(get_option('blipbot_kom_s')=='on') echo ' CHECKED'; ?> /> powiadamiaj o komentarzach oznaczonych jako spam</p>
			
			<table class="form-table"> 
				<tbody>
					<tr valign='top'> 
					<th scope='row'>Treść powiadomienia:</th> 
					<td><input name='blipbot_kom_msgs' type='text' id='blipbot_kom_msgs' value='<?php if(get_option('blipbot_kom_msgs')!=''){echo get_option('blipbot_kom_msgs');} else {echo 'Użytkownik %autor% dodał do wpisu "%tytul%" komentarz, który został oznaczony jako spam %url%';} ?>' size='60' /></td> 
				</tr>
			</table>

			<h4>Komentarze dodane do postów</h4>
			<p style='margin: 12px 0 5px 10px;'><input type='checkbox' name='blipbot_kom_1' id='blipbot_kom_1' value='on'<?php if(get_option('blipbot_kom_1')=='on') echo ' CHECKED'; ?> /> Powiadamiaj o komentarzach dodanych do postów</p>

			<table class="form-table">
				<tbody> 
					<tr valign='top'> 
					<th scope='row'>Treść powiadomienia:</th> 
					<td><input name='blipbot_kom_msg1' type='text' id='blipbot_kom_msg1' value='<?php if(get_option('blipbot_kom_msg1')!=''){echo get_option('blipbot_kom_msg1');} else {echo 'Użytkownik %autor% dodał nowy komentarz do wpisu "%tytul%" %url%';} ?>' size='60' /></td> 
				</tr>
			</table>	

			<p class="submit"><input type="submit" name="save" value="Zapisz" /> <input type="submit" name="delete" value="Usuń wszystkie ustawienia" /></p> 
			</form>
			
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
				<p>Jeżeli jesteś zadowolony z funkcjonalności tej wtyczki, to <input type="image" src="../wp-content/plugins/wp-blipbot/postaw_piwo.gif" style="vertical-align: middle;" border="0" name="submit" alt=""> :D Z góry dziękuję!</p>
				<img alt="" border="0" src="https://www.paypal.com/pl_PL/i/scr/pixel.gif" width="1" height="1" style="margin-bottom: 10px;">
			</form>

		</div>
	</div>
	<?php
	}

function BlipBotNotice()
	{
	echo "<div class='error fade' style='background-color: #ff9999;'><p>Wtyczka <strong>WP BlipBot</strong> wymaga ponownej konfiguracji. Przejdź do ustawień wtyczki i skorzystaj z opcji automatycznego uzyskiwania Tokena.</p></div>";
	}

function BlipBotComment($comment_ID)
	{
	global $blipbot_login, $blipbot_pass;
	$blipbot_nick = get_option('blipbot_nick');
	$blipbot_kom_0 = get_option('blipbot_kom_0');
	$blipbot_kom_1 = get_option('blipbot_kom_1');
	$blipbot_kom_s = get_option('blipbot_kom_s');
	$blipbot_idn = get_option('blipbot_idn');

	$comment		= get_comment($comment_ID); 
	$status			= $comment->comment_approved;
	
	if(($status==="0" && $blipbot_kom_0=="on") || ($status==="1" && $blipbot_kom_1=="on") || ($status==="spam" && $blipbot_kom_s=="on"))
		{
		$autor			= $comment->comment_author;
		$autor_url		= $comment->comment_author_url;
		$autor_email	= $comment->comment_author_email;
		$autor_ip		= $comment->comment_author_IP;
		$tresc			= $comment->comment_content;
		$post_ID		= $comment->comment_post_ID;
		$post			= get_post($post_ID);
		$tytul			= $post->post_title;
		
		if($blipbot_idn="on")
			{
			$IDN = new idna_convert();
			$domena = $IDN->decode(get_permalink($post_ID));
			$permalink = $domena."#comment-".$comment_ID;
			}
		else
			{
			$permalink = get_permalink($post_ID)."#comment-".$comment_ID;
			}

		$permalink_pa	= get_settings('home')."/wp-admin/edit-comments.php#comment-".$comment_ID;
		$permalink_pas	= get_settings('home')."/wp-admin/edit-comments.php?comment_status=spam#comment-".$comment_ID;

		if($status==="0" && $blipbot_kom_0=="on")		$wiadomosc = get_option('blipbot_kom_msg0');
		if($status==="1" && $blipbot_kom_1=="on")		$wiadomosc = get_option('blipbot_kom_msg1');
		if($status==="spam" && $blipbot_kom_s=="on")	$wiadomosc = get_option('blipbot_kom_msgs');
		
		$wiadomosc = str_replace('%tytul%',$tytul,$wiadomosc);
		$wiadomosc = str_replace('%autor%',$autor,$wiadomosc);
		$wiadomosc = str_replace('%autor_url%',$autor,$wiadomosc);
		$wiadomosc = str_replace('%autor_email%',$autor_email,$wiadomosc);
		$wiadomosc = str_replace('%autor_ip%',$autor_ip,$wiadomosc);
		if($status==="0" || $status==="1") {$wiadomosc = str_replace('%url%',$permalink_pa,$wiadomosc);} if($status==="spam") {$wiadomosc = str_replace('%url%',$permalink_pas,$wiadomosc);}
		$wiadomosc = str_replace('%url_blog%',$permalink,$wiadomosc);

		if(strlen($wiadomosc)>160)
			{
			$tytul_s = substr($tytul, 0, 160-strlen($wiadomosc)-3)."...";
			$wiadomosc = str_replace($tytul,$tytul_s,$wiadomosc);
			}

		if($wiadomosc!="")
			{
			$oauth_consumer = new OAuthConsumer (get_option('blipbot_consumer'), get_option('blipbot_consumer_secret'));
			$oauth_token    = new OAuthToken (get_option('blipbot_token'), get_option('blipbot_token_secret'));

			//$uchwyt = fopen("/home/services/httpd/xn--wicek-k0a.pl/wp-content/wp-blipbot/gravatar.jpg", 'w');
			//fwrite($uchwyt, file_get_contents("http://www.gravatar.com/avatar.php?gravatar_id=".md5($autor_email)."&size=100"));
			//fclose($uchwyt);

			$blipapi = new BlipApi ($oauth_consumer, $oauth_token);
			$blipapi->uagent = 'WP BlipBot (http://więcek.pl/projekty/wp-blipbot)';
			$privmsg = new BlipApi_Privmsg();
			$privmsg->body = $wiadomosc;
			//$privmsg->image = "/home/services/httpd/xn--wicek-k0a.pl/wp-content/wp-blipbot/gravatar.jpg";
			$privmsg->user = $blipbot_nick;
			try { $blipapi->create($privmsg); } catch (RuntimeException $e) {}
			}
		}
	}

function BlipBotMenu()
	{
	add_options_page('WP BlipBot', 'WP BlipBot', 7, __FILE__, 'BlipBotUstawienia');
	}

function BlipBotHead()
	{
	$baza = get_bloginfo('wpurl');
	echo "<link rel='stylesheet' href='".$baza."/wp-content/plugins/wp-blipbot/blipbot.css?ver=".$wersja."' type='text/css' media='screen' /> ";
	}

function BlipBotFooter()
	{
	$baza = get_bloginfo('wpurl');
	$poid = get_the_ID();
	?>
	<script type='text/javascript'>
	jQuery(
	function()
		{	
		jQuery("div.blipnij").click(
		function()
			{
			var pID = jQuery(this).attr("id");
			jQuery("#"+pID).removeClass("blipnij");
			jQuery("#"+pID).addClass("blipanie");
			jQuery("#"+pID).load("<?php echo $baza ?>/wp-content/plugins/wp-blipbot/blipbot.ajax.php?BlipBotID="+pID);
			});

		jQuery("div#wpblipbot a.f5").click(
		function()
			{
			var pID = jQuery(this).attr("id");
			jQuery("div#wpblipbot #obrazki").load("<?php echo $baza ?>/wp-content/plugins/wp-blipbot/blipbot.ajax.php?ObrazkiID="+pID);
			return false;
			});

		jQuery("div#wpblipbot span#anuluj").click(
		function()
			{
			jQuery("div#wpblipbot span#wybrany").text("");
			jQuery("div#wpblipbot input#wpblipbot-obrazek").attr("value", "");
			jQuery("div#wpblipbot span#anuluj").hide();
			return false;
			});
		
		jQuery("div#wpblipbot a#zmien").click(
		function()
			{
			jQuery("div#wpblipbot span#kiedy").hide();
			jQuery("div#wpblipbot span#spanzmien").hide();
			jQuery("div#wpblipbot span#spanjak").show();
			return false;
			});
		
		jQuery("div#wpblipbot button#wybierzjak").click(
		function()
			{
			var jak = jQuery("div#wpblipbot #wpblipbot-jak").attr("value");
			
			switch (jak)
				{
				case "automatycznie":
				jQuery("div#wpblipbot span#kiedy").text("automatycznie");
				jQuery("div#wpblipbot #wpblipbot-jak").attr("value", "0");
				jQuery("div#wpblipbot span#spanjak").hide();
				jQuery("div#wpblipbot span#kiedy").show();
				jQuery("div#wpblipbot span#spanzmien").show();
				break;
				
				case "recznie":
				jQuery("div#wpblipbot span#kiedy").text("ręcznie");
				jQuery("div#wpblipbot #wpblipbot-jak").attr("value", "1");
				jQuery("div#wpblipbot span#spanjak").hide();
				jQuery("div#wpblipbot span#kiedy").show();
				jQuery("div#wpblipbot span#spanzmien").show();
				break;
				}
			return false;
			});
		});
	</script>
	
	<?php
	}

function BlipBotAddMetaTags($pID)
	{
	if(!wp_is_post_revision($pID) && $_POST['wpblipbot-jak']!="")
		{
		$obrazek		= $_POST['wpblipbot-obrazek'];
		$obrazek_zew	= $_POST['wpblipbot-obrazek-zew'];
		
		if($obrazek!="")
			{
			if(get_post_meta($pID, 'wpblipbot-obrazek'))	{update_post_meta($pID, 'wpblipbot-obrazek', $obrazek);}
			else											{add_post_meta($pID, 'wpblipbot-obrazek', $obrazek, true);}
			}

		if($obrazek_zew!="")
			{
			if(get_post_meta($pID, 'wpblipbot-obrazek-zew'))	{update_post_meta($pID, 'wpblipbot-obrazek-zew', $obrazek_zew);}
			else												{add_post_meta($pID, 'wpblipbot-obrazek-zew', $obrazek_zew, true);}
			}

		$jak = $_POST['wpblipbot-jak'];
		if(get_post_meta($pID, 'wpblipbot-jak')) 		{update_post_meta($pID, 'wpblipbot-jak', $jak);}
		else											{add_post_meta($pID, 'wpblipbot-jak', $jak, true);}
		
		$szablon = $_POST['wpblipbot-szablon'];

		if(get_option('blipbot_wiadomosc')!='')
			{
			$szablon_standardowy = get_option('blipbot_wiadomosc');
			}
		else
			{
			$szablon_standardowy = '%tytul% %adres% [tagi: %tagi%]';
			}
	
		if($szablon!=$szablon_standardowy && $szablon!="")
			{
			if(get_post_meta($pID, 'wpblipbot-szablon')) 		{update_post_meta($pID, 'wpblipbot-szablon', $szablon);}
			else												{add_post_meta($pID, 'wpblipbot-szablon', $szablon, true);}
			}
		}
	}

function BlipBotAddMetaTagsInput() 
	{
	global $post, $wpdb;
	$post_ID = $post;
	if(is_object($post_ID))
		{
		$post_ID = $post->ID;
		}

	$zrodlo = get_option('blipbot_zrodlo');

	if(get_post_meta($post_ID, 'wpblipbot-obrazek'))
		{
		$link		= get_post_meta($post_ID, 'wpblipbot-obrazek', true);
		$tytul		= $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE `guid` LIKE '$link'");
		}

	if(get_post_meta($post_ID, 'wpblipbot-obrazek-zew'))
		{
		$link		= get_post_meta($post_ID, 'wpblipbot-obrazek-zew', true);
		}

	if(get_post_meta($post_ID, 'wpblipbot-jak'))
		{
		$jak		= get_post_meta($post_ID, 'wpblipbot-jak', true);
		
		if($jak=="automatycznie")	$kiedy = "automatycznie";
		if($jak=="recznie") 		$kiedy = "ręcznie";
		}
	else
		{
		if(get_option('blipbot_auto')=="on")
			{
			$jak = "automatycznie";
			$kiedy = "automatycznie";
			}
		else
			{
			$jak = "recznie";
			$kiedy = "ręcznie";
			}
		}
	
	if(get_post_meta($post_ID, 'wpblipbot-szablon'))
		{
		$szablon  = get_post_meta($post_ID, 'wpblipbot-szablon', true);
		}
	else
		{
		if(get_option('blipbot_wiadomosc')!='')
			{
			$szablon = get_option('blipbot_wiadomosc');
			}
		else
			{
			$szablon = '%tytul% %adres% [tagi: %tagi%]';
			}
		}
	
	?>
	<div id='advanced-sortables' class='meta-box-sortables'> 
		<div id="adv-tagsdiv" class="postbox closed"> 
			<div class="handlediv" title="Kliknij aby przełączyć"><br /></div><h3 class='hndle'><span>WP BlipBot</span></h3>
			<div class="inside" id="wpblipbot">
				<div id="postaiosp">

					<h4>Ustawienia statusu</h4>
					<p id="ustawieniastatusu">
						<strong>Wyślij status:</strong> <span id="kiedy"><?php echo $kiedy; ?></span> <span id="spanzmien">(<a href="#" id="zmien">zmień</a>)</span>

						<span id="spanjak" style="display: none; height: 12px;">
							
							<select id="wpblipbot-jak" name="wpblipbot-jak">
								<option value="automatycznie"<?php		if($jak=='automatycznie')		echo ' selected'; ?>>automatycznie</option> 
								<option value="recznie"<?php			if($jak=='recznie')			echo ' selected'; ?>>ręcznie</option> 
							</select>
							
							<button id="wybierzjak">ok</button>
						</span>
					</p>

					<h4>Szablon wiadomości</h4>
					<p>Jeżeli chcesz, możesz zmienić szablon wysyłanej wiadomości dla tego konkretnego posta:</p>
					<input style="margin-left: 20px;" type="text" name="wpblipbot-szablon" id="wpblipbot-szablon" value="<?php echo $szablon; ?>" size="80" />
					
					
					<h4>Dołącz grafikę do statusu</h4>
					
					<?php
					if($zrodlo=="wewnetrzne")
						{ ?>
						<p>Wybierz obrazek, który chcesz dołączyć do statusu (<a href="#" class="f5" id="O<?php echo $post_ID; ?>">odśwież listę załadowanych obrazków</a>):</p>
						<div id="obrazki" style="margin-left: 20px;"></div>
						
						<p style="margin-bottom: 30px;"><strong>Wybrany obrazek:</strong> <span id="wybrany"><?php echo $tytul; ?></span> <span id="anuluj"<?php if($link=='') echo ' style="display: none;"'; ?>>(<a href="#" title="anuluj">anuluj</a>)</span></p>
						
						<input type="hidden" name="wpblipbot-obrazek" id="wpblipbot-obrazek" value="<?php echo $link; ?>" />
						<?php }
					
					if($zrodlo=="zewnetrzne")
						{ ?>
						<p>Podaj adres obrazka umieszczonego na zewnętrznym serwerze:<br />
						<input style="margin-left: 20px;" type="text" value="<?php echo $link; ?>" id="wpblipbot-obrazek-zew" name="wpblipbot-obrazek-zew" size="80" /></p>
						<?php }
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
	}

function BlipBotColumn($defaults)
	{
	$defaults['blipbot'] = __('Blip');
    return $defaults;
	}

function BlipBotColumnIn($column_name, $id)
	{
	if($column_name=='blipbot')
		{
		if(get_post_status($id)=='publish')
			{
			$ostatnio	= get_post_meta($id, 'wpblipbot-last', true);
			$kolejny	= $ostatnio+604800;
			$aktualny	= time();
			
			if($kolejny<=$aktualny)	{ ?><div class='blipnij' title='Blipnij' id='B<?php echo $id; ?>'>&nbsp;</div><?php }
			else						{ ?><div class='blipniety' title='Status wysłany' id='B<?php echo $id; ?>'>&nbsp;</div><?php }
			}
		}
	}

if(!get_option('blipbot_token') AND $_GET['page']!="wp-blipbot/wp-blipbot.php")
	{
	add_action('admin_notices', 'BlipBotNotice');
	}

add_action('admin_head', 'BlipBotHead');
add_action('admin_footer', 'BlipBotFooter');
add_filter('manage_posts_columns', 'BlipBotColumn');
add_action('manage_posts_custom_column', 'BlipBotColumnIn', 10, 2);
add_action('admin_menu','BlipBotMenu');
add_action('edit_form_advanced', 'BlipBotAddMetaTagsInput');
add_action('edit_page_form', 'BlipBotAddMetaTagsInput');

add_action('edit_post', 'BlipBotAddMetaTags');
add_action('publish_post', 'BlipBotAddMetaTags');
add_action('save_post', 'BlipBotAddMetaTags');
add_action('edit_page_form', 'BlipBotAddMetaTags');

add_action('BlipBotSendHook','BlipBotSendCheck', 10, 1);
add_action('comment_post', 'BlipBotComment');
add_action('publish_post','BlipBotSend');
?>
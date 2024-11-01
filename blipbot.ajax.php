<?php
require_once(dirname(__FILE__).'/../../../wp-config.php');

set_include_path(get_include_path() . PATH_SEPARATOR . dirname (__FILE__));

if(!class_exists('OAuthException'))
	{
	require_once(dirname(__FILE__).'/OAuth.php');
	}

require_once(dirname(__FILE__).'/blipapi.php');
require_once(dirname(__FILE__).'/blipapi_picture.php');
require_once(dirname(__FILE__).'/blipapi_privmsg.php');
require_once(dirname(__FILE__).'/blipapi_status.php');
require_once(dirname(__FILE__).'/idna.php');

$blipbot_login = get_option('blipbot_login');

function BlipBotGen($pID)
	{
	global $blipbot_login, $blipbot_pass, $wpdb;
	if($pID>0)
		{$w = $wpdb->get_row("SELECT ID,post_title,post_date,post_modified FROM $wpdb->posts WHERE `ID` LIKE '".$pID."'");}
	else
		{$w = $wpdb->get_row("SELECT ID,post_title FROM $wpdb->posts WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'post' ORDER BY post_date DESC LIMIT 1");}

	if(get_option('blipbot_znak')!=''){$znak=get_option('blipbot_znak');} else {$znak=', ';}

	$zc=0; $zt=0;
	$pobierz_wt = $wpdb->get_results("SELECT p.taxonomy AS ptaxonomy, t.slug AS tslug FROM $wpdb->terms AS t, $wpdb->term_relationships AS r, $wpdb->term_taxonomy AS p WHERE r.object_id=".$w->ID." AND r.term_taxonomy_id LIKE p.term_taxonomy_id AND t.term_id LIKE p.term_id AND (p.taxonomy LIKE 'post_tag' OR p.taxonomy LIKE 'category')");
	foreach($pobierz_wt as $wt)
		{
		if($wt->ptaxonomy == 'category')
			{
			if($zc>0)$rc=$znak;
			$kategoria = explode('-',$wt->tslug);
			$kategorie .= $rc."#".$kategoria[0];
			$kategorie0 .= $rc."<a href='http://www.blip.pl/tags/".$kategoria[0]."' title='".$kategoria[0]."'>#".$kategoria[0]."</a>";
			$zc++;
			}
		if($wt->ptaxonomy == 'post_tag')
			{
			if($zt>0)$rt=$znak;
			$tagi .= $rt."#".$wt->tslug;
			$tagi0 .= $rt."<a href='http://www.blip.pl/tags/".$wt->tslug."' title='".$wt->tslug."'>#".$wt->tslug."</a>";
			$zt++;
			}
		}

	if(get_post_meta($pID, 'wpblipbot-szablon'))
		{
		$blipbot_wiadomosc  = get_post_meta($pID, 'wpblipbot-szablon', true);
		}
	else
		{
		if(get_option('blipbot_wiadomosc')!='')
			{
			$blipbot_wiadomosc = get_option('blipbot_wiadomosc');
			}
		else
			{
			$blipbot_wiadomosc = '%tytul% %adres% [tagi: %tagi%]';
			}
		}

	if(get_option('blipbot_idn')=="on")
		{
		$IDN = new idna_convert();
		$link = $IDN->decode(get_permalink($w->ID));
		}
	else
		{
		$link = get_permalink($w->ID);
		}

	$link0 = "<a href='".$link."' title='".$link."'>[link]</a>";

	$get_post = get_post($w->ID); 
	$autor_id = $get_post->post_author;

	$wu = $wpdb->get_row("SELECT ID,display_name FROM $wpdb->users WHERE `ID` LIKE '".$autor_id."'");

	$wiadomosc = str_replace('%tytul%',$w->post_title,$blipbot_wiadomosc);
	$wiadomosc = str_replace('%adres%',$link,$wiadomosc);
	$wiadomosc = str_replace('%autor%',$wu->display_name,$wiadomosc);
	$wiadomosc = str_replace('%kategorie%',$kategorie,$wiadomosc);
	$wiadomosc = str_replace('%tagi%',$tagi,$wiadomosc);

	$wiadomosc0 = str_replace('%tytul%',$w->post_title,$blipbot_wiadomosc);
	$wiadomosc0 = str_replace('%adres%',$link0,$wiadomosc0);
	$wiadomosc0 = str_replace('%autor%',$wu->display_name,$wiadomosc0);
	$wiadomosc0 = str_replace('%kategorie%',$kategorie0,$wiadomosc0);
	$wiadomosc0 = str_replace('%tagi%',$tagi0,$wiadomosc0);
	
	$dlugosc_wiadomosci = strlen($wiadomosc)-strlen($link)+21;;
	
	if($dlugosc_wiadomosci>160)
		{
		$tytul = substr($w->post_title, 0, 160-$dlugosc_wiadomosci-3)."...";
		$wiadomosc = str_replace($w->post_title,$tytul,$wiadomosc);
		$wiadomosc0 = str_replace($w->post_title,$tytul,$wiadomosc0);
		}
	if($pID>0) return $wiadomosc; else return $wiadomosc0;
	}

function BlipBotSendCheck($post_ID)
	{
	return BlipBotSend($post_ID);
	}

function BlipBotSend($post_ID)
	{
	if($post_ID[0]=="B")
		{?>
		<script type='text/javascript'>
			jQuery(document).ready(function()
				{
				jQuery("#<?php echo $post_ID; ?>").removeClass("blipanie"); 
				jQuery("#<?php echo $post_ID; ?>").addClass("blipniety");
				});
		</script>
		<?php }

	global $current_user;
	get_currentuserinfo();
	
	$user_LE		= $current_user->user_level;
	$post_ID		= str_replace("B", "", $post_ID);
	$post 			= get_post($post_ID);
	$post_date 		= $post->post_date;
	$post_modified	= $post->post_modified;
	$post_ID		= $post->ID;

	$jak	= get_post_meta($post_ID, 'wpblipbot-jak', true);
	$last	= get_post_meta($id, 'wpblipbot-last', true);
	
	$next	= $ostatnio+604800;
	$czas	= time();
	

	if(((($jak == "automatycznie") && ($post_date >= $post_modified)) || isset($_GET['BlipBotID'])) && $user_LE>=2)
		{
		global $blipbot_login, $blipbot_pass, $wpdb;

		$content = BlipBotGen($post_ID);
		
		$oauth_consumer = new OAuthConsumer (get_option('blipbot_consumer'), get_option('blipbot_consumer_secret'));
		$oauth_token    = new OAuthToken (get_option('blipbot_token'), get_option('blipbot_token_secret'));

		$blipapi = new BlipApi ($oauth_consumer, $oauth_token);

		$blipapi->uagent = 'WP BlipBot (http://więcek.pl/projekty/wp-blipbot)';

		$status = new BlipApi_Status();

		$obrazek		= get_post_meta($post_ID, 'wpblipbot-obrazek', true);
		$obrazek_zew	= get_post_meta($post_ID, 'wpblipbot-obrazek-zew', true);

		if($obrazek!=null && get_option('blipbot_zrodlo')=="wewnetrzne")
			{
			$post_name		= $post->post_name;
			$baza			= get_option('home');
			$dysk			= $_SERVER[DOCUMENT_ROOT];
			
			$lokalizacja	= str_replace($baza, $dysk, $obrazek);

			$status->body = $content;
			$status->image = $lokalizacja;
			
			$obr = 1;
			}
		
		if($obrazek_zew!=null && get_option('blipbot_zrodlo')=="zewnetrzne")
			{
			$dysk			= $_SERVER[DOCUMENT_ROOT];

			$lokalizacja	= $dysk."/wp-content/wp-blipbot/post.jpg";
			
			$uchwyt = fopen($lokalizacja, 'w');
			fwrite($uchwyt, file_get_contents($obrazek_zew));
			fclose($uchwyt);
			
			$status->body = $content;
			$status->image = $lokalizacja;
			
			$obr = 1;
			}

		if($obr!=1)
			{
			$status->body = $content;
			}

		try {
		$blipapi->create ($status);
			
		if(get_post_meta($post_ID, 'wpblipbot-last'))
			{
			update_post_meta($post_ID, 'wpblipbot-last', time());
			}
		else
			{
			add_post_meta($post_ID, 'wpblipbot-last', time(), true);
			}
			
		} catch (RuntimeException $e) {}
			
		}
	}

function ObrazkiID($post_ID)
	{
	global $wpdb;
	$post_ID		= str_replace("O", "", $post_ID);

	if($post_ID=="0")
		{
		echo "<p style='color: #d00000; margin: 20px 0;'>Aby wyświetlić listę załadowanych obrazków, musisz najpierw zapisać szkic!<br />(w przyszłych wersjach postaram się wyeliminować tą niedogodność).</p>";
		}
	else
		{
		$attachments = get_children(array('post_parent' => $post_ID, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));

		if(empty($attachments))
			{
			echo "<p style='color: #d00000; margin: 20px 0;'>Nie znalazłem żadnych obrazków załadowanych do tego postu :( Ależ mi głupio...</p>";
			return "";
			}

		$itemtag = tag_escape($itemtag);
		$captiontag = tag_escape($captiontag);
		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		
		?>
		<script type='text/javascript'>
		jQuery(
		function()
			{	
			jQuery("div#obrazki a").click(
			function()
				{
				var title = jQuery(this).attr("title");
				var link = jQuery(this).attr("href");
				jQuery("span#wybrany").text(title);
				jQuery("input#wpblipbot-obrazek").attr("value", link);
				jQuery("span#anuluj").show();
				return false;
				});
			});
		</script>
		<?php
		foreach($attachments as $post_ID => $attachment)
			{
			echo wp_get_attachment_link($post_ID, 'thumbnail', false, false);
			}

		if(get_post_meta($post_ID, 'obrazek-na-blipa'))
			{
			$link		= get_post_meta($post_ID, 'obrazek-na-blipa', true);
			$tytul		= $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE `guid` LIKE '$link'");
			}
		}
	}

if(isset($_GET['BlipBotID']))
	{
	BlipBotSend($_GET['BlipBotID']);
	}

if(isset($_GET['ObrazkiID']))
	{
	ObrazkiID($_GET['ObrazkiID']);
	}
?>
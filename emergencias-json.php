<?php
/*
Plugin Name: Emergencias JSON
Plugin URI: https://github.com/culturagovbr/emergencias-json
Description: Plugin que implementa a geração de um json da programação do Emergencias, para ser consumido por aplicações externas. Ele funciona em cima do tema do Emergencias para WP, que não está disponível no github 
Author: leogermani
Version: 0.1
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


class Emergencias_JSON {
	
	var $languages = array('pb', 'en', 'es');
	
	function Emergencias_JSON() {
	
		add_action('save_post', array(&$this, 'hook_save_post'));
	
	
	}
	
	function get_dir() {
		$up = wp_upload_dir();
		$folderpath = $up['basedir'] . '/json/';
		
		if (!file_exists($folderpath)) {
			
			if (!mkdir($folderpath, 0777)) {
				
				add_action('admin_notices', array(&$this, 'add_folder_error_message'));
				
				return false;
				
			}
		}
		
		return $folderpath;
		
	}
	
	function add_folder_error_message() {
		?>
		<div class="error notice">
        <p>ERRO: O plugin Emergencias JSON não conseguiu criar a pasta para salvar os arquivos</p>
		</div>
		<?php
	}
	
	function hook_save_post( $post_id ) {
		
		global $post;

        if ( wp_is_post_revision( $post_id ) )
            return;

        // Verifica o autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		
		if ( $post->post_type == 'session' ) {
			
			$this->generate_sessions_json();
			$this->generate_locations_json();
			
		}
		
		if ( $post->post_type == 'speaker' ) {
			
			$this->generate_speakers_json();
			
		}
        
		
	}
	
	function savejson($type, $language, $data) {
		
		$folder = $this->get_dir();
		
		if (!$folder)
			return false;
		
		$filename = $folder . $type . '-' . $language . '.json';
		$json = json_encode($data);
		
		$fh = fopen($filename, 'w');

		fwrite($fh, $json);
		fclose($fh);
		
	}
	
	function generate_sessions_json() {
	
		// Preferi fazer uma consulta direto ao banco, pq se usar o WP_query e as funções nativas
		// o QTranslate filtra tudo e não consigo pegar os conteúdos das diferentes linguagens
		
		// o Q translate é um plugin medonho. Ele grava todas as traduções de um campo no proprio campo, dividido por uma tag. ex:
		// [:pb]conteudo em portugues[:en]conteudo em ingles[:es]conteudo em espanhol[:]
		//
		// E filtra o q vai exibir dependendo da URL.. não tem um jeito muito bom de recuperar isso por outras funções... 
		// May the force be with us...
		
		// E aqui um pequeno esquema pra, vai que alguém decide não usar mais o QTranslate, esse plugin continua funcionando:
		if (!function_exists('qtranxf_use')) {
			function qtranxf_use($x = false, $text, $y = false) {
				return $text;
			}
		}
		
		
		
		
		global $wpdb;
		
		$q = "SELECT ID, post_title, post_content, post_excerpt FROM $wpdb->posts WHERE post_type = 'session' AND post_status = 'publish'";
		
		$posts = $wpdb->get_results($q);
		
		$resutls = array();
		foreach ($this->languages as $l)
			$results[$l] = array();

		
		foreach ($posts as $post) {
			
			// Pegamos todos os metadados
			$post_id = $post->ID;
			
			$date = get_post_meta($post_id, 'session_date', true);
			if ($date)
				$date = date('Y-m-d', $date); // o tema salva a data em timestamp, então convertemos

			$startTime = get_post_meta($post_id, 'session_time', true);
			$endTime = get_post_meta($post_id, 'session_end_time', true);
			
			$registration_code  = get_post_meta($post_id, 'session_registration_code', true);
			$registration_code_title  = get_post_meta($post_id, 'session_registration_title', true);
			$registration_code_text  = get_post_meta($post_id, 'session_registration_text', true);
			
			$timestamp = strtotime($date . ' ' . $startTime); // criamos um timestamp considerando também o horário
			
			$speakers = get_post_meta($post_id, 'session_speakers_list', true);
			
			
			//pegamoso ID do primeiro termo da taxonomia de lugares
			$lugar = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_relationships 
				WHERE object_id = $post->ID AND term_taxonomy_id IN (
					SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'session-location'
				) LIMIT 1");
			////////////
			
			
			// Taxonomias
			$types = wp_get_post_terms( $post->ID, 'session-type' );
			$tracks = wp_get_post_terms( $post->ID, 'session-track' );
			////////////
			
			//Imagens
			$thumb_id = get_post_thumbnail_id( $post->ID );
			
			$defaultImage = '';
			$defaultImageThumb = '';
			
			if ($thumb_id) {
				$img = wp_get_attachment_image_src($thumb_id);
				
				if (is_array($img) && isset($img[0]))
					$defaultImageThumb = $img[0];
				
				$img = wp_get_attachment_image_src($thumb_id, 'full');
				if (is_array($img) && isset($img[0]))
					$defaultImage = $img[0];
				
			}
			////////////
			
			
			// Comecamos a montar o array
			$r = array();
			
			$r['id'] = $post->ID;
		    $r['spaceId'] = $lugar;
		    $r['startsAt'] = $startTime;
		    $r['endsAt'] = $endTime;
		    $r['startsOn'] = $date;
		    $r['timestamp'] = $timestamp;
		    $r['defaultImage'] = $defaultImage;
		    $r['defaultImageThumb'] = $defaultImageThumb;
		    $r['registration_code'] = $registration_code;
		    $r['registration_code_title'] = $registration_code_title;
		    $r['registration_code_text'] = $registration_code_text;
		    
		    $r['terms'] = array();
		    
		    $r['speakers'] = array();
		    if (is_array($speakers)) {
				foreach ($speakers as $speaker)
					$r['speakers'][] = $speaker;
			}
		    
		    // tratamos os campos traduzíveis
		    foreach ($this->languages as $l) {
				
				$res = $r;
				$res['name'] = qtranxf_use($l, $post->post_title,false);
				$res['shortDescription'] = qtranxf_use($l, $post->post_excerpt,false);
				$res['description'] = qtranxf_use($l, $post->post_content,false);
				
				//tracks
				$res['terms']['tracks'] = array();
				if (is_array($tracks)) {
					foreach($tracks as $track) {
						if (is_object($track) && isset($track->name)) {
							$term_name = $track->name;
							
							//Ah, como eu amo o QTranslate
							$term_translations = get_option('qtranslate_term_name');
							if (is_array($term_translations) && isset($term_translations[$term_name]) && is_array($term_translations[$term_name]) && isset($term_translations[$term_name][$l])) {
								$res['terms']['tracks'][] = $term_translations[$term_name][$l];
							} else {
								$res['terms']['tracks'][] = $term_name;
							}
						}
					}
				}
				
				//types
				$res['terms']['types'] = array();
				if (is_array($types)) {
					foreach($types as $type) {
						if (is_object($type) && isset($type->name)) {
							$term_name = $type->name;
							
							//Ah, como eu amo o QTranslate
							$term_translations = get_option('qtranslate_term_name');
							if (is_array($term_translations) && isset($term_translations[$term_name]) && is_array($term_translations[$term_name]) && isset($term_translations[$term_name][$l])) {
								$res['terms']['types'][] = $term_translations[$term_name][$l];
							} else {
								$res['terms']['types'][] = $term_name;
							}
						}
					}
				}
				
				
				
				array_push($results[$l], $res);
				
				
				
			} //foreach language
		    
		    
		    
		} //foreach post
		
		
		foreach ($this->languages as $l) {
			$this->savejson('events', $l, $results[$l]);
		}
		
		
	
	
	}
	
	function generate_speakers_json() {
		
		// veja e explicacao na generate_sessions_json
		if (!function_exists('qtranxf_use')) {
			function qtranxf_use($x = false, $text, $y = false) {
				return $text;
			}
		}

		global $wpdb;
		
		$q = "SELECT ID, post_title, post_content, post_excerpt FROM $wpdb->posts WHERE post_type = 'speaker' AND post_status = 'publish'";
		
		$posts = $wpdb->get_results($q);
		
		$resutls = array();
		foreach ($this->languages as $l)
			$results[$l] = array();

		
		foreach ($posts as $post) {
		
			//meta dados
			$speaker_keynote = get_post_meta($post->ID, 'speaker_keynote', true);
			$speaker_title = get_post_meta($post->ID, 'speaker_title', true);
			
			//Imagens
			$thumb_id = get_post_thumbnail_id( $post->ID );
			
			$defaultImage = '';
			$defaultImageThumb = '';
			
			if ($thumb_id) {
				$img = wp_get_attachment_image_src($thumb_id);
				
				if (is_array($img) && isset($img[0]))
					$defaultImageThumb = $img[0];
				
				$img = wp_get_attachment_image_src($thumb_id, 'full');
				if (is_array($img) && isset($img[0]))
					$defaultImage = $img[0];
				
			}
			////////////
			
			
			
			
			// Comecamos a montar o array
			$r = array();
			
			$r['id'] = $post->ID;
		    $r['defaultImage'] = $defaultImage;
		    $r['defaultImageThumb'] = $defaultImageThumb;
		    $r['speaker_keynote'] = $speaker_keynote;
		    $r['speaker_title'] = $speaker_title;
		    
		    
		    // tratamos os campos traduzíveis
		    foreach ($this->languages as $l) {
				
				$res = $r;
				$res['name'] = qtranxf_use($l, $post->post_title,false);
				$res['shortDescription'] = qtranxf_use($l, $post->post_excerpt,false);
				$res['description'] = qtranxf_use($l, $post->post_content,false);
				
				array_push($results[$l], $res);
				
				
				
			} //foreach language
			
		
		}// foreach post
		
		foreach ($this->languages as $l) {
			$this->savejson('speakers', $l, $results[$l]);
		}
		
	}
	
	function generate_locations_json() {
		
		// veja e explicacao na generate_sessions_json
		if (!function_exists('qtranxf_use')) {
			function qtranxf_use($x = false, $text, $y = false) {
				return $text;
			}
		}
		
		$terms = get_terms('session-location', array(
			'hide_empty' => false
		));
		
		
		$resutls = array();
		foreach ($this->languages as $l)
			$results[$l] = array();
		
		
		
		
		foreach ($this->languages as $l) {
			
			if (is_array($terms)) {

				
				foreach($terms as $term) {
					
					$r = array();
					
					
					
					if (is_object($term) && isset($term->name)) {
						$term_name = $type->name;
						
						//Ah, como eu amo o QTranslate
						$term_translations = get_option('qtranslate_term_name');
						if (is_array($term_translations) && isset($term_translations[$term_name]) && is_array($term_translations[$term_name]) && isset($term_translations[$term_name][$l])) {
							$res['terms']['types'][] = $term_translations[$term_name][$l];
						} else {
							$res['terms']['types'][] = $term_name;
						}
						
						$r['id'] = $term->term_taxonomy_id;
						$r['name'] = $term_name;
						$r['shortDescription'] = qtranxf_use($l, $term->description, false);
						
						array_push($results[$l], $r);

					}
					
				} // foreach term
				
			}
			
		} //foreach language
		
		foreach ($this->languages as $l) {
			$this->savejson('spaces', $l, $results[$l]);
		}
	
	}

	
}

function Emergencias_JSON_init() {
	global $EmergenciasJSON;
	$EmergenciasJSON = new Emergencias_JSON();
}
add_action( 'admin_init', 'Emergencias_JSON_init' );

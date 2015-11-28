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
			
		}
		
		if ( $post->post_type == 'speaker' ) {
			
			$this->generate_speakers_json();
			
		}
        
        
        
		
	}
	
	function generate_sessions_json() {
	
	}
	
	function generate_speakers_json() {
		
	}

	
}

function Emergencias_JSON_init() {
	global $EmergenciasJSON;
	$EmergenciasJSON = new Emergencias_JSON();
}
add_action( 'admin_init', 'Emergencias_JSON_init' );

<?php

/**
 * Plugin Name:       Comments with files
 * Plugin URI:        http://www.samy.kantari.fr/
 * Description:       Add attachments in comments.
 * Version:           1.0.0
 * Author:            Kantari Samy, Developer Back @ Whodunit
 * Author URI:        http://www.kantari.fr/
 * Contributors:      whodunitagency, leprincenoir
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/**
 * DEFINES
 */
define( 'Comments_With_Files'          , '1.0.0' );
define( 'Comments_With_Files_FILE'     , __FILE__ );


if ( ! class_exists( 'Comments_With_Files' ) ) :

    class Comments_With_Files {
        protected $plugin_name;
        protected $version;
        protected $max = 50;

        protected static $instance = null;

        public function __construct() {

            $this->plugin_name = 'comments-with-files';
            $this->version     = Comments_With_Files;


            /**
             * Vérification des champs
             */
            add_filter('preprocess_comment', array($this, 'checking_fields'));


            /**
             * Correction balise form
             */
            add_action('comment_form_top', array($this, 'form_tag_correction'));

            /**
             * Ajout champ
             */
            add_action('comment_form_after_fields', array($this, 'additional_fields'));
            add_action('comment_form_logged_in_after', array($this, 'additional_fields'));


            /**
             * Save
             */
            add_action('comment_post', array($this, 'save_comment_meta_data'));
            add_action('delete_comment', array($this, 'delete_attachments'));


            /**
             * Affichage FO
             */
            add_filter('comment_text', array($this, 'modify_comment_front'));
        }

        public function delete_attachments($comment_id)
        {
            $attachment_ids = get_comment_meta($comment_id, 'attachment_id');
            if(!empty($attachment_ids) && count ($attachment_ids) > 0){
                foreach ($attachment_ids as $attachment_id) {
                    wp_delete_attachment($attachment_id, TRUE);
                }
            }
        }

        /**
         * On ferme la balise form par défaut et on recrée la notre avec enctype="multipart/form-data"
         */
        public function form_tag_correction($args)
        {
            echo '</form><form action="'. get_home_url() .'/wp-comments-post.php" method="POST" enctype="multipart/form-data" id="attachment_form" class="comment-form" novalidate>';
        }


        /**
         * On ajoute les pièces jointes à chaque commentaire
         */
        public function modify_comment_front($text) {

            $attachment_ids = get_comment_meta(get_comment_ID(), 'attachment_id'); // modification meta
            if(!empty($attachment_ids) && count ($attachment_ids) > 0){
                $add = '<ul>';
                foreach ($attachment_ids as $attachment_id) {
                    $attachment_link = wp_get_attachment_url($attachment_id);
                    $attachment_name = basename(get_attached_file($attachment_id));

                    $add .= '<li><a class="attachmentLink" target="_blank" href="' . $attachment_link . '" title="Download: ' . $attachment_name . '">';
                    $add .= '<strong>'.$attachment_name.'</strong> ';
                    $add .= '</a></li>';

                }
                $add .= '</ul>';

                $text =  $add . $text;
            }

            return $text;
        }


        public function checking_fields($commentdata) {

            if (isset($_FILES['attachments']) && count($_FILES['attachments']) > 0) {
                $errors = '';
                $errors_code = '';
                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    $code = $_FILES['attachments']['error'][$key];

                    switch ($code) {
                        case 0:
                            $text = "";
                            break;
                        case 1:
                            $text = __('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                            break;
                        case 2:
                            $text = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                            break;
                        case 3:
                            $text = __('The uploaded file was only partially uploaded.');
                            break;
                        case 4:
                            $text = __('No file was uploaded.');
                            break;
                        case 6:
                            $text = __('Missing a temporary folder. Introduced in PHP 5.0.3.');
                            break;
                        case 7:
                            $text = __('Failed to write file to disk. Introduced in PHP 5.1.0.');
                            break;
                        case 8:
                            $text = __('A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.');
                            break;


                        default:
                            $text = __('Unknown error.');
                    }

                    if($text != '') {
                        $errors_code .= '<li>'.$name.' : '.$text.'</li>';
                        continue;
                    }



                    $fileInfo = pathinfo($name);
                    $fileExtension = strtolower($fileInfo['extension']);

                    if (function_exists('finfo_file')) {
                        $fileType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['attachments']['tmp_name'][$key]);
                    } elseif (function_exists('mime_content_type')) {
                        $fileType = mime_content_type($_FILES['attachments']['tmp_name'][$key]);
                    } else {
                        $fileType = $_FILES['attachments']['type'][$key];
                    }
                    $tmp = '';

                    if(!in_array($fileType, $this->get_file_mime_types())) {
                        $tmp .= '<strong>'.$fileExtension.' - '.__("Extension is not allowed").'</strong>';
                    }


                    if($_FILES['attachments']['size'][$key] > ($this->max * 1048576)) {
                        if($tmp != '') {
                            $tmp .= '<br>';
                        }
                        $tmp .= '<strong>'.$name.' - ' . __("The file size is too big") . ' (max : '.$this->max.'MB)</strong>';
                    }

                    if($tmp != '') {
                        $errors .= '<li>' . $tmp . '</li>';
                    }

                }

                if ($errors_code != '') {
                    $errors_code = '<ul>'.$errors_code.'</ul><a href="javascript:history.go(-1)">'.__("Back").'</a>';
                    wp_die($errors_code);
                }

                if ($errors != '') {
                    $errors = '<ul>'.$errors.'</ul><a href="javascript:history.go(-1)">'.__("Back").'</a>';
                    wp_die($errors);
                }
                // error 4 is actually empty file mate
            }


            return $commentdata;
        }

        public function add_attachment($fileHandler, $postId)
        {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
            return media_handle_upload($fileHandler, $postId);
        }

        public function save_comment_meta_data( $comment_id ) {

            if ( count($_FILES['attachments']) > 0 ) {
                $files = $_FILES["attachments"];
                $bindId = $_POST['comment_post_ID'];
                foreach ($files['name'] as $key => $value) {
                    if ($files['name'][$key]) {
                        $file = array(
                            'name' => $files['name'][$key],
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key]
                        );
                        $_FILES = array ("attachments" => $file);
                        foreach ($_FILES as $file => $array) {
                            $attachId = $this->add_attachment($file, $bindId);
                            add_comment_meta($comment_id, 'attachment_id', $attachId);
                        }
                    }
                }
                unset($_FILES);
            }
        }


        /**
         * Type de fichiers autorisé
         */
        public function get_file_mime_types() {
            $types = array(
                //'text/plain', // txt, html, php, css

                // ms office
                'application/msword', // doc
                'application/vnd.ms-excel', // xls
                'application/vnd.ms-powerpoint', // ppt

                // ms office
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
                'application/vnd.openxmlformats-officedocument.presentationml.presentation', // pptx

                // open office
                'application/vnd.oasis.opendocument.text', // odt
                'application/vnd.oasis.opendocument.spreadsheet', // ods

                // adobe
                'application/pdf',

                // images
                'image/gif',
                'image/jpeg',
                'image/png',
            );
            return apply_filters( 'filter_get_file_mime_types', $types ); // hook si on souhaite modifier cette liste
        }


        public function additional_fields() {
            echo '<p class="comment-form-attachments">'.
                '<label for="attachments">' . __( 'Attachments' ) . '</label>'.
                '<input id="attachments" name="attachments[]" multiple type="file" /></p>';
        }

        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Comments_With_Files::get_instance();

endif;
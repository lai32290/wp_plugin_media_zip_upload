<?php
/*
Plugin Name: Media Unzip
Author: LXuancheng
License: GPL2
Version: 0.0.1
Text Domain: media_unzip
Description: This is a plugin to upload medias in lot
 */

if(!defined("ABSPATH")) exit;

class MediaUnzip {
    public static $text_domain = "media_unzip";
    public static $instance;

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    public function __construct() {
        add_action('admin_menu', [$this, 'save_admin_page']);
        add_action('admin_menu', [$this, 'create_admin_menu']);
    }

    public function create_admin_menu() {
        add_menu_page('Upload Media Zip', 'Upload Media Zip', 'manage_options',
            'upload_media_zip', [$this, 'create_admin_page'], 'dashicons-images-alt2', 10);
    }

    public function verify_file_types($filetype) {
        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];

        return in_array($filetype, $allowed_types);
    }

    public function save_admin_page() {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if(isset($_FILES['fileToUpload'])) {
            $dir = wp_upload_dir()['path'];

            $target_file = $dir . '/' . basename($_FILES['fileToUpload']['name']);

            move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file);

            $file_name = basename($_FILES['fileToUpload']['name']);


            $zip = new ZipArchive();
            $res = $zip->open($target_file);

            if($res == true) {
                echo "<h3>O arquivo zip foi descompactado.</h3>";
                $zip->extractTo($dir);

                echo "<h3>Arquivo descompactado com sucesso! " . wp_upload_dir()['url'] . "</h3>";

                echo "Tem " . $zip->numFiles . " arquivos neste arquivo zip.<br>";

                for($i = 0 ; $i < $zip->numFiles; $i++) {
                    $media_file_name = wp_upload_dir()['url'] . '/' . $zip->getNameIndex($i);

                    $filetype = wp_check_filetype(basename($media_file_name), null);
                    $allowed = $this->verify_file_types($filetype['type']);

                    if($allowed) {
                        echo "<a href='" . $media_file_name . "' target='_blank'>" . $media_file_name . "</a><br>";

                        $attachment = [
                            'guid' => $media_file_name,
                            'post_mim_type' => $filetype['type'],
                            'post_title' => preg_replace("/\.[^.]+$/", '', $zip->getNameIndex($i)),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        ];

                        $attach_id = wp_insert_attachment($attachment, $dir . '/' . $zip->getNameIndex($i));

                        $attach_data = wp_generate_attachment_metadata($attach_id, $dir . '/' . $zip->getNameIndex($i));
                        wp_update_attachment_metadata($attach_id, $attach_data);
                    }
                    else {
                        echo $zip->getNameIndex($i) . ' Nao pode ser enviado, o tipo ' . $filetype['type'] . ' nao eh permitido';
                    }
                }
            }
            else {
                echo "<h3>O arquivo zip nao pode ser descompactado.</h3>";
            }

            $zip->close();
        }
    }

    public function create_admin_page() {
?>

<h3>Media Zip</h3>
<form action="/wp-admin/admin.php?page=upload_media_zip"
    enctype="multipart/form-data" method="post">
    <div>
        <label>
            Selecione o arquivo zip
            <input type="file" name="fileToUpload" id="fileToUpload">
        </label>
    </div>
    <div>
        <?php submit_button(); ?>
    </div>

</form>
<?php
    }
}

MediaUnzip::getInstance();

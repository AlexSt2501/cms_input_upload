<?php
namespace samson\cms\input;

use samson\upload\Upload;

/**
 * Generic SamsonCMS input field
 * @author Vitaly Iegorov<egorov@samsonos.com>
 *
 */
class File extends Field
{
    /** Upload file controller */
    public function __async_upload()
    {
        s()->async(true);

        /** @var \samsonphp\fs\FileService $fsModule */
        $fsModule = m('fs');

        // Create object for uploading file to server
        $upload = new Upload(array(), $_GET['i']);

        // Uploading file to server;
        $upload->upload($file_path);

        // Call scale if it is loaded
        if (class_exists('\samson\scale\ScaleController', false) && $this->isImage($fsModule->extension($file_path))) {
            /** @var \samson\scale\ScaleController $scale */
            $scale = m('scale');
            $scale->resize($upload->fullPath(), $upload->name(), $upload->uploadDir);
        }

        /** @var \samson\activerecord\materialfield $field Save path to file in DB */
        $field = Field::fromMetadata($_GET['e'], $_GET['f'], $_GET['i']);
        $field->save($file_path);

        // Return upload object for further usage
        return array('status' => 1, 'path' => $upload->fullPath());
    }

    /** Delete file controller */
    public function __async_delete()
    {
        s()->async(true);

        /** @var \samsonphp\fs\FileService $fsModule */
        $fsModule = m('fs');

        /** @var \samson\activerecord\materialfield $field */
        $field = Field::fromMetadata($_GET['e'], $_GET['f'], $_GET['i']);

        // Build uploaded file path
        $file = $field->obj->Value;

        // Delete thumbnails
        if (class_exists('\samson\scale\ScaleController', false) && $this->isImage($fsModule->extension($file))) {

            /** @var string $path Path to file */
            $path = '';

            // Get file path
            preg_match('/.*\//', $file, $path);
            $path = $path[0];

            // Get image src
            $src = substr($file, strlen($path));

            /** @var \samson\scale\ScaleController $scale */
            $scale = m('scale');

            foreach (array_keys($scale->thumnails_sizes) as $folder) {
                // Form image path for scale module
                $imageScalePath = $path . $folder . '/' . $src;
                if ($fsModule->exists($imageScalePath)) {
                    $fsModule->delete($imageScalePath);
                }
            }
        }

        $fsModule->delete($file);
        // Save empty field value
        $field->save('');

        return array('status'=>true);
    }

    private function isImage($extension)
    {
        return ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'gif');
    }

    /** @see \samson\core\iModuleViewable::toView() */
    public function toView($prefix = NULL, array $restricted = array())
    {
        $controller = \samson\core\AutoLoader::oldClassName(get_class($this));

        // Generate controller links
        $this->set('upload_controller', $controller.'/upload?f='.$this->param.'&e='.$this->entity.'&i='.$this->obj->id)
        ->set('delete_controller', $controller.'/delete?f='.$this->param.'&e='.$this->entity.'&i='.$this->obj->id);

        //$this->set('empty_text', 'Выберите текст');
        // Call parent rendering routine
        return parent::toView($prefix, $restricted);
    }
}

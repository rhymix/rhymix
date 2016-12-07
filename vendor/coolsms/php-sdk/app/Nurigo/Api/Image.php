<?php
/* vi:set sw=4 ts=4 expandtab: */

namespace Nurigo\Api;

use Nurigo\Coolsms;
use Nurigo\Exceptions\CoolsmsSDKException;

require_once __DIR__ . "/../../../bootstrap.php";

/**
 * @class Image
 * @brief management image, using Rest API
 */
class Image extends Coolsms
{
    /**
     * @brief get image list( HTTP Method GET )
     * @param integer $offset [optional]
     * @param integer $limit  [optional]
     * @return object(total_count, offset, limit, list['image_id', 'image_id' ...])
     */
    public function getImageList($offset = null, $limit = null) 
    {
        $options = new \stdClass();
        $options->offset = $offset;
        $options->limit = $limit;
        return $this->request('image_list', $options);
    }

    /**
     * @brief get image info ( HTTP Method GET )
     * @param string $image_id [required]
     * @return object(image_id, file_name, original_name, file_size, width, height)
     */
    public function getImageInfo($image_id) 
    {
        if (!$image_id) throw new CoolsmsSDKException('image_id is required', 202);

        $options = new \stdClass();
        $options->image_id = $image_id;
        return $this->request(sprintf('images/%s', $image_id), $options);;
    }

    /**
     * @brief upload image ( HTTP Method POST )
     * @param mixed  $image    [required]
     * @param string $encoding [optional]
     * @return object(image_id)
     */
    public function uploadImage($image, $encoding = null)
    {
        if (!$image) throw new CoolsmsSDKException('image is required', 202);

        $options = new \stdClass();
        $options->image = $image;
        $options->encoding = $encoding;
        return $this->request('upload_image', $options, true);
    }

    /**
     * @brief delete images ( HTTP Method POST )
     * @param string $image_ids [required]
     * @return object(success_count)
     */
    public function deleteImages($image_ids) 
    {
        if (!$image_ids) throw new CoolsmsSDKException('image_ids is required', 202);

        $options = new \stdClass();
        $options->image_ids = $image_ids;
        return $this->request('delete_images', $options, true);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: 32823
 * Date: 2016/4/29
 * Time: 0:06
 */
namespace Models\Common;

class Resource extends BaseModel
{
    protected $table = 'resource';
    protected $primaryKey = 'app_id';

    /**
     * 添加资源
     * @param $appId
     * @param $resources
     * @param int $typeId
     * @return array
     */
    public function setResource($appId,$resources=array(),$typeId=1)
    {
        $this->deleteResource($appId,$typeId);
        if(empty($resources))
        {
            return array();
        }

        foreach($resources as $resource)
        {
            $item = array(
                'app_id'=>$appId,
                'image_url'=>$resource,
                'type_id'=>$typeId,
                'add_time'=>time()
            );
           $this->insert($item);
        }
        return true;
    }

    public function deleteResource($appId = 0,$typeId = 0)
    {
       return $this->where(array('app_id'=>$appId,'type_id'=>$typeId))->delete();
    }

    /**
     *
     * @param int $appId 应用ID
     * @param int $typeId 类型
     * @return array
     */
    public function getImagesByAppId($appId = 0, $typeId = 1){

        $list =  $this->where(array('app_id'=>$appId,'type_id' => $typeId))->orderBy('add_time','desc')->get(array('image_url'));
        if($list->isEmpty())
        {
            return array();
        }
        $images = array();
        $list = $list->toArray();

        foreach($list as $item)
        {
            array_push($images,$item['image_url']);
        }

        return $images;
    }

    public function getResourceCountByAppId($appId,$typeId = 1)
    {
        return $this->where(array('app_id'=>$appId,'type_id'=>$typeId))->count();
    }
}
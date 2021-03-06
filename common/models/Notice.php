<?php

namespace common\models;

use librarys\models\BaseModel;
use yii\db\Query;

class Notice extends BaseModel {

    public static function tableName() {
        return '{{%notice}}';
    }

    public static function getDb() {
        return \Yii::$app->db_jbv2;
    }
    
    public function init(){
    	parent::init();
    }
    
    public function rules() {
        return [
            [['id'],'integer'],
            [['title'],'unique','message'=>'标题已经存在！'],
            [['title','description'],'required','message'=>'不能为空！'],
            [['title','client','description','content','status','userid','username','createtime'],'safe'],
        ];
    }
    
    /**
     * 查询数据
     * @param type $conditions
     * @param type $offset
     * @param type $size
     * @param type $orderby
     * @param type $return_count_flag
     * @param type $index_by
     * @return type
     */
    public static function search($conditions=[],$offset=0, $size=0,$orderby = array(),$return_count_flag=false,$index_by='') {
        $query = static::find();
        if(count($conditions)>0){
            foreach ($conditions as $con){
                $query = $query->andWhere($con);
            }
        }
        $total = 0;
        if($return_count_flag===true){
            $total = $query->count('*');
        }
        if(!empty($index_by)){
            $query = $query->indexBy($index_by);
        }
        if(count($orderby)>0){
            $query = $query->orderBy($orderby);
        }
        if($size>0){
            $query = $query->limit($size)->offset($offset);
        }
        $list = $query->asArray(true)->all();
        return array('list'=>$list,'total'=>$total);
    }
    
    /**
     * 获取数据最大值
     * @param type $where
     * @return type
     */
    public function getNum($where=array()){
        $query = static::find();
                foreach($where as $wd){
                   $query = $query->andWhere($wd);
                }
//                $query =$query->createCommand()->getRawSql();
               return $query->count();
    }
    
    /**
     * 根据id，删除相关数据
     * @param type $id
     * @return type
     */
    public function noticeDel($id=0){
        return static::findOne($id)->delete();
    }
    
    /**
     * 根据标题，查询当前数据是否已经添加过
     * @param type $title
     */
    public function getNoticeOne($where=array()){
       return static::find()
                ->where($where)
                ->asArray(true)
                ->one();
    }
    
    /**
     * 添加数据
     * @param type $param
     * @return type
     */
    public function add($param = array()){
        $this->attributes = $param;
        return $this->save();
    }
    
    /**
     *  根据id，修改数据
     * @param type $data 需要修改的数据
     * @param type $id 查询条件
     * @return type
     */
    public function noticeUpdate($data,$id=''){
        $where =array(
            'id'=>$id,
        );
        return static::getDb()->createCommand()->update("9939_notice", array('client'=>$data['client'],'title'=>$data['title'],'description'=>$data['description'],'content'=>$data['content'],'updatetime'=>$data['updatetime']), $where)->execute();
    }
    
    public function screens($id=0){
         $where =array(
            'id'=>$id,
        );
        return static::getDb()->createCommand()->update("9939_notice", array('status'=>2), $where)->execute();
    }
}

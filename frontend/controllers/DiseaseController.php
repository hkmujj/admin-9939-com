<?php
/**
 * @version 0.0.0.1
 */

namespace frontend\controllers;

use common\models\CacheHelper;
use common\models\Disease;
use common\models\Search;
use frontend\models\DiseaseService;
use common\models\Symptom;
use common\models\disease\Article;
use common\models\doctor\Doctor;
use librarys\controllers\frontend\FrontController;
use yii\base\Module;
use common\models\FSearchTrait as SearchTrait;

/**
 * 疾病信息
 * @author gaoqing
 */
class DiseaseController extends FrontController{
	use SearchTrait;
    private $disease = null;
    private $diseaseService = null;
    private $symptom = null;
    private $article = null;
    private $params = [];
    public $right = []; //疾病块右侧通用内容

    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->disease = new Disease();
        $this->diseaseService = new DiseaseService();
        $this->symptom = new Symptom();
        $this->article = new Article();
    }

    public function init()
    {
        parent::init();
        $this->setLayout('disease');
    }
    
    public function behaviors() {
        return [
            [
                'class' =>'\librarys\actions\filters\PageCacheFilter',
                'enabled'=>true,
                'cache' => 'cache_file',
                'only' => ['dispatch'], //需要添加缓存的方法列表
                'duration' => 24 * 60 * 60, //默认一天
                'variations' => [
                    $_SERVER['REQUEST_URI']
                ],
            ],
        ];
    }

    /**
     * 路由配置方法
     * @author gaoqing
     * @date 2016-03-22
     * @return array 路由集
     */
    private function routeMap(){
        return [
            '' => 'index',           //疾病首页
            'jzzn'=>'guide',         //就诊指南
            'ys'=>'doctor',         //找医生
            'yy'=>'hospital',         //找医院
            'yaopin'=>'drugs',         //找药品
            'zixun'=>'onlineInquiry',         //在线问诊
            'jianjie' => 'abstractwords',               //简介
            'zz' => 'symptom',                          //症状
            'by' => 'reason',                           //病因
            'yf' => 'prevent',                          //预防
            'lcjc' => 'inspect',                        //检查
            'jb' => 'diagnosis',                        //鉴别
            'zl' => 'treatment',                        //治疗
            'yshl' => 'food',                           //饮食护理
            'bfz' => 'neopathy',                        //并发症
        ];
    }

    /**
     * 分流方法
     * @author gaoqing
     * @date 2016-03-22
     * @return string 视图
     */
    public function actionDispatch(){
        $this->params = $this->getRequestParams();

        //得到调用的 action 方法
        $route = $this->routeMap();
        $dmodule = trim($this->params['dmodule'], '/');
        $actionName = "action" . ucfirst($route[$dmodule]);
        return $this->$actionName();
    }

    /**
     * 疾病并发症
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionNeopathy(){
        $view = "neopathy";
        $params = $this->exexuteQuery($view, 8);
        return $this->render($view, $params);
    }

    /**
     * 疾病治疗
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionTreatment(){
        $view = "treatment";
        $params = $this->exexuteQuery($view, 5);
        return $this->render($view, $params);
    }

    /**
     * 疾病饮食护理
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionFood(){
        $view = "food";
        $params = $this->exexuteQuery($view, 6);
        return $this->render($view, $params);
    }

    /**
     * 疾病诊断
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionDiagnosis(){
        $view = "diagnosis";
        $params = $this->exexuteQuery($view, 4);
        return $this->render($view, $params);
    }

    /**
     * 疾病检查
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionInspect(){
        $view = "inspect";
        $params = $this->exexuteQuery($view, 3);
        return $this->render($view, $params);
    }

    /**
     * 疾病预防
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionPrevent(){
        $view = "prevent";
        $params = $this->exexuteQuery($view, 7);
        return $this->render($view, $params);
    }

    /**
     * 疾病病因
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionReason(){
        $view = "reason";
        $params = $this->exexuteQuery($view, 2);
        return $this->render($view, $params);
    }

    /**
     * 首页
     * @author gaoqing
     * @date 2016-03-21
     * @return string 视图
     */
    public function actionIndex(){
        $view = "index";
        
        //1、疾病的信息（根据疾病的 dname, 获取疾病信息）
        $disease = $this->diseaseService->getDiseasesByPinyin($this->params['dname'], false, true, true, true);
        $disease['alias_arr'] = [];
        if (!empty($disease['alias'])){
            $disease['alias_arr'] = $this->explodeStr($disease['alias']);
        }

        //2、获取 4 条症状信息
        $symptoms = [];
        if ($this->isNotNull($disease['tsymptom'])){
            $symptoms = array_slice($disease['tsymptom'], 0, 4);
        }

        //3、获取全面解读部分数据
        $allReads = CacheHelper::getCache('frontend_disease_index_wzjd', ['id' => $disease['id'], 'name' => $disease['name']]);

        //4、关注问题
        $asks = CacheHelper::getCache('frontend_disease_index_gzwt', ['id' => $disease['id'], 'keywords' => $disease['keywords']]);

        //得到当前病 对应到 9939_com_v2sns wd_keshi 表中的 id 值
        $v2snsKeshiID = $this->diseaseService->getKeshiIDByName($disease['name']);

        //医生信息
        $doctors = $this->getDoctors();
        
        //得到热词部分
        $hotWords = $this->letterHotWordsBatch($disease['name']);
        $model['randWords'] = $hotWords;
        
        $disease['thumb']=empty($disease['thumb'])?'/images/dise_02.jpg': \librarys\helpers\utils\Url::getuploadfileUrl(1,$disease['thumb']);
        $params = [
            'disease' => $disease,
            'symptoms' => $symptoms,
            'allReads' => $allReads,
            'asks' => $asks,
            'v2snsKeshiID' => $v2snsKeshiID,
            'doctors' => $doctors,
            'model' => $model,
        ];
        return $this->render($view, $params);
    }

    /**
     * 得到全科医生的数据集
     * @author gaoqing
     * @date 2016-04-05
     * @return array 全科医生的数据集
     */
    private function getDoctors(){
        $doctors = [];

        $doctorIDs = $this->doctorsIDs();

        //得到随机的医生 id 集
        $doctorIDArr = [];
        while (count($doctorIDArr) != 4){
            $index = rand(0, 9);
            if (!in_array($index, $doctorIDArr)){
                $doctorIDArr[] = $doctorIDs[$index];
            }
        }
        $doctorids = implode(',', $doctorIDArr);
        $doctor = new Doctor();
        $doctors = $doctor->getDoctorById($doctorids);

        return $doctors;
    }

    /**
     * 疾病症状
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionSymptom(){
        $view = "symptom";
        $params = $this->exexuteQuery($view, 1, true);
        return $this->render($view, $params);
    }

    /**
     * 执行查询操作
     * @author gaoqing
     * @date 2016-03-24
     * @param string $veiwName 视图名称
     * @param int $artType 文章类型的整数值
     * （1 症状 2 病因 3 检查 4 鉴别 5 治疗 6 饮食护理 7 预防  8 并发症）
     * @parm boolean $isRelSym 是否获取其 典型症状
     * @return array 视图参数集
     */
    private function exexuteQuery($veiwName, $artType, $isRelSym = false){
        //1、获取疾病信息
        $disease = $this->diseaseService->getDiseasesByPinyin($this->params['dname'], false, $isRelSym);

        //2、获取【相关阅读】
        $relArticles = $this->diseaseService->getListByDiseaseid(['diseaseid' => $disease['id']], 0, 3);
        if (isset($relArticles) && !empty($relArticles)){
            foreach ($relArticles as &$relArticle){
                $relArticle['url'] = 'article/' . date('Y/md', $relArticle['inputtime']) . '/' . $relArticle['id'] . '.shtml';
            }
        }

        //3、获取 【相应类型文章】
        $typerticles = $this->diseaseService->getListByDiseaseid(['diseaseid' => $disease['id'], 'type' => [$artType]], 0, 12);
        if (isset($typerticles) && !empty($typerticles)){
            foreach ($typerticles as &$typerticle){
                $typerticle['url'] = 'article/' . date('Y/md', $typerticle['inputtime']) . '/' . $typerticle['id'] . '.shtml';
            }
        }
        $params = [
            'disease' => $disease,
            'relArticles' => $relArticles,
            $veiwName . 'Articles' => $typerticles,
        ];
        return $params;
    }

    /**
     * 简介
     * @author gaoqing
     * @date 2016-03-24
     * @return string 视图
     */
    public function actionAbstractwords(){
        $view = "abstractwords";
        $disease = $this->diseaseService->getDiseasesByPinyin($this->params['dname'], true, true, true, true);
        $disease['alias_arr'] = [];
        if (!empty($disease['alias'])){
            $disease['alias_arr'] = $this->explodeStr($disease['alias']);
        }
        $params = [
            'disease' => $disease,
        ];
        return $this->render($view, $params);
    }

    /**
     *  就诊指南
     * @param type $param
     */
    public function actionGuide() {
        $data = [];
        $temp = $this->params;
        $disease = $this->disease->getDiseasesByPinyin($temp['dname']);
        
        $data['disease'] = $disease;
        return $this->render('guide',$data);
    }
    
    /**
     * 在线问诊
     */
    public function actionOnlineInquiry() {
        $data = [];
        $temp = $this->params;
        
        $disease = $this->disease->getDiseasesByPinyin($temp['dname']);
        $doctor = $this->getDoctor($disease['id'], 3, 3);
        $asks = Search::search_ask($disease['keywords'], 0, 6);//相关问答
        //得到当前病 对应到 9939_com_v2sns wd_keshi 表中的 id 值
        $v2snsKeshiID = $this->diseaseService->getKeshiIDByName($disease['name']);
        
        $data['disease'] = $disease;
        $data['doctor'] = $doctor;
        $data['asks'] = $asks;
        $data['v2snsKeshiID'] = $v2snsKeshiID;
        return $this->render('online_inquiry',$data);
    }
    
    /**
     * 疾病文章解读 文章列表
     */
    public function actionArticleList() {
        $data = [];
        $this->params = $this->getRequestParams();
        $dname = $this->params['dname'];
        $type = (!empty($this->params['type']) && is_numeric($this->params['type'])) ? $this->params['type'] : ''; //文章类型分八种
        
        $disease = $this->disease->getDiseasesByPinyin($dname);
        //文章列表
        $condition['diseaseid'] = $disease['id'];
        $condition['type'] = [$type];
        $total = $this->article->getCountByDiseaseid($condition);
        
        $limit = 35;
        $paging = $this->helpPaging('pager_disease_article')->setSize($limit)->setPageSetSize(5);
        $paging->setTotal($total);
        
        $offset = $paging->getOffset();
        $article = $this->diseaseService->getListByDiseaseid($condition,$offset,$limit);
        $hotWords = $this->letterHotWordsBatch($disease['name']); //英文字母列表
        $data['disease'] = $disease;
        $data['type'] = $type;
        $data['article'] = $article;
        $data['paging'] = $paging;
        $data['hotWords'] = $hotWords;
        return $this->render('article_list',$data);
    }
    
    /**
     * 找医生
     * @return type
     */
    public function actionDoctor() {
        $data = [];
        $temp = $this->params;
        $disease = $this->disease->getDiseasesByPinyin($temp['dname']);
        
        $data['disease'] =$disease; 
        return $this->render('doctor',$data);
    }
    
    /**
     * 找药品
     * @return type
     */
    public function actionDrugs() {
        $data = [];
        $temp = $this->params;
        $disease = $this->disease->getDiseasesByPinyin($temp['dname']);
        
        $data['disease'] = $disease;
        return $this->render('drugs', $data);
    }

    /**
     * 找医院
     * @return type
     */
    public function actionHospital() {
        $data = [];
        $temp = $this->params;
        $disease = $this->disease->getDiseasesByPinyin($temp['dname']);
        
        $data['disease'] = $disease;
        return $this->render('hospital', $data);
    }

    private function getRequestParams(){
        $params = $this->getRequest()->queryParams;
        array_walk($params, function(&$value){
            $value = trim($value, '/');
        });
        return $params;
    }
    /**
     * 通过疾病id 获取 医生
     * @param type $diseaseid
     * @return type
     */
    private function getDoctor($diseaseid, $offset = 0, $length = 3) {
        require '../data/doctorid.php';
        $doc = new Doctor();
        $dep = new \common\models\Department();
        
        $department = $dep->getDepartmentsByDisid($diseaseid);
        $level1 = $dep->getDepartmentById($department['0']['class_level1']);//疾病对应的一级科室
        
        $uid = array_key_exists($level1['name'], $_DOCTORID) ? $_DOCTORID[$level1['name']] : $_DOCTORID['内科'];//科室名称获取医生id信息
        $arruid = array_slice($uid,$offset, $length);
        $uidstr = implode(',', $arruid);
        $doctor = $doc->getDoctorById($uidstr);
        return $doctor;
    }

    public function render($view, $params = [])
    {
        $disease = $params['disease'];

        //查询右侧数据
        $symptom_rel = $this->symptom->getSymptomsByDisid($disease['id']);
        $disease_rel = $this->disease->getDiseaseDisByDisid($disease['id']);
        $doctor = $this->getDoctor($disease['id'], 0, 3);
        
        $right = [
            'disease' => $disease,
            'symptom_rel' => $symptom_rel,
            'disease_rel' => $disease_rel,
            'expert' => $doctor,
        ];
        $this->right = $right;
        return parent::render($view, $params);
    }

    private function isNotNull($param){
        $isNotNull = false;
        if (isset($param) && !empty($param)){
            $isNotNull = true;
        }
        return $isNotNull;
    }

    private function doctorsIDs(){
        return [830765, 830774, 831181, 1785, 831253, 831200, 1440561, 1442194, 221102, 329006];
    }

    /**
     * 将 字符串信息，按照 特定的分隔符，拆分成数组
     * @author gaoqing
     * @date 2016-03-24
     * @param string $str 字符串值
     * @return array 拆分后的数组
     */
    private function explodeStr($str){
        $arr = [];
        $pattern = '/\s+/';
        if (strstr($str, ',')){
            $pattern = '/\,/';
        }
        if (strstr($str, '，')){
        	$pattern = '/\，/';
        }
        $arr = preg_split($pattern, $str);
        //$arr = explode($delimiter, trim($str, $delimiter));
        foreach ($arr as $key => $value){
        	if (empty($value)){
        		unset($arr[$key]);
        	}
        }
        return $arr;
    }

}

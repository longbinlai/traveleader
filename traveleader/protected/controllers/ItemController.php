<?phpclass ItemController extends YController{    public function actionIndex()    {        $id = $_REQUEST['category_id'];        $price = $_REQUEST['price'];        $category = Category::model()->findByPk($id);        if ($id) {            $category = Category::model()->findByPk($id);            $childs = $category->descendants()->findAll();            $ids = array($id);            foreach ($childs as $child)                $ids[] = $child->id;            $cid = implode(',', $ids);            $condition = $id ? 'is_show = 1 and category_id in (' . $cid . ')' : 'is_show = 1';        }        if ($price) {            if ($price && $id) {                $catmodel = new Category();                $ids = $catmodel->getMeChildsId($id);                $cid = implode(',', $ids);                $condition = $id ? 'is_show = 1 and  shop_price=' . $price . ' and category_id in (' . $cid . ')' : 'is_show = 1';            }        }        $keyword = $_REQUEST['keyword'];        if ($keyword) {            $condition = $keyword ? 'is_show = 1 and title like' . "'%$keyword%'" . 'or sn like' . "'%$keyword%'" : 'is_show = 1';        }        $criteria = new CDbCriteria(array(            'condition' => $condition,            'order' => 'sort_order asc, item_id desc'        ));        $count = Item::model()->count($criteria);        $pages = new CPagination($count);        // results per page        $pages->pageSize = 20;        $pages->applyLimit($criteria);        $items = Item::model()->findAll($criteria);        $this->render('index', array(            'items' => $items,            'pages' => $pages,            'keyword' => $keyword,            'category' => $category        ));    }    public function actionList($key)    {        $category = Category::model()->findByPk(3);        $descendants = $category->children()->findAll();        $this->render('list', array(            'categories' => $descendants,            'key' => $key        ));    }    /**     * Displays a particular model.     * @param integer $id the ID of the model to be displayed     */    public function actionView($id)    {        $item = $this->loadModel($id);        /* 记录浏览历史 */        if (isset(Yii::app()->request->cookies['history'])) {            $history = explode(',', Yii::app()->request->cookies['history']->value);            array_unshift($history, $id);            $history = array_unique($history);            while (count($history) > 5) {                array_pop($history);            }            $cookie = new CHttpCookie('history', implode(',', $history));            $cookie->expire = F::gmtime() + 3600 * 24 * 30;            Yii::app()->request->cookies['history'] = $cookie;        } else {            $cookie = new CHttpCookie('history', $id);            $cookie->expire = F::gmtime() + 3600 * 24 * 30;            Yii::app()->request->cookies['history'] = $cookie;        }        /* 更新点击次数 */        $item->click_count = $item->click_count + 1;        $item->save();        //show sku//        $skus = CJSON::decode($model->skus);//        $sku_data = array('checkbox' => array());//        if (!empty($skus['checkbox'])) {//            foreach ($skus['checkbox'] as $k => $v) {//                if (!is_array($v)) continue;//                $item_prop = ItemProp::model()->findByPk($k);//                if (!$item_prop) continue;//                $vids = array();//                foreach ($v as $kk => $vv) {//                    $ids = explode(':', $vv);//                    if (!empty($ids[1])) {//                        $vids[$vv] = $ids[1];//                    }//                }//                if (empty($vids)) continue;//                $cri = new CDbCriteria();//                $cri->addInCondition('value_id', $vids);//                $cri->select = 'value_id, value_name';//                $prop_values = PropValue::model()->findAll($cri);//                $prop_value_names = array();//                $vids = array_flip($vids);//                foreach ($prop_values as $prop_value) {//                    $prop_value_names[$vids[$prop_value->value_id]] = $prop_value->value_name;//                }//                $sku_data['checkbox'][$item_prop->prop_name] = $prop_value_names;//            }//        }//        $sku_data['table'] = !empty($skus['table']) ? $skus['table'] : array();        $category = $item->category;        $parentCategories = $category->parent()->findAll();        $parentCategories = array_reverse($parentCategories);        $categoryIds = array($category->category_id);        foreach ($parentCategories as $cate) {            if (!$cate->isRoot()) {                $params['cat'] = $cate->getUrl();                $this->breadcrumbs[] = array('name' => $cate->name . '>> ', 'url' => Yii::app()->createUrl('catalog/index', array('cat' => $cate->getUrl())));                $categoryIds[] = $cate->category_id;            }        }        $params['cat'] = $category->getUrl();        //$this->breadcrumbs[] = array('name' => $category->name . '>> ', 'url' => Yii::app()->createUrl('catalog/index', array('cat' => $category->getUrl())));                $country_id = $item->country;        $state_id = $item->state;        $city_id = $item->city;        $country = Area::model()->findByPk($country_id);        $state = Area::model()->findByPk($state_id);        $city = Area::model()->findByPk($city_id);        if("中国" != $country->name){        	$this->breadcrumbs[] = array('name' => $country->name. "旅游" .'>> ', 'url' => Yii::app()->createUrl('catalog/index', array('country' => $country->area_id)));        }        $this->breadcrumbs[] = array('name' => $state->name. "旅游" .'>> ', 'url' => Yii::app()->createUrl('catalog/index', array('state' => $state->area_id)));        $this->breadcrumbs[] = array('name' => $city->name. "旅游" .'>> ', 'url' => Yii::app()->createUrl('catalog/index', array('city' => $city->area_id)));        $this->breadcrumbs[] = array('name' => $item->title, 'url' => Yii::app()->createUrl('item/view', array('id' => $item->item_id)));        Yii::app()->params['categoryIds'] = $categoryIds;        $this->render('view', array(            'item' => $item,//            'sku_data' => $sku_data,        ));    }    public function actionClearHistory()    {        unset(Yii::app()->request->cookies['history']);    }    /**     * Returns the data model based on the primary key given in the GET variable.     * If the data model is not found, an HTTP exception will be raised.     * @param integer the ID of the model to be loaded     */    public function loadModel($id)    {        $model = Item::model()->findByPk((int)$id);        if ($model === null)            throw new CHttpException(404, 'The requested page does not exist.');        return $model;    }    // Uncomment the following methods and override them if needed    /*      public function filters()      {      // return the filter configuration for this controller, e.g.:      return array(      'inlineFilterName',      array(      'class'=>'path.to.FilterClass',      'propertyName'=>'propertyValue',      ),      );      }      public function actions()      {      // return external action classes, e.g.:      return array(      'action1'=>'path.to.ActionClass',      'action2'=>array(      'class'=>'path.to.AnotherActionClass',      'propertyName'=>'propertyValue',      ),      );      }     */}
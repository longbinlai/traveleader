<?php

class Post extends CActiveRecord
{
	/**
	 * The followings are the available columns in table 'tbl_post':
	 * @var integer $id
	 * @var integer $category_id // Reserve for future use
	 * @var string $title
	 * @var string $summary
	 * @var string $content
	 * @var string $author_id
	 * @var string $tags
	 * @var integer $status
	 * @var integer $views
	 * @var integer $ding
	 * @var string $cover_pic
	 * @var string language
	 * @var string $create_time
	 * @var string $update_time

	 * @var integer $is_best
	 * @var string $country
	 * @var string $state
	 * @var string $city
	 * @var string $scenery_id
	 */
	const STATUS_DRAFT=1;
	const STATUS_PUBLISHED=2;
	const STATUS_ARCHIVED=3;

	private $_oldTags;

	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'post';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, content, status, scenery_id', 'required'),
			array('status', 'in', 'range'=>array(1,2,3)),
			array('is_best', 'in', 'range'=>array(0,1)),
			array('title', 'length', 'max'=>128),
			array('cover_pic', 'length', 'max'=>128),
			array('language', 'length', 'max'=>45),
			array('tags', 'match', 'pattern'=>'/^[\w\s,]+$/', 'message'=>'Tags can only contain word characters.'),
			//array('tags', 'normalizeTags'),
			array('views, ding, item_id, scenery_id, country, state, city', 'numerical', 'integerOnly' => true),
			array('views, ding, scenery_id, country, state, city,image_id', 'length', 'max' => 10),
			array('title, status, views, ding, create_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'author' => array(self::BELONGS_TO, 'User', 'author_id'),
			//'comments' => array(self::HAS_MANY, 'Comment', 'owner_id', 'condition'=>'t.status='.Comment::STATUS_APPROVED, 'order'=>'t.create_time DESC'),
			//'commentCount' => array(self::STAT, 'Comment', 'owner_id', 'condition'=>'t.status='.Comment::STATUS_APPROVED),
			'item' => array(self::BELONGS_TO, 'Item', 'item_id'),
			'area_country' => array(self::BELONGS_TO, 'Area', 'country'),
			'area_state' => array(self::BELONGS_TO, 'Area', 'state'),
			'area_city' => array(self::BELONGS_TO, 'Area', 'city'),
			'scenery' => array(self::BELONGS_TO, 'Scenery', 'scenery_id'),
		);
	}
	
	public function behaviors() {
		return array(
			'commentable' => array(
					'class' => 'ext.comment-module.behaviors.CommentableBehavior',
					// name of the table created in last step
					'mapTable' => 'posts_comments_nm',
						// name of column to related model id in mapTable
					'mapRelatedColumn' => 'postId'
			),
			/*
			'coverBehavior' => array(
					'class' => 'ext.imageAttachment.ImageAttachmentBehavior',
						// size for image preview in widget
					'previewHeight' => 200,
					'previewWidth' => 300,
						// extension for image saving, can be also tiff, png or gif
					'extension' => 'jpg,png,jpeg',
					// folder to store images
					'directory' => '/traveleader/upload/postcover/preview',
					// url for images folder
					'url' => Yii::app()->baseUrl.'/upload/postcover/preview',
					// image versions
					'versions' => array(
						'small' => array(
								'resize' => array(200, null),
					),
							//'medium' => array(
							//			'resize' => array(800, null),
							//)
					)
				)*/
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => Yii::t('Post', 'Id'),
			'category_id' => Yii::t('Post', '类别'),
			'title' => Yii::t('Post', '标题'),
			'summary' => Yii::t('Post', '摘要'),
			'content' => Yii::t('Post', '我游我记'),
			'tags' => Yii::t('Post', '标签'),
			'status' => Yii::t('Post', '状态'),
			'views' => Yii::t('Post', '浏览次数'),
			'ding' => Yii::t('Post', '顶'),
			'create_time' => Yii::t('Post', '创建时间'),
			'update_time' => Yii::t('Post', '更新时间'),
			'author_id' => Yii::t('Post', '作者'),
			'language' => Yii::t('Post', '语言'),
			'cover_pic' => Yii::t('Post', '封面图片'),
			'is_best' => Yii::t('Post', '精品'),
			'item_id' => Yii::t('Post', '旅游产品'),
			'country' => Yii::t('Post', '国家/洲'),
			'state' => Yii::t('Post', '省/国家'),
			'city' => Yii::t('Post', '城市'),
			'scenery_id' => Yii::t('Post', '景点'),
			'image_id' => Yii::t('Post', 'ImageAttachement'),
		);
	}

	/**
	 * @return string the URL that shows the detail of the post
	 */
	public function getUrl()
	{
		return Yii::app()->createUrl('post/view', array(
			'id'=>$this->id,
		));
	}

	/**
	 * @return array a list of links that point to the post list filtered by every tag of this post
	 */
	public function getTagLinks()
	{
		$links=array();
		foreach(Tag::string2array($this->tags) as $tag)
			$links[]=CHtml::link(CHtml::encode($tag), array('post/index', 'tag'=>$tag));
		return $links;
	}

	/**
	 * Normalizes the user-entered tags.
	 */
	public function normalizeTags($attribute,$params)
	{
		$this->tags=Tag::array2string(array_unique(Tag::string2array($this->tags)));
	}

	/**
	 * Adds a new comment to this post.
	 * This method will set status and post_id of the comment accordingly.
	 * @param Comment the comment to be added
	 * @return boolean whether the comment is saved successfully
	 */
	public function addComment($comment)
	{
		if(Yii::app()->params['commentNeedApproval'])
			$comment->status=Comment::STATUS_PENDING;
		else
			$comment->status=Comment::STATUS_APPROVED;
		$comment->post_id=$this->id;
		return $comment->save();
	}

	/**
	 * This is invoked when a record is populated with data from a find() call.
	 */
	protected function afterFind()
	{
		parent::afterFind();
		$this->_oldTags=$this->tags;
	}

	/**
	 * This is invoked before the record is saved.
	 * @return boolean whether the record should be saved.
	 */
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if($this->isNewRecord)
			{
				$this->create_time=$this->update_time=time();
				$this->author_id=Yii::app()->user->id;
			}
			else
				$this->update_time=time();
			return true;
		}
		else
			return false;
	}

	/**
	 * This is invoked after the record is saved.
	 */
	protected function afterSave()
	{
		parent::afterSave();
		Tag::model()->updateFrequency($this->_oldTags, $this->tags);
	}

	/**
	 * This is invoked after the record is deleted.
	 */
	protected function afterDelete()
	{
		parent::afterDelete();
		//Comment::model()->deleteAll('post_id='.$this->id);
		Tag::model()->updateFrequency($this->tags, '');
	}

	/**
	 * Retrieves the list of posts based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the needed posts.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('title',$this->title,true);
		//$criteria->compare('author_id', $this->author_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('author_id', Yii::app()->user->getId());
		//$criteria->compare('ding', $this->ding);
		//$criteria->compare('views', $this->views);
		//$criteria->compare('create_time', $this->create_time);
		
		return new CActiveDataProvider('Post', array(
			'criteria'=>$criteria,
			'sort'=>array(
				'defaultOrder'=>'status, update_time DESC',
			),
		));
	}
	
	public function getStatusOptions()
	{
		return array(
				self::STATUS_DRAFT      => Yii::t('Post','草稿'),
				self::STATUS_PUBLISHED  => Yii::t('Post','发布'),
				self::STATUS_ARCHIVED   => Yii::t('Post','存档'),
		);
	}
}
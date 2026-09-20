<?php

namespace frontend\controllers;

use frontend\models\Blog;
use frontend\models\BlogSearch;
use frontend\models\Category;
use frontend\models\BlogTag;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class BlogController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new BlogSearch();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'categoryModels' => Category::find()->with('translations')->all(),
            'dataProvider' => $searchModel->search(\Yii::$app->request->queryParams),
            'tagModels' => BlogTag::find()->joinWith('posts')->groupBy('blog_tag.id')->orderBy(['blog_tag.name' => SORT_ASC])->all(),
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCategory($id)
    {
        $searchModel = new BlogSearch();
        $query = Blog::find()->with(['translations', 'tags', 'category', 'user'])
            ->andWhere(['category_id' => $id])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'categoryModels' => Category::find()->with('translations')->all(),
            'dataProvider' => new ActiveDataProvider(['query' => $query]),
            'tagModels' => BlogTag::find()->orderBy(['name' => SORT_ASC])->all(),
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Blog::find()->with(['translations', 'tags', 'category', 'user'])->where(['id' => $id])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

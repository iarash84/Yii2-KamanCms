<?php

namespace frontend\controllers;

use frontend\models\ContentTranslation;
use frontend\models\Page;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class PageController extends Controller
{
    public function actionView($slug)
    {
        $language = Yii::$app->languageManager->activeLanguage;
        $query = Page::published()->with(['translations', 'featuredMedia']);
        if ($language === Yii::$app->languageManager->defaultLanguage) {
            $page = $query->andWhere(['slug' => $slug])->one();
        } else {
            $pageId = ContentTranslation::find()->select('entity_id')->where([
                'entity_type' => 'Page', 'language' => $language, 'attribute' => 'slug', 'value' => $slug,
            ])->scalar();
            $page = $query->andWhere(['or', ['id' => $pageId ?: 0], ['slug' => $slug]])->one();
        }
        if ($page === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        return $this->render('view', ['model' => $page]);
    }
}

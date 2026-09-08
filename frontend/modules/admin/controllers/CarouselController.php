<?php

namespace frontend\modules\admin\controllers;

use Yii;
use frontend\models\Carousel;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use common\components\SecureUpload;

/**
 * CarouselController implements the CRUD actions for Carousel model.
 */
class CarouselController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'reorder' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Carousel models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Carousel::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]),
            'pagination' => false,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Carousel model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Carousel(['status' => 1]);

        if ($model->load(Yii::$app->request->post())) {
            $upload = UploadedFile::getInstance($model, 'image');
            if ($upload !== null) {
                $model->image = SecureUpload::storeImage($upload);
                $model->user_id = Yii::$app->user->id;
                $model->sort_order = ((int) Carousel::find()->max('sort_order')) + 10;

                if ($this->saveModel($model)) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Carousel created'));
                return $this->redirect(['index']);
                }
            } else {
                $model->addError('image', Yii::t('app', 'Image is required.'));
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * Updates an existing Carousel model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldImage = $model->image;
        if ($model->load(Yii::$app->request->post())) {
            $upload = UploadedFile::getInstance($model, 'image');
            $model->image = $upload === null ? $oldImage : SecureUpload::storeImage($upload);
            if ($this->saveModel($model)) {
                if ($upload !== null) {
                    $this->deleteImage($oldImage);
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'Carousel updated Successfully'));
                return $this->redirect(['index']);
            }
        }
        return $this->render('update', ['model' => $model]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     */
    public function actionReorder()
    {
        $ids = json_decode((string) Yii::$app->request->post('ids'), true);
        if (!is_array($ids) || $ids === [] || count($ids) !== count(array_unique(array_map('strval', $ids)))) {
            throw new BadRequestHttpException(Yii::t('app', 'Invalid carousel order.'));
        }
        $ids = array_map('intval', $ids);
        $models = Carousel::find()->where(['id' => $ids])->indexBy('id')->all();
        if (count($models) !== count($ids) || (int) Carousel::find()->count() !== count($ids)) {
            throw new BadRequestHttpException(Yii::t('app', 'Invalid carousel order.'));
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $position => $id) {
                $models[$id]->updateAttributes(['sort_order' => ($position + 1) * 10]);
            }
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
        return $this->asJson(['success' => true]);
    }


    /**
     * Deletes an existing Carousel model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->deleteImage($model->image);
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Carousel model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Carousel the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Carousel::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    private function saveModel(Carousel $model): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()
                || !$model->saveTranslations(Yii::$app->request->post('translations', []))) {
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
            return true;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    private function deleteImage(?string $image): void
    {
        if ($image === null || $image === '') {
            return;
        }
        $path = Yii::getAlias('@webroot/' . ltrim($image, '/'));
        if (is_file($path)) {
            unlink($path);
        }
    }
}

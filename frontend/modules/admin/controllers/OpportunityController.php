<?php

namespace frontend\modules\admin\controllers;

use frontend\models\Setting;
use Yii;
use frontend\models\Opportunity;
use frontend\models\OpportunitySearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * OpportunityController implements the CRUD actions for Opportunity model.
 */
class OpportunityController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [ 'view', 'delete'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Opportunity models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OpportunitySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (($model = Setting::find()->where(['type'=>'Opportunity'])->one()) == null)
            $model = new Setting();

        if ($model->load(Yii::$app->request->post())) {

            $model->user_id = Yii::$app->user->identity->getId();
            $model->type = 'Opportunity';
            $model->save();
            $model->saveTranslations(Yii::$app->request->post('translations', []));
            Yii::$app->session->setFlash('success',Yii::t('app','Thank you! Update successfully completed!'));

            return $this->redirect(['opportunity/index']);
        } else {

            return $this->render('index', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }
    }

    /**
     * Displays a single Opportunity model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        if ($model->read_at === null) {
            $model->updateAttributes(['read_at' => time()]);
        }
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionDetail($id)
    {
        $model = $this->findModel($id);
        if ($model->read_at === null) {
            $model->updateAttributes(['read_at' => time()]);
        }
        return $this->renderPartial('_detail', ['model' => $model]);
    }

    public function actionDownload($id)
    {
        $model = $this->findModel($id);
        $fileName = basename((string) $model->resume);
        if ($fileName === '' || $fileName !== $model->resume) {
            throw new NotFoundHttpException(Yii::t('app', 'File not found.'));
        }
        $path = Yii::getAlias('@storage/resumes/' . $fileName);
        if (!is_file($path)) {
            throw new NotFoundHttpException(Yii::t('app', 'File not found.'));
        }

        return Yii::$app->response->sendFile($path, 'resume-' . $model->id . '.pdf', [
            'mimeType' => 'application/pdf',
            'inline' => false,
        ]);
    }

    /**
     * Creates a new Opportunity model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Opportunity();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Opportunity model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $path = Yii::getAlias('@storage/resumes/' . basename((string) $model->resume));
        if ($model->delete() && is_file($path)) {
            unlink($path);
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Opportunity model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Opportunity the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Opportunity::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

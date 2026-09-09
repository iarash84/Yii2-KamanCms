<?php

namespace frontend\modules\admin\controllers;

use common\components\SecureUpload;
use common\models\Log;
use frontend\models\ChangePasswordForm;
use frontend\models\SignupForm;
use Yii;
use common\models\User;
use frontend\models\UserSearch;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
            $searchModel = new UserSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            $model = new SignupForm();
            if ($model->load(Yii::$app->request->post())) {
                $model->avatarFile = UploadedFile::getInstance($model, 'avatarFile');
                if ($user = $model->signup()) {
                    return $this->redirect(['index']);
                }
            }

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'model' => $model,
            ]);
    }


    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
            $model = $this->findModel($id);
            $oldAvatar = $model->avatar;

            if ($model->load(Yii::$app->request->post())) {
                $model->avatarFile = UploadedFile::getInstance($model, 'avatarFile');

                if (!$model->validate()) {
                    return $this->render('update', ['model' => $model]);
                }

                $this->guardLastSuperAdmin($model->id, $model->role);

                if (!empty($model->password)) {
                    $model->setPassword($model->password);
                    $model->generateAuthKey();
                }

                $auth = Yii::$app->authManager;
                $role = $auth->getRole($model->role);
                if ($role === null) {
                    $model->addError('role', Yii::t('app', 'Invalid role.'));
                } else {
                    if ($this->saveProfile($model, $oldAvatar)) {
                        $auth->revokeAll($model->id);
                        $auth->assign($role, $model->id);
                        return $this->redirect(['index']);
                    }
                }
            }

            $roles = array_intersect(
                array_keys(Yii::$app->authManager->getRolesByUser($model->id)),
                ['editor', 'admin', 'superAdmin']
            );
            if ($model->role === null) {
                $model->role = empty($roles) ? $this->fallbackRole($model->id) : reset($roles);
            }

            return $this->render('update', [
                'model' => $model,
            ]);
    }

    public function actionProfile()
    {
        $model = $this->findModel(Yii::$app->user->id);
        $model->scenario = 'profile';
        $oldAvatar = $model->avatar;

        if ($model->load(Yii::$app->request->post())) {
            $model->avatarFile = UploadedFile::getInstance($model, 'avatarFile');
            if ($model->validate() && $this->saveProfile($model, $oldAvatar)) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Profile updated successfully.'));
                return $this->redirect(['profile']);
            }
        }

        return $this->render('profile', ['model' => $model]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionChange()
    {
        $model = new ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = User::find()->where(['id' => Yii::$app->user->identity->getId()])->one();
            $user->setPassword($model->newPassword);
            $user->generateAuthKey();
            if ($user->save(false)) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Password changed successfully.'));
                return $this->redirect(['change']);
            }
            $model->addError('newPassword', Yii::t('app', 'Unable to save the new password.'));
        } else {
            return $this->render('changePassword', ['model' => $model]);
        }

        return $this->render('changePassword', ['model' => $model]);
    }

    public function actionLog(){
        $dataProvider = new ActiveDataProvider([
            'query' => Log::find()->orderBy(['created_at' => SORT_DESC]),
        ]);

        return $this->render('log', [
            'dataProvider' => $dataProvider
        ]);
    }


    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
            $model = $this->findModel($id);
            $this->guardLastSuperAdmin($model->id, null);
            Yii::$app->authManager->revokeAll($model->id);
            $avatar = $model->avatar;
            $model->delete();
            SecureUpload::deleteAvatar($avatar);

            return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    private function guardLastSuperAdmin($userId, $newRole)
    {
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser($userId);
        if (isset($roles['superAdmin']) && $newRole !== 'superAdmin'
            && count($auth->getUserIdsByRole('superAdmin')) <= 1) {
            throw new \yii\web\BadRequestHttpException(
                Yii::t('app', 'The last super administrator cannot be removed or demoted.')
            );
        }
    }

    private function fallbackRole($userId)
    {
        $activeUsers = User::find()->where(['status' => User::STATUS_ACTIVE])->count();
        return $activeUsers === 1 && User::findOne($userId) !== null ? 'superAdmin' : 'editor';
    }

    private function saveProfile(User $model, $oldAvatar)
    {
        $newAvatar = null;
        if ($model->avatarFile !== null) {
            $newAvatar = SecureUpload::storeAvatar($model->avatarFile);
            $model->avatar = $newAvatar;
        } elseif ($model->removeAvatar) {
            $model->avatar = null;
        }

        if ($model->save(false)) {
            if (($newAvatar !== null || $model->removeAvatar) && $oldAvatar !== $model->avatar) {
                SecureUpload::deleteAvatar($oldAvatar);
            }
            return true;
        }

        if ($newAvatar !== null) {
            SecureUpload::deleteAvatar($newAvatar);
            $model->avatar = $oldAvatar;
        }
        return false;
    }
}

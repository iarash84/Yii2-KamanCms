<?php
namespace frontend\controllers;

use frontend\models\Blog;
use frontend\models\Carousel;
use frontend\models\Faqs;
use frontend\models\HomeSection;
use frontend\models\Opportunity;
use frontend\models\OpportunityForm;
use frontend\models\OrderForm;
use frontend\models\Sample;
use frontend\models\Setting;
use Yii;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use common\components\SecureUpload;
use frontend\components\PublicRateLimiter;
use frontend\services\NotificationService;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $homepageSettings = Setting::find()->with('translations')
            ->where(['type' => ['CompanyName', 'Home']])->indexBy('type')->all();
        $siteTitle = isset($homepageSettings['CompanyName'])
            ? trim((string) $homepageSettings['CompanyName']->getLocalizedContent())
            : '';
        $homeContent = isset($homepageSettings['Home'])
            ? trim((string) $homepageSettings['Home']->getLocalizedContent())
            : '';

        return $this->render('index', [
            'siteTitle' => $siteTitle !== '' ? $siteTitle : Yii::t('app', 'Website'),
            'homeContent' => $homeContent,
            'slides' => Carousel::find()->with('translations')->where(['status' => 1])
                ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all(),
            'sections' => HomeSection::find()->with('translations')->where(['status' => 1])
                ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all(),
            'portfolioItems' => Sample::find()->with('translations')
                ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->limit(3)->all(),
            'posts' => Blog::find()->with('translations')
                ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->limit(3)->all(),
            'faqs' => Faqs::find()->with('translations')->where(['status' => 1])
                ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->limit(4)->all(),
            'stats' => [
                'projects' => (int) Sample::find()->count(),
                'articles' => (int) Blog::find()->count(),
                'answers' => (int) Faqs::find()->where(['status' => 1])->count(),
            ],
        ]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Displays order page.
     *
     * @return mixed
     */
    public function actionOrder()
    {
        if (Yii::$app->request->isPost) {
            PublicRateLimiter::enforce('order');
        }
        $model = new OrderForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveOrder()) {
                NotificationService::formSubmitted('order', $model->attributes);
                Yii::$app->session->setFlash('success', Yii::t('app', 'Thank you for your request. We will respond to you as soon as possible.'));
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app','There was an error when adding your contact.'));
            }

            return $this->refresh();
        } else {
            return $this->render('order', [
                'model' => $model,
            ]);
        }
    }

    /**
     * @return string
     */
    public function actionSample(){

        $dataProvider = new ActiveDataProvider([
            'query' => Sample::find(),
        ]);

        return $this->render('sample' , [
            'dataProvider' => $dataProvider,
        ] );
    }

    /**
     * Displays opportunity page.
     *
     * @return mixed
     */
    public function actionOpportunity()
    {
        if (Yii::$app->request->isPost) {
            PublicRateLimiter::enforce('opportunity');
        }
        $model = new OpportunityForm();
        $model->resume = UploadedFile::getInstance($model, 'resume');
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $opportunity= new Opportunity();
            if (!empty($model->resume)) {
                $opportunity->resume = SecureUpload::storeResume($model->resume);
            }

            $opportunity->name = $model->name;
            $opportunity->email = $model->email;
            $opportunity->phone_number = $model->phoneNumber;
            if (!$opportunity->save()) {
                if ($opportunity->resume) {
                    $resumePath = Yii::getAlias('@storage/resumes/' . basename((string) $opportunity->resume));
                    if (is_file($resumePath)) {
                        unlink($resumePath);
                    }
                }
                Yii::error(['errors' => $opportunity->getErrors()], __METHOD__);
                Yii::$app->session->setFlash('error', Yii::t('app', 'Your request could not be saved. Please try again.'));
                return $this->render('opportunity', ['model' => $model]);
            }

            NotificationService::formSubmitted('opportunity', $model->attributes);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Your resume uploaded successfully. We will respond to you as soon as possible.'));

            return $this->refresh();
        } else {

            if(Yii::$app->user->isGuest) {

                return $this->render('opportunity', [
                    'model' => $model,
                ]);
            }else
            {
                return $this->redirect(['/admin/opportunity/index']);
            }
        }
    }

    /**
     * Displays FAQS page.
     *
     * @return string
     */
    public function actionFaqs()
    {
        $models = Faqs::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        return $this->render('faqs', ['models' => $models]);
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        if (Yii::$app->request->isPost) {
            PublicRateLimiter::enforce('contact');
        }
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveContact()) {
                NotificationService::formSubmitted('contact', $model->attributes);
                Yii::$app->session->setFlash('success', Yii::t('app','Thank you for contacting us. We will respond to you as soon as possible.'));
            } else {
                Yii::$app->session->setFlash('error', 'There was an error when adding your contact.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout(){
        if (Yii::$app->user->isGuest) {
            $model = Setting::find()->where(['type' => 'About'])->one();
            return $this->render('about',[
            'model' => $model,
        ]);
        } else {
            return $this->redirect(['/admin/setting/about']);
        }
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset(){

        if (Yii::$app->request->isPost) {
            PublicRateLimiter::enforce('password-reset', 3, 900);
        }

        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}

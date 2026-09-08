<?php

use frontend\widgets\Icon;

?>
<aside class="form-assurance" aria-label="<?= Yii::t('app', 'What happens next') ?>">
    <div class="form-assurance-heading">
        <span class="form-assurance-icon"><?= Icon::show('check') ?></span>
        <div><strong><?= Yii::t('app', 'What happens next') ?></strong><small><?= Yii::t('app', 'A clear, low-pressure first step') ?></small></div>
    </div>
    <ol>
        <li><span>1</span><?= Yii::t('app', 'We review your request and its context.') ?></li>
        <li><span>2</span><?= Yii::t('app', 'A specialist usually responds within one business day.') ?></li>
        <li><span>3</span><?= Yii::t('app', 'You decide whether the proposed next step is useful.') ?></li>
    </ol>
    <p><?= Icon::show('settings') ?><?= Yii::t('app', 'Your details are used to review and respond to this request.') ?></p>
</aside>
